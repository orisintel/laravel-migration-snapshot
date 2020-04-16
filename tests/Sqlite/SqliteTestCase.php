<?php


namespace OrisIntel\MigrationSnapshot\Tests\Sqlite;

use OrisIntel\MigrationSnapshot\Tests\TestCase;

abstract class SqliteTestCase extends TestCase
{
    protected $dbDefault = 'sqlite';

    public static function setUpBeforeClass() : void
    {
        // File must exist before connection will initialize, even if empty.
        touch(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/database/database.sqlite');
    }
}
