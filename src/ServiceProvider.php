<?php

namespace OrisIntel\MigrationSnapshot;

use OrisIntel\MigrationSnapshot\Commands\MigrateDumpCommand;
use OrisIntel\MigrationSnapshot\Commands\MigrateLoadCommand;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrateDumpCommand::class,
                MigrateLoadCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->app->register(EventServiceProvider::class);
    }
}