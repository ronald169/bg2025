<?php

namespace App\Notifications;

use App\Models\Quiz;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuizResultNotification extends Notification
{
    use Queueable;


    public function __construct(
        public Quiz $quiz,
        public int $score,
        public int $total
    ) {}


    public function via(object $notifiable): array
    {
        // return ['mail'];
        return $notifiable->getNotificationChannels('quiz_results');
    }


    public function toMail(object $notifiable): MailMessage
    {
        $percentage = round(($this->score / $this->total) * 100);

        return (new MailMessage)
            ->subject(__('Quiz Results: :quiz', ['quiz' => $this->quiz->title]))
            ->greeting(__('Great job, :name!', ['name' => $notifiable->name]))
            ->line(__('You completed the quiz: **:quiz**', ['quiz' => $this->quiz->title]))
            ->line(__('**Score:** :score/:total (:percentage%)', [
                'score' => $this->score,
                'total' => $this->total,
                'percentage' => $percentage
            ]))
            ->action(__('Review Quiz'), route('student.quiz.show', $this->quiz))
            ->line($percentage >= 70
                ? __('Excellent work! Keep up the good progress!')
                : __('Good effort! Review the material and try again.')
            )
            ->salutation(__('Keep Learning,') . "\n" . __('BrainGenius'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $percentage = round(($this->score / $this->total) * 100);

        return [
            'type' => 'quiz_result',
            'quiz_id' => $this->quiz->id,
            'quiz_title' => $this->quiz->title,
            'score' => $this->score,
            'total' => $this->total,
            'percentage' => $percentage,
            'title' => __('Quiz Results: :quiz', ['quiz' => $this->quiz->title]),
            'message' => __('You scored :score/:total (:percentage%)', [
                'score' => $this->score,
                'total' => $this->total,
                'percentage' => $percentage
            ]),
            'action_url' => route('student.quiz.show', $this->quiz),
            'action_text' => __('View Results'),
            'icon' => 'o-trophy',
            'color' => $percentage >= 70 ? 'green' : 'orange',
        ];
    }
}
