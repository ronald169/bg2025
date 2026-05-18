<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StreakMilestoneNotification extends Notification
{
    use Queueable;

    protected $streak;

    /**
     * Create a new notification instance.
     */
    public function __construct($streak)
    {
        $this->streak = $streak;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = match($this->streak) {
            7 => 'Super! Du lernst seit einer Woche jeden Tag! Weiter so! 🔥',
            30 => 'Unglaublich! 30 Tage in Folge! Du bist ein echter Deutsch-Profi! 🎉',
            100 => '100 Tage! Du bist eine Inspiration! 🌟',
            default => "{$this->streak} Tage Lernstreak! Fantastisch! 🏆",
        };
        
        return (new MailMessage)
            ->subject("🔥 {$this->streak} Tage Lernstreak!")
            ->greeting('Hallo ' . $notifiable->name . '!')
            ->line($message)
            ->action('Weiter lernen', url('/student/dashboard'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "{$this->streak} Tage Lernstreak!",
            'message' => "Du lernst seit {$this->streak} Tagen in Folge!",
            'type' => 'streak_milestone',
        ];
    }
}
