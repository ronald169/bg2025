<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelComeNotification extends Notification
{
    use Queueable;


    public function __construct()
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }


    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Welcome to BrainGenius! 🎉'))
            ->greeting(__('Hello :name!', ['name' => $notifiable->name]))
            ->line(__('Welcome to BrainGenius, your personal learning coach!'))
            ->line(__('We\'re excited to help you succeed in your studies.'))
            ->action(__('Start Learning'), url('/student/dashboard'))
            ->line(__('Here are some things you can do:'))
            ->line('• ' . __('Browse available courses'))
            ->line('• ' . __('Complete your profile'))
            ->line('• ' . __('Set your learning goals'))
            ->line(__('If you have any questions, feel free to reply to this email.'))
            ->salutation(__('Best regards,') . "\n" . __('The BrainGenius Team'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'title' => __('Welcome to BrainGenius! 🎉'),
            'message' => __('Welcome to your new learning journey! Start by exploring available courses.'),
            'action_url' => '/student/dashboard',
            'action_text' => __('Go to Dashboard'),
            'icon' => 'o-academic-cap',
        ];
    }
}
