<?php
// app/Models/Announcement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_id', 'teacher_id', 'title', 'content', 'type',
        'icon', 'color', 'is_published', 'is_pinned', 'send_notification',
        'published_at', 'expires_at', 'views_count'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_pinned' => 'boolean',
        'send_notification' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'views_count' => 'integer',
    ];

    // ========== RELATIONS ==========

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function notifications()
    {
        return $this->hasMany(AnnouncementNotification::class);
    }

    // Mark as read for a user
    public function markAsRead($userId)
    {
        return $this->notifications()->updateOrCreate(
            ['user_id' => $userId],
            ['is_read' => true, 'read_at' => now()]
        );
    }

    // Check if user has read
    public function isReadByUser($userId)
    {
        return $this->notifications()
            ->where('user_id', $userId)
            ->where('is_read', true)
            ->exists();
    }
    // ========== SCOPES ==========

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->where(function($q) {
                        $q->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                    })
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    // ========== ACCESSORS ==========

    public function getTypeClassAttribute()
    {
        return match($this->type) {
            'info' => 'bg-blue-100 text-blue-800 border-blue-200',
            'warning' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            'success' => 'bg-green-100 text-green-800 border-green-200',
            'danger' => 'bg-red-100 text-red-800 border-red-200',
            default => 'bg-gray-100 text-gray-800 border-gray-200',
        };
    }

    public function getTypeIconAttribute()
    {
        return match($this->type) {
            'info' => 'o-information-circle',
            'warning' => 'o-exclamation-triangle',
            'success' => 'o-check-circle',
            'danger' => 'o-x-circle',
            default => 'o-megaphone',
        };
    }

    public function getFormattedDateAttribute()
    {
        return $this->published_at?->format('d.m.Y H:i') ?? $this->created_at->format('d.m.Y H:i');
    }

    public function getTimeAgoAttribute()
    {
        return $this->published_at?->diffForHumans() ?? $this->created_at->diffForHumans();
    }

    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
