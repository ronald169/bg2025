<?php

use App\Models\QuizAttempt;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Quiz-Verlauf - Deutsch lernen')]
#[Layout('components.layouts.dashboard-student')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'per_page', history: true)]
    public int $perPage = 10;

    #[Computed]
    public function stats()
    {
        $userId = auth()->id();

        $totalAttempts = QuizAttempt::where('user_id', $userId)->count();
        $passedAttempts = QuizAttempt::where('user_id', $userId)
            ->where('is_passed', true)
            ->count();

        // Calculer le meilleur score en pourcentage
        $bestAttempt = QuizAttempt::where('user_id', $userId)
            ->with(['quiz.questions'])
            ->get()
            ->sortByDesc(function ($attempt) {
                return $this->calculateScorePercentage($attempt);
            })
            ->first();

        $bestScore = 0;
        $bestQuizTitle = null;

        if ($bestAttempt) {
            $bestScore = $this->calculateScorePercentage($bestAttempt);
            $bestQuizTitle = $bestAttempt->quiz->title;
        }

        // Calculer la moyenne des scores
        $allAttempts = QuizAttempt::where('user_id', $userId)->with(['quiz.questions'])->get();
        $averageScore = $allAttempts->map(function ($attempt) {
            return $this->calculateScorePercentage($attempt);
        })->avg() ?? 0;

        return [
            'total_attempts' => $totalAttempts,
            'total_passed' => $passedAttempts,
            'pass_rate' => $totalAttempts > 0 ? round(($passedAttempts / $totalAttempts) * 100) : 0,
            'best_score' => round($bestScore),
            'best_quiz' => $bestQuizTitle,
            'average_score' => round($averageScore),
        ];
    }

    #[Computed]
    public function attempts()
    {
        return QuizAttempt::where('user_id', auth()->id())
            ->with(['quiz' => function($q) {
                $q->with(['lesson.course', 'questions']);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function calculateScorePercentage($attempt): float
    {
        $totalPoints = $attempt->quiz->questions->sum('points');
        if ($totalPoints === 0) return 0;
        return ($attempt->score / $totalPoints) * 100;
    }

    public function getScoreColor($percentage): string
    {
        if ($percentage >= 80) return 'text-green-600';
        if ($percentage >= 60) return 'text-yellow-600';
        if ($percentage >= 40) return 'text-orange-600';
        return 'text-red-600';
    }

    public function getScoreBgColor($percentage): string
    {
        if ($percentage >= 80) return 'bg-green-100';
        if ($percentage >= 60) return 'bg-yellow-100';
        if ($percentage >= 40) return 'bg-orange-100';
        return 'bg-red-100';
    }

    public function getDuration($attempt): string
    {
        if ($attempt->started_at && $attempt->completed_at) {
            $minutes = $attempt->started_at->diffInMinutes($attempt->completed_at);
            return formatDuration($minutes);
        }
        return '-';
    }

    public function viewResults($attemptId): void
    {
        $this->redirectRoute('student.quiz.results', ['attempt' => $attemptId], navigate: true);
    }
}
?>

<div class="py-8">
    <div class="max-w-6xl px-4 mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">📊 {{ __('Quiz-Verlauf') }}</h1>
            <p class="mt-1 text-gray-600">{{ __('Verfolge deine Quiz-Leistungen im Überblick') }}</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-4">
            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->stats['total_attempts'] }}</div>
                <div class="text-sm text-gray-500">Quiz-Versuche</div>
            </x-card>

            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-green-600">{{ $this->stats['total_passed'] }}</div>
                <div class="text-sm text-gray-500">Bestanden</div>
            </x-card>

            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->stats['pass_rate'] }}%</div>
                <div class="text-sm text-gray-500">Erfolgsquote</div>
            </x-card>

            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->stats['average_score'] }}%</div>
                <div class="text-sm text-gray-500">Ø Punktzahl</div>
            </x-card>
        </div>

        <!-- Best Score Card (si existant) -->
        @if($this->stats['best_quiz'])
        <div class="p-4 mb-8 border border-yellow-200 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 bg-yellow-500 rounded-full">
                        <x-icon name="o-trophy" class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Beste Leistung</p>
                        <p class="font-semibold text-gray-900">{{ $this->stats['best_quiz'] }}</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-yellow-600">{{ $this->stats['best_score'] }}%</div>
                    <div class="text-xs text-gray-500">Bestes Ergebnis</div>
                </div>
            </div>
        </div>
        @endif

        <!-- Per Page Selector -->
        <div class="flex justify-end mb-4">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500">Anzeigen:</span>
                <select wire:model.live="perPage" class="px-3 py-1.5 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                    <option value="10">10 pro Seite</option>
                    <option value="25">25 pro Seite</option>
                    <option value="50">50 pro Seite</option>
                </select>
            </div>
        </div>

        <!-- Attempts List -->
        <x-card class="border-0 shadow-sm">
            <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                <x-icon name="o-document-text" class="w-5 h-5 text-[#FF6B35]" />
                <h2 class="font-semibold text-gray-900">{{ __('Deine Quiz-Versuche') }}</h2>
            </div>

            @if($this->attempts->count() > 0)
                <div class="space-y-3">
                    @foreach($this->attempts as $attempt)
                    @php
                        $scorePercentage = $this->calculateScorePercentage($attempt);
                    @endphp
                    <div
                        wire:click="viewResults({{ $attempt->id }})"
                        class="p-4 transition border rounded-lg cursor-pointer hover:shadow-md hover:bg-gray-50">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <h3 class="font-semibold text-gray-900">{{ $attempt->quiz->title }}</h3>
                                    <span class="px-2 py-0.5 text-xs rounded-full {{ $attempt->is_passed ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $attempt->is_passed ? 'Bestanden' : 'Nicht bestanden' }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500">{{ $attempt->quiz->lesson->course->title ?? $attempt->quiz->course->title ?? 'Deutsch' }}</p>
                                <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-gray-500">
                                    <span class="flex items-center gap-1">
                                        <x-icon name="o-calendar" class="w-4 h-4" />
                                        {{ $attempt->created_at->format('d.m.Y') }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <x-icon name="o-clock" class="w-4 h-4" />
                                        {{ $this->getDuration($attempt) }}
                                    </span>
                                    @if($attempt->completed_at)
                                        <span class="flex items-center gap-1">
                                            <x-icon name="o-check-circle" class="w-4 h-4 text-green-500" />
                                            Abgeschlossen
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-center md:text-right">
                                <div class="text-2xl font-bold {{ $this->getScoreColor($scorePercentage) }}">
                                    {{ round($scorePercentage) }}%
                                </div>
                                <div class="inline-block px-2 py-1 text-xs rounded-full {{ $this->getScoreBgColor($scorePercentage) }} {{ $this->getScoreColor($scorePercentage) }}">
                                    {{ $attempt->score }} Punkte
                                </div>
                            </div>
                        </div>

                        <!-- Barre de progression pour le score -->
                        <div class="mt-3">
                            <div class="w-full h-1.5 bg-gray-200 rounded-full">
                                <div class="h-1.5 rounded-full transition-all duration-300
                                    {{ $attempt->is_passed ? 'bg-green-500' : 'bg-red-500' }}"
                                    style="width: {{ $scorePercentage }}%"></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $this->attempts->links() }}
                </div>
            @else
                <div class="py-12 text-center">
                    <x-icon name="o-document-text" class="w-16 h-16 mx-auto mb-3 text-gray-300" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">Noch keine Quiz-Versuche</h3>
                    <p class="mb-4 text-gray-500">Absolviere ein Quiz, um deine Ergebnisse hier zu sehen.</p>
                    <x-button link="{{ route('student.catalog') }}" class="btn-primary">
                        Kurse entdecken →
                    </x-button>
                </div>
            @endif
        </x-card>

        <!-- Note MVP -->
        <div class="p-4 mt-8 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="font-medium text-blue-800">MVP Version</p>
                    <p class="text-sm text-blue-700">Prochaines fonctionnalités : détails par question, comparaison avec la moyenne, graphiques d'évolution, et export des résultats.</p>
                </div>
            </div>
        </div>
    </div>
</div>
