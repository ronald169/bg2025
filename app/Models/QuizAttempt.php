<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $fillable = [
        'user_id', 'quiz_id', 'score', 'answers', 'started_at', 'completed_at', 'is_passed'
    ];

    protected $casts = [
        'answers' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_passed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function getPercentageAttribute()
    {
        $total = $this->quiz->total_questions;
        return $total > 0 ? round(($this->score / $total) * 100) : 0;
    }

    // Accesseur pour le pourcentage de score
    public function getScorePercentageAttribute()
    {
        $quiz = $this->quiz;
        if (!$quiz) return 0;

        $totalPoints = $quiz->questions->sum('points');
        if ($totalPoints === 0) return 0;

        return round(($this->score / $totalPoints) * 100);
    }

    // Accesseur pour le total des points
    public function getTotalPointsAttribute()
    {
        return $this->quiz->questions->sum('points');
    }
}
