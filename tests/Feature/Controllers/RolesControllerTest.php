<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Feature\Controllers;

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

    public function testStoreRequiresAuth()
    {
        auth()->logout();

        $response = $this->post(route('genealabs.laravel-governor.roles.store'), [
            'name' => 'NoAuthRole',
            'description' => 'test',
        ]);

        $response->assertRedirect();
    }
}
