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
            // a placeholder poster image (640x360) — replace with storage path if uploading real files
            'poster' => $this->faker->imageUrl(640, 360, 'business', true),
            'duration' => $this->faker->numberBetween(15, 120),
            'points_reward' => $this->faker->numberBetween(5, 50),
            'is_active' => true,
        ];
    }
}
