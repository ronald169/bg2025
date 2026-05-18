<?php

namespace App\Models;

use App\Traits\Seoable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Lesson extends Model
{
    use SoftDeletes, Seoable;

    protected $fillable = [
        'course_id', 'title', 'slug', 'description', 'content',
        'video_url', 'video_type', 'video_id', 'duration',
        'attachments', 'resources', 'order', 'is_free', 'is_published', 'quiz_id',
        'meta_title', 'meta_description', 'meta_keywords',
        'og_title', 'og_description', 'og_image',
        'canonical_url', 'robots'
    ];

    protected $casts = [
        'attachments' => 'array',
        'resources' => 'array',
        'is_free' => 'boolean',
        'is_published' => 'boolean',
        'duration' => 'integer'
    ];

    // ========== RELATIONS ==========

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function progress()
    {
        return $this->hasMany(Progress::class);
    }

    public function quiz()
    {
        return $this->hasOne(Quiz::class);
    }

    // ========== ACCESSORS ==========

    public function getFormattedDurationAttribute()
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        return "{$minutes}:{$seconds}";
    }

    // SEO
    public function getMetaTitleAttribute($value)
    {
        if ($value) {
            return $value;
        }
        return $this->title . ' - ' . ($this->course->title ?? '') . ' - ' . config('app.name');
    }

    public function getMetaDescriptionAttribute($value)
    {
        if ($value) {
            return $value;
        }
        return Str::limit(strip_tags($this->description ?? $this->content ?? ''), 160);
    }

    // ========== NAVIGATION ==========

    public function getNextAttribute()
    {
        return $this->course->lessons()
            ->where('order', '>', $this->order)
            ->where('is_published', true)
            ->orderBy('order')
            ->first();
    }

    public function getPreviousAttribute()
    {
        return $this->course->lessons()
            ->where('order', '<', $this->order)
            ->where('is_published', true)
            ->orderBy('order', 'desc')
            ->first();
    }

    // ========== BOOT ==========

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lesson) {
            if (empty($lesson->slug)) {
                $lesson->slug = Str::slug($lesson->title);
            }
        });
    }
}
