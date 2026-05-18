<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class MessagesTableSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = User::where('role', 'teacher')->get();
        $students = User::where('role', 'student')->get();

        foreach ($teachers as $teacher) {
            $assignedStudents = $students->random(min(3, $students->count()));

            foreach ($assignedStudents as $student) {
                $conversation = Conversation::create([
                    'teacher_id' => $teacher->id,
                    'student_id' => $student->id,
                    'last_message_at' => now(),
                ]);

                // Message du professeur
                Message::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $teacher->id,
                    'receiver_id' => $student->id,
                    'content' => "Bonjour {$student->name}, comment se passe votre apprentissage ?",
                    'is_read' => true,
                    'created_at' => now()->subDays(2),
                ]);

                // Réponse de l'étudiant
                Message::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $student->id,
                    'receiver_id' => $teacher->id,
                    'content' => "Bonjour, tout se passe bien ! J'apprécie beaucoup les cours.",
                    'is_read' => true,
                    'created_at' => now()->subDays(1),
                ]);

                $conversation->update(['last_message_at' => now()->subDays(1)]);
            }
        }
    }
}
