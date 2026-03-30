<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor;

use Closure;
use Illuminate\Support\Facades\Cache;

class GovernorCache
{
    protected const PREFIX = 'governor:';

    protected const KEYS = [
        'actions',
        'entities',
        'permissions',
        'roles',
    ];

    public function remember(string $key, Closure $callback): mixed
    {
        if (! $this->isEnabled()) {
            return $callback();
        }

        $cacheKey = self::PREFIX . $key;
        $ttl = config('genealabs-laravel-governor.cache.ttl');

        if ($ttl === null) {
            return Cache::rememberForever($cacheKey, $callback);
        }

        return Cache::remember($cacheKey, (int) $ttl, $callback);
    }

    public function flush(): void
    {
        foreach (self::KEYS as $key) {
            Cache::forget(self::PREFIX . $key);
        }
    }

    public function forget(string $key): void
    {
        Cache::forget(self::PREFIX . $key);
    }

    public function isEnabled(): bool
    {
        return (bool) config('genealabs-laravel-governor.cache.enabled', false);
    }
}
