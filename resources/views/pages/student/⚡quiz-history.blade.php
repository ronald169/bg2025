<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\QuizAttempt;
use Mary\Traits\Toast;

new
#[Title('Quiz History - German Learning')]
#[Layout('layouts.app')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'per_page', history: true)]
    public int $perPage = 10;

    // Getters (remplacent les anciens #[Computed])
    public function getStatsProperty(): array
    {
        $userId = auth()->id();

        $totalAttempts = QuizAttempt::where('user_id', $userId)->count();
        $passedAttempts = QuizAttempt::where('user_id', $userId)
            ->where('is_passed', true)
            ->count();

        $bestAttempt = QuizAttempt::where('user_id', $userId)
            ->with(['quiz.questions'])
            ->get()
            ->sortByDesc(fn($attempt) => $this->calculateScorePercentage($attempt))
            ->first();

        $bestScore = 0;
        $bestQuizTitle = null;

        if ($bestAttempt) {
            $bestScore = $this->calculateScorePercentage($bestAttempt);
            $bestQuizTitle = $bestAttempt->quiz->title;
        }

        $allAttempts = QuizAttempt::where('user_id', $userId)->with(['quiz.questions'])->get();
        $averageScore = $allAttempts->map(fn($attempt) => $this->calculateScorePercentage($attempt))->avg() ?? 0;

        return [
            'total_attempts' => $totalAttempts,
            'total_passed'   => $passedAttempts,
            'pass_rate'      => $totalAttempts > 0 ? round(($passedAttempts / $totalAttempts) * 100) : 0,
            'best_score'     => round($bestScore),
            'best_quiz'      => $bestQuizTitle,
            'average_score'  => round($averageScore),
        ];
    }

    public function getAttemptsProperty()
    {
        return QuizAttempt::where('user_id', auth()->id())
            ->with(['quiz' => fn($q) => $q->with(['lesson.course', 'questions'])])
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
        if ($percentage >= 80) return 'text-success';
        if ($percentage >= 60) return 'text-warning';
        if ($percentage >= 40) return 'text-accent';
        return 'text-error';
    }

    public function getScoreBgColor($percentage): string
    {
        if ($percentage >= 80) return 'bg-success/20';
        if ($percentage >= 60) return 'bg-warning/20';
        if ($percentage >= 40) return 'bg-accent/20';
        return 'bg-error/20';
    }

    public function getDuration($attempt): string
    {
        if ($attempt->started_at && $attempt->completed_at) {
            $minutes = $attempt->started_at->diffInMinutes($attempt->completed_at);
            return $minutes . ' min';
        }
        return '-';
    }

    public function viewResults($attemptId): void
    {
        $this->redirectRoute('student.quiz.results', ['attempt' => $attemptId]);
    }

    public function render()
    {
        return $this->view([
            'stats'    => $this->stats,
            'attempts' => $this->attempts,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-6xl px-3 mx-auto md:px-4">

        {{-- En-tête --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold md:text-3xl">📊 {{ __('Quiz History') }}</h1>
            <p class="mt-1 text-base-content/70">{{ __('Track your quiz performance') }}</p>
        </div>

        {{-- Cartes de statistiques --}}
        <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-4">
            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-primary">{{ $stats['total_attempts'] }}</div>
                <div class="text-sm text-base-content/70">{{ __('Total attempts') }}</div>
            </x-card>
            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-success">{{ $stats['total_passed'] }}</div>
                <div class="text-sm text-base-content/70">{{ __('Passed') }}</div>
            </x-card>
            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-primary">{{ $stats['pass_rate'] }}%</div>
                <div class="text-sm text-base-content/70">{{ __('Success rate') }}</div>
            </x-card>
            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-primary">{{ $stats['average_score'] }}%</div>
                <div class="text-sm text-base-content/70">{{ __('Average score') }}</div>
            </x-card>
        </div>

        {{-- Meilleur score --}}
        @if($stats['best_quiz'])
            <div class="p-4 mb-8 border rounded-xl bg-gradient-to-r from-warning/10 to-accent/10 border-warning/20">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-warning">
                            <x-icon name="o-trophy" class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <p class="text-sm text-base-content/70">{{ __('Best performance') }}</p>
                            <p class="font-semibold text-base-content">{{ $stats['best_quiz'] }}</p>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-warning">{{ $stats['best_score'] }}%</div>
                        <div class="text-xs text-base-content/60">{{ __('Best score') }}</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Sélecteur par page --}}
        <div class="flex justify-end mb-4">
            <div class="flex items-center gap-2">
                <span class="text-sm text-base-content/70">{{ __('Show') }}:</span>
                <select wire:model.live="perPage" class="px-3 py-1.5 text-sm border rounded-lg focus:ring-primary focus:border-primary">
                    <option value="10">10 {{ __('per page') }}</option>
                    <option value="25">25 {{ __('per page') }}</option>
                    <option value="50">50 {{ __('per page') }}</option>
                </select>
            </div>
        </div>

        {{-- Liste des tentatives --}}
        <x-card class="shadow-sm">
            <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                <x-icon name="o-document-text" class="w-5 h-5 text-primary" />
                <h2 class="font-semibold">{{ __('Your quiz attempts') }}</h2>
            </div>

            @if($attempts->count() > 0)
                <div class="space-y-3">
                    @foreach($attempts as $attempt)
                        @php
                            $scorePercentage = $this->calculateScorePercentage($attempt);
                        @endphp
                        <div wire:click="viewResults({{ $attempt->id }})"
                             class="p-4 transition border rounded-lg cursor-pointer hover:shadow-md hover:bg-base-200">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <h3 class="font-semibold text-base-content">{{ $attempt->quiz->title }}</h3>
                                        <x-badge :value="$attempt->is_passed ? __('Passed') : __('Failed')"
                                                 :class="$attempt->is_passed ? 'badge-success' : 'badge-error'" class="badge-soft" />
                                    </div>
                                    <p class="text-sm text-base-content/70">{{ $attempt->quiz->lesson->course->title ?? __('German course') }}</p>
                                    <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-base-content/60">
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
                                                <x-icon name="o-check-circle" class="w-4 h-4 text-success" />
                                                {{ __('Completed') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-center md:text-right">
                                    <div class="text-2xl font-bold {{ $this->getScoreColor($scorePercentage) }}">
                                        {{ round($scorePercentage) }}%
                                    </div>
                                    <x-badge :value="$attempt->score . ' ' . __('points')" :class="$this->getScoreBgColor($scorePercentage) . ' ' . $this->getScoreColor($scorePercentage)" class="badge-soft" />
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="w-full h-1.5 bg-base-200 rounded-full">
                                    <div class="h-1.5 rounded-full transition-all duration-300 {{ $attempt->is_passed ? 'bg-success' : 'bg-error' }}"
                                         style="width: {{ $scorePercentage }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $attempts->links() }}
                </div>
            @else
                <div class="py-12 text-center">
                    <x-icon name="o-document-text" class="w-16 h-16 mx-auto mb-3 text-base-content/30" />
                    <h3 class="mb-2 text-lg font-semibold text-base-content">{{ __('No quiz attempts yet') }}</h3>
                    <p class="mb-4 text-base-content/60">{{ __('Take a quiz to see your results here.') }}</p>
                    <x-button link="{{ route('student.catalog') }}" label="{{ __('Discover courses') }}" class="btn-primary" />
                </div>
            @endif
        </x-card>
    </div>
</div>
