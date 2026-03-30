<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Http\Controllers\Nova;

use GeneaLabs\LaravelGovernor\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamSwitcherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('teams', 'currentTeam');

        return response()->json([
            'teams' => $user->teams->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
            ]),
            'currentTeamId' => $user->current_team_id,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'team_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $teamId = (int) $request->input('team_id');

        $isMember = $user->teams()
            ->where('governor_teams.id', $teamId)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'message' => 'You are not a member of this team.',
            ], 403);
        }

        $user->switchTeam($teamId);

        return response()->json([
            'currentTeamId' => $teamId,
        ]);
    }
}
