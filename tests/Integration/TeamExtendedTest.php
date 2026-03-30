<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class TeamExtendedTest extends UnitTestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function testOwnerNameAttribute()
    {
        $teamName = 'Owner Team' . uniqid();
        $team = Team::create(['name' => $teamName, 'description' => 'test']);

        $this->assertEquals($this->user->name, $team->owner_name);
    }

    public function testPermissionsRelationship()
    {
        $teamName = 'Perm Team' . uniqid();
        $team = Team::create(['name' => $teamName, 'description' => 'test']);

        Permission::create([
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
            'team_id' => $team->id,
        ]);

        $team->refresh();

        $this->assertTrue($team->permissions->isNotEmpty());
    }

    public function testOwnerRelationship()
    {
        $teamName = 'Owner Team' . uniqid();
        $team = Team::create(['name' => $teamName, 'description' => 'test']);

        $this->assertNotNull($team->owner);
        $this->assertEquals($this->user->id, $team->owner->id);
    }
}
