<?php
// database/seeders/NotesTableSeeder.php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotesTableSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::where('role', 'student')->get();
        $lessons = Lesson::all();

        $sampleNotes = [
            'Remember: Der, die, das - masculine, feminine, neuter',
            'Important: Modal verbs change the word order',
            'Practice: Conjugate regular verbs in present tense',
            'Key vocabulary for this lesson',
            'Common mistakes to avoid',
            'Useful phrases for daily conversations',
        ];

        foreach ($students as $student) {
            $noteCount = rand(3, 8);
            $randomLessons = $lessons->random($noteCount);

            foreach ($randomLessons as $lesson) {
                Note::create([
                    'user_id' => $student->id,
                    'course_id' => $lesson->course_id,
                    'title' => 'Notes for ' . $lesson->title,
                    'content' => $sampleNotes[array_rand($sampleNotes)] . "\n\nAdditional notes from the lesson...",
                ]);
            }
        }
    }
}
