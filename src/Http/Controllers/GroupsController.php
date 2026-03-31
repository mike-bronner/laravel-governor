<?php

namespace GeneaLabs\LaravelGovernor\Http\Controllers;

use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\Group;
use GeneaLabs\LaravelGovernor\Http\Requests\GroupDeleteRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\StoreGroupRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GroupsController extends Controller
{
    public function index(): View
    {
        $groups = (new Group)
            ->with("entities")
            ->orderBy("name")
            ->get();

        return view("genealabs-laravel-governor::groups.index")
            ->with([
                "groups" => $groups,
            ]);
    }

    public function create(): View
    {
        $entities = (new Entity)
            ->orderBy("name")
            ->get();

        return view("genealabs-laravel-governor::groups.create")
            ->with([
                "entities" => $entities,
            ]);
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $request->process();

        return redirect()->route("genealabs.laravel-governor.groups.index");
    }

    public function edit(Group $group): View
    {
        $entities = (new Entity)
            ->orderBy("name")
            ->get();

        return view("genealabs-laravel-governor::groups.edit")
            ->with([
                "entities" => $entities,
                "group" => $group,
            ]);
    }

    public function destroy(GroupDeleteRequest $request, Group $group): RedirectResponse
    {
        $request->process();

        return redirect()->route("genealabs.laravel-governor.groups.index");
    }
}
