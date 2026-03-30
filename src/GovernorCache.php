<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor;

use Illuminate\Support\Facades\Cache;

class GovernorCache
{
    public const KEY_ROLES = 'genealabs:laravel-governor:roles';
    public const KEY_PERMISSIONS = 'genealabs:laravel-governor:permissions';
    public const KEY_ROLE_USERS_PREFIX = 'genealabs:laravel-governor:role-users:';
    public const KEY_ACTIONS = 'genealabs:laravel-governor:actions';
    public const KEY_ENTITIES = 'genealabs:laravel-governor:entities';

    public static function ttl(): int
    {
        return (int) config('genealabs-laravel-governor.cache-ttl', 300);
    }

    public static function roles(): mixed
    {
        if (static::ttl() <= 0) {
            return static::freshRoles();
        }

        return Cache::remember(static::KEY_ROLES, static::ttl(), function () {
            return static::freshRoles();
        });
    }

    public static function permissions(): mixed
    {
        if (static::ttl() <= 0) {
            return static::freshPermissions();
        }

        return Cache::remember(static::KEY_PERMISSIONS, static::ttl(), function () {
            return static::freshPermissions();
        });
    }

    public static function actions(): mixed
    {
        if (static::ttl() <= 0) {
            return static::freshActions();
        }

        return Cache::remember(static::KEY_ACTIONS, static::ttl(), function () {
            return static::freshActions();
        });
    }

    public static function entities(): mixed
    {
        if (static::ttl() <= 0) {
            return static::freshEntities();
        }

        return Cache::remember(static::KEY_ENTITIES, static::ttl(), function () {
            return static::freshEntities();
        });
    }

    public static function flushRoles(): void
    {
        Cache::forget(static::KEY_ROLES);

        $roleClass = config("genealabs-laravel-governor.models.role");

        app()->instance("governor-roles", (new $roleClass)
            ->select('name')
            ->toBase()
            ->get());
    }

    public static function flushPermissions(): void
    {
        Cache::forget(static::KEY_PERMISSIONS);

        $permissionClass = config("genealabs-laravel-governor.models.permission");

        app()->instance("governor-permissions", (new $permissionClass)
            ->with("role", "team")
            ->toBase()
            ->get());
    }

    public static function flushAll(): void
    {
        Cache::forget(static::KEY_ROLES);
        Cache::forget(static::KEY_PERMISSIONS);
        Cache::forget(static::KEY_ACTIONS);
        Cache::forget(static::KEY_ENTITIES);
    }

    protected static function freshRoles(): mixed
    {
        $roleClass = config("genealabs-laravel-governor.models.role");

        return (new $roleClass)
            ->select('name')
            ->toBase()
            ->get();
    }

    protected static function freshPermissions(): mixed
    {
        $permissionClass = config("genealabs-laravel-governor.models.permission");

        return (new $permissionClass)
            ->with("role", "team")
            ->toBase()
            ->get();
    }

    protected static function freshActions(): mixed
    {
        $actionClass = app(config('genealabs-laravel-governor.models.action'));

        return (new $actionClass)
            ->orderBy("name")
            ->get();
    }

    protected static function freshEntities(): mixed
    {
        $entityClass = app(config('genealabs-laravel-governor.models.entity'));

        return (new $entityClass)
            ->select("name", "policy_class")
            ->with("group:name")
            ->orderBy("name")
            ->toBase()
            ->get();
    }
}
