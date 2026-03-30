<?php

namespace GeneaLabs\LaravelGovernor\Http\Controllers;

use GeneaLabs\LaravelGovernor\Http\Requests\TeamStoreRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\TransferTeamOwnershipRequest;
use GeneaLabs\LaravelGovernor\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TeamsController extends Controller
{
    public function index(): View
    {
        $teams = (new Team)
            ->orderBy("name")
            ->get();

        return view("genealabs-laravel-governor::teams.index")
            ->with([
                "teams" => $teams,
            ]);
    }

    public function create(): View
    {
        $entityClass = config("genealabs-laravel-governor.models.entity");
        $ownershipClass = config("genealabs-laravel-governor.models.ownership");
        $teamClass = config("genealabs-laravel-governor.models.team");

        $entities = (new $entityClass)
            ->whereNotIn('name', ['Permission (Laravel Governor)', 'Entity (Laravel Governor)', "Action (Laravel Governor)", "Ownership (Laravel Governor)", "Team Invitation (Laravel Governor)"])
            ->orderBy("group_name")
            ->orderBy("name")
            ->get();
        $ownerships = (new $ownershipClass)
            ->all()
            ->pluck('name', 'name');
        $ownerships = collect(["not" => ""])->merge($ownerships);
        $team = new $teamClass;
        $permissionMatrix = $this->createPermissionMatrix($team, $entities);

        return view("genealabs-laravel-governor::teams.create")
            ->with([
                "entities" => $entities,
                "ownerships" => $ownerships,
                "permissionMatrix" => $permissionMatrix,
            ]);
    }

    public function store(TeamStoreRequest $request): RedirectResponse
    {
        $request->process();

        return redirect()->route('genealabs.laravel-governor.teams.index');
    }

    public function edit(Team $team): View
    {
        $entityClass = config("genealabs-laravel-governor.models.entity");
        $ownershipClass = config("genealabs-laravel-governor.models.ownership");

        $entities = (new $entityClass)
            ->whereNotIn('name', ['Permission (Laravel Governor)', 'Entity (Laravel Governor)', "Action (Laravel Governor)", "Ownership (Laravel Governor)", "Team Invitation (Laravel Governor)"])
            ->orderBy("group_name")
            ->orderBy("name")
            ->get();
        $ownerships = (new $ownershipClass)
            ->all()
            ->pluck('name', 'name');
        $ownerships = collect(["not" => ""])->merge($ownerships);
        $permissionMatrix = $this->createPermissionMatrix($team, $entities);

        return view("genealabs-laravel-governor::teams.edit")
            ->with([
                "entities" => $entities,
                "ownerships" => $ownerships,
                "permissionMatrix" => $permissionMatrix,
                "team" => $team,
            ]);
    }

    public function transferOwnership(TransferTeamOwnershipRequest $request, Team $team): RedirectResponse
    {
        $request->process();

        return redirect()->route('genealabs.laravel-governor.teams.index');
    }

    protected function createPermissionMatrix(Team $team, Collection $entities): array
    {
        $permissionMatrix = [];

        $actionClass = app(config('genealabs-laravel-governor.models.action'));
        $actions = (new $actionClass)
            ->orderBy("name")
            ->get();

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                $selectedOwnership = 'no';

                foreach ($team->permissions as $permissioncheck) {
                    if (
                        $permissioncheck->entity->name === $entity->name
                        && $permissioncheck->action->name === $action->name
                    ) {
                        $selectedOwnership = $permissioncheck->ownership->name;
                    }
                }

                $groupName = ucwords($entity->group_name)
                    ?: "Ungrouped";
                $permissionMatrix[$groupName][$entity->name][$action->name] = $selectedOwnership;
            }
        }

        return $permissionMatrix;
    }
}
