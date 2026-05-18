<?php
// database/seeders/LearningPathsTableSeeder.php

namespace Database\Seeders;

use App\Models\LearningPath;
use App\Models\LearningStreak;
use App\Models\User;
use Illuminate\Database\Seeder;

class LearningPathsTableSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::where('role', 'student')->get();

        foreach ($students as $student) {
            $currentLevel = $student->german_level;
            $levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
            $currentIndex = array_search($currentLevel, $levels);
            $targetIndex = min(5, $currentIndex + 2);

            LearningPath::create([
                'user_id' => $student->id,
                'current_level' => $currentLevel,
                'target_level' => $levels[$targetIndex],
                'learning_goal' => $student->learning_goal,
                'overall_progress' => rand(10, 80),
                'total_points' => rand(100, 5000),
                'total_hours_studied' => rand(5, 200),
                'reading_skill' => rand(20, 90),
                'writing_skill' => rand(20, 90),
                'listening_skill' => rand(20, 90),
                'speaking_skill' => rand(20, 90),
                'grammar_skill' => rand(20, 90),
                'vocabulary_skill' => rand(20, 90),
                'started_at' => now()->subMonths(rand(1, 6)),
                'is_active' => true,
            ]);

            LearningStreak::create([
                'user_id' => $student->id,
                'current_streak' => rand(0, 30),
                'longest_streak' => rand(5, 50),
                'last_study_date' => now()->subDays(rand(0, 5)),
                'total_study_days' => rand(10, 100),
            ]);
        }
    }
}
