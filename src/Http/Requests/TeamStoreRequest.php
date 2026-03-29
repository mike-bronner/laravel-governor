<?php

namespace GeneaLabs\LaravelGovernor\Http\Requests;

use GeneaLabs\LaravelGovernor\Team;
use Illuminate\Foundation\Http\FormRequest as Request;

class TeamStoreRequest extends Request
{
    public function authorize(): bool
    {
        $teamClass = config("genealabs-laravel-governor.models.team");

        return auth()->check()
            && ($this->team
                ? auth()->user()->can("update", $this->team)
                : auth()->user()->can("create", $teamClass));
    }

    public function rules(): array
    {
        return [
            "description" => "string|nullable",
            "name" => "required|string",
            "permissions" => "array",
            "permissions.*.*.create" => "in:no,any",
        ];
    }

    public function process(): Team
    {
        $permissionClass = config("genealabs-laravel-governor.models.permission");
        $teamClass = config("genealabs-laravel-governor.models.team");
        $team = $this->team
            ?? new $teamClass;
        $team->fill($this->all());

        if ($this->filled('permissions')) {
            $team->permissions()->delete();

            foreach ($this->permissions as $group) {
                foreach ($group as $entity => $actions) {
                    foreach ($actions as $action => $ownership) {
                        if ($action === 'create'
                            && ! in_array($ownership, ['no', 'any'], true)
                        ) {
                            $ownership = 'no';
                        }

                        if ($ownership) {
                            (new $permissionClass)
                                ->updateOrCreate([
                                    "action_name" => $action,
                                    "entity_name" => urldecode($entity),
                                    "team_id" => $team->getKey(),
                                ], [
                                    "ownership_name" => $ownership,
                                ]);
                        }
                    }
                }
            }
        }

        $team->save();

        return $team;
    }
}
