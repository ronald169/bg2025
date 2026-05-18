<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function view(User $user, Quiz $quiz): bool
    {
        // return $user->coursesEnrolled()
        //     ->where('course_id', $quiz->lesson->course_id)
        //     ->exists();
        return true;
    }

    public function attempt(User $user, Quiz $quiz): bool
    {
        // Verifier si la lecon est completee
        $lessonCompleted = $user->progress()
            ->where('lesson_id', $quiz->lesson_id)
            ->where('is_completed', true)
            ->exists();

        // Verifier le nombre de tentatives
        $attemptsCount = $user->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->count();

        // return $lessonCompleted && $attemptsCount < $quiz->max_attempts;
        return true;
    }
}
