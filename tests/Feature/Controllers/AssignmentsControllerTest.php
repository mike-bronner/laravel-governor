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

    public function testEditPageIsAccessible()
    {
        $response = $this->get(route('genealabs.laravel-governor.assignments.edit', 0));

        $response->assertOk();
    }

    public function testUpdateProcessesAssignments()
    {
        $otherUser = User::factory()->create();

        $response = $this->put(route('genealabs.laravel-governor.assignments.update', 0), [
            'users' => [
                'Member' => [$otherUser->id],
            ],
        ]);

        $response->assertRedirect();
        $this->assertTrue($otherUser->fresh()->roles->contains('Member'));
    }
}
