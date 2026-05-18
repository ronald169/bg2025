<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashcardSet extends Model
{
    protected $fillable = ['user_id', 'name', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cards()
    {
        return $this->belongsToMany(Flashcard::class, 'flashcard_set_card')
                    ->withPivot('known')
                    ->withTimestamps();
    }
}
