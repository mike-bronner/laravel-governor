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
        $listener = new CreatedListener();
        $newUser = User::factory()->create();

        // The listener fires automatically via eloquent events
        $this->assertTrue($newUser->roles->contains('Member'));
    }

    public function testCreatedListenerSkipsTenancyModels()
    {
        $listener = new CreatedListener();
        $listener->handle('eloquent.created: Hyn\\Tenancy\\Models\\Website', [new \stdClass()]);

        // No exception thrown
        $this->assertTrue(true);
    }

    public function testCreatedListenerSkipsHostnameModels()
    {
        $listener = new CreatedListener();
        $listener->handle('eloquent.created: Hyn\\Tenancy\\Models\\Hostname', [new \stdClass()]);

        $this->assertTrue(true);
    }

    public function testCreatedListenerSkipsNonAuthModels()
    {
        $listener = new CreatedListener();
        $author = Author::factory()->create();

        // Should not try to sync roles on non-auth model
        $listener->handle('eloquent.created: ' . Author::class, [$author]);
        $this->assertTrue(true);
    }

    public function testCreatingListenerSetsOwnerOnGovernable()
    {
        $author = Author::factory()->create();

        $this->assertEquals($this->user->id, $author->governor_owned_by);
    }

    public function testCreatingListenerSkipsTenancyModels()
    {
        $listener = new CreatingListener();
        $listener->handle('eloquent.creating: Hyn\\Tenancy\\Models\\Website', [new \stdClass()]);

        $this->assertTrue(true);
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
}
