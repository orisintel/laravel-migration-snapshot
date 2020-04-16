<?php


namespace OrisIntel\MigrationSnapshot\Tests\Mysql;

use OrisIntel\MigrationSnapshot\Commands\MigrateDumpCommand;
use OrisIntel\MigrationSnapshot\Tests\TestCase;

class MigrateDumpTest extends TestCase
{
    public function test_handle()
    {
        $this->createTestTablesWithoutMigrate();
        $result = \Artisan::call('migrate:dump');
        $this->assertEquals(0, $result);
        $this->assertDirectoryExists($this->schemaSqlDirectory);
        $this->assertFileExists($this->schemaSqlPath);
        $result_sql = file_get_contents($this->schemaSqlPath);
        $this->assertStringContainsString('CREATE TABLE `test_ms`', $result_sql);
        $this->assertStringContainsString('INSERT INTO `migrations`', $result_sql);
        $this->assertStringNotContainsString(' AUTO_INCREMENT=', $result_sql);
        $last_character = mb_substr($result_sql, -1);
        $this->assertRegExp("/[\r\n]\z/mu", $last_character);
    }

    public function test_trimUnderscoresFromForeign()
    {
        $sql = "KEY z_index,
  CONSTRAINT `__b_fk` FOREIGN KEY (`b`) REFERENCES `b` ON(`b`),
  CONSTRAINT `a_fk` FOREIGN KEY (`a`) REFERENCES `a` ON(`a`)
);
...KEY z2_index,
  CONSTRAINT `__d_fk` FOREIGN KEY (`d`) REFERENCES `d` ON(`d`),
  CONSTRAINT `c_fk` FOREIGN KEY (`c`) REFERENCES `c` ON(`c`)
);";
        $trimmed = MigrateDumpCommand::trimUnderscoresFromForeign($sql);
        $this->assertEquals(
            "KEY z_index,
  CONSTRAINT `a_fk` FOREIGN KEY (`a`) REFERENCES `a` ON(`a`),
  CONSTRAINT `b_fk` FOREIGN KEY (`b`) REFERENCES `b` ON(`b`)
);
...KEY z2_index,
  CONSTRAINT `c_fk` FOREIGN KEY (`c`) REFERENCES `c` ON(`c`),
  CONSTRAINT `d_fk` FOREIGN KEY (`d`) REFERENCES `d` ON(`d`)
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
