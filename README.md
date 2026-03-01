# Governor For Laravel
[![Build Status](https://travis-ci.org/GeneaLabs/laravel-governor.svg?branch=master)](https://travis-ci.org/GeneaLabs/laravel-governor)
[![Coverage Status](https://coveralls.io/repos/github/GeneaLabs/laravel-governor/badge.svg?branch=master)](https://coveralls.io/github/GeneaLabs/laravel-governor?branch=master)
[![Latest StableVersion](https://poser.pugx.org/genealabs/laravel-governor/v/stable.png)](https://packagist.org/packages/genealabs/laravel-governor)
[![Total Downloads](https://poser.pugx.org/genealabs/laravel-governor/downloads.png)](https://packagist.org/packages/genealabs/laravel-governor)

![Governor for Laravel](https://repository-images.githubusercontent.com/41706753/37d93d00-f1b1-11e9-9f67-067c80849466)

**Manage authorization with granular role-based permissions in your Laravel apps.**

![screencast 2017-06-04 at 3 34 56 pm](https://cloud.githubusercontent.com/assets/1791050/26765962/fa085878-493b-11e7-9bb7-b4d9f88844a0.gif)

## Goal
Provide a simple method of managing ACL in a Laravel application built on the
 Laravel Authorization functionality. By leveraging Laravel's native
 Authorization functionality there is no additional learning or implementation
 curve. All you need to know is Laravel, and you will know how to use Governor
 for Laravel.

## Requirements

| Laravel | PHP            | Package |
|---------|----------------|---------|
| 9.x     | 8.2 - 8.4      | latest  |
| 10.x    | 8.2 - 8.5      | latest  |
| 11.x    | 8.2 - 8.5      | latest  |
| 12.x    | 8.2 - 8.5      | latest  |

### Additional Requirements
- Bootstrap 3 (needs to be included in your layout file)
- FontAwesome 4 (needs to be included in your layout file)

## Installation
The user with the lowest primary key will be set up as the SuperAdmin. If you're
 starting on a new project, be sure to add an initial user now. If you already
 have users, you can update the role-user entry to point to your intended user,
 if the first user is not the intended SuperAdmin. Now let's get the package
 installed.

Install via composer:
```sh
composer require genealabs/laravel-governor
```

## Implementation
1. First we need to update the database by running the migrations and data seeders:
    ```sh
    php artisan migrate --path="vendor/genealabs/laravel-governor/database/migrations"
    php artisan db:seed --class=LaravelGovernorDatabaseSeeder
    ```

2. If you have seeders of your own, run them now:
    ```sh
    php artisan db:seed
    ```

3. Next, assign permissions (this requires you have users already populated):
    ```sh
    php artisan db:seed --class=LaravelGovernorPermissionsTableSeeder
    ```

4. Now we need to make the assets available:
    ```sh
    php artisan governor:publish --assets
    ```

5. Lastly, add the Governable trait to the User model of your app:
    ```php
    // [...]
    use GeneaLabs\LaravelGovernor\Traits\Governable;

    class User extends Authenticatable
    {
        use Governable;
        // [...]
    }
    ```

## Upgrading
The following upgrade guides should help navigate updates with breaking changes.

### From 0.11.5+ to 0.12 [Breaking]
The role_user pivot table has replaced the composite key with a primary key, as Laravel does not fully support composite keys. Run:
```sh
php artisan db:seed --class="LaravelGovernorUpgradeTo0120"
```

### From 0.11 to 0.11.5 [Breaking]
The primary keys of the package's tables have been renamed. (This should have been a minor version change, instead of a patch, as this was a breaking change.) Run:
```sh
php artisan db:seed --class="LaravelGovernorUpgradeTo0115"
```

### From 0.10 to 0.11 [Breaking]
The following traits have changed:
- `Governable` has been renamed to `Governing`.
- `Governed` has been renamed to `Governable`.
- the `governor_created_by` has been renamed to `governor_owned_by`. Run migrations to update your tables.
    ```sh
    php artisan db:seed --class="LaravelGovernorUpgradeTo0110"
    ```
- replace any reference in your app from `governor_created_by` to
    `governor_owned_by`.

### From 0.6 to Version 0.10 [Breaking]
To upgrade from version previous to `0.10.0`, first run the migrations and
seeders, then run the update seeder:
```sh
php artisan migrate --path="vendor/genealabs/laravel-governor/database/migrations"
php artisan db:seed --class="LaravelGovernorDatabaseSeeder"
php artisan db:seed --class="LaravelGovernorUpgradeTo0100"
```

### to 0.6 [Breaking]
1. If you were extending `GeneaLabs\LaravelGovernor\Policies\LaravelGovernorPolicy`,
  change to extend `GeneaLabs\LaravelGovernor\Policies\BasePolicy`;
2. Support for version of Laravel lower than 5.5 has been dropped.

## Configuration
If you need to make any changes (see Example selection below for the default
 config file) to the default configuration, publish the configuration file:

```sh
php artisan governor:publish --config
```

and make any necessary changes. (We don't recommend publishing the config file
if you don't need to make any changes.)

### Views
If you would like to customize the views, publish them:

```sh
php artisan governor:publish --views
```

and edit them in `resources\views\vendor\genealabs\laravel-governor`.

### Policies
Policies are now auto-detected and automatically added to the entities list. You
 will no longer need to manage Entities manually. New policies will be available
 for role assignment when editing roles. Check out the example policy in
 the Examples section below. See Laravel's documentation on how to create
 policies and check for them in code:
 https://laravel.com/docs/5.4/authorization#writing-policies

**Your policies must extend LaravelGovernorPolicy in order to function with
 Governor.** By default you do not need to include any of the methods, as they
 are implemented automatically and perform checks based on reflection. However,
 if you need to customize anything, you are free to override any of the `before`,
 `create`, `edit`, `view`, `inspect`, and `remove` methods.

#### Checking Authorization
To validate a user against a given policy, use one of the keywords that Governor
 validates against: `before`, `create`, `edit`, `view`, `inspect`, and `remove`.
 For example, if the desired policy to check has a class name of `BlogPostPolicy`,
 you would authorize your user with something like `$user->can('create', (new BlogPost))`
 or `$user->can('edit', $blogPost)`.

### Filter Queries To Show Ownly Allowed Items
Often it is desirable to let the user see only the items that they have access
    to. This was previously difficult and tedious. Using Nova as an example, you
    can now limit the index view as follows:
    ```php
    <?php namespace App\Nova;

    use Laravel\Nova\Resource as NovaResource;
    use Laravel\Nova\Http\Requests\NovaRequest;

    abstract class Resource extends NovaResource
    {
        public static function indexQuery(NovaRequest $request, $query)
        {
            $model = $query->getModel();

            if ($model
                && is_object($model)
                && method_exists($model, "filterViewAnyable")
            ) {
                $query = $model->filterViewAnyable($query);
            }

            return $query;
        }

        // ...
    }
    ```

    The available query filters are:
    - `filterDeletable(Builder $query)`
    - `filterUpdatable(Builder $query)`
    - `filterViewable(Builder $query)`
    - `filterViewAnyable(Builder $query)`

    The same functionality is availabe via model scopes, as well:
    - `deletable()`
    - `updatable()`
    - `viewable()`
    - `viewAnyable()`

### Tables
Tables will automatically be updated with a `governor_owned_by` column that references
 the user that created the entry. There is no more need to run separate
 migrations or work around packages that have models without a created_by
 property.

### Admin Views
The easiest way to integrate Governor for Laravel into your app is to add the
 menu items to the relevant section of your app's menu (make sure to restrict
 access appropriately using the Laravel Authorization methods). The following
 routes can be added:
- Role Management: `genealabs.laravel-governor.roles.index`
- User-Role Assignments: `genealabs.laravel-governor.assignments.index`

For example:
```php
<li><a href="{{ route('genealabs.laravel-governor.roles.index') }}">Governor</a></li>
```

### 403 Unauthorized
We recommend making a custom 403 error page to let the user know they don't have
 access. Otherwise the user will just see the default error message. See
 https://laravel.com/docs/5.4/errors#custom-http-error-pages for more details on
 how to set those up.

### Authorization API
You can check a user's ability to perform certain actions via a public API. It
is recommended to use Laravel Passport to maintain session state between your
client and your backend. Here's an example that checks if the currently logged
in user can create `GeneaLabs\LaravelGovernor\Role` model records:

```php
$response = $this
    ->json(
        "GET",
        route('genealabs.laravel-governor.api.user-can.show', "create"),
        [
            "model" => "GeneaLabs\LaravelGovernor\Role",
        ]
    );
```

This next example checks if the user can edit `GeneaLabs\LaravelGovernor\Role`
model records:
```php
$response = $this
    ->json(
        "GET",
        route('genealabs.laravel-governor.api.user-can.show', "edit"),
        [
            "model" => "GeneaLabs\LaravelGovernor\Role",
            "primary-key" => 1,
        ]
    );
```

The abilities `inspect`, `edit`, and `remove`, except `create` and `view`,
require the primary key to be passed.

### Role-Check API
// TODO: add documentation
```php
$response = $this
    ->json(
        "GET",
        route('genealabs.laravel-governor.api.user-is.show', "SuperAdmin")
    );
```

## Examples
### Config File
```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Layout Blade File
    |--------------------------------------------------------------------------
    |
    | This value is used to reference your main layout blade view to render
    | the views provided by this package. The layout view referenced here
    | should include Bootstrap 3 and FontAwesome 4 to work as intended.
    */
    'layout-view' => 'layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Layout Content Section Name
    |--------------------------------------------------------------------------
    |
    | Specify the name of the section in the view referenced above that is
    | used to render the main page content. If this does not match, you
    | will only get blank pages when accessing views in Governor.
    */
    'content-section' => 'content',

    /*
    |--------------------------------------------------------------------------
    | Authorization Model
    |--------------------------------------------------------------------------
    |
    | Here you can customize what model should be used for authorization checks
    | in the event that you have customized your authentication processes.
    */
    'auth-model' => config('auth.providers.users.model') ?? config('auth.model'),

    /*
    |--------------------------------------------------------------------------
    | User Model Name Property
    |--------------------------------------------------------------------------
    |
    | This value is used to display your users when assigning them to roles.
    | You can choose any property of your auth-model defined above that is
    | exposed via JSON.
    */
    'user-name-property' => 'name',

    /*
    |--------------------------------------------------------------------------
    | URL Prefix
    |--------------------------------------------------------------------------
    |
    | If you want to change the URL used by the browser to access the admin
    | pages, you can do so here. Be careful to avoid collisions with any
    | existing URLs of your app when doing so.
    */
    'url-prefix' => '/genealabs/laravel-governor/',
];
```

### Policy
#### No Methods Required For Default Policies
Adding policies is crazily simple! All the work has been refactored out so all
 you need to worry about now is creating a policy class, and that's it!

```php
<?php namespace GeneaLabs\LaravelGovernor\Policies;

use GeneaLabs\LaravelGovernor\Interfaces\GovernablePolicy;
use Illuminate\Auth\Access\HandlesAuthorization;

class MyPolicy extends LaravelGovernorPolicy
{
    use HandlesAuthorization;
}
```

#### Default Methods In A Policy Class
Adding any of the `before`, `create`, `update`, `view`, `viewAny`, `delete`,
`restore`, and `forceDelete` methods to your policy is only required if you want
to customize a given method.

```php
abstract class BasePolicy
{
    public function before($user)
    {
        return $user->hasRole("SuperAdmin")
            ?: null;
    }

    public function create(Model $user) : bool
    {
        return $this->validatePermissions(
            $user,
            'create',
            $this->entity
        );
    }

    public function update(Model $user, Model $model) : bool
    {
        return $this->validatePermissions(
            $user,
            'update',
            $this->entity,
            $model->governor_owned_by
        );
    }

    public function viewAny(Model $user) : bool
    {
        return true;

        return $this->validatePermissions(
            $user,
            'viewAny',
            $this->entity
        );
    }

    public function view(Model $user, Model $model) : bool
    {
        return $this->validatePermissions(
            $user,
            'view',
            $this->entity,
            $model->governor_owned_by
        );
    }

    public function delete(Model $user, Model $model) : bool
    {
        return $this->validatePermissions(
            $user,
            'delete',
            $this->entity,
            $model->governor_owned_by
        );
    }

    public function restore(Model $user, Model $model) : bool
    {
        return $this->validatePermissions(
            $user,
            'restore',
            $this->entity,
            $model->governor_owned_by
        );
    }

    public function forceDelete(Model $user, Model $model) : bool
    {
        return $this->validatePermissions(
            $user,
            'forceDelete',
            $this->entity,
            $model->governor_owned_by
        );
    }
}
```
