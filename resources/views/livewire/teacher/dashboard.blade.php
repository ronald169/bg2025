<?php

use App\Models\Course;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\QuizAttempt;
use App\Models\Message;
use App\Models\Lesson;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Lehrer-Dashboard - Deutsch lernen')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use Toast;

    #[Computed]
    public function user()
    {
        return Auth::user();
    }

    #[Computed]
    public function stats()
    {
        $courses = $this->user->coursesTaught();
        $courseIds = $courses->pluck('id');

        $totalStudents = Enrollment::whereIn('course_id', $courseIds)->distinct('user_id')->count('user_id');
        $totalLessons = Lesson::whereIn('course_id', $courseIds)->count();

        // Compter les quizzes via les leçons
        $totalQuizzes = Lesson::whereIn('course_id', $courseIds)
            ->whereHas('quiz')
            ->count();

        return [
            'total_courses' => $courses->count(),
            'total_students' => $totalStudents,
            'total_lessons' => $totalLessons,
            'total_quizzes' => $totalQuizzes,
            'avg_progress' => $this->calculateAverageProgress(),
        ];
    }

    #[Computed]
    public function recentCourses()
    {
        return $this->user->coursesTaught()
            ->withCount(['lessons', 'enrollments'])
            ->with('subject')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($course) {
                // Calculer la progression moyenne des étudiants pour ce cours
                $avgProgress = Enrollment::where('course_id', $course->id)->avg('progress') ?? 0;
                $course->avg_progress = round($avgProgress);
                return $course;
            });
    }

    #[Computed]
    public function topStudents()
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

    #[Computed]
    public function recentEnrollments()
    {
        $courseIds = $this->user->coursesTaught()->pluck('id');

        return Enrollment::whereIn('course_id', $courseIds)
            ->with(['user', 'course'])
            ->latest()
            ->take(10)
            ->get();
    }

    #[Computed]
    public function pendingMessages()
    {
        return Message::where('receiver_id', $this->user->id)
            ->where('is_read', false)
            ->count();
    }

    #[Computed]
    public function recentQuizAttempts()
    {
        // Récupérer les IDs des leçons des cours de l'enseignant
        $courseIds = $this->user->coursesTaught()->pluck('id');
        $lessonIds = Lesson::whereIn('course_id', $courseIds)->pluck('id');

        // Récupérer les IDs des quizzes liés à ces leçons
        $quizIds = \App\Models\Quiz::whereIn('lesson_id', $lessonIds)->pluck('id');

        return QuizAttempt::whereIn('quiz_id', $quizIds)
            ->with(['user', 'quiz'])
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->take(5)
            ->get()
            ->map(function ($attempt) {
                // Calculer le pourcentage si nécessaire
                if ($attempt->quiz && $attempt->quiz->questions) {
                    $totalPoints = $attempt->quiz->questions->sum('points');
                    if ($totalPoints > 0) {
                        $attempt->score_percentage = round(($attempt->score / $totalPoints) * 100);
                    } else {
                        $attempt->score_percentage = 0;
                    }
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

    public function getFormattedDate($date): string
    {
        if (!$date) return '-';
        return $date->format('d.m.Y');
    }

    public function getProgressColor($progress): string
    {
        if ($progress >= 80) return 'text-green-600';
        if ($progress >= 50) return 'text-blue-600';
        if ($progress >= 20) return 'text-yellow-600';
        return 'text-gray-600';
    }

    public function getGreeting(): string
    {
        $hour = now()->hour;
        if ($hour < 12) return 'Guten Morgen';
        if ($hour < 18) return 'Guten Tag';
        return 'Guten Abend';
    }
}
?>

<div class="py-8">
    <div class="px-4 mx-auto max-w-7xl">

        <!-- Header -->
        <div class="flex flex-col gap-4 mb-8 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">📊 {{ __('Lehrer-Dashboard') }}</h1>
                <p class="mt-1 text-gray-600">
                    {{ $this->getGreeting() }}, {{ $this->user->name }}! 👋
                </p>
            </div>
            <div class="flex gap-3">
                <x-button
                    icon="o-plus-circle"
                    link="{{ route('teacher.courses.create') }}"
                    class="btn-primary">
                    {{ __('Neuen Kurs erstellen') }}
                </x-button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-4">
            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->stats['total_courses'] }}</div>
                <div class="text-sm text-gray-500">{{ __('Kurse') }}</div>
            </x-card>

            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->stats['total_students'] }}</div>
                <div class="text-sm text-gray-500">{{ __('Studenten') }}</div>
            </x-card>

            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->stats['total_lessons'] }}</div>
                <div class="text-sm text-gray-500">{{ __('Lektionen') }}</div>
            </x-card>

            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->stats['avg_progress'] }}%</div>
                <div class="text-sm text-gray-500">{{ __('Ø Fortschritt') }}</div>
            </x-card>
        </div>

        <!-- Messages Alert -->
        @if($this->pendingMessages > 0)
        <div class="p-4 mb-8 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 bg-blue-500 rounded-full">
                        <x-icon name="o-envelope" class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="font-medium text-blue-800">{{ __('Ungelesene Nachrichten') }}</p>
                        <p class="text-sm text-blue-700">
                            {{ __('Sie haben :count neue Nachricht(en)', ['count' => $this->pendingMessages]) }}
                        </p>
                    </div>
                </div>
                <x-button
                    link="{{ route('teacher.messages') }}"
                    label="{{ __('Nachrichten anzeigen') }} →"
                    class="btn-outline btn-sm" />
            </div>
        </div>
        @endif

        <!-- Recent Courses -->
        <x-card class="mb-8 border-0 shadow-sm">
            <div class="flex items-center justify-between pb-2 mb-4 border-b">
                <div class="flex items-center gap-2">
                    <x-icon name="o-academic-cap" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Meine Kurse') }}</h2>
                </div>
                @if($this->recentCourses->count() > 0)
                    <a href="{{ route('teacher.courses') }}" class="text-sm text-[#FF6B35] hover:underline">
                        {{ __('Alle anzeigen') }} →
                    </a>
                @endif
            </div>

            @if($this->recentCourses->count() > 0)
                <div class="space-y-4">
                    @foreach($this->recentCourses as $course)
                    <div class="flex flex-col justify-between p-4 transition border rounded-lg md:flex-row md:items-center hover:bg-gray-50">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="font-semibold text-gray-900">{{ $course->title }}</h4>
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">
                                    {{ $course->subject->name ?? 'Deutsch' }}
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                                <span class="flex items-center gap-1">
                                    <x-icon name="o-book-open" class="w-4 h-4" />
                                    {{ $course->lessons_count }} {{ __('Lektionen') }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <x-icon name="o-users" class="w-4 h-4" />
                                    {{ $course->enrollments_count }} {{ __('Studenten') }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <x-icon name="o-chart-bar" class="w-4 h-4" />
                                    {{ __('Ø Fortschritt') }}: {{ $course->avg_progress }}%
                                </span>
                            </div>
                            <div class="w-32 mt-2">
                                <div class="w-full h-1.5 bg-gray-200 rounded-full">
                                    <div class="h-1.5 rounded-full bg-[#FF6B35]" style="width: {{ $course->avg_progress }}%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-3 md:mt-0">
                            <x-button
                                icon="o-eye"
                                link="{{ route('teacher.courses.edit', $course) }}"
                                size="sm"
                                class="btn-ghost btn-sm"
                                tooltip="{{ __('Ansehen') }}" />
                            <x-button
                                label="{{ __('Verwalten') }}"
                                link="{{ route('teacher.courses.edit', $course) }}"
                                size="sm"
                                class="btn-primary btn-sm" />
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <x-icon name="o-academic-cap" class="w-16 h-16 mx-auto mb-3 text-gray-300" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('Noch keine Kurse') }}</h3>
                    <p class="mb-4 text-gray-500">{{ __('Erstellen Sie Ihren ersten Kurs, um zu beginnen.') }}</p>
                    <x-button link="{{ route('teacher.courses.create') }}" class="btn-primary">
                        {{ __('Ersten Kurs erstellen →') }}
                    </x-button>
                </div>
            @endif
        </x-card>

        <!-- Two Columns -->
        <div class="grid gap-8 lg:grid-cols-2">
            <!-- Top Students -->
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-trophy" class="w-5 h-5 text-yellow-500" />
                    <h2 class="font-semibold text-gray-900">{{ __('Beste Studenten') }}</h2>
                </div>

                @if($this->topStudents->count() > 0)
                    <div class="space-y-3">
                        @foreach($this->topStudents as $index => $student)
                        <div class="flex items-center justify-between p-3 border rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-sm">
                                    {{ $index + 1 }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $student->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $student->email }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-[#FF6B35]">{{ $student->courses_count }}</p>
                                <p class="text-xs text-gray-500">{{ __('Kurse') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center">
                        <x-icon name="o-users" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                        <p class="text-gray-500">{{ __('Noch keine Studenten') }}</p>
                    </div>
                @endif
            </x-card>

            <!-- Recent Enrollments -->
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-user-plus" class="w-5 h-5 text-green-500" />
                    <h2 class="font-semibold text-gray-900">{{ __('Letzte Anmeldungen') }}</h2>
                </div>

                @if($this->recentEnrollments->count() > 0)
                    <div class="space-y-3">
                        @foreach($this->recentEnrollments as $enrollment)
                        <div class="flex items-center justify-between p-3 border rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900">{{ $enrollment->user->name }}</p>
                                <p class="text-sm text-gray-500">{{ $enrollment->course->title }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">{{ $this->getFormattedDate($enrollment->created_at) }}</p>
                                <span class="inline-block px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded-full">
                                    {{ __('Eingeschrieben') }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center">
                        <x-icon name="o-user-group" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                        <p class="text-gray-500">{{ __('Keine neuen Anmeldungen') }}</p>
                    </div>
                @endif
            </x-card>
        </div>

        <!-- Recent Quiz Attempts -->
        @if($this->recentQuizAttempts->count() > 0)
        <div class="mt-8">
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-document-text" class="w-5 h-5 text-purple-500" />
                    <h2 class="font-semibold text-gray-900">{{ __('Letzte Quiz-Versuche') }}</h2>
                </div>

                <div class="space-y-3">
                    @foreach($this->recentQuizAttempts as $attempt)
                    <div class="flex items-center justify-between p-3 border rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">{{ $attempt->user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $attempt->quiz->title }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold {{ ($attempt->score_percentage ?? 0) >= 70 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $attempt->score_percentage ?? 0 }}%
                            </p>
                            <p class="text-xs text-gray-500">{{ $this->getFormattedDate($attempt->completed_at) }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-card>
        </div>
        @endif

        <!-- Note MVP -->
        <div class="p-4 mt-8 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="font-medium text-blue-800">MVP Version</p>
                    <p class="text-sm text-blue-700">{{ __('Prochaines fonctionnalités : analyses détaillées, suivi des progrès des étudiants, résultats de quiz et outils de communication.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
