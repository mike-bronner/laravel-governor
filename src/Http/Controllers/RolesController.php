<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Http\Controllers;

use GeneaLabs\LaravelGovernor\Action;
use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\Http\Requests\CreateRoleRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\UpdateRoleRequest;
use GeneaLabs\LaravelGovernor\Role;
use GeneaLabs\LaravelGovernor\Traits\EntityManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RolesController extends Controller
{
    use EntityManagement;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $roleClass = config("genealabs-laravel-governor.models.role");
        $this->authorize('viewAny', $roleClass);
        $roles = (new $roleClass)
            ->orderBy("name")
            ->get();

        return view('genealabs-laravel-governor::roles.index', compact('roles'));
    }

    public function create(): View
    {
        $entityClass = config("genealabs-laravel-governor.models.entity");
        $ownershipClass = config("genealabs-laravel-governor.models.ownership");
        $roleClass = config("genealabs-laravel-governor.models.role");
        $entities = (new $entityClass)
            ->whereNotIn('name', ['Permission (Laravel Governor)', 'Entity (Laravel Governor)', "Ability (Laravel Governor)", "Owned Resource (Laravel Governor)", "Team Invitation (Laravel Governor)"])
            ->orderBy("group_name")
            ->orderBy("name")
            ->get();
        $ownerships = (new $ownershipClass)
            ->all()
            ->pluck('name', 'name');
        $ownerships = collect(["not" => ""])->merge($ownerships);
        $role = new $roleClass;
        $this->authorize('create', $role);
        $customActions = (new Action)
            ->distinct()
            ->get()
            ->filter(function (Action $action): bool {
                return $action->model_class !== "";
            });
        $permissionMatrix = $this->createPermissionMatrix($role, $entities, $customActions);

        return view('genealabs-laravel-governor::roles.create')
            ->with([
                "customActions" => $customActions,
                "entities" => $entities,
                "ownerships" => $ownerships,
                "permissionMatrix" => $permissionMatrix,
            ]);
    }

    public function store(CreateRoleRequest $request): RedirectResponse
    {
        $roleClass = config("genealabs-laravel-governor.models.role");
        (new $roleClass)->create($request->except(['_token']));

        return redirect()->route('genealabs.laravel-governor.roles.index');
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);
        $entityClass = config("genealabs-laravel-governor.models.entity");
        $ownershipClass = config("genealabs-laravel-governor.models.ownership");
        $roleClass = config("genealabs-laravel-governor.models.role");
        $teamClass = config("genealabs-laravel-governor.models.team");

        $ownerships = (new $ownershipClass)
            ->all()
            ->pluck('name', 'name');
        $permissibleClass = request("filter") === "team_id"
            ? $teamClass
            : $roleClass;
        $role->load("permissions");

        if (request("owner") === "yes") {
            return $role
                ->ownedBy
                ->effectivePermissions
                ->toArray();
        }

        $this->parsePolicies();

        $entities = (new $entityClass)
            ->whereNotIn(
                "name",
                [
                    "Ability (Laravel Governor)",
                    "Entity (Laravel Governor)",
                    "Owned Resource (Laravel Governor)",
                    "Permission (Laravel Governor)",
                    "Team Invitation (Laravel Governor)",
                ],
            )
            ->orderBy("group_name")
            ->orderBy("name")
            ->get();
        $customActions = (new Action)
            ->distinct()
            ->get()
            ->filter(function (Action $action): bool {
                return $action->model_class !== "";
            });
        $permissionMatrix = $this->createPermissionMatrix($role, $entities, $customActions);
        $ownerships = collect(["not" => "no"])->merge($ownerships);

        return view('genealabs-laravel-governor::roles.edit', compact(
            'customActions',
            'entities',
            'role',
            'permissionMatrix',
            'ownerships'
        ));
    }

    public function update(UpdateRoleRequest $request): RedirectResponse
    {
        $request->process();

        return redirect()->route('genealabs.laravel-governor.roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);
        $role->delete();

        return redirect()->route('genealabs.laravel-governor.roles.index');
    }

    protected function createPermissionMatrix(Role $role, Collection $entities, Collection $customActions): array
    {
        $permissionMatrix = [];
        $actionClass = app(config('genealabs-laravel-governor.models.action'));
        $actions = (new $actionClass)
            ->orderBy("name")
            ->get();
        $entities->each(function (Entity $entity) use ($actions, $customActions, &$permissionMatrix, $role) {
            $permissions = $role->permissions
                ->where("entity_name", $entity->name);
            $actions
                ->filter(function (Action $action) use ($entity): bool {
                    $entityName = Str::replace(" ", "", $entity->name);

                    return Str::contains($action->name, "\\{$entityName}:")
                        || ! Str::contains($action->name, ":");
                })
                ->each(function (Action $action) use ($entity, $permissions, &$permissionMatrix) {
                    $selectedOwnership = $permissions
                        ->where("action_name", $action->name)
                        ->first()
                        ?->ownership_name
                        ?? "no";
                    $groupName = ucwords(
                        $entity->group_name
                            ?? "Ungrouped"
                    );

                    $permissionMatrix[$groupName][$entity->name][$action->name] = $selectedOwnership;
                });
        });

        return $permissionMatrix;
    }
}
