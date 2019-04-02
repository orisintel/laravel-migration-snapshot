<?php


namespace OrisIntel\MigrationSnapshot\Tests;


class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Won't trigger MigrateStartingHandler.
        $this->loadMigrationsFrom(__DIR__ . '/migrations/setup');
    }

    protected function getPackageProviders($app)
    {
        return ['\OrisIntel\MigrationSnapshot\ServiceProvider'];
    }
}
