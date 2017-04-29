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
        return $this->skip(function () {
            return $this->skipMultiserver();
        });
    }

    /**
     * Attempt to lock this command.
     * @return bool true if we want to skip
     */
    public function skipMultiserver()
    {
        $this->key = md5($this->expression.$this->command);

        // Delete any old completed runs that are more than 10 seconds ago
        DB::connection($this->connection)
            ->table($this->lock_table)
            ->where('mutex', $this->key)
            ->where('complete', '<', Carbon::now()->subSeconds(10))
            ->delete();

        // Attempt to acquire the lock
        $gotLock = true;
        try {
            DB::connection($this->connection)
                ->table($this->lock_table)
                ->insert([
                    'mutex' => $this->key,
                    'lock'  => $this->server_id,
                    'start' => Carbon::now()
                ]);
        } catch (\PDOException $e) {
            // Catch the PDOException to fail silently because the query builder does not support INSERT IGNORE
            $gotLock = false;
        }

        return $gotLock === false;
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
