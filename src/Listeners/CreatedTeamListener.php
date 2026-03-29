<?php namespace GeneaLabs\LaravelGovernor\Listeners;

class CreatedTeamListener
{
    public function handle($team)
    {
        if (auth()->check()) {
            $user = auth()->user();
            $team->members()->syncWithoutDetaching([$user->id]);
            $this->seedPermissionsFromOwner($team, $user);
        }
    }

    protected function seedPermissionsFromOwner($team, $user): void
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
