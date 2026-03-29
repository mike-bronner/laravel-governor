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

        // The main seeder is already called in setUp via CreatesApplication.
        // Run it again with null admin config to verify it completes without errors.
        $seeder = new LaravelGovernorDatabaseSeeder();
        $seeder->setContainer(app());
        $seeder->run();

        $this->assertTrue(true);
    }
}
