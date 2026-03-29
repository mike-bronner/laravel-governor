<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Providers;

use GeneaLabs\LaravelGovernor\Console\Commands\Publish;
use GeneaLabs\LaravelGovernor\Console\Commands\Setup;
use GeneaLabs\LaravelGovernor\Http\Middleware\ParseCustomPolicyActions;
use GeneaLabs\LaravelGovernor\Listeners\CreatedInvitationListener;
use GeneaLabs\LaravelGovernor\Listeners\CreatedListener;
use GeneaLabs\LaravelGovernor\Listeners\CreatedTeamListener;
use GeneaLabs\LaravelGovernor\Listeners\CreatingInvitationListener;
use GeneaLabs\LaravelGovernor\Listeners\CreatingListener;
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
    }

    public function boot(): void
    {
        $this->app
            ->singleton('governor-actions', function () {
                $actionClass = app(config('genealabs-laravel-governor.models.action'));

                return (new $actionClass)
                    ->orderBy("name")
                    ->get();
            });
        $this->app
            ->singleton('governor-entities', function () {
                $entityClass = app(config('genealabs-laravel-governor.models.entity'));

                return (new $entityClass)
                    ->select("name")
                    ->with("group:name")
                    ->orderBy("name")
                    ->toBase()
                    ->get();
            });
        $this->app
            ->singleton("governor-permissions", function () {
                $permissionClass = config("genealabs-laravel-governor.models.permission");

                return (new $permissionClass)
                    ->with("role", "team")
                    ->toBase()
                    ->get();
            });
        $this->app
            ->singleton("governor-roles", function () {
                $roleClass = config("genealabs-laravel-governor.models.role");

                return (new $roleClass)
                    ->select('name')
                    ->toBase()
                    ->get();
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

        $this->app
            ->make(Kernel::class)
            ->pushMiddleware(ParseCustomPolicyActions::class);
    }

    public function provides(): array
    {
        return [];
    }
}
