<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::where('role', 'student')->get();
        $courses = Course::get();

        foreach ($students as $student) {
            // chaque eleve s'inscrit entre 1 a 5 cours
            $randomCourses = $courses->random(rand(1, min(5, $courses->count())));

            foreach($randomCourses as $course) {
                $enrollment = Enrollment::firstOrCreate(
                    [
                    'user_id' => $student->id,
                    'course_id' => $course->id
                    ],
                    ['completed_at' => now()->subDays(rand(0, 365))]
                );
            }
        }
    }
}
