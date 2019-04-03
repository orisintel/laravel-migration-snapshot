<?php


namespace OrisIntel\MigrationSnapshot\Tests\Postgresql;

use OrisIntel\MigrationSnapshot\Tests\TestCase;

class MigrateDumpTest extends TestCase
{
    protected $dbDefault = 'pgsql';

    public function test_handle()
    {
        $result = \Artisan::call('migrate:dump');
        $this->assertEquals(0, $result);
        $this->assertDirectoryExists($this->resultDir);
        $this->assertFileExists($this->resultFile);
        $result_sql = file_get_contents($this->resultFile);
        $this->assertRegExp('/CREATE TABLE (public\.)?test_ms /', $result_sql);
        $this->assertRegExp('/INSERT INTO (public\.)?migrations /', $result_sql);
    }
}