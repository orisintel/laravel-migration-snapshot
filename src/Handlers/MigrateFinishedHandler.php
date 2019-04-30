<?php


namespace OrisIntel\MigrationSnapshot\Handlers;

use Illuminate\Console\Events\CommandFinished;
use OrisIntel\MigrationSnapshot\Commands\MigrateDumpCommand;

class MigrateFinishedHandler
{
    public function handle(CommandFinished $event)
    {
        if (
            'migrate' === $event->command // CONSIDER: Also `migrate:fresh`.
            && ! $event->input->hasParameterOption(['--help', '--pretend', '-V', '--version'])
            && env('MIGRATION_SNAPSHOT', true)
            && in_array(app()->environment(), explode(',', config('migration-snapshot.environments')), true)
        ) {
            $options = MigrateStartingHandler::inputToArtisanOptions($event->input);
            $database = $options['--database'] ?? env('DB_CONNECTION');
            $db_driver = \DB::connection($database)->getDriverName();
            if (! in_array($db_driver, MigrateDumpCommand::SUPPORTED_DB_DRIVERS, true)) {
                return;
            }

            // CONSIDER: Only calling when at least one migration applied.
            \Artisan::call('migrate:dump', $options, $event->output);
        }
    }
}
