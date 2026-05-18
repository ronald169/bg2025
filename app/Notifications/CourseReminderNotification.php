<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseReminderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Course $course,
        public string $reminderType = 'lesson' // lesson, quiz, assignment
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // return ['mail'];
        // Vérifier les préférences utilisateur
        return $notifiable->getNotificationChannels('course_reminder');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = match($this->reminderType) {
            'lesson' => __('Lesson Reminder: :course', ['course' => $this->course->title]),
            'quiz' => __('Quiz Reminder: :course', ['course' => $this->course->title]),
            'assignment' => __('Assignment Due Soon: :course', ['course' => $this->course->title]),
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting(__('Hi :name!', ['name' => $notifiable->name]))
            ->line(match($this->reminderType) {
                'lesson' => __('You have an upcoming lesson in :course', ['course' => $this->course->title]),
                'quiz' => __('There\'s a quiz scheduled for :course', ['course' => $this->course->title]),
                'assignment' => __('An assignment is due soon for :course', ['course' => $this->course->title]),
            })
            ->action(__('Go to Course'), route('course.show', $this->course))
            ->line(__('Stay on track with your learning goals!'))
            ->salutation(__('Happy Learning,') . "\n" . __('BrainGenius'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'course_reminder',
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
            'reminder_type' => $this->reminderType,
            'title' => match($this->reminderType) {
                'lesson' => __('Lesson Reminder: :course', ['course' => $this->course->title]),
                'quiz' => __('Quiz Reminder: :course', ['course' => $this->course->title]),
                'assignment' => __('Assignment Due: :course', ['course' => $this->course->title]),
            },
            'message' => match($this->reminderType) {
                'lesson' => __('You have an upcoming lesson. Don\'t forget to study!'),
                'quiz' => __('A quiz is scheduled. Make sure you\'re prepared!'),
                'assignment' => __('An assignment deadline is approaching.'),
            },
            'action_url' => route('course.show', $this->course),
            'action_text' => __('View Course'),
            'icon' => match($this->reminderType) {
                'lesson' => 'o-video-camera',
                'quiz' => 'o-question-mark-circle',
                'assignment' => 'o-document-text',
            },
        ];
    }
}
