<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Listeners;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class CreatedTeamListener
{
    public function handle(Model $team): void
    {
        if (auth()->check()) {
            $user = auth()->user();
            $team->members()->syncWithoutDetaching([$user->id]);
            $this->seedPermissionsFromOwner($team, $user);
        }
    }

    protected function seedPermissionsFromOwner(Model $team, Authenticatable $user): void
    {
        $permissionClass = config('genealabs-laravel-governor.models.permission');
        $roleClass = config('genealabs-laravel-governor.models.role');

        $roleNames = $user->roles()->pluck('name');
        $ownerPermissions = (new $permissionClass)
            ->whereIn('role_name', $roleNames)
            ->get();

        $seenKeys = [];

        foreach ($ownerPermissions as $permission) {
            $key = $permission->entity_name . '|' . $permission->action_name . '|' . $permission->ownership_name;

            if (isset($seenKeys[$key])) {
                continue;
            }

            $seenKeys[$key] = true;

            (new $permissionClass)->create([
                'entity_name' => $permission->entity_name,
                'action_name' => $permission->action_name,
                'ownership_name' => $permission->ownership_name,
                'team_id' => $team->getKey(),
            ]);
        }
    }
}
