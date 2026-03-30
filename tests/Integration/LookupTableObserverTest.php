<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Action;
use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\GovernorCache;
use GeneaLabs\LaravelGovernor\Observers\LookupTableObserver;
use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Cache;

class LookupTableObserverTest extends IntegrationTestCase
{
    protected GovernorCache $governorCache;
    protected LookupTableObserver $observer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->governorCache = app(GovernorCache::class);
        $this->observer = app(LookupTableObserver::class);
    }

    public function test_saved_event_flushes_cache_and_clears_singletons(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => 3600]);

        // Populate cache
        $this->governorCache->remember('actions', fn () => 'cached-actions');
        $this->governorCache->remember('roles', fn () => 'cached-roles');
        $this->assertTrue(Cache::has('governor:actions'));
        $this->assertTrue(Cache::has('governor:roles'));

        // Bind fake singleton instances
        app()->instance('governor-actions', 'stale-actions');
        app()->instance('governor-entities', 'stale-entities');
        app()->instance('governor-permissions', 'stale-permissions');
        app()->instance('governor-roles', 'stale-roles');

        // Trigger saved observer directly
        $action = Action::first();
        $this->observer->saved($action);

        // Cache should be flushed
        $this->assertFalse(Cache::has('governor:actions'));
        $this->assertFalse(Cache::has('governor:roles'));

        // Singletons should be forgotten (resolving again should not return stale string)
        $this->assertNotSame('stale-actions', app('governor-actions'));
    }

    public function test_deleted_event_flushes_cache_and_clears_singletons(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => 3600]);

        // Populate cache
        $this->governorCache->remember('entities', fn () => 'cached-entities');
        $this->assertTrue(Cache::has('governor:entities'));

        // Bind fake singleton
        app()->instance('governor-entities', 'stale-entities');

        // Create and delete a role to trigger deleted observer directly
        $role = new Role();
        $role->name = 'observer-delete-test';
        $role->description = 'Testing observer deleted event';
        $role->save();

        // Re-populate cache after the save flush
        $this->governorCache->remember('entities', fn () => 'cached-entities-again');
        $this->assertTrue(Cache::has('governor:entities'));

        $this->observer->deleted($role);

        $this->assertFalse(Cache::has('governor:entities'));
    }

    public function test_observer_is_injected_with_governor_cache(): void
    {
        $observer = app(LookupTableObserver::class);

        $this->assertInstanceOf(LookupTableObserver::class, $observer);
    }

    public function test_entity_modification_triggers_observer(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => 3600]);

        $this->governorCache->remember('actions', fn () => 'cached');
        $this->assertTrue(Cache::has('governor:actions'));

        // Modify an entity — observer should fire via Eloquent events
        $entity = Entity::first();

        if ($entity) {
            $original = $entity->policy_class;
            $entity->policy_class = 'App\\Policies\\TestPolicy';
            $entity->save();

            $this->assertFalse(Cache::has('governor:actions'));

            // Restore
            $entity->policy_class = $original;
            $entity->save();
        } else {
            $this->markTestSkipped('No entity records to test with');
        }
    }

    public function test_permission_modification_triggers_observer(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => 3600]);

        $this->governorCache->remember('permissions', fn () => 'cached');
        $this->assertTrue(Cache::has('governor:permissions'));

        $permission = Permission::first();

        if ($permission) {
            $permission->touch();
            $this->assertFalse(Cache::has('governor:permissions'));
        } else {
            $this->markTestSkipped('No permission records to test with');
        }
    }
}
