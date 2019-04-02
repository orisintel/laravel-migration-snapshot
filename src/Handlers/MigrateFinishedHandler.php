<?php


namespace OrisIntel\MigrationSnapshot\Handlers;

use Illuminate\Console\Events\CommandFinished;

class MigrateFinishedHandler
{
    public function handle(CommandFinished $event)
    {
        if (
            'migrate' === $event->command
            && $event->input->validate()
            && ! $event->input->hasParameterOption(['--help', '--pretend'])
            && env('MIGRATION_SNAPSHOT', true)
            // CONSIDER: Making configurable blacklist of environments.
            && 'production' !== app()->environment()
        ) {
            $options = MigrateStartingHandler::inputToArtisanOptions($event->input);
            // CONSIDER: Only calling when at least one migration applied.
            \Artisan::call('migrate:dump', $options);
        }
    }
}