<?php


namespace OrisIntel\MigrationSnapshot\Tests;


use OrisIntel\MigrationSnapshot\Commands\MigrateDumpCommand;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $dbDefault = 'mysql';
    protected $schemaSqlDirectory;
    protected $schemaSqlPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaSqlPath = realpath(
                __DIR__ . '/../vendor/orchestra/testbench-core/laravel/database'
            ) . MigrateDumpCommand::SCHEMA_SQL_PATH_SUFFIX;
        $this->schemaSqlDirectory = dirname($this->schemaSqlPath);

        // Not leaving to tearDown since it can be useful to see result after
        // failure.
        foreach (glob($this->schemaSqlDirectory . '/*') as $sql_path) {
            unlink($sql_path);
        }
        if (file_exists($this->schemaSqlDirectory)) {
            rmdir($this->schemaSqlDirectory);
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', $this->dbDefault);
    }

    protected function getPackageProviders($app)
    {
        return ['\OrisIntel\MigrationSnapshot\ServiceProvider'];
    }

    protected function createTestTablesWithoutMigrate() : void
    {
        // Executing without `loadMigrationsFrom` and without `Artisan::call` to
        // avoid unnecessary runs through migration hooks.

        require_once(__DIR__ . '/migrations/setup/0000_00_00_000000_create_test_tables.php');
        \Schema::dropAllTables();
        \Schema::dropAllViews();
        \Schema::create('migrations', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->increments('id');
            $table->string('migration', 255);
            $table->integer('batch');
        });
        (new \CreateTestTables)->up();
        \DB::table('migrations')->insert([
            'migration' => '0000_00_00_000000_create_test_tables',
            'batch' => 1,
        ]);
    }
}
