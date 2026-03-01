<?php

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Traits;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class GoverningTest extends UnitTestCase
{
    protected $author;
    protected $team;
    protected $user;

    public function setUp() : void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->team = (new Team)->create([
            "name" => "Test Team",
            "description" => "bla bla bla",
        ]);
        $this->author = Author::factory()->create();
        $this->author->teams()->attach($this->team);
    }

    public function testHasRole()
    {
        $this->assertTrue($this->user->hasRole("Member"));
    }

    public function testRolesRelationship()
    {
        $role = (new Role)
            ->where("name", "Member")
            ->first();

        $this->assertTrue($this->user->roles->contains($role));
    }

    public function testOwnedTeamsRelationship()
    {
        $teams = (new Team)
            ->get();

        $this->assertTrue($teams->contains($this->team));
        $this->assertTrue($this->user->ownedTeams->contains($this->team));
    }

    public function testPermissionsAttribute()
    {
        $permission = (new Permission)->create([
            "role_name" => "Member",
            "entity_name" => "author",
            "action_name" => "delete",
            "ownership_name" => "any"
        ]);
        $permissions = (new Permission)
            ->toBase()
            ->get();
        app()->instance("governor-permissions", $permissions);

        $this->assertTrue($this->user->permissions->keyBy("id")->has($permission->id));
    }

    public function testHasRoleWithNonExistingRole()
    {
        $this->assertFalse($this->user->hasRole("Janitor"));
    }

    public function testHasRoleWithoutRoles()
    {
        (new Role)
            ->whereIn("name", ["Member"])
            ->delete();

        $this->assertFalse($this->user->hasRole("Member"));
    }

    public function testHasRoleWhereUserHasNoRole()
    {
        $this->user->roles()->sync([]);

        $this->assertFalse($this->user->hasRole("Member"));
    }
}
