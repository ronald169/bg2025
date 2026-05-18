<?php
// database/seeders/QuizzesTableSeeder.php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Quiz;
use Illuminate\Database\Seeder;

class QuizzesTableSeeder extends Seeder
{
    public function run(): void
    {
        $lessons = Lesson::all();

        foreach ($lessons as $lesson) {
            $quiz = Quiz::create([
                'lesson_id' => $lesson->id,
                'title' => 'Quiz: ' . $lesson->title,
                'description' => 'Test your knowledge of ' . $lesson->title,
                'time_limit' => rand(10, 30),
                'passing_score' => 70,
                'max_attempts' => 3,
                'is_published' => true,
                'order' => 1,
            ]);

            $this->createQuestions($quiz, $lesson->course->level);
        }
    }

    private function createQuestions($quiz, $level): void
    {
        $questions = $this->getQuestionsForLevel($level);

        foreach ($questions as $index => $q) {
            \App\Models\QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => $q['question'],
                'type' => $q['type'],
                'options' => $q['options'] ?? null,
                'correct_answer' => $q['correct_answer'],
                'points' => $q['points'],
                'explanation' => $q['explanation'],
                'order' => $index,
            ]);
        }
    }

    private function getQuestionsForLevel($level): array
    {
        $baseQuestions = [
            [
                'question' => 'What is the German word for "Hello"?',
                'type' => 'multiple_choice',
                'options' => ['Bonjour', 'Hallo', 'Ciao', 'Hola'],
                'correct_answer' => ['Hallo'],
                'points' => 1,
                'explanation' => '"Hallo" is the standard German greeting.'
            ],
            [
                'question' => 'The German word for "yes" is "Ja".',
                'type' => 'true_false',
                'options' => null,
                'correct_answer' => ['true'],
                'points' => 1,
                'explanation' => 'Correct! "Ja" means yes in German.'
            ],
            [
                'question' => 'How do you say "Thank you" in German?',
                'type' => 'short_answer',
                'options' => null,
                'correct_answer' => ['Danke'],
                'points' => 2,
                'explanation' => '"Danke" is the standard way to say thank you.'
            ],
            [
                'question' => 'Which of these means "Good morning"?',
                'type' => 'multiple_choice',
                'options' => ['Gute Nacht', 'Guten Abend', 'Guten Morgen', 'Guten Tag'],
                'correct_answer' => ['Guten Morgen'],
                'points' => 1,
                'explanation' => '"Guten Morgen" is used until around noon.'
            ],
            [
                'question' => '"Auf Wiedersehen" means "See you later".',
                'type' => 'true_false',
                'options' => null,
                'correct_answer' => ['false'],
                'points' => 1,
                'explanation' => '"Auf Wiedersehen" means "Goodbye". "Bis später" means "See you later".'
            ],
        ];

        $advancedQuestions = [
            [
                'question' => 'What is the correct past participle of "gehen"?',
                'type' => 'multiple_choice',
                'options' => ['geht', 'ging', 'gegangen', 'gegeht'],
                'correct_answer' => ['gegangen'],
                'points' => 2,
                'explanation' => '"gegangen" is the correct past participle of "gehen".'
            ],
            [
                'question' => 'The Konjunktiv II of "haben" is "hätte".',
                'type' => 'true_false',
                'options' => null,
                'correct_answer' => ['true'],
                'points' => 2,
                'explanation' => 'Correct! "hätte" is the Konjunktiv II form of "haben".'
            ],
        ];

        // Add level-specific questions
        if (in_array($level, ['C1', 'C2'])) {
            return array_merge($baseQuestions, $advancedQuestions);
        }

        return $baseQuestions;
    }
}
