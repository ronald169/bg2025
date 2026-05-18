<?php

use App\Models\User;
use App\Models\Progress;
use App\Models\QuizAttempt;
use App\Models\StudySession;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Student Details - Lehrer')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use Toast;

    public User $user;

    #[Computed]
    public function courses()
    {
        return $this->user->enrollments()
            ->with('course')
            ->get()
            ->map(function ($enrollment) {
                $course = $enrollment->course;
                $course->progress = $enrollment->progress;
                $course->enrolled_at = $enrollment->created_at;

                // Dernière activité
                $lastProgress = Progress::where('user_id', $this->user->id)
                    ->whereHas('lesson', function($q) use ($course) {
                        $q->where('course_id', $course->id);
                    })
                    ->latest('updated_at')
                    ->first();
                $course->last_activity = $lastProgress?->updated_at;

                // Nombre de leçons complétées
                $course->completed_lessons = Progress::where('user_id', $this->user->id)
                    ->whereHas('lesson', function($q) use ($course) {
                        $q->where('course_id', $course->id);
                    })
                    ->where('is_completed', true)
                    ->count();

                $course->total_lessons = $course->lessons()->count();

                return $course;
            });
    }

    #[Computed]
    public function stats()
    {
        $totalLessons = Progress::where('user_id', $this->user->id)->count();
        $completedLessons = Progress::where('user_id', $this->user->id)
            ->where('is_completed', true)
            ->count();

        $totalStudyTime = StudySession::where('user_id', $this->user->id)->sum('duration_minutes');

        $quizAttempts = QuizAttempt::where('user_id', $this->user->id)->get();
        $avgScore = 0;
        if ($quizAttempts->count() > 0) {
            $totalPercentage = 0;
            foreach ($quizAttempts as $attempt) {
                $quiz = $attempt->quiz;
                if ($quiz && $quiz->questions) {
                    $totalPoints = $quiz->questions->sum('points');
                    if ($totalPoints > 0) {
                        $percentage = round(($attempt->score / $totalPoints) * 100);
                        $totalPercentage += $percentage;
                    }
                }
            }
            $avgScore = round($totalPercentage / $quizAttempts->count());
        }

        // Meilleur score
        $bestAttempt = $quizAttempts->sortByDesc(function($attempt) {
            $quiz = $attempt->quiz;
            if ($quiz && $quiz->questions) {
                $totalPoints = $quiz->questions->sum('points');
                if ($totalPoints > 0) {
                    return ($attempt->score / $totalPoints) * 100;
                }
            }
            return 0;
        })->first();

        $bestScore = 0;
        if ($bestAttempt) {
            $quiz = $bestAttempt->quiz;
            if ($quiz && $quiz->questions) {
                $totalPoints = $quiz->questions->sum('points');
                if ($totalPoints > 0) {
                    $bestScore = round(($bestAttempt->score / $totalPoints) * 100);
                }
            }
        }

        return [
            'total_courses' => $this->courses->count(),
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'completion_rate' => $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0,
            'quiz_attempts' => $quizAttempts->count(),
            'avg_score' => $avgScore,
            'best_score' => $bestScore,
            'total_study_time' => $totalStudyTime,
        ];
    }

    public function getFormattedStudyTime($minutes): string
    {
        if ($minutes < 60) return "{$minutes} min";
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
    }

    public function getProgressColor($progress): string
    {
        if ($progress >= 80) return 'bg-green-500';
        if ($progress >= 50) return 'bg-blue-500';
        if ($progress >= 20) return 'bg-yellow-500';
        return 'bg-gray-400';
    }

    public function getProgressTextColor($progress): string
    {
        if ($progress >= 80) return 'text-green-600';
        if ($progress >= 50) return 'text-blue-600';
        if ($progress >= 20) return 'text-yellow-600';
        return 'text-gray-600';
    }
}
?>

