<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamGrading extends Model
{
    protected $table = 'exam_gradings';

    protected $fillable = [
        'answer_id', 'teacher_id', 'ai_score', 'ai_feedback',
        'ai_criteria_scores', 'ai_graded_at', 'teacher_score',
        'teacher_feedback', 'graded_at', 'status'
    ];

    protected $casts = [
        'ai_criteria_scores' => 'array',
        'ai_graded_at' => 'datetime',
        'graded_at' => 'datetime',
    ];

    public function answer(): BelongsTo
    {
        return $this->belongsTo(ExamAnswer::class, 'answer_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'teacher_id');
    }
}
