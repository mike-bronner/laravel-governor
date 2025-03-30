<?php namespace GeneaLabs\LaravelGovernor\Listeners;

class CreatedTeamListener
{
    public function handle($team)
    {
        if (auth()->check()) {
            $team->members()->syncWithoutDetaching([auth()->user()->id]);
        }
    }
}
