<?php

namespace App\Models;

use App\Traits\Seoable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Quiz extends Model
{
    use SoftDeletes, Seoable;

    protected $fillable = [
        'lesson_id', 'title', 'description',
        'time_limit', 'passing_score', 'max_attempts',
        'is_published', 'order',
        'meta_title', 'meta_description', 'meta_keywords',
        'og_title', 'og_description', 'canonical_url', 'robots'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'time_limit' => 'integer',
        'passing_score' => 'integer',
        'max_attempts' => 'integer'
    ];

    // ========== RELATIONS ==========

    // Relation avec la leçon (inverse)
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    // SEO
    public function getMetaTitleAttribute($value)
    {
        if ($value) {
            return $value;
        }
        return $this->title . ' - Quiz - ' . ($this->lesson->course->title ?? '') . ' - ' . config('app.name');
    }

    public function getMetaDescriptionAttribute($value)
    {
        if ($value) {
            return $value;
        }
        return 'Test your knowledge with this quiz: ' . Str::limit(strip_tags($this->description ?? ''), 150);
    }

    // Accès au cours via la leçon
    public function getCourseAttribute()
    {
        return $this->lesson->course;
    }

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    // ========== ACCESSORS ==========

    public function getTotalQuestionsAttribute()
    {
        return $this->questions()->count();
    }

    public function getCourseIdAttribute()
    {
        return $this->lesson->course_id;
    }
}
