<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    /** @use HasFactory<\Database\Factories\QuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'question',
        'type',
        'options',
        'correct_answer',
        'points',
        'explanation',
        'quiz_id'
    ];

    protected $casts = [
        'points' => 'integer',
        'options' => 'array' // Cast JSON to array
    ];

    // Relations
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    // Accessors
    public function getFormattedOptionsAttribute()
    {
        if ($this->type === 'multiple_choice' && is_array($this->options)) {
            return array_combine(
                range(0, count($this->options) - 1),
                $this->options
            );
        }
        return [];
    }

    public function getIsMultipleChoiceAttribute()
    {
        return $this->type === 'multiple_choice';
    }

    public function getIsTrueFalseAttribute()
    {
        return $this->type === 'true_false';
    }

    public function getIsShortAnswerAttribute()
    {
        return $this->type === 'short_answer';
    }
}
