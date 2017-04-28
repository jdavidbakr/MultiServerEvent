<?php

use Orchestra\Testbench\TestCase;


class MultiServerEventTest extends TestCase {

    /**
     * @test
     */
    public function it_initializes_the_schedule()
    {
        $schedule = new jdavidbakr\MultiServerEvent\Scheduling\Schedule(app()[\Illuminate\Contracts\Cache\Repository::class]);
        $this->assertTrue($schedule instanceof jdavidbakr\MultiServerEvent\Scheduling\Schedule);

        $schedule->command('inspire')
            ->daily()
            ->withoutOverlappingMultiserver();
    }

}