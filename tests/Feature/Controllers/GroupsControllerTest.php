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

    public function testIndexPageIsAccessible()
    {
        $response = $this->get(route('genealabs.laravel-governor.groups.index'));

        $response->assertOk();
    }

    public function testCreatePageIsAccessible()
    {
        $response = $this->get(route('genealabs.laravel-governor.groups.create'));

        $response->assertOk();
    }

    public function testStoreCreatesGroup()
    {
        $groupName = 'StoreGroup' . uniqid();
        $entity = Entity::first();

        $response = $this->post(route('genealabs.laravel-governor.groups.store'), [
            'name' => $groupName,
            'entity_names' => [$entity->name],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('governor_groups', ['name' => $groupName]);
    }

    public function testEditPageIsAccessible()
    {
        $group = Group::create(['name' => 'EditGroup' . uniqid()]);

        $response = $this->get(route('genealabs.laravel-governor.groups.edit', $group));

        $response->assertOk();
    }

    public function testDestroyDeletesGroup()
    {
        $groupName = 'DelGroup' . uniqid();
        $group = Group::create(['name' => $groupName]);

        $response = $this->delete(route('genealabs.laravel-governor.groups.destroy', $group));

        $response->assertRedirect();
        $this->assertDatabaseMissing('governor_groups', ['name' => $groupName]);
    }
}
