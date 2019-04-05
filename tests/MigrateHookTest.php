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
        // Implicitly load when no migrations.
        \DB::table('migrations')->delete();

        // Test that dump file is used.
        $output = new BufferedOutput();
        $result = \Artisan::call('migrate', [], $output);
        $this->assertEquals(0, $result);

        $output_string = $output->fetch();
        $this->assertContains('Loaded schema', $output_string);
        $this->assertContains('Dumped schema', $output_string);
    }

    public function test_handle_doesNotLoadWhenDbHasMigrated()
    {
        // Make the dump file.
        $this->createTestTablesWithoutMigrate();
        // TODO: Fix inclusion of `, ['--quiet' => true]` here breaking test.
        $result = \Artisan::call('migrate:dump');
        $this->assertEquals(0, $result);

        // Test that dump file is used.
        $output = new BufferedOutput();
        $result = \Artisan::call('migrate', [], $output);
        $this->assertEquals(0, $result);

        $output_string = $output->fetch();
        $this->assertNotContains('Loaded schema', $output_string);

        $this->assertEquals(1, \DB::table('test_ms')->count());
    }
}