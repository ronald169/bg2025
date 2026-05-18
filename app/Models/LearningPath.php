<?php
// app/Models/LearningPath.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Carbon\Carbon;

class LearningPath extends Model
{
    protected $fillable = [
        'user_id',
        'current_level',
        'target_level',
        'learning_goal',
        'overall_progress',
        'total_points',
        'total_hours_studied',
        'total_quizzes_taken',
        'total_quizzes_passed',
        'target_certification',
        'target_exam_date',
        'exam_registered',
        'reading_skill',
        'writing_skill',
        'listening_skill',
        'speaking_skill',
        'grammar_skill',
        'vocabulary_skill',
        'custom_goals',
        'milestones',
        'started_at',
        'completed_at',
        'last_activity_at',
        'estimated_completion_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'custom_goals' => 'array',
        'milestones' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'estimated_completion_date' => 'date',
        'target_exam_date' => 'date',
        'exam_registered' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'reading_skill' => 0,
        'writing_skill' => 0,
        'listening_skill' => 0,
        'speaking_skill' => 0,
        'grammar_skill' => 0,
        'vocabulary_skill' => 0,
        'total_points' => 0,
        'total_hours_studied' => 0,
        'overall_progress' => 0,
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(PathMilestone::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(PathRecommendation::class);
    }

    public function completedCourses()
    {
        return $this->hasManyThrough(
            Enrollment::class,
            User::class,
            'id',
            'user_id',
            'user_id',
            'id'
        )->where('progress', 100);
    }

    // Accesseurs
    public function getSkillsAverageAttribute(): int
    {
        $skills = [
            $this->reading_skill,
            $this->writing_skill,
            $this->listening_skill,
            $this->speaking_skill,
            $this->grammar_skill,
            $this->vocabulary_skill,
        ];
        
        return (int) round(array_sum($skills) / count($skills));
    }

    public function getDaysSinceStartAttribute(): int
    {
        if (!$this->started_at) return 0;
        return $this->started_at->diffInDays(now());
    }

    public function getEstimatedDaysRemainingAttribute(): int
    {
        if (!$this->estimated_completion_date) return 0;
        return now()->diffInDays($this->estimated_completion_date);
    }

    public function getNextLevelAttribute(): ?string
    {
        $levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $currentIndex = array_search($this->current_level, $levels);
        
        if ($currentIndex !== false && isset($levels[$currentIndex + 1])) {
            return $levels[$currentIndex + 1];
        }
        
        return null;
    }

    public function getProgressToNextLevelAttribute(): int
    {
        $levelProgress = [
            'A1' => $this->overall_progress,
            'A2' => max(0, $this->overall_progress - 20),
            'B1' => max(0, $this->overall_progress - 40),
            'B2' => max(0, $this->overall_progress - 60),
            'C1' => max(0, $this->overall_progress - 80),
            'C2' => max(0, $this->overall_progress - 95),
        ];
        
        return min(100, max(0, $levelProgress[$this->current_level] ?? 0));
    }

    // Méthodes
    public function updateProgress(): void
    {
        // Calculer la progression basée sur les compétences
        $skillProgress = ($this->reading_skill + $this->writing_skill + 
                         $this->listening_skill + $this->speaking_skill + 
                         $this->grammar_skill + $this->vocabulary_skill) / 6;
        
        // Ajouter un bonus pour les cours complétés
        $completedCoursesBonus = min(20, $this->user->enrollments()->where('progress', 100)->count() * 2);
        
        // Ajouter un bonus pour les quiz réussis
        $quizBonus = min(15, $this->total_quizzes_passed * 1.5);
        
        // Progression totale
        $this->overall_progress = min(100, round($skillProgress + $completedCoursesBonus + $quizBonus));
        
        // Mise à jour automatique du niveau si progression suffisante
        $this->updateCurrentLevel();
        
        $this->save();
    }

    public function updateCurrentLevel(): void
    {
        $levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $thresholds = [20, 40, 60, 80, 95, 100];
        
        foreach ($thresholds as $index => $threshold) {
            if ($this->overall_progress >= $threshold && isset($levels[$index])) {
                if ($levels[$index] !== $this->current_level) {
                    $this->current_level = $levels[$index];
                    $this->addMilestone('level_up', [
                        'level' => $this->current_level,
                        'date' => now(),
                    ]);
                }
            }
        }
        
        $this->save();
    }

    public function updateSkills(array $skills): void
    {
        foreach ($skills as $skill => $value) {
            if (in_array($skill, ['reading', 'writing', 'listening', 'speaking', 'grammar', 'vocabulary'])) {
                $field = $skill . '_skill';
                $this->$field = min(100, max(0, $value));
            }
        }
        
        $this->save();
        $this->updateProgress();
    }

    public function addPoints(int $points): void
    {
        $this->total_points += $points;
        $this->save();
        
        // Vérifier les récompenses
        $this->checkRewards();
    }

    public function addStudyTime(int $minutes): void
    {
        $this->total_hours_studied += ($minutes / 60);
        $this->last_activity_at = now();
        $this->save();
        
        // Mettre à jour la streak
        $this->user->updateStreak();
    }

    public function addQuizResult(int $score, bool $passed): void
    {
        $this->total_quizzes_taken++;
        
        if ($passed) {
            $this->total_quizzes_passed++;
            $this->addPoints($score * 10);
        }
        
        $this->save();
    }

    public function addMilestone(string $type, array $data = []): void
    {
        $milestones = $this->milestones ?? [];
        
        $milestones[] = [
            'type' => $type,
            'data' => $data,
            'achieved_at' => now(),
        ];
        
        $this->milestones = $milestones;
        $this->save();
    }

    public function checkRewards(): void
    {
        $rewards = [
            ['points' => 100, 'milestone' => 'first_100_points'],
            ['points' => 500, 'milestone' => 'first_500_points'],
            ['points' => 1000, 'milestone' => 'first_1000_points'],
            ['hours' => 10, 'milestone' => '10_hours_studied'],
            ['hours' => 50, 'milestone' => '50_hours_studied'],
            ['hours' => 100, 'milestone' => '100_hours_studied'],
        ];
        
        foreach ($rewards as $reward) {
            if (isset($reward['points']) && $this->total_points >= $reward['points']) {
                $this->addMilestone($reward['milestone']);
            }
            if (isset($reward['hours']) && $this->total_hours_studied >= $reward['hours']) {
                $this->addMilestone($reward['milestone']);
            }
        }
    }

    public function getRecommendations(): array
    {
        $recommendations = [];
        
        // Recommandations basées sur les compétences faibles
        $weakSkills = [];
        if ($this->reading_skill < 50) $weakSkills[] = 'reading';
        if ($this->writing_skill < 50) $weakSkills[] = 'writing';
        if ($this->listening_skill < 50) $weakSkills[] = 'listening';
        if ($this->speaking_skill < 50) $weakSkills[] = 'speaking';
        if ($this->grammar_skill < 50) $weakSkills[] = 'grammar';
        if ($this->vocabulary_skill < 50) $weakSkills[] = 'vocabulary';
        
        if (!empty($weakSkills)) {
            $recommendations[] = [
                'type' => 'weak_skills',
                'skills' => $weakSkills,
                'message' => 'Concentrez-vous sur ' . implode(', ', $weakSkills) . ' pour progresser plus rapidement.',
            ];
        }
        
        // Recommandation pour l'examen
        if ($this->target_exam_date && $this->overall_progress < 80) {
            $daysLeft = now()->diffInDays($this->target_exam_date);
            if ($daysLeft < 30) {
                $recommendations[] = [
                    'type' => 'exam_urgent',
                    'message' => 'Votre examen approche ! Augmentez votre rythme d\'étude à 1h par jour minimum.',
                ];
            }
        }
        
        return $recommendations;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('current_level', $level);
    }

    public function scopeTargetingCertification($query, string $certification = null)
    {
        $query->whereNotNull('target_certification');
        
        if ($certification) {
            $query->where('target_certification', $certification);
        }
        
        return $query;
    }
}