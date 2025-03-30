<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Policies;

use GeneaLabs\LaravelGovernor\Traits\EntityManagement;
use GeneaLabs\LaravelGovernor\Traits\GovernorOwnedByField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BasePolicy
{
    use EntityManagement;
    use GovernorOwnedByField;

    protected string $entity;
    protected Collection $permissions;

    public function __construct()
    {
        $this->entity = $this->getEntity(get_class($this));
        $this->permissions = $this->getPermissions();
    }

    protected function getPermissions(): Collection
    {
        return app("governor-permissions");
    }

    public function create(?Model $user): bool
    {
        return $this->validatePermissions(
            $user,
            'create',
            $this->entity
        );
    }

    public function update(?Model $user, Model $model): bool
    {
        return $this->validatePermissions(
            $user,
            'update',
            $this->entity,
            $model
        );
    }

    public function viewAny(?Model $user): bool
    {
        return $this->validatePermissions(
            $user,
            'viewAny',
            $this->entity
        );
    }

    public function view(?Model $user, Model $model): bool
    {
        return $this->validatePermissions(
            $user,
            'view',
            $this->entity,
            $model
        );
    }

    public function delete(?Model $user, Model $model): bool
    {
        return $this->validatePermissions(
            $user,
            'delete',
            $this->entity,
            $model
        );
    }

    public function restore(?Model $user, Model $model): bool
    {
        return $this->validatePermissions(
            $user,
            'restore',
            $this->entity,
            $model
        );
    }

    public function forceDelete(?Model $user, Model $model): bool
    {
        return $this->validatePermissions(
            $user,
            'forceDelete',
            $this->entity,
            $model
        );
    }

    protected function authorizeCustomAction(?Model $user, Model $model): bool
    {
        $action = debug_backtrace(
            ! DEBUG_BACKTRACE_PROVIDE_OBJECT
                | DEBUG_BACKTRACE_IGNORE_ARGS,
            2,
        )[1]["function"];

        return $this->validatePermissions(
            $user,
            get_class($model) . ":" . $action,
            $this->entity,
            $model,
        );
    }

    protected function validatePermissions(
        ?Model $user,
        string $action,
        string $entity,
        ?Model $model = null
    ): bool {
        if (! $user) {
            $user = $this->createGuestUser();
        }

        $user->loadMissing("roles", "teams.permissions");

        if ($user->hasRole("SuperAdmin")) {
            return true;
        }

        if (
            $user->roles->isEmpty()
            && $user->teams->isEmpty()
        ) {
            return false;
        }

        $ownership = 'other';

        if (
            $model
            && $user->getKey() == $model->governor_owned_by
        ) {
            $ownership = 'own';
        }

        $filteredPermissions = $this->filterPermissions($action, $entity, $ownership);

        foreach ($filteredPermissions as $permission) {
            if (
                $user->roles->pluck("name")->contains($permission->role_name)
                || $user->teams->pluck("id")->contains($permission->team_id)
            ) {
                return true;
            }
        }

        return false;
    }

    protected function filterPermissions($action, $entity, $ownership)
    {
        $filteredPermissions = $this
            ->permissions
            ->filter(function ($permission) use ($action, $entity, $ownership) {
                return ($permission->action_name === $action
                    && $permission->entity_name === $entity
                    && in_array($permission->ownership_name, [$ownership, 'any']));
            });

        return $filteredPermissions;
    }

    protected function createGuestUser()
    {
        if (! auth()->user()) {
            $roleClass = config("genealabs-laravel-governor.models.role");
            $userClass = config("genealabs-laravel-governor.models.auth");
            $user = new $userClass;
            $guest = (new $roleClass)->find("Guest");

            if ($guest) {
                $user->roles = collect([$guest]);
            }

            auth()->setUser($user);

            return $user;
        }
    }
}
