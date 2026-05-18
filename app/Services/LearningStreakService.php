<?php

namespace App\Services;

use App\Models\User;
use App\Models\StudySession;
use Carbon\Carbon;

class LearningStreakService
{
    /**
     * Enregistre une session d'étude et met à jour la série
     */
    public function recordStudySession(User $user, array $data): StudySession
    {
        // Crée la session d'étude
        $session = $user->studySessions()->create([
            'duration_minutes' => $data['duration_minutes'] ?? 0,
            'date' => $data['date'] ?? now()->toDateString(),
            'started_at' => $data['started_at'] ?? now(),
            'ended_at' => $data['ended_at'] ?? now(),
            'course_id' => $data['course_id'] ?? null,
            'lesson_id' => $data['lesson_id'] ?? null,
            'topics_covered' => $data['topics_covered'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        // Met à jour la série d'apprentissage
        $user->updateLearningStreak();

        return $session;
    }

    /**
     * Vérifie et répare les séries (pour les batchs nocturnes)
     */
    public function validateAndRepairStreaks(): array
    {
        $results = [
            'repaired' => 0,
            'reset' => 0,
            'unchanged' => 0,
        ];

        $users = User::with('learningStreak')->get();

        foreach ($users as $user) {
            $streak = $user->learningStreak;

            if (!$streak->last_study_date) {
                continue;
            }

            $lastDate = $streak->last_study_date;
            $today = now();

            // Si la dernière étude était avant-hier ou plus, réinitialise la série
            if ($lastDate->diffInDays($today) > 1) {
                $streak->current_streak = 0;
                $streak->save();
                $results['reset']++;
            } elseif ($lastDate->isYesterday()) {
                // L'utilisateur a étudié hier, la série est toujours valide
                $results['unchanged']++;
            }
        }

        return $results;
    }

    /**
     * Récupère le leaderboard des séries
     */
    public function getStreakLeaderboard(int $limit = 10): array
    {
        return User::with('learningStreak')
            ->whereHas('learningStreak', function ($query) {
                $query->where('current_streak', '>', 0);
            })
            ->join('learning_streaks', 'users.id', '=', 'learning_streaks.user_id')
            ->orderBy('learning_streaks.current_streak', 'desc')
            ->orderBy('learning_streaks.longest_streak', 'desc')
            ->limit($limit)
            ->get(['users.*', 'learning_streaks.current_streak', 'learning_streaks.longest_streak'])
            ->map(function ($user) {
                return [
                    'user' => $user->only(['id', 'name', 'email']),
                    'current_streak' => $user->current_streak,
                    'longest_streak' => $user->longest_streak,
                    'avatar' => $user->profile_photo_path,
                ];
            })
            ->toArray();
    }

    /**
     * Envoie des rappels pour maintenir les séries
     */
    public function sendStreakReminders(): void
    {
        $users = User::with('learningStreak')
            ->whereHas('learningStreak', function ($query) {
                $query->where('current_streak', '>', 0)
                      ->whereDate('last_study_date', now()->subDay());
            })
            ->where('push_notifications', true)
            ->get();

        foreach ($users as $user) {
            // Envoyer une notification push/email
            $user->notify(new \App\Notifications\StreakReminderNotification($user->learningStreak));
        }
    }

    /**
     * Calcule les statistiques globales des séries
     */
    public function getGlobalStreakStats(): array
    {
        $stats = \DB::table('learning_streaks')
            ->select([
                \DB::raw('AVG(current_streak) as avg_streak'),
                \DB::raw('MAX(current_streak) as max_streak'),
                \DB::raw('COUNT(CASE WHEN current_streak >= 7 THEN 1 END) as weekly_warriors'),
                \DB::raw('COUNT(CASE WHEN current_streak >= 30 THEN 1 END) as monthly_masters'),
                \DB::raw('COUNT(*) as total_users_with_streak'),
            ])
            ->first();

        return [
            'average_streak' => round($stats->avg_streak ?? 0, 1),
            'longest_streak' => $stats->max_streak ?? 0,
            'weekly_warriors' => $stats->weekly_warriors ?? 0,
            'monthly_masters' => $stats->monthly_masters ?? 0,
            'active_users' => $stats->total_users_with_streak ?? 0,
        ];
    }
}
