<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    /** @use HasFactory<\Database\Factories\EnrollmentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    // Accessors
    public function getIsCompletedAttribute()
    {
        return !is_null($this->completed_at);
    }

    public function getProgressPercentageAttribute()
    {
        // Chargement eager pour éviter les requêtes N+1
        $this->load(['course.lessons', 'user.progress']);

        $totalLesson = $this->course->lessons->count();
        if ($totalLesson === 0) return 0;

        $completedLessons = $this->user->progress()
            ->whereIn('lesson_id', $this->course->lessons->pluck('id'))
            ->where('is_completed', true)
            ->count();

        return round(($completedLessons / $totalLesson) * 100);
    }
}
