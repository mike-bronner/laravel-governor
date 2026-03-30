<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Feature\Nova;

use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class TeamSwitcherControllerTest extends UnitTestCase
{
    protected User $user;
    protected Team $teamA;
    protected Team $teamB;
    protected Team $teamC;

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
        $this->teamC = (new Team)->create([
            'name' => 'Team Charlie',
            'description' => 'Not a member',
        ]);

        $this->user->teams()->attach([$this->teamA->id, $this->teamB->id]);
    }

    public function testIndexReturnsUserTeams(): void
    {
        $response = $this->getJson('/genealabs/laravel-governor/nova/team-switcher');

        $response->assertOk()
            ->assertJsonCount(2, 'teams')
            ->assertJsonFragment(['name' => 'Team Alpha'])
            ->assertJsonFragment(['name' => 'Team Beta'])
            ->assertJsonMissing(['name' => 'Team Charlie']);
    }

    public function testIndexReturnsCurrentTeamId(): void
    {
        $this->user->switchTeam($this->teamA->id);

        $response = $this->getJson('/genealabs/laravel-governor/nova/team-switcher');

        $response->assertOk()
            ->assertJson(['currentTeamId' => $this->teamA->id]);
    }

    public function testIndexReturnsNullCurrentTeamIdWhenNoneSet(): void
    {
        $response = $this->getJson('/genealabs/laravel-governor/nova/team-switcher');

        $response->assertOk()
            ->assertJson(['currentTeamId' => null]);
    }

    public function testStoreSwitchesTeam(): void
    {
        $response = $this->postJson('/genealabs/laravel-governor/nova/team-switcher', [
            'team_id' => $this->teamA->id,
        ]);

        $response->assertOk()
            ->assertJson(['currentTeamId' => $this->teamA->id]);

        $this->user->refresh();
        $this->assertEquals($this->teamA->id, $this->user->current_team_id);
    }

    public function testStoreSwitchesBetweenTeams(): void
    {
        $this->postJson('/genealabs/laravel-governor/nova/team-switcher', [
            'team_id' => $this->teamA->id,
        ])->assertOk();

        $response = $this->postJson('/genealabs/laravel-governor/nova/team-switcher', [
            'team_id' => $this->teamB->id,
        ]);

        $response->assertOk()
            ->assertJson(['currentTeamId' => $this->teamB->id]);

        $this->user->refresh();
        $this->assertEquals($this->teamB->id, $this->user->current_team_id);
    }

    public function testStoreRejectsNonMemberTeam(): void
    {
        $response = $this->postJson('/genealabs/laravel-governor/nova/team-switcher', [
            'team_id' => $this->teamC->id,
        ]);

        $response->assertForbidden();
    }

    public function testStoreRequiresTeamId(): void
    {
        $response = $this->postJson('/genealabs/laravel-governor/nova/team-switcher', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('team_id');
    }

    public function testCardDoesNotShowTeamsUserIsNotMemberOf(): void
    {
        $response = $this->getJson('/genealabs/laravel-governor/nova/team-switcher');

        $teamIds = collect($response->json('teams'))->pluck('id')->all();
        $this->assertContains($this->teamA->id, $teamIds);
        $this->assertContains($this->teamB->id, $teamIds);
        $this->assertNotContains($this->teamC->id, $teamIds);
    }
}
