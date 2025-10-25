<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ad>
 */
class AdFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'video_url' => $this->faker->url(),
            'duration' => $this->faker->numberBetween(15, 120),
            'points_reward' => $this->faker->numberBetween(5, 50),
            'is_active' => true,
        ];
    }
}
