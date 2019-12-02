<?php

namespace AndrewGatenby\SnapMigrations;

use Exception;
use Laravel\Lumen\Testing\DatabaseMigrations;
use MySQLDump;
use MySQLImport;
use Illuminate\Support\Arr;

trait SnapMigrations
{
    /** @var string */
    protected $snapMigrationFilename;

    /** @var bool */
    protected $shouldRunSeeder = false;

    /**
     * @param bool $shouldRunSeeder Pass true to run db:seed as part of the Snap Migration being made
     * @throws \Exception
     */
    public function setUp(bool $shouldRunSeeder = false)
    {
        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[DatabaseMigrations::class])) {
            throw new Exception('Remove DatabaseMigrations from your Test classes and use SnapMigrations instead');
        }
        $this->shouldRunSeeder = $shouldRunSeeder;

        parent::setUp();

        $this->runDatabaseMigrations($this->shouldRunSeeder);
    }

    /**
     * @param bool $shouldRunSeeder
     * @throws Exception
     */
    public function runDatabaseMigrations(bool $shouldRunSeeder)
    {
        // generate our filename
        $this->setSnapMigrationFilename(storage_path(env('SNAP_MIGRATION_SQL_FILE', 'snap_migration.sql')));

        /*
         * In the case that the Snap Migration file doesn't exist or is outdated, we want to do a fresh migration and
         * then take a fresh static snapshot of it. Potentially we may we seeding the database too, before we take the
         * snapshot.
         */
        if ($this->checkSnapMigrationFile($this->snapMigrationFilename) === false) {
            $this->artisan('migrate:fresh');
            if ($shouldRunSeeder === true) {
                $this->artisan('db:seed');
            }
            $this->makeSnapMigration($this->snapMigrationFilename);
            return;
        }

        // Happy that the Snap Migration file exists and is current, so we will restore from that static SQL file
        $this->restoreSnapMigration($this->snapMigrationFilename);
    }

    /**
     * @param string $filename
     * @throws Exception
     */
    private function restoreSnapMigration(string $filename)
    {
        $config = $this->getDatabaseConfig();

        $mysqli = $this->makeMysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database']
        );
        $import = new MySQLImport($mysqli);
        $import->load($filename);
    }

    /**
     * @param string $filename
     * @return void
     */
    private function setSnapMigrationFilename(string $filename): void
    {
        $this->snapMigrationFilename = $filename;
    }

    /**
     * @param string $filename
     * @return bool
     */
    private function checkSnapMigrationFile(string $filename)
    {
        return (
            $this->checkSnapMigrationFileExists($filename) &&
            $this->checkSnapMigrationFileAge($filename)
        );
    }

    /**
     * @param string $filename
     * @return bool
     */
    private function checkSnapMigrationFileExists(string $filename): bool
    {
        return file_exists($filename) && is_readable($filename);
    }

    /**
     * @param string $filename
     * @return bool
     */
    private function checkSnapMigrationFileAge(string $filename): bool
    {
        $lastModifiedTime = filemtime($filename);
        foreach (glob('database/migrations/*.php') as $migrationFile) {
            if (filemtime($migrationFile) > $lastModifiedTime) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $filename
     * @throws Exception
     */
    private function makeSnapMigration(string $filename)
    {
      $config = $this->getDatabaseConfig();

        $mysqli = $this->makeMysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database']
        );
        $dump = new MySQLDump($mysqli);
        $dump->save($filename);
    }

    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $dbName
     * @return \mysqli
     * @throws Exception
     */
    private function makeMysqli(string $host, string $username, string $password, string $dbName): \mysqli
    {
        return new \mysqli($host, $username, $password, $dbName);
    }

    /**
     * Get the database connection from the config settings.
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    protected function getDatabaseConnection()
    {
        return config('database.default');
    }

    /**
     * @return mixed
     */
    protected function getDatabaseConfig()
    {
        $connection = $this->getDatabaseConnection();
        $config = config("database.connections.{$connection}");

        if (isset($config['read'])) {
            $config = $this->getWriteConfig($config);
        }

        return $config;
    }

    /**
     * Get the read configuration for a read / write connection.
     *
     * @param  array $config
     * @return array
     */
    protected function getWriteConfig(array $config)
    {
        return $this->mergeReadWriteConfig($config, $this->getReadWriteConfig($config, 'write'));
    }

    /**
     * Get a read / write level configuration.
     *
     * @param  array $config
     * @param  string $type
     * @return array
     */
    protected function getReadWriteConfig(array $config, $type)
    {
        return isset($config[$type][0]) ? Arr::random($config[$type]) : $config[$type];
    }

    /**
     * Merge a configuration for a read / write connection.
     *
     * @param  array $config
     * @param  array $merge
     * @return array
     */
    protected function mergeReadWriteConfig(array $config, array $merge)
    {
        return Arr::except(array_merge($config, $merge), ['read', 'write']);
    }
}
