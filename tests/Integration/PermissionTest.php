<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class PermissionTest extends UnitTestCase
{
    public function testPermissionCanBeCreated()
    {
        $permission = Permission::create([
            'role_name' => 'Test' . uniqid(),
            'entity_name' => 'Author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        $this->assertDatabaseHas('governor_permissions', [
            'role_name' => $permission->role_name,
            'entity_name' => 'Author',
            'action_name' => 'create',
        ]);
    }

    public function testPermissionCanBeRetrieved()
    {
        $permission = Permission::create([
            'role_name' => 'TestRole_' . uniqid(),
            'entity_name' => 'Author',
            'action_name' => 'edit',
            'ownership_name' => 'own',
        ]);

        $retrieved = Permission::where('role_name', $permission->role_name)->first();

        $this->assertNotNull($retrieved);
        $this->assertEquals('edit', $retrieved->action_name);
    }

    public function testPermissionCanBeUpdated()
    {
        $permission = Permission::create([
            'role_name' => 'EditRole_' . uniqid(),
            'entity_name' => 'Post',
            'action_name' => 'view',
            'ownership_name' => 'any',
        ]);

        $permission->update(['ownership_name' => 'own']);

        $this->assertEquals('own', $permission->fresh()->ownership_name);
    }

    public function testPermissionCanBeDeleted()
    {
        $permission = Permission::create([
            'role_name' => 'DeleteRole_' . uniqid(),
            'entity_name' => 'Post',
            'action_name' => 'delete',
            'ownership_name' => 'any',
        ]);

        $id = $permission->id;
        $permission->delete();

        $this->assertDatabaseMissing('governor_permissions', ['id' => $id]);
    }

    public function testPermissionBelongsToRole()
    {
        $roleName = 'RoleTest_' . uniqid();
        $role = Role::firstOrCreate(['name' => $roleName]);
        
        $permission = Permission::create([
            'role_name' => $roleName,
            'entity_name' => 'Author',
            'action_name' => 'view',
            'ownership_name' => 'any',
        ]);

        $this->assertNotNull($permission->role());
    }
}
