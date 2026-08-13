<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SubjectsTableSeeder::class,
            UsersTableSeeder::class,
            CoursesTableSeeder::class,
            LessonsTableSeeder::class,
            QuizzesTableSeeder::class,
            EnrollmentsTableSeeder::class,
            ProgressTableSeeder::class,
            ReviewsTableSeeder::class,
            FlashcardsTableSeeder::class,
            LearningPathsTableSeeder::class,
            NotesTableSeeder::class,
            ConversationsTableSeeder::class,
            // MessagesTableSeeder::class,
            ExamLesenSeeder::class,
        ]);
    }
}
