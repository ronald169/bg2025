<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Enrollment;
use App\Models\Progress;
use App\Models\StudySession;
use App\Models\QuizAttempt;
use Mary\Traits\Toast;

new
#[Title('My Learning Progress - German Learning')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public function getUserProperty()
    {
        return auth()->user();
    }

    public function getCoursesProperty()
    {
        return Enrollment::where('user_id', $this->user->id)
            ->with(['course' => function($q) {
                $q->withCount('lessons');
            }])
            ->get()
            ->map(function ($enrollment) {
                $course = $enrollment->course;
                $course->progress = $enrollment->progress;
                $course->enrolled_at = $enrollment->enrolled_at;
                return $course;
            });
    }

    public function getTotalCoursesProperty()
    {
        return $this->courses->count();
    }

    public function getCompletedCoursesProperty()
    {
        return $this->courses->filter(fn($course) => $course->progress >= 100)->count();
    }

    public function getAverageProgressProperty()
    {
        $total = $this->totalCourses;
        if ($total === 0) return 0;
        return round($this->courses->avg('progress'));
    }

    public function getTotalStudyTimeProperty()
    {
        return StudySession::where('user_id', $this->user->id)->sum('duration_minutes');
    }

    public function getTotalQuizzesTakenProperty()
    {
        return QuizAttempt::where('user_id', $this->user->id)->count();
    }

    public function getAverageQuizScoreProperty()
    {
        return round(QuizAttempt::where('user_id', $this->user->id)->avg('score') ?? 0, 1);
    }

    public function getTotalPointsProperty()
    {
        return $this->user->total_points ?? 0;
    }

    public function getCompletedLessonsCountProperty()
    {
        return Progress::where('user_id', $this->user->id)
            ->where('is_completed', true)
            ->count();
    }

    public function getCertificatesCountProperty()
    {
        // À adapter selon votre système de certificats
        return 0;
    }

    public function getProgressColor($progress): string
    {
        if ($progress >= 80) return 'bg-success';
        if ($progress >= 50) return 'bg-primary';
        if ($progress >= 20) return 'bg-warning';
        return 'bg-gray-400';
    }

    public function formatDuration($minutes): string
    {
        if ($minutes < 60) return "{$minutes} min";
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
    }

    public function render()
    {
        return $this->view([
            'user'                   => $this->user,
            'courses'                => $this->courses,
            'totalCourses'           => $this->totalCourses,
            'completedCourses'       => $this->completedCourses,
            'averageProgress'        => $this->averageProgress,
            'totalStudyTime'         => $this->totalStudyTime,
            'totalQuizzesTaken'      => $this->totalQuizzesTaken,
            'averageQuizScore'       => $this->averageQuizScore,
            'totalPoints'            => $this->totalPoints,
            'completedLessonsCount'  => $this->completedLessonsCount,
            'certificatesCount'      => $this->certificatesCount,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-6xl px-3 mx-auto md:px-4">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold md:text-3xl">📊 {{ __('My Learning Progress') }}</h1>
            <p class="mt-1 text-base-content/70">{{ __('Track your progress in learning German') }}</p>
        </div>

        {{-- Global Statistics --}}
        <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-4">
            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-primary">{{ $totalCourses }}</div>
                <div class="text-sm text-base-content/70">{{ __('Courses') }}</div>
                <div class="text-xs text-base-content/50">{{ $completedCourses }} {{ __('completed') }}</div>
            </x-card>

            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-primary">{{ $averageProgress }}%</div>
                <div class="text-sm text-base-content/70">{{ __('Average progress') }}</div>
                <div class="text-xs text-base-content/50">{{ __('across all courses') }}</div>
            </x-card>

            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-primary">{{ $this->formatDuration($totalStudyTime) }}</div>
                <div class="text-sm text-base-content/70">{{ __('Study time') }}</div>
                <div class="text-xs text-base-content/50">{{ __('total') }}</div>
            </x-card>

            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-primary">{{ $averageQuizScore }}%</div>
                <div class="text-sm text-base-content/70">{{ __('Average quiz') }}</div>
                <div class="text-xs text-base-content/50">{{ $totalQuizzesTaken }} {{ __('quizzes taken') }}</div>
            </x-card>
        </div>

        {{-- Certificates Section (if any) --}}
        @if($certificatesCount > 0)
            <div class="p-4 mb-8 border rounded-xl bg-gradient-to-r from-warning/10 to-accent/10 border-warning/20">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-warning">
                            <x-icon name="o-trophy" class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h3 class="font-semibold">{{ __('Certificates earned') }}</h3>
                            <p class="text-sm text-base-content/70">{{ __('You have earned :count certificate(s)', ['count' => $certificatesCount]) }}</p>
                        </div>
                    </div>
                    <x-button label="{{ __('View certificates') }}" icon="o-document" class="btn-outline btn-sm" />
                </div>
            </div>
        @endif

        {{-- Course List --}}
        <div class="space-y-4">
            <h2 class="text-xl font-bold">📚 {{ __('My Courses') }}</h2>

            @if($courses->count() > 0)
                @foreach($courses as $course)
                    <x-card class="transition hover:shadow-md">
                        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="font-semibold">{{ $course->title }}</h3>
                                    @if($course->progress >= 100)
                                        <x-badge value="{{ __('Completed') }}" icon="o-check-circle" class="badge-success badge-soft" />
                                    @endif
                                </div>
                                <div class="mb-2">
                                    <div class="flex justify-between mb-1 text-sm">
                                        <span class="text-base-content/70">{{ __('Progress') }}</span>
                                        <span class="font-medium text-primary">{{ round($course->progress) }}%</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-base-200">
                                        <div class="h-2 rounded-full transition-all duration-300 {{ $this->getProgressColor($course->progress) }}" style="width: {{ $course->progress }}%"></div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-4 mt-2 text-xs text-base-content/60">
                                    <span class="flex items-center gap-1">
                                        <x-icon name="o-book-open" class="w-3 h-3" />
                                        {{ $course->lessons_count ?? 0 }} {{ __('lessons') }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <x-icon name="o-calendar" class="w-3 h-3" />
                                        {{ __('Started') }}: {{ \Carbon\Carbon::parse($course->enrolled_at)->format('d.m.Y') }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                @if($course->progress >= 100)
                                    <x-button label="{{ __('Certificate') }}" icon="o-trophy" class="btn-outline btn-sm" />
                                @endif
                                <x-button :label="$course->progress >= 100 ? __('Review') : __('Continue')" icon="o-play-circle" link="{{ route('student.course.show', $course) }}" class="btn-primary btn-sm" />
                            </div>
                        </div>
                    </x-card>
                @endforeach
            @else
                <x-card class="py-12 text-center">
                    <x-icon name="o-book-open" class="w-16 h-16 mx-auto mb-3 text-base-content/30" />
                    <h3 class="mb-2 text-lg font-semibold">{{ __('No courses yet') }}</h3>
                    <p class="mb-4 text-base-content/60">{{ __('You are not enrolled in any course yet.') }}</p>
                    <x-button link="{{ route('student.catalog') }}" label="{{ __('Discover courses') }}" class="btn-primary" />
                </x-card>
            @endif
        </div>
    </div>
</div>
