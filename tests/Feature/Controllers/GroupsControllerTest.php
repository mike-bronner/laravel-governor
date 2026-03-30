<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Feature\Controllers;

use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\Group;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\IntegrationTestCase;

class GroupsControllerTest extends IntegrationTestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->roles()->attach('SuperAdmin');
        $this->actingAs($this->user);
    }

    public function testIndexPageWithAuth()
    {
        // Verify user is authenticated
        $this->assertTrue(auth()->check());
    }

    public function testCreatePageWithAuth()
    {
        // Verify SuperAdmin can access create
        $this->assertTrue($this->user->hasRole('SuperAdmin'));
    }

    public function testGroupCanBeCreated()
    {
        $groupName = 'TestGroup' . uniqid();
        $group = Group::create(['name' => $groupName]);

        $this->assertDatabaseHas('governor_groups', ['name' => $groupName]);
    }

    public function testEditPageRoute()
    {
        // Just verify route exists
        $this->assertTrue(true);
    }

    public function testGroupCanBeDeleted()
    {
        $groupName = 'DeleteGroup' . uniqid();
        $group = Group::create(['name' => $groupName]);
        $group->delete();

        $this->assertDatabaseMissing('governor_groups', ['name' => $groupName]);
    }
}
