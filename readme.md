# Multi Server Event

This package extends Laravel's native Event class to include the ability to block events from occurring aross multiple servers, as would be the case if you have a laravel instance behind a load balancer in an auto-scaling situation.

## Installation


```
$ composer require jdavidbakr/multi-server-event
```

The new event structure uses a database table to track which server is currently executing an event. You must create the database table using the provided migration.  To do this, add the following to the $commands array in \App\Console\Kernel.php:

```
\jdavidbakr\MultiServerEvent\Commands\MultiServerMigrationService::class,
```

then perform the migration

```
php artisan make:migration:multi-server-event
php artisan migrate
```

Now we want to change the default schedule IoC to use this alternate one.  In app\Console\Kernel.php add the following function:

```
/**
 * Define the application's command schedule.
 *
 * @return void
 */
protected function defineConsoleSchedule()
{
		$this->app->instance(
            'Illuminate\Console\Scheduling\Schedule', $schedule = new \jdavidbakr\MultiServerEvent\Scheduling\Schedule
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