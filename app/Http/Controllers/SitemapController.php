<?php
// app/Http/Controllers/SitemapController.php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $courses = Cache::remember('sitemap_courses', 3600, function () {
            return Course::where('is_published', true)->get();
        });
        
        $lessons = Cache::remember('sitemap_lessons', 3600, function () {
            return Lesson::where('is_published', true)->get();
        });
        
        $quizzes = Cache::remember('sitemap_quizzes', 3600, function () {
            return Quiz::where('is_published', true)->get();
        });

        return response()->view('sitemap', [
            'courses' => $courses,
            'lessons' => $lessons,
            'quizzes' => $quizzes,
        ])->header('Content-Type', 'text/xml');
    }
}