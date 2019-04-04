<?php

namespace OrisIntel\MigrationSnapshot\Commands;

final class MigrateDumpCommand extends \Illuminate\Console\Command
{
    public const SCHEMA_SQL_PATH_SUFFIX = '/migrations/sql/schema.sql';
    public const SUPPORTED_DB_DRIVERS = ['mysql', 'pgsql'];

    protected $signature = 'migrate:dump
        {--database= : The database connection to use}';

    protected $description = 'Dump current database schema/structure as plain-text SQL file.';

    public function handle()
    {
        $exit_code = null;

        $database = $this->option('database') ?: \DB::getDefaultConnection();
        \DB::setDefaultConnection($database);
        $db_config = \DB::getConfig();

        // CONSIDER: Ending with ".mysql" or "-mysql.sql" unless in
        // compatibility mode.
        $schema_sql_path = database_path() . self::SCHEMA_SQL_PATH_SUFFIX;
        $schema_sql_directory = dirname($schema_sql_path);
        if (! file_exists($schema_sql_directory)) {
            mkdir($schema_sql_directory, 0755);
        }

        if (! in_array($db_config['driver'], self::SUPPORTED_DB_DRIVERS, true)) {
            throw new \InvalidArgumentException(
                'Unsupported DB driver ' . var_export($db_config['driver'], 1)
            );
        }

        // Delegate to driver-specific dump CLI command since their output is
        // faster and more accurate than Laravel's schema DSL.
        // ASSUMES: Dump utilities for DBMS installed and in path.
        // CONSIDER: Accepting options for underlying dump utilities from CLI.
        // CONSIDER: Option to dump to console Stdout instead.
        // CONSIDER: Option to dump for each DB connection instead of only one.
        // CONSIDER: Separate classes.
        $method = $db_config['driver'] . 'Dump';
        $exit_code = self::{$method}($db_config, $schema_sql_path);

        if (0 !== $exit_code) {
            // Do not leave possibly incomplete file since loading it could
            // leave an incomplete DB with no sign of a problem.
            if (file_exists($schema_sql_path)) {
                unlink($schema_sql_path);
            }
            exit($exit_code); // CONSIDER: Returning instead.
        }

        $this->info('Dumped schema');
    }

    /**
     * @param array  $db_config   like ['host' => , 'port' => ].
     * @param string $schema_sql_path like '.../schema.sql'
     *
     * @return int containing exit code.
     */
    private static function mysqlDump(array $db_config, string $schema_sql_path) : int
    {
        // CONSIDER: Supporting unix_socket.
        // CONSIDER: Alternative tools like `xtrabackup` or even just querying
        // "SHOW CREATE TABLE" via Eloquent.
        // CONSIDER: Capturing Stderr and outputting with `$this->error()`.

        // Not including connection name in file since typically only one DB.
        // Excluding any hash or date suffix since only current is relevant.
        $command_prefix = 'mysqldump --routines --skip-add-drop-table'
            . ' --skip-add-locks --skip-comments --skip-set-charset --tz-utc'
            . ' --host=' . escapeshellarg($db_config['host'])
            . ' --port=' . escapeshellarg($db_config['port'])
            . ' --user=' . escapeshellarg($db_config['username'])
            . ' --password=' . escapeshellarg($db_config['password'])
            . ' ' . escapeshellarg($db_config['database']);
        passthru(
            $command_prefix
            . ' --result-file=' . escapeshellarg($schema_sql_path)
            . ' --no-data',
            $exit_code
        );
        if (0 !== $exit_code) {
            return $exit_code;
        }

        $schema_sql = file_get_contents($schema_sql_path);
        if (false === $schema_sql) {
            return 1;
        }
        $schema_sql = preg_replace('/(\b)AUTO_INCREMENT=[0-9]+/iu', '\1', $schema_sql);
        if (false === file_put_contents($schema_sql_path, $schema_sql)) {
            return 1;
        }

        // Include migration rows to avoid unnecessary reruns conflicting.
        // CONSIDER: How this could be done as consistent snapshot with
        // dump of structure, and avoid duplicate "SET" comments.
        passthru(
            $command_prefix . ' migrations --no-create-info --skip-extended-insert >> '
                . escapeshellarg($schema_sql_path),
            $exit_code
        );

        return $exit_code;
    }

    /**
     * @param array $db_config like ['host' => , 'port' => ].
     *
     * @return int containing exit code.
     */
    private static function pgsqlDump(array $db_config, string $schema_sql_path) : int
    {
        // CONSIDER: Supporting unix_socket.
        // CONSIDER: Instead querying pg catalog tables via Eloquent.
        // CONSIDER: Capturing Stderr and outputting with `$this->error()`.

        // CONSIDER: Instead using DSN-like URL instead of env. var. for pass.
        $command_prefix = 'PGPASSWORD=' . escapeshellarg($db_config['password'])
            . ' pg_dump'
            . ' --host=' . escapeshellarg($db_config['host'])
            . ' --port=' . escapeshellarg($db_config['port'])
            . ' --username=' . escapeshellarg($db_config['username'])
            . ' ' . escapeshellarg($db_config['database']);
        passthru(
            $command_prefix
            . ' --file=' . escapeshellarg($schema_sql_path)
            . ' --schema-only',
            $exit_code
        );
        if (0 !== $exit_code) {
            return $exit_code;
        }

        // Include migration rows to avoid unnecessary reruns conflicting.
        // CONSIDER: How this could be done as consistent snapshot with
        // dump of structure, and avoid duplicate "SET" comments.
        passthru(
            $command_prefix . ' --table=migrations --data-only --inserts >> '
                . escapeshellarg($schema_sql_path),
            $exit_code
        );

        return $exit_code;
    }
}