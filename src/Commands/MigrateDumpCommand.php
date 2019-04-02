<?php

namespace OrisIntel\MigrationSnapshot\Commands;

final class MigrateDumpCommand extends \Illuminate\Console\Command
{
    public const SCHEMA_MIGRATIONS_PATH = '/migrations/sql/schema-and-migrations.sql';

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
        $result_file = database_path() . self::SCHEMA_MIGRATIONS_PATH;
        $result_dir = dirname($result_file);
        if (! file_exists($result_dir)) {
            mkdir($result_dir, 0755);
        }

        // Delegate to driver-specific dump CLI command.
        // ASSUMES: Dump utilities for DBMS installed and in path.
        // CONSIDER: Accepting options for underlying dump utilities from CLI.
        // CONSIDER: Option to dump to console Stdout instead.
        // CONSIDER: Option to dump for each DB connection instead of only one.
        // CONSIDER: Separate classes.
        switch($db_config['driver']) {
        case 'mysql':
            $exit_code = self::mysqlDump($db_config, $result_file);
            break;
        case 'pgsql':
            $exit_code = self::pgsqlDump($db_config, $result_file);
            break;
        default:
            throw new \InvalidArgumentException(
                'Unsupported DB driver ' . var_export($db_config['driver'], 1)
            );
        }

        if (0 !== $exit_code) {
            exit($exit_code);
        }
    }

    /**
     * @param array  $db_config   like ['host' => , 'port' => ].
     * @param string $result_file like '.../schema-and-migrations.sql'
     *
     * @return int containing exit code.
     */
    private static function mysqlDump(array $db_config, string $result_file) : int
    {
        // CONSIDER: Supporting unix_socket.
        // CONSIDER: Alternative tools like `xtrabackup` or even just querying
        // "SHOW CREATE TABLE" via Eloquent.
        // CONSIDER: Capturing Stderr and outputting with `$this->error()`.

        // Not including connection name in file since typically only one DB.
        // Excluding any hash or date suffix since only current is relevant.
        $command_prefix = 'mysqldump --compact --routines --tz-utc'
            . ' --host=' . escapeshellarg($db_config['host'])
            . ' --port=' . escapeshellarg($db_config['port'])
            . ' --user=' . escapeshellarg($db_config['username'])
            . ' --password=' . escapeshellarg($db_config['password'])
            . ' ' . escapeshellarg($db_config['database']);
        passthru(
            $command_prefix
            . ' --result-file=' . escapeshellarg($result_file)
            . ' --no-data',
            $exit_code
        );

        // Include migration rows to avoid unnecessary reruns conflicting.
        if (0 === $exit_code) {
            // CONSIDER: How this could be done as consistent snapshot with
            // dump of structure, and avoid duplicate "SET" comments.
            passthru(
                $command_prefix . ' migrations --no-create-info --skip-extended-insert >> ' . escapeshellarg($result_file),
                $exit_code
            );
        }

        return $exit_code;
    }

    /**
     * @param array $db_config like ['host' => , 'port' => ].
     *
     * @return int containing exit code.
     */
    private static function pgsqlDump(array $db_config, string $result_file) : int
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
            . ' --dbname=' . escapeshellarg($db_config['database']);
        passthru(
            $command_prefix
            . ' --file=' . escapeshellarg($result_file)
            . ' --schema-only',
            $exit_code
        );

        // Include migration rows to avoid unnecessary reruns conflicting.
        if (0 === $exit_code) {
            // CONSIDER: How this could be done as consistent snapshot with
            // dump of structure, and avoid duplicate "SET" comments.
            passthru(
                $command_prefix . ' --table=migrations --data-only --inserts >> ' . escapeshellarg($result_file),
                $exit_code
            );
        }

        return $exit_code;
    }
}