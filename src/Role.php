<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public static function boot(): void
    {
        parent::boot();

        static::saved(function () {
            GovernorCache::flushRoles();
        });

        static::deleted(function () {
            GovernorCache::flushRoles();
        });
    }

    protected $keyType = 'string';
    protected $primaryKey = 'name';
    protected $rules = [
        'name' => 'required|min:3|unique:governor_roles,name',
        'description' => 'required|min:25',
    ];
    protected $fillable = [
        'name',
        'description',
    ];
    protected $table = "governor_roles";

    public $incrementing = false;

    public function entities(): HasMany
    {
        return $this->hasMany(
            config('genealabs-laravel-governor.models.entity'),
            "entity_name"
        );
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(
            config('genealabs-laravel-governor.models.permission'),
            'role_name'
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('genealabs-laravel-governor.models.auth'),
            'governor_role_user',
            'role_name',
            'user_id'
        );
    }
}
