# Governor For Laravel
[![Tests](https://github.com/mikebronner/laravel-governor/actions/workflows/tests.yml/badge.svg)](https://github.com/mikebronner/laravel-governor/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/mikebronner/laravel-governor/branch/master/graph/badge.svg)](https://codecov.io/gh/mikebronner/laravel-governor)
[![Latest StableVersion](https://poser.pugx.org/genealabs/laravel-governor/v/stable.png)](https://packagist.org/packages/genealabs/laravel-governor)
[![Total Downloads](https://poser.pugx.org/genealabs/laravel-governor/downloads.png)](https://packagist.org/packages/genealabs/laravel-governor)

![Governor for Laravel](https://repository-images.githubusercontent.com/41706753/37d93d00-f1b1-11e9-9f67-067c80849466)

**Manage authorization with granular role-based permissions in your Laravel apps.**

![screencast 2017-06-04 at 3 34 56 pm](https://cloud.githubusercontent.com/assets/1791050/26765962/fa085878-493b-11e7-9bb7-b4d9f88844a0.gif)

## Table of Contents
- [Goal](#goal)
- [Requirements](#requirements)
- [Installation](#installation)
- [Implementation](#implementation)
- [Upgrading](#upgrading)
- [Configuration](#configuration)
- [API Reference](#api-reference)
  - [Traits](#traits)
  - [Policies](#policies)
  - [Models](#models)
  - [Artisan Commands](#artisan-commands)
  - [REST API Endpoints](#rest-api-endpoints)
  - [Events](#events)
  - [Blade Components](#blade-components)
- [Examples](#examples)

## Goal
Provide a simple method of managing ACL in a Laravel application built on the
 Laravel Authorization functionality. By leveraging Laravel's native
 Authorization functionality there is no additional learning or implementation
 curve. All you need to know is Laravel, and you will know how to use Governor
 for Laravel.

## Requirements

| Laravel | PHP            | Package |
|---------|----------------|---------|
| 10.x    | 8.2+           | latest  |
| 11.x    | 8.2+           | latest  |
| 12.x    | 8.2+           | latest  |
| 13.x    | 8.3+           | latest  |

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

5. Lastly, add the `Governing` trait to the User model of your app:
    ```php
    use GeneaLabs\LaravelGovernor\Traits\Governing;

    class User extends Authenticatable
    {
        use Governing;
    }
    ```

    For non-User models that should be governed (have ownership tracking and
    permission-scoped queries), use the `Governable` trait instead:
    ```php
    use GeneaLabs\LaravelGovernor\Traits\Governable;

    class BlogPost extends Model
    {
        use Governable;
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
Publish the configuration file if you need to customize defaults:

```sh
php artisan governor:publish --config
```

### Config Options

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `layout-view` | `string` | `'layouts.app'` | Blade layout used to render Governor's built-in admin views. Must include Bootstrap 3 and FontAwesome 4. |
| `content-section` | `string` | `'content'` | Name of the `@yield` section in your layout where Governor renders page content. |
| `auth-model-primary-key-type` | `string` | `'bigInteger'` | Primary key type of your User model. Used when Governor adds the `governor_owned_by` column. Accepts `'integer'` or `'bigInteger'`. |
| `models.auth` | `string` | `config('auth.providers.users.model')` | Fully-qualified class name of your User model. |
| `models.action` | `string` | `Action::class` | Model class for actions. Override to extend the default. |
| `models.assignment` | `string` | `Assignment::class` | Model class for role assignments. |
| `models.entity` | `string` | `Entity::class` | Model class for entities. |
| `models.group` | `string` | `Group::class` | Model class for entity groups. |
| `models.ownership` | `string` | `Ownership::class` | Model class for ownership levels. |
| `models.permission` | `string` | `Permission::class` | Model class for permissions. |
| `models.role` | `string` | `Role::class` | Model class for roles. |
| `models.team` | `string` | `Team::class` | Model class for teams. |
| `models.invitation` | `string` | `TeamInvitation::class` | Model class for team invitations. |
| `user-name-property` | `string` | `'name'` | Property on the User model used for display in the admin UI. |
| `url-prefix` | `string` | `'/genealabs/laravel-governor/'` | URL prefix for all Governor web routes. |
| `superadmins` | `string\|null` | `env('GOVERNOR_SUPERADMINS')` | JSON array of users to auto-create as SuperAdmins during seeding. Format: `[{"name":"...","email":"...","password":"..."}]` |
| `admins` | `string\|null` | `env('GOVERNOR_ADMINS')` | JSON array of users to auto-create as Admins during seeding. Same format as `superadmins`. |
| `entity-aliases` | `array` | `[]` | Map of raw entity names to display names in the UI. E.g. `['User' => 'Team Member']`. |
| `cache.enabled` | `bool` | `false` | Enable cross-request caching of lookup tables (roles, actions, entities, permissions). |
| `cache.ttl` | `int\|null` | `3600` | Cache TTL in seconds. Set to `null` for "forever" (until manually invalidated). |

### Caching
Governor can cache lookup table queries across requests to reduce database load.
This is disabled by default.

To enable caching, publish the config file and update the `cache` section:
```php
'cache' => [
    'enabled' => true,
    'ttl' => 3600, // seconds, or null for "forever"
],
```

Cache is automatically invalidated when any lookup table model (Action, Entity,
Ownership, Permission, Role) is created, updated, or deleted. Invalidation is
coarse-grained: a change to any single lookup table flushes the cache for all
lookup tables. This keeps the invalidation logic simple and reliable, which is
appropriate given that lookup tables change infrequently.

### Views
If you would like to customize the views, publish them:

```sh
php artisan governor:publish --views
```

and edit them in `resources/views/vendor/genealabs-laravel-governor`.

### Tables
Tables will automatically be updated with a `governor_owned_by` column that
references the user that created the entry. There is no more need to run
separate migrations or work around packages that have models without a
`created_by` property.

### Admin Views
The easiest way to integrate Governor into your app is to add menu items to the
relevant section of your app's menu (restrict access using Laravel Authorization
methods). The following named routes are available:
- Role Management: `genealabs.laravel-governor.roles.index`
- User-Role Assignments: `genealabs.laravel-governor.assignments.index`
- Teams: `genealabs.laravel-governor.teams.index`
- Groups: `genealabs.laravel-governor.groups.index`

For example:
```php
<li><a href="{{ route('genealabs.laravel-governor.roles.index') }}">Governor</a></li>
```

### 403 Unauthorized
We recommend making a custom 403 error page to let the user know they don't have
 access. Otherwise the user will just see the default error message. See
 https://laravel.com/docs/errors#custom-http-error-pages for more details on
 how to set those up.

---

## API Reference

### Traits

Governor provides two traits for your Eloquent models. Use `Governing` on your
User model (it includes `Governable` automatically). Use `Governable` on any
other model that should be governed.

#### `Governing` Trait
**Namespace:** `GeneaLabs\LaravelGovernor\Traits\Governing`
**Use on:** Your User model

This trait adds role management, team membership, and permission resolution to
the User model. It includes the `Governable` trait, so you do not need to add
both.

##### `hasRole(string $name): bool`
Check whether the user has a specific role.

- **Parameters:** `$name` — the role name (e.g. `'SuperAdmin'`, `'Member'`)
- **Returns:** `true` if the user has the given role or is a SuperAdmin

```php
if ($user->hasRole('Editor')) {
    // user has the Editor role
}
```

##### `roles(): BelongsToMany`
Eloquent relationship to the user's assigned roles.

- **Returns:** `BelongsToMany` relationship to `Role` via `governor_role_user`

```php
$roleNames = $user->roles->pluck('name'); // ['SuperAdmin', 'Member']
```

##### `ownedTeams(): HasMany`
Eloquent relationship to teams owned by this user.

- **Returns:** `HasMany` relationship to `Team` where `governor_owned_by` matches the user

```php
$myTeams = $user->ownedTeams;
```

##### `teams(): BelongsToMany`
Eloquent relationship to teams the user is a member of.

- **Returns:** `BelongsToMany` relationship to `Team` via `governor_team_user`

```php
$teamNames = $user->teams->pluck('name');
```

##### `permissions` (Accessor)
All permissions associated with the user's roles.

- **Returns:** `Collection` of `Permission` models

```php
$permissions = $user->permissions;
```

##### `effectivePermissions` (Accessor)
Deduplicated permissions across all roles, collapsed to the highest ownership
level per entity/action pair. If any role grants `"any"` ownership, the
effective permission is `"any"`; if the best is `"own"`, it returns `"own"`.

- **Returns:** `Collection` of `Permission` models with `role_name` and `team_name` set to `null`

```php
$effective = $user->effectivePermissions;
```

---

#### `Governable` Trait
**Namespace:** `GeneaLabs\LaravelGovernor\Traits\Governable`
**Use on:** Any Eloquent model that should have ownership tracking and
permission-scoped queries

##### `ownedBy(): BelongsTo`
Eloquent relationship to the user who owns this model.

- **Returns:** `BelongsTo` relationship to the auth model via `governor_owned_by`

```php
$owner = $blogPost->ownedBy;
```

##### `teams(): MorphToMany`
Eloquent polymorphic relationship to teams associated with this model.

- **Returns:** `MorphToMany` relationship to `Team` via `governor_teamables`

```php
$teams = $blogPost->teams;
```

##### Query Scopes

All scopes filter query results based on the authenticated user's permissions.
If the user has `"any"` ownership for the relevant action, the query is
unmodified. If the user has `"own"` ownership, results are limited to records
they own or belong to their teams. If neither, the query returns no results.

| Scope | Filters by action | Usage |
|-------|-------------------|-------|
| `scopeDeletable` | `delete` | `BlogPost::deletable()->get()` |
| `scopeForceDeletable` | `forceDelete` | `BlogPost::forceDeletable()->get()` |
| `scopeRestorable` | `restore` | `BlogPost::restorable()->get()` |
| `scopeUpdatable` | `update` | `BlogPost::updatable()->get()` |
| `scopeViewable` | `view` | `BlogPost::viewable()->get()` |
| `scopeViewAnyable` | `viewAny` | `BlogPost::viewAnyable()->get()` |

These scopes can be applied directly to any query builder instance, making
them useful in Nova or other admin panels:

**Nova example:**
```php
use Laravel\Nova\Resource as NovaResource;
use Laravel\Nova\Http\Requests\NovaRequest;

abstract class Resource extends NovaResource
{
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->viewAnyable();
    }
}
```

---

### Policies

#### `BasePolicy`
**Namespace:** `GeneaLabs\LaravelGovernor\Policies\BasePolicy`

All Governor policies must extend this class. It provides automatic permission
checking for standard Laravel policy methods. Policies are auto-detected from
your `app/Policies` directory (and any paths configured via `policy_paths` in
config) — you do not need to register them manually.

##### Default Policy Methods

These methods are implemented automatically. Override them only if you need
custom behavior:

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `create(?Model $user): bool` | Can the user create a new instance? Checks `create` action permission. |
| `update` | `update(?Model $user, Model $model): bool` | Can the user update this model? Checks `update` action with ownership. |
| `viewAny` | `viewAny(?Model $user): bool` | Can the user list all instances? Checks `viewAny` action permission. |
| `view` | `view(?Model $user, Model $model): bool` | Can the user view this model? Checks `view` action with ownership. |
| `delete` | `delete(?Model $user, Model $model): bool` | Can the user delete this model? Checks `delete` action with ownership. |
| `restore` | `restore(?Model $user, Model $model): bool` | Can the user restore this model? Checks `restore` action with ownership. |
| `forceDelete` | `forceDelete(?Model $user, Model $model): bool` | Can the user force-delete this model? Checks `forceDelete` action with ownership. |

SuperAdmins bypass all permission checks and always return `true`.

Guest users (unauthenticated) are assigned the `Guest` role if it exists.

##### Custom Policy Actions

You can define custom actions beyond the standard CRUD operations by adding
public methods to your policy class. Governor automatically detects any public
methods that are not inherited from `BasePolicy` and registers them as custom
actions in the permissions system.

```php
class BlogPostPolicy extends BasePolicy
{
    public function publish(?Model $user, Model $model): bool
    {
        return $this->authorizeCustomAction($user, $model);
    }

    public function archive(?Model $user, Model $model): bool
    {
        return $this->authorizeCustomAction($user, $model);
    }
}
```

Custom actions are registered with names in the format
`App\Models\BlogPost:publish` and appear in the role permission editor.

To check custom actions in your application:
```php
$user->can('publish', $blogPost);
```

##### Creating a Policy

Create a policy class that extends `BasePolicy`. That's it — no methods are
required for the default CRUD actions to work:

```php
<?php

namespace App\Policies;

use GeneaLabs\LaravelGovernor\Policies\BasePolicy;

class BlogPostPolicy extends BasePolicy
{
    // All standard CRUD permissions are handled automatically.
    // Add custom methods only for non-standard actions.
}
```

**Checking authorization** uses standard Laravel authorization:
```php
// In controllers
$this->authorize('update', $blogPost);

// In Blade templates
@can('delete', $blogPost)
    <button>Delete</button>
@endcan

// Directly on the user
$user->can('create', App\Models\BlogPost::class);
$user->can('update', $blogPost);
```

---

### Models

Governor ships with the following Eloquent models. All can be swapped via the
`models` config key.

#### `Role`
**Table:** `governor_roles`
**Primary Key:** `name` (string, non-incrementing)

| Attribute | Type | Description |
|-----------|------|-------------|
| `name` | `string` | Unique role name (e.g. `SuperAdmin`, `Member`, `Editor`) |
| `description` | `string` | Human-readable description (min 25 chars) |

**Relationships:**
- `permissions(): HasMany` — permissions assigned to this role
- `users(): BelongsToMany` — users assigned to this role

#### `Team`
**Table:** `governor_teams`

| Attribute | Type | Description |
|-----------|------|-------------|
| `name` | `string` | Team name |
| `description` | `string\|null` | Optional description |
| `governor_owned_by` | `int\|null` | ID of the user who owns the team |

**Relationships:**
- `members(): BelongsToMany` — team members (custom relation that prevents detaching the owner)
- `invitations(): HasMany` — pending team invitations
- `permissions(): HasMany` — team-level permissions
- `ownedBy(): BelongsTo` — the user who owns the team
- `teams(): MorphToMany` — parent teams (polymorphic)

#### `Permission`
**Table:** `governor_permissions`

| Attribute | Type | Description |
|-----------|------|-------------|
| `role_name` | `string\|null` | Role this permission belongs to |
| `entity_name` | `string` | Entity (model) this permission applies to |
| `action_name` | `string` | Action name (`create`, `update`, `view`, `delete`, etc.) |
| `ownership_name` | `string` | Ownership level: `no`, `own`, or `any` |
| `team_id` | `int\|null` | Team this permission belongs to (for team-level permissions) |

**Relationships:**
- `role(): BelongsTo` — the associated role
- `team(): BelongsTo` — the associated team (if team-level)

#### `Entity`
**Table:** `governor_entities`
**Primary Key:** `name` (string, non-incrementing)

Entities represent governed model types. They are auto-created when policies are
detected.

| Attribute | Type | Description |
|-----------|------|-------------|
| `name` | `string` | Entity name (derived from policy class name) |
| `policy_class` | `string\|null` | Fully-qualified policy class name |

**Relationships:**
- `group(): BelongsTo` — optional entity group
- `permissions(): HasMany` — permissions for this entity

**Methods:**
- `displayName(): string` — returns the entity alias (from config) or the raw name

#### `Action`
**Table:** `governor_actions`
**Primary Key:** `name` (string, non-incrementing)

Standard actions (`create`, `update`, `view`, `viewAny`, `delete`, `restore`,
`forceDelete`) are seeded automatically. Custom actions are registered when
policies define additional public methods.

#### `Ownership`
**Table:** `governor_ownerships`
**Primary Key:** `name` (string, non-incrementing)

Ownership levels control the scope of a permission: `no` (denied), `own` (only
records owned by the user or their team), `any` (all records).

#### `Group`
**Table:** `governor_groups`
**Primary Key:** `name` (string, non-incrementing)

Groups organize entities in the permission editor UI.

---

### Artisan Commands

#### `governor:publish`
Publish package assets, config, views, or migrations.

```sh
php artisan governor:publish --assets    # Publish frontend assets
php artisan governor:publish --config    # Publish config file
php artisan governor:publish --views     # Publish Blade views
php artisan governor:publish --migrations # Publish migration files
```

Flags can be combined:
```sh
php artisan governor:publish --config --views
```

#### `governor:setup`
Assign the SuperAdmin role to an existing user. Requires either `--superadmin`
(email) or `--user` (ID).

```sh
php artisan governor:setup --superadmin=admin@example.com
php artisan governor:setup --user=1
```

The command will fail if:
- Neither option is provided
- Both options are provided
- The user does not exist
- The `SuperAdmin` role has not been seeded yet

---

### REST API Endpoints

Governor provides two JSON API endpoints, prefixed with
`api` + your configured `url-prefix`. These require the `auth:api` middleware
(e.g. Laravel Passport or Sanctum).

#### Check User Ability
**Route:** `GET /api/{url-prefix}/user-can/{ability}`
**Route Name:** `genealabs.laravel-governor.api.user-can.show`

Check if the authenticated user can perform a given action on a model.

| Parameter | Location | Required | Description |
|-----------|----------|----------|-------------|
| `ability` | URL | Yes | Action to check: `create`, `update`, `view`, `viewAny`, `delete`, `restore`, `forceDelete`, or a custom action |
| `model` | Query | Yes | Fully-qualified model class name |
| `primary-key` | Query | For `update`, `view`, `delete`, `restore`, `forceDelete` | Primary key of the specific model instance |

```php
// Can the user create a Role?
GET /api/genealabs/laravel-governor/user-can/create?model=GeneaLabs\LaravelGovernor\Role

// Can the user update Role with primary key 1?
GET /api/genealabs/laravel-governor/user-can/update?model=GeneaLabs\LaravelGovernor\Role&primary-key=1
```

**Response:** `200 OK` with `{"can": true}` or `{"can": false}`

#### Check User Role
**Route:** `GET /api/{url-prefix}/user-is/{role}`
**Route Name:** `genealabs.laravel-governor.api.user-is.show`

Check if the authenticated user has a specific role.

| Parameter | Location | Required | Description |
|-----------|----------|----------|-------------|
| `role` | URL | Yes | Role name to check (e.g. `SuperAdmin`, `Member`) |

```php
GET /api/genealabs/laravel-governor/user-is/SuperAdmin
```

**Response:** `200 OK` with `{"is": true}` or `{"is": false}`

---

### Web Routes

Governor registers the following resourceful web routes under the configured
`url-prefix`, all using the `web` middleware group:

| Route Name | Method | URI | Controller |
|------------|--------|-----|------------|
| `genealabs.laravel-governor.roles.index` | GET | `{prefix}/roles` | `RolesController@index` |
| `genealabs.laravel-governor.roles.create` | GET | `{prefix}/roles/create` | `RolesController@create` |
| `genealabs.laravel-governor.roles.store` | POST | `{prefix}/roles` | `RolesController@store` |
| `genealabs.laravel-governor.roles.show` | GET | `{prefix}/roles/{role}` | `RolesController@show` |
| `genealabs.laravel-governor.roles.edit` | GET | `{prefix}/roles/{role}/edit` | `RolesController@edit` |
| `genealabs.laravel-governor.roles.update` | PUT/PATCH | `{prefix}/roles/{role}` | `RolesController@update` |
| `genealabs.laravel-governor.roles.destroy` | DELETE | `{prefix}/roles/{role}` | `RolesController@destroy` |
| `genealabs.laravel-governor.groups.*` | CRUD | `{prefix}/groups` | `GroupsController` |
| `genealabs.laravel-governor.teams.*` | CRUD | `{prefix}/teams` | `TeamsController` |
| `genealabs.laravel-governor.teams.transfer-ownership` | POST | `{prefix}/teams/{team}/transfer-ownership` | `TeamsController@transferOwnership` |
| `genealabs.laravel-governor.assignments.*` | CRUD | `{prefix}/assignments` | `AssignmentsController` |
| `genealabs.laravel-governor.invitations.*` | CRUD | `{prefix}/invitations` | `InvitationController` |

---

### Events

Governor hooks into Eloquent model events to automate ownership tracking and
cache invalidation.

#### Ownership Tracking Events

| Event | Listener | Description |
|-------|----------|-------------|
| `eloquent.creating: *` | `CreatingListener` | Sets `governor_owned_by` to the authenticated user's ID on any model with the `Governable` or `Governing` trait, if the column exists and isn't already set. |
| `eloquent.created: *` | `CreatedListener` | After model creation, creates the `governor_owned_by` column on the model's table if it doesn't exist yet (auto-migration). |
| `eloquent.saving: *` | `CreatingListener` | Same as `creating` — ensures ownership is set on save as well. |

#### Team Events

| Event | Listener | Description |
|-------|----------|-------------|
| `eloquent.creating: {TeamInvitation}` | `CreatingInvitationListener` | Validates and prepares team invitation data before creation. |
| `eloquent.created: {TeamInvitation}` | `CreatedInvitationListener` | Sends invitation notification to the invited user after creation. |
| `eloquent.created: {Team}` | `CreatedTeamListener` | Automatically adds the team creator as a member after team creation. |

#### Cache Invalidation

| Event | Observer | Description |
|-------|----------|-------------|
| `created`, `updated`, `deleted` on lookup models | `LookupTableObserver` | Flushes all Governor cache keys when any `Action`, `Entity`, `Ownership`, `Permission`, or `Role` is modified. Also refreshes the in-memory singletons (`governor-actions`, `governor-entities`, `governor-permissions`, `governor-roles`). |

---

### Blade Components

#### `<x-governor-menu-bar />`

Renders a navigation bar component for Governor's admin interface. Include it in
your layout to provide navigation between roles, assignments, teams, and groups.

```blade
<x-governor-menu-bar />
```

---

## Examples

### Config File
```php
<?php

return [
    'layout-view' => 'layouts.app',
    'content-section' => 'content',
    'auth-model-primary-key-type' => 'bigInteger',
    'models' => [
        'auth' => config('auth.providers.users.model') ?? config('auth.model'),
        'action' => GeneaLabs\LaravelGovernor\Action::class,
        'assignment' => GeneaLabs\LaravelGovernor\Assignment::class,
        'entity' => GeneaLabs\LaravelGovernor\Entity::class,
        'group' => GeneaLabs\LaravelGovernor\Group::class,
        'ownership' => GeneaLabs\LaravelGovernor\Ownership::class,
        'permission' => GeneaLabs\LaravelGovernor\Permission::class,
        'role' => GeneaLabs\LaravelGovernor\Role::class,
        'team' => GeneaLabs\LaravelGovernor\Team::class,
        'invitation' => GeneaLabs\LaravelGovernor\TeamInvitation::class,
    ],
    'user-name-property' => 'name',
    'url-prefix' => '/genealabs/laravel-governor/',
    'superadmins' => env('GOVERNOR_SUPERADMINS'),
    'admins' => env('GOVERNOR_ADMINS'),
    'entity-aliases' => [],
    'cache' => [
        'enabled' => false,
        'ttl' => 3600,
    ],
];
```

### Complete Policy with Custom Actions
```php
<?php

namespace App\Policies;

use GeneaLabs\LaravelGovernor\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;

class BlogPostPolicy extends BasePolicy
{
    // Standard CRUD actions are inherited automatically.

    // Custom action: publish
    public function publish(?Model $user, Model $model): bool
    {
        return $this->authorizeCustomAction($user, $model);
    }

    // Custom action: archive
    public function archive(?Model $user, Model $model): bool
    {
        return $this->authorizeCustomAction($user, $model);
    }
}
```

### Using Permission Scopes
```php
// Get only blog posts the user can view
$posts = BlogPost::viewable()->paginate(15);

// Get only blog posts the user can edit
$editable = BlogPost::updatable()->get();

// Get only blog posts the user can delete
$deletable = BlogPost::deletable()->get();
```

### Checking Roles and Permissions
```php
// Check if user has a specific role
if ($user->hasRole('Editor')) {
    // ...
}

// Check policy authorization (standard Laravel)
if ($user->can('update', $blogPost)) {
    // ...
}

// Check custom action
if ($user->can('publish', $blogPost)) {
    // ...
}

// Get all effective permissions for a user
$permissions = $user->effectivePermissions;
```

### Team Management
```php
// Get user's teams
$teams = $user->teams;

// Get teams owned by the user
$ownedTeams = $user->ownedTeams;

// Get team members
$members = $team->members;

// Transfer team ownership (via web route)
POST /genealabs/laravel-governor/teams/{team}/transfer-ownership
```
