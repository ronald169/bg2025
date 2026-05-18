<?php
// database/seeders/UsersTableSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // 1 Administrator
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@allemandexpress.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
            'german_level' => 'C2',
            'learning_goal' => 'certification',
            'email_notifications' => true,
        ]);

        // 3 Teachers
        $teachers = [
            ['name' => 'Anna Schmidt', 'email' => 'anna.schmidt@allemandexpress.com', 'german_level' => 'C2', 'bio' => 'Native German speaker with 10 years of teaching experience.'],
            ['name' => 'Michael Weber', 'email' => 'michael.weber@allemandexpress.com', 'german_level' => 'C2', 'bio' => 'Certified Goethe instructor specializing in exam preparation.'],
            ['name' => 'Clara Hoffmann', 'email' => 'clara.hoffmann@allemandexpress.com', 'german_level' => 'C2', 'bio' => 'Expert in German grammar and conversation.'],
        ];

        foreach ($teachers as $teacher) {
            User::create([
                'name' => $teacher['name'],
                'email' => $teacher['email'],
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'status' => 'active',
                'german_level' => $teacher['german_level'],
                'learning_goal' => 'certification',
                'bio' => $teacher['bio'],
                'email_notifications' => true,
            ]);
        }

        // 30 Students with different levels
        $students = [];
        $names = [
            'Lucas', 'Emma', 'Louis', 'Chloé', 'Jules', 'Inès', 'Hugo', 'Léa', 'Arthur', 'Camille',
            'Paul', 'Sarah', 'Nathan', 'Manon', 'Raphaël', 'Zoé', 'Gabriel', 'Lina', 'Maxime', 'Juliette',
            'Adam', 'Alice', 'Tom', 'Rose', 'Léo', 'Anna', 'Théo', 'Mia', 'Ethan', 'Eva'
        ];

        $levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $goals = ['certification', 'conversation', 'travel', 'business'];

        for ($i = 0; $i < 30; $i++) {
            $level = $levels[array_rand($levels)];
            User::create([
                'name' => $names[$i] . ' Dupont',
                'email' => strtolower($names[$i]) . '.dupont@example.com',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'status' => 'active',
                'german_level' => $level,
                'learning_goal' => $goals[array_rand($goals)],
                'bio' => 'Student passionate about learning German.',
                'motivation' => 'I want to master German for ' . ($level === 'C2' ? 'professional purposes' : 'travel and conversation'),
                'study_reminders' => rand(0, 1),
                'email_notifications' => true,
            ]);
        }
    }
}
