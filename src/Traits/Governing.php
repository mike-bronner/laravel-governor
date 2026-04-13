<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

trait Governing
{
    use Governable;

    public function hasRole(string $name): bool
    {
        $role = app("governor-roles")
            ->where("name", $name)
            ->first();

        if (! $role) {
            return false;
        }

        $this->loadMissing("roles");

        return $this->roles->contains($role->name)
            || $this->roles->contains("SuperAdmin");
    }

    public function roles(): BelongsToMany
    {
        $roleClass = config("genealabs-laravel-governor.models.role");

        return $this->belongsToMany($roleClass, 'governor_role_user', 'user_id', 'role_name');
    }

    /**
     * @deprecated Use governorOwnedTeams() for polymorphic ownership lookup.
     */
    public function ownedTeams(): HasMany
    {
        return $this->hasMany(
            config("genealabs-laravel-governor.models.team"),
            "governor_owned_by"
        );
    }

    public function governorOwnedTeams(): Collection
    {
        $teamClass = config("genealabs-laravel-governor.models.team");
        $ownableClass = config(
            "genealabs-laravel-governor.models.ownable",
            \GeneaLabs\LaravelGovernor\GovernorOwnable::class,
        );

        $teamIds = (new $ownableClass)
            ->where('ownable_type', $teamClass)
            ->where('user_id', $this->getKey())
            ->pluck('ownable_id');

        return (new $teamClass)->whereIn('id', $teamIds)->get();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(
            config('genealabs-laravel-governor.models.team'),
            "governor_team_user",
            "user_id",
            "team_id"
        );
    }

    public function getPermissionsAttribute(): Collection
    {
        $roleNames = $this->roles->pluck('name');

        return app("governor-permissions")
            ->whereIn('role_name', $roleNames);
    }

    public function getEffectivePermissionsAttribute(): Collection
    {
        $results = collect();
        $groupedPermissions = $this->permissions
            ->groupBy(function ($permission) {
                return $permission->entity_name . "|" . $permission->action_name;
            });

        foreach ($groupedPermissions as $entityAction => $permissions) {
            $permission = $permissions->first();
            $permission->role_name = null;
            $permission->team_name = null;

            if ($permissions->pluck("ownership_name")->contains("any")) {
                $permission->ownership_name = "any";
                $results = $results->push($permission);
            }

            if ($permissions->pluck("ownership_name")->contains("own")) {
                $permission->ownership_name = "own";
                $results = $results->push($permission);
            }
        }

        return $results;
    }
}
