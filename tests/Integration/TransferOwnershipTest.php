<?php

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\GovernorOwnable;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
class TransferOwnershipTest extends UnitTestCase
{
    protected User $owner;
    protected User $member;
    protected Team $team;

    public function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->member = User::factory()->create();

        $this->actingAs($this->owner);

        $this->team = (new Team)->create([
            "name" => "Transfer Test Team",
            "description" => "Testing ownership transfer",
        ]);

        $this->team->members()->attach($this->owner);
        $this->team->members()->attach($this->member);
        $this->team->load("members");
    }

    public function testOwnerCanTransferOwnershipToMember(): void
    {
        $this->team->transferOwnership($this->member);

        $this->team->refresh();

        $this->assertEquals($this->member->getKey(), $this->team->governor_owned_by);
    }

    public function testPreviousOwnerRetainsMembershipAfterTransfer(): void
    {
        $this->team->transferOwnership($this->member);

        $this->team->refresh();
        $this->team->load("members");

        $this->assertTrue($this->team->members->contains($this->owner));
    }

    public function testNewOwnerBecomesTeamOwner(): void
    {
        $this->team->transferOwnership($this->member);

        $this->team->refresh();

        $this->assertEquals($this->member->getKey(), $this->team->governor_owned_by);
        $this->assertEquals($this->member->name, $this->team->ownerName);
    }

    public function testTransferOwnershipViaControllerAsOwner(): void
    {
        $response = $this->post(
            route('genealabs.laravel-governor.teams.transfer-ownership', $this->team),
            ["new_owner_id" => $this->member->getKey()]
        );

        $response->assertRedirect(route('genealabs.laravel-governor.teams.index'));

        $this->team->refresh();
        $this->assertEquals($this->member->getKey(), $this->team->governor_owned_by);
    }

    public function testTransferOwnershipViaControllerAsNonOwnerIsForbidden(): void
    {
        $this->actingAs($this->member);

        $response = $this->post(
            route('genealabs.laravel-governor.teams.transfer-ownership', $this->team),
            ["new_owner_id" => $this->member->getKey()]
        );

        $response->assertForbidden();
    }

    public function testTransferOwnershipViaControllerToNonMemberFails(): void
    {
        $nonMember = User::factory()->create();

        $response = $this->post(
            route('genealabs.laravel-governor.teams.transfer-ownership', $this->team),
            ["new_owner_id" => $nonMember->getKey()]
        );

        $response->assertSessionHasErrors("new_owner_id");

        $this->team->refresh();
        $this->assertEquals($this->owner->getKey(), $this->team->governor_owned_by);
    }

    public function testTransferOwnershipRequiresNewOwnerId(): void
    {
        $response = $this->post(
            route('genealabs.laravel-governor.teams.transfer-ownership', $this->team),
            []
        );

        $response->assertSessionHasErrors("new_owner_id");
    }

    public function testTransferOwnershipCreatesPolymorphicRecord(): void
    {
        $this->team->transferOwnership($this->member);

        $this->assertDatabaseHas('governor_ownables', [
            'ownable_type' => Team::class,
            'ownable_id' => $this->team->getKey(),
            'user_id' => $this->member->getKey(),
        ]);
    }

    public function testTransferOwnershipUpdatesExistingPolymorphicRecord(): void
    {
        // First transfer
        $this->team->transferOwnership($this->member);

        // Second transfer back
        $this->team->transferOwnership($this->owner);

        $this->assertDatabaseHas('governor_ownables', [
            'ownable_type' => Team::class,
            'ownable_id' => $this->team->getKey(),
            'user_id' => $this->owner->getKey(),
        ]);

        // Should only have one record, not two
        $count = GovernorOwnable::where('ownable_type', Team::class)
            ->where('ownable_id', $this->team->getKey())
            ->count();
        $this->assertEquals(1, $count);
    }

    public function testTransferOwnershipClearsGovernorOwnerRelation(): void
    {
        $this->team->transferOwnership($this->member);

        $this->assertFalse($this->team->relationLoaded('governorOwner'));
    }

    public function testOwnerNameUsesPolymorphicRelationship(): void
    {
        $this->team->transferOwnership($this->member);
        $this->team->refresh();

        $this->assertEquals($this->member->name, $this->team->ownerName);
    }

    public function testOwnerNameFallsBackToDeprecatedOwnerRelationship(): void
    {
        // Remove polymorphic record but keep column — should fall back to owner()
        GovernorOwnable::where('ownable_type', Team::class)
            ->where('ownable_id', $this->team->getKey())
            ->delete();

        $freshTeam = Team::find($this->team->id);

        $this->assertEquals($this->owner->name, $freshTeam->ownerName);
    }

    public function testPreviousOwnerCannotPerformOwnerActionsAfterTransfer(): void
    {
        $this->team->transferOwnership($this->member);
        $this->team->refresh();

        // The previous owner is no longer the owner
        $this->assertNotEquals($this->owner->getKey(), $this->team->governor_owned_by);

        // Attempting to transfer again as the old owner should fail
        $this->actingAs($this->owner);

        $thirdUser = User::factory()->create();
        $this->team->members()->attach($thirdUser);

        $response = $this->post(
            route('genealabs.laravel-governor.teams.transfer-ownership', $this->team),
            ["new_owner_id" => $thirdUser->getKey()]
        );

        $response->assertForbidden();
    }
}
