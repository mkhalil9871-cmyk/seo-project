<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => fake()->company() . ' SEO Project',
            'domain' => fake()->domainName(),
            'industry' => fake()->randomElement(['E-commerce', 'SaaS', 'Local Business', 'Healthcare', 'Real Estate']),
            'country' => fake()->country(),
            'language' => 'en',
            'status' => 'active',
        ];
    }
}