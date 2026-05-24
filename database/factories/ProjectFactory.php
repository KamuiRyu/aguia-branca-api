<?php

namespace Database\Factories;

use App\Models\Idea;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(),
            'status' => 'PLANEJADO',
            'idea_origin_id' => Idea::factory(),
            'responsible_team' => fake()->word(),
            'budget' => fake()->randomFloat(2, 1000, 100000),
            'deadline' => fake()->date(),
            'progress_percentage' => fake()->numberBetween(0, 100),
            'expected_roi' => fake()->randomFloat(2, 5, 50),
            'description' => fake()->paragraph(),
        ];
    }
}
