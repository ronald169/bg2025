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
    // public function index(): Response
    // {
    //     $courses = Cache::remember('sitemap_courses', 3600, function () {
    //         return Course::where('is_published', true)->get();
    //     });

    //     $lessons = Cache::remember('sitemap_lessons', 3600, function () {
    //         return Lesson::where('is_published', true)->get();
    //     });

    //     $quizzes = Cache::remember('sitemap_quizzes', 3600, function () {
    //         return Quiz::where('is_published', true)->get();
    //     });

    //     return response()->view('sitemap', [
    //         'courses' => $courses,
    //         'lessons' => $lessons,
    //         'quizzes' => $quizzes,
    //     ])->header('Content-Type', 'text/xml');
    // }

    public function index(): Response
    {
        $cacheKeys = ['sitemap_courses', 'sitemap_lessons', 'sitemap_quizzes'];

        foreach ($cacheKeys as $key) {
            try {
                // Tenter de récupérer le cache
                $data = Cache::get($key);
                if ($data === null) {
                    throw new \Exception('Cache miss');
                }
            } catch (\Exception $e) {
                // Si le cache est corrompu ou absent, le recréer
                Cache::forget($key);
                $data = $this->buildSitemapData($key);
                Cache::put($key, $data, 3600);
            }
        }

        $courses = Cache::get('sitemap_courses');
        $lessons = Cache::get('sitemap_lessons');
        $quizzes = Cache::get('sitemap_quizzes');

        return response()->view('sitemap', [
            'courses' => $courses,
            'lessons' => $lessons,
            'quizzes' => $quizzes,
        ])->header('Content-Type', 'text/xml');
    }

    private function buildSitemapData(string $key)
    {
        return match($key) {
            'sitemap_courses' => Course::where('is_published', true)->get(),
            'sitemap_lessons' => Lesson::where('is_published', true)->get(),
            'sitemap_quizzes' => Quiz::where('is_published', true)->get(),
        };
    }
}
