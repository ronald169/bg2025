<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'type' => 'welcome_email',
                'subject' => 'Welcome to BrainGenius! 🎉',
                'body' => "Hello {{ user_name }},\n\nWelcome to BrainGenius! We're excited to have you on board.\n\nStart your learning journey today!",
                'channel' => 'mail',
                'variables' => json_encode(['user_name']),
            ],
            [
                'type' => 'course_reminder',
                'subject' => 'Reminder: Your course continues!',
                'body' => "Hi {{ user_name }},\n\nDon't forget to continue your course: {{ course_title }}\n\nKeep up the great work!",
                'channel' => 'mail',
                'variables' => json_encode(['user_name', 'course_title']),
            ],
            [
                'type' => 'quiz_results',
                'subject' => 'Your Quiz Results',
                'body' => "Hello {{ user_name }},\n\nYou scored {{ score }}/{{ total }} on {{ quiz_title }}\n\nKeep practicing!",
                'channel' => 'mail',
                'variables' => json_encode(['user_name', 'score', 'total', 'quiz_title']),
            ],
            [
                'type' => 'streak_reminder',
                'subject' => 'Keep your streak alive! 🔥',
                'body' => "Hi {{ user_name }},\n\nYou have a {{ streak_days }}-day streak! Study today to keep it going.",
                'channel' => 'mail',
                'variables' => json_encode(['user_name', 'streak_days']),
            ],
        ];

        foreach ($templates as $template) {
            DB::table('notification_templates')->insert($template);
        }
    }
}
