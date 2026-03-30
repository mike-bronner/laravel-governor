<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Ownership;
use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class OwnershipModelTest extends UnitTestCase
{
    public function testPermissionsRelationship()
    {
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        $ownership = Ownership::where('name', 'any')->first();

        $this->assertTrue($ownership->permissions->isNotEmpty());
    }

    public function testOwnershipHasStringPrimaryKey()
    {
        $ownership = new Ownership();

        $this->assertEquals('string', $ownership->getKeyType());
        $this->assertEquals('name', $ownership->getKeyName());
        $this->assertFalse($ownership->getIncrementing());
    }

    public function testOwnershipTableName()
    {
        $ownership = new Ownership();

        $this->assertEquals('governor_ownerships', $ownership->getTable());
    }
}
