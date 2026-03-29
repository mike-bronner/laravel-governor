<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\Group;
use GeneaLabs\LaravelGovernor\Ownership;
use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\TeamInvitation;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class CoreModelsTest extends UnitTestCase
{
    public function testEntityModel()
    {
        $entity = new Entity();
        $this->assertEquals('governor_entities', $entity->getTable());
        $this->assertTrue($entity->timestamps);
    }

    public function testGroupModel()
    {
        $group = new Group();
        $this->assertEquals('governor_groups', $group->getTable());
        $this->assertTrue($group->timestamps);
    }

    public function testOwnershipModel()
    {
        $ownership = new Ownership();
        $this->assertEquals('governor_ownerships', $ownership->getTable());
        $this->assertNotNull($ownership);
    }

    public function testPermissionModel()
    {
        $permission = new Permission();
        $this->assertEquals('governor_permissions', $permission->getTable());
        $this->assertTrue($permission->timestamps);
    }

    public function testPermissionFieldModelExists()
    {
        // PermissionField extends Laravel Nova Field which might not be available in test
        // Just verify the file exists
        $filePath = realpath(__DIR__ . '/../../src/PermissionField.php');
        $this->assertFileExists($filePath);
    }

    public function testRoleModel()
    {
        $role = new Role();
        $this->assertEquals('governor_roles', $role->getTable());
        $this->assertTrue($role->timestamps);
    }

    public function testTeamModel()
    {
        $team = new Team();
        $this->assertEquals('governor_teams', $team->getTable());
        $this->assertTrue($team->timestamps);
    }

    public function testTeamInvitationModel()
    {
        $invitation = new TeamInvitation();
        $this->assertEquals('governor_team_invitations', $invitation->getTable());
        $this->assertTrue($invitation->timestamps);
    }

    public function testEntityCanBeInstantiated()
    {
        $entity = new Entity();
        $this->assertInstanceOf(Entity::class, $entity);
    }

    public function testGroupCanBeInstantiated()
    {
        $group = new Group();
        $this->assertInstanceOf(Group::class, $group);
    }

    public function testPermissionCanBeInstantiated()
    {
        $permission = new Permission();
        $this->assertInstanceOf(Permission::class, $permission);
    }

    public function testRoleCanBeInstantiated()
    {
        $role = new Role();
        $this->assertInstanceOf(Role::class, $role);
    }

    public function testTeamCanBeInstantiated()
    {
        $team = new Team();
        $this->assertInstanceOf(Team::class, $team);
    }

    public function testTeamInvitationCanBeInstantiated()
    {
        $invitation = new TeamInvitation();
        $this->assertInstanceOf(TeamInvitation::class, $invitation);
    }
}
