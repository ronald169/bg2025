<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewsTableSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::where('role', 'student')->get();
        $courses = Course::all();

        $comments = [
            'Excellent cours, très bien expliqué !',
            'Les vidéos sont de très bonne qualité.',
            'J\'ai beaucoup appris, merci !',
            'Très pédagogique, je recommande.',
            'Les exercices sont pertinents.',
            'Un cours complet et bien structuré.',
        ];

        foreach ($courses as $course) {
            // 2-5 avis par cours
            $reviewCount = rand(2, 5);
            $reviewers = $students->random(min($reviewCount, $students->count()));

            $totalRating = 0;
            foreach ($reviewers as $reviewer) {
                $rating = rand(3, 5);
                $totalRating += $rating;

                DB::table('reviews')->insert([
                    'user_id' => $reviewer->id,
                    'course_id' => $course->id,
                    'rating' => $rating,
                    'comment' => $comments[array_rand($comments)],
                    'is_approved' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Mettre à jour la note moyenne du cours
            $averageRating = $totalRating / $reviewCount;
            $course->update([
                'average_rating' => $averageRating,
                'reviews_count' => $reviewCount,
            ]);
        }
    }
}
