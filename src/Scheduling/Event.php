<?php

namespace jdavidbakr\MultiServerEvent\Scheduling;

use Illuminate\Console\Scheduling\Event as NativeEvent;
use DB;

class Event extends NativeEvent {

	public $withoutOverlappingMultiserver = false;
	private $server_id;
	public $connection = null;

	public function buildCommand()
	{
		$response = parent::buildCommand();
		$this->clearMultiserver();
		return $response;
	}

	public function withoutOverlappingMultiServer()
	{
		$this->withoutOverlappingMultiserver = true;
		return $this->skip(function() {
			return $this->skipMultiserver();
		});
	}

	public function skipMultiserver()
	{
		$key = md5($this->mutexPath());
		$this->server_id = str_random(32);
		// Attempt to acquire the lock
		DB::connection($this->connection)
			->insert('insert ignore into multi_server_event set `mutex` = ?, `lock` = ?, `start` = now()',
				[$key, $this->server_id]);
		// If the mutex already exists in the table, the above query will fail silently.
		// We can perform a select to see if we got the lock or not.
		$lock = DB::connection($this->connection)
			->select('select `lock` from multi_server_event where mutex = ?',
				[$key]);
		if($lock[0]->lock == $this->server_id) {
			// We got the lock
			return false;
		} else {
			// Someone else has the lock
			return true;			
		}
	}

	public function clearMultiserver()
	{
		// Clear the lock
		if($this->server_id) {
			DB::connection($this->connection)
				->delete('delete from multi_server_event where `lock` = ?',
					[$this->server_id]);
		}
	}
	
}
