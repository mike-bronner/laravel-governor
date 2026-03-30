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
}
