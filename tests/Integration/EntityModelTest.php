<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\Group;
use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class EntityModelTest extends UnitTestCase
{
    public function testGroupRelationship()
    {
        $entity = Entity::first();
        $this->assertNotNull($entity, 'Expected at least one entity to exist from seeding');

        $groupName = 'TestGroup' . uniqid();
        $group = Group::create(['name' => $groupName]);

        $entity->group_name = $groupName;
        $entity->save();
        $entity->refresh();

        $this->assertNotNull($entity->group);
        $this->assertEquals($groupName, $entity->group->name);
    }

    public function testPermissionsRelationship()
    {
        $entity = Entity::first();

        Permission::create([
            'role_name' => 'Member',
            'entity_name' => $entity->name,
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        $entity->refresh();

        $this->assertTrue($entity->permissions->isNotEmpty());
    }

    public function testEntityHasStringPrimaryKey()
    {
        $entity = new Entity();

        $this->assertEquals('string', $entity->getKeyType());
        $this->assertEquals('name', $entity->getKeyName());
        $this->assertFalse($entity->getIncrementing());
    }
}
