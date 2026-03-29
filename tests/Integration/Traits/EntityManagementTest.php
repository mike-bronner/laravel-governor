<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Traits;

use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\Exceptions\EntityNameCollisionException;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Alternate\Author as AlternateAuthorPolicy;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;

class EntityManagementTest extends UnitTestCase
{
    #[Test]
    public function it_detects_entity_name_collision_for_same_named_policies(): void
    {
        $this->expectException(EntityNameCollisionException::class);
        $this->expectExceptionMessage('Entity name collision detected');
        $this->expectExceptionMessage(
            'GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author'
        );
        $this->expectExceptionMessage(
            'GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Alternate\Author'
        );

        // The setup already registered the Author policy and created
        // its entity via parsePolicies(). Now register a second Author
        // policy with a different FQCN that resolves to the same name.

        // First ensure the existing entity has its policy_class set
        $existingEntity = Entity::where('name', 'like', '%Author%')
            ->whereNotNull('policy_class')
            ->first();

        if (! $existingEntity) {
            // Force policy_class on the existing entity
            Entity::where('name', 'like', '%Author%')->update([
                'policy_class' => 'GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author',
            ]);

            // Refresh the cached entities
            $this->refreshGovernorEntities();
        }

        // Register the alternate policy and trigger entity resolution
        Gate::policy(Author::class, AlternateAuthorPolicy::class);
        $this->getEntity(AlternateAuthorPolicy::class);
    }

    #[Test]
    public function it_stores_policy_class_on_new_entities(): void
    {
        // Clear all entities to start fresh
        Entity::query()->delete();
        $this->refreshGovernorEntities();

        $policyClass = 'GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\User';
        $this->getEntity($policyClass);

        $entity = Entity::where('policy_class', $policyClass)->first();

        $this->assertNotNull($entity);
        $this->assertEquals($policyClass, $entity->policy_class);
    }

    #[Test]
    public function it_backfills_policy_class_on_existing_entities_without_one(): void
    {
        // Find an entity and clear its policy_class
        $entity = Entity::first();
        $originalName = $entity->name;

        Entity::where('name', $originalName)->update(['policy_class' => null]);
        $this->refreshGovernorEntities();

        // Re-resolve via a known policy
        $policyClass = 'GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author';
        $entity = Entity::where('name', 'like', '%Author%')->first();

        if ($entity) {
            Entity::where('name', $entity->name)->update(['policy_class' => null]);
            $this->refreshGovernorEntities();

            $this->getEntity($policyClass);

            $updated = Entity::where('name', $entity->name)->first();
            $this->assertEquals($policyClass, $updated->policy_class);
        } else {
            $this->markTestSkipped('No Author entity found to test backfill.');
        }
    }

    #[Test]
    public function it_allows_same_policy_to_resolve_without_collision(): void
    {
        $policyClass = 'GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author';

        // Ensure entity exists with this policy_class
        $entity = Entity::where('name', 'like', '%Author%')->first();
        if ($entity && ! $entity->policy_class) {
            Entity::where('name', $entity->name)->update([
                'policy_class' => $policyClass,
            ]);
            $this->refreshGovernorEntities();
        }

        // Should not throw — same policy resolving again
        $name = $this->getEntity($policyClass);

        $this->assertNotEmpty($name);
    }

    #[Test]
    public function collision_message_identifies_both_policy_classes(): void
    {
        $existingClass = 'GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author';
        $alternateClass = AlternateAuthorPolicy::class;

        // Ensure entity has the existing policy class
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

    private function refreshGovernorEntities(): void
    {
        $entityClass = config('genealabs-laravel-governor.models.entity');
        $entities = (new $entityClass)
            ->select('name', 'policy_class')
            ->with('group:name')
            ->orderBy('name')
            ->toBase()
            ->get();

        app()->instance('governor-entities', $entities);
    }

    /**
     * Use the EntityManagement trait's getEntity method via a
     * test-specific helper that exposes the protected method.
     */
    private function getEntity(string $policyClassName): string
    {
        $helper = new class {
            use \GeneaLabs\LaravelGovernor\Traits\EntityManagement;

            public function resolveEntity(string $policyClassName): string
            {
                return $this->getEntity($policyClassName);
            }
        };

        return $helper->resolveEntity($policyClassName);
    }
}
