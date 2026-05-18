<?php

use App\Models\User;
use App\Models\Progress;
use App\Models\QuizAttempt;
use App\Models\Review;
use App\Models\Enrollment;
use App\Models\StudySession;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Benutzerdetails - Admin')]
#[Layout('components.layouts.dashboard-admin')]
class extends Component {
    use Toast;

    public User $user;

    #[Computed]
    public function stats()
    {
        return [
            'courses_enrolled' => $this->user->coursesEnrolled()->count(),
            'courses_taught' => $this->user->coursesTaught()->count(),
            'completed_lessons' => Progress::where('user_id', $this->user->id)
                ->where('is_completed', true)->count(),
            'total_lessons' => Progress::where('user_id', $this->user->id)->count(),
            'quiz_attempts' => QuizAttempt::where('user_id', $this->user->id)->count(),
            'quiz_passed' => QuizAttempt::where('user_id', $this->user->id)
                ->where('is_passed', true)->count(),
            'reviews_written' => Review::where('user_id', $this->user->id)->count(),
            'total_study_time' => StudySession::where('user_id', $this->user->id)->sum('duration_minutes'),
            'active_enrollments' => Enrollment::where('user_id', $this->user->id)
                ->where('status', 'active')->count(),
        ];
    }

    #[Computed]
    public function recentCourses()
    {
        return $this->user->coursesEnrolled()
            ->with('subject')
            ->latest('enrollments.created_at')
            ->take(5)
            ->get()
            ->map(function ($course) {
                $enrollment = $course->pivot;
                $course->progress = $enrollment->progress;
                $course->enrolled_at = $enrollment->created_at;
                return $course;
            });
    }

    #[Computed]
    public function recentActivity()
    {
        $recentQuizzes = QuizAttempt::where('user_id', $this->user->id)
            ->with('quiz')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($attempt) {
                return [
                    'type' => 'quiz',
                    'title' => $attempt->quiz->title,
                    'date' => $attempt->created_at,
                    'score' => $attempt->score,
                    'is_passed' => $attempt->passed,
                    'icon' => 'o-document-text',
                    'color' => $attempt->passed ? 'green' : 'red',
                ];
            });

