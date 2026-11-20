<?php

namespace Database\Factories;

use App\Models\Logbook;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Logbook>
 */
class LogbookFactory extends Factory
{
    /** 1x1 transparent PNG stands in for a drawn signature. */
    public const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::factory(),
            'semester_id' => fn () => Semester::current()?->id ?? Semester::factory()->current()->create()->id,
            'week_no' => fake()->unique()->numberBetween(1, Logbook::MAX_WEEKS),
            'entry_date' => fake()->dateTimeBetween('-3 months'),
            'meeting_mode' => 'face_to_face',
            'meeting_time' => '10:00',
            'meeting_minutes' => 30,
            'meeting_place' => 'Bilik Perbincangan 2',
            'progress' => fake()->paragraph(),
            'current_status' => fake()->sentence(),
            'problem' => fake()->sentence(),
            'next_week_task' => fake()->sentence(),
            'status' => Logbook::STATUS_PENDING,
            'student_signature' => self::SIGNATURE,
            'submitted_at' => now(),
        ];
    }

    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'supervisor_comment' => fake()->sentence(),
            'status' => Logbook::STATUS_APPROVED,
            'supervisor_signature' => self::SIGNATURE,
            'reviewed_at' => now(),
        ]);
    }

    public function needsRevision(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Logbook::STATUS_REVISION,
            'supervisor_comment' => 'Please add more detail on the problems you faced.',
        ]);
    }
}
