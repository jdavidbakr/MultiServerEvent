# Multi Server Event

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

This package extends Laravel's native Event class to include the ability to block events from occurring aross multiple servers, as would be the case if you have a laravel instance behind a load balancer in an auto-scaling situation.

It uses a database table to track the currently running process, and each server generates a unique key to lock the command. In order to prevent a condition where a short-running command's lock doesn't last long enough, we are implementing a minimum 10 second break between the completion of the command and its next execution time, so if a command runs every minute but takes between 50 and 59 seconds to complete, the next command will be delayed one more minute.

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

[ico-version]: https://img.shields.io/packagist/v/jdavidbakr/multi-server-event.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/jdavidbakr/multi-server-event.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/jdavidbakr/multi-server-event
[link-downloads]: https://packagist.org/packages/jdavidbakr/multi-server-event
[link-author]: https://github.com/jdavidbakr
[link-contributors]: ../../contributors
