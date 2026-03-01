<?php namespace GeneaLabs\LaravelGovernor\Tests\Fixtures;

use GeneaLabs\LaravelGovernor\Tests\Database\Factories\UserFactory;
use GeneaLabs\LaravelGovernor\Traits\Governing;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use Governing;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'email',
        'name',
        'password',
    ];
    protected $hidden = [
        "password",
        "remember_token",
        "activation_token",
    ];

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}