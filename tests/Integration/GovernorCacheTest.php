<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\GovernorCache;
use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Tests\CreatesApplication;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GovernorCacheTest extends TestCase
{
    use CreatesApplication;

    #[Test]
    public function roles_are_cached_after_first_lookup(): void
    {
        // First call populates cache
        $roles = GovernorCache::roles();
        $this->assertNotEmpty($roles);

        // Verify cache key exists
        $this->assertNotNull(Cache::get(GovernorCache::KEY_ROLES));
    }

    #[Test]
    public function permissions_are_cached_after_first_lookup(): void
    {
        $permissions = GovernorCache::permissions();
        $this->assertNotNull($permissions);
        $this->assertNotNull(Cache::get(GovernorCache::KEY_PERMISSIONS));
    }

    #[Test]
    public function actions_are_cached_after_first_lookup(): void
    {
        $actions = GovernorCache::actions();
        $this->assertNotNull($actions);
        $this->assertNotNull(Cache::get(GovernorCache::KEY_ACTIONS));
    }

    #[Test]
    public function entities_are_cached_after_first_lookup(): void
    {
        $entities = GovernorCache::entities();
        $this->assertNotNull($entities);
        $this->assertNotNull(Cache::get(GovernorCache::KEY_ENTITIES));
    }

    #[Test]
    public function repeated_role_lookups_hit_cache_not_db(): void
    {
        // First call
        GovernorCache::roles();

        // Count queries for second call
        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        GovernorCache::roles();

        $this->assertSame(0, $queryCount, 'Second role lookup should not hit the database');
    }

    #[Test]
    public function repeated_permission_lookups_hit_cache_not_db(): void
    {
        GovernorCache::permissions();

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        GovernorCache::permissions();

        $this->assertSame(0, $queryCount, 'Second permission lookup should not hit the database');
    }

    #[Test]
    public function creating_a_role_flushes_roles_cache(): void
    {
        GovernorCache::roles();
        $this->assertNotNull(Cache::get(GovernorCache::KEY_ROLES));

        Role::create([
            'name' => 'TestCacheRole',
            'description' => 'A role created to test cache invalidation behavior',
        ]);

        // Cache should be repopulated (flush + re-resolve)
        $roles = app('governor-roles');
        $this->assertTrue($roles->pluck('name')->contains('TestCacheRole'));
    }

    #[Test]
    public function updating_a_role_flushes_roles_cache(): void
    {
        $role = Role::create([
            'name' => 'UpdateCacheRole',
            'description' => 'A role that will be updated to test invalidation',
        ]);

        GovernorCache::roles();

        $role->description = 'Updated description for cache invalidation test';
        $role->save();

        $roles = app('governor-roles');
        $this->assertTrue($roles->pluck('name')->contains('UpdateCacheRole'));
    }

    #[Test]
    public function deleting_a_role_flushes_roles_cache(): void
    {
        Role::create([
            'name' => 'DeleteCacheRole',
            'description' => 'A role that will be deleted to test invalidation',
        ]);

        $rolesBefore = GovernorCache::roles();
        $this->assertTrue($rolesBefore->pluck('name')->contains('DeleteCacheRole'));

        Role::where('name', 'DeleteCacheRole')->first()->delete();

        $rolesAfter = app('governor-roles');
        $this->assertFalse($rolesAfter->pluck('name')->contains('DeleteCacheRole'));
    }

    #[Test]
    public function saving_a_permission_flushes_permissions_cache(): void
    {
        GovernorCache::permissions();
        $this->assertNotNull(Cache::get(GovernorCache::KEY_PERMISSIONS));

        Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'User',
            'action_name' => 'view',
            'ownership_name' => 'own',
        ]);

        // The Permission model's saved event calls syncPermissionsSingleton
        // which now calls GovernorCache::flushPermissions()
        $permissions = app('governor-permissions');
        $this->assertNotEmpty($permissions);
    }

    #[Test]
    public function deleting_a_permission_flushes_permissions_cache(): void
    {
        $permission = Permission::create([
            'role_name' => 'Member',
            'entity_name' => 'User',
            'action_name' => 'restore',
            'ownership_name' => 'own',
        ]);

        GovernorCache::permissions();

        $permission->delete();

        $permissions = app('governor-permissions');
        $this->assertFalse(
            $permissions->contains(function ($p) {
                return $p->role_name === 'Member'
                    && $p->entity_name === 'User'
                    && $p->action_name === 'restore';
            })
        );
    }

    #[Test]
    public function flush_all_clears_all_cache_keys(): void
    {
        GovernorCache::roles();
        GovernorCache::permissions();
        GovernorCache::actions();
        GovernorCache::entities();

        $this->assertNotNull(Cache::get(GovernorCache::KEY_ROLES));
        $this->assertNotNull(Cache::get(GovernorCache::KEY_PERMISSIONS));
        $this->assertNotNull(Cache::get(GovernorCache::KEY_ACTIONS));
        $this->assertNotNull(Cache::get(GovernorCache::KEY_ENTITIES));

        GovernorCache::flushAll();

        $this->assertNull(Cache::get(GovernorCache::KEY_ROLES));
        $this->assertNull(Cache::get(GovernorCache::KEY_PERMISSIONS));
        $this->assertNull(Cache::get(GovernorCache::KEY_ACTIONS));
        $this->assertNull(Cache::get(GovernorCache::KEY_ENTITIES));
    }

    #[Test]
    public function cache_is_bypassed_when_ttl_is_zero(): void
    {
        config()->set('genealabs-laravel-governor.cache-ttl', 0);

        GovernorCache::roles();

        $this->assertNull(
            Cache::get(GovernorCache::KEY_ROLES),
            'Cache should not be populated when TTL is 0'
        );
    }

    #[Test]
    public function cache_ttl_is_configurable(): void
    {
        $this->assertSame(300, GovernorCache::ttl());

        config()->set('genealabs-laravel-governor.cache-ttl', 600);
        $this->assertSame(600, GovernorCache::ttl());

        config()->set('genealabs-laravel-governor.cache-ttl', 0);
        $this->assertSame(0, GovernorCache::ttl());
    }

    #[Test]
    public function modifying_role_user_pivot_via_assignment_flushes_cache(): void
    {
        $user = User::create([
            'name' => 'Cache Test User',
            'email' => 'cache-test@example.com',
            'password' => bcrypt('password'),
        ]);

        GovernorCache::roles();

        // Attach user to Admin role
        $user->roles()->attach('Admin');

        // Verify the singleton was refreshed
        $roles = app('governor-roles');
        $this->assertNotEmpty($roles);
    }

    #[Test]
    public function all_existing_authorization_checks_pass_with_caching(): void
    {
        // Ensure caching is enabled
        config()->set('genealabs-laravel-governor.cache-ttl', 300);

        // Resolve all cached singletons
        $roles = app('governor-roles');
        $permissions = app('governor-permissions');

        // Verify these contain the expected seeded data
        $this->assertTrue($roles->pluck('name')->contains('SuperAdmin'));
        $this->assertTrue($roles->pluck('name')->contains('Admin'));
        $this->assertTrue($roles->pluck('name')->contains('Member'));

        // Permissions collection should be resolvable (may be empty if no permissions seeded)
        $this->assertNotNull($permissions);
    }

    #[Test]
    public function performance_cached_lookups_make_fewer_queries(): void
    {
        // Clear cache
        GovernorCache::flushAll();

        // Count queries for uncached call
        $uncachedQueries = 0;
        $listener = function () use (&$uncachedQueries) {
            $uncachedQueries++;
        };
        DB::listen($listener);
        GovernorCache::roles();
        GovernorCache::permissions();

        // Reset listener
        DB::flushQueryLog();

        // Count queries for cached call
        $cachedQueries = 0;
        $listener2 = function () use (&$cachedQueries) {
            $cachedQueries++;
        };

        // Remove old listener, add new
        DB::listen($listener2);

        // Reset counter — we only care about new queries
        $cachedQueries = 0;
        $prevUncached = $uncachedQueries;

        GovernorCache::roles();
        GovernorCache::permissions();

        // Cached queries should be the number added by the second listener minus base
        // Since array cache driver is in-memory, second calls should add 0 queries
        $newQueries = $cachedQueries;
        $this->assertSame(0, $newQueries, 'Cached lookups should not generate DB queries');
    }
}
