<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

trait Governable
{
    use EntityManagement;

    protected function applyPermissionToQuery(Builder $query, string $ability): Builder
    {
        $entityName = $this->getEntityFromModel(get_class($this));
        $ownerships = $this->getOwnershipsForEntity($entityName, $ability);

        return $this->filterQuery($query, $ownerships->pluck("ownership_name"));
    }

    protected function filterQuery(Builder $query, Collection $ownerships): Builder
    {
        if (
            $ownerships->contains("any")
            || auth()->user()?->hasRole("SuperAdmin")
        ) {
            return $query;
        }

        if ($ownerships->contains("own")) {
            $authModel = config("genealabs-laravel-governor.models.auth");
            $authTable = (new $authModel)->getTable();

            if (method_exists($query->getModel(), "teams")) {
                if ($query->getModel()->getTable() === $authTable) {
                    return $query
                        ->whereHas("teams", function ($query) {
                            $query->whereIn("governor_team_user.user_id", auth()->user()->teams->pluck("id"));
                        })
                        ->orWhere($query->getModel()->getKeyName(), auth()->user()->getKey());
                }

                return $query
                    ->whereHas("teams", function ($query) {
                        $query->whereIn("governor_teamables.team_id", auth()->user()->teams->pluck("id"));
                    })
                    ->orWhereHas("governorOwner", function ($ownerQuery) {
                        $ownerQuery->where("governor_ownables.user_id", auth()->user()->getKey());
                    });
            }

            if ($query->getModel()->getTable() === $authTable) {
                return $query->where(
                    $query->getModel()->getKeyName(),
                    auth()->user()->getKey(),
                );
            }

            return $query->whereHas("governorOwner", function ($ownerQuery) {
                $ownerQuery->where("governor_ownables.user_id", auth()->user()->getKey());
            });
        }

        return $query->whereRaw("1 = 2");
    }

    protected function getOwnershipsForEntity(
        string $entityName,
        string $ability,
    ): Collection {
        if (! $entityName) {
            return collect();
        }

        return app("governor-permissions")
            ->where("action_name", $ability)
            ->where("entity_name", $entityName);
    }

    public function governorOwner(): MorphOne
    {
        return $this->morphOne(
            config("genealabs-laravel-governor.models.ownable", \GeneaLabs\LaravelGovernor\GovernorOwnable::class),
            "ownable"
        );
    }

    /**
     * @deprecated Use governorOwner() instead. This method will be removed in a future version.
     *
     * Returns the owning user via the polymorphic governor_ownables table.
     * Previously returned a BelongsTo against the governor_owned_by column.
     */
    public function getOwnedByAttribute(): ?Model
    {
        $this->unsetRelation('governorOwner');
        $ownable = $this->governorOwner;

        if ($ownable) {
            return $ownable->owner;
        }

        return null;
    }

    public function getGovernorOwnedByAttribute()
    {
        // Always unset and reload to ensure fresh query, since ownership tracking
        // moved to polymorphic table and may not be pre-loaded.
        $this->unsetRelation('governorOwner');
        $ownable = $this->governorOwner;

        if ($ownable) {
            return $ownable->user_id;
        }

        // Fall back to the deprecated column value if the model has it
        return $this->attributes['governor_owned_by'] ?? null;
    }

    public function teams(): MorphToMany
    {
        return $this->MorphToMany(
            config("genealabs-laravel-governor.models.team"),
            "teamable",
            "governor_teamables"
        );
    }

    public function scopeDeletable(Builder $query): Builder
    {
        return $this->applyPermissionToQuery($query, "delete");
    }

    public function scopeForceDeletable(Builder $query): Builder
    {
        return $this->applyPermissionToQuery($query, "forceDelete");
    }

    public function scopeRestorable(Builder $query): Builder
    {
        return $this->applyPermissionToQuery($query, "restore");
    }

    public function scopeUpdatable(Builder $query): Builder
    {
        return $this->applyPermissionToQuery($query, "update");
    }

    public function scopeViewable(Builder $query): Builder
    {
        return $this->applyPermissionToQuery($query, "view");
    }

    public function scopeViewAnyable(Builder $query): Builder
    {
        return $this->applyPermissionToQuery($query, "viewAny");
    }
}
