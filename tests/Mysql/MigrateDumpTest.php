<?php


namespace OrisIntel\MigrationSnapshot\Tests\Mysql;

use OrisIntel\MigrationSnapshot\Tests\TestCase;

class MigrateDumpTest extends TestCase
{
    public function test_handle()
    {
        $result = \Artisan::call('migrate:dump');
        $this->assertEquals(0, $result);
        $this->assertDirectoryExists($this->resultDir);
        $this->assertFileExists($this->resultFile);
        $result_sql = file_get_contents($this->resultFile);
        $this->assertContains('CREATE TABLE `test_ms`', $result_sql);
        $this->assertContains('INSERT INTO `migrations`', $result_sql);
    }
}