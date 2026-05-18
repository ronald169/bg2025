<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCoursesSeeder extends Seeder
{
    public function run(): void
    {
        // Créer les matières
        $subjects = [
            ['name' => 'Mathematics', 'slug' => 'mathematics', 'icon' => 'calculator', 'color' => 'blue'],
            ['name' => 'Physics', 'slug' => 'physics', 'icon' => 'beaker', 'color' => 'purple'],
            ['name' => 'Chemistry', 'slug' => 'chemistry', 'icon' => 'flask', 'color' => 'green'],
            ['name' => 'Biology', 'slug' => 'biology', 'icon' => 'leaf', 'color' => 'emerald'],
            ['name' => 'French', 'slug' => 'french', 'icon' => 'book-open', 'color' => 'red'],
            ['name' => 'History', 'slug' => 'history', 'icon' => 'clock', 'color' => 'amber'],
            ['name' => 'Geography', 'slug' => 'geography', 'icon' => 'map', 'color' => 'teal'],
            ['name' => 'English', 'slug' => 'english', 'icon' => 'globe-alt', 'color' => 'indigo'],
            ['name' => 'Philosophy', 'slug' => 'philosophy', 'icon' => 'academic-cap', 'color' => 'rose'],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }

        // Créer quelques professeurs (si pas déjà existants)
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@braingenius.com'],
            [
                'name' => 'Professor Smith',
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ]
        );

        // Créer des cours de démo
        $courses = [
            [
                'title' => 'Mathematics - Algebra Fundamentals',
                'description' => 'Master the basics of algebra with interactive lessons and practice exercises.',
                'level' => 'college',
                'difficulty' => 'beginner',
                'estimated_duration' => 480,
                'price' => 0,
                'is_featured' => true,
            ],
            [
                'title' => 'Physics: Mechanics and Motion',
                'description' => 'Understand the laws of motion, forces, and energy through real-world examples.',
                'level' => 'lycee',
                'difficulty' => 'intermediate',
                'estimated_duration' => 600,
                'price' => 49.99,
                'is_featured' => true,
            ],
            [
                'title' => 'French Literature - Advanced Analysis',
                'description' => 'Deep dive into French literary masterpieces and learn to write compelling analyses.',
                'level' => 'terminale',
                'difficulty' => 'advanced',
                'estimated_duration' => 720,
                'price' => 79.99,
                'sale_price' => 59.99,
            ],
            [
                'title' => 'History of the 20th Century',
                'description' => 'Explore major events that shaped our modern world.',
                'level' => 'lycee',
                'difficulty' => 'intermediate',
                'estimated_duration' => 540,
                'price' => 39.99,
            ],
            [
                'title' => 'Biology: Cell Structure and Function',
                'description' => 'Learn about the building blocks of life in this comprehensive biology course.',
                'level' => 'college',
                'difficulty' => 'beginner',
                'estimated_duration' => 360,
                'price' => 0,
            ],
            [
                'title' => 'English Grammar Mastery',
                'description' => 'Perfect your English grammar with clear explanations and interactive exercises.',
                'level' => 'college',
                'difficulty' => 'beginner',
                'estimated_duration' => 420,
                'price' => 29.99,
            ],
        ];

        foreach ($courses as $index => $courseData) {
            $course = Course::create(array_merge($courseData, [
                'slug' => Str::slug($courseData['title']),
                'short_description' => Str::limit($courseData['description'], 100),
                'subject_id' => Subject::inRandomOrder()->first()->id,
                'teacher_id' => $teacher->id,
                'is_published' => true,
                'requirements' => ['Basic math skills', 'Curiosity to learn'],
                'what_you_will_learn' => ['Understand core concepts', 'Solve practical problems', 'Apply knowledge to real situations'],
            ]));

            // Créer quelques leçons pour chaque cours
            for ($i = 1; $i <= 10; $i++) {
                Lesson::create([
                    'course_id' => $course->id,
                    'title' => "Lesson {$i}: Introduction to Topic {$i}",
                    'slug' => "lesson-{$i}-introduction",
                    'description' => "In this lesson, we'll explore the fundamental concepts of Topic {$i}.",
                    'content' => "<h2>Welcome to Lesson {$i}</h2><p>This is a sample lesson content. In the full version, this would contain detailed explanations, examples, and interactive elements.</p>",
                    'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                    'video_type' => 'youtube',
                    'duration' => rand(300, 1200),
                    'order' => $i,
                    'is_free' => $i <= 2,
                    'is_published' => true,
                ]);
            }
        }
    }
}
