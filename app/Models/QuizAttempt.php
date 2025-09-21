<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    /** @use HasFactory<\Database\Factories\QuizAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_id',
        'score',
        'time_spent',
        'completed_at',
        'answers'
    ];

    protected $casts = [
        'score' => 'integer',
        'time_spent' => 'integer',
        'completed_at' => 'datetime',
        'answers' => 'array' // Cast JSON to array
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    // Accessors
    public function getIsCompletedAttribute()
    {
        return !is_null($this->completed_at);
    }

    public function getTimeSpentFormattedAttribute()
    {
        $minutes = floor($this->time_spent / 60);
        $seconds = $this->time_spent % 60;

        return "{$minutes}m {$seconds}s";
    }

    public function getPercentageScoreAttribute()
    {
        $maxScore = $this->quiz()->max_score ?? 100;
        return $maxScore > 0 ? round(($this->score / $maxScore) * 100) : 0;
    }

    public function getPassedAttribute()
    {
        return $this->percentatge_score >= 70;
    }
}
