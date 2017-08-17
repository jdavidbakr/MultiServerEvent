<?php

namespace jdavidbakr\MultiServerEvent\Scheduling;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\CacheMutex;
use Illuminate\Console\Scheduling\Event as NativeEvent;
use Illuminate\Support\Facades\DB;

class Event extends NativeEvent
{
    /**
     * Hash we will use to identify our server and lock the process.
     * @var string
     */
    protected $server_id;

    /**
     * Hash of the command mutex we will use to uniquely identify the command.
     * @var string
     */
    protected $key;

    /**
     * The database connection for the multi_server_event table.
     * @var string
     */
    public $connection = null;

    /**
     * The name of the table that contains the process locks.
     * @var string
     */
    public $lock_table = 'multi_server_event';

    /**
     * Create a new event instance.
     *
     * @param CacheMutex $cacheMutex
     * @param  string $command
     * @throws \RuntimeException
     */
    public function __construct(CacheMutex $cacheMutex, $command)
    {
        parent::__construct($cacheMutex, $command);
        $this->server_id = str_random(32);
        $this->then(function() {
            $this->clearMultiserver();
        });
    }

    /**
     * Prevents this command from executing across multiple servers attempting
     * at the same time.
     * @return $this
     */
    public function withoutOverlappingMultiServer()
    {
        return $this->skip(function() {
            return $this->skipMultiserver();
        });
    }

    /**
     * Attempt to lock this command.
     * @return bool true if we want to skip
     */
    public function skipMultiserver()
    {
        $this->key = $this->getKey();

        // Delete any old completed runs that are more than 10 seconds ago
        $next = $this->nextRunDate();
        DB::connection($this->connection)
            ->table($this->lock_table)
            ->where('mutex', $this->key)
            ->where('complete', '<', Carbon::now()->subSeconds(10))
            ->where('next', '<', $next)
            ->delete();

        // Attempt to acquire the lock
        $gotLock = true;
        try {
            DB::connection($this->connection)
                ->table($this->lock_table)
                ->insert([
                    'mutex' => $this->key,
                    'lock' => $this->server_id,
                    'start' => Carbon::now(),
                    'next' => $next,
                ]);
        } catch (\PDOException $e) {
            // Catch the PDOException to fail silently because the query builder does not support INSERT IGNORE
            $gotLock = false;
        }

        return $gotLock === false;
    }

    /**
     * @return string
     */
    private function getKey()
    {
        return md5($this->expression . $this->command);
    }

    /**
     * Prevents this command to be dead forever when not succeeded to close cron session as it takes too long to be executing
     * @param int $minutes
     * @return $this
     */
    public function ensureFinishedMultiServer($minutes)
    {
        return $this->when(function() use ($minutes) {
            return $this->ensureFinished($minutes);
        });
    }

    /**
     * Attempt to lock this command.
     * @param int $minutes
     * @return bool true if we want to skip
     * @throws \RuntimeException
     */
    public function ensureFinished($minutes)
    {
        $this->key = $this->getKey();

        // Finish any uncompleted command which runs for more than restricted minutes
        $result = DB::connection($this->connection)
            ->table($this->lock_table)
            ->where('mutex', $this->key)
            ->where('start', '<', Carbon::now()->subMinutes($minutes))
            ->whereNull('complete')
            ->update([
                'complete' => Carbon::now(),
            ]);

        return true;
    }

    /**
     * Delete our locks.
     * @return void
     */
    public function clearMultiserver()
    {
        // Clear the lock
        if ($this->server_id) {
            DB::connection($this->connection)
                ->table($this->lock_table)
                ->where('mutex', $this->key)
                ->where('lock', $this->server_id)
                ->update(['complete' => Carbon::now()]);
        }
    }
}
