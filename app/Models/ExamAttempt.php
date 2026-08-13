<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    protected $table = 'exam_attempts';

    protected $fillable = [
        'user_id', 'exam_id', 'module_id', 'started_at', 'finished_at',
        'last_activity_at', 'status', 'total_score', 'max_possible_score'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(ExamModule::class, 'module_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'exam_attempt_id');
    }
}
