<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Traits;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class GovernableExtendedTest extends UnitTestCase
{
    protected $user;
    protected $team;
    protected $author;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $teamName = 'Ext Team' . uniqid();
        $this->team = Team::create(['name' => $teamName, 'description' => 'test']);
        $this->author = Author::factory()->create();
        $this->author->teams()->attach($this->team);
        $this->user->teams()->attach($this->team);
    }

    public function testScopeViewableWithOwnPermissionAndTeams()
    {
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'Author (Laravel Governor)',
            'action_name' => 'view',
            'ownership_name' => 'own',
        ]);

        $results = Author::viewable()->get();

        // Should include own and team-shared authors
        $this->assertTrue($results->contains($this->author));
    }

    public function testScopeUpdatableWithOwnPermissionAndTeams()
    {
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'Author (Laravel Governor)',
            'action_name' => 'update',
            'ownership_name' => 'own',
        ]);

        $results = Author::updatable()->get();

        $this->assertTrue($results->contains($this->author));
    }

    public function testScopeViewableAsSuperAdmin()
    {
        $this->user->roles()->attach('SuperAdmin');

        $results = Author::viewable()->get();

        $this->assertTrue($results->isNotEmpty());
    }

    public function testUserScopeViewableWithOwnPermission()
    {
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'User (Laravel Governor)',
            'action_name' => 'view',
            'ownership_name' => 'own',
        ]);

        $results = User::viewable()->get();

        // User scopes filter by teams or own key
        $this->assertTrue($results->contains($this->user));
    }

    public function testUserScopeViewableWithAnyPermission()
    {
        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'User (Laravel Governor)',
            'action_name' => 'view',
            'ownership_name' => 'any',
        ]);

        $results = User::viewable()->get();

        $this->assertTrue($results->isNotEmpty());
    }

    public function testScopeViewableReturnsCollectionWithNoPermissions()
    {
        $results = Author::viewable()->get();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $results);
    }
}
