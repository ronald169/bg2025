<?php

use App\Models\Enrollment;
use App\Models\Progress;
use App\Models\StudySession;
use App\Models\QuizAttempt;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Mein Lernfortschritt - Deutsch lernen')]
#[Layout('components.layouts.dashboard-student')]
class extends Component {
    use Toast;

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function courses()
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

    #[Computed]
    public function totalCourses()
    {
        return $this->courses->count();
    }

    #[Computed]
    public function completedCourses()
    {
        return $this->courses->filter(function($course) {
            return $course->progress >= 100;
        })->count();
    }

    #[Computed]
    public function averageProgress()
    {
        if ($this->totalCourses === 0) return 0;
        return round($this->courses->avg('progress'));
    }

    #[Computed]
    public function totalStudyTime()
    {
        return StudySession::where('user_id', $this->user->id)->sum('duration_minutes');
    }

    #[Computed]
    public function totalQuizzesTaken()
    {
        return QuizAttempt::where('user_id', $this->user->id)->count();
    }

    #[Computed]
    public function averageQuizScore()
    {
        return round(QuizAttempt::where('user_id', $this->user->id)->avg('score') ?? 0, 1);
    }

    #[Computed]
    public function totalPoints()
    {
        return $this->user->total_points ?? 0;
    }

    #[Computed]
    public function completedLessonsCount()
    {
        return Progress::where('user_id', $this->user->id)
            ->where('completed', true)
            ->count();
    }

    #[Computed]
    public function certificatesCount()
    {
        // À adapter selon votre système de certificats
        return 0;
    }

    public function getProgressColor($progress)
    {
        if ($progress >= 80) return 'bg-green-500';
        if ($progress >= 50) return 'bg-blue-500';
        if ($progress >= 20) return 'bg-yellow-500';
        return 'bg-gray-400';
    }

    public function getFormattedStudyTime($minutes)
    {
        if ($minutes < 60) return "{$minutes} min";
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
    }
}
?>

<div class="py-8">
    <div class="max-w-6xl px-4 mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">📊 {{ __('Mein Lernfortschritt') }}</h1>
            <p class="mt-1 text-gray-600">{{ __('Verfolge deine Fortschritte beim Deutschlernen') }}</p>
        </div>

        <!-- Statistiques globales -->
        <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-4">
            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->totalCourses }}</div>
                <div class="text-sm text-gray-500">Kurse</div>
                <div class="text-xs text-gray-400">{{ $this->completedCourses }} abgeschlossen</div>
            </x-card>

            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->averageProgress }}%</div>
                <div class="text-sm text-gray-500">Ø Fortschritt</div>
                <div class="text-xs text-gray-400">über alle Kurse</div>
            </x-card>

            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->getFormattedStudyTime($this->totalStudyTime) }}</div>
                <div class="text-sm text-gray-500">Studienzeit</div>
                <div class="text-xs text-gray-400">insgesamt</div>
            </x-card>

            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->averageQuizScore }}%</div>
                <div class="text-sm text-gray-500">Quiz Ø</div>
                <div class="text-xs text-gray-400">{{ $this->totalQuizzesTaken }} Quiz absolviert</div>
            </x-card>
        </div>

        <!-- Section des certificats -->
        @if($this->certificatesCount > 0)
        <div class="p-4 mb-8 border border-yellow-200 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 bg-yellow-500 rounded-full">
                        <x-icon name="o-trophy" class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Zertifikate erhalten</h3>
                        <p class="text-sm text-gray-600">Du hast {{ $this->certificatesCount }} Zertifikat(e) erhalten</p>
                    </div>
                </div>
                <x-button label="Zertifikate anzeigen" icon="o-document" class="btn-outline btn-sm" />
            </div>
        </div>
        @endif

        <!-- Liste des cours -->
        <div class="space-y-4">
            <h2 class="text-xl font-bold text-gray-900">📚 {{ __('Meine Kurse') }}</h2>

            @if($this->courses->count() > 0)
                @foreach($this->courses as $course)
                <x-card class="transition border-0 shadow-sm hover:shadow-md">
                    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="font-semibold text-gray-900">{{ $course->title }}</h3>
                                @if($course->progress >= 100)
                                    <span class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded-full">
                                        ✓ Abgeschlossen
                                    </span>
                                @endif
                            </div>

                            <!-- Barre de progression -->
                            <div class="mb-2">
                                <div class="flex justify-between mb-1 text-sm">
                                    <span class="text-gray-600">Fortschritt</span>
                                    <span class="font-medium text-[#FF6B35]">{{ $course->progress }}%</span>
                                </div>
                                <div class="w-full h-2 bg-gray-200 rounded-full">
                                    <div class="h-2 rounded-full transition-all duration-300 {{ $this->getProgressColor($course->progress) }}"
                                         style="width: {{ $course->progress }}%"></div>
                                </div>
                            </div>

                            <!-- Détails supplémentaires -->
                            <div class="flex flex-wrap gap-4 mt-2 text-xs text-gray-500">
                                <span class="flex items-center gap-1">
                                    <x-icon name="o-book-open" class="w-3 h-3" />
                                    {{ $course->lessons_count ?? 0 }} Lektionen
                                </span>
                                <span class="flex items-center gap-1">
                                    <x-icon name="o-calendar" class="w-3 h-3" />
                                    Begonnen: {{ \Carbon\Carbon::parse($course->enrolled_at)->format('d.m.Y') }}
                                </span>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            @if($course->progress >= 100)
                                <x-button
                                    label="Zertifikat"
                                    icon="o-trophy"
                                    class="btn-outline btn-sm" />
                            @endif
                            <x-button
                                label="{{ $course->progress >= 100 ? 'Wiederholen' : 'Weiterlernen' }}"
                                icon="o-play-circle"
                                link="{{ route('student.course.show', $course) }}"
                                class="btn-primary btn-sm" />
                        </div>
                    </div>
                </x-card>
                @endforeach
            @else
                <div class="py-12 text-center bg-white border rounded-xl">
                    <x-icon name="o-book-open" class="w-16 h-16 mx-auto mb-3 text-gray-300" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">Noch keine Kurse</h3>
                    <p class="mb-4 text-gray-500">Du bist noch in keinem Kurs eingeschrieben.</p>
                    <x-button link="{{ route('student.catalog') }}" class="btn-primary">
                        Kurse entdecken →
                    </x-button>
                </div>
            @endif
        </div>

        <!-- Note MVP -->
        <div class="p-4 mt-8 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="font-medium text-blue-800">MVP Version</p>
                    <p class="text-sm text-blue-700">Prochaines fonctionnalités : graphiques d'évolution, objectifs personnalisés, comparaison avec la communauté, et recommandations basées sur tes progrès.</p>
                </div>
            </div>
        </div>
    </div>
</div>
