<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Providers;

use GeneaLabs\LaravelGovernor\GovernorCache;
use GeneaLabs\LaravelGovernor\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Cache;

class ServiceCacheIntegrationTest extends IntegrationTestCase
{
    public function testGovernorSingletonsUseCacheWhenEnabled(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => 3600]);

        $cache = app(GovernorCache::class);
        $cache->flush();

        app()->forgetInstance('governor-actions');
        app()->forgetInstance('governor-entities');
        app()->forgetInstance('governor-permissions');
        app()->forgetInstance('governor-roles');

        $actions = app('governor-actions');
        $entities = app('governor-entities');
        $permissions = app('governor-permissions');
        $roles = app('governor-roles');

        $this->assertTrue(Cache::has('governor:actions'));
        $this->assertTrue(Cache::has('governor:entities'));
        $this->assertTrue(Cache::has('governor:permissions'));
        $this->assertTrue(Cache::has('governor:roles'));

        $this->assertNotNull($actions);
        $this->assertNotNull($entities);
        $this->assertNotNull($permissions);
        $this->assertNotNull($roles);
    }

    public function testGovernorSingletonsWorkWithoutCache(): void
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

        $this->assertFalse(Cache::has('governor:actions'));
        $this->assertNotNull($actions);
        $this->assertNotNull($entities);
        $this->assertNotNull($permissions);
        $this->assertNotNull($roles);
    }

    public function testLookupTableObserversRegisteredOnAllModels(): void
    {
        config(['genealabs-laravel-governor.cache.enabled' => true]);
        config(['genealabs-laravel-governor.cache.ttl' => 3600]);

        $cache = app(GovernorCache::class);
        $cache->remember('actions', fn () => 'test-data');
        $this->assertTrue(Cache::has('governor:actions'));

        $action = app(config('genealabs-laravel-governor.models.action'))->first();

        if ($action) {
            $action->touch();
            $this->assertFalse(Cache::has('governor:actions'), 'Observer should flush cache on model save');
        }
    }
}
