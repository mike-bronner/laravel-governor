<?php namespace GeneaLabs\LaravelGovernor\Tests;

use GeneaLabs\LaravelGovernor\Database\Seeders\LaravelGovernorDatabaseSeeder;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\Test;

class AlwaysRunFirstTest extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            'GeneaLabs\LaravelGovernor\Providers\Service',
            'GeneaLabs\LaravelGovernor\Providers\Auth',
            'GeneaLabs\LaravelGovernor\Providers\Route',
            'GeneaLabs\LaravelGovernor\Providers\Nova',
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $sqliteDatabase = __DIR__ . '/database/database.sqlite';

        if (file_exists($sqliteDatabase)) {
            unlink($sqliteDatabase);
        }
        touch($sqliteDatabase);

        $app['config']->set('genealabs-laravel-governor.models', [
            'auth'       => User::class,
            'action'     => \GeneaLabs\LaravelGovernor\Action::class,
            'assignment' => \GeneaLabs\LaravelGovernor\Assignment::class,
            'entity'     => \GeneaLabs\LaravelGovernor\Entity::class,
            'group'      => \GeneaLabs\LaravelGovernor\Group::class,
            'ownership'  => \GeneaLabs\LaravelGovernor\Ownership::class,
            'permission' => \GeneaLabs\LaravelGovernor\Permission::class,
            'role'       => \GeneaLabs\LaravelGovernor\Role::class,
            'team'       => \GeneaLabs\LaravelGovernor\Team::class,
            'invitation' => \GeneaLabs\LaravelGovernor\TeamInvitation::class,
        ]);
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'                  => 'sqlite',
            'url'                     => null,
            'database'                => $sqliteDatabase,
            'prefix'                  => '',
            'foreign_key_constraints' => false,
        ]);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->artisan('migrate');
        $this->artisan('db:seed', [
            '--database'      => 'sqlite',
            '--class'         => LaravelGovernorDatabaseSeeder::class,
            '--no-interaction' => true,
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    public function testMigrateAndInstallTheDatabase(): void
    {
        $this->assertTrue(true);
    }
}
