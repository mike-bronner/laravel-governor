<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Assignment;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class AssignmentTest extends UnitTestCase
{
    protected Assignment $assignment;

    public function setUp(): void
    {
        parent::setUp();
        $this->assignment = new Assignment();
    }

    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf(Assignment::class, $this->assignment);
    }

    public function testHasCorrectTableName()
    {
        $this->assertEquals('governor_role_user', $this->assignment->getTable());
    }

    public function testCanQueryUserRelationshipDefinition()
    {
        $relation = $this->assignment->user();
        $this->assertNotNull($relation);
    }

    public function testCanQueryRoleRelationshipDefinition()
    {
        $relation = $this->assignment->role();
        $this->assertNotNull($relation);
    }

    public function testAssignUsersToRolesAssignsCorrectly()
    {
        $user = User::factory()->create();

        $this->assignment->assignUsersToRoles([
            'SuperAdmin' => [$user->id],
        ]);

        $this->assertTrue($user->fresh()->roles->contains('SuperAdmin'));
    }

    public function testGetAllUsersOfRoleReturnsCollection()
    {
        $users = $this->assignment->getAllUsersOfRole('Member');

        $this->assertIsIterable($users);
    }
}
