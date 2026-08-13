<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamQuestion extends Model
{
    use SoftDeletes;

    protected $table = 'exam_questions';

    protected $fillable = [
        'teil_id', 'sort_order', 'question_type', 'content', 'image_path',
        'points', 'options', 'correct_answer', 'correct_answer_explanation'
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
    ];

    public function teil(): BelongsTo
    {
        return $this->belongsTo(ExamTeil::class, 'teil_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'question_id');
    }

    public function getCorrectAnswerValue(): mixed
    {
        return is_array($this->correct_answer)
            ? ($this->correct_answer[0] ?? null)
            : $this->correct_answer;
    }
}
