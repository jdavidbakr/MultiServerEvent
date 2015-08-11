<?php

namespace jdavidbakr\MultiServerEvent\Scheduling;

use Illuminate\Console\Scheduling\Schedule as NativeSchedule;

class Schedule extends NativeSchedule {

    /**
     * Add a new command event to the schedule.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Console\Scheduling\Event
     */
    public function exec($command, array $parameters = [])
    {
        if (count($parameters)) {
            $command .= ' '.$this->compileParameters($parameters);
        }

        $this->events[] = $event = new Event($command);

        return $event;
    }
	
}