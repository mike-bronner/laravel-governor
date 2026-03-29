<?php

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Listeners;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class CreatedTeamListenerTest extends UnitTestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function testTeamCreationSeedsPermissionsFromOwnerRoles()
    {
        $role = (new Role)->find('Member');
        $this->user->roles()->syncWithoutDetaching([$role->name]);

        (new Permission)->create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);
        (new Permission)->create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'view',
            'ownership_name' => 'own',
        ]);

        $team = (new Team)->create([
            'name' => 'Seeded Team',
            'description' => 'Test team for permission seeding',
        ]);

        $teamPermissions = (new Permission)
            ->where('team_id', $team->getKey())
            ->get();

        $this->assertCount(2, $teamPermissions);

        $createPermission = $teamPermissions
            ->where('action_name', 'create')
            ->where('entity_name', 'author')
            ->first();
        $this->assertNotNull($createPermission);
        $this->assertEquals('any', $createPermission->ownership_name);
        $this->assertNull($createPermission->role_name);

        $viewPermission = $teamPermissions
            ->where('action_name', 'view')
            ->where('entity_name', 'author')
            ->first();
        $this->assertNotNull($viewPermission);
        $this->assertEquals('own', $viewPermission->ownership_name);
    }

    public function testTeamCreationPreservesDefaultBehaviorWhenOwnerHasNoRolePermissions()
    {
        $team = (new Team)->create([
            'name' => 'Empty Team',
            'description' => 'Owner has no role permissions',
        ]);

        $teamPermissions = (new Permission)
            ->where('team_id', $team->getKey())
            ->get();

        $this->assertCount(0, $teamPermissions);
        $this->assertTrue($team->members->contains($this->user));
    }

    public function testNewTeamMemberInheritsTeamPermissions()
    {
        $role = (new Role)->find('Member');
        $this->user->roles()->syncWithoutDetaching([$role->name]);

        (new Permission)->create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'update',
            'ownership_name' => 'any',
        ]);

        $team = (new Team)->create([
            'name' => 'Inherit Team',
            'description' => 'Test inheritance',
        ]);

        $newMember = User::factory()->create();
        $team->members()->attach($newMember);

        // Refresh permissions singleton
        $permissionClass = config('genealabs-laravel-governor.models.permission');
        $permissions = (new $permissionClass)
            ->with('role', 'team')
            ->toBase()
            ->get();
        app()->instance('governor-permissions', $permissions);

        $newMember->load('teams.permissions');

        $teamPermissions = $newMember->teams->flatMap->permissions;
        $this->assertTrue(
            $teamPermissions->contains(function ($p) {
                return $p->entity_name === 'author'
                    && $p->action_name === 'update'
                    && $p->ownership_name === 'any';
            })
        );
    }

    public function testTeamOwnerCanOverridePermissionsAfterCreation()
    {
        $role = (new Role)->find('Member');
        $this->user->roles()->syncWithoutDetaching([$role->name]);

        (new Permission)->create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'delete',
            'ownership_name' => 'own',
        ]);

        $team = (new Team)->create([
            'name' => 'Override Team',
            'description' => 'Test override',
        ]);

        // Owner overrides the seeded permission
        $seededPermission = (new Permission)
            ->where('team_id', $team->getKey())
            ->where('action_name', 'delete')
            ->where('entity_name', 'author')
            ->first();

        $seededPermission->update(['ownership_name' => 'any']);

        $this->assertEquals('any', $seededPermission->fresh()->ownership_name);
    }

    public function testDuplicatePermissionsAcrossRolesAreDeduped()
    {
        $memberRole = (new Role)->find('Member');
        $adminRole = (new Role)->firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'Admin role for testing deduplication']
        );

        $this->user->roles()->syncWithoutDetaching([$memberRole->name, $adminRole->name]);

        // Same permission on both roles
        (new Permission)->create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);
        (new Permission)->create([
            'role_name' => 'Admin',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        $team = (new Team)->create([
            'name' => 'Dedup Team',
            'description' => 'Test deduplication',
        ]);

        $teamPermissions = (new Permission)
            ->where('team_id', $team->getKey())
            ->get();

        // Should only have one team permission for author/create/any
        $this->assertCount(1, $teamPermissions);
    }

    public function testTeamCreationWithoutAuthDoesNotSeedPermissions()
    {
        auth()->logout();

        // Create team directly (simulating non-auth context)
        $team = new Team;
        $team->name = 'No Auth Team';
        $team->description = 'Created without auth';
        $team->save();

        $teamPermissions = (new Permission)
            ->where('team_id', $team->getKey())
            ->get();

        $this->assertCount(0, $teamPermissions);
    }
}
