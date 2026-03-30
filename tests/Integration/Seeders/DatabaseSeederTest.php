<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Seeders;

use GeneaLabs\LaravelGovernor\Database\Seeders\LaravelGovernorDatabaseSeeder;
use GeneaLabs\LaravelGovernor\Tests\CreatesApplication;
use Orchestra\Testbench\TestCase;

class DatabaseSeederTest extends TestCase
{
    use CreatesApplication;

    public function testCompletesWithoutErrorsWhenAdminConfigIsMissing(): void
    {
        config()->set('genealabs-laravel-governor.superadmins', null);
        config()->set('genealabs-laravel-governor.admins', null);

        $seeder = new LaravelGovernorDatabaseSeeder();
        $seeder->setContainer(app());
        $seeder->run();

        $roleClass = config('genealabs-laravel-governor.models.role');
        $this->assertNotNull((new $roleClass)->find('SuperAdmin'));
        $this->assertNotNull((new $roleClass)->find('Member'));
    }
}
