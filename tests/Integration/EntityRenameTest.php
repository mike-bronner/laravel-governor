<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\Group;
use GeneaLabs\LaravelGovernor\Http\Controllers\GroupsController;
use GeneaLabs\LaravelGovernor\Http\Controllers\RolesController;
use GeneaLabs\LaravelGovernor\Http\Controllers\TeamsController;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EntityRenameTest extends IntegrationTestCase
{
    protected User $user;

    /** @var list<string> */
    private array $internalEntities = [
        'Ability (Laravel Governor)',
        'Owned Resource (Laravel Governor)',
        'Permission (Laravel Governor)',
        'Entity (Laravel Governor)',
        'Team Invitation (Laravel Governor)',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->roles()->attach('SuperAdmin');
        $this->actingAs($this->user);
    }

    // ──────────────────────────────────────────────
    // Migration tests
    // ──────────────────────────────────────────────

    public function testMigrationRenamesActionEntityToAbility(): void
    {
        DB::table('governor_entities')
            ->where('name', 'Ability (Laravel Governor)')
            ->update(['name' => 'Action (Laravel Governor)']);

        $migration = require __DIR__ . '/../../database/migrations/0001_01_02_000013_rename_action_ownership_entities.php';
        $migration->up();

        $this->assertDatabaseMissing('governor_entities', [
            'name' => 'Action (Laravel Governor)',
        ]);
        $this->assertDatabaseHas('governor_entities', [
            'name' => 'Ability (Laravel Governor)',
        ]);
    }

    public function testMigrationRenamesOwnershipEntityToOwnedResource(): void
    {
        DB::table('governor_entities')
            ->where('name', 'Owned Resource (Laravel Governor)')
            ->update(['name' => 'Ownership (Laravel Governor)']);

        $migration = require __DIR__ . '/../../database/migrations/0001_01_02_000013_rename_action_ownership_entities.php';
        $migration->up();

        $this->assertDatabaseMissing('governor_entities', [
            'name' => 'Ownership (Laravel Governor)',
        ]);
        $this->assertDatabaseHas('governor_entities', [
            'name' => 'Owned Resource (Laravel Governor)',
        ]);
    }

    public function testMigrationRollbackRestoresOriginalNames(): void
    {
        $migration = require __DIR__ . '/../../database/migrations/0001_01_02_000013_rename_action_ownership_entities.php';
        $migration->down();

        $this->assertDatabaseHas('governor_entities', [
            'name' => 'Action (Laravel Governor)',
        ]);
        $this->assertDatabaseHas('governor_entities', [
            'name' => 'Ownership (Laravel Governor)',
        ]);
        $this->assertDatabaseMissing('governor_entities', [
            'name' => 'Ability (Laravel Governor)',
        ]);
        $this->assertDatabaseMissing('governor_entities', [
            'name' => 'Owned Resource (Laravel Governor)',
        ]);
    }

    public function testMigrationIsIdempotentWhenAlreadyRenamed(): void
    {
        $migration = require __DIR__ . '/../../database/migrations/0001_01_02_000013_rename_action_ownership_entities.php';
        $migration->up();

        $this->assertDatabaseHas('governor_entities', [
            'name' => 'Ability (Laravel Governor)',
        ]);
        $this->assertDatabaseHas('governor_entities', [
            'name' => 'Owned Resource (Laravel Governor)',
        ]);
    }

    public function testMigrationRenamesBothEntitiesInSingleRun(): void
    {
        DB::table('governor_entities')
            ->where('name', 'Ability (Laravel Governor)')
            ->update(['name' => 'Action (Laravel Governor)']);
        DB::table('governor_entities')
            ->where('name', 'Owned Resource (Laravel Governor)')
            ->update(['name' => 'Ownership (Laravel Governor)']);

        $migration = require __DIR__ . '/../../database/migrations/0001_01_02_000013_rename_action_ownership_entities.php';
        $migration->up();

        $this->assertDatabaseMissing('governor_entities', ['name' => 'Action (Laravel Governor)']);
        $this->assertDatabaseMissing('governor_entities', ['name' => 'Ownership (Laravel Governor)']);
        $this->assertDatabaseHas('governor_entities', ['name' => 'Ability (Laravel Governor)']);
        $this->assertDatabaseHas('governor_entities', ['name' => 'Owned Resource (Laravel Governor)']);
    }

    // ──────────────────────────────────────────────
    // Controller integration tests — verify renamed
    // internal entities are filtered from view data
    // ──────────────────────────────────────────────

    public function testRolesCreateFiltersRenamedInternalEntities(): void
    {
        Gate::before(fn () => true);

        $controller = app(RolesController::class);
        $view = $controller->create();
        $entities = $view->getData()['entities'];
        $entityNames = $entities->pluck('name')->toArray();

        foreach ($this->internalEntities as $name) {
            $this->assertNotContains($name, $entityNames, "'{$name}' should be filtered from roles create view");
        }
    }

    public function testRolesEditFiltersRenamedInternalEntities(): void
    {
        Gate::before(fn () => true);

        $role = Role::firstOrCreate(
            ['name' => 'TestEditRole'],
            ['description' => 'test']
        );

        $controller = app(RolesController::class);
        $view = $controller->edit($role);
        $entities = $view->getData()['entities'];
        $entityNames = $entities->pluck('name')->toArray();

        foreach ($this->internalEntities as $name) {
            $this->assertNotContains($name, $entityNames, "'{$name}' should be filtered from roles edit view");
        }
    }

    public function testGroupsCreateFiltersRenamedInternalEntities(): void
    {
        $controller = app(GroupsController::class);
        $view = $controller->create();
        $entities = $view->getData()['entities'];
        $entityNames = $entities->pluck('name')->toArray();

        foreach ($this->internalEntities as $name) {
            $this->assertNotContains($name, $entityNames, "'{$name}' should be filtered from groups create view");
        }
    }

    public function testGroupsEditFiltersRenamedInternalEntities(): void
    {
        $group = Group::firstOrCreate(
            ['name' => 'TestEditGroup'],
            ['description' => 'test']
        );

        $controller = app(GroupsController::class);
        $view = $controller->edit($group);
        $entities = $view->getData()['entities'];
        $entityNames = $entities->pluck('name')->toArray();

        foreach ($this->internalEntities as $name) {
            $this->assertNotContains($name, $entityNames, "'{$name}' should be filtered from groups edit view");
        }
    }

    public function testTeamsCreateFiltersRenamedInternalEntities(): void
    {
        $controller = app(TeamsController::class);
        $view = $controller->create();
        $entities = $view->getData()['entities'];
        $entityNames = $entities->pluck('name')->toArray();

        foreach ($this->internalEntities as $name) {
            $this->assertNotContains($name, $entityNames, "'{$name}' should be filtered from teams create view");
        }
    }

    public function testTeamsEditFiltersRenamedInternalEntities(): void
    {
        $team = Team::firstOrCreate(
            ['name' => 'TestEditTeam'],
            ['description' => 'test']
        );

        $controller = app(TeamsController::class);
        $view = $controller->edit($team);
        $entities = $view->getData()['entities'];
        $entityNames = $entities->pluck('name')->toArray();

        foreach ($this->internalEntities as $name) {
            $this->assertNotContains($name, $entityNames, "'{$name}' should be filtered from teams edit view");
        }
    }
}
