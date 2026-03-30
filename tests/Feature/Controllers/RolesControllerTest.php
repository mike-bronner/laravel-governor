<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Feature\Controllers;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\IntegrationTestCase;

class RolesControllerTest extends IntegrationTestCase
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
        $response = $this->get(route('genealabs.laravel-governor.roles.index'));

        $response->assertOk();
    }

    public function testIndexPageRequiresAuth()
    {
        auth()->logout();

        $response = $this->get(route('genealabs.laravel-governor.roles.index'));

        $response->assertRedirect();
    }

    public function testCreatePageIsAccessible()
    {
        $response = $this->get(route('genealabs.laravel-governor.roles.create'));

        $response->assertOk();
    }

    public function testStoreCreatesRole()
    {
        $roleName = 'StoreRole' . uniqid();

        $response = $this->post(route('genealabs.laravel-governor.roles.store'), [
            'name' => $roleName,
            'description' => 'A test role',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('governor_roles', ['name' => $roleName]);
    }

    public function testEditPageIsAccessible()
    {
        $role = Role::create(['name' => 'EditRole' . uniqid(), 'description' => 'desc']);

        $response = $this->get(route('genealabs.laravel-governor.roles.edit', $role));

        $response->assertOk();
    }

    public function testUpdateUpdatesRole()
    {
        $role = Role::create(['name' => 'UpdRole' . uniqid(), 'description' => 'desc']);

        $response = $this->put(route('genealabs.laravel-governor.roles.update', $role), [
            'name' => $role->name,
            'description' => 'updated description',
            'permissions' => [],
        ]);

        $response->assertRedirect();
    }

    public function testDestroyDeletesRole()
    {
        $roleName = 'DelRole' . uniqid();
        $role = Role::create(['name' => $roleName, 'description' => 'desc']);

        $response = $this->delete(route('genealabs.laravel-governor.roles.destroy', $role));

        $response->assertRedirect();
        $this->assertDatabaseMissing('governor_roles', ['name' => $roleName]);
    }
}
