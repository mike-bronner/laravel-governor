<?php

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Seeders;

use GeneaLabs\LaravelGovernor\Database\Seeders\LaravelGovernorAdminSeeder;
use GeneaLabs\LaravelGovernor\Tests\CreatesApplication;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AdminSeederTest extends TestCase
{
    use CreatesApplication;

    #[Test]
    public function it_skips_when_admins_config_is_null(): void
    {
        config()->set('genealabs-laravel-governor.admins', null);

        Log::shouldReceive('warning')->never();

        $seeder = new LaravelGovernorAdminSeeder();
        $seeder->run();

        $this->assertTrue(true);
    }

    #[Test]
    public function it_skips_when_admins_config_is_not_valid_json_array(): void
    {
        config()->set('genealabs-laravel-governor.admins', 'not-json');

        Log::shouldReceive('warning')->never();

        $seeder = new LaravelGovernorAdminSeeder();
        $seeder->run();

        $this->assertTrue(true);
    }

    #[Test]
    public function it_skips_and_warns_when_roles_do_not_exist(): void
    {
        config()->set('genealabs-laravel-governor.admins', json_encode([
            ['name' => 'Test Admin', 'email' => 'admin-test@example.com', 'password' => 'secret'],
        ]));

        $roleClass = config('genealabs-laravel-governor.models.role');
        (new $roleClass)->where('name', 'Admin')->delete();

        Log::shouldReceive('warning')
            ->once()
            ->with('Governor: Skipping admin user setup — required roles (Admin, Member) do not exist.');

        $seeder = new LaravelGovernorAdminSeeder();
        $seeder->run();

        $userModel = config('genealabs-laravel-governor.models.auth');
        $this->assertNull((new $userModel)->where('email', 'admin-test@example.com')->first());
    }

    #[Test]
    public function it_creates_admin_user_when_roles_exist(): void
    {
        config()->set('genealabs-laravel-governor.admins', json_encode([
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'password' => 'secret123'],
        ]));

        $seeder = new LaravelGovernorAdminSeeder();
        $seeder->run();

        $userModel = config('genealabs-laravel-governor.models.auth');
        $user = (new $userModel)->where('email', 'admin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertEquals('Admin User', $user->name);
        $this->assertTrue($user->roles->pluck('name')->contains('Admin'));
        $this->assertTrue($user->roles->pluck('name')->contains('Member'));
    }
}
