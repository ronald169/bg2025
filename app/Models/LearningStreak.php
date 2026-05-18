<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningStreak extends Model
{
    protected $fillable = [
        'user_id', 'current_streak', 'longest_streak', 'last_study_date', 'total_study_days'
    ];

    protected $casts = [
        'last_study_date' => 'datetime',
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'total_study_days' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function studiedToday(): bool
    {
        return $this->last_study_date?->isToday() ?? false;
    }

    public function isInDanger(): bool
    {
        return $this->last_study_date?->isYesterday() && !$this->studiedToday();
    }

    public function nextGoal(): ?array
    {
        $milestones = [3, 7, 14, 30, 60, 90];

        foreach ($milestones as $days) {
            if ($this->current_streak < $days) {
                return [
                    'days_required' => $days,
                    'days_remaining' => $days - $this->current_streak,
                    'progress_percentage' => ($this->current_streak / $days) * 100
                ];
            }
        }

        return null;
    }
}
