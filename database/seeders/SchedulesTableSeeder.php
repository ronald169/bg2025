<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Seeder;

class SchedulesTableSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = User::where('role', 'teacher')->get();

        foreach ($teachers as $teacher) {
            $courses = Course::where('teacher_id', $teacher->id)->get();

            foreach ($courses as $course) {
                $lessons = $course->lessons()->take(5)->get();
                $startDate = now()->startOfWeek();

                foreach ($lessons as $index => $lesson) {
                    $lesson->update([
                        'scheduled_at' => $startDate->copy()->addDays($index * 2)->setTime(14, 0, 0),
                    ]);
                }
            }
        }
    }
}
