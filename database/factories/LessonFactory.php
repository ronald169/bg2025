<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(9, true),
            'position' => 0,
            'video_url' => null,
            'duration' => fake()->randomElement([45, 60, 90, 120]),
        ];
    }

    public function is_free()
    {
        return $this->state(fn (array $attributes) => [
            'is_free' => true,
        ]);
    }
}