<div class="py-4 md:py-6">
    <div class="max-w-5xl px-3 mx-auto md:px-4">

        <!-- Navigation -->
        <div class="mb-5">
            <a href="{{ route('teacher.students') }}" class="inline-flex items-center gap-1 text-sm text-[#FF6B35] hover:text-[#E55A2A] transition">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Zurück zu den Studenten') }}
            </a>
        </div>

        <!-- Header - Version responsive -->
        <div class="p-4 mb-6 bg-white shadow-sm rounded-xl md:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <!-- Avatar -->
                <div class="flex justify-center sm:justify-start">
                    <div class="w-20 h-20 md:w-24 md:h-24 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-3xl md:text-4xl">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                </div>

                <!-- Infos -->
                <div class="flex-1 text-center sm:text-left">
                    <h1 class="text-xl font-bold text-gray-900 md:text-2xl">{{ $user->name }}</h1>
                    <p class="text-sm text-gray-500 md:text-base">{{ $user->email }}</p>
                    <div class="flex flex-wrap items-center justify-center gap-2 mt-2 sm:justify-start">
                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">
                            {{ $user->german_level ?? 'A1' }} - Deutsch
                        </span>
                        <span class="text-xs text-gray-400">
                            {{ __('Mitglied seit') }} {{ $user->created_at->format('d.m.Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards - Grille responsive -->
        <div class="grid grid-cols-2 gap-3 mb-6 md:grid-cols-4">
            <div class="bg-white rounded-lg p-3 text-center shadow-sm border-l-4 border-l-[#FF6B35]">
                <p class="text-xs text-gray-500">📚 {{ __('Kurse') }}</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->stats['total_courses'] }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-green-500">
                <p class="text-xs text-gray-500">✅ {{ __('Lektionen') }}</p>
                <p class="text-xl font-bold text-green-600">{{ $this->stats['completed_lessons'] }}/{{ $this->stats['total_lessons'] }}</p>
                <p class="text-xs text-gray-400">{{ $this->stats['completion_rate'] }}%</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-purple-500">
                <p class="text-xs text-gray-500">📝 {{ __('Quiz-Versuche') }}</p>
                <p class="text-xl font-bold text-purple-600">{{ $this->stats['quiz_attempts'] }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-yellow-500">
                <p class="text-xs text-gray-500">⭐ {{ __('Ø Quiz') }}</p>
                <p class="text-xl font-bold text-yellow-600">{{ $this->stats['avg_score'] }}%</p>
            </div>
        </div>

        <!-- Temps d'étude et meilleur score -->
        <div class="grid grid-cols-1 gap-3 mb-6 sm:grid-cols-2">
            <div class="flex items-center justify-between p-3 rounded-lg bg-gradient-to-r from-blue-50 to-cyan-50">
                <div class="flex items-center gap-2">
                    <x-icon name="o-clock" class="w-5 h-5 text-blue-600" />
                    <div>
                        <p class="text-xs text-gray-500">{{ __('Temps d\'étude total') }}</p>
                        <p class="text-lg font-bold text-blue-700">{{ $this->getFormattedStudyTime($this->stats['total_study_time']) }}</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between p-3 rounded-lg bg-gradient-to-r from-orange-50 to-yellow-50">
                <div class="flex items-center gap-2">
                    <x-icon name="o-trophy" class="w-5 h-5 text-yellow-600" />
                    <div>
                        <p class="text-xs text-gray-500">{{ __('Meilleur score') }}</p>
                        <p class="text-lg font-bold text-yellow-700">{{ $this->stats['best_score'] }}%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enrolled Courses - Version responsive -->
        <div class="overflow-hidden bg-white shadow-sm rounded-xl">
            <div class="flex items-center gap-2 p-4 border-b bg-gray-50">
                <x-icon name="o-academic-cap" class="w-5 h-5 text-[#FF6B35]" />
                <h2 class="font-semibold text-gray-900">{{ __('Eingeschriebene Kurse') }}</h2>
                <span class="text-sm text-gray-500">({{ $this->courses->count() }})</span>
            </div>

            @if($this->courses->count() > 0)
                <!-- Version Desktop: Tableau -->
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-gray-50">
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Kurs') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">{{ __('Fortschritt') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">{{ __('Lektionen') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Letzte Aktivität') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">{{ __('Aktionen') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->courses as $course)
                            <tr class="transition border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $course->title }}</p>
                                        <p class="text-xs text-gray-400">{{ $course->level ?? 'A1' }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold {{ $this->getProgressTextColor($course->progress) }}">
                                            {{ $course->progress }}%
                                        </span>
                                        <div class="w-20 h-1.5 bg-gray-200 rounded-full">
                                            <div class="h-1.5 rounded-full {{ $this->getProgressColor($course->progress) }}"
                                                 style="width: {{ $course->progress }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    {{ $course->completed_lessons }}/{{ $course->total_lessons }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-500">
                                        {{ $course->last_activity ? $course->last_activity->diffForHumans() : '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('teacher.courses.edit', $course) }}"
                                       class="px-2 py-1 text-sm text-[#FF6B35] hover:bg-orange-50 rounded-lg transition">
                                        {{ __('Anzeigen') }}
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Version Mobile: Cartes -->
                <div class="divide-y divide-gray-100 md:hidden">
                    @foreach($this->courses as $course)
                    <div class="p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">{{ $course->title }}</h3>
                                <p class="text-xs text-gray-400">{{ $course->level ?? 'A1' }}</p>
                            </div>
                            <a href="{{ route('teacher.courses.edit', $course) }}"
                               class="text-xs text-[#FF6B35] hover:underline">
                                {{ __('Anzeigen') }} →
                            </a>
                        </div>

                        <!-- Progression -->
                        <div class="mb-2">
                            <div class="flex justify-between mb-1 text-xs">
                                <span class="text-gray-500">{{ __('Fortschritt') }}</span>
                                <span class="font-medium {{ $this->getProgressTextColor($course->progress) }}">
                                    {{ $course->progress }}%
                                </span>
                            </div>
                            <div class="w-full h-1.5 bg-gray-200 rounded-full">
                                <div class="h-1.5 rounded-full {{ $this->getProgressColor($course->progress) }}"
                                     style="width: {{ $course->progress }}%"></div>
                            </div>
                        </div>

                        <!-- Infos supplémentaires -->
                        <div class="flex justify-between mt-2 text-xs text-gray-500">
                            <span>📚 {{ $course->completed_lessons }}/{{ $course->total_lessons }} Lektionen</span>
                            <span>🕐 {{ $course->last_activity ? $course->last_activity->diffForHumans() : '-' }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <x-icon name="o-academic-cap" class="w-16 h-16 mx-auto mb-3 text-gray-300" />
                    <p class="text-gray-500">{{ __('Keine Kurse eingeschrieben') }}</p>
                    <p class="mt-1 text-sm text-gray-400">{{ __('Dieser Student ist noch in keinem Kurs eingeschrieben.') }}</p>
                </div>
            @endif
        </div>

        <!-- Note MVP -->
        <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">MVP Version</p>
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('Prochaines fonctionnalités : suivi détaillé des progrès, messagerie directe, et analyses individuelles.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
