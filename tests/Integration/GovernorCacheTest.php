<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Action;
use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\GovernorCache;
use GeneaLabs\LaravelGovernor\Ownership;
use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GovernorCacheTest extends IntegrationTestCase
{
    protected GovernorCache $governorCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->governorCache = app(GovernorCache::class);
    }

    public function test_cache_is_populated_on_first_lookup_and_reused(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => 3600]);

        // Flush any existing state
        $this->governorCache->flush();
        app()->forgetInstance('governor-actions');
        app()->forgetInstance('governor-entities');
        app()->forgetInstance('governor-permissions');
        app()->forgetInstance('governor-roles');

        // First call — should hit DB and populate cache
        $this->assertFalse(Cache::has('governor:actions'));

        $actions = $this->governorCache->remember('actions', function () {
            return Action::orderBy('name')->get();
        });

        $this->assertTrue(Cache::has('governor:actions'));
        $this->assertNotEmpty($actions);

        // Second call — should use cached value, not the closure
        $closureCalled = false;
        $cachedActions = $this->governorCache->remember('actions', function () use (&$closureCalled) {
            $closureCalled = true;

            return Action::orderBy('name')->get();
        });

        $this->assertFalse($closureCalled, 'Closure should not be called on cached lookup');
        $this->assertEquals($actions->count(), $cachedActions->count());
    }

    public function test_modifying_action_flushes_cache(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => 3600]);

        // Populate cache
        $this->governorCache->remember('actions', function () {
            return Action::orderBy('name')->get();
        });
        $this->assertTrue(Cache::has('governor:actions'));

        // Modify a lookup table entry
        $action = Action::first();
        $originalName = $action->name;
        $action->name = 'test-cache-invalidation-action';
        $action->save();

        // Cache should be flushed
        $this->assertFalse(Cache::has('governor:actions'));

        // Restore
        $action->name = $originalName;
        $action->save();
    }

    public function test_modifying_role_flushes_cache(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => 3600]);

        $this->governorCache->remember('roles', function () {
            return Role::select('name')->toBase()->get();
        });
        $this->assertTrue(Cache::has('governor:roles'));

        $role = new Role();
        $role->name = 'test-cache-role';
        $role->description = 'A role created to test cache invalidation behavior';
        $role->save();

        $this->assertFalse(Cache::has('governor:roles'));

        $role->delete();
    }

    public function test_modifying_ownership_flushes_cache(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => 3600]);

        $this->governorCache->remember('actions', function () {
            return Action::orderBy('name')->get();
        });
        $this->assertTrue(Cache::has('governor:actions'));

        $ownership = Ownership::first();
        $ownership->touch();

        // All keys flushed on any lookup table change
        $this->assertFalse(Cache::has('governor:actions'));
    }

    public function test_deleting_lookup_entry_flushes_cache(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => 3600]);

        $this->governorCache->remember('roles', function () {
            return Role::select('name')->toBase()->get();
        });
        $this->assertTrue(Cache::has('governor:roles'));

        $role = new Role();
        $role->name = 'test-delete-cache-role';
        $role->description = 'A role created to test cache invalidation on delete';
        $role->save();

        // Re-populate cache
        $this->governorCache->remember('roles', function () {
            return Role::select('name')->toBase()->get();
        });
        $this->assertTrue(Cache::has('governor:roles'));

        $role->delete();

        $this->assertFalse(Cache::has('governor:roles'));
    }

    public function test_cached_lookups_reduce_query_count(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => 3600]);

        $this->governorCache->flush();

        // First call: populates cache (will query DB)
        DB::enableQueryLog();
        $this->governorCache->remember('actions', function () {
            return Action::orderBy('name')->get();
        });
        $firstCallQueries = count(DB::getQueryLog());
        DB::flushQueryLog();

        // Second call: should use cache (no DB queries)
        $this->governorCache->remember('actions', function () {
            return Action::orderBy('name')->get();
        });
        $secondCallQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertGreaterThan(0, $firstCallQueries, 'First lookup should query the database');
        $this->assertSame(0, $secondCallQueries, 'Cached lookup should not query the database');
    }

    public function test_cache_disabled_always_runs_closure(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => false]);

        $callCount = 0;
        $closure = function () use (&$callCount) {
            $callCount++;

            return Action::orderBy('name')->get();
        };

        $this->governorCache->remember('actions', $closure);
        $this->governorCache->remember('actions', $closure);

        $this->assertSame(2, $callCount, 'Closure should be called every time when cache is disabled');
    }

    public function test_cache_with_null_ttl_stores_forever(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => null]);

        $this->governorCache->flush();

        $this->governorCache->remember('actions', function () {
            return Action::orderBy('name')->get();
        });

        $this->assertTrue(Cache::has('governor:actions'));
    }

    public function test_flush_clears_all_governor_cache_keys(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => 3600]);

        // Populate multiple cache keys
        $this->governorCache->remember('actions', fn () => 'actions-data');
        $this->governorCache->remember('entities', fn () => 'entities-data');
        $this->governorCache->remember('permissions', fn () => 'permissions-data');
        $this->governorCache->remember('roles', fn () => 'roles-data');

        $this->assertTrue(Cache::has('governor:actions'));
        $this->assertTrue(Cache::has('governor:entities'));
        $this->assertTrue(Cache::has('governor:permissions'));
        $this->assertTrue(Cache::has('governor:roles'));

        $this->governorCache->flush();

        $this->assertFalse(Cache::has('governor:actions'));
        $this->assertFalse(Cache::has('governor:entities'));
        $this->assertFalse(Cache::has('governor:permissions'));
        $this->assertFalse(Cache::has('governor:roles'));
    }
}
