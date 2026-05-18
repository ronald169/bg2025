<?php
// database/seeders/ProgressTableSeeder.php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Progress;
use Illuminate\Database\Seeder;

class ProgressTableSeeder extends Seeder
{
    public function run(): void
    {
        $enrollments = Enrollment::all();

        foreach ($enrollments as $enrollment) {
            $lessons = Lesson::where('course_id', $enrollment->course_id)->get();
            $completedCount = 0;

            foreach ($lessons as $lesson) {
                $isCompleted = rand(0, 100) < $enrollment->progress;

                if ($isCompleted) {
                    $completedCount++;
                }

                Progress::create([
                    'user_id' => $enrollment->user_id,
                    'lesson_id' => $lesson->id,
                    'is_completed' => $isCompleted,
                    'completed_at' => $isCompleted ? now()->subDays(rand(1, 30)) : null,
                    'last_accessed' => now()->subDays(rand(0, 10)),
                ]);
            }

            // Update enrollment progress based on actual completions
            $actualProgress = $lessons->count() > 0 ? round(($completedCount / $lessons->count()) * 100) : 0;
            $enrollment->update(['progress' => $actualProgress]);
        }
    }
}
