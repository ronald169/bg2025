<?php
// app/Models/AnnouncementNotification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementNotification extends Model
{
    protected $fillable = [
        'announcement_id', 'user_id', 'is_read', 'read_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}