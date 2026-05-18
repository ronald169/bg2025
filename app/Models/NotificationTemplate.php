<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'title', 'type', 'subject', 'content', 'action_url', 'action_text', 'is_active', 'variables'
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean'
    ];
}
