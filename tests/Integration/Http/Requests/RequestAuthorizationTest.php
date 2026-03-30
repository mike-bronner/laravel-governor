<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Http\Requests;

use GeneaLabs\LaravelGovernor\Http\Requests\CreateAssignmentRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\CreateRoleRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\GroupDeleteRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\RoleUpdateRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\StoreGroupRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\StoreRoleRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\TeamStoreRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\UpdateGroupRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\UpdateRoleRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\UpdateTeamRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\UserCan;
use GeneaLabs\LaravelGovernor\Http\Requests\UserIs;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class RequestAuthorizationTest extends UnitTestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function testCreateAssignmentRequestAuthorization()
    {
        $request = new CreateAssignmentRequest();
        $request->setUserResolver(fn () => $this->user);

        // SuperAdmin should be authorized
        $this->user->roles()->attach('SuperAdmin');
        $this->assertTrue($request->authorize());
    }

    public function testCreateRoleRequestAuthorization()
    {
        $request = new CreateRoleRequest();
        $request->setUserResolver(fn () => $this->user);

        // SuperAdmin should be authorized
        $this->user->roles()->attach('SuperAdmin');
        $this->assertTrue($request->authorize());
    }

    public function testStoreRoleRequestAuthorization()
    {
        $request = new StoreRoleRequest();
        $request->setUserResolver(fn () => $this->user);

        // SuperAdmin should be authorized
        $this->user->roles()->attach('SuperAdmin');
        $this->assertTrue($request->authorize());
    }

    public function testStoreGroupRequestAuthorization()
    {
        $request = new StoreGroupRequest();
        $request->setUserResolver(fn () => $this->user);

        // SuperAdmin should be authorized
        $this->user->roles()->attach('SuperAdmin');
        $this->assertTrue($request->authorize());
    }

    public function testUserCanRequestRules()
    {
        $request = new UserCan();

        $rules = $request->rules();

        $this->assertArrayHasKey('model', $rules);
        $this->assertArrayHasKey('primary-key', $rules);
    }

    public function testUserIsRequestRules()
    {
        $request = new UserIs();

        $rules = $request->rules();

        // UserIs rules are empty
        $this->assertIsArray($rules);
    }
}
