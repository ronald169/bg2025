<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Course;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\QuizAttempt;
use App\Models\Message;
use App\Models\Lesson;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new
#[Title('Teacher Dashboard')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    // Getters (remplacent #[Computed])
    public function getUserProperty()
    {
        return Auth::user();
    }

    public function getStatsProperty()
    {
        $courses = $this->user->coursesTaught();
        $courseIds = $courses->pluck('id');

        $totalStudents = Enrollment::whereIn('course_id', $courseIds)->distinct('user_id')->count('user_id');
        $totalLessons = Lesson::whereIn('course_id', $courseIds)->count();
        $totalQuizzes = Lesson::whereIn('course_id', $courseIds)->whereHas('quiz')->count();

        return [
            'total_courses' => $courses->count(),
            'total_students' => $totalStudents,
            'total_lessons' => $totalLessons,
            'total_quizzes' => $totalQuizzes,
            'avg_progress' => $this->calculateAverageProgress(),
        ];
    }

    public function getRecentCoursesProperty()
    {
        return $this->user->coursesTaught()
            ->withCount(['lessons', 'enrollments'])
            ->with('subject')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($course) {
                $avgProgress = Enrollment::where('course_id', $course->id)->avg('progress') ?? 0;
                $course->avg_progress = round($avgProgress);
                return $course;
            });
    }

    public function getTopStudentsProperty()
    {
        $courseIds = $this->user->coursesTaught()->pluck('id');

        return User::whereHas('enrollments', function($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds);
            })
            ->withCount(['enrollments as courses_count' => function($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds);
            }])
            ->orderBy('courses_count', 'desc')
            ->take(5)
            ->get();
    }

    public function getRecentEnrollmentsProperty()
    {
        $courseIds = $this->user->coursesTaught()->pluck('id');

        return Enrollment::whereIn('course_id', $courseIds)
            ->with(['user', 'course'])
            ->latest()
            ->take(10)
            ->get();
    }

    public function getPendingMessagesProperty()
    {
        return Message::where('receiver_id', $this->user->id)
            ->where('is_read', false)
            ->count();
    }

    public function getRecentQuizAttemptsProperty()
    {
        $courseIds = $this->user->coursesTaught()->pluck('id');
        $lessonIds = Lesson::whereIn('course_id', $courseIds)->pluck('id');
        $quizIds = \App\Models\Quiz::whereIn('lesson_id', $lessonIds)->pluck('id');

        return QuizAttempt::whereIn('quiz_id', $quizIds)
            ->with(['user', 'quiz'])
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->take(5)
            ->get()
            ->map(function ($attempt) {
                if ($attempt->quiz && $attempt->quiz->questions) {
                    $totalPoints = $attempt->quiz->questions->sum('points');
                    $attempt->score_percentage = $totalPoints > 0 ? round(($attempt->score / $totalPoints) * 100) : 0;
                } else {
                    $attempt->score_percentage = 0;
                }
                return $attempt;
            });
    }

    private function calculateAverageProgress(): int
    {
        $courseIds = $this->user->coursesTaught()->pluck('id');
        $enrollments = Enrollment::whereIn('course_id', $courseIds)->get();
        if ($enrollments->isEmpty()) return 0;
        return round($enrollments->avg('progress'));
    }

    public function formatDate($date): string
    {
        return $date ? $date->format('d.m.Y') : '-';
    }

    public function getProgressColor($progress): string
    {
        if ($progress >= 80) return 'text-success';
        if ($progress >= 50) return 'text-info';
        if ($progress >= 20) return 'text-warning';
        return 'text-gray-500';
    }

    public function getGreeting(): string
    {
        $hour = now()->hour;
        if ($hour < 12) return __('Good morning');
        if ($hour < 18) return __('Good afternoon');
        return __('Good evening');
    }

    public function render()
    {
        return $this->view([
            'user'                => $this->user,
            'stats'               => $this->stats,
            'recentCourses'       => $this->recentCourses,
            'topStudents'         => $this->topStudents,
            'recentEnrollments'   => $this->recentEnrollments,
            'pendingMessages'     => $this->pendingMessages,
            'recentQuizAttempts'  => $this->recentQuizAttempts,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold md:text-3xl">👨‍🏫 {{ __('Teacher Dashboard') }}</h1>
                <p class="mt-1 text-sm text-base-content/70">{{ $this->getGreeting() }}, {{ $user->name }}! 👋</p>
            </div>
            <x-button label="{{ __('Create new course') }}" icon="o-plus-circle" link="{{ route('teacher.courses.create') }}" class="btn-primary" />
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-4">
            <x-stat title="{{ __('Courses') }}" :value="$stats['total_courses']" icon="o-academic-cap" class="text-primary" />
            <x-stat title="{{ __('Students') }}" :value="$stats['total_students']" icon="o-users" class="text-success" />
            <x-stat title="{{ __('Lessons') }}" :value="$stats['total_lessons']" icon="o-book-open" class="text-info" />
            <x-stat title="{{ __('Avg progress') }}" :value="$stats['avg_progress'] . '%'" icon="o-chart-bar" class="text-warning" />
        </div>

        {{-- Messages Alert --}}
        @if($pendingMessages > 0)
            <div class="p-4 mb-8 border rounded-lg bg-info/10 border-info/20">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-info">
                            <x-icon name="o-envelope" class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <p class="font-medium text-info">{{ __('Unread messages') }}</p>
                            <p class="text-sm text-info/80">{{ __('You have :count new message(s)', ['count' => $pendingMessages]) }}</p>
                        </div>
                    </div>
                    <x-button label="{{ __('View messages') }}" link="{{ route('teacher.messages') }}" class="btn-outline btn-sm" />
                </div>
            </div>
        @endif

        {{-- Recent Courses --}}
        <x-card class="mb-8 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-2 mb-4 border-b">
                <div class="flex items-center gap-2">
                    <x-icon name="o-academic-cap" class="w-5 h-5 text-primary" />
                    <h2 class="font-semibold">{{ __('My Courses') }}</h2>
                </div>
                @if($recentCourses->count() > 0)
                    <a href="{{ route('teacher.courses') }}" class="text-sm text-primary hover:underline">{{ __('All courses') }}</a>
                @endif
            </div>

            @if($recentCourses->count() > 0)
                <div class="space-y-4">
                    @foreach($recentCourses as $course)
                        <div class="flex flex-col justify-between p-4 transition border rounded-lg md:flex-row md:items-center hover:bg-base-200">
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <h4 class="font-semibold">{{ $course->title }}</h4>
                                    <x-badge :value="$course->subject->name ?? __('German')" class="badge-soft badge-neutral" />
                                </div>
                                <div class="flex flex-wrap gap-4 text-sm text-base-content/70">
                                    <span class="flex items-center gap-1"><x-icon name="o-book-open" class="w-4 h-4" />{{ $course->lessons_count }} {{ __('lessons') }}</span>
                                    <span class="flex items-center gap-1"><x-icon name="o-users" class="w-4 h-4" />{{ $course->enrollments_count }} {{ __('students') }}</span>
                                    <span class="flex items-center gap-1"><x-icon name="o-chart-bar" class="w-4 h-4" />{{ __('Avg progress') }}: {{ $course->avg_progress }}%</span>
                                </div>
                                <div class="w-32 mt-2">
                                    <div class="w-full h-1.5 bg-base-200 rounded-full">
                                        <div class="h-1.5 rounded-full bg-primary" style="width: {{ $course->avg_progress }}%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-2 mt-3 md:mt-0">
                                <x-button icon="o-eye" class="btn-ghost btn-sm" tooltip-left="{{ __('View') }}" link="{{ route('teacher.courses.edit', $course) }}" />
                                <x-button label="{{ __('Manage') }}" link="{{ route('teacher.lessons.index', $course) }}" class="btn-primary btn-sm" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <x-icon name="o-academic-cap" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <h3 class="mb-2 text-lg font-semibold">{{ __('No courses yet') }}</h3>
                    <p class="mb-4 text-base-content/60">{{ __('Create your first course to get started.') }}</p>
                    <x-button link="{{ route('teacher.courses.create') }}" label="{{ __('Create first course') }}" class="btn-primary" />
                </div>
            @endif
        </x-card>

        {{-- Two Columns --}}
        <div class="grid gap-8 lg:grid-cols-2">
            {{-- Top Students --}}
            <x-card class="shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-trophy" class="w-5 h-5 text-warning" />
                    <h2 class="font-semibold">{{ __('Top Students') }}</h2>
                </div>
                @if($topStudents->count() > 0)
                    <div class="space-y-3">
                        @foreach($topStudents as $index => $student)
                            <div class="flex items-center justify-between p-3 border rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-8 h-8 text-sm font-bold text-white rounded-full bg-gradient-to-r from-primary to-secondary">{{ $index + 1 }}</div>
                                    <div><p class="font-medium">{{ $student->name }}</p><p class="text-xs text-base-content/60">{{ $student->email }}</p></div>
                                </div>
                                <div class="text-right"><p class="font-semibold text-primary">{{ $student->courses_count }}</p><p class="text-xs text-base-content/60">{{ __('courses') }}</p></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center"><x-icon name="o-users" class="w-12 h-12 mx-auto mb-2 text-base-content/30" /><p class="text-base-content/60">{{ __('No students yet') }}</p></div>
                @endif
            </x-card>

            {{-- Recent Enrollments --}}
            <x-card class="shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-user-plus" class="w-5 h-5 text-success" />
                    <h2 class="font-semibold">{{ __('Recent Enrollments') }}</h2>
                </div>
                @if($recentEnrollments->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentEnrollments as $enrollment)
                            <div class="flex items-center justify-between p-3 border rounded-lg">
                                <div><p class="font-medium">{{ $enrollment->user->name }}</p><p class="text-sm text-base-content/60">{{ $enrollment->course->title }}</p></div>
                                <div class="text-right"><p class="text-xs text-base-content/60">{{ $this->formatDate($enrollment->created_at) }}</p><x-badge value="{{ __('Enrolled') }}" class="badge-success badge-soft badge-sm" /></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center"><x-icon name="o-user-group" class="w-12 h-12 mx-auto mb-2 text-base-content/30" /><p class="text-base-content/60">{{ __('No recent enrollments') }}</p></div>
                @endif
            </x-card>
        </div>

        {{-- Recent Quiz Attempts --}}
        @if($recentQuizAttempts->count() > 0)
            <div class="mt-8">
                <x-card class="shadow-sm">
                    <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                        <x-icon name="o-document-text" class="w-5 h-5 text-secondary" />
                        <h2 class="font-semibold">{{ __('Recent Quiz Attempts') }}</h2>
                    </div>
                    <div class="space-y-3">
                        @foreach($recentQuizAttempts as $attempt)
                            <div class="flex items-center justify-between p-3 border rounded-lg">
                                <div><p class="font-medium">{{ $attempt->user->name }}</p><p class="text-sm text-base-content/60">{{ $attempt->quiz->title }}</p></div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold {{ ($attempt->score_percentage ?? 0) >= 70 ? 'text-success' : 'text-error' }}">{{ $attempt->score_percentage ?? 0 }}%</p>
                                    <p class="text-xs text-base-content/60">{{ $this->formatDate($attempt->completed_at) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            </div>
        @endif
    </div>
</div>
