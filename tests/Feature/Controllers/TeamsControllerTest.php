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

    public function testStoreRequiresAuth()
    {
        auth()->logout();

        $response = $this->post(route('genealabs.laravel-governor.teams.store'), [
            'name' => 'NoAuthTeam',
            'description' => 'test',
        ]);

        $response->assertRedirect();
    }
}
