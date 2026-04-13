<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Listeners;

use GeneaLabs\LaravelGovernor\Traits\GovernorOwnedByField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreatingListener
{
    use GovernorOwnedByField;

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function handle(string $event, array $models)
    {
        if (
            Str::contains($event, "Hyn\Tenancy\Models\Website")
            || Str::contains($event, "Hyn\Tenancy\Models\Hostname")
        ) {
            return;
        }

        collect($models)
            ->filter(function ($model) {
                return $model instanceof Model
                    && in_array(
                        "GeneaLabs\\LaravelGovernor\\Traits\\Governable",
                        class_uses_recursive($model)
                    );
            })
            ->filter()
            ->each(function ($model) {
                $model->getEntityFromModel(get_class($model));

                // Deprecated: governor_owned_by column is maintained for backward
                // compatibility but ownership is now tracked in governor_ownables.
                // The column will be removed in a future release.
                //
                // Check if governor_owned_by was explicitly set (before save).
                // Access the raw attributes directly to bypass the accessor.
                $attrs = $model->getAttributes();
                $explicit = $attrs['governor_owned_by'] ?? null;

                if (! $explicit && auth()->check()) {
                    $model->governor_owned_by = auth()->user()->id;
                }
            });
    }
}
