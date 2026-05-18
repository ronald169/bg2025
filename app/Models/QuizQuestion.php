<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = [
        'quiz_id', 'question', 'type', 'options', 'correct_answer', 'points', 'explanation', 'order'
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
        'points' => 'integer'
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function isCorrect($answer): bool
    {
        return match ($this->type) {
            'multiple_choice' => in_array($answer, $this->correct_answer),
            'true_false' => $answer == $this->correct_answer[0],
            'text' => strtolower(trim($answer)) == strtolower(trim($this->correct_answer[0])),
            default => false,
        };
    }
}
