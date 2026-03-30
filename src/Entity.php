<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entity extends Model
{
    protected $keyType = 'string';
    protected $primaryKey = 'name';
    protected $rules = [
        'name' => 'required|min:3|unique:governor_entities,name',
    ];
    protected $fillable = [
        'name',
        'policy_class',
    ];
    protected $table = "governor_entities";

    public $incrementing = false;

    public function displayName(): string
    {
        $aliases = config('genealabs-laravel-governor.entity-aliases', []);

        return $aliases[$this->name] ?? $this->name;
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(
            config('genealabs-laravel-governor.models.group')
        );
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(
            config('genealabs-laravel-governor.models.permission'),
            'entity_name'
        );
    }
}
