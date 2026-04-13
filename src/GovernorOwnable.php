<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GovernorOwnable extends Model
{
    protected $table = 'governor_ownables';

    protected $fillable = [
        'ownable_type',
        'ownable_id',
        'user_id',
    ];

    public function ownable(): MorphTo
    {
        return $this->morphTo();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(
            config("genealabs-laravel-governor.models.auth"),
            "user_id"
        );
    }
}
