<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = Course::get();

        foreach($courses as $course) {
            $lessonCount = rand(5, 12);

            for ($i = 1; $i <= $lessonCount; $i++) {
                Lesson::factory()->create([
                    'course_id' => $course->id,
                    'position' => $i
                ]);
            }
        }
    }
}
