<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = User::where('role', 'teacher')->get();

        //pour chaque teacher on cree entre 1 a 4 cours
        foreach ($teachers as $teacher) {
            Course::factory()->count(rand(1, 4))->create([
                'user_id' => $teacher->id,
            ]);
        }
    }
}
