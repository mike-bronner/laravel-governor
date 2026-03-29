<?php namespace GeneaLabs\LaravelGovernor\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;

class GovernorEntity extends Resource
{
    public static $model;
    public static $title = "name";
    public static $displayInPermissions = false;
    public static $globallySearchable = false;

    public function fields(Request $request)
    {
        return [
            Text::make("Name", "name")
                ->resolveUsing(function ($name) {
                    $aliases = config('genealabs-laravel-governor.entity-aliases', []);

                    return $aliases[$name] ?? $name;
                })
                ->sortable(),
        ];
    }

    public function title()
    {
        $aliases = config('genealabs-laravel-governor.entity-aliases', []);

        return $aliases[$this->name] ?? $this->name;
    }
}
