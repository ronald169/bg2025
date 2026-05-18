<?php
// app/Models/PathMilestone.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PathMilestone extends Model
{
    protected $fillable = [
        'learning_path_id', 'type', 'title', 'description',
        'required_points', 'required_hours', 'required_skill_level',
        'required_level', 'reward_points', 'reward_badge',
        'reward_certificate', 'achieved_at', 'is_achieved'
    ];

    protected $casts = [
        'required_points' => 'integer',
        'required_hours' => 'integer',
        'required_skill_level' => 'integer',
        'reward_points' => 'integer',
        'achieved_at' => 'datetime',
        'is_achieved' => 'boolean',
    ];

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function markAsAchieved(): void
    {
        $this->is_achieved = true;
        $this->achieved_at = now();
        $this->save();
        
        // Ajouter les points de récompense
        if ($this->reward_points > 0) {
            $this->learningPath->addPoints($this->reward_points);
        }
    }
}