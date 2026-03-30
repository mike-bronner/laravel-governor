<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Fixtures;

use GeneaLabs\LaravelGovernor\Traits\Governable;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use Governable;

    protected $fillable = [
        'title',
    ];
}
