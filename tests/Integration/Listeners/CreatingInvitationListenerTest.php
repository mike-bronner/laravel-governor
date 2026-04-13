<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Listeners;

use GeneaLabs\LaravelGovernor\GovernorOwnable;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\TeamInvitation;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class CreatingInvitationListenerTest extends UnitTestCase
{
    protected User $user;
    protected Team $team;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->team = (new Team)->create([
            'name' => 'Invitation Test Team',
            'description' => 'Test team',
        ]);
        $this->user->teams()->attach($this->team);
    }

    public function testCreatingInvitationSetsGovernorOwnedByWhenAuthenticated(): void
    {
        $invitation = (new TeamInvitation)->create([
            'team_id' => $this->team->id,
            'email' => 'invited@example.com',
        ]);

        $this->assertEquals($this->user->getKey(), $invitation->governor_owned_by);
    }

    public function testCreatingInvitationCreatesPolymorphicOwnershipRecord(): void
    {
        $invitation = (new TeamInvitation)->create([
            'team_id' => $this->team->id,
            'email' => 'invited2@example.com',
        ]);

        $this->assertDatabaseHas('governor_ownables', [
            'ownable_type' => TeamInvitation::class,
            'ownable_id' => $invitation->getKey(),
            'user_id' => $this->user->getKey(),
        ]);
    }

    public function testCreatingInvitationWithoutAuthDoesNotSetOwner(): void
    {
        auth()->logout();

        $listener = new \GeneaLabs\LaravelGovernor\Listeners\CreatingInvitationListener();
        $invitation = new TeamInvitation([
            'team_id' => $this->team->id,
            'email' => 'invited3@example.com',
        ]);

        $listener->handle($invitation);

        // Token should be set but governor_owned_by should NOT be set
        $this->assertNotNull($invitation->token);
        $this->assertNull($invitation->getAttributes()['governor_owned_by'] ?? null);
    }
}
