<?php

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Seeders;

use GeneaLabs\LaravelGovernor\Database\Seeders\LaravelGovernorSuperAdminSeeder;
use GeneaLabs\LaravelGovernor\Tests\CreatesApplication;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SuperAdminSeederTest extends TestCase
{
    use CreatesApplication;

    #[Test]
    public function it_skips_when_superadmins_config_is_null(): void
    {
        config()->set('genealabs-laravel-governor.superadmins', null);

        Log::shouldReceive('warning')->never();

        $seeder = new LaravelGovernorSuperAdminSeeder();
        $seeder->run();

        $this->assertTrue(true);
    }

    #[Test]
    public function it_skips_when_superadmins_config_is_not_valid_json_array(): void
    {
        config()->set('genealabs-laravel-governor.superadmins', 'not-json');

        Log::shouldReceive('warning')->never();

        $seeder = new LaravelGovernorSuperAdminSeeder();
        $seeder->run();

        $this->assertTrue(true);
    }

    #[Test]
    public function it_skips_and_warns_when_roles_do_not_exist(): void
    {
        config()->set('genealabs-laravel-governor.superadmins', json_encode([
            ['name' => 'Test', 'email' => 'test@example.com', 'password' => 'secret'],
        ]));

        $roleClass = config('genealabs-laravel-governor.models.role');
        (new $roleClass)->where('name', 'SuperAdmin')->delete();

        Log::shouldReceive('warning')
            ->once()
            ->with('Governor: Skipping super admin user setup — required roles (SuperAdmin, Member) do not exist.');

        $seeder = new LaravelGovernorSuperAdminSeeder();
        $seeder->run();

        $userModel = config('genealabs-laravel-governor.models.auth');
        $this->assertNull((new $userModel)->where('email', 'test@example.com')->first());
    }

    #[Test]
    public function it_creates_superadmin_user_when_roles_exist(): void
    {
        config()->set('genealabs-laravel-governor.superadmins', json_encode([
            ['name' => 'Super User', 'email' => 'super@example.com', 'password' => 'secret123'],
        ]));

        $seeder = new LaravelGovernorSuperAdminSeeder();
        $seeder->run();

        $userModel = config('genealabs-laravel-governor.models.auth');
        $user = (new $userModel)->where('email', 'super@example.com')->first();

        $this->assertNotNull($user);
        $this->assertEquals('Super User', $user->name);
        $this->assertTrue($user->roles->pluck('name')->contains('SuperAdmin'));
        $this->assertTrue($user->roles->pluck('name')->contains('Member'));
    }
}
