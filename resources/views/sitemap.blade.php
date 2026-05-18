<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

    <!-- Page d'accueil -->
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->toDateString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Catalogue des cours -->
    <url>
        <loc>{{ route('student.catalog') }}</loc>
        <lastmod>{{ now()->toDateString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- Page de contact -->
    <url>
        <loc>{{ url('/#contact') }}</loc>
        <lastmod>{{ now()->toDateString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <!-- Cours individuels -->
    @foreach($courses as $course)
    <url>
        <loc>{{ route('student.course.show', $course) }}</loc>
        <lastmod>{{ $course->updated_at->toDateString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach

    <!-- Leçons individuelles -->
    @foreach($lessons as $lesson)
    <url>
        <loc>{{ route('student.lesson.show', ['course' => $lesson->course, 'lesson' => $lesson]) }}</loc>
        <lastmod>{{ $lesson->updated_at->toDateString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach

    <!-- Quiz individuels -->
    @foreach($quizzes as $quiz)
    <url>
        <loc>{{ route('student.quiz.show', $quiz) }}</loc>
        <lastmod>{{ $quiz->updated_at->toDateString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    @endforeach

</urlset>