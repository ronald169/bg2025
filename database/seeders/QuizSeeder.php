<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // creer un quiz pour certene lecon
        $lessons = Lesson::has('course')->with('course')->take(20)->get();

        foreach ($lessons as $lesson) {
            // 50% de chance d'avoir un quiz pour cette lesson
            if (rand(0, 1)) {
                $quiz = Quiz::factory()->create([
                    'lesson_id' => $lesson->id,
                    'time_limit' => fake()->boolean() ? rand(10, 30) : null,
                ]);

                // Creer entre 3 et 10 question par quiz
                Question::factory()
                    ->count(rand(3, 10))
                    ->create(['quiz_id' => $quiz->id]);

                // Creer des tentatives de quiz pour certain eleves
                $students = User::where('role', 'student')
                    ->inRandomOrder()
                    ->take(rand(5, 15))
                    ->get();

                foreach ($students as $student) {
                    $attemptsCount = rand(1, 3);

                    for ($i =1; $i <= $attemptsCount; $i++) {
                        QuizAttempt::factory()->create([
                            'user_id' => $student->id,
                            'quiz_id' => $quiz->id,
                            'score' => $i === $attemptsCount - 1 ? rand(70, 100) : rand(0, 100),
                        ]);
                    }
                }
            }
        }
    }
}
