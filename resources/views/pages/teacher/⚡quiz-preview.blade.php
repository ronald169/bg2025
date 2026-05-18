<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Course;
use App\Models\Quiz;
use Mary\Traits\Toast;

new
#[Title('Quiz Preview - Teacher')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public Course $course;
    public Quiz $quiz;
    public $questions = [];
    public $userAnswers = [];
    public $currentQuestionIndex = 0;
    public $showResults = false;

    // Getters (si nécessaire)
    public function getScoreProperty(): array
    {
        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($this->questions as $index => $question) {
            $totalPoints += $question->points;
            $userAnswer = $this->userAnswers[$index] ?? null;
            if ($userAnswer !== null && $this->isAnswerCorrect($question, $userAnswer)) {
                $earnedPoints += $question->points;
            }
        }

        $percentage = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100) : 0;

        return [
            'total_points' => $totalPoints,
            'earned_points' => $earnedPoints,
            'percentage' => $percentage,
            'passed' => $percentage >= ($this->quiz->passing_score ?? 70),
        ];
    }

    public function mount(Course $course, Quiz $quiz): void
    {
        if ($course->teacher_id !== auth()->id()) {
            abort(403);
        }

        $this->course = $course;
        $this->quiz = $quiz->load('questions');
        $this->questions = $this->quiz->questions;
    }

    public function saveAnswer($answer): void
    {
        $this->userAnswers[$this->currentQuestionIndex] = $answer;
    }

    public function nextQuestion(): void
    {
        if ($this->currentQuestionIndex < count($this->questions) - 1) {
            $this->currentQuestionIndex++;
        }
    }

    public function previousQuestion(): void
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
        }
    }

    public function submitQuiz(): void
    {
        if (count($this->userAnswers) < count($this->questions)) {
            $this->warning(__('Please answer all questions before submitting the quiz.'));
            return;
        }
        $this->showResults = true;
    }

    public function resetQuiz(): void
    {
        $this->userAnswers = [];
        $this->currentQuestionIndex = 0;
        $this->showResults = false;
    }

    private function isAnswerCorrect($question, $answer): bool
    {
        $correctAnswerArray = $question->correct_answer;
        $correctAnswer = is_array($correctAnswerArray) ? ($correctAnswerArray[0] ?? null) : $correctAnswerArray;

        if ($question->type === 'multiple_choice') {
            return $answer == $correctAnswer;
        }
        if ($question->type === 'true_false') {
            return $answer == $correctAnswer;
        }
        if ($question->type === 'short_answer') {
            $cleanAnswer = strtolower(trim((string)$answer));
            $cleanCorrect = strtolower(trim((string)$correctAnswer));
            return $cleanAnswer == $cleanCorrect;
        }
        return false;
    }

    public function getOptionLetter($index): string
    {
        return chr(65 + (int)$index);
    }

    public function render()
    {
        return $this->view([
            'score' => $this->score,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-3xl px-3 mx-auto md:px-4">

        {{-- Navigation --}}
        <div class="mb-5">
            <a href="{{ route('teacher.quizzes.index', ['course' => $course]) }}" wire:navigate class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to quiz') }}
            </a>
        </div>

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-xl font-bold md:text-2xl">👁️ {{ __('Quiz Preview') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ $quiz->title }}</p>
        </div>

        @if(!$showResults)
            <x-card class="shadow-sm">
                {{-- Progress --}}
                <div class="mb-6">
                    <div class="flex justify-between mb-2 text-sm text-base-content/70">
                        <span>{{ __('Progress') }}</span>
                        <span>{{ count($userAnswers) }}/{{ count($questions) }} {{ __('answered') }}</span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-base-200">
                        <div class="h-2 transition-all rounded-full bg-primary" style="width: {{ count($questions) > 0 ? (count($userAnswers) / count($questions)) * 100 : 0 }}%"></div>
                    </div>
                </div>

                {{-- Current Question --}}
                @php $question = $questions[$currentQuestionIndex]; @endphp
                <div class="mb-6">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="text-sm text-base-content/70">{{ __('Question') }} {{ $currentQuestionIndex + 1 }}/{{ count($questions) }}</span>
                        <x-badge :value="$question->points . ' ' . __('points')" class="badge-neutral badge-soft" />
                    </div>
                    <p class="mb-6 text-lg font-medium">{{ $question->question }}</p>

                    {{-- Answers by type --}}
                    @if($question->type === 'multiple_choice')
                        <div class="space-y-3">
                            @foreach($question->options as $index => $option)
                                @if($option)
                                    <div class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition
                                                {{ (isset($userAnswers[$currentQuestionIndex]) && $userAnswers[$currentQuestionIndex] == $index) ? 'border-primary bg-primary/10' : 'border-base-200 hover:bg-base-200' }}"
                                         wire:click="saveAnswer({{ $index }})">
                                        <div class="w-6 h-6 rounded-full border flex items-center justify-center text-sm font-medium
                                                    {{ (isset($userAnswers[$currentQuestionIndex]) && $userAnswers[$currentQuestionIndex] == $index) ? 'bg-primary border-primary text-white' : 'border-base-300' }}">
                                            {{ $this->getOptionLetter($index) }}
                                        </div>
                                        <span class="flex-1">{{ $option }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @elseif($question->type === 'true_false')
                        <div class="grid grid-cols-2 gap-4">
                            <div class="cursor-pointer" wire:click="saveAnswer('true')">
                                <div class="p-4 border-2 rounded-xl text-center transition
                                            {{ (isset($userAnswers[$currentQuestionIndex]) && $userAnswers[$currentQuestionIndex] === 'true') ? 'border-success bg-success/10' : 'border-base-200 hover:border-success/50' }}">
                                    <x-icon name="o-check-circle" class="w-8 h-8 mx-auto mb-2 text-success" />
                                    <span class="font-medium text-success">{{ __('True') }}</span>
                                </div>
                            </div>
                            <div class="cursor-pointer" wire:click="saveAnswer('false')">
                                <div class="p-4 border-2 rounded-xl text-center transition
                                            {{ (isset($userAnswers[$currentQuestionIndex]) && $userAnswers[$currentQuestionIndex] === 'false') ? 'border-error bg-error/10' : 'border-base-200 hover:border-error/50' }}">
                                    <x-icon name="o-x-circle" class="w-8 h-8 mx-auto mb-2 text-error" />
                                    <span class="font-medium text-error">{{ __('False') }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <x-textarea wire:model="userAnswers.{{ $currentQuestionIndex }}" rows="4" placeholder="{{ __('Your answer here...') }}" class="w-full" wire:change="saveAnswer($event.target.value)" />
                    @endif
                </div>

                {{-- Navigation --}}
                <div class="flex items-center justify-between pt-4 border-t">
                    <x-button wire:click="previousQuestion" label="{{ __('Previous') }}" icon="o-arrow-left" class="btn-ghost" :disabled="$currentQuestionIndex === 0" />
                    @if($currentQuestionIndex < count($questions) - 1)
                        <x-button wire:click="nextQuestion" label="{{ __('Next') }}" icon-right="o-arrow-right" class="btn-primary" />
                    @else
                        <x-button wire:click="submitQuiz" label="{{ __('Submit quiz') }}" icon="o-check" class="btn-success" />
                    @endif
                </div>
            </x-card>
        @else
            {{-- Results --}}
            <x-card class="text-center shadow-sm">
                @if($score['passed'])
                    <x-icon name="o-trophy" class="w-16 h-16 mx-auto mb-4 text-warning" />
                    <h2 class="mb-2 text-2xl font-bold text-success">{{ __('Quiz passed!') }} 🎉</h2>
                @else
                    <x-icon name="o-academic-cap" class="w-16 h-16 mx-auto mb-4 text-primary" />
                    <h2 class="mb-2 text-2xl font-bold text-warning">{{ __('Quiz completed') }}</h2>
                @endif

                <div class="grid max-w-sm grid-cols-2 gap-4 mx-auto mb-6">
                    <div class="p-3 rounded-lg bg-base-200">
                        <div class="text-2xl font-bold text-primary">{{ $score['percentage'] }}%</div>
                        <div class="text-xs text-base-content/60">{{ __('Score') }}</div>
                    </div>
                    <div class="p-3 rounded-lg bg-base-200">
                        <div class="text-2xl font-bold text-primary">{{ $score['earned_points'] }}/{{ $score['total_points'] }}</div>
                        <div class="text-xs text-base-content/60">{{ __('Points') }}</div>
                    </div>
                </div>

                <div class="flex justify-center gap-3">
                    <x-button wire:click="resetQuiz" label="{{ __('Retake quiz') }}" icon="o-arrow-path" class="btn-outline" />
                    <a href="{{ route('teacher.quizzes.index', ['course' => $course]) }}" class="btn btn-primary">{{ __('Back to quiz') }}</a>
                </div>
            </x-card>
        @endif
    </div>
</div>
