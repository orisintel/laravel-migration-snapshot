<?php


namespace OrisIntel\MigrationSnapshot\Tests\Mysql;

use OrisIntel\MigrationSnapshot\Tests\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class MigrateHookTest extends TestCase
{
    public function test_handle()
    {
        // Make the dump file.
        $this->createTestTablesWithoutMigrate();
        // TODO: Fix inclusion of `, ['--quiet' => true]` here breaking test.
        $result = \Artisan::call('migrate:dump');
        $this->assertEquals(0, $result);
        \Schema::dropAllTables();

        // Test that dump file is used.
        $output = new BufferedOutput();
        $result = \Artisan::call('migrate', [], $output);
        $this->assertEquals(0, $result);

        $output_string = $output->fetch();
        $this->assertContains('Loaded schema', $output_string);
        $this->assertContains('Dumped schema', $output_string);
    }
}