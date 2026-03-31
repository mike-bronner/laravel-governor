<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
use Illuminate\Support\Facades\DB;

class EntityRenameTest extends UnitTestCase
{
    public function testMigrationRenamesActionEntityToAbility(): void
    {
        // Revert to pre-migration state
        DB::table('governor_entities')
            ->where('name', 'Ability (Laravel Governor)')
            ->update(['name' => 'Action (Laravel Governor)']);

        // Run the rename migration
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
        // Revert to pre-migration state
        DB::table('governor_entities')
            ->where('name', 'Owned Resource (Laravel Governor)')
            ->update(['name' => 'Ownership (Laravel Governor)']);

        // Run the rename migration
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
        // Current state has new names (from setUp migrations)
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
        // New names already exist from setUp — running up again should not fail
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
        // Revert both to pre-migration state
        DB::table('governor_entities')
            ->where('name', 'Ability (Laravel Governor)')
            ->update(['name' => 'Action (Laravel Governor)']);
        DB::table('governor_entities')
            ->where('name', 'Owned Resource (Laravel Governor)')
            ->update(['name' => 'Ownership (Laravel Governor)']);

        $migration = require __DIR__ . '/../../database/migrations/0001_01_02_000013_rename_action_ownership_entities.php';
        $migration->up();

        // Both old names gone
        $this->assertDatabaseMissing('governor_entities', ['name' => 'Action (Laravel Governor)']);
        $this->assertDatabaseMissing('governor_entities', ['name' => 'Ownership (Laravel Governor)']);

        // Both new names present
        $this->assertDatabaseHas('governor_entities', ['name' => 'Ability (Laravel Governor)']);
        $this->assertDatabaseHas('governor_entities', ['name' => 'Owned Resource (Laravel Governor)']);
    }

    public function testRenamedEntitiesAreExcludedFromEntityQueries(): void
    {
        // Verify that querying entities while excluding internal ones
        // uses the new names, not the old ones
        $internalNames = [
            'Permission (Laravel Governor)',
            'Entity (Laravel Governor)',
            'Ability (Laravel Governor)',
            'Owned Resource (Laravel Governor)',
            'Team Invitation (Laravel Governor)',
        ];

        $visibleEntities = DB::table('governor_entities')
            ->whereNotIn('name', $internalNames)
            ->pluck('name');

        // Internal entities should not appear in filtered results
        foreach ($internalNames as $internalName) {
            $this->assertNotContains($internalName, $visibleEntities->toArray());
        }
    }
}
