<?php

namespace Database\Factories;

use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(2),
            'time_limit' => fake()->randomElement([15, 20, 30, 45, 60]),
            'max_attempts' => fake()->numberBetween(1, 5),
            'lesson_id' => Lesson::factory(),
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'updated_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function withoutTimeLimit(): static
    {
        return $this->state(fn (array $attributes) => [
            'time_limit' => null,
        ]);
    }

    public function withSingleAttempt(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_attempts' => 1,
        ]);
    }

    public function withUnlimitedAttempts(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_attempts' => 999,
        ]);
    }
}
