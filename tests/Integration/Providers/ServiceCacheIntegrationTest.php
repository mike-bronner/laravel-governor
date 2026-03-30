<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Providers;

use GeneaLabs\LaravelGovernor\GovernorCache;
use GeneaLabs\LaravelGovernor\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Cache;

class ServiceCacheIntegrationTest extends IntegrationTestCase
{
    public function test_governor_singletons_use_cache_when_enabled(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => 3600]);

        $cache = app(GovernorCache::class);
        $cache->flush();

        // Forget all singleton instances to force re-resolution through cache
        app()->forgetInstance('governor-actions');
        app()->forgetInstance('governor-entities');
        app()->forgetInstance('governor-permissions');
        app()->forgetInstance('governor-roles');

        // Resolve each singleton — this executes the closure bodies in Service::boot()
        $actions = app('governor-actions');
        $entities = app('governor-entities');
        $permissions = app('governor-permissions');
        $roles = app('governor-roles');

        // Values should now be cached
        $this->assertTrue(Cache::has('governor:actions'));
        $this->assertTrue(Cache::has('governor:entities'));
        $this->assertTrue(Cache::has('governor:permissions'));
        $this->assertTrue(Cache::has('governor:roles'));

        // Values should be collections (not null)
        $this->assertNotNull($actions);
        $this->assertNotNull($entities);
        $this->assertNotNull($permissions);
        $this->assertNotNull($roles);
    }

    public function test_governor_singletons_work_without_cache(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => false]);

        app()->forgetInstance('governor-actions');
        app()->forgetInstance('governor-entities');
        app()->forgetInstance('governor-permissions');
        app()->forgetInstance('governor-roles');

        $actions = app('governor-actions');
        $entities = app('governor-entities');
        $permissions = app('governor-permissions');
        $roles = app('governor-roles');

        // Should not be cached
        $this->assertFalse(Cache::has('governor:actions'));

        // But values should still be returned
        $this->assertNotNull($actions);
        $this->assertNotNull($entities);
        $this->assertNotNull($permissions);
        $this->assertNotNull($roles);
    }

    public function test_lookup_table_observers_registered_on_all_models(): void
    {
        // Verify the observer is registered by modifying a model and checking cache invalidation
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => 3600]);

        $cache = app(GovernorCache::class);
        $cache->remember('actions', fn () => 'test-data');
        $this->assertTrue(Cache::has('governor:actions'));

        // Modifying any observed model should flush the cache
        $actionClass = config('genealabs-laravel-governor.models.action');
        $action = (new $actionClass)::first();

        if ($action) {
            $action->touch();
            $this->assertFalse(Cache::has('governor:actions'), 'Observer should flush cache on model save');
        }
    }
}
