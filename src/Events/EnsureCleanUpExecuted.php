<?php

namespace jdavidbakr\MultiServerEvent\Events;

use Illuminate\Queue\SerializesModels;

/**
 * Class EnsureCleanUpExecuted
 * @package jdavidbakr\MultiServerEvent\Events
 */
class EnsureCleanUpExecuted
{
    use SerializesModels;
    /**
     * @var string
     */
    private $command;

    /**
     * EnsureCleanUpExecuted constructor.
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
