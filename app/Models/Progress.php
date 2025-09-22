<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Progress extends Model
{
    /** @use HasFactory<\Database\Factories\ProgressFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lesson_id',
        'is_completed',
        'time_spent',
        'last_accessed'
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'time_spent' => 'integer',
        'last_accessed' => 'datetime'
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    // Accessors
    public function getTimeSpentFormattedAttribute()
    {
        $hours = floor($this->time_spent / 3600);
        $minutes = floor(($this->time_spent % 3600) / 60);
        $seconds = $this->time_spent % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m {$seconds}s";
        }

        return "{$minutes}m {$seconds}s";
    }

    public function getIsRecentlyAccessedAttribute()
    {
        return $this->last_accessed && $this->last_accessed->gt(now()->subDay());
    }
}
