<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamTeil extends Model
{
    use SoftDeletes;

    protected $table = 'exam_teils';

    protected $fillable = [
        'title', 'order', 'duration_minutes', 'instructions',
        'content', 'content_image', 'audio_path', 'source'
    ];

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(ExamModule::class, 'exam_module_teil', 'teil_id', 'module_id')
                    ->withPivot('order');
    }

    // Toutes les questions liées à ce teil (peut traverser plusieurs modules)
    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class, 'teil_id');
    }
}
