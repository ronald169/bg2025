<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $table = 'exams';

    protected $fillable = [
        'title', 'slug', 'subtitle', 'level', 'total_duration_minutes', 'is_active'
    ];

    public function modules(): HasMany
    {
        return $this->hasMany(ExamModule::class, 'exam_id');
    }
}
