<?php

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Seeders;

use GeneaLabs\LaravelGovernor\Database\Seeders\LaravelGovernorDatabaseSeeder;
use GeneaLabs\LaravelGovernor\Tests\CreatesApplication;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DatabaseSeederTest extends TestCase
{
    use CreatesApplication;

    #[Test]
    public function it_completes_without_errors_when_admin_config_is_missing(): void
    {
        config()->set('genealabs-laravel-governor.superadmins', null);
        config()->set('genealabs-laravel-governor.admins', null);

        $seeder = new LaravelGovernorDatabaseSeeder();
        $seeder->setContainer(app());
        $seeder->setCommand($this->artisan('db:seed', [
            '--class' => LaravelGovernorDatabaseSeeder::class,
            '--no-interaction' => true,
        ]));

        // Should not throw any exception
        $this->assertTrue(true);
    }
}
