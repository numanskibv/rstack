<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stack>
 */
class StackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'          => 'Laravel',
            'slug'          => fake()->unique()->lexify('stack-????'),
            'runtime'       => 'PHP 8.3',
            'description'   => 'Laravel stack for testing',
            'template_path' => 'storage/stacks/laravel',
        ];
    }
}
