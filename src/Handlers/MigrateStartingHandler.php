<?php


namespace OrisIntel\MigrationSnapshot\Handlers;

use Illuminate\Console\Events\CommandStarting;
use OrisIntel\MigrationSnapshot\Commands\MigrateDumpCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;

class MigrateStartingHandler
{
    // CONSIDER: Supporting `--env`, `--force`, `--pretend`.
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

    /** Tokens not already defined as `DUMP_LOADABLE_OPTIONS` for validation. */
    private const OTHER_TOKENS = [
        // In order of appearance from "migrate --help".
        'migrate',
        '--force',
        '--path',
        '--realpath',
        '--pretend',
        '--seed',
        '--step',
        '-h',
        '--help',
        '-V',
        '--version',
        '--env',
        '-v',
        '-vv',
        '-vvv',
    ];

    public function handle(CommandStarting $event)
    {
        // CONSIDER: Never implicitly loading on `migrate` to simplify code
        // (dropping this whole class) and to avoid confusion.

        if (
            'migrate' === $event->command
            // Avoid knowingly starting migrate which will fail.
            && self::inputValidateWorkaround($event->input)
            && ! $event->input->hasParameterOption(['--help', '--pretend', '-V', '--version'])
            && env('MIGRATION_SNAPSHOT', true) // CONSIDER: Config option.
            // Never implicitly load fresh (from file) in production since it
            // would need to drop first, and that would be destructive.
            // CONSIDER: Making configurable blacklist of environments.
            && 'production' !== app()->environment()
            // No point in implicitly loading when it's not present.
            && file_exists(database_path() . MigrateDumpCommand::SCHEMA_SQL_PATH_SUFFIX)
        ) {
            // Must pass along options or it may use wrong DB or have
            // inconsistent output.
            $options = self::inputToArtisanOptions($event->input);
            $database = $options['--database'] ?? \DB::getConfig('name');
            $db_driver = \DB::getDriverName();
            if (! in_array($db_driver, MigrateDumpCommand::SUPPORTED_DB_DRIVERS, true)) {
                // CONSIDER: Logging or emitting console warning.
                return;
            }

            // Only implicitly load when DB has *not* migrated any since load
            // would wipe existing data.
            $has_migrated_any = false;
            // Try-catch instead of information_schema since not all have one.
            try {
                $has_migrated_any = ! is_null(
                    \DB::table('migrations')->value('id')
                );
            } catch (\PDOException $e) {
                // No op. when table does not exist.
                if (
                    ! in_array($e->getCode(), ['42P01', '42S02'], true)
                    && ! preg_match("/\b(does ?n[o']t exist|no such table)\b/iu", $e->getMessage())
                ) {
                    throw $e;
                }
            }
            if ($has_migrated_any) {
                return;
            }

            // CONSIDER: Defaulting to --no-drop when not explicitly specified
            // with environment variable, for extra safety.
            // CONSIDER: Explicitly passing output class (since underlying
            // command classes may not always use `passthru`).
            \Artisan::call('migrate:load', $options, $event->output);
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
            } elseif (false !== $input->getParameterOption($option)) {
                $options[$option] = $input->getParameterOption($option);
            }
        }

        return $options;
    }

    /**
     * @param ArgvInput|InputInterface $input
     *
     * @throws \RuntimeException when input is invalid.
     * @throws \ReflectionException
     *
     * @return bool true when valid or workaround is unnecessary.
     */
    private static function inputValidateWorkaround($input) : bool
    {
        // Workaround unnecessary (or non-functional) when not exactly `ArgvInput`.
        if (get_class($input) != ArgvInput::class) {
            return true;
        }
        // Since `$input->validate()` isn't working at this point check against
        // known tokens for `migrate`.
        $reflection = new \ReflectionProperty(get_class($input), 'tokens');
        $reflection->setAccessible(true);
        $tokens = $reflection->getValue($input);
        $valid_names = array_merge(array_keys(self::DUMP_LOADABLE_OPTIONS), self::OTHER_TOKENS);
        foreach ($tokens as $token) {
            $name = explode('=', $token, 2)[0];
            if (! in_array($name, $valid_names, true)) {
                throw new \RuntimeException('The "' . $token . '" option does not exist.');
            }
        }

        return true;
    }
}