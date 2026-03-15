<?php

namespace Database\Seeders;

use App\Models\Stack;
use Illuminate\Database\Seeder;

class StackSeeder extends Seeder
{
    public function run(): void
    {
        $stacks = [
            [
                'name'          => 'Laravel',
                'slug'          => 'laravel',
                'runtime'       => 'PHP 8.3',
                'description'   => 'Laravel application with PHP-FPM, Nginx, and MySQL.',
                'template_path' => 'storage/stacks/laravel',
            ],
            [
                'name'          => 'Node.js',
                'slug'          => 'node',
                'runtime'       => 'Node 20',
                'description'   => 'Node.js application with a custom server entry point.',
                'template_path' => 'storage/stacks/node',
            ],
            [
                'name'          => 'Static Site',
                'slug'          => 'static',
                'runtime'       => 'Nginx',
                'description'   => 'Static HTML/CSS/JS site served by Nginx.',
                'template_path' => 'storage/stacks/static',
            ],
            [
                'name'          => 'PHP',
                'slug'          => 'php',
                'runtime'       => 'PHP 8.3',
                'description'   => 'Pure PHP application with PHP-FPM and Nginx. No framework required.',
                'template_path' => 'storage/stacks/php',
            ],
        ];

        foreach ($stacks as $stack) {
            Stack::firstOrCreate(['slug' => $stack['slug']], $stack);
        }
    }
}
