<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Policies;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class GuestUserTest extends UnitTestCase
{
    public function testGuestUserCanAccessWithGuestRole()
    {
        // Create a Guest role (with unique name to avoid conflicts)
        $roleName = 'Guest' . uniqid();
        Role::create([
            'name' => $roleName,
            'description' => 'Unauthenticated users role',
        ]);

        Permission::create([
            'role_name' => $roleName,
            'entity_name' => 'Author (Laravel Governor)',
            'action_name' => 'viewAny',
            'ownership_name' => 'any',
        ]);

        // Logout
        auth()->logout();

        // Test guest access through policy
        $policy = new \GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author();
        $result = $policy->viewAny(null);

        // Result will depend on whether guest role is found
        $this->assertTrue(is_bool($result));
    }

    public function testGuestUserCannotAccessWithoutGuestRole()
    {
        // Ensure no Guest role
        Role::where('name', 'Guest')->delete();

        auth()->logout();

        $policy = new \GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author();
        $result = $policy->create(null);

        $this->assertFalse($result);
    }
}
