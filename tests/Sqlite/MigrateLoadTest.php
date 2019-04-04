<?php


namespace OrisIntel\MigrationSnapshot\Tests\Sqlite;


class MigrateLoadTest extends SqliteTestCase
{
    public function test_handle()
    {
        // Make the dump file.
        $this->createTestTablesWithoutMigrate();
        $result = \Artisan::call('migrate:dump');
        $this->assertEquals(0, $result);

        $result = \Artisan::call('migrate:load');
        $this->assertEquals(0, $result);

        $this->assertEquals(
            '0000_00_00_000000_create_test_tables',
            \DB::table('migrations')->value('migration')
        );

        $this->assertNull(\DB::table('test_ms')->value('name'));
    }
}