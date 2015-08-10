# Multi Server Event

This package extends Laravel's native Event class to include the ability to block events from occurring aross multiple servers.

## Installation


```
$ composer require jdavidbakr/multi-server-event
```

The new event structure uses a database table to track which server is currently executing an event. You must create the database table using the provided migration.  To do this, add the following to the $commands array in \App\Console\Kernel:

```
'jdavidbakr\MultiServerEvent\Commands\MultiServerMigrationServiceProvider',
```

then perform the migration

```
php artisan make:migration:multi-server-event
```
