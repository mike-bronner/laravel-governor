<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Policies;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class RolePolicyTest extends UnitTestCase
{
    protected $user;
    protected $superAdmin;
    protected $superAdminRole;
    protected $memberRole;

    public function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->roles()->attach('SuperAdmin');

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->superAdminRole = Role::where('name', 'SuperAdmin')->first();
        $this->memberRole = Role::where('name', 'Member')->first();

        // Give Member role permissions on roles
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'Role (Laravel Governor)',
            'action_name' => 'delete',
            'ownership_name' => 'any',
        ]);
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'Role (Laravel Governor)',
            'action_name' => 'forceDelete',
            'ownership_name' => 'any',
        ]);
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'Role (Laravel Governor)',
            'action_name' => 'restore',
            'ownership_name' => 'any',
        ]);
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'Role (Laravel Governor)',
            'action_name' => 'update',
            'ownership_name' => 'any',
        ]);
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'Role (Laravel Governor)',
            'action_name' => 'view',
            'ownership_name' => 'any',
        ]);
    }

    public function testCannotDeleteSuperAdminRole()
    {
        $this->assertFalse($this->user->can('delete', $this->superAdminRole));
    }

    public function testCanDeleteNonSuperAdminRole()
    {
        $role = Role::create(['name' => 'TestRole', 'description' => 'Test']);

        $this->assertTrue($this->user->can('delete', $role));
    }

    public function testCannotForceDeleteSuperAdminRole()
    {
        $this->assertFalse($this->user->can('forceDelete', $this->superAdminRole));
    }

    public function testCanForceDeleteNonSuperAdminRole()
    {
        $role = Role::create(['name' => 'TestRole2', 'description' => 'Test']);

        $this->assertTrue($this->user->can('forceDelete', $role));
    }

    public function testCannotRestoreSuperAdminRole()
    {
        $this->assertFalse($this->user->can('restore', $this->superAdminRole));
    }

    public function testCanRestoreNonSuperAdminRole()
    {
        $role = Role::create(['name' => 'TestRole3', 'description' => 'Test']);

        $this->assertTrue($this->user->can('restore', $role));
    }

    public function testCannotUpdateSuperAdminRole()
    {
        $this->assertFalse($this->user->can('update', $this->superAdminRole));
    }

    public function testCanUpdateNonSuperAdminRole()
    {
        $role = Role::create(['name' => 'TestRole4', 'description' => 'Test']);

        $this->assertTrue($this->user->can('update', $role));
    }

    public function testCannotViewSuperAdminRole()
    {
        $this->assertFalse($this->user->can('view', $this->superAdminRole));
    }

    public function testCanViewNonSuperAdminRole()
    {
        $role = Role::create(['name' => 'TestRole5', 'description' => 'Test']);

        $this->assertTrue($this->user->can('view', $role));
    }

    public function testSuperAdminCannotDeleteSuperAdminRole()
    {
        $this->assertFalse($this->superAdmin->can('delete', $this->superAdminRole));
    }

    public function testSuperAdminCannotUpdateSuperAdminRole()
    {
        $this->assertFalse($this->superAdmin->can('update', $this->superAdminRole));
    }
}
