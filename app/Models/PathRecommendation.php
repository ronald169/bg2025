<?php
// app/Models/PathRecommendation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PathRecommendation extends Model
{
    protected $fillable = [
        'learning_path_id', 'course_id', 'quiz_id', 'type', 'priority',
        'title', 'description', 'metadata', 'target_skill', 'target_level',
        'is_viewed', 'viewed_at', 'is_applied', 'applied_at', 'is_dismissed'
    ];

    protected $casts = [
        'metadata' => 'array',
        'priority' => 'integer',
        'is_viewed' => 'boolean',
        'is_applied' => 'boolean',
        'is_dismissed' => 'boolean',
        'viewed_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}