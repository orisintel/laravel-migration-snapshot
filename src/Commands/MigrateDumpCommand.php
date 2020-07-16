<?php

namespace OrisIntel\MigrationSnapshot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class MigrateDumpCommand extends Command
{
    public const SCHEMA_SQL_PATH_SUFFIX = '/migrations/sql/schema.sql';
    public const DATA_SQL_PATH_SUFFIX = '/migrations/sql/data.sql';

    public const SUPPORTED_DB_DRIVERS = ['mysql', 'pgsql', 'sqlite'];

    protected $signature = 'migrate:dump
        {--database= : The database connection to use}
        {--include-data : Include data present in the tables that was created via migrations. }
        ';

    protected $description = 'Dump current database schema/structure as plain-text SQL file.';

    public function handle()
    {
        $exit_code = null;

        $database = $this->option('database') ?: DB::getDefaultConnection();
        DB::setDefaultConnection($database);
        $db_config = DB::getConfig();

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
        $method = $db_config['driver'] . 'SchemaDump';
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

        $data_sql_path = null;
        if ($this->option('include-data')) {
            $this->info('Starting Data Dump');

            $data_sql_path = database_path() . self::DATA_SQL_PATH_SUFFIX;

            $method = $db_config['driver'] . 'DataDump';
            $exit_code = self::{$method}($db_config, $data_sql_path);

            if (0 !== $exit_code) {
                if (file_exists($data_sql_path)) {
                    unlink($data_sql_path);
                }

                exit($exit_code);
            }
        }

        $this->info('Dumped Data');

        $after_dump = config('migration-snapshot.after-dump');
        if ($after_dump) {
            $after_dump($schema_sql_path, $data_sql_path);
            $this->info('Ran After-dump');
        }
    }

    /**
     * @param array $output
     *
     * @return array
     */
    public static function reorderMigrationRows(array $output) : array
    {
        if (config('migration-snapshot.reorder')) {
            $reordered = [];
            foreach ($output as $line) {
                // Extract parts of "INSERT ... VALUES ([id],'[ver]',[batch])
                // where version begins with "YYYY_MM_DD_HHMMSS".
                $occurrences = preg_match(
                    "/^(.*?VALUES\s*)\([0-9]+,\s*'([0-9_]{17})(.*?),\s*[0-9]+\s*\)\s*;\s*$/iu",
                    $line,
                    $m
                );
                if (1 !== $occurrences) {
                    throw new \UnexpectedValueException(
                        'Only insert rows supported:' . PHP_EOL . var_export($line, 1)
                    );
                }
                // Reassemble parts with new values and index by timestamp of
                // version string to sort.
                $reordered[$m[2]] = "$m[1](/*NEWID*/,'$m[2]$m[3],0);";
            }
            ksort($reordered);
            $reordered = array_values($reordered);
            foreach ($reordered as $index => &$line) {
                $line = str_replace('/*NEWID*/', $index + 1, $line);
            }

            return $reordered;
        }

        return $output;
    }

    /**
     * @param array  $db_config   like ['host' => , 'port' => ].
     * @param string $schema_sql_path like '.../schema.sql'
     *
     * @return int containing exit code.
     */
    private static function mysqlSchemaDump(array $db_config, string $schema_sql_path) : int
    {
        // TODO: Suppress warning about insecure password.
        // CONSIDER: Intercepting stdout and stderr and converting to colorized
        // console output with `$this->info` and `->error`.
        passthru(
            static::mysqlCommandPrefix($db_config)
            . ' --result-file=' . escapeshellarg($schema_sql_path)
            . ' --no-data'
            . ' --routines',
            $exit_code
        );

        if (0 !== $exit_code) {
            return $exit_code;
        }

        $schema_sql = file_get_contents($schema_sql_path);
        if (false === $schema_sql) {
            return 1;
        }

        $schema_sql = preg_replace('/\s+AUTO_INCREMENT=[0-9]+/iu', '', $schema_sql);
        $schema_sql = self::trimUnderscoresFromForeign($schema_sql);
        if (false === file_put_contents($schema_sql_path, $schema_sql)) {
            return 1;
        }

        // Include migration rows to avoid unnecessary reruns conflicting.
        exec(
            static::mysqlCommandPrefix($db_config)
                . ' migrations'
                . ' --no-create-info'
                . ' --skip-extended-insert'
                . ' --skip-routines'
                . ' --single-transaction'
                . ' --compact',
            $output,
            $exit_code
        );

        if (0 !== $exit_code) {
            return $exit_code;
        }

        $output = self::reorderMigrationRows($output);

        // Append reordered rows, and include a line break to make SCM diffs
        // easier to read.
        file_put_contents(
            $schema_sql_path,
            implode(PHP_EOL, $output) . PHP_EOL,
            FILE_APPEND
        );

        return $exit_code;
    }

