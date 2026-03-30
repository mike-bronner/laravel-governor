<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Traits;

use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\Exceptions\EntityNameCollisionException;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Article;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Alternate\Author as AlternateAuthorPolicy;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\ArticlePolicy;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
use GeneaLabs\LaravelGovernor\Traits\EntityManagement;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class EntityManagementTest extends UnitTestCase
{
    use EntityManagement;

    public function setUp(): void
    {
        parent::setUp();

        cache()->forget("genealabs:laravel-governor:policies");
    }

    // ── Collision detection ──────────────────────────────────────────

    public function testDetectsEntityNameCollisionForSameNamedPolicies(): void
    {
        $this->expectException(EntityNameCollisionException::class);
        $this->expectExceptionMessage('Entity name collision detected');
        $this->expectExceptionMessage(
            'GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author'
        );
        $this->expectExceptionMessage(
            'GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Alternate\Author'
        );

        $existingEntity = Entity::where('name', 'like', '%Author%')
            ->whereNotNull('policy_class')
            ->first();

        if (! $existingEntity) {
            Entity::where('name', 'like', '%Author%')->update([
                'policy_class' => 'GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author',
            ]);

            $this->refreshGovernorEntities();
        }

        Gate::policy(Author::class, AlternateAuthorPolicy::class);
        $this->getEntity(AlternateAuthorPolicy::class);
    }

    public function testStoresPolicyClassOnNewEntities(): void
    {
        Entity::query()->delete();
        $this->refreshGovernorEntities();

        $policyClass = 'GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\User';
        $this->getEntity($policyClass);

        $entity = Entity::where('policy_class', $policyClass)->first();

        $this->assertNotNull($entity);
        $this->assertEquals($policyClass, $entity->policy_class);
    }

    public function testBackfillsPolicyClassOnExistingEntitiesWithoutOne(): void
    {
        $policyClass = 'GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author';
        $entity = Entity::where('name', 'like', '%Author%')->first();

        if (! $entity) {
            $this->markTestSkipped('No Author entity found to test backfill.');
        }

        Entity::where('name', $entity->name)->update(['policy_class' => null]);
        $this->refreshGovernorEntities();

        $this->getEntity($policyClass);

        $updated = Entity::where('name', $entity->name)->first();
        $this->assertEquals($policyClass, $updated->policy_class);
    }

    public function testAllowsSamePolicyToResolveWithoutCollision(): void
    {
        $policyClass = 'GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author';

        $entity = Entity::where('name', 'like', '%Author%')->first();
        if ($entity && ! $entity->policy_class) {
            Entity::where('name', $entity->name)->update([
                'policy_class' => $policyClass,
            ]);
            $this->refreshGovernorEntities();
        }

        $name = $this->getEntity($policyClass);

        $this->assertNotEmpty($name);
    }

    public function testCollisionMessageIdentifiesBothPolicyClasses(): void
    {
        $existingClass = 'GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author';
        $alternateClass = AlternateAuthorPolicy::class;

        $entity = Entity::where('name', 'like', '%Author%')->first();
        if ($entity) {
            Entity::where('name', $entity->name)->update([
                'policy_class' => $existingClass,
            ]);
            $this->refreshGovernorEntities();
        }

        try {
            $this->getEntity($alternateClass);
            $this->fail('Expected EntityNameCollisionException was not thrown');
        } catch (EntityNameCollisionException $e) {
            $this->assertStringContainsString($existingClass, $e->getMessage());
            $this->assertStringContainsString($alternateClass, $e->getMessage());
            $this->assertStringContainsString('collision', strtolower($e->getMessage()));
        }
    }

    // ── Policy discovery ─────────────────────────────────────────────

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

        $registeredPolicies = $gate->policies();
        $this->assertNotContains(
            ArticlePolicy::class,
            $registeredPolicies,
            'ArticlePolicy should not be manually registered for this test',
        );

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
        $this->assertStringContainsString('Article', $entityName);

        $entityClass = config('genealabs-laravel-governor.models.entity');
        $entity = (new $entityClass())
            ->where('name', 'like', '%Article%')
            ->first();

        $this->assertNotNull($entity);
    }

    public function testGetEntityFromModelWorksForAutoDiscoveredPolicy(): void
    {
        $entityName = $this->getEntityFromModel(Article::class);

        $this->assertNotEmpty($entityName);
    }

    public function testGetPoliciesResultIsCached(): void
    {
        $first = $this->getPolicies();
        $second = $this->getPolicies();

        $this->assertEquals($first->toArray(), $second->toArray());
        $this->assertNotNull(cache()->get("genealabs:laravel-governor:policies"));
    }

    // ── Tokenizer ────────────────────────────────────────────────────

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

    // ── Entity resolution ────────────────────────────────────────────

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

    // ── Helpers ───────────────────────────────────────────────────────

    private function refreshGovernorEntities(): void
    {
        $entityClass = config('genealabs-laravel-governor.models.entity');
        $entities = (new $entityClass)
            ->select("name", "policy_class")
            ->with("group:name")
            ->orderBy("name")
            ->toBase()
            ->get();

        app()->instance('governor-entities', $entities);
    }
}
