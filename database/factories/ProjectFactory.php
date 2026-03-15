<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'server_id'  => Server::factory(),
            'stack_id'   => Stack::factory(),
            'name'       => fake()->words(3, true),
            'slug'       => fake()->unique()->lexify('project-????'),
            'domain'     => null,
            'subdomain'  => null,
            'dns_status' => null,
            'port'       => fake()->unique()->numberBetween(8001, 9000),
            'status'     => 'ready',
            'repository' => null,
            'branch'     => 'main',
            'env_vars'   => null,
        ];
    }

    public function withSubdomain(string $subdomain, ?string $dnsStatus = 'pending'): static
    {
        return $this->state([
            'subdomain'  => $subdomain,
            'dns_status' => $dnsStatus,
        ]);
    }
}
