<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamModule extends Model
{
    use SoftDeletes;

    protected $table = 'exam_modules';

    protected $fillable = [
        'exam_id', 'name', 'code', 'order', 'duration_minutes',
        'general_instructions', 'has_global_numbering'
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function teils(): BelongsToMany
    {
        return $this->belongsToMany(ExamTeil::class, 'exam_module_teil', 'module_id', 'teil_id')
                    ->withPivot('order')
                    ->orderByPivot('order');
    }

    // Questions accessibles via les teils du module (lecture seule)
    public function questions(): HasMany
    {
        $teilIds = $this->teils()->pluck('exam_teils.id');
        return ExamQuestion::whereIn('teil_id', $teilIds)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'module_id');
    }

    public function scopeStandalone($query)
    {
        return $query->whereNull('exam_id');
    }

    public function scopeInExam($query)
    {
        return $query->whereNotNull('exam_id');
    }
}
