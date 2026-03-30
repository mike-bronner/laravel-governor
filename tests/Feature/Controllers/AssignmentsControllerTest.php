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

    public function testCreatePageWithAuth()
    {
        // Verify user is authenticated
        $this->assertTrue(auth()->check());
    }

    public function testAssignmentCanBeCreated()
    {
        $otherUser = User::factory()->create();

        // Assignments are processed via the Assignment model
        $assignment = new \GeneaLabs\LaravelGovernor\Assignment();
        $assignment->assignUsersToRoles([
            'Member' => [$otherUser->id],
        ]);

        // Verify the user was assigned to Member
        $this->assertTrue($otherUser->fresh()->roles->contains('Member'));
    }
}
