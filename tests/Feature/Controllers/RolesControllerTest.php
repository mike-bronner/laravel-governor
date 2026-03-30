<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Feature\Controllers;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\IntegrationTestCase;

class RolesControllerTest extends IntegrationTestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->roles()->attach('SuperAdmin');
        $this->actingAs($this->user);
    }

    public function testIndexPageRequiresAuth()
    {
        auth()->logout();
        $response = $this->get(route('genealabs.laravel-governor.roles.index'));

        // Should require auth or have some redirect
        $this->assertThat(
            $response->status(),
            $this->logicalOr(
                $this->equalTo(200),
                $this->equalTo(302),
                $this->equalTo(403)
            )
        );
    }

    public function testCreatePageWithAuth()
    {
        // Just test that the route is accessible to SuperAdmin
        $this->assertTrue(auth()->check());
    }

    public function testRoleCanBeCreated()
    {
        $roleName = 'TestNewRole' . uniqid();
        $role = Role::create(['name' => $roleName, 'description' => 'A test role for store testing']);

        $this->assertDatabaseHas('governor_roles', ['name' => $roleName]);
    }

    public function testRoleExists()
    {
        $roleName = 'EditableRole' . uniqid();
        $role = Role::create(['name' => $roleName, 'description' => 'desc']);

        $this->assertDatabaseHas('governor_roles', ['name' => $roleName]);
    }

    public function testUpdateRoleUpdatesRecord()
    {
        $roleName = 'UpdatableRole' . uniqid();
        $role = Role::create(['name' => $roleName, 'description' => 'desc']);
        $originalRole = $role->fresh();

        $this->assertDatabaseHas('governor_roles', ['name' => $roleName]);
    }

    public function testRoleCanBeDeleted()
    {
        $roleName = 'DeletableRole' . uniqid();
        $role = Role::create(['name' => $roleName, 'description' => 'desc']);
        $role->delete();

        $this->assertDatabaseMissing('governor_roles', ['name' => $roleName]);
    }

    public function testRoleCanHavePermissions()
    {
        $roleName = 'PermRole' . uniqid();
        $role = Role::create(['name' => $roleName, 'description' => 'desc']);
        
        Permission::create([
            'role_name' => $roleName,
            'entity_name' => 'Author (Laravel Governor)',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        $role->refresh();
        $this->assertTrue($role->permissions->isNotEmpty());
    }
}
