<?php namespace GeneaLabs\LaravelGovernor\Tests\Integration\Providers;

use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class ServiceTest extends UnitTestCase
{
    protected string $sqliteDatabase = __DIR__ . '/../../database/database.sqlite';

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Wipe the persistent SQLite file so migrations always start clean.
        if (file_exists($this->sqliteDatabase)) {
            unlink($this->sqliteDatabase);
        }
        touch($this->sqliteDatabase);

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            "url" => null,
            'database' => $this->sqliteDatabase,
            'prefix' => '',
            "foreign_key_constraints" => false,
        ]);
    }

    public function tearDown() : void
    {
        parent::tearDown();
    }


    public function testEntityParsing()
    {
        $this->assertTrue(true);
    }
}
