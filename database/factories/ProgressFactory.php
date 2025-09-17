<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Progress>
 */
class ProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $is_completed = fake()->boolean(50);

        return [
            'user_id' => User::factory(),
            'lesson_id' => Lesson::factory(),
            'time_spent' => $is_completed ? rand(45, 200) : null,
            'is_completed' => $is_completed,
            'last_accessed' => $is_completed ? fake()->dateTimeBetween('-6 months', 'now') : null,
        ];
    }
}
