<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Listeners;

use GeneaLabs\LaravelGovernor\GovernorOwnable;
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
                return $model instanceof Model;
            })
            ->each(function ($model) {
                $this->assignDefaultRole($model);
                $this->createOwnershipRecord($model);
            });
    }

    protected function assignDefaultRole(Model $model): void
    {
        if (get_class($model) !== config('genealabs-laravel-governor.models.auth')) {
            return;
        }

        try {
            $model->roles()->syncWithoutDetaching('Member');
        } catch (\Exception $exception) {
            $roleClass = config("genealabs-laravel-governor.models.role");
            (new $roleClass)->firstOrCreate([
                'name' => 'Member',
                'description' => 'Represents the baseline registered user. Customize permissions as best suits your site.',
            ]);
            $model->roles()->attach('Member');
        }
    }

    protected function createOwnershipRecord(Model $model): void
    {
        if (! in_array(
            'GeneaLabs\LaravelGovernor\Traits\Governable',
            class_uses_recursive($model),
        )) {
            return;
        }

        $ownerId = $model->_governor_pending_owner_id ?? null;
        unset($model->_governor_pending_owner_id);

        // Use the column value if explicitly set (deprecated but maintained for backward compat)
        if (! $ownerId) {
            // Check raw attributes directly, don't use accessor
            $attrs = $model->getAttributes();
            if (isset($attrs['governor_owned_by'])) {
                $ownerId = $attrs['governor_owned_by'];
            }
        }

        if (! $ownerId && auth()->check()) {
            $ownerId = auth()->user()->id;
        }

        if (! $ownerId) {
            return;
        }

        $ownableClass = config(
            "genealabs-laravel-governor.models.ownable",
            GovernorOwnable::class,
        );

        (new $ownableClass)->firstOrCreate(
            [
                'ownable_type' => get_class($model),
                'ownable_id' => $model->getKey(),
            ],
            [
                'user_id' => $ownerId,
            ],
        );
    }
}
