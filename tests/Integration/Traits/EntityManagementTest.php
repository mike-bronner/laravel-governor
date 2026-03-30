<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Traits;

use GeneaLabs\LaravelGovernor\Tests\Fixtures\Article;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\ArticlePolicy;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
use GeneaLabs\LaravelGovernor\Traits\EntityManagement;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Gate;

class EntityManagementTest extends UnitTestCase
{
    use EntityManagement;

    public function setUp(): void
    {
        parent::setUp();

        cache()->forget("genealabs:laravel-governor:policies");
    }

    public function testGetPoliciesReturnsManuallyRegisteredPolicies(): void
    {
        $policies = $this->getPolicies();

        $this->assertInstanceOf(Collection::class, $policies);
        $this->assertTrue(
            $policies->contains('GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author'),
            'Manually registered Author policy should be detected',
        );
    }

    public function testGetPoliciesReturnsAutoDiscoveredPolicies(): void
    {
        $gate = app(GateContract::class);

        // Verify ArticlePolicy is NOT manually registered
        $registeredPolicies = $gate->policies();
        $this->assertNotContains(
            ArticlePolicy::class,
            $registeredPolicies,
            'ArticlePolicy should not be manually registered for this test',
        );

        // Configure policy_paths to point at our test fixtures
        config()->set('genealabs-laravel-governor.policy_paths', [
            __DIR__ . '/../../Fixtures/Policies',
        ]);

        $policies = $this->getPolicies();

        $this->assertTrue(
            $policies->contains(ArticlePolicy::class),
            'Auto-discovered ArticlePolicy should be detected',
        );
    }

    public function testManuallyAndAutoDiscoveredPoliciesCoexist(): void
    {
        config()->set('genealabs-laravel-governor.policy_paths', [
            __DIR__ . '/../../Fixtures/Policies',
        ]);

        $policies = $this->getPolicies();

        $this->assertTrue(
            $policies->contains('GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author'),
            'Manually registered Author policy should still be present',
        );

        $this->assertTrue(
            $policies->contains(ArticlePolicy::class),
            'Auto-discovered ArticlePolicy should also be present',
        );
    }

    public function testParsePoliciesCreatesEntitiesForAutoDiscoveredPolicies(): void
    {
        config()->set('genealabs-laravel-governor.policy_paths', [
            __DIR__ . '/../../Fixtures/Policies',
        ]);

        $policies = $this->getPolicies();

        $this->assertTrue(
            $policies->contains(ArticlePolicy::class),
            'ArticlePolicy should be in discovered policies',
        );

        $entityName = $this->getEntity(ArticlePolicy::class);
        $this->assertStringContainsString(
            'Article',
            $entityName,
            'Entity name should contain Article',
        );

        $entityClass = config('genealabs-laravel-governor.models.entity');
        $entity = (new $entityClass())
            ->where('name', 'like', '%Article%')
            ->first();

        $this->assertNotNull(
            $entity,
            'Entity should be created when getEntity() is called with ArticlePolicy',
        );
    }

    public function testGetEntityFromModelWorksForAutoDiscoveredPolicy(): void
    {
        $entityName = $this->getEntityFromModel(Article::class);

        $this->assertNotEmpty(
            $entityName,
            'Auto-discovered policy should resolve an entity name for the Article model',
        );
    }

    public function testGetEntityReturnsEmptyStringForPolicyWithoutPackageName(): void
    {
        $result = $this->getEntity('SomePolicy');

        $this->assertSame('', $result);
    }

    public function testGetEntityReturnsEmptyStringForSingleSegmentNamespace(): void
    {
        $result = $this->getEntity('OrphanPolicy');

        $this->assertSame('', $result);
    }

    public function testGetEntityReturnsValidEntityForAppNamespace(): void
    {
        $result = $this->getEntity('App\\Policies\\PostPolicy');

        $this->assertNotEmpty($result);
        $this->assertSame('Post', $result);
    }

    public function testGetEntityReturnsValidEntityForPackageNamespace(): void
    {
        $result = $this->getEntity('Vendor\\PackageName\\Policies\\WidgetPolicy');

        $this->assertNotEmpty($result);
        $this->assertSame('Widget (Package Name)', $result);
    }

    public function testGetEntityDoesNotCreateEntityWithEmptyPackageName(): void
    {
        $entityClass = config('genealabs-laravel-governor.models.entity');

        $countBefore = (new $entityClass)->count();

        $this->getEntity('StandalonePolicy');

        $countAfter = (new $entityClass)->count();

        $this->assertSame($countBefore, $countAfter);
    }

    public function testParsePoliciesSkipsPolicesWithoutValidPackageName(): void
    {
        Gate::policy('SomeModel', 'BadPolicy');

        $entityClass = config('genealabs-laravel-governor.models.entity');
        $countBefore = (new $entityClass)->count();

        $this->parsePolicies();

        $countAfter = (new $entityClass)->count();

        $entities = (new $entityClass)->pluck('name')->toArray();
        foreach ($entities as $name) {
            $this->assertStringNotContainsString('()', $name, 'Entity should not have empty package name parentheses');
        }
    }

    public function testGetEntityFromModelReturnsEmptyStringWhenNoPolicyExists(): void
    {
        $result = $this->getEntityFromModel('NonExistent\\Model');

        $this->assertSame('', $result);
    }

    public function testValidAutoDiscoveredEntitiesStillAppear(): void
    {
        $result = $this->getEntity('GeneaLabs\\LaravelGovernor\\Policies\\Entity');

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Laravel Governor', $result);
    }

    public function testResolveClassNameFromFileUsesTokenizer(): void
    {
        $filePath = __DIR__ . '/../../Fixtures/Policies/ArticlePolicy.php';

        $result = $this->resolveClassNameFromFile($filePath);

        $this->assertSame(ArticlePolicy::class, $result);
    }

    public function testResolveClassNameFromFileReturnsNullForInvalidFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile, '<?php echo "no class here";');

        $result = $this->resolveClassNameFromFile($tmpFile);

        $this->assertNull($result);
        unlink($tmpFile);
    }

    public function testGetPoliciesResultIsCached(): void
    {
        $first = $this->getPolicies();
        $second = $this->getPolicies();

        $this->assertEquals($first->toArray(), $second->toArray());
        $this->assertNotNull(cache()->get("genealabs:laravel-governor:policies"));
    }
}
