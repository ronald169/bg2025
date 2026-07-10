<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\User;
use App\Models\Progress;
use App\Models\QuizAttempt;
use App\Models\StudySession;
use Mary\Traits\Toast;

new
#[Title('Student Details - Teacher')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public User $user;

    // Getters
    public function getCoursesProperty()
    {
        return $this->user->enrollments()
            ->with('course')
            ->get()
            ->map(function ($enrollment) {
                $course = $enrollment->course;
                $course->progress = $enrollment->progress;
                $course->enrolled_at = $enrollment->created_at;

                $lastProgress = Progress::where('user_id', $this->user->id)
                    ->whereHas('lesson', fn($q) => $q->where('course_id', $course->id))
                    ->latest('updated_at')
                    ->first();
                $course->last_activity = $lastProgress?->updated_at;

                $course->completed_lessons = Progress::where('user_id', $this->user->id)
                    ->whereHas('lesson', fn($q) => $q->where('course_id', $course->id))
                    ->where('is_completed', true)
                    ->count();

                $course->total_lessons = $course->lessons()->count();
                return $course;
            });
    }

    public function getStatsProperty()
    {
        $totalLessons = Progress::where('user_id', $this->user->id)->count();
        $completedLessons = Progress::where('user_id', $this->user->id)->where('is_completed', true)->count();
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
            'total_courses'      => $this->courses->count(),
            'total_lessons'      => $totalLessons,
            'completed_lessons'  => $completedLessons,
            'completion_rate'    => $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0,
            'quiz_attempts'      => $quizAttempts->count(),
            'avg_score'          => $avgScore,
            'best_score'         => $bestScore,
            'total_study_time'   => $totalStudyTime,
        ];
    }

    public function formatDuration($minutes): string
    {
        if ($minutes < 60) return "{$minutes} min";
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
    }

    public function getProgressColor($progress): string
    {
        if ($progress >= 80) return 'bg-success';
        if ($progress >= 50) return 'bg-primary';
        if ($progress >= 20) return 'bg-warning';
        return 'bg-gray-400';
    }

    public function getProgressTextColor($progress): string
    {
        if ($progress >= 80) return 'text-success';
        if ($progress >= 50) return 'text-primary';
        if ($progress >= 20) return 'text-warning';
        return 'text-gray-500';
    }

    public function render()
    {
        return $this->view([
            'courses' => $this->courses,
            'stats'   => $this->stats,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-5xl px-3 mx-auto md:px-4">

        {{-- Navigation --}}
        <div class="mb-5">
            <a href="{{ route('teacher.students') }}" wire:navigate class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to students') }}
            </a>
        </div>

        {{-- Header --}}
        <div class="p-4 mb-6 shadow-sm bg-base-100 rounded-xl md:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="flex justify-center sm:justify-start">
                    <div class="flex items-center justify-center w-20 h-20 text-3xl font-bold text-white rounded-full md:w-24 md:h-24 bg-gradient-to-r from-primary to-secondary md:text-4xl">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                </div>
                <div class="flex-1 text-center sm:text-left">
                    <h1 class="text-xl font-bold md:text-2xl">{{ $user->name }}</h1>
                    <p class="text-sm md:text-base text-base-content/70">{{ $user->email }}</p>
                    <div class="flex flex-wrap items-center justify-center gap-2 mt-2 sm:justify-start">
                        <x-badge :value="$user->german_level ?? 'A1' . ' - German'" class="badge-neutral badge-soft" />
                        <span class="text-xs text-base-content/50">{{ __('Member since') }} {{ $user->created_at->format('d.m.Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-3 mb-6 md:grid-cols-4">
            <x-stat title="{{ __('Courses') }}" :value="$stats['total_courses']" icon="o-academic-cap" class="text-primary" />
            <x-stat title="{{ __('Lessons') }}" :value="$stats['completed_lessons'] . '/' . $stats['total_lessons']" icon="o-book-open" class="text-success" />
            <x-stat title="{{ __('Quiz attempts') }}" :value="$stats['quiz_attempts']" icon="o-document-text" class="text-secondary" />
            <x-stat title="{{ __('Avg quiz') }}" :value="round($stats['avg_score']) . '%'" icon="o-chart-bar" class="text-warning" />
        </div>

        {{-- Extra info row --}}
        <div class="grid grid-cols-1 gap-3 mb-6 sm:grid-cols-2">
            <div class="flex items-center justify-between p-3 rounded-lg bg-gradient-to-r from-info/10 to-cyan-50">
                <div class="flex items-center gap-2">
                    <x-icon name="o-clock" class="w-5 h-5 text-info" />
                    <div>
                        <p class="text-xs text-base-content/60">{{ __('Total study time') }}</p>
                        <p class="text-lg font-bold text-info">{{ $this->formatDuration($stats['total_study_time']) }}</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between p-3 rounded-lg bg-gradient-to-r from-warning/10 to-yellow-50">
                <div class="flex items-center gap-2">
                    <x-icon name="o-trophy" class="w-5 h-5 text-warning" />
                    <div>
                        <p class="text-xs text-base-content/60">{{ __('Best score') }}</p>
                        <p class="text-lg font-bold text-warning">{{ round($stats['best_score']) }}%</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Enrolled Courses --}}
        <x-card class="shadow-sm">
            <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                <x-icon name="o-academic-cap" class="w-5 h-5 text-primary" />
                <h2 class="font-semibold">{{ __('Enrolled courses') }}</h2>
                <x-badge :value="$courses->count()" class="badge-soft badge-neutral" />
            </div>

            @if($courses->count() > 0)
                {{-- Desktop table --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-base-200">
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Course') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Progress') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Lessons') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Last activity') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($courses as $course)
                                <tr class="transition border-b hover:bg-base-200">
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-sm font-medium">{{ $course->title }}</p>
                                            <p class="text-xs text-base-content/60">{{ $course->level ?? 'A1' }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-2">
                                            <span class="text-sm font-semibold {{ $this->getProgressTextColor($course->progress) }}">{{ round($course->progress) }}%</span>
                                            <div class="w-20 h-1.5 bg-base-200 rounded-full">
                                                <div class="h-1.5 rounded-full {{ $this->getProgressColor($course->progress) }}" style="width: {{ $course->progress }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center">{{ $course->completed_lessons }}/{{ $course->total_lessons }} </td>
                                    <td class="px-4 py-3 text-sm text-base-content/60">{{ $course->last_activity ? $course->last_activity->diffForHumans() : '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <x-button label="{{ __('View') }}" link="{{ route('teacher.courses.edit', $course) }}" class="btn-outline btn-sm" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards --}}
                <div class="divide-y divide-base-200 md:hidden">
                    @foreach($courses as $course)
                        <div class="p-3">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <p class="font-semibold">{{ $course->title }}</p>
                                    <p class="text-xs text-base-content/60">{{ $course->level ?? 'A1' }}</p>
                                </div>
                                <x-button label="{{ __('View') }}" link="{{ route('teacher.courses.edit', $course) }}" class="btn-outline btn-xs" />
                            </div>
                            <div class="mb-2">
                                <div class="flex justify-between mb-1 text-xs">
                                    <span class="text-base-content/60">{{ __('Progress') }}</span>
                                    <span class="font-medium {{ $this->getProgressTextColor($course->progress) }}">{{ round($course->progress) }}%</span>
                                </div>
                                <div class="w-full h-1.5 bg-base-200 rounded-full">
                                    <div class="h-1.5 rounded-full {{ $this->getProgressColor($course->progress) }}" style="width: {{ $course->progress }}%"></div>
                                </div>
                            </div>
                            <div class="flex justify-between text-xs text-base-content/50">
                                <span>📚 {{ $course->completed_lessons }}/{{ $course->total_lessons }} {{ __('lessons') }}</span>
                                <span>🕐 {{ $course->last_activity ? $course->last_activity->diffForHumans() : '-' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <x-icon name="o-academic-cap" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <p class="text-base-content/60">{{ __('No courses enrolled') }}</p>
                    <p class="text-sm text-base-content/50">{{ __('This student is not enrolled in any course yet.') }}</p>
                </div>
            @endif
        </x-card>
    </div>
</div>
