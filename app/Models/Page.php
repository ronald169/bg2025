<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    /** @use HasFactory<\Database\Factories\PageFactory> */
    use HasFactory;

    public $timestamps  = false;
	protected $fillable = [
		'title',
		'slug',
		'body',
		'active',
		'seo_title',
		'meta_description',
		'meta_keywords',
	];
}
