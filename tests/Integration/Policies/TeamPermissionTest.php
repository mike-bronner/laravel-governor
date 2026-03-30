<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Policies;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class TeamPermissionTest extends UnitTestCase
{
    protected $user;
    protected $team;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->roles()->sync([]);
        $this->actingAs($this->user);
        $this->team = Team::create([
            'name' => 'Team Perm Test',
            'description' => 'test',
        ]);
        $this->user->teams()->attach($this->team);
    }

    public function testUserCanAccessViaTeamPermission()
    {
        Permission::create([
            'entity_name' => 'Author (Laravel Governor)',
            'action_name' => 'create',
            'ownership_name' => 'any',
            'team_id' => $this->team->id,
        ]);

        $this->assertTrue($this->user->can('create', Author::class));
    }

    public function testUserCannotAccessWithoutTeamPermission()
    {
        $this->assertFalse($this->user->can('create', Author::class));
    }
}
