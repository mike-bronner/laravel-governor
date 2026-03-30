<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class RoleModelTest extends UnitTestCase
{
    public function testEntitiesRelationship()
    {
        $role = Role::where('name', 'Member')->first();

        $this->assertNotNull($role->entities());
    }

    public function testPermissionsRelationship()
    {
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        $role = Role::where('name', 'Member')->first();
        
        $this->assertTrue($role->permissions->isNotEmpty());
    }

    public function testUsersRelationship()
    {
        $user = User::factory()->create();
        
        $role = Role::where('name', 'Member')->first();
        
        $this->assertTrue($role->users->contains($user));
    }

    public function testRoleHasStringPrimaryKey()
    {
        $role = new Role();

        $this->assertEquals('string', $role->getKeyType());
        $this->assertEquals('name', $role->getKeyName());
        $this->assertFalse($role->getIncrementing());
    }
}
