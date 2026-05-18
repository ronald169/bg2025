<?php
// database/seeders/ConversationsTableSeeder.php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConversationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::where('role', 'student')->take(10)->get();
        $teachers = User::where('role', 'teacher')->get();

        $messages = [
            ['student' => 'I have a question about the homework.', 'teacher' => 'Of course! What would you like to know?'],
            ['student' => 'When is the next quiz?', 'teacher' => 'The quiz will be next Friday.'],
            ['student' => 'Can you recommend additional resources?', 'teacher' => 'Yes, check out the extra materials in the course.'],
            ['student' => 'Thank you for your help!', 'teacher' => "You're welcome! Keep up the good work!"],
            ['student' => 'I\'m struggling with grammar.', 'teacher' => 'Let me schedule a tutoring session for you.'],
        ];

        foreach ($students as $student) {
            $teacher = $teachers->random();

            $conversation = Conversation::create([
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
            ]);

            // Add 2-3 messages
            $numMessages = rand(2, 4);
            for ($i = 0; $i < $numMessages; $i++) {
                $msg = $messages[array_rand($messages)];
                $isStudent = $i % 2 == 0;

                Message::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $isStudent ? $student->id : $teacher->id,
                    'receiver_id' => $isStudent ? $teacher->id : $student->id,
                    'content' => $isStudent ? $msg['student'] : $msg['teacher'],
                    'is_read' => true,
                    'created_at' => now()->subHours(rand(1, 48)),
                ]);
            }
        }
    }
}
