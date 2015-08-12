<?php

namespace jdavidbakr\MultiServerEvent\Scheduling;

use Illuminate\Console\Scheduling\Event as NativeEvent;
use Illuminate\Contracts\Container\Container;
use DB;

class Event extends NativeEvent {

	/**
	 * Hash we will use to identify our server and lock the process
	 * @var string
	 */
	protected $server_id;
	/**
	 * Hash of the command mutex we will use to uniquely identify the command
	 * @var string
	 */
	protected $key;
	/**
	 * The database connection for the multi_server_event table
	 * @var string
	 */
	public $connection = null;
	/**
	 * The name of the table that contains the process locks
	 * @var string
	 */
	public $lock_table = 'multi_server_event';

    /**
     * Create a new event instance.
     *
     * @param  string  $command
     * @return void
     */
    public function __construct($command)
    {
    	parent::__construct($command);
		$this->server_id = str_random(32);
    }

    /**
     * Run the given event, then clear our lock
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function run(Container $container)
    {
    	parent::run($container);
		$this->clearMultiserver();
	}

	/**
	 * Prevents this command from executing across multiple servers attempting
	 * at the same time
	 * @return [type] [description]
	 */
	public function withoutOverlappingMultiServer()
	{
		return $this->skip(function() {
			return $this->skipMultiserver();
		});
	}

	/**
	 * Attempt to lock this command 
	 * @return bool true if we want to skip
	 */
	public function skipMultiserver()
	{
		$this->key = md5($this->mutexPath());
		// Delete any old completed runs that are more than 10 seconds ago
		DB::connection($this->connection)
			->delete('delete from `'.$this->lock_table.'` where `mutex` = ? and complete < now() - interval 10 second',
				[$this->key]);
		// Attempt to acquire the lock
		DB::connection($this->connection)
			->insert('insert ignore into `'.$this->lock_table.'` set `mutex` = ?, `lock` = ?, `start` = now()',
				[$this->key, $this->server_id]);
		// If the mutex already exists in the table, the above query will fail silently.
		// Now we will perform a select to see if we got the lock or not.
		$lock = DB::connection($this->connection)
			->select('select `lock` from `'.$this->lock_table.'` where mutex = ?',
				[$this->key]);
		if($lock[0]->lock == $this->server_id) {
			// We got the lock
			return false;
		} else {
			// Someone else has the lock
			return true;			
		}
	}

	/**
	 * Delete our locks
	 * @return void 
	 */
	public function clearMultiserver()
	{
		// Clear the lock
		if($this->server_id) {
			DB::connection($this->connection)
				->update('update `'.$this->lock_table.'` set complete = now() where `mutex` = ? and `lock` = ?',
					[$this->key, $this->server_id]);
		}
	}
	
}
