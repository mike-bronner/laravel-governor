<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Feature\Controllers;

use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\IntegrationTestCase;

class AssignmentsControllerTest extends IntegrationTestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->roles()->attach('SuperAdmin');
        $this->actingAs($this->user);
    }

    public function testStoreProcessesAssignments()
    {
        $otherUser = User::factory()->create();

        $response = $this->post(route('genealabs.laravel-governor.assignments.store'), [
            'users' => [
                'Member' => [$otherUser->id],
            ],
        ]);

        $response->assertRedirect();
        $this->assertTrue($otherUser->fresh()->roles->contains('Member'));
    }

    public function testStoreRequiresAuth()
    {
        auth()->logout();

        $response = $this->post(route('genealabs.laravel-governor.assignments.store'), [
            'users' => [],
        ]);

        $response->assertRedirect();
    }
}
