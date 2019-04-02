<?php


namespace OrisIntel\MigrationSnapshot\Tests;


class MigrateDumpTest extends TestCase
{
    private $resultDir;
    private $resultFile;

    public function setUp() : void
    {
        parent::setUp();

        $this->resultDir = realpath(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/database/migrations/sql');
        $this->resultFile = $this->resultDir . '/schema-and-migrations.sql';
        // Not leaving to tearDown since it can be useful to see result after
        // failure.
        if (file_exists($this->resultFile)) {
            unlink($this->resultFile);
        }
    }

    public function test_handle()
    {
        $result = \Artisan::call('migrate:dump');
        $this->assertEquals(0, $result);
        $this->assertDirectoryExists($this->resultDir);
        $this->assertFileExists($this->resultFile);
        $result_sql = file_get_contents($this->resultFile);
        $this->assertStringContainsString('CREATE TABLE `test_ms`', $result_sql);
        $this->assertStringContainsString('INSERT INTO `migrations`', $result_sql);
    }
}