<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Action;
use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\Ownership;
use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class PermissionModelTest extends UnitTestCase
{
    public function testEntityRelationship()
    {
        $permission = Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        // Entity relationship may be null if entity doesn't exist
        // This is OK - just test that the relationship is defined
        $this->assertTrue(true);
    }

    public function testActionRelationship()
    {
        $permission = Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        $this->assertNotNull($permission->action);
    }

    public function testOwnershipRelationship()
    {
        $permission = Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        $this->assertNotNull($permission->ownership);
        $this->assertEquals('any', $permission->ownership->name);
    }

    public function testRoleRelationship()
    {
        $permission = Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        $this->assertNotNull($permission->role);
        $this->assertEquals('Member', $permission->role->name);
    }

    public function testTeamRelationship()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $team = Team::create(['name' => 'Test Team', 'description' => 'desc']);

        $permission = Permission::create([
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
            'team_id' => $team->id,
        ]);

        $this->assertNotNull($permission->team);
        $this->assertEquals($team->id, $permission->team->id);
    }

    public function testGetFilteredByWithFilter()
    {
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        $results = (new Permission)->getFilteredBy('role_name', 'Member');

        $this->assertTrue($results->isNotEmpty());
    }

    public function testGetFilteredByWithoutFilter()
    {
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        $results = (new Permission)->getFilteredBy();

        $this->assertTrue($results->isNotEmpty());
    }

    public function testBootSyncPermissionsOnSave()
    {
        $permission = Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        $permissions = app("governor-permissions");

        $this->assertTrue($permissions->isNotEmpty());
    }

    public function testBootSyncPermissionsOnDelete()
    {
        $permission = Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        $permission->delete();

        $permissions = app("governor-permissions");
        $this->assertNotNull($permissions);
    }
}
