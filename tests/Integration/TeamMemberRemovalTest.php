<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
use LogicException;

class TeamMemberRemovalTest extends UnitTestCase
{
    protected User $owner;
    protected User $member;
    protected Team $team;

    public function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->actingAs($this->owner);

        $this->team = (new Team)->create([
            'name' => 'Test Team',
            'description' => 'A test team',
        ]);

        $this->owner->teams()->attach($this->team);

        $this->member = User::factory()->create();
        $this->team->members()->attach($this->member);

        $this->team->load('members');
    }

    public function testOwnerCannotBeDetachedFromTeamMembers(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The team owner cannot be removed from their own team.');

        $this->team->members()->detach($this->owner);
    }

    public function testOwnerCannotBeRemovedViaRemoveMemberMethod(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The team owner cannot be removed from their own team.');

        $this->team->removeMember($this->owner);
    }

    public function testOtherMembersCanBeDetachedFromTeam(): void
    {
        $this->assertTrue($this->team->members->contains($this->member));

        $this->team->members()->detach($this->member);
        $this->team->load('members');

        $this->assertFalse($this->team->members->contains($this->member));
    }

    public function testOtherMembersCanBeRemovedViaRemoveMemberMethod(): void
    {
        $this->assertTrue($this->team->members->contains($this->member));

        $this->team->removeMember($this->member);
        $this->team->load('members');

        $this->assertFalse($this->team->members->contains($this->member));
    }

    public function testOwnerCannotBeDetachedUsingIdDirectly(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The team owner cannot be removed from their own team.');

        $this->team->members()->detach($this->owner->getKey());
    }

    public function testOwnerCannotBeDetachedInBulkDetach(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The team owner cannot be removed from their own team.');

        $this->team->members()->detach([
            $this->member->getKey(),
            $this->owner->getKey(),
        ]);
    }

    public function testDetachAllPreventsRemovalWhenOwnerIsMember(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The team owner cannot be removed from their own team.');

        $this->team->members()->detach();
    }

    public function testOwnerRemainsAfterRemovingOtherMember(): void
    {
        $initialCount = $this->team->members()->count();

        $this->team->members()->detach($this->member);

        $this->assertEquals($initialCount - 1, $this->team->members()->count());
        $this->assertTrue(
            $this->team->members()->where('user_id', $this->owner->getKey())->exists()
        );
        $this->assertFalse(
            $this->team->members()->where('user_id', $this->member->getKey())->exists()
        );
    }
}
