<?php

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\TeamInvitation;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class TeamTest extends UnitTestCase
{
    public function setUp() : void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->team = (new Team)->create([
            "name" => "Test Team",
            "description" => "bla bla bla",
        ]);
        $this->user->teams()->attach($this->team);
        $this->team->load("members");
    }

    public function testMembersRelationship()
    {
        $this->assertTrue($this->team->members->contains($this->user));
    }

    public function testOwnedByRelationship()
    {
        $this->assertEquals($this->team->ownedBy->email, $this->user->email);
    }

    public function testInvitationsRelationship()
    {
        $invitation = (new TeamInvitation)->create([
            "team_id" => $this->team->id,
            "email" => "test1@example.com",
        ]);

        $this->assertTrue($this->team->invitations->contains($invitation));
    }

    public function testPermissionsRelationship()
    {
        $permission = (new Permission)->firstOrCreate([
            "team_id" => $this->team->id,
            "entity_name" => "author",
            "action_name" => "create",
            "ownership_name" => "any",
        ]);

        $this->assertTrue($this->team->permissions->contains($permission));
    }
}
