<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Feature\Controllers;

use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\IntegrationTestCase;

class TeamsControllerTest extends IntegrationTestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->roles()->attach('SuperAdmin');
        $this->actingAs($this->user);
    }

    public function testIndexPageIsAccessible()
    {
        $response = $this->get(route('genealabs.laravel-governor.teams.index'));

        $response->assertOk();
    }

    public function testCreatePageIsAccessible()
    {
        $response = $this->get(route('genealabs.laravel-governor.teams.create'));

        $response->assertOk();
    }

    public function testStoreCreatesTeam()
    {
        $teamName = 'StoreTeam' . uniqid();

        $response = $this->post(route('genealabs.laravel-governor.teams.store'), [
            'name' => $teamName,
            'description' => 'A new test team',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('governor_teams', ['name' => $teamName]);
    }

    public function testEditPageIsAccessible()
    {
        $team = Team::create(['name' => 'EditTeam' . uniqid(), 'description' => 'desc']);

        $response = $this->get(route('genealabs.laravel-governor.teams.edit', $team));

        $response->assertOk();
    }

    public function testDestroyDeletesTeam()
    {
        $teamName = 'DelTeam' . uniqid();
        $team = Team::create(['name' => $teamName, 'description' => 'desc']);

        $response = $this->delete(route('genealabs.laravel-governor.teams.destroy', $team));

        $response->assertRedirect();
        $this->assertDatabaseMissing('governor_teams', ['name' => $teamName]);
    }
}
