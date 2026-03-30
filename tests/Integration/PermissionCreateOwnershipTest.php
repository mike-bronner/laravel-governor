<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
use InvalidArgumentException;

class PermissionCreateOwnershipTest extends UnitTestCase
{
    public function testCreateActionAllowsNoOwnership(): void
    {
        $permission = new Permission([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'no',
        ]);

        $permission->save();

        $this->assertDatabaseHas('governor_permissions', [
            'action_name' => 'create',
            'entity_name' => 'author',
            'role_name' => 'Member',
            'ownership_name' => 'no',
        ]);
    }

    public function testCreateActionAllowsAnyOwnership(): void
    {
        $permission = new Permission([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'any',
        ]);

        $permission->save();

        $this->assertDatabaseHas('governor_permissions', [
            'action_name' => 'create',
            'entity_name' => 'author',
            'role_name' => 'Member',
            'ownership_name' => 'any',
        ]);
    }

    public function testCreateActionRejectsOwnOwnership(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The 'create' action only allows 'no' or 'any' ownership.");

        $permission = new Permission([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'own',
        ]);

        $permission->save();
    }

    public function testCreateActionRejectsOtherOwnership(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The 'create' action only allows 'no' or 'any' ownership.");

        $permission = new Permission([
            'role_name' => 'Member',
            'entity_name' => 'author',
            'action_name' => 'create',
            'ownership_name' => 'other',
        ]);

        $permission->save();
    }

    public function testNonCreateActionsAllowAllOwnerships(): void
    {
        $actions = ['view', 'update', 'delete', 'restore', 'forceDelete', 'viewAny'];

        foreach ($actions as $action) {
            foreach (['any', 'own', 'other'] as $ownership) {
                $permission = new Permission([
                    'role_name' => 'Member',
                    'entity_name' => 'author',
                    'action_name' => $action,
                    'ownership_name' => $ownership,
                ]);

                $permission->save();

                $this->assertDatabaseHas('governor_permissions', [
                    'action_name' => $action,
                    'entity_name' => 'author',
                    'role_name' => 'Member',
                    'ownership_name' => $ownership,
                ]);

                // Clean up for next iteration
                $permission->delete();
            }
        }
    }
}
