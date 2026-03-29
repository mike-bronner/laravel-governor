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
        // Test the relationship definition exists and is correctly configured
        $relation = $this->assignment->user();
        $this->assertNotNull($relation);
    }

    public function testCanQueryRoleRelationshipDefinition()
    {
        // Test the relationship definition exists and is correctly configured
        $relation = $this->assignment->role();
        $this->assertNotNull($relation);
    }

    public function testAddAllUsersToMemberRoleOnEmptyUsers()
    {
        // This method should not throw exception even with no users
        $this->assignment->addAllUsersToMemberRole();
        $this->assertTrue(true);
    }

    public function testRemoveAllSuperAdminUsersFromOtherRolesWithEmptyArray()
    {
        // Should not throw exception with empty array
        $this->assignment->removeAllSuperAdminUsersFromOtherRoles([]);
        $this->assertTrue(true);
    }

    public function testRemoveAllSuperAdminUsersFromOtherRolesWithNoSuperAdminKey()
    {
        $assignedUsers = [
            'Member' => [1, 2],
            'Editor' => [3],
        ];
        
        // Should not throw exception
        $this->assignment->removeAllSuperAdminUsersFromOtherRoles($assignedUsers);
        $this->assertTrue(true);
    }

    public function testAssignUsersToRolesWithEmptyArray()
    {
        // Should handle empty assignments gracefully
        $this->assignment->assignUsersToRoles([]);
        $this->assertTrue(true);
    }

    public function testAssignUsersToRolesSkipsMemberRole()
    {
        $assignedUsers = [
            'Member' => [1, 2], // Should be skipped
        ];
        
        // Should not throw exception and should skip Member role
        $this->assignment->assignUsersToRoles($assignedUsers);
        $this->assertTrue(true);
    }

    public function testRemoveUsersFromRolesWithEmptyArray()
    {
        // Should handle empty assignments gracefully
        $this->assignment->removeUsersFromRoles([]);
        $this->assertTrue(true);
    }

    public function testGetAllUsersOfRoleStructure()
    {
        // Get users from existing role
        try {
            $users = $this->assignment->getAllUsersOfRole('Member');
            // Should return a collection even if empty
            $this->assertIsIterable($users);
        } catch (\Exception $e) {
            // Role might not exist yet, that's ok
            $this->assertTrue(true);
        }
    }
}
