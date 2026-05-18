<?php

namespace App\Models;

use App\Traits\Seoable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Course extends Model
{
    use SoftDeletes, Seoable;

    protected $fillable = [
        'title', 'slug', 'description', 'short_description',
        'subject_id', 'teacher_id', 'level', 'difficulty',
        'estimated_duration', 'thumbnail', 'preview_video', 'video_type',
        'requirements', 'what_you_will_learn', 'tags',
        'is_published', 'is_featured', 'is_free',
        'price', 'sale_price',
        'views_count', 'enrollments_count', 'average_rating', 'reviews_count',
        'meta_title', 'meta_description', 'meta_keywords',
        'og_title', 'og_description', 'og_image',
        'twitter_title', 'twitter_description', 'twitter_image',
        'canonical_url', 'robots'
    ];

    protected $casts = [
        'requirements' => 'array',
        'what_you_will_learn' => 'array',
        'tags' => 'array',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'is_free' => 'boolean',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    // ========== RELATIONS ==========

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments')
                    ->withTimestamps()
                    ->withPivot('status', 'progress');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function quizzes()
    {
        return $this->hasManyThrough(Quiz::class, Lesson::class);
    }

    // SEO Accessors
    public function getMetaTitleAttribute($value)
    {
        if ($value) {
            return $value;
        }
        return $this->title . ' - ' . config('app.name');
    }

    public function getMetaDescriptionAttribute($value)
    {
        if ($value) {
            return $value;
        }
        return Str::limit(strip_tags($this->description), 160);
    }

    public function getOgImageAttribute($value)
    {
        if ($value) {
            return $value;
        }
        return $this->thumbnail ?? asset('images/default-og-image.jpg');
    }

    // ========== ACCESSORS ==========

    public function getTotalQuizzesAttribute()
    {
        return $this->lessons()->whereHas('quiz')->count();
    }

    public function getLessonsCountAttribute()
    {
        return $this->lessons()->count();
    }

    public function getEnrollmentsCountAttribute()
    {
        return $this->enrollments()->count();
    }


    public function getFormattedDurationAttribute()
    {
        $hours = floor($this->estimated_duration / 60);
        $minutes = $this->estimated_duration % 60;

        if ($hours > 0) {
            return "{$hours}h" . ($minutes > 0 ? " {$minutes}min" : "");
        }
        return "{$minutes}min";
    }

    public function getCurrentPriceAttribute()
    {
        return $this->sale_price && $this->sale_price < $this->price
            ? $this->sale_price
            : $this->price;
    }

    public function getTotalLessonsAttribute()
    {
        return $this->lessons()->count();
    }

    // ========== SCOPES ==========

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    // ========== BOOT ==========

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title);
            }
        });
    }

    // Sitemap Cache Clearing
    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('sitemap_courses');
        });
        
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('sitemap_courses');
        });
    }
}
