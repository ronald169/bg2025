<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    /** @use HasFactory<\Database\Factories\SubjectFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'level',
        'color',
        'icon'
    ];

    // Relations
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    // Accessors
    public function getDisplayNameAttribute()
    {
        return $this->name . ' (' . ucfirst($this->level) . ')';
    }
}
