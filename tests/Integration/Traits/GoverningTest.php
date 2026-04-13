<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Traits;

use GeneaLabs\LaravelGovernor\GovernorOwnable;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class GoverningTest extends UnitTestCase
{
    protected User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function testGovernorOwnedTeamsReturnsTeamsOwnedViaPolymorphic(): void
    {
        $team1 = (new Team)->create([
            'name' => 'Team Alpha',
            'description' => 'First team',
        ]);
        $team2 = (new Team)->create([
            'name' => 'Team Beta',
            'description' => 'Second team',
        ]);

        $ownedTeams = $this->user->governorOwnedTeams();

        $this->assertCount(2, $ownedTeams);
        $this->assertTrue($ownedTeams->contains('id', $team1->id));
        $this->assertTrue($ownedTeams->contains('id', $team2->id));
    }

    public function testGovernorOwnedTeamsExcludesOtherUsersTeams(): void
    {
        $otherUser = User::factory()->create();

        $myTeam = (new Team)->create([
            'name' => 'My Team',
            'description' => 'Mine',
        ]);

        $this->actingAs($otherUser);
        $otherTeam = (new Team)->create([
            'name' => 'Other Team',
            'description' => 'Not mine',
        ]);

        $this->actingAs($this->user);
        $ownedTeams = $this->user->governorOwnedTeams();

        $this->assertCount(1, $ownedTeams);
        $this->assertTrue($ownedTeams->contains('id', $myTeam->id));
        $this->assertFalse($ownedTeams->contains('id', $otherTeam->id));
    }

    public function testGovernorOwnedTeamsReturnsEmptyCollectionWhenNoTeams(): void
    {
        $ownedTeams = $this->user->governorOwnedTeams();

        $this->assertCount(0, $ownedTeams);
    }
}
