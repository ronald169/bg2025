<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\QuizAttempt;
use Mary\Traits\Toast;

new
#[Title('Quiz Results - German Learning')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public QuizAttempt $attempt;
    public bool $showSolutions = false;

    public function mount(QuizAttempt $attempt): void
    {
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        $this->attempt = $attempt->load(['quiz.lesson.course', 'quiz.questions']);
    }

    // Getters (remplacent les anciens #[Computed])
    public function getScoreProperty(): int
    {
        return $this->attempt->score;
    }

    public function getPassedProperty(): bool
    {
        return $this->attempt->is_passed;
    }

    public function getPercentageProperty(): int
    {
        $totalPoints = $this->attempt->quiz->questions->sum('points');
        if ($totalPoints === 0) return 0;
        return round(($this->score / $totalPoints) * 100);
    }

    public function getTotalPointsProperty(): int
    {
        return $this->attempt->quiz->questions->sum('points');
    }

    public function getTotalQuestionsProperty(): int
    {
        return $this->attempt->quiz->questions->count();
    }

    public function getTimeTakenProperty(): string
    {
        if ($this->attempt->started_at && $this->attempt->completed_at) {
            $seconds = $this->attempt->started_at->diffInSeconds($this->attempt->completed_at);
            $minutes = floor($seconds / 60);
            $secs = $seconds % 60;
            if ($minutes > 0) return $minutes . ' min';
            return $secs . ' sec';
        }
        return '-';
    }

    public function getQuestionsWithAnswersProperty(): array
    {
        $answers = $this->attempt->answers ?? [];
        $results = [];

        foreach ($this->attempt->quiz->questions as $index => $question) {
            $options = $question->options;
            if (is_string($options)) $options = json_decode($options, true);

            $userAnswer = $answers[$index] ?? null;
            $correctAnswer = $question->correct_answer;
            if (is_string($correctAnswer)) $correctAnswer = json_decode($correctAnswer, true);

            $isCorrect = $this->isAnswerCorrect($question, $userAnswer);

            $results[] = [
                'id'                       => $question->id,
                'question'                 => $question->question,
                'type'                     => $question->type,
                'options'                  => $options,
                'user_answer'              => $userAnswer,
                'formatted_user_answer'    => $this->formatAnswer($question, $userAnswer, $options),
                'correct_answer'           => $correctAnswer,
                'formatted_correct_answer' => $this->formatAnswer($question, $correctAnswer[0] ?? null, $options),
                'is_correct'               => $isCorrect,
                'points'                   => $question->points,
                'explanation'              => $question->explanation,
            ];
        }

        return $results;
    }

    public function getCorrectCountProperty(): int
    {
        return collect($this->questionsWithAnswers)->where('is_correct', true)->count();
    }

    public function getEarnedPointsProperty(): int
    {
        return collect($this->questionsWithAnswers)->sum(fn($q) => $q['is_correct'] ? $q['points'] : 0);
    }

    private function isAnswerCorrect($question, $answer): bool
    {
        if ($answer === null || $answer === '') return false;

        $correctAnswer = $question->correct_answer;
        if (is_string($correctAnswer)) $correctAnswer = json_decode($correctAnswer, true);
        $correctAnswerValue = is_array($correctAnswer) ? ($correctAnswer[0] ?? null) : $correctAnswer;

        return match($question->type) {
            'multiple_choice' => $answer == $correctAnswerValue,
            'true_false'      => $answer == $correctAnswerValue,
            'short_answer', 'text' => strtolower(trim($answer ?? '')) == strtolower(trim($correctAnswerValue ?? '')),
            default => false,
        };
    }

    private function formatAnswer($question, $answer, $options = null): string
    {
        if ($answer === null || $answer === '') return __('Not answered');

        if ($question->type === 'multiple_choice' && $options) {
            $index = array_search($answer, $options);
            if ($index !== false) {
                $letter = chr(65 + $index);
                return "{$letter}. {$answer}";
            }
            return $answer;
        }

        if ($question->type === 'true_false') {
            return $answer === 'true' ? __('True') : __('False');
        }

        return $answer;
    }

    public function retakeQuiz(): void
    {
        $this->redirectRoute('student.quiz.show', $this->attempt->quiz);
    }

    public function toggleSolutions(): void
    {
        $this->showSolutions = !$this->showSolutions;
    }

    public function getScoreColor($percentage): string
    {
        if ($percentage >= 80) return 'text-success';
        if ($percentage >= 60) return 'text-warning';
        if ($percentage >= 40) return 'text-accent';
        return 'text-error';
    }

    public function render()
    {
        return $this->view([
            'score'                => $this->score,
            'passed'               => $this->passed,
            'percentage'           => $this->percentage,
            'totalPoints'          => $this->totalPoints,
            'totalQuestions'       => $this->totalQuestions,
            'timeTaken'            => $this->timeTaken,
            'questionsWithAnswers' => $this->questionsWithAnswers,
            'correctCount'         => $this->correctCount,
            'earnedPoints'         => $this->earnedPoints,
            'scoreColor'           => $this->getScoreColor($this->percentage),
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-4xl px-3 mx-auto md:px-4">

        {{-- Fil d’Ariane --}}
        <div class="mb-5">
            <a href="{{ route('student.course.show', $attempt->quiz->lesson->course) }}" wire:navigate
               class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to course') }}
            </a>
        </div>

        {{-- Carte principale des résultats --}}
        <div class="mb-6 overflow-hidden shadow bg-base-100 rounded-xl">
            <div class="p-8 text-center {{ $passed ? 'bg-gradient-to-r from-success/10 to-emerald-50' : 'bg-gradient-to-r from-error/10 to-orange-50' }}">
                @if($passed)
                    <div class="flex items-center justify-center w-20 h-20 mx-auto mb-4 rounded-full shadow-lg bg-success">
                        <x-icon name="o-check" class="w-10 h-10 text-white" />
                    </div>
                    <h1 class="text-2xl font-bold text-success">{{ __('Congratulations!') }} 🎉</h1>
                    <p class="mt-2 text-success/80">{{ __('You passed the quiz!') }}</p>
                @else
                    <div class="flex items-center justify-center w-20 h-20 mx-auto mb-4 rounded-full shadow-lg bg-error">
                        <x-icon name="o-x-mark" class="w-10 h-10 text-white" />
                    </div>
                    <h1 class="text-2xl font-bold text-error">{{ __('Not passed') }}</h1>
                    <p class="mt-2 text-error/80">{{ __('Keep practicing and try again!') }} 📚</p>
                @endif
            </div>

            <div class="p-6">
                {{-- Statistiques --}}
                <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4">
                    <div class="p-3 text-center rounded-lg bg-base-200">
                        <p class="text-xs text-base-content/60">{{ __('Score') }}</p>
                        <p class="text-xl font-bold {{ $scoreColor }}">{{ $percentage }}%</p>
                    </div>
                    <div class="p-3 text-center rounded-lg bg-base-200">
                        <p class="text-xs text-base-content/60">{{ __('Points') }}</p>
                        <p class="text-xl font-bold text-base-content">{{ $earnedPoints }}/{{ $totalPoints }}</p>
                    </div>
                    <div class="p-3 text-center rounded-lg bg-base-200">
                        <p class="text-xs text-base-content/60">{{ __('Questions') }}</p>
                        <p class="text-xl font-bold text-base-content">{{ $correctCount }}/{{ $totalQuestions }}</p>
                    </div>
                    <div class="p-3 text-center rounded-lg bg-base-200">
                        <p class="text-xs text-base-content/60">{{ __('Time') }}</p>
                        <p class="text-xl font-bold text-base-content">{{ $timeTaken }}</p>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-wrap justify-center gap-3">
                    <x-button link="{{ route('student.course.show', $attempt->quiz->lesson->course) }}"
                        label="{{ __('Back to course') }}" icon="o-arrow-left" class="btn-ghost" />

                    @if(!$passed)
                        <x-button wire:click="retakeQuiz" label="{{ __('Retake quiz') }}" icon="o-arrow-path" class="btn-primary" spinner />
                    @endif

                    <x-button wire:click="toggleSolutions" :label="$showSolutions ? __('Hide solutions') : __('Show solutions')"
                        :icon="$showSolutions ? 'o-eye-slash' : 'o-eye'" class="btn-outline" />
                </div>
            </div>
        </div>

        {{-- Détails des solutions (si affichées) --}}
        @if($showSolutions)
            <x-card class="shadow">
                <div class="flex items-center justify-between pb-2 mb-4 border-b">
                    <div class="flex items-center gap-2">
                        <x-icon name="o-document-text" class="w-5 h-5 text-primary" />
                        <h2 class="font-semibold">{{ __('Detailed results') }}</h2>
                    </div>
                    <span class="text-xs text-base-content/60">{{ __('Click on a question for more details') }}</span>
                </div>

                <div class="space-y-3">
                    @foreach($questionsWithAnswers as $index => $question)
                        <div x-data="{ expanded: false }" class="overflow-hidden border rounded-lg">
                            <div @click="expanded = !expanded"
                                 class="flex items-center justify-between p-4 cursor-pointer transition
                                        {{ $question['is_correct'] ? 'hover:bg-success/5' : 'hover:bg-error/5' }}
                                        {{ $question['is_correct'] ? 'border-l-4 border-l-success' : 'border-l-4 border-l-error' }}">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $question['is_correct'] ? 'bg-success/20' : 'bg-error/20' }}">
                                        @if($question['is_correct'])
                                            <x-icon name="o-check" class="w-4 h-4 text-success" />
                                        @else
                                            <x-icon name="o-x-mark" class="w-4 h-4 text-error" />
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium text-base-content">{{ __('Question') }} {{ $index + 1 }}</p>
                                        <p class="text-sm text-base-content/70 line-clamp-1">{{ $question['question'] }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-badge :value="$question['points'] . ' ' . __('points')" :class="$question['is_correct'] ? 'badge-success' : 'badge-error'" class="badge-soft" />
                                    <x-icon name="o-chevron-down" class="w-5 h-5 transition-transform text-base-content/50" x-bind:class="expanded ? 'rotate-180' : ''" />
                                </div>
                            </div>

                            <div x-show="expanded" x-collapse class="p-4 border-t bg-base-200">
                                <div class="mb-3">
                                    <p class="mb-1 text-sm font-medium text-base-content/80">📝 {{ __('Question') }}:</p>
                                    <p class="text-base-content">{{ $question['question'] }}</p>
                                </div>

                                <div class="mb-3">
                                    <p class="mb-1 text-sm font-medium text-base-content/80">✏️ {{ __('Your answer') }}:</p>
                                    <p class="px-3 py-2 rounded-lg {{ $question['is_correct'] ? 'bg-success/20 text-success-content' : 'bg-error/20 text-error-content' }}">
                                        {{ $question['formatted_user_answer'] }}
                                    </p>
                                </div>

                                @if(!$question['is_correct'])
                                    <div class="mb-3">
                                        <p class="mb-1 text-sm font-medium text-base-content/80">✓ {{ __('Correct answer') }}:</p>
                                        <p class="px-3 py-2 rounded-lg text-success-content bg-success/10">
                                            {{ $question['formatted_correct_answer'] }}
                                        </p>
                                    </div>
                                @endif

                                @if($question['explanation'])
                                    <div class="p-3 border rounded-lg bg-info/10 text-info-content border-info/20">
                                        <p class="flex items-center gap-1 mb-1 text-sm font-medium text-info">
                                            <x-icon name="o-light-bulb" class="w-4 h-4" />
                                            {{ __('Explanation') }}:
                                        </p>
                                        <p class="text-sm">{{ $question['explanation'] }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif

        {{-- Conseils en cas d'échec --}}
        @if(!$passed)
            <div class="p-4 mt-6 border rounded-lg bg-warning/10 border-warning/20">
                <div class="flex items-start gap-3">
                    <x-icon name="o-light-bulb" class="w-5 h-5 text-warning mt-0.5" />
                    <div>
                        <p class="font-medium text-warning">{{ __('Tips for improvement') }}</p>
                        <ul class="mt-2 space-y-1 text-sm text-warning/80">
                            <li>• 📚 {{ __('Review the course materials on topics you struggled with') }}</li>
                            <li>• 📝 {{ __('Take notes while learning') }}</li>
                            <li>• 🃏 {{ __('Practice with flashcards to reinforce key concepts') }}</li>
                            <li>• 💬 {{ __('Ask questions in the course forum if something is unclear') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Actions supplémentaires --}}
        <div class="flex justify-center gap-3 mt-6">
            <x-button link="{{ route('student.quiz-history') }}" label="{{ __('All quiz results') }}" icon="o-clock" class="btn-outline" />
            @if($passed && $percentage >= 90)
                <x-button label="{{ __('Share result 🎉') }}" icon="o-share" class="btn-primary" />
            @endif
        </div>
    </div>
</div>
