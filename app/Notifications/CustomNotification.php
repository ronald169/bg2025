<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class CustomNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $type;
    protected string $title;
    protected string $message;
    protected ?string $actionUrl;
    protected ?string $actionText;

    public function __construct(string $type, string $title, string $message, ?string $actionUrl = null, ?string $actionText = null)
    {
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
        ];
    }
}
