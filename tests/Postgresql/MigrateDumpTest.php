<?php


namespace OrisIntel\MigrationSnapshot\Tests\Postgresql;

use OrisIntel\MigrationSnapshot\Tests\TestCase;

class MigrateDumpTest extends TestCase
{
    protected $dbDefault = 'pgsql';

    public function test_handle()
    {
        $this->createTestTablesWithoutMigrate();
        $result = \Artisan::call('migrate:dump');
        $this->assertEquals(0, $result);
        $this->assertDirectoryExists($this->schemaSqlDirectory);
        $this->assertFileExists($this->schemaSqlPath);
        $result_sql = file_get_contents($this->schemaSqlPath);
        $this->assertMatchesRegularExpression('/CREATE TABLE (public\.)?test_ms /', $result_sql);
        $this->assertMatchesRegularExpression('/INSERT INTO (public\.)?migrations /', $result_sql);
        $last_character = mb_substr($result_sql, -1);
        $this->assertMatchesRegularExpression("/[\r\n]\z/mu", $last_character);
    }
}
