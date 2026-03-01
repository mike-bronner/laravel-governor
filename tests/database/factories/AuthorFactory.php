<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Database\Factories;

use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}