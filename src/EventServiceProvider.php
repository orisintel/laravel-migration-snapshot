<?php


namespace OrisIntel\MigrationSnapshot;

final class EventServiceProvider extends \Illuminate\Foundation\Support\Providers\EventServiceProvider
{
    protected $listen = [
        // CONSIDER: Only registering these when Laravel version doesn't have
        // more specific hooks.
        'Illuminate\Console\Events\CommandFinished' => ['OrisIntel\MigrationSnapshot\Handlers\MigrateFinishedHandler'],
        'Illuminate\Console\Events\CommandStarting' => ['OrisIntel\MigrationSnapshot\Handlers\MigrateStartingHandler'],
    ];
}