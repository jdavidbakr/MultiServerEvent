# Multi Server Event

This package extends Laravel's native Event class to include the ability to block events from occurring aross multiple servers, as would be the case if you have a laravel instance behind a load balancer in an auto-scaling situation.

## Installation


```
$ composer require jdavidbakr/multi-server-event
```

The new event structure uses a database table to track which server is currently executing an event. You must create the database table using the provided migration.  We also will need to register our new "run" command.  To do this, add the following to the $commands array in \App\Console\Kernel.php:

```
\jdavidbakr\MultiServerEvent\Commands\MultiServerMigrationService::class,
\jdavidbakr\MultiServerEvent\Commands\ScheduleRunCommand::class,
```

then perform the migration

```
php artisan make:migration:multi-server-event
```

Even though we've replaced our "run" command above, we still need to tell the Kernel to use our new class when executing our cron jobs.  In app\Console\Kernel.php, change the class we will use for the schedule:

```
replace:

use Illuminate\Console\Scheduling\Schedule;

with:

use jdavidbakr\MultiServerEvent\Scheduling\Schedule;
```

Then, add the following function to override its default:

```
/**
 * Define the application's command schedule.
 *
 * @return void
 */
protected function defineConsoleSchedule()
{
    $this->app->instance(
        'jdavidbakr\MultiServerEvent\Scheduling\Schedule', $schedule = new Schedule
    );

    $this->schedule($schedule);
}
```

## Usage

When composing your schedule, simply add "withoutOverlappingMultiServer()" to the command, i.e.

```
$schedule->command('inspire')
		->daily()
		->withoutOverlappingMultiServer();
```

This will prevent multiple servers from executing the same event at the same time.