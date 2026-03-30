<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class EntityRenameTest extends UnitTestCase
{
    public function testOldInternalEntityNamesNoLongerExistInControllers(): void
    {
        // Verify the migration renames the old entity names to new ones
        // Check that old names don't appear in controller filters
        $rolesControllerFile = file_get_contents(__DIR__ . '/../../src/Http/Controllers/RolesController.php');
        
        // Old names should not appear as literal strings (they were replaced)
        $this->assertStringNotContainsString("\"Action (Laravel Governor)\"", $rolesControllerFile);
        $this->assertStringNotContainsString("\"Ownership (Laravel Governor)\"", $rolesControllerFile);
    }

    public function testRolesControllerFiltersRenamedInternalEntities(): void
    {
        // This test verifies that the controller filters out internal entities
        // by checking that the filter list includes the new names
        $controllerFile = file_get_contents(__DIR__ . '/../../src/Http/Controllers/RolesController.php');
        
        $this->assertStringContainsString('Ability (Laravel Governor)', $controllerFile);
        $this->assertStringContainsString('Owned Resource (Laravel Governor)', $controllerFile);
        $this->assertStringNotContainsString("\"Action (Laravel Governor)\"", $controllerFile);
        $this->assertStringNotContainsString("\"Ownership (Laravel Governor)\"", $controllerFile);
    }

    public function testTeamsControllerFiltersRenamedInternalEntities(): void
    {
        // This test verifies that the controller filters out internal entities
        // by checking that the filter list includes the new names
        $controllerFile = file_get_contents(__DIR__ . '/../../src/Http/Controllers/TeamsController.php');
        
        $this->assertStringContainsString('Ability (Laravel Governor)', $controllerFile);
        $this->assertStringContainsString('Owned Resource (Laravel Governor)', $controllerFile);
        $this->assertStringNotContainsString("\"Action (Laravel Governor)\"", $controllerFile);
        $this->assertStringNotContainsString("\"Ownership (Laravel Governor)\"", $controllerFile);
    }

    public function testGroupsControllerFiltersInternalEntities(): void
    {
        // Verify that GroupsController now filters internal entities
        $controllerFile = file_get_contents(__DIR__ . '/../../src/Http/Controllers/GroupsController.php');
        
        $this->assertStringContainsString('Ability (Laravel Governor)', $controllerFile);
        $this->assertStringContainsString('Owned Resource (Laravel Governor)', $controllerFile);
        $this->assertStringContainsString("whereNotIn", $controllerFile);
    }
}
