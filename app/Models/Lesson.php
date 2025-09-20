<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lesson extends Model
{
    /** @use HasFactory<\Database\Factories\LessonFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'video_url',
        'duration',
        'order',
        'is_free',
        'course_id',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'duration' => 'integer',
        'order' => 'integer'
    ];

    // Relation

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(Progress::class);
    }

    // Méthodes métier
    public function markAsCompleted(User $user, $timeSpent = 0)
    {
        // Trouver ou créer l'enregistrement de progression
        $progress = Progress::firstOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $this->id
            ],
            [
                'time_spent' => 0,
                'is_completed' => false
            ]
        );

        // Mettre à jour le temps passé
        $progress->time_spent += $timeSpent;
        $progress->last_accessed = now();

        // Si pas déjà complété, vérifier les critères
        if (!$progress->is_completed) {
            $progress->is_completed = $this->checkCompletionCriteria($user, $timeSpent);
        }

        $progress->save();

        return $progress;
    }

    public function checkCompletionCriteria(User $user, $timeSpent = 0)
    {
        $timeCriteria = ($timeSpent >= ($this->duration * 60 * 0.8));
        $quizCriteria = true;

        if ($this->quiz) {
            $bestAttempt = $this->quiz->attempts()
                                ->where('user_id', $user->id)
                                ->orderBy('score', 'desc')
                                ->first();

            $quizCriteria = $bestAttempt && $bestAttempt->score >= 70;
        }

        return $timeCriteria && $quizCriteria;
    }

    // Accessors
    public function getDurationFormattedAttribute()
    {
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}min";
        }
        return "{$minutes}min";
    }

    public function getIsUnlockedAttribute()
    {
        // Logique pour déterminer si la leçon est débloquée
        return true;
    }
}
