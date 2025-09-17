<?php

namespace Database\Factories;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'title' => $title,
            'slug' => str()->slug($title),
            'description' => fake()->paragraphs(),
            'objectives' => fake()->sentences(4, true),
            'prerequisites' => fake()->sentences(2, true),
            'price' => fake()->randomElement([0, 9.99, 19.99, 29.99]),
            'difficulty' => fake()->randomElement(['beginner','intermediate','advanced']),
            'estimated_duration' => fake()->numberBetween(5, 40),
            'is_published' => fake()->boolean(80),
            'user_id' => User::factory()->teacher(),
            'subject_id' => Subject::factory()
        ];
    }
}
