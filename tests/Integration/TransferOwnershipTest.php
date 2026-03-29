<?php

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
use InvalidArgumentException;

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

    public function testTransferToNonMemberIsRejected(): void
    {
        $nonMember = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The new owner must be an existing member of the team.");

        $this->team->transferOwnership($nonMember);
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

        // The InvalidArgumentException from the model should cause a 500
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
