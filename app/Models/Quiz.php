<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    /** @use HasFactory<\Database\Factories\QuizFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'time_limit',
        'max_attempts',
        'lesson_id'
    ];

    protected $casts = [
        'time_limit' => 'integer',
        'max_attempts' => 'integer'
    ];

    // Relations
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    // Accessors
    public function getMaxScoreAttribute()
    {
        return $this->questions()->sum('points');
    }

    public function getQuestionCountAttribute()
    {
        return $this->questions()->count();
    }

    public function getHasTimeLimitAttribute()
    {
        return !is_null($this->time_limit);
    }
}
