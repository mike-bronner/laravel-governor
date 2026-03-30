<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Seeders;

use GeneaLabs\LaravelGovernor\Database\Seeders\LaravelGovernorSuperAdminSeeder;
use GeneaLabs\LaravelGovernor\Tests\CreatesApplication;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use CreatesApplication;

    public function testSkipsWhenSuperadminsConfigIsNull(): void
    {
        config()->set('genealabs-laravel-governor.superadmins', null);

        Log::shouldReceive('warning')->never();

        $seeder = new LaravelGovernorSuperAdminSeeder();
        $seeder->run();

        $userModel = config('genealabs-laravel-governor.models.auth');
        $this->assertCount(0, (new $userModel)->where('email', 'like', '%super%example%')->get());
    }

    public function testSkipsWhenSuperadminsConfigIsNotValidJsonArray(): void
    {
        config()->set('genealabs-laravel-governor.superadmins', 'not-json');

        Log::shouldReceive('warning')->never();

        $seeder = new LaravelGovernorSuperAdminSeeder();
        $seeder->run();

        $userModel = config('genealabs-laravel-governor.models.auth');
        $this->assertCount(0, (new $userModel)->where('email', 'like', '%super%example%')->get());
    }

    public function testSkipsAndWarnsWhenRolesDoNotExist(): void
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

    public function testCreatesSuperadminUserWhenRolesExist(): void
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
