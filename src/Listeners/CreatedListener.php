<?php namespace GeneaLabs\LaravelGovernor\Listeners;

use GeneaLabs\LaravelGovernor\GovernorCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreatedListener
{
    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function handle(string $event, array $models)
    {
        if (Str::contains($event, "Hyn\Tenancy\Models\Website")
            || Str::contains($event, "Hyn\Tenancy\Models\Hostname")
        ) {
            return;
        }

        collect($models)
            ->filter(function ($model) {
                return $model instanceof Model
                    && get_class($model) === config('genealabs-laravel-governor.models.auth');
            })
            ->each(function ($model) {
                try {
                    $model->roles()->syncWithoutDetaching('Member');
                } catch (Exception $exception) {
                    $roleClass = config("genealabs-laravel-governor.models.role");
                    (new $roleClass)->firstOrCreate([
                        'name' => 'Member',
                        'description' => 'Represents the baseline registered user. Customize permissions as best suits your site.',
                    ]);
                    $model->roles()->attach('Member');
                }

                GovernorCache::flushRoles();
            });
    }
}
