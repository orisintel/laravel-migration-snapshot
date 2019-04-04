<?php


namespace OrisIntel\MigrationSnapshot\Tests\Sqlite;

class MigrateDumpTest extends SqliteTestCase
{
    public function test_handle()
    {
        $this->createTestTablesWithoutMigrate();
        $result = \Artisan::call('migrate:dump');
        $this->assertEquals(0, $result);
        $this->assertDirectoryExists($this->schemaSqlDirectory);
        $this->assertFileExists($this->schemaSqlPath);
        $result_sql = file_get_contents($this->schemaSqlPath);
        $this->assertRegExp('/CREATE TABLE( IF NOT EXISTS)? "test_ms" /', $result_sql);
        $this->assertRegExp('/INSERT INTO "?migrations"? /', $result_sql);
    }
}