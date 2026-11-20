<?php

namespace Database\Factories;

use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Semester>
 */
class SemesterFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->subWeeks(fake()->numberBetween(1, 6))->startOfWeek();

        return [
            'name' => 'Semester 1 '.$start->year.'/'.($start->year + 1),
            'starts_on' => $start,
            'is_current' => false,
        ];
    }

    public function current(): static
    {
        // Only one semester is current at a time.
        return $this->state(fn () => ['is_current' => true])
            ->afterCreating(fn (Semester $semester) => $semester->makeCurrent());
    }
}