    /**
     * @param array  $db_config   like ['host' => , 'port' => ].
     * @param string $data_sql_path like '.../data.sql'
     *
     * @return int containing exit code.
     */
    private static function mysqlDataDump(array $db_config, string $data_sql_path) : int
    {
        passthru(
            static::mysqlCommandPrefix($db_config)
            . ' --result-file=' . escapeshellarg($data_sql_path)
            . ' --no-create-info --skip-routines --skip-triggers'
            . ' --ignore-table=' . escapeshellarg($db_config['database'] . '.migrations')
            . ' --single-transaction', // Avoid disruptive locks.
            $exit_code
        );

        if (0 !== $exit_code) {
            return $exit_code;
        }

        $data_sql = file_get_contents($data_sql_path);
        if (false === $data_sql) {
            return 1;
        }

        $data_sql = preg_replace('/\s+AUTO_INCREMENT=[0-9]+/iu', '', $data_sql);
        if (false === file_put_contents($data_sql_path, $data_sql)) {
            return 1;
        }

        return $exit_code;
    }

    /**
     * @param array $db_config
     *
     * @return string
     */
    private static function mysqlCommandPrefix(array $db_config) : string
    {
        // CONSIDER: Supporting unix_socket.
        // CONSIDER: Alternative tools like `xtrabackup` or even just querying
        // "SHOW CREATE TABLE" via Eloquent.
        // CONSIDER: Capturing Stderr and outputting with `$this->error()`.

        // Not including connection name in file since typically only one DB.
        // Excluding any hash or date suffix since only current is relevant.

        return 'mysqldump --skip-add-drop-table'
            . ' --skip-add-locks --skip-comments --skip-set-charset --tz-utc --set-gtid-purged=OFF'
            . ' --host=' . escapeshellarg($db_config['host'])
            . ' --port=' . escapeshellarg($db_config['port'])
            . ' --user=' . escapeshellarg($db_config['username'])
            . ' --password=' . escapeshellarg($db_config['password'])
            . ' ' . escapeshellarg($db_config['database']);
    }

    /**
     * Trim underscores from FK constraint names to workaround PTOSC quirk.
     *
     * @param string $sql like "CONSTRAINT _my_fk FOREIGN KEY ..."
     *
     * @return string without leading underscores like "CONSTRAINT my_fk ...".
     */
    public static function trimUnderscoresFromForeign(string $sql) : string
    {
        if (! config('migration-snapshot.trim-underscores')) {
            return $sql;
        }

        $trimmed = preg_replace(
            '/(^|,)(\s*CONSTRAINT\s+[`"]?)_+(.*?[`"]?\s+FOREIGN\s+KEY\b.*)/imu',
            '\1\2\3',
            $sql
        );

        // Reorder constraints for consistency since dump put underscored first.
        $offset = 0;
        // Sort each adjacent block of constraints.
        while (preg_match('/((?:^|,)?\s*CONSTRAINT\s+.*?(?:,|\)\s*\)))+/imu', $trimmed, $m, PREG_OFFSET_CAPTURE, $offset)) {
            // Bump offset to avoid unintentionally reprocessing already sorted.
            $offset = $m[count($m) - 1][1] + strlen($m[count($m) - 1][0]);
            $constraints_original = $m[0][0];
            if (! preg_match_all('/(?:^|,)\s*CONSTRAINT\s+.*?(?:,|\)\s*\))/imu', $constraints_original, $m)) {
                continue;
            }
            $constraints_array = $m[0];
            foreach ($constraints_array as &$constraint) {
                $constraint = trim($constraint, ",\r\n");
                // Trim extra parenthesis at the end of table definitions.
                $constraint = preg_replace('/(\s*\))\s*\)\z/imu', '\1', $constraint, 1);
            }
            sort($constraints_array);
            $separator = ',' . PHP_EOL;
            // Comma or "\n)".
            $terminator = preg_match('/(,|\s*\))\z/imu', $constraints_original, $m)
                ? $m[1] : '';
            $constraints_sorted = $separator
                . implode($separator, $constraints_array)
                . $terminator;
            $trimmed = str_replace($constraints_original, $constraints_sorted, $trimmed);
        }

        return $trimmed;
    }

