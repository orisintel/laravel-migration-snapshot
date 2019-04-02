<?php


namespace OrisIntel\MigrationSnapshot\Tests\Postgresql;

use OrisIntel\MigrationSnapshot\Tests\TestCase;

class MigrateLoadTest extends TestCase
{
    protected $dbDefault = 'pgsql';

    public function test_handle()
    {
        $result = \Artisan::call('migrate:dump');
        $this->assertEquals(0, $result);
        \Schema::dropAllTables();
        $result = \Artisan::call('migrate:load');
        $this->assertEquals(0, $result);

        $this->assertEquals(
            '0000_00_00_000000_create_test_tables',
            \DB::table('migrations')->value('migration')
        );
    }

    // TODO: Test no-drop and no-op-when-production.
}