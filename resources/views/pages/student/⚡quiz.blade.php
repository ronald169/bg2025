<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Mary\Traits\Toast;

new
#[Title('Quiz - German Learning')]
#[Layout('layouts.guest')]
class extends Component {
    use Toast;

    public Quiz $quiz;
    public $attempt = null;
    public array $answers = [];
    public int $currentQuestionIndex = 0;
    public ?int $timeRemaining = null;
    public bool $quizStarted = false;
    public bool $quizCompleted = false;
    public $startTime = null;

    public function mount(Quiz $quiz): void
    {
        $this->quiz = $quiz->load(['questions', 'lesson.course']);

        $this->attempt = QuizAttempt::where('user_id', auth()->id())
            ->where('quiz_id', $this->quiz->id)
            ->whereNull('completed_at')
            ->first();

        if ($this->attempt) {
            $this->quizStarted = true;
            $this->startTime = $this->attempt->started_at;
            $this->answers = $this->attempt->answers ?? [];
            $this->calculateTimeRemaining();
        }
    }

    // Getters (remplacent les anciens #[Computed])
    public function getQuestionsProperty()
    {
        return $this->quiz->questions;
    }

    public function getTotalQuestionsProperty(): int
    {
        return $this->questions->count();
    }

    public function getCurrentQuestionProperty()
    {
        return $this->questions[$this->currentQuestionIndex] ?? null;
    }

    public function getProgressProperty(): int
    {
        if ($this->totalQuestions === 0) return 0;
        $answered = count(array_filter($this->answers));
        return round(($answered / $this->totalQuestions) * 100);
    }

    public function getHasAnswerForCurrentProperty(): bool
    {
        return isset($this->answers[$this->currentQuestionIndex]);
    }

    public function startQuiz()
    {
        if (!auth()->check()) {
            $this->info(__('Log in'));

            return $this->redirectIntended(route('login'), true);
        }

        $this->attempt = QuizAttempt::create([
            'user_id'   => auth()->id(),
            'quiz_id'   => $this->quiz->id,
            'started_at' => now(),
            'answers'   => [],
            'score'     => 0,
            'is_passed' => false,
        ]);

        $this->quizStarted = true;
        $this->startTime = now();
        $this->answers = [];
        $this->currentQuestionIndex = 0;
        $this->calculateTimeRemaining();
        $this->dispatch('start-timer');

        $this->success(__('Quiz started! Good luck! 🎯'));
    }

    public function calculateTimeRemaining(): void
    {
        if ($this->quiz->time_limit && $this->startTime) {
            $endTime = $this->startTime->copy()->addMinutes($this->quiz->time_limit);
            $remaining = now()->diffInSeconds($endTime, false);
            $this->timeRemaining = max(0, $remaining);

            if ($this->timeRemaining <= 0 && !$this->quizCompleted) {
                $this->autoSubmitQuiz();
            }
        }
    }

    public function updateTimer(): void
    {
        if ($this->quiz->time_limit && $this->startTime && !$this->quizCompleted) {
            $endTime = $this->startTime->copy()->addMinutes($this->quiz->time_limit);
            $remaining = now()->diffInSeconds($endTime, false);
            $this->timeRemaining = max(0, $remaining);

            if ($this->timeRemaining <= 0 && !$this->quizCompleted) {
                $this->autoSubmitQuiz();
            }
        }
    }

    public function autoSubmitQuiz(): void
    {
        if ($this->quizCompleted) return;
        $this->submitQuiz();
        $this->warning(__('Time has expired! The quiz was automatically submitted. ⏰'));
    }

    public function saveAnswer($answer): void
    {
        $this->answers[$this->currentQuestionIndex] = $answer;

        if ($this->attempt) {
            $this->attempt->update([
                'answers'         => $this->answers,
                'last_activity_at' => now(),
            ]);
        }
    }

