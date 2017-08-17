<?php

namespace jdavidbakr\MultiServerEvent\Events;

use Illuminate\Queue\SerializesModels;

class EnsureCleanUpExecuted
{
    use SerializesModels;
    private $command;

    /**
     * Deletion constructor.
     * @param string $command
     */
    public function __construct($command)
    {
        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }
}