        $recentReviews = Review::where('user_id', $this->user->id)
            ->with('course')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($review) {
                return [
                    'type' => 'review',
                    'title' => $review->course->title,
                    'date' => $review->created_at,
                    'rating' => $review->rating,
                    'icon' => 'o-star',
                    'color' => 'yellow',
                ];
            });

        return $recentQuizzes->concat($recentReviews)
            ->sortByDesc('date')
            ->take(10)
            ->values();
    }

    public function getFormattedStudyTime($minutes): string
    {
        if ($minutes < 60) return "{$minutes} min";
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
    }

    public function getRoleBadgeClass($role): string
    {
        return match($role) {
            'admin' => 'bg-red-100 text-red-700',
            'teacher' => 'bg-blue-100 text-blue-700',
            'student' => 'bg-green-100 text-green-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function getRoleIcon($role): string
    {
        return match($role) {
            'admin' => 'o-shield-check',
            'teacher' => 'custom.chalkboard-teacher',
            'student' => 'o-academic-cap',
            default => 'o-user',
        };
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
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        <!-- Navigation -->
        <div class="mb-5">
            <a href="{{ route('admin.users') }}" class="inline-flex items-center gap-1 text-sm text-[#FF6B35] hover:text-[#E55A2A] transition">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Zurück zu den Benutzern') }}
            </a>
        </div>

        <!-- Header -->
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
                    <div class="flex flex-wrap items-center justify-center gap-2 mb-1 sm:justify-start">
                        <h1 class="text-xl font-bold text-gray-900 md:text-2xl">{{ $user->name }}</h1>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full {{ $this->getRoleBadgeClass($user->role) }}">
                            <x-icon :name="$this->getRoleIcon($user->role)" class="w-3 h-3" />
                            {{ ucfirst($user->role) }}
                        </span>
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                            {{ $user->status === 'active' ? 'Aktiv' : 'Inaktiv' }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                    <div class="flex flex-wrap items-center justify-center gap-3 mt-2 text-xs text-gray-400 sm:justify-start">
                        <span class="flex items-center gap-1">
                            <x-icon name="o-calendar" class="w-3 h-3" />
                            {{ __('Mitglied seit') }} {{ $user->created_at->format('d.m.Y') }}
                        </span>
                        @if($user->german_level)
                        <span class="flex items-center gap-1">
                            <x-icon name="o-language" class="w-3 h-3" />
                            {{ __('Niveau') }}: {{ $user->german_level }}
                        </span>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-center gap-2 sm:justify-end">
                    <a href="{{ route('admin.users.edit', $user) }}" class="p-2 text-gray-400 transition rounded-lg hover:text-orange-600" title="Bearbeiten">
                        <x-icon name="o-pencil" class="w-5 h-5" />
                    </a>
                    @if($user->status === 'active')
                        <button wire:click="toggleStatus" class="p-2 text-gray-400 transition rounded-lg hover:text-yellow-600" title="Deaktivieren">
                            <x-icon name="o-eye-slash" class="w-5 h-5" />
                        </button>
                    @else
                        <button wire:click="toggleStatus" class="p-2 text-gray-400 transition rounded-lg hover:text-green-600" title="Aktivieren">
                            <x-icon name="o-eye" class="w-5 h-5" />
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-3 lg:grid-cols-5">
            <div class="bg-white rounded-lg p-3 text-center shadow-sm border-l-4 border-l-[#FF6B35]">
                <p class="text-xs text-gray-500">📚 {{ __('Kurse') }}</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->stats['courses_enrolled'] }}</p>
                @if($user->role === 'teacher')
                    <p class="text-xs text-gray-400">{{ __('davon unterrichtet') }}: {{ $this->stats['courses_taught'] }}</p>
                @endif
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-green-500">
                <p class="text-xs text-gray-500">✅ {{ __('Lektionen') }}</p>
                <p class="text-xl font-bold text-green-600">{{ $this->stats['completed_lessons'] }}/{{ $this->stats['total_lessons'] }}</p>
                <p class="text-xs text-gray-400">{{ __('abgeschlossen') }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-purple-500">
                <p class="text-xs text-gray-500">📝 {{ __('Quiz-Versuche') }}</p>
                <p class="text-xl font-bold text-purple-600">{{ $this->stats['quiz_attempts'] }}</p>
                <p class="text-xs text-gray-400">{{ __('davon bestanden') }}: {{ $this->stats['quiz_passed'] }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-blue-500">
                <p class="text-xs text-gray-500">⭐ {{ __('Bewertungen') }}</p>
                <p class="text-xl font-bold text-blue-600">{{ $this->stats['reviews_written'] }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-yellow-500">
                <p class="text-xs text-gray-500">⏱️ {{ __('Lernzeit') }}</p>
                <p class="text-lg font-bold text-yellow-600">{{ $this->getFormattedStudyTime($this->stats['total_study_time']) }}</p>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="mb-6 overflow-hidden bg-white shadow-sm rounded-xl">
            <div class="flex items-center gap-2 p-4 border-b bg-gray-50">
                <x-icon name="o-user-circle" class="w-5 h-5 text-[#FF6B35]" />
                <h2 class="font-semibold text-gray-900">{{ __('Persönliche Informationen') }}</h2>
            </div>
            <div class="p-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="flex flex-col">
                        <p class="text-xs text-gray-500">{{ __('Vollständiger Name') }}</p>
                        <p class="font-medium text-gray-900">{{ $user->name }}</p>
                    </div>
                    <div class="flex flex-col">
                        <p class="text-xs text-gray-500">{{ __('E-Mail-Adresse') }}</p>
                        <p class="font-medium text-gray-900">{{ $user->email }}</p>
                    </div>
                    <div class="flex flex-col">
                        <p class="text-xs text-gray-500">{{ __('Telefonnummer') }}</p>
                        <p class="font-medium text-gray-900">{{ $user->phone ?? '—' }}</p>
                    </div>
                    <div class="flex flex-col">
                        <p class="text-xs text-gray-500">{{ __('Geburtsdatum') }}</p>
                        <p class="font-medium text-gray-900">{{ $user->date_of_birth?->format('d.m.Y') ?? '—' }}</p>
                    </div>
                    @if($user->role === 'student')
                    <div class="flex flex-col">
                        <p class="text-xs text-gray-500">{{ __('Deutschniveau') }}</p>
                        <p class="font-medium text-gray-900">{{ $user->german_level ?? 'A1' }}</p>
                    </div>
                    <div class="flex flex-col">
                        <p class="text-xs text-gray-500">{{ __('Lernziel') }}</p>
                        <p class="font-medium text-gray-900">{{ $user->learning_goal ?? '—' }}</p>
                    </div>
                    @endif
                    <div class="flex flex-col">
                        <p class="text-xs text-gray-500">{{ __('Letzte Aktualisierung') }}</p>
                        <p class="font-medium text-gray-900">{{ $user->updated_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Courses -->
        <div class="mb-6 overflow-hidden bg-white shadow-sm rounded-xl">
            <div class="flex items-center justify-between p-4 border-b bg-gray-50">
                <div class="flex items-center gap-2">
                    <x-icon name="o-academic-cap" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Letzte Kurse') }}</h2>
                </div>
                <a href="{{ route('admin.enrollments', ['user' => $user->id]) }}" class="text-xs text-[#FF6B35] hover:underline">
                    {{ __('Alle anzeigen') }} →
                </a>
            </div>
            <div class="p-4">
                @if($this->recentCourses->count() > 0)
                    <div class="space-y-3">
                        @foreach($this->recentCourses as $course)
                        <div class="flex items-center justify-between p-3 transition border rounded-lg hover:bg-gray-50">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $course->title }}</p>
                                <p class="text-xs text-gray-400">{{ $course->subject->name ?? 'Allgemein' }}</p>
                                <div class="flex items-center gap-3 mt-1">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs text-gray-500">{{ __('Fortschritt') }}:</span>
                                        <span class="text-xs font-semibold {{ $this->getProgressTextColor($course->progress) }}">
                                            {{ $course->progress }}%
                                        </span>
                                    </div>
                                    <div class="w-24 h-1.5 bg-gray-200 rounded-full">
                                        <div class="h-1.5 rounded-full {{ $this->getProgressColor($course->progress) }}"
                                             style="width: {{ $course->progress }}%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-400">{{ $course->enrolled_at->format('d.m.Y') }}</p>
                                <a href="{{ route('admin.courses.edit', $course) }}" class="text-xs text-[#FF6B35] hover:underline">
                                    {{ __('Details') }}
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-6 text-center">
                        <p class="text-sm text-gray-500">{{ __('Keine Kurse eingeschrieben') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="overflow-hidden bg-white shadow-sm rounded-xl">
            <div class="flex items-center gap-2 p-4 border-b bg-gray-50">
                <x-icon name="o-clock" class="w-5 h-5 text-[#FF6B35]" />
                <h2 class="font-semibold text-gray-900">{{ __('Letzte Aktivitäten') }}</h2>
            </div>
            <div class="p-4">
                @if($this->recentActivity->count() > 0)
                    <div class="space-y-3">
                        @foreach($this->recentActivity as $activity)
                        <div class="flex items-center gap-3 p-3 border rounded-lg">
                            <div class="w-8 h-8 rounded-full bg-{{ $activity['color'] }}-100 flex items-center justify-center">
                                <x-icon :name="$activity['icon']" class="w-4 h-4 text-{{ $activity['color'] }}-600" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">
                                    @if($activity['type'] === 'quiz')
                                        {{ __('Quiz') }}: {{ $activity['title'] }}
                                    @else
                                        {{ __('Bewertung') }}: {{ $activity['title'] }}
                                    @endif
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    @if($activity['type'] === 'quiz')
                                        <span class="text-xs {{ $activity['is_passed'] ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $activity['is_passed'] ? 'Bestanden' : 'Nicht bestanden' }}
                                        </span>
                                    @else
                                        <div class="flex text-yellow-400">
                                            @for($i = 1; $i <= 5; $i++)
                                                <x-icon name="o-star" class="w-3 h-3" :class="$i <= $activity['rating'] ? 'text-yellow-400' : 'text-gray-300'" />
                                            @endfor
                                        </div>
                                    @endif
                                    <span class="text-xs text-gray-400">{{ $activity['date']->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-6 text-center">
                        <p class="text-sm text-gray-500">{{ __('Keine Aktivitäten') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Note MVP -->
        <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">MVP Version</p>
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('Prochaines fonctionnalités : historique complet des actions, export des données et gestion des permissions.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
