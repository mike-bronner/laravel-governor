<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Seeders;

use GeneaLabs\LaravelGovernor\Database\Seeders\LaravelGovernorAdminSeeder;
use GeneaLabs\LaravelGovernor\Tests\CreatesApplication;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;

class AdminSeederTest extends TestCase
{
    use CreatesApplication;

    public function testSkipsWhenAdminsConfigIsNull(): void
    {
        config()->set('genealabs-laravel-governor.admins', null);

        Log::shouldReceive('warning')->never();

        $seeder = new LaravelGovernorAdminSeeder();
        $seeder->run();

        $userModel = config('genealabs-laravel-governor.models.auth');
        $this->assertCount(0, (new $userModel)->where('email', 'like', '%admin%example%')->get());
    }

    public function testSkipsWhenAdminsConfigIsNotValidJsonArray(): void
    {
        config()->set('genealabs-laravel-governor.admins', 'not-json');

        Log::shouldReceive('warning')->never();

        $seeder = new LaravelGovernorAdminSeeder();
        $seeder->run();

        $userModel = config('genealabs-laravel-governor.models.auth');
        $this->assertCount(0, (new $userModel)->where('email', 'like', '%admin%example%')->get());
    }

    public function testSkipsAndWarnsWhenRolesDoNotExist(): void
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

    public function testCreatesAdminUserWhenRolesExist(): void
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
