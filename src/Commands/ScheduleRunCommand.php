<?php

namespace jdavidbakr\MultiServerEvent\Commands;

use Illuminate\Console\Scheduling\ScheduleRunCommand as Command;

class ScheduleRunCommand extends Command
{

    public function __construct(\jdavidbakr\MultiServerEvent\Scheduling\Schedule $schedule)
    {
        $this->schedule = $schedule;

        parent::__construct($schedule);
    }

}