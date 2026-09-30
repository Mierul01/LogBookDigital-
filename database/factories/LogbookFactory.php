<?php

namespace Database\Factories;

use App\Models\Logbook;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Logbook>
 */
class LogbookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::factory(),
            'week_no' => fake()->unique()->numberBetween(1, Logbook::MAX_WEEKS),
            'entry_date' => fake()->dateTimeBetween('-3 months'),
            'progress' => fake()->paragraph(),
            'current_status' => fake()->sentence(),
            'problem' => fake()->sentence(),
            'next_week_task' => fake()->sentence(),
        ];
    }

    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'supervisor_comment' => fake()->sentence(),
            // 1x1 transparent PNG stands in for a drawn signature.
            'supervisor_signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
            'reviewed_at' => now(),
        ]);
    }
}
