<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Traits;

use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
use GeneaLabs\LaravelGovernor\Traits\EntityManagement;
use Illuminate\Support\Facades\Gate;

class EntityManagementTest extends UnitTestCase
{
    use EntityManagement;

    public function testGetEntityReturnsEmptyStringForPolicyWithoutPackageName()
    {
        $result = $this->getEntity('SomePolicy');

        $this->assertSame('', $result);
    }

    public function testGetEntityReturnsEmptyStringForSingleSegmentNamespace()
    {
        $result = $this->getEntity('OrphanPolicy');

        $this->assertSame('', $result);
    }

    public function testGetEntityReturnsValidEntityForAppNamespace()
    {
        $result = $this->getEntity('App\\Policies\\PostPolicy');

        $this->assertNotEmpty($result);
        $this->assertSame('Post', $result);
    }

    public function testGetEntityReturnsValidEntityForPackageNamespace()
    {
        $result = $this->getEntity('Vendor\\PackageName\\Policies\\WidgetPolicy');

        $this->assertNotEmpty($result);
        $this->assertSame('Widget (Package Name)', $result);
    }

    public function testGetEntityDoesNotCreateEntityWithEmptyPackageName()
    {
        $entityClass = config('genealabs-laravel-governor.models.entity');

        $countBefore = (new $entityClass)->count();

        $this->getEntity('StandalonePolicy');

        $countAfter = (new $entityClass)->count();

        $this->assertSame($countBefore, $countAfter);
    }

    public function testParsePoliciesSkipsPolicesWithoutValidPackageName()
    {
        Gate::policy('SomeModel', 'BadPolicy');

        $entityClass = config('genealabs-laravel-governor.models.entity');
        $countBefore = (new $entityClass)->count();

        $this->parsePolicies();

        $countAfter = (new $entityClass)->count();

        // Should not have added an entity with empty package name
        $entities = (new $entityClass)->pluck('name')->toArray();
        foreach ($entities as $name) {
            $this->assertStringNotContainsString('()', $name, 'Entity should not have empty package name parentheses');
        }
    }

    public function testGetEntityFromModelReturnsEmptyStringWhenNoPolicyExists()
    {
        $result = $this->getEntityFromModel('NonExistent\\Model');

        $this->assertSame('', $result);
    }

    public function testValidAutoDiscoveredEntitiesStillAppear()
    {
        // Governor's own policies have proper namespaces
        $result = $this->getEntity('GeneaLabs\\LaravelGovernor\\Policies\\Entity');

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Laravel Governor', $result);
    }
}
