<?php


namespace OrisIntel\MigrationSnapshot\Tests\Mysql;

use OrisIntel\MigrationSnapshot\Commands\MigrateDumpCommand;
use OrisIntel\MigrationSnapshot\Tests\TestCase;

class MigrateDumpTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('migration-snapshot.reorder', true);
        $app['config']->set('migration-snapshot.trim-underscores', true);
    }

    public function test_handle()
    {
        $this->createTestTablesWithoutMigrate();
        $result = \Artisan::call('migrate:dump');
        $this->assertEquals(0, $result);
        $this->assertDirectoryExists($this->schemaSqlDirectory);
        $this->assertFileExists($this->schemaSqlPath);
        $result_sql = file_get_contents($this->schemaSqlPath);
        $this->assertContains('CREATE TABLE `test_ms`', $result_sql);
        $this->assertContains('INSERT INTO `migrations`', $result_sql);
        $this->assertNotContains(' AUTO_INCREMENT=', $result_sql);
        $last_character = mb_substr($result_sql, -1);
        $this->assertRegExp("/[\r\n]\z/mu", $last_character);
    }

    public function test_trimUnderscoresFromForeign()
    {
        $sql = "KEY z_index,
  CONSTRAINT _b_fk FOREIGN KEY('b') REFERENCES b ON('b'),
  CONSTRAINT a_fk FOREIGN KEY('a') REFERENCES a ON('a')
);";
        $trimmed = MigrateDumpCommand::trimUnderscoresFromForeign($sql);
        $this->assertEquals(
            "KEY z_index,
  CONSTRAINT a_fk FOREIGN KEY('a') REFERENCES a ON('a'),
  CONSTRAINT b_fk FOREIGN KEY('b') REFERENCES b ON('b')
);",
            $trimmed
        );
    }

    public function test_reorderMigrationRows()
    {
        $output = [
            "INSERT INTO migrations VALUES (1,'0001_01_01_000001_one',1);",
            "INSERT INTO migrations VALUES (2,'0001_01_01_000003_three',2);",
            "INSERT INTO migrations VALUES (3,'0001_01_01_000002_two',3);",
        ];
        $reordered = array_values(
            MigrateDumpCommand::reorderMigrationRows($output)
        );
        $this->assertEquals([
            "INSERT INTO migrations VALUES (1,'0001_01_01_000001_one',0);",
            "INSERT INTO migrations VALUES (2,'0001_01_01_000002_two',0);",
            "INSERT INTO migrations VALUES (3,'0001_01_01_000003_three',0);",
        ], $reordered);
    }
}
