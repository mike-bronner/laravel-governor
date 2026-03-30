<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Traits;

use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class GoverningTeamSwitcherTest extends UnitTestCase
{
    protected User $user;
    protected Team $teamA;
    protected Team $teamB;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->teamA = (new Team)->create([
            'name' => 'Team Alpha',
            'description' => 'First team',
        ]);
        $this->teamB = (new Team)->create([
            'name' => 'Team Beta',
            'description' => 'Second team',
        ]);

        $this->user->teams()->attach([$this->teamA->id, $this->teamB->id]);
    }

    public function testCurrentTeamRelationshipReturnsNull(): void
    {
        $this->assertNull($this->user->currentTeam);
    }

    public function testCurrentTeamRelationshipReturnsTeam(): void
    {
        $this->user->update(['current_team_id' => $this->teamA->id]);
        $this->user->refresh();

        $this->assertNotNull($this->user->currentTeam);
        $this->assertEquals($this->teamA->id, $this->user->currentTeam->id);
    }

    public function testSwitchTeamUpdatesCurrentTeamId(): void
    {
        $this->user->switchTeam($this->teamA->id);

        $this->assertEquals($this->teamA->id, $this->user->current_team_id);
        $this->assertEquals($this->teamA->id, $this->user->currentTeam->id);
    }

    public function testSwitchTeamCanSwitchBetweenTeams(): void
    {
        $this->user->switchTeam($this->teamA->id);
        $this->assertEquals($this->teamA->id, $this->user->current_team_id);

        $this->user->switchTeam($this->teamB->id);
        $this->assertEquals($this->teamB->id, $this->user->current_team_id);
    }
}
