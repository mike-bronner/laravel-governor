<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Traits;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class GoverningExtendedTest extends UnitTestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function testEffectivePermissionsAttributeWithAny()
    {
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        $permissions = app("governor-permissions");
        app()->instance("governor-permissions", $permissions);

        $effective = $this->user->effectivePermissions;

        $this->assertTrue($effective->isNotEmpty());
        $this->assertTrue($effective->contains(function ($p) {
            return $p->ownership_name === 'any' && $p->action_name === 'create';
        }));
    }

    public function testEffectivePermissionsAttributeWithOwn()
    {
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'update',
            'ownership_name' => 'own',
        ]);

        $permissions = app("governor-permissions");
        app()->instance("governor-permissions", $permissions);

        $effective = $this->user->effectivePermissions;

        $this->assertTrue($effective->contains(function ($p) {
            return $p->ownership_name === 'own' && $p->action_name === 'update';
        }));
    }

    public function testEffectivePermissionsMergesMultipleOwnershipsForSameAction()
    {
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'view',
            'ownership_name' => 'any',
        ]);
        Permission::create([
            'role_name' => 'SuperAdmin',
            'entity_name' => 'author',
            'action_name' => 'view',
            'ownership_name' => 'own',
        ]);

        $this->user->roles()->attach('SuperAdmin');

        $permissions = app("governor-permissions");
        app()->instance("governor-permissions", $permissions);

        $effective = $this->user->effectivePermissions;

        $viewPerms = $effective->filter(fn($p) => $p->action_name === 'view');
        $this->assertTrue($viewPerms->isNotEmpty());
    }

    public function testTeamsRelationshipOnGoverning()
    {
        $team = Team::create(['name' => 'Test Team G', 'description' => 'desc']);
        $this->user->teams()->attach($team);

        $this->assertTrue($this->user->teams->contains($team));
    }
}
