<?php namespace GeneaLabs\LaravelGovernor\Tests;

use GeneaLabs\LaravelGovernor\Database\Seeders\LaravelGovernorDatabaseSeeder;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author as AuthorPolicy;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\User as UserPolicy;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use Illuminate\Support\Facades\Gate;

trait CreatesApplication
{
    protected function getEnvironmentSetUp($app)
    {
        Gate::policy(Author::class, AuthorPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('genealabs-laravel-governor.models.auth', User::class);
        $app["router"]->get('login', 'Auth\LoginController@showLoginForm')->name('login');
        $app["router"]->post('login', 'Auth\LoginController@login');
        $app["router"]->post('logout', 'Auth\LoginController@logout')->name('logout');
        $app["router"]->get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
        $app["router"]->post('register', 'Auth\RegisterController@register');
        $app["router"]->get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm');
        $app["router"]->post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
        $app["router"]->get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm');
        $app["router"]->post('password/reset', 'Auth\ResetPasswordController@reset');
    }

    protected function getPackageProviders($app)
    {
        return [
            'GeneaLabs\LaravelGovernor\Providers\Service',
            'GeneaLabs\LaravelGovernor\Providers\Auth',
            'GeneaLabs\LaravelGovernor\Providers\Route',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . "/database/migrations");
        $this->loadMigrationsFrom(__DIR__ . "/../database/migrations");
        $this->artisan('migrate');
        $this->artisan('db:seed', [
            '--class' => LaravelGovernorDatabaseSeeder::class,
            '--no-interaction' => true
            ]);

        $actionClass = app(config('genealabs-laravel-governor.models.action'));
        $entityClass = app(config('genealabs-laravel-governor.models.entity'));
        $roleClass = config("genealabs-laravel-governor.models.role");
        $actions = (new $actionClass)
            ->orderBy("name")
            ->get();
        $entities = (new $entityClass)
            ->select("name", "policy_class")
            ->with("group:name")
            ->orderBy("name")
            ->toBase()
            ->get();
        $roles = (new $roleClass)
            ->select('name')
            ->toBase()
            ->get();

        app()->instance("governor-actions", $actions);
        app()->instance("governor-entities", $entities);
        app()->instance("governor-roles", $roles);
    }
}
