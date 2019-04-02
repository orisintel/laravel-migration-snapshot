<?php


namespace OrisIntel\MigrationSnapshot\Tests;


class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $dbDefault = 'mysql';
    protected $resultDir;
    protected $resultFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Won't trigger MigrateStartingHandler.
        $this->loadMigrationsFrom(__DIR__ . '/migrations/setup');

        $this->resultDir = realpath(
            __DIR__ . '/../vendor/orchestra/testbench-core/laravel/database/migrations'
        ) . '/sql';
        $this->resultFile = $this->resultDir . '/schema-and-migrations.sql';
        // Not leaving to tearDown since it can be useful to see result after
        // failure.
        foreach (glob($this->resultDir . '/*') as $sql_path) {
            unlink($sql_path);
        }
        if (file_exists($this->resultDir)) {
            rmdir($this->resultDir);
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
