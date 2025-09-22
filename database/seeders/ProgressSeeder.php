<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $enrollments = Enrollment::get();

        foreach ($enrollments as $enrollment) {

            $lessons = Lesson::where('course_id', $enrollment->course_id)->get();

            foreach ($lessons as $lesson) {

                $completed = fake()->boolean(40);

                Progress::factory()->create([
                    'user_id' => $enrollment->user_id,
                    'lesson_id' => $lesson->id,
                    'is_completed' => $completed,
                    'last_accessed' => $completed ? now()->subDays(rand(1,180)) : null
                ]);
            }
        }

    }
}
