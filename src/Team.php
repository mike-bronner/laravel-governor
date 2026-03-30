<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor;

use GeneaLabs\LaravelGovernor\Traits\Governable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Team extends Model
{
    use Governable;

    protected $rules = [
        'name' => 'required|min:3',
        'description' => 'string',
    ];
    protected $fillable = [
        'name',
        'description',
    ];
    protected $table = "governor_teams";

    public function invitations(): HasMany
    {
        return $this->hasMany(
            config('genealabs-laravel-governor.models.invitation'),
            "team_id"
        );
    }

    public function owner(): BelongsTo
    {
        $authClass = config("genealabs-laravel-governor.models.auth");

        return $this->belongsTo($authClass, "governor_owned_by", "id");
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            config('genealabs-laravel-governor.models.auth'),
            "governor_team_user",
            "team_id",
            "user_id"
        );
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(
            config('genealabs-laravel-governor.models.permission')
        );
    }

    public function getOwnerNameAttribute(): string
    {
        return $this->owner->name
            ?? "";
    }

    public function transferOwnership(Model $newOwner): self
    {
        $this->loadMissing('members');

        $this->governor_owned_by = $newOwner->getKey();
        $this->save();

        return $this;
    }
}
