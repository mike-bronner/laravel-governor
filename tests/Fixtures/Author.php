<?php namespace GeneaLabs\LaravelGovernor\Tests\Fixtures;

use GeneaLabs\LaravelGovernor\Tests\Database\Factories\AuthorFactory;
use GeneaLabs\LaravelGovernor\Traits\Governable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use Governable;
    use HasFactory;

    protected $fillable = [
        "name",
    ];

    protected static function newFactory(): AuthorFactory
    {
        return AuthorFactory::new();
    }
}