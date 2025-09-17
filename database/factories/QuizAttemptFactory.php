<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $completed = fake()->boolean(80);
        $quiz = Quiz::factory()->create();

        return [
            'user_id' => User::factory(),
            'quiz_id' => $quiz->id,
            'score' => $completed ? fake()->numberBetween(0, 100) : 0,
            'time_spent' => fake()->numberBetween(30, $quiz->time_limit * 60),
            'completed_at' => $completed ? fake()->dateTimeBetween('-3 months', 'now'): null,
            'answers' => $this->generateAnswers($quiz),
            'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'updated_at' => fake()->dateTimeBetween('-3 months', 'now')
        ];
    }

    private function generateAnswers($quiz): string
    {
        $answers = [];
        $questions = $quiz->questions;

        foreach ($questions as $question) {
            $answers[$question->id] = $this->generateAnswerForQuestion($question);
        }

        return json_encode($answers);
    }

    private function generateAnswerForQuestion($question): string
    {
        return match($question->type) {
            'multiple_choice' => (string) fake()->numberBetween(0, 3),
            'true_false' => fake()->randomElement(['true', 'false']),
            'short_answer' => fake()->word()
        };
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'score' => fake()->numberBetween(50, 100),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => fake()->dateTimeBetween('-3 months', 'nom'),
            'score' => fake()->numberBetween(0, 49)
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => null,
            'score' => 0,
        ]);
    }

    public function withHighScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->numberBetween(80, 100),
        ]);
    }

    public function withLowScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->numberBetween(0, 40)
        ]);
    }
}
