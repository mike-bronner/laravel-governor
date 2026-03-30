<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Http\Requests;

use GeneaLabs\LaravelGovernor\Assignment;
use GeneaLabs\LaravelGovernor\Group;
use GeneaLabs\LaravelGovernor\Http\Requests\CreateAssignmentRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\GroupDeleteRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\StoreGroupRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\StoreRoleRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\TeamStoreRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\UpdateGroupRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\UpdateRoleRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\UpdateTeamRequest;
use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
use Illuminate\Http\Request;

class RequestProcessingTest extends UnitTestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->roles()->attach('SuperAdmin');
        $this->actingAs($this->user);
    }

    public function testStoreGroupRequestProcessing()
    {
        $groupName = 'ProcessGroup' . uniqid();
        $entity = \GeneaLabs\LaravelGovernor\Entity::first();

        $request = new StoreGroupRequest();
        $request->merge([
            'name' => $groupName,
            'entity_names' => [$entity->name],
        ]);
        $request->setUserResolver(fn () => $this->user);

        $request->process();

        $this->assertDatabaseHas('governor_groups', ['name' => $groupName]);
    }

    public function testStoreRoleRequestProcessing()
    {
        $roleName = 'ProcessRole' . uniqid();

        $request = new StoreRoleRequest();
        $request->merge([
            'name' => $roleName,
            'description' => 'A test role',
        ]);
        $request->setUserResolver(fn () => $this->user);

        $request->process();

        $this->assertDatabaseHas('governor_roles', ['name' => $roleName]);
    }

    public function testCreateAssignmentRequestProcessing()
    {
        $otherUser = User::factory()->create();
        $otherUser->roles()->sync([]);

        $request = new CreateAssignmentRequest();
        $request->merge([
            'users' => [
                'Member' => [$otherUser->id],
            ],
        ]);
        $request->setUserResolver(fn () => $this->user);

        $request->process();

        // User should now have Member role
        $otherUser->refresh();
        $this->assertTrue($otherUser->roles->contains('Member'));
    }

    public function testUpdateRoleRequestProcessingWithPermissions()
    {
        $roleName = 'RoleWithPerms' . uniqid();
        $role = Role::create(['name' => $roleName, 'description' => 'test']);

        $request = new UpdateRoleRequest();
        $request->merge([
            'role' => $roleName,
            'name' => $roleName,
            'description' => 'updated',
            'permissions' => [
                'Ungrouped' => [
                    'Author (Laravel Governor)' => [
                        'create' => 'any',
                        'update' => 'own',
                    ],
                ],
            ],
        ]);
        $request->setUserResolver(fn () => $this->user);

        $request->process();

        $role->refresh();
        $this->assertTrue($role->permissions->isNotEmpty());
    }

    public function testTeamStoreRequestProcessing()
    {
        $teamName = 'ProcessTeam' . uniqid();

        $request = new TeamStoreRequest();
        $request->merge([
            'name' => $teamName,
            'description' => 'A test team',
        ]);
        $request->setUserResolver(fn () => $this->user);

        $team = $request->process();

        $this->assertDatabaseHas('governor_teams', ['name' => $teamName]);
        $this->assertNotNull($team);
    }

    public function testGroupDeleteRequestProcessing()
    {
        $groupName = 'DeleteGroup' . uniqid();
        $group = Group::create(['name' => $groupName]);

        $request = new GroupDeleteRequest();
        $request->setRouteResolver(function () use ($group) {
            return new Request([], [], ['group' => $group]);
        });
        $request->merge(['group' => $group]);
        $request->setUserResolver(fn () => $this->user);

        $result = $request->process();

        $this->assertDatabaseMissing('governor_groups', ['name' => $groupName]);
        $this->assertEquals($group->name, $result->name);
    }

    public function testUpdateGroupRequestProcessing()
    {
        $roleName = 'GroupRole' . uniqid();
        $role = Role::create(['name' => $roleName, 'description' => 'test']);

        $request = new UpdateGroupRequest();
        $request->merge([
            'id' => $role->name,
            'name' => $roleName,
            'permissions' => [],
        ]);
        $request->setUserResolver(fn () => $this->user);

        $request->process();

        $role->refresh();
        $this->assertNotNull($role);
    }

    public function testUpdateTeamRequestProcessing()
    {
        $team = Team::create(['name' => 'TeamToUpdate' . uniqid(), 'description' => 'test']);

        $request = new UpdateTeamRequest();
        $request->merge([
            'team' => $team,
            'permissions' => [
                'Ungrouped' => [
                    'Author (Laravel Governor)' => [
                        'create' => 'any',
                    ],
                ],
            ],
        ]);
        $request->setUserResolver(fn () => $this->user);

        $request->process();

        $team->refresh();
        $this->assertTrue($team->permissions->isNotEmpty());
    }
}
