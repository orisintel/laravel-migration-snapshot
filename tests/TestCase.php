<?php


namespace OrisIntel\MigrationSnapshot\Tests;


use OrisIntel\MigrationSnapshot\Commands\MigrateDumpCommand;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $dbDefault = 'mysql';
    protected $schemaSqlDirectory;
    protected $schemaSqlPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Won't trigger MigrateStartingHandler.
        $this->loadMigrationsFrom(__DIR__ . '/migrations/setup');

        $this->schemaSqlPath = realpath(
            __DIR__ . '/../vendor/orchestra/testbench-core/laravel/database'
        ) . MigrateDumpCommand::SCHEMA_SQL_PATH_SUFFIX;
        $this->schemaSqlDirectory = dirname($this->schemaSqlPath);
        // Not leaving to tearDown since it can be useful to see result after
        // failure.
        foreach (glob($this->schemaSqlDirectory . '/*') as $sql_path) {
            unlink($sql_path);
        }
        if (file_exists($this->schemaSqlDirectory)) {
            rmdir($this->schemaSqlDirectory);
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', $this->dbDefault);
    }

    protected function getPackageProviders($app)
    {
        return ['\OrisIntel\MigrationSnapshot\ServiceProvider'];
    }
}