    public function nextQuestion(): void
    {
        if ($this->currentQuestionIndex < $this->totalQuestions - 1) {
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
        if ($this->quizCompleted) return;

        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($this->questions as $index => $question) {
            $points = $question->points;
            $totalPoints += $points;

            $userAnswer = $this->answers[$index] ?? null;
            if ($this->isAnswerCorrect($question, $userAnswer)) {
                $earnedPoints += $points;
            }
        }

        $percentage = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
        $passed = $percentage >= ($this->quiz->passing_score ?? 70);

        $this->attempt->update([
            'completed_at' => now(),
            'answers'      => $this->answers,
            'score'        => $earnedPoints,
            'is_passed'    => $passed,
        ]);

        $this->quizCompleted = true;

        if ($passed) {
            $this->success(__('Congratulations! You passed the quiz! 🎉'));
        } else {
            $this->warning(__('Not this time. Keep learning and try again! 📚'));
        }
    }

    private function isAnswerCorrect($question, $answer): bool
    {
        if ($answer === null || $answer === '') return false;

        $correctAnswer = $question->correct_answer;
        if (is_string($correctAnswer)) {
            $correctAnswer = json_decode($correctAnswer, true);
        }
        $correctAnswerValue = is_array($correctAnswer) ? $correctAnswer[0] : $correctAnswer;

        return match($question->type) {
            'multiple_choice' => $answer == $correctAnswerValue,
            'true_false'      => $answer == $correctAnswerValue,
            'short_answer', 'text' => strtolower(trim($answer ?? '')) == strtolower(trim($correctAnswerValue ?? '')),
            default => false,
        };
    }

    public function viewResults(): void
    {
        $this->redirectRoute('student.quiz.results', ['attempt' => $this->attempt->id]);
    }

    public function getFormattedTime(): string
    {
        if ($this->timeRemaining === null) return '--:--';
        $minutes = floor($this->timeRemaining / 60);
        $seconds = $this->timeRemaining % 60;
        return sprintf("%02d:%02d", $minutes, $seconds);
    }

    public function getTimeColor(): string
    {
        if ($this->timeRemaining === null) return 'text-gray-700';
        if ($this->timeRemaining < 60) return 'text-red-600 font-bold';
        if ($this->timeRemaining < 300) return 'text-orange-600';
        return 'text-gray-700';
    }

    public function getStructuredDataProperty(): string
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Quiz',
            'name' => $this->quiz->title,
            'description' => strip_tags($this->quiz->description ?? ''),
            'educationalLevel' => $this->quiz->lesson->course->level ?? 'A1',
            'assesses' => __('German language proficiency'),
            'numberOfQuestions' => $this->questions->count(),
        ];

        return json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    public function render()
    {
        return $this->view([
            'questions'       => $this->questions,
            'totalQuestions'  => $this->totalQuestions,
            'currentQuestion' => $this->currentQuestion,
            'progress'        => $this->progress,
            'hasAnswer'       => $this->hasAnswerForCurrent,
            'formattedTime'   => $this->getFormattedTime(),
            'timeColor'       => $this->getTimeColor(),
        ])->layoutData([
            'structuredData' => $this->structuredData,
        ]);
    }
};

?>

{{-- SEO Meta Tags --}}
@section('meta_title', $this->quiz->meta_title ?? $this->quiz->title . ' - Quiz - ' . $this->quiz->lesson->course->title . ' - ' . config('app.name'))
@section('meta_description', $this->quiz->meta_description ?? Str::limit(strip_tags($this->quiz->description ?? ''), 160))
@section('meta_keywords', $this->quiz->meta_keywords ?? 'German quiz, ' . $this->quiz->title . ', test German, ' . ($this->quiz->lesson->course->level ?? 'A1'))
@section('og_title', $this->quiz->og_title ?? $this->quiz->title)
@section('og_description', $this->quiz->og_description ?? strip_tags($this->quiz->description ?? 'Test your German knowledge'))
@section('og_image', $this->quiz->og_image ?? ($this->quiz->lesson->course->thumbnail ? asset('storage/' . $this->quiz->lesson->course->thumbnail) : asset('images/og-image.jpg')))
@section('canonical_url', $this->quiz->canonical_url ?? url()->current())
@section('meta_robots', $this->quiz->robots ?? 'index,follow')

