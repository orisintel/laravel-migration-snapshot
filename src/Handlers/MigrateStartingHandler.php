<?php


namespace OrisIntel\MigrationSnapshot\Handlers;

use Illuminate\Console\Events\CommandStarting;
use Symfony\Component\Console\Input\InputInterface;

class MigrateStartingHandler
{
    // CONSIDER: Supporting `--env` and `--force`.
    private const DUMP_LOADABLE_OPTIONS = [
        '--ansi'           => 'bool',
        '--database'       => 'string',
        '-n'               => 'bool',
        '--no-ansi'        => 'bool',
        '--no-interaction' => 'bool',
        '-q'               => 'bool',
        '--quiet'          => 'bool',
        '-v'               => 'bool',
        '--verbose'        => 'bool',
        '-vv'              => 'bool',
        '-vvv'             => 'bool',
    ];

    public function handle(CommandStarting $event)
    {
        if (
            'migrate' === $event->command
            && $event->input->validate()
            && ! $event->input->hasParameterOption(['--help', '--pretend'])
            && env('MIGRATION_SNAPSHOT', true) // CONSIDER: Config option.
            // Never implicitly load fresh (from file) in production since it
            // would need to drop first, and that would be destructive.
            // CONSIDER: Making configurable blacklist of environments.
            && 'production' !== app()->environment()
        ) {
            $options = self::inputToArtisanOptions($event->input);
            // CONSIDER: Defaulting to --no-drop when not explicitly specified
            // with environment variable, for extra safety.
            \Artisan::call('migrate:load', $options);
        }
    }

    /** @return array of `Artisan::call` compatible options like ['-v' => true]. */
    public static function inputToArtisanOptions(InputInterface $input) : array
    {
        $options = [];
        foreach (self::DUMP_LOADABLE_OPTIONS as $option => $type) {
            if ('bool' === $type) {
                // CONSIDER: Avoiding repetitive "-v" when "-vv" or "-vvv".
                if (false !== $input->getParameterOption($option)) {
                    $options[$option] = true;
                }
            } else {
                $input->getParameterOption($option);
            }
        }

        return $options;
    }
}