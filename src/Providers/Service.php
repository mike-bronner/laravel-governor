<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Providers;

use GeneaLabs\LaravelGovernor\Action;
use GeneaLabs\LaravelGovernor\Console\Commands\Publish;
use GeneaLabs\LaravelGovernor\Console\Commands\Setup;
use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\GovernorCache;
use GeneaLabs\LaravelGovernor\Http\Middleware\ParseCustomPolicyActions;
use GeneaLabs\LaravelGovernor\Listeners\CreatedInvitationListener;
use GeneaLabs\LaravelGovernor\Listeners\CreatedListener;
use GeneaLabs\LaravelGovernor\Listeners\CreatedTeamListener;
use GeneaLabs\LaravelGovernor\Listeners\CreatingInvitationListener;
use GeneaLabs\LaravelGovernor\Listeners\CreatingListener;
use GeneaLabs\LaravelGovernor\Observers\LookupTableObserver;
use GeneaLabs\LaravelGovernor\Ownership;
use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\View\Components\MenuBar;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\AggregateServiceProvider;
use Illuminate\Support\Facades\Schema;

class Service extends AggregateServiceProvider
{
    protected $defer = true;

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'genealabs-laravel-governor');
        $this->commands(Publish::class, Setup::class);
        $this->app->singleton(GovernorCache::class);
    }

    public function boot(): void
    {
        $cache = $this->app->make(GovernorCache::class);

        $this->app
            ->singleton('governor-actions', function () use ($cache) {
                return $cache->remember('actions', function () {
                    return app(config('genealabs-laravel-governor.models.action'))
                        ->orderBy("name")
                        ->get();
                });
            });
        $this->app
            ->singleton('governor-entities', function () use ($cache) {
                return $cache->remember('entities', function () {
                    return app(config('genealabs-laravel-governor.models.entity'))
                        ->select("name", "policy_class")
                        ->with("group:name")
                        ->orderBy("name")
                        ->toBase()
                        ->get();
                });
            });
        $this->app
            ->singleton("governor-permissions", function () use ($cache) {
                return $cache->remember('permissions', function () {
                    return app(config("genealabs-laravel-governor.models.permission"))
                        ->with("role", "team")
                        ->toBase()
                        ->get();
                });
            });
        $this->app
            ->singleton("governor-roles", function () use ($cache) {
                return $cache->remember('roles', function () {
                    return app(config("genealabs-laravel-governor.models.role"))
                        ->select('name')
                        ->toBase()
                        ->get();
                });
            });

        $teamClass = config("genealabs-laravel-governor.models.team");
        $invitationClass = config("genealabs-laravel-governor.models.invitation");
        app('events')->listen('eloquent.created: *', CreatedListener::class);
        app('events')->listen('eloquent.creating: *', CreatingListener::class);
        app('events')->listen('eloquent.saving: *', CreatingListener::class);
        app('events')->listen("eloquent.created: {$invitationClass}", CreatedInvitationListener::class);
        app('events')->listen("eloquent.created: {$teamClass}", CreatedTeamListener::class);
        app('events')->listen("eloquent.creating: {$invitationClass}", CreatingInvitationListener::class);
        $this->publishes([
            __DIR__ . '/../../config/config.php' => config_path('genealabs-laravel-governor.php')
        ], 'config');
        $this->publishes([
            __DIR__ . '/../../dist' => public_path('vendor/genealabs/laravel-governor')
        ], 'assets');
        $this->publishes([
            __DIR__ . '/../../resources/views' => base_path('resources/views/vendor/genealabs-laravel-governor')
        ], 'views');
        $this->publishes([
            __DIR__ . '/../../database/migrations' => base_path('database/migrations')
        ], 'migrations');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'genealabs-laravel-governor');
        $this->loadViewComponentsAs('governor', [
            MenuBar::class,
        ]);
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->registerLookupTableObservers();

        $this->app
            ->make(Kernel::class)
            ->pushMiddleware(ParseCustomPolicyActions::class);
    }

    protected function registerLookupTableObservers(): void
    {
        $observer = $this->app->make(LookupTableObserver::class);

        $models = [
            config('genealabs-laravel-governor.models.action', Action::class),
            config('genealabs-laravel-governor.models.entity', Entity::class),
            config('genealabs-laravel-governor.models.ownership', Ownership::class),
            config('genealabs-laravel-governor.models.permission', Permission::class),
            config('genealabs-laravel-governor.models.role', Role::class),
        ];

        foreach ($models as $model) {
            $model::observe($observer);
        }
    }

    public function provides(): array
    {
        return [];
    }
}
