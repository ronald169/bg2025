<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $course,
        public $amount
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Payment Confirmation - :course', ['course' => $this->course->title]))
            ->greeting(__('Hello :name!', ['name' => $notifiable->name]))
            ->line(__('Thank you for your purchase! You are now enrolled in :course.', ['course' => $this->course->title]))
            ->line(__('Amount paid: $:amount', ['amount' => number_format($this->amount, 2)]))
            ->action(__('Start Learning'), url('/student/course/' . $this->course->slug))
            ->line(__('Happy learning!'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('Payment Confirmation'),
            'message' => __('You have successfully enrolled in :course', ['course' => $this->course->title]),
            'type' => 'success',
            'amount' => $this->amount,
            'course_id' => $this->course->id,
            'action_url' => '/student/course/' . $this->course->slug,
        ];
    }
}
