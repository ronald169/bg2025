<?php
// database/seeders/CoursesTableSeeder.php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CoursesTableSeeder extends Seeder
{
    public function run(): void
    {
        $teacherIds = User::where('role', 'teacher')->pluck('id')->toArray();
        $subjectIds = range(1, 45);

        $courses = [
            // A1 Courses
            ['title' => 'German for Beginners A1', 'level' => 'A1', 'description' => 'Start your German journey from scratch. Learn basic greetings, introductions, and everyday vocabulary.', 'short_description' => 'Complete beginner course to start speaking German'],
            ['title' => 'A1 Grammar Made Easy', 'level' => 'A1', 'description' => 'Master German grammar fundamentals: articles, pronouns, present tense, and basic sentence structure.', 'short_description' => 'Master A1 grammar with simple explanations'],

            // A2 Courses
            ['title' => 'Everyday German A2', 'level' => 'A2', 'description' => 'Learn to handle everyday situations: shopping, travel, dining out, and simple conversations.', 'short_description' => 'Handle daily conversations with confidence'],
            ['title' => 'A2 Grammar & Vocabulary', 'level' => 'A2', 'description' => 'Expand your grammar with past tense, modal verbs, and build essential vocabulary.', 'short_description' => 'Build your vocabulary and master past tense'],

            // B1 Courses
            ['title' => 'Intermediate German B1', 'level' => 'B1', 'description' => 'Express yourself on familiar topics, describe experiences, and understand main points of discussions.', 'short_description' => 'Express yourself confidently on familiar topics'],
            ['title' => 'B1 Exam Preparation', 'level' => 'B1', 'description' => 'Prepare for Goethe-Zertifikat B1 with targeted exercises and mock tests.', 'short_description' => 'Pass your Goethe B1 certification'],

            // B2 Courses
            ['title' => 'Advanced German B2', 'level' => 'B2', 'description' => 'Understand complex texts, discuss abstract topics, and write detailed essays.', 'short_description' => 'Master complex texts and abstract discussions'],
            ['title' => 'B2 Business German', 'level' => 'B2', 'description' => 'Learn professional German for meetings, presentations, and business correspondence.', 'short_description' => 'Succeed in German-speaking work environments'],

            // C1 Courses
            ['title' => 'Proficient German C1', 'level' => 'C1', 'description' => 'Express yourself fluently and spontaneously, understand implicit meaning, and use language flexibly.', 'short_description' => 'Achieve near-native fluency and expression'],
            ['title' => 'C1 Academic German', 'level' => 'C1', 'description' => 'Prepare for university studies in German: academic writing, presentations, and research.', 'short_description' => 'Prepare for German university studies'],

            // C2 Courses
            ['title' => 'Mastery German C2', 'level' => 'C2', 'description' => 'Master the German language with near-native proficiency, nuance, and style.', 'short_description' => 'Achieve near-native mastery of German'],
            ['title' => 'C2 Literary German', 'level' => 'C2', 'description' => 'Explore German literature, analyze complex texts, and refine your writing style.', 'short_description' => 'Explore German literature and refine your style'],
        ];

        foreach ($courses as $index => $courseData) {
            Course::create([
                'title' => $courseData['title'],
                'slug' => \Illuminate\Support\Str::slug($courseData['title']),
                'description' => $courseData['description'],
                'short_description' => $courseData['short_description'],
                'subject_id' => $subjectIds[array_rand($subjectIds)],
                'teacher_id' => $teacherIds[array_rand($teacherIds)],
                'level' => $courseData['level'],
                'estimated_duration' => rand(300, 1200),
                'price' => 0,
                'is_free' => true,
                'is_published' => true,
                'requirements' => ['Basic interest in German language', 'Motivation to learn'],
                'what_you_will_learn' => ['Speak German confidently', 'Understand native speakers', 'Write correctly', 'Pass exams'],
            ]);
        }
    }
}
