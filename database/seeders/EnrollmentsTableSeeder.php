<?php
// database/seeders/EnrollmentsTableSeeder.php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnrollmentsTableSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::where('role', 'student')->take(10)->get();
        $coursesByLevel = [
            'A1' => Course::where('level', 'A1')->get(),
            'A2' => Course::where('level', 'A2')->get(),
            'B1' => Course::where('level', 'B1')->get(),
            'B2' => Course::where('level', 'B2')->get(),
            'C1' => Course::where('level', 'C1')->get(),
            'C2' => Course::where('level', 'C2')->get(),
        ];

        foreach ($students as $student) {
            $studentLevel = $student->german_level;

            // Enroll in courses at student's level and one level below
            $levelsToEnroll = [];
            if (isset($coursesByLevel[$studentLevel])) {
                $levelsToEnroll = array_merge($levelsToEnroll, $coursesByLevel[$studentLevel]->toArray());
            }

            // Add one level below
            $levelMap = ['A2' => 'A1', 'B1' => 'A2', 'B2' => 'B1', 'C1' => 'B2', 'C2' => 'C1'];
            if (isset($levelMap[$studentLevel]) && isset($coursesByLevel[$levelMap[$studentLevel]])) {
                $levelsToEnroll = array_merge($levelsToEnroll, $coursesByLevel[$levelMap[$studentLevel]]->toArray());
            }

            foreach ($levelsToEnroll as $course) {
                Enrollment::create([
                    'user_id' => $student->id,
                    'course_id' => $course['id'],
                    'status' => 'active',
                    'progress' => rand(0, 100),
                    'enrolled_at' => now()->subDays(rand(1, 90)),
                ]);
            }
        }
    }
}