    /**
     * @param array $db_config like ['host' => , 'port' => ].
     * @param string $schema_sql_path
     *
     * @return int containing exit code.
     */
    private static function pgsqlSchemaDump(array $db_config, string $schema_sql_path) : int
    {
        passthru(
            static::pgsqlCommandPrefix($db_config)
            . ' --file=' . escapeshellarg($schema_sql_path)
            . ' --schema-only',
            $exit_code
        );

        if (0 !== $exit_code) {
            return $exit_code;
        }

        // Include migration rows to avoid unnecessary reruns conflicting.
        exec(
            static::pgsqlCommandPrefix($db_config) . ' --table=migrations --data-only --inserts',
            $output,
            $exit_code
        );

        if (0 !== $exit_code) {
            return $exit_code;
        }

        // Cut "SET" statements and workaround `--no-comments` not always working.
        $output = array_filter(
            $output,
            function ($line) {
                return 0 === preg_match('/^\s*(--|SELECT\s|SET\s)/iu', $line)
                    && 0 < mb_strlen($line);
            }
        );

        $output = self::reorderMigrationRows($output);

        file_put_contents(
            $schema_sql_path,
            implode(PHP_EOL, $output) . PHP_EOL,
            FILE_APPEND
        );

        return $exit_code;
    }

    /**
     * @param array $db_config
     * @param string $data_sql_path
     *
     * @return int
     */
    private static function pgsqlDataDump(array $db_config, string $data_sql_path) : int
    {
        passthru(
            static::pgsqlCommandPrefix($db_config)
            . ' --file=' . escapeshellarg($data_sql_path)
            . ' --exclude-table=' . escapeshellarg($db_config['database'] . '.migrations')
            . ' --data-only',
            $exit_code
        );

        if (0 !== $exit_code) {
            return $exit_code;
        }

        return $exit_code;
    }

    /**
     * @param array $db_config
     *
     * @return string
     */
    private static function pgsqlCommandPrefix(array $db_config) : string
    {
        return 'PGPASSWORD=' . escapeshellarg($db_config['password'])
            . ' pg_dump'
            . ' --host=' . escapeshellarg($db_config['host'])
            . ' --port=' . escapeshellarg($db_config['port'])
            . ' --username=' . escapeshellarg($db_config['username'])
            . ' ' . escapeshellarg($db_config['database']);
    }

    /**
     * @param array  $db_config   like ['host' => , 'port' => ].
     * @param string $schema_sql_path like '.../schema.sql'
     *
     * @return int containing exit code.
     */
    private static function sqliteSchemaDump(array $db_config, string $schema_sql_path) : int
    {
        // CONSIDER: Accepting command name as option or from config.
        $command_prefix = 'sqlite3 ' . escapeshellarg($db_config['database']);

        // Since Sqlite lacks Information Schema, and dumping everything may be
        // too slow or memory intense, just query tables and dump them
        // individually.
        // CONSIDER: Using Laravel's `Schema` code instead.
        exec($command_prefix . ' .tables', $output, $exit_code);
        if (0 !== $exit_code) {
            return $exit_code;
        }

        $tables = preg_split('/\s+/', implode(' ', $output));

        file_put_contents($schema_sql_path, '');

        foreach ($tables as $table) {
            // Only migrations should dump data with schema.
            $sql_command = 'migrations' === $table ? '.dump' : '.schema';

            $output = [];
            exec(
                $command_prefix . ' ' . escapeshellarg("$sql_command $table"),
                $output,
                $exit_code
            );

            if (0 !== $exit_code) {
                return $exit_code;
            }

            if ('migrations' === $table) {
                $insert_rows = array_slice($output, 4, -1);
                $sorted = self::reorderMigrationRows($insert_rows);
                array_splice($output, 4, -1, $sorted);
            }

            file_put_contents(
                $schema_sql_path,
                implode(PHP_EOL, $output) . PHP_EOL,
                FILE_APPEND
            );
        }

        return $exit_code;
    }

    /**
     * @param array $db_config
     * @param string $data_sql_path
     *
     * @return int
     */
    private static function sqliteDataDump(array $db_config, string $data_sql_path) : int
    {
        // CONSIDER: Accepting command name as option or from config.
        $command_prefix = 'sqlite3 ' . escapeshellarg($db_config['database']);

        // Since Sqlite lacks Information Schema, and dumping everything may be
        // too slow or memory intense, just query tables and dump them
        // individually.
        // CONSIDER: Using Laravel's `Schema` code instead.
        exec($command_prefix . ' .tables', $output, $exit_code);
        if (0 !== $exit_code) {
            return $exit_code;
        }

        $tables = preg_split('/\s+/', implode(' ', $output));

        foreach ($tables as $table) {
            // We don't want to dump the migrations table here
            if ('migrations' === $table) {
                continue;
            }

            // Only migrations should dump data with schema.
            $sql_command = '.dump';

            $output = [];
            exec(
                $command_prefix . ' ' . escapeshellarg("$sql_command $table"),
                $output,
                $exit_code
            );

            if (0 !== $exit_code) {
                return $exit_code;
            }

            file_put_contents(
                $data_sql_path,
                implode(PHP_EOL, $output) . PHP_EOL,
                FILE_APPEND
            );
        }

        return $exit_code;
    }
}
