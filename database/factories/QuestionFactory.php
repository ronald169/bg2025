<?php

namespace Database\Factories;

use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['multiple_choice', 'true_false', 'short_answer']);
        return [
            'question' => fake()->sentence(10) . '?',
            'type' => $type,
            'options' => $this->getOptionsBasedOnType($type),
            'correct_answer' => $this->getCorrectAnswerBasedOnType($type),
            'points' => fake()->numberBetween(1, 5),
            'explanation' => fake()->boolean(70) ? fake()->paragraph(2) : null,
            'quiz_id' => Quiz::factory(),
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'updated_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    private function getOptionsBasedOntype(string $type): ?string
    {
        return match($type) {
            'multiple_choice' => json_encode([
                fake()->sentence(3),
                fake()->sentence(3),
                fake()->sentence(3),
                fake()->sentence(3),
            ]),
            'true_false' => null,
            'short_answer' => null
        };
    }

    private function getCorrectAnswerBasedOnType(string $type): string
    {
        return match($type) {
            'multiple_choice' => (string) fake()->numberBetween(0, 3),
            'true_false' => fake()->randomElement(['true', 'false']),
            'short_answer' => fake()->word()
        };
    }

    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'multiple_choice',
            'options' => json_encode([
                fake()->sentence(3),
                fake()->sentence(3),
                fake()->sentence(3),
                fake()->sentence(3)
            ]),
            'correct_answer' => (string) fake()->numberBetween(0,3)
        ]);
    }

    public function trueFalse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'true_false',
            'options' => null,
            'correct_answer' => fake()->randomElement(['true', 'false'])
        ]);
    }

    public function shortAnswer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'short_answer',
            'options' => null,
            'correct_answer' => fake()->word()
        ]);
    }

    public function withHighPoints(): static
    {
        return $this->state(fn (array $attributes) => [
            'points' => fake()->numberBetween(3, 5),
        ]);
    }

    public function withExplanation(): static
    {
        return $this->state(fn (array $attributes) => [
            'explanation' => fake()->paragraph(2),
        ]);
    }
}
