<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExamAnswer extends Model
{
    protected $table = 'exam_answers';

    protected $fillable = [
        'exam_attempt_id', 'question_id', 'text_answer',
        'is_correct', 'points_earned'
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class, 'question_id');
    }

    public function grading(): HasOne
    {
        return $this->hasOne(ExamGrading::class, 'answer_id');
    }
}
