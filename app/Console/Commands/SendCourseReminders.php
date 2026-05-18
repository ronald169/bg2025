<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Console\Command;
use App\Notifications\CourseReminderNotification;

class SendCourseReminders extends Command
{
    protected $signature = 'notifications:send-course-reminders';
    protected $description = 'Send course reminders to enrolled students';

    public function handle(): void
    {
        // Récupérer les étudiants avec des cours actifs
        $enrollments = Enrollment::with(['user', 'course'])
            ->where('is_active', true)
            ->whereHas('course', function ($query) {
                $query->whereHas('lessons', function ($q) {
                    $q->where('scheduled_at', '>=', now())
                       ->where('scheduled_at', '<=', now()->addDay());
                });
            })
            ->get();

        foreach ($enrollments as $enrollment) {
            $enrollment->user->notify(
                new CourseReminderNotification($enrollment->course, 'lesson')
            );
        }

        $this->info("Sent {$enrollments->count()} course reminders.");
    }
}
