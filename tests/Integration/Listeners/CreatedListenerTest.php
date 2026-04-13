<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Listeners;

use GeneaLabs\LaravelGovernor\Listeners\CreatedListener;
use GeneaLabs\LaravelGovernor\Listeners\CreatingListener;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\AuthorWithoutGovernable;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class CreatedListenerTest extends UnitTestCase
{
    protected User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function testCreatedListenerAssignsMemberRoleToNewUser()
    {
        $newUser = User::factory()->create();

        $this->assertTrue($newUser->roles->contains('Member'));
    }

    public function testCreatedListenerSkipsTenancyModels()
    {
        $listener = new CreatedListener();
        $roleCountBefore = \GeneaLabs\LaravelGovernor\Assignment::count();

        $listener->handle('eloquent.created: Hyn\\Tenancy\\Models\\Website', [new \stdClass()]);

        $this->assertEquals($roleCountBefore, \GeneaLabs\LaravelGovernor\Assignment::count());
    }

    public function testCreatedListenerSkipsNonAuthModels()
    {
        $listener = new CreatedListener();
        $author = Author::factory()->create();
        $roleCountBefore = \GeneaLabs\LaravelGovernor\Assignment::count();

        $listener->handle('eloquent.created: ' . Author::class, [$author]);

        $this->assertEquals($roleCountBefore, \GeneaLabs\LaravelGovernor\Assignment::count());
    }

    public function testCreatingListenerSetsOwnerOnGovernable()
    {
        $author = Author::factory()->create();

        $this->assertEquals($this->user->id, $author->governor_owned_by);
    }

    public function testCreatingListenerSkipsNonGovernableModels()
    {
        $listener = new CreatingListener();
        $model = new AuthorWithoutGovernable(['name' => 'Test']);

        $listener->handle('eloquent.creating: ' . AuthorWithoutGovernable::class, [$model]);

        $this->assertNull($model->governor_owned_by ?? null);
    }

    public function testCreatingListenerDoesNotOverrideExistingOwner()
    {
        $otherUser = User::factory()->create();

        $author = new Author(['name' => 'Test']);
        $author->governor_owned_by = $otherUser->id;
        $author->save();

        $this->assertEquals($otherUser->id, $author->governor_owned_by);
    }

    public function testCreatedListenerCreatesPolymorphicOwnershipForGovernableModel()
    {
        $author = Author::factory()->create();

        $this->assertDatabaseHas('governor_ownables', [
            'ownable_type' => Author::class,
            'ownable_id' => $author->getKey(),
            'user_id' => $this->user->id,
        ]);
    }

    public function testCreatedListenerSkipsOwnershipForNonGovernableModel()
    {
        $listener = new CreatedListener();
        $model = AuthorWithoutGovernable::create(['name' => 'Test Non-Governable']);

        $this->assertDatabaseMissing('governor_ownables', [
            'ownable_type' => AuthorWithoutGovernable::class,
            'ownable_id' => $model->getKey(),
        ]);
    }

    public function testCreatedListenerSkipsOwnershipWhenNoAuthAndNoExplicitOwner()
    {
        auth()->logout();

        $listener = new CreatedListener();

        // Create an author without auth — the CreatingListener won't set governor_owned_by
        // and there's no auth user, so no ownership record should be created
        $author = new Author(['name' => 'Unowned']);
        // Bypass the creating listener's auth check by setting no owner
        $author->setRawAttributes(array_merge($author->getAttributes(), ['name' => 'Unowned']));

        // Directly call the listener to test the no-owner path
        $author->saveQuietly();
        $listener->handle('eloquent.created: ' . Author::class, [$author]);

        $this->assertDatabaseMissing('governor_ownables', [
            'ownable_type' => Author::class,
            'ownable_id' => $author->getKey(),
        ]);
    }

    public function testCreatedListenerUsesExplicitColumnValueForOwnership()
    {
        $otherUser = User::factory()->create();

        $author = new Author(['name' => 'Explicit Owner']);
        $author->governor_owned_by = $otherUser->id;
        $author->save();

        $this->assertDatabaseHas('governor_ownables', [
            'ownable_type' => Author::class,
            'ownable_id' => $author->getKey(),
            'user_id' => $otherUser->id,
        ]);
    }
}
