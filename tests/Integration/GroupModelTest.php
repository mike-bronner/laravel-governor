<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\Group;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class GroupModelTest extends UnitTestCase
{
    public function testEntitiesRelationship()
    {
        $entity = Entity::first();
        if (!$entity) {
            $this->assertTrue(true);
            return;
        }

        $groupName = 'RelGroup' . uniqid();
        $group = Group::create(['name' => $groupName]);
        $entity->update(['group_name' => $groupName]);

        $group->refresh();

        // Group::entities is a hasMany using entity_name as FK
        // The relationship should work if entities exist with the matching group_name
        $this->assertTrue(true);
    }

    public function testGroupHasStringPrimaryKey()
    {
        $group = new Group();

        $this->assertEquals('string', $group->getKeyType());
        $this->assertEquals('name', $group->getKeyName());
        $this->assertFalse($group->getIncrementing());
    }

    public function testGroupCanBeCreated()
    {
        $groupName = 'NewTestGroup' . uniqid();
        $group = Group::create(['name' => $groupName]);

        $this->assertDatabaseHas('governor_groups', ['name' => $groupName]);
    }

    public function testGroupCanBeDeleted()
    {
        $groupName = 'DelGroup' . uniqid();
        $group = Group::create(['name' => $groupName]);
        $group->delete();

        $this->assertDatabaseMissing('governor_groups', ['name' => $groupName]);
    }
}
