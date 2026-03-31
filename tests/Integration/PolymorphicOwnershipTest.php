<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\GovernorOwnable;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class PolymorphicOwnershipTest extends UnitTestCase
{
    protected User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    // --- GovernorOwnable model tests ---

    public function testGovernorOwnableIsCreatedWhenGovernableModelIsCreated()
    {
        $author = Author::factory()->create();

        $ownable = GovernorOwnable::where('ownable_type', Author::class)
            ->where('ownable_id', $author->getKey())
            ->first();

        $this->assertNotNull($ownable);
        $this->assertEquals($this->user->id, $ownable->user_id);
    }

    public function testGovernorOwnableOwnableRelationship()
    {
        $author = Author::factory()->create();

        $ownable = GovernorOwnable::where('ownable_type', Author::class)
            ->where('ownable_id', $author->getKey())
            ->first();

        $this->assertInstanceOf(Author::class, $ownable->ownable);
        $this->assertEquals($author->id, $ownable->ownable->id);
    }

    public function testGovernorOwnableOwnerRelationship()
    {
        $author = Author::factory()->create();

        $ownable = GovernorOwnable::where('ownable_type', Author::class)
            ->where('ownable_id', $author->getKey())
            ->first();

        $this->assertInstanceOf(User::class, $ownable->owner);
        $this->assertEquals($this->user->id, $ownable->owner->id);
    }

    public function testGovernorOwnableTableName()
    {
        $ownable = new GovernorOwnable();

        $this->assertEquals('governor_ownables', $ownable->getTable());
    }

    public function testGovernorOwnableFillableAttributes()
    {
        $ownable = new GovernorOwnable();

        $this->assertEquals(['ownable_type', 'ownable_id', 'user_id'], $ownable->getFillable());
    }

    // --- governorOwner() MorphOne relationship tests ---

    public function testGovernorOwnerRelationshipReturnsOwnable()
    {
        $author = Author::factory()->create();

        $ownable = $author->governorOwner;

        $this->assertInstanceOf(GovernorOwnable::class, $ownable);
        $this->assertEquals($this->user->id, $ownable->user_id);
        $this->assertEquals(Author::class, $ownable->ownable_type);
        $this->assertEquals($author->getKey(), $ownable->ownable_id);
    }

    public function testGovernorOwnerRelationshipReturnsNullWhenNoOwner()
    {
        $author = Author::factory()->create();

        // Remove the ownership record
        GovernorOwnable::where('ownable_type', Author::class)
            ->where('ownable_id', $author->getKey())
            ->delete();

        $author->unsetRelation('governorOwner');

        $this->assertNull($author->governorOwner);
    }

    public function testGovernorOwnerCanBeEagerLoaded()
    {
        $author1 = Author::factory()->create();
        $author2 = Author::factory()->create();

        $authors = Author::with('governorOwner')
            ->whereIn('id', [$author1->id, $author2->id])
            ->get();

        // Verify eager loading populated the relation without additional queries
        foreach ($authors as $author) {
            $this->assertTrue($author->relationLoaded('governorOwner'));
            $this->assertInstanceOf(GovernorOwnable::class, $author->governorOwner);
            $this->assertEquals($this->user->id, $author->governorOwner->user_id);
        }
    }

    // --- Attach/detach via polymorphic relationship ---

    public function testAttachOwnershipViaPolymorphicRelationship()
    {
        $author = Author::factory()->create();

        // Remove auto-created ownership
        GovernorOwnable::where('ownable_type', Author::class)
            ->where('ownable_id', $author->getKey())
            ->delete();
        $author->unsetRelation('governorOwner');

        // Manually attach via the relationship
        $otherUser = User::factory()->create();
        $author->governorOwner()->create([
            'user_id' => $otherUser->id,
        ]);

        $author->unsetRelation('governorOwner');
        $this->assertNotNull($author->governorOwner);
        $this->assertEquals($otherUser->id, $author->governorOwner->user_id);
    }

    public function testDetachOwnershipViaPolymorphicRelationship()
    {
        $author = Author::factory()->create();

        $this->assertNotNull($author->governorOwner);

        // Detach by deleting the relationship
        $author->governorOwner()->delete();
        $author->unsetRelation('governorOwner');

        $this->assertNull($author->governorOwner);
    }

    // --- getGovernorOwnedByAttribute accessor tests ---

    public function testGetGovernorOwnedByAttributeReturnsUserIdFromPolymorphicTable()
    {
        $author = Author::factory()->create();

        $this->assertEquals($this->user->id, $author->governor_owned_by);
    }

    public function testGetGovernorOwnedByAttributeReturnsNullWhenNoOwnership()
    {
        $author = Author::factory()->create();

        GovernorOwnable::where('ownable_type', Author::class)
            ->where('ownable_id', $author->getKey())
            ->delete();
        $author->unsetRelation('governorOwner');

        // Clear the column attribute too so we test the full fallback path
        $author->setRawAttributes(
            array_diff_key($author->getAttributes(), ['governor_owned_by' => true])
        );

        $this->assertNull($author->governor_owned_by);
    }

    public function testGetGovernorOwnedByAttributeFallsBackToColumnValue()
    {
        $author = Author::factory()->create();

        // Remove the polymorphic record so the accessor falls back to column
        GovernorOwnable::where('ownable_type', Author::class)
            ->where('ownable_id', $author->getKey())
            ->delete();
        $author->unsetRelation('governorOwner');

        // The raw attribute should still hold the value from CreatingListener
        $this->assertEquals($this->user->id, $author->governor_owned_by);
    }

    // --- getOwnedByAttribute (deprecated) accessor tests ---

    public function testGetOwnedByAttributeReturnsOwnerModel()
    {
        $author = Author::factory()->create();

        $owner = $author->ownedBy;

        $this->assertInstanceOf(User::class, $owner);
        $this->assertEquals($this->user->id, $owner->id);
    }

    public function testGetOwnedByAttributeReturnsNullWhenNoOwnership()
    {
        $author = Author::factory()->create();

        GovernorOwnable::where('ownable_type', Author::class)
            ->where('ownable_id', $author->getKey())
            ->delete();
        $author->unsetRelation('governorOwner');

        $this->assertNull($author->ownedBy);
    }

    // --- Eager loading works after removing unsetRelation ---

    public function testEagerLoadedGovernorOwnerIsNotDiscardedOnAccessorAccess()
    {
        Author::factory()->count(3)->create();

        $authors = Author::with('governorOwner')->get();

        // Accessing governor_owned_by should use the already-loaded relation,
        // not discard it (which was the N+1 bug).
        foreach ($authors as $author) {
            $this->assertTrue($author->relationLoaded('governorOwner'));
            $ownedBy = $author->governor_owned_by;
            $this->assertEquals($this->user->id, $ownedBy);
            // Relation should still be loaded after accessor access
            $this->assertTrue($author->relationLoaded('governorOwner'));
        }
    }
}
