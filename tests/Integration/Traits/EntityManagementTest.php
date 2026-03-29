<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Traits;

use GeneaLabs\LaravelGovernor\Tests\Fixtures\Article;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\ArticlePolicy;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
use GeneaLabs\LaravelGovernor\Traits\EntityManagement;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Collection;

class EntityManagementTest extends UnitTestCase
{
    use EntityManagement;

    public function test_get_policies_returns_manually_registered_policies(): void
    {
        $policies = $this->getPolicies();

        $this->assertInstanceOf(Collection::class, $policies);
        $this->assertTrue(
            $policies->contains('GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author'),
            'Manually registered Author policy should be detected',
        );
    }

    public function test_get_policies_returns_auto_discovered_policies(): void
    {
        $gate = app(Gate::class);

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

    public function test_manually_and_auto_discovered_policies_coexist(): void
    {
        config()->set('genealabs-laravel-governor.policy_paths', [
            __DIR__ . '/../../Fixtures/Policies',
        ]);

        $policies = $this->getPolicies();

        // Manually registered
        $this->assertTrue(
            $policies->contains('GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author'),
            'Manually registered Author policy should still be present',
        );

        // Auto-discovered
        $this->assertTrue(
            $policies->contains(ArticlePolicy::class),
            'Auto-discovered ArticlePolicy should also be present',
        );
    }

    public function test_parse_policies_creates_entities_for_auto_discovered_policies(): void
    {
        config()->set('genealabs-laravel-governor.policy_paths', [
            __DIR__ . '/../../Fixtures/Policies',
        ]);

        $policies = $this->getPolicies();

        // Check auto-discovered ArticlePolicy is in the collection
        $this->assertTrue(
            $policies->contains(ArticlePolicy::class),
            'ArticlePolicy should be in discovered policies',
        );

        // Manually call getEntity to verify it creates the database record
        $entityName = $this->getEntity(ArticlePolicy::class);
        $this->assertStringContainsString(
            'Article',
            $entityName,
            'Entity name should contain Article',
        );

        // Verify entity was created in database
        $entityClass = config('genealabs-laravel-governor.models.entity');
        $entity = (new $entityClass())
            ->where('name', 'like', '%Article%')
            ->first();

        $this->assertNotNull(
            $entity,
            'Entity should be created when getEntity() is called with ArticlePolicy',
        );
    }

    public function test_get_entity_from_model_works_for_auto_discovered_policy(): void
    {
        $entityName = $this->getEntityFromModel(Article::class);

        $this->assertNotEmpty(
            $entityName,
            'Auto-discovered policy should resolve an entity name for the Article model',
        );
    }
}
