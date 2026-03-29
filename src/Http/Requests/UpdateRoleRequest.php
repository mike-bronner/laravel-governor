<?php

namespace GeneaLabs\LaravelGovernor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest as Request;

class UpdateRoleRequest extends Request
{
    public function authorize(): bool
    {
        $roleClass = config("genealabs-laravel-governor.models.role");

        if (is_string($this->role)) {
            $this->role = (new $roleClass)
                ->where("name", $this->role)
                ->first();
        }

        return auth()->check()
            && ($this->role
                ? auth()->user()->can("update", $this->role)
                : auth()->user()->can("create", $roleClass));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            "description" => "string|nullable",
            "permissions" => "array",
            "permissions.*.*.create" => "in:no,any",
        ];
    }

    public function process(): void
    {
        $roleClass = config("genealabs-laravel-governor.models.role");

        if (is_string($this->role)) {
            $this->role = (new $roleClass)
                ->where("name", $this->role)
                ->first();
        }

        $permissionClass = config("genealabs-laravel-governor.models.permission");
        $this->role->fill($this->all());

        if ($this->filled('permissions')) {
            $this->role->permissions()->delete();

            foreach ($this->permissions as $group) {
                foreach ($group as $entity => $actions) {
                    foreach ($actions as $action => $ownership) {
                        if ($action === 'create'
                            && ! in_array($ownership, ['no', 'any'], true)
                        ) {
                            $ownership = 'no';
                        }

                        if ('no' !== $ownership) {
                            (new $permissionClass)
                                ->updateOrCreate([
                                    "action_name" => $action,
                                    "entity_name" => urldecode($entity),
                                    "role_name" => $this->role->name,
                                ], [
                                    "ownership_name" => $ownership,
                                ]);
                        }
                    }
                }
            }
        }

        $this->role->save();
    }
}