<div class="py-4 md:py-6"
     x-data="{
         timerInterval: null,
         initTimer() {
             if (this.timerInterval) clearInterval(this.timerInterval);
             this.timerInterval = setInterval(() => {
                 $wire.updateTimer();
             }, 1000);
         }
     }"
     x-init="initTimer()"
     x-on:start-timer.window="initTimer()">

    <div class="max-w-3xl px-3 mx-auto md:px-4">

        {{-- Fil d’Ariane --}}
        <div class="mb-5">
            <a href="{{ route('student.course.show', $quiz->lesson->course) }}" wire:navigate
               class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to course') }}
            </a>
        </div>

        {{-- En-tête --}}
        <div class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <div class="flex flex-wrap gap-2">
                    <x-badge value="Quiz" icon="o-document-text" class="badge-primary badge-soft" />
                    @if($quiz->time_limit)
                        <x-badge :value="'⏱️ ' . $quiz->time_limit . ' min'" class="badge-info badge-soft" />
                    @endif
                </div>
                @if($quizStarted && !$quizCompleted && $quiz->time_limit)
                    <div class="px-3 py-1 rounded-lg bg-base-200 {{ $timeColor }}">
                        <span class="font-mono text-sm font-bold">⏰ {{ $formattedTime }}</span>
                    </div>
                @endif
            </div>
            <h1 class="text-2xl font-bold md:text-3xl">{{ $quiz->title }}</h1>
            <p class="mt-1 text-base-content/70">{{ $quiz->lesson->course->title }}</p>
        </div>

        @if(!$quizStarted)
            {{-- Écran de démarrage --}}
            <x-card class="text-center">
                <x-icon name="o-document-text" class="w-16 h-16 mx-auto mb-4 text-primary" />
                <h2 class="mb-2 text-xl font-bold">{{ __('Start quiz') }}</h2>
                <p class="mb-4 text-base-content/70">
                    {{ __('This quiz contains :count questions.', ['count' => $totalQuestions]) }}
                    @if($quiz->time_limit)
                        {{ __('You have :minutes minutes to complete it.', ['minutes' => $quiz->time_limit]) }}
                    @endif
                </p>
                <div class="p-4 mb-6 text-left rounded-lg bg-base-200">
                    <p class="mb-2 font-medium">{{ __('📋 Information') }}</p>
                    <ul class="space-y-1 text-sm text-base-content/70">
                        <li>• {{ __('Answer all questions') }}</li>
                        <li>• {{ __('You can navigate between questions') }}</li>
                        <li>• {{ __('Passing score') }}: {{ $quiz->passing_score ?? 70 }}%</li>
                        @if($quiz->time_limit)
                            <li>• {{ __('The quiz will be submitted automatically when time runs out') }}</li>
                        @endif
                    </ul>
                </div>
                <x-button wire:click="startQuiz" label="{{ __('Start quiz') }}" icon="o-play" class="px-8 py-3 text-lg btn-primary" spinner />
            </x-card>

        @elseif(!$quizCompleted)
            {{-- Quiz en cours --}}
            <x-card>
                {{-- Barre de progression --}}
                <div class="mb-6">
                    <div class="flex justify-between mb-2 text-sm text-base-content/70">
                        <span>{{ __('Progress') }}</span>
                        <span>{{ $progress }}%</span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-base-200">
                        <div class="h-2 transition-all duration-300 rounded-full bg-primary" style="width: {{ $progress }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-base-content/60">
                        {{ __('Question :current of :total', ['current' => $currentQuestionIndex + 1, 'total' => $totalQuestions]) }}
                    </p>
                </div>

                {{-- Question --}}
                <div class="mb-6">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="text-sm font-medium text-base-content/70">{{ __('Question') }} {{ $currentQuestionIndex + 1 }}</span>
                        <x-badge :value="$currentQuestion->points . ' ' . __('points')" class="badge-soft badge-neutral" />
                    </div>
                    <p class="mb-6 text-lg font-medium">{{ $currentQuestion->question }}</p>

                    {{-- Type de réponse --}}
                    @if($currentQuestion->type === 'multiple_choice')
                        @php
                            $options = $currentQuestion->options;
                            if (is_string($options)) $options = json_decode($options, true);
                        @endphp
                        <div class="space-y-3">
                            @foreach($options ?? [] as $index => $option)
                                <div class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition
                                            {{ (isset($answers[$currentQuestionIndex]) && $answers[$currentQuestionIndex] == $option) ? 'border-primary bg-primary/10' : 'border-base-200 hover:bg-base-200' }}"
                                     wire:click="saveAnswer('{{ addslashes($option) }}')">
                                    <div class="w-6 h-6 rounded-full border flex items-center justify-center text-sm font-medium
                                                {{ (isset($answers[$currentQuestionIndex]) && $answers[$currentQuestionIndex] == $option) ? 'bg-primary border-primary text-white' : 'border-base-300' }}">
                                        {{ chr(65 + $index) }}
                                    </div>
                                    <span class="flex-1">{{ $option }}</span>
                                </div>
                            @endforeach
                        </div>

                    @elseif($currentQuestion->type === 'true_false')
                        <div class="grid grid-cols-2 gap-4">
                            <div class="cursor-pointer" wire:click="saveAnswer('true')">
                                <div class="p-4 border-2 rounded-xl text-center transition
                                            {{ (isset($answers[$currentQuestionIndex]) && $answers[$currentQuestionIndex] === 'true') ? 'border-success bg-success/10' : 'border-base-200 hover:border-success/50' }}">
                                    <x-icon name="o-check-circle" class="w-8 h-8 mx-auto mb-2 text-success" />
                                    <span class="font-medium text-success">{{ __('True') }}</span>
                                </div>
                            </div>
                            <div class="cursor-pointer" wire:click="saveAnswer('false')">
                                <div class="p-4 border-2 rounded-xl text-center transition
                                            {{ (isset($answers[$currentQuestionIndex]) && $answers[$currentQuestionIndex] === 'false') ? 'border-error bg-error/10' : 'border-base-200 hover:border-error/50' }}">
                                    <x-icon name="o-x-circle" class="w-8 h-8 mx-auto mb-2 text-error" />
                                    <span class="font-medium text-error">{{ __('False') }}</span>
                                </div>
                            </div>
                        </div>

                    @else
                        <textarea
                            wire:model="answers.{{ $currentQuestionIndex }}"
                            wire:change="saveAnswer($event.target.value)"
                            placeholder="{{ __('Enter your answer here...') }}"
                            rows="4"
                            class="w-full px-4 py-3 border rounded-lg focus:ring-primary focus:border-primary"></textarea>
                    @endif
                </div>

                {{-- Navigation --}}
                <div class="flex items-center justify-between pt-4 border-t">
                    <x-button wire:click="previousQuestion" label="{{ __('Previous') }}" icon="o-arrow-left" class="btn-ghost" :disabled="$currentQuestionIndex === 0" />
                    <div class="flex gap-2">
                        @if($currentQuestionIndex < $totalQuestions - 1)
                            <x-button wire:click="nextQuestion" label="{{ __('Next') }}" icon-right="o-arrow-right" class="btn-primary" />
                        @else
                            <x-button wire:click="submitQuiz" label="{{ __('Submit quiz') }}" icon="o-check" class="btn-success" wire:confirm="{{ __('Are you sure you want to submit the quiz?') }}" />
                        @endif
                    </div>
                </div>
            </x-card>

        @else
            {{-- Écran de résultats --}}
            <x-card class="text-center">
                @if($attempt->is_passed)
                    <x-icon name="o-trophy" class="w-20 h-20 mx-auto mb-4 text-warning" />
                    <h2 class="mb-2 text-2xl font-bold text-success">{{ __('Quiz passed! 🎉') }}</h2>
                @else
                    <x-icon name="o-academic-cap" class="w-20 h-20 mx-auto mb-4 text-primary" />
                    <h2 class="mb-2 text-2xl font-bold text-warning">{{ __('Quiz completed') }}</h2>
                @endif
                <p class="mb-6 text-base-content/70">
                    {{ __('You scored :score points.', ['score' => $attempt->score]) }}
                </p>
                <div class="flex flex-col justify-center gap-3 sm:flex-row">
                    <x-button wire:click="viewResults" label="{{ __('View results 📊') }}" icon="o-chart-bar" class="btn-primary" />
                    <x-button link="{{ route('student.course.show', $quiz->lesson->course) }}" label="{{ __('Back to course') }}" icon="o-arrow-left" class="btn-outline" />
                </div>
            </x-card>
        @endif
    </div>
</div>
