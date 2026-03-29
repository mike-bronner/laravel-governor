<?php namespace GeneaLabs\LaravelGovernor\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Text;

class GovernorPermission extends Resource
{
    public static $model;
    public static $perPageViaRelationship = 25;
    public static $displayInPermissions = false;
    public static $globallySearchable = false;
    public static $title = "entity";

    public function fields(Request $request)
    {
        return [
            Text::make("Role", "role_name", "GeneaLabs\LaravelGovernor\Nova\GovernorRole")
                ->onlyOnIndex()
                ->sortable(),
            Text::make("Action", "action_name", "GeneaLabs\LaravelGovernor\Nova\GovernorAction")
                ->resolveUsing(function ($actionName) {
                    return "can {$actionName}";
                })
                ->onlyOnIndex()
                ->sortable(),
            Text::make("Ownership", "ownership_name", "GeneaLabs\LaravelGovernor\Nova\GovernorOwnership")
                ->onlyOnIndex()
                ->sortable(),
            Text::make("Entity", "entity_name", "GeneaLabs\LaravelGovernor\Nova\GovernorEntity")
                ->resolveUsing(function ($entityName) {
                    $aliases = config('genealabs-laravel-governor.entity-aliases', []);

                    return $aliases[$entityName] ?? $entityName;
                })
                ->onlyOnIndex()
                ->sortable(),
            BelongsTo::make("Role", "role", "GeneaLabs\LaravelGovernor\Nova\GovernorRole")
                ->hideFromIndex(),
            BelongsTo::make("Action", "action", "GeneaLabs\LaravelGovernor\Nova\GovernorAction")
                ->hideFromIndex(),
            BelongsTo::make("Ownership", "ownership", "GeneaLabs\LaravelGovernor\Nova\GovernorOwnership")
                ->hideFromIndex(),
            BelongsTo::make("Entity", "entity", "GeneaLabs\LaravelGovernor\Nova\GovernorEntity")
                ->hideFromIndex(),
        ];
    }
}
