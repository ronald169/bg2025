<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Progress;
use App\Models\StudySession;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new
#[Title('My Dashboard - German Learning')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public string $activeTab = 'overview';

    // Getters (remplacent #[Computed])
    public function getUserProperty()
    {
        return Auth::user();
    }

    public function getStatsProperty()
    {
        $user = $this->user;

        $totalCourses = Enrollment::where('user_id', $user->id)->count();
        $completedLessons = Progress::where('user_id', $user->id)
            ->where('is_completed', true)
            ->count();
        $totalLessons = Progress::where('user_id', $user->id)->count();
        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        $totalStudyTime = StudySession::where('user_id', $user->id)->sum('duration_minutes');
        $weeklyStudyTime = StudySession::where('user_id', $user->id)
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('duration_minutes');

        $quizCount = QuizAttempt::where('user_id', $user->id)->count();
        $avgQuizScore = QuizAttempt::where('user_id', $user->id)->avg('score') ?? 0;

        return [
            'total_courses' => $totalCourses,
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
            'progress' => $progress,
            'total_study_time' => $totalStudyTime,
            'weekly_study_time' => $weeklyStudyTime,
            'quiz_count' => $quizCount,
            'avg_quiz_score' => round($avgQuizScore, 1),
        ];
    }

    public function getRecentCoursesProperty()
    {
        $user = $this->user;

        return Enrollment::where('user_id', $user->id)
            ->with(['course.subject', 'course.teacher', 'course.lessons'])
            ->where('progress', '<', 100)
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($enrollment) {
                $course = $enrollment->course;
                $course->progress = $enrollment->progress;

                $lastProgress = Progress::where('user_id', $this->user->id)
                    ->whereIn('lesson_id', $course->lessons->pluck('id'))
                    ->latest('updated_at')
                    ->first();

                $course->last_accessed = $lastProgress?->updated_at;
                $course->lessons_count = $course->lessons()->count();

                return $course;
            });
    }

    public function getRecentActivityProperty()
    {
        $user = $this->user;

        $lessonActivities = Progress::where('user_id', $user->id)
            ->with(['lesson.course'])
            ->where('is_completed', true)
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(function ($progress) {
                return [
                    'id' => $progress->id,
                    'type' => 'lesson',
                    'title' => $progress->lesson->title,
                    'course_title' => $progress->lesson->course->title,
                    'time' => $progress->updated_at,
                    'icon' => 'o-check-circle',
                    'color' => 'green',
                    'url' => route('student.lesson.show', [
                        'course' => $progress->lesson->course,
                        'lesson' => $progress->lesson
                    ]),
                ];
            });

        $quizActivities = QuizAttempt::where('user_id', $user->id)
            ->with(['quiz.lesson.course', 'quiz.questions'])
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function ($attempt) {
                $totalQuestions = $attempt->quiz->questions->sum('points') ?? 0;
                $percentage = $totalQuestions > 0 ? round(($attempt->score / $totalQuestions) * 100) : 0;

                return [
                    'id' => $attempt->id,
                    'type' => 'quiz',
                    'title' => $attempt->quiz->title,
                    'course_title' => $attempt->quiz->lesson->course->title,
                    'time' => $attempt->created_at,
                    'percentage' => $percentage,
                    'passed' => $percentage >= ($attempt->quiz->passing_score ?? 70),
                    'icon' => 'o-document-text',
                    'color' => $percentage >= 70 ? 'green' : 'orange',
                    'url' => route('student.quiz.results', $attempt->id),
                ];
            });

        return $lessonActivities->concat($quizActivities)
            ->sortByDesc('time')
            ->take(10)
            ->values();
    }

    public function getRecommendedCoursesProperty()
    {
        $user = $this->user;
        $enrolledIds = Enrollment::where('user_id', $user->id)->pluck('course_id');

        return Course::where('is_published', true)
            ->whereNotIn('id', $enrolledIds)
            ->with(['subject', 'teacher'])
            ->withCount('lessons')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->take(4)
            ->get();
    }

    public function getStreakDataProperty()
    {
        $user = $this->user;
        $streak = $user->learningStreak;

        $history = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $hasStudy = StudySession::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->exists();
            $minutes = StudySession::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->sum('duration_minutes');

            $history[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'studied' => $hasStudy,
                'minutes' => $minutes,
            ];
        }

        return [
            'current' => $streak->current_streak ?? 0,
            'longest' => $streak->longest_streak ?? 0,
            'last_study' => $streak->last_study_date,
            'history' => $history,
        ];
    }

    public function getWeeklyProgressDataProperty()
    {
        $user = $this->user;
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $data = [];

        foreach ($days as $index => $day) {
            $date = now()->startOfWeek()->addDays($index);
            $minutes = StudySession::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->sum('duration_minutes');

            $data[] = [
                'day' => $day,
                'minutes' => $minutes,
            ];
        }

        return $data;
    }

    public function formatDuration($minutes): string
    {
        if ($minutes < 60) return "{$minutes} min";
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
    }

    public function getGreeting(): string
    {
        $hour = now()->hour;
        if ($hour < 12) return __('Good morning');
        if ($hour < 18) return __('Good afternoon');
        return __('Good evening');
    }

    public function getMotivationalMessage(): string
    {
        $streak = $this->streakData['current'];

        if ($streak >= 30) return __('Incredible! :days days in a row! You are a role model! 🔥', ['days' => $streak]);
        if ($streak >= 7) return __('Great! :days days in a row! Keep it up! 🌟', ['days' => $streak]);
        if ($streak >= 3) return __('Well done! :days days in a row! Keep going! 💪', ['days' => $streak]);
        if ($streak > 0) return __('Good to see you again! :days day(s) in a row! 📚', ['days' => $streak]);
        return __('Ready for your first German lesson today? 🇩🇪');
    }

    public function render()
    {
        return $this->view([
            'user'               => $this->user,
            'stats'              => $this->stats,
            'recentCourses'      => $this->recentCourses,
            'recentActivity'     => $this->recentActivity,
            'recommendedCourses' => $this->recommendedCourses,
            'streakData'         => $this->streakData,
            'weeklyProgressData' => $this->weeklyProgressData,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Welcome header --}}
        <div class="p-6 mb-8 text-white bg-gradient-to-r from-primary to-secondary rounded-2xl">
            <div class="flex flex-col items-start justify-between md:flex-row md:items-center">
                <div>
                    <h1 class="text-2xl font-bold">{{ $this->getGreeting() }}, {{ $user->name }}! 👋</h1>
                    <p class="mt-1 text-white/80">{{ $this->getMotivationalMessage() }}</p>
                </div>
                <div class="px-4 py-2 mt-4 text-center rounded-lg md:mt-0 bg-white/20">
                    <div class="text-2xl font-bold">{{ $streakData['current'] }}</div>
                    <div class="text-xs">{{ __('Days in a row') }} 🔥</div>
                </div>
            </div>
        </div>

        {{-- Quick stats --}}
        <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-4">
            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-primary">{{ $stats['total_courses'] }}</div>
                <div class="text-sm text-base-content/70">{{ __('Courses') }}</div>
            </x-card>
            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-primary">{{ $stats['completed_lessons'] }}</div>
                <div class="text-sm text-base-content/70">{{ __('Lessons') }}</div>
                <div class="text-xs text-base-content/50">{{ $stats['total_lessons'] }} {{ __('total') }}</div>
            </x-card>
            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-primary">{{ $this->formatDuration($stats['total_study_time']) }}</div>
                <div class="text-sm text-base-content/70">{{ __('Study time') }}</div>
                <div class="text-xs text-base-content/50">{{ __('This week') }}: {{ $this->formatDuration($stats['weekly_study_time']) }}</div>
            </x-card>
            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-primary">{{ round($stats['avg_quiz_score']) }}%</div>
                <div class="text-sm text-base-content/70">{{ __('Quiz average') }}</div>
                <div class="text-xs text-base-content/50">{{ $stats['quiz_count'] }} {{ __('quizzes taken') }}</div>
            </x-card>
        </div>

        {{-- Main grid --}}
        <div class="grid gap-8 lg:grid-cols-3">
            {{-- Left column (2/3) --}}
            <div class="space-y-8 lg:col-span-2">

                {{-- Weekly activity chart --}}
                <x-card title="📊 {{ __('Weekly Activity') }}" class="shadow-sm">
                    <div class="flex items-end justify-between h-32 mb-4">
                        @foreach($weeklyProgressData as $day)
                        <div class="flex flex-col items-center w-10">
                            <div class="relative group">
                                <div class="w-8 rounded-t-lg bg-primary/20" style="height: {{ max(4, $day['minutes'] * 2) }}px">
                                    <div class="w-full transition-all rounded-t-lg bg-primary" style="height: {{ min(100, ($day['minutes'] / 120) * 100) }}%"></div>
                                </div>
                                <div class="absolute hidden px-2 py-1 mb-2 text-xs text-white transform -translate-x-1/2 rounded bg-base-100 bottom-full left-1/2 group-hover:block whitespace-nowrap">
                                    {{ $day['minutes'] }} min
                                </div>
                            </div>
                            <span class="mt-2 text-xs text-base-content/60">{{ $day['day'] }}</span>
                        </div>
                        @endforeach
                    </div>
                    <div class="flex justify-between pt-4 text-sm border-t text-base-content/70">
                        <span>{{ __('Total') }}: {{ $this->formatDuration($stats['weekly_study_time']) }}</span>
                        <span>{{ __('Daily average') }}: {{ $this->formatDuration(round($stats['weekly_study_time'] / 7)) }}</span>
                    </div>
                </x-card>

                {{-- Courses in progress --}}
                <x-card title="📚 {{ __('My Courses') }}" class="shadow-sm">
                    @if($recentCourses->count() > 0)
                        <div class="space-y-4">
                            @foreach($recentCourses as $course)
                                <div class="flex flex-col justify-between p-4 transition border rounded-lg md:flex-row md:items-center hover:bg-base-200">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-base-content">{{ $course->title }}</h4>
                                        <div class="flex flex-wrap items-center gap-3 mt-1 text-sm text-base-content/70">
                                            <span>{{ $course->subject->name ?? 'German' }}</span>
                                            <span>{{ $course->lessons_count }} {{ __('lessons') }}</span>
                                            @if($course->last_accessed)
                                                <span>{{ __('Last accessed') }}: {{ $course->last_accessed->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                        <div class="mt-2">
                                            <div class="flex justify-between mb-1 text-xs">
                                                <span>{{ __('Progress') }}</span>
                                                <span class="font-medium text-primary">{{ round($course->progress) }}%</span>
                                            </div>
                                            <div class="w-full h-2 rounded-full bg-base-200">
                                                <div class="h-2 rounded-full bg-primary" style="width: {{ $course->progress }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 md:mt-0 md:ml-4">
                                        <x-button icon-right="o-arrow-right" label="{{ __('Continue') }}" link="{{ route('student.course.show', $course) }}" class="btn-primary btn-sm" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-8 text-center">
                            <x-icon name="o-academic-cap" class="w-12 h-12 mx-auto text-base-content/30" />
                            <p class="mt-2 text-base-content/60">{{ __('No courses yet') }}</p>
                            <x-button icon-right="o-arrow-right" link="{{ route('student.catalog') }}" label="{{ __('Discover courses') }}" class="mt-4 btn-primary" />
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- Right column (1/3) --}}
            <div class="space-y-8">

                {{-- Streak card --}}
                <x-card class="text-white shadow-sm bg-gradient-to-r from-primary to-secondary">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-white/80">{{ __('Learning streak') }}</p>
                            <p class="text-4xl font-bold">{{ $streakData['current'] }}</p>
                            <p class="text-xs text-white/80">{{ __('days') }}</p>
                        </div>
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-white/20">
                            <x-icon name="o-fire" class="w-8 h-8" />
                        </div>
                    </div>
                    <div class="flex justify-between pt-4 text-sm border-t border-white/30">
                        <span>{{ __('Record') }}: {{ $streakData['longest'] }}</span>
                        <span>{{ __('Last activity') }}: {{ $streakData['last_study']?->diffForHumans() ?? __('Never') }}</span>
                    </div>
                    <div class="grid grid-cols-7 gap-1 mt-4">
                        @foreach($streakData['history'] as $day)
                            <div class="text-center">
                                <div class="w-8 h-8 mx-auto rounded-full flex items-center justify-center text-xs {{ $day['studied'] ? 'bg-white/30 text-white' : 'bg-white/10 text-white/50' }}">
                                    {{ substr($day['day'], 0, 1) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>

                {{-- Recent activity --}}
                <x-card title="🕒 {{ __('Recent Activity') }}" class="shadow-sm">
                    @if($recentActivity->count() > 0)
                        <div class="space-y-3">
                            @foreach($recentActivity->take(5) as $activity)
                                <a href="{{ $activity['url'] }}" class="block p-3 transition rounded-lg hover:bg-base-200">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-{{ $activity['color'] }}/20 flex items-center justify-center">
                                            <x-icon :name="$activity['icon']" class="w-4 h-4 text-{{ $activity['color'] }}/80" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium truncate text-base-content">{{ $activity['title'] }}</p>
                                            <p class="text-xs text-base-content/60">{{ $activity['course_title'] }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-base-content/50">{{ $activity['time']->diffForHumans() }}</p>
                                            @if($activity['type'] === 'quiz')
                                                <p class="text-xs {{ $activity['passed'] ? 'text-success' : 'text-warning' }}">
                                                    {{ $activity['percentage'] }}%
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="py-6 text-center">
                            <x-icon name="o-clock" class="w-10 h-10 mx-auto text-base-content/30" />
                            <p class="mt-2 text-sm text-base-content/60">{{ __('No activities yet') }}</p>
                            <p class="text-xs text-base-content/50">{{ __('Start a lesson!') }}</p>
                        </div>
                    @endif
                </x-card>

                {{-- Recommended courses --}}
                @if($recommendedCourses->count() > 0)
                    <x-card title="💡 {{ __('Recommended for you') }}" class="shadow-sm">
                        <div class="space-y-3">
                            @foreach($recommendedCourses->take(3) as $course)
                                <a href="{{ route('student.course.show', $course) }}" class="block p-3 transition border rounded-lg hover:bg-base-200">
                                    <h4 class="text-sm font-medium text-base-content">{{ $course->title }}</h4>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-base-content/60">{{ $course->subject->name ?? 'German' }}</span>
                                        <span class="text-xs text-primary">{{ $course->lessons_count }} {{ __('lessons') }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <div class="mt-4 text-center">
                            <a href="{{ route('student.catalog') }}" icon-right="o-arrow-right" class="text-sm text-primary hover:underline">
                                {{ __('All courses') }}
                            </a>
                        </div>
                    </x-card>
                @endif
            </div>
        </div>
    </div>
</div>
