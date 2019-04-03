<?php


namespace OrisIntel\MigrationSnapshot\Tests\Postgresql;

use OrisIntel\MigrationSnapshot\Tests\TestCase;

class MigrateLoadTest extends TestCase
{
    protected $dbDefault = 'pgsql';

    public function test_handle()
    {
        // Make the dump file.
        $this->createTestTablesWithoutMigrate();
        $result = \Artisan::call('migrate:dump');
        $this->assertEquals(0, $result);
        \Schema::dropAllTables();

        $result = \Artisan::call('migrate:load');
        $this->assertEquals(0, $result);

        $this->assertEquals(
            '0000_00_00_000000_create_test_tables',
            \DB::table('migrations')->value('migration')
        );

        $table_name = \DB::table('information_schema.tables')
            ->where('table_catalog', \DB::getDatabaseName())
            ->where('table_schema', 'public')
            ->whereNotIn('table_name', ['migrations'])
            ->where('table_name', 'NOT LIKE', 'pg_%')
            ->value('table_name');

        $this->assertEquals('test_ms', $table_name);
    }

    // TODO: Test no-drop and prompt-when-production.
}