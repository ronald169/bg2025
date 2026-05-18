<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flashcard extends Model
{
    protected $fillable = ['question', 'answer', 'example'];

    public function sets()
    {
        return $this->belongsToMany(FlashcardSet::class, 'flashcard_set_card')
                    ->withPivot('known')
                    ->withTimestamps();
    }
}
