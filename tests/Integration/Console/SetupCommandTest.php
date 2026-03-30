<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Console;

use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class SetupCommandTest extends UnitTestCase
{
    public function testAssignsSuperAdminRoleByEmail(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->artisan('governor:setup', ['--superadmin' => 'admin@example.com'])
            ->expectsOutput('User admin@example.com has been assigned the SuperAdmin role.')
            ->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->roles->contains('SuperAdmin'));
    }

    public function testAssignsSuperAdminRoleById(): void
    {
        $user = User::factory()->create();

        $this->artisan('governor:setup', ['--user' => $user->getKey()])
            ->expectsOutput("User ID {$user->getKey()} has been assigned the SuperAdmin role.")
            ->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->roles->contains('SuperAdmin'));
    }

    public function testAlsoAssignsMemberRole(): void
    {
        $user = User::factory()->create(['email' => 'member@example.com']);

        $this->artisan('governor:setup', ['--superadmin' => 'member@example.com'])
            ->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->roles->contains('SuperAdmin'));
        $this->assertTrue($user->roles->contains('Member'));
    }

    public function testFailsWithInvalidEmail(): void
    {
        $this->artisan('governor:setup', ['--superadmin' => 'nonexistent@example.com'])
            ->expectsOutput('No user found with email: nonexistent@example.com')
            ->assertFailed();
    }

    public function testFailsWithInvalidUserId(): void
    {
        $this->artisan('governor:setup', ['--user' => 99999])
            ->expectsOutput('No user found with ID: 99999')
            ->assertFailed();
    }

    public function testFailsWithNoOptions(): void
    {
        $this->artisan('governor:setup')
            ->expectsOutput('You must provide either --superadmin=<email> or --user=<id>.')
            ->assertFailed();
    }

    public function testFailsWithBothOptions(): void
    {
        $user = User::factory()->create(['email' => 'both@example.com']);

        $this->artisan('governor:setup', ['--superadmin' => 'both@example.com', '--user' => $user->getKey()])
            ->expectsOutput('Provide only one of --superadmin=<email> or --user=<id>, not both.')
            ->assertFailed();
    }

    public function testDoesNotDuplicateRolesOnRerun(): void
    {
        $user = User::factory()->create(['email' => 'dupe@example.com']);

        $this->artisan('governor:setup', ['--superadmin' => 'dupe@example.com'])
            ->assertSuccessful();
        $this->artisan('governor:setup', ['--superadmin' => 'dupe@example.com'])
            ->assertSuccessful();

        $user->refresh();
        $roleCount = $user->roles->filter(fn ($role) => $role->name === 'SuperAdmin')->count();
        $this->assertEquals(1, $roleCount);
    }
}
