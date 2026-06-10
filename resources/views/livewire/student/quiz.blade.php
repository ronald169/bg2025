<?php

use App\Models\Quiz;
use App\Models\QuizAttempt;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;

new
#[Title('Quiz - Deutsch lernen')]
#[Layout('components.layouts.guest')]
class extends Component {
    use Toast;

    public Quiz $quiz;
    public $attempt = null;
    public $answers = [];
    public $currentQuestionIndex = 0;
    public $timeRemaining = null;
    public $quizStarted = false;
    public $quizCompleted = false;
    public $startTime = null;
    public $timerInterval = null;

    public function mount(Quiz $quiz): void
    {
        $this->quiz = $quiz->load(['questions', 'lesson.course']);

        // Vérifier si l'utilisateur a déjà une tentative en cours
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

    #[Computed]
    public function questions()
    {
        return $this->quiz->questions;
    }

    #[Computed]
    public function totalQuestions()
    {
        return $this->questions->count();
    }

    #[Computed]
    public function currentQuestion()
    {
        return $this->questions[$this->currentQuestionIndex] ?? null;
    }

    #[Computed]
    public function progress()
    {
        if ($this->totalQuestions === 0) return 0;
        $answered = count(array_filter($this->answers));
        return round(($answered / $this->totalQuestions) * 100);
    }

    #[Computed]
    public function hasAnswerForCurrent()
    {
        return isset($this->answers[$this->currentQuestionIndex]);
    }

    public function startQuiz(): void
    {
        $this->attempt = QuizAttempt::create([
            'user_id' => auth()->id(),
            'quiz_id' => $this->quiz->id,
            'started_at' => now(),
            'answers' => [],
            'score' => 0,
            'is_passed' => false,
        ]);

        $this->quizStarted = true;
        $this->startTime = now();
        $this->answers = [];
        $this->currentQuestionIndex = 0;
        $this->calculateTimeRemaining();
        $this->dispatch('start-timer');

        $this->success('Quiz gestartet! Viel Erfolg! 🎯');
    }

    public function calculateTimeRemaining(): void
    {
        if ($this->quiz->time_limit && $this->startTime) {
            $endTime = $this->startTime->copy()->addMinutes($this->quiz->time_limit);
            $remaining = now()->diffInSeconds($endTime, false);
            $this->timeRemaining = max(0, $remaining);

            // Si le temps est écoulé, soumettre automatiquement
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

            // Auto-submit quand le temps est écoulé
            if ($this->timeRemaining <= 0 && !$this->quizCompleted) {
                $this->autoSubmitQuiz();
            }
        }
    }

    public function autoSubmitQuiz(): void
    {
        if ($this->quizCompleted) return;

        $this->submitQuiz();
        $this->warning('Zeit abgelaufen! Das Quiz wurde automatisch abgeschlossen. ⏰');
    }

    public function saveAnswer($answer): void
    {
        $this->answers[$this->currentQuestionIndex] = $answer;

        // Sauvegarder la progression
        if ($this->attempt) {
            $this->attempt->update([
                'answers' => $this->answers,
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

        // Calculer le score
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

        // Mettre à jour la tentative
        $this->attempt->update([
            'completed_at' => now(),
            'answers' => $this->answers,
            'score' => $earnedPoints,
            'is_passed' => $passed,
        ]);

        $this->quizCompleted = true;

        if ($passed) {
            $this->success('Glückwunsch! Du hast das Quiz bestanden! 🎉');
        } else {
            $this->warning('Leider nicht bestanden. Versuche es erneut! 📚');
        }
    }

    private function isAnswerCorrect($question, $answer): bool
    {
        // Si la réponse est null ou vide
        if ($answer === null || $answer === '') {
            return false;
        }

        // Récupérer la réponse correcte
        $correctAnswer = $question->correct_answer;
        if (is_string($correctAnswer)) {
            $correctAnswer = json_decode($correctAnswer, true);
        }

        $correctAnswerValue = is_array($correctAnswer) ? $correctAnswer[0] : $correctAnswer;

        return match($question->type) {
            'multiple_choice' => $answer == $correctAnswerValue,
            'true_false' => $answer == $correctAnswerValue,
            'short_answer', 'text' => strtolower(trim($answer ?? '')) == strtolower(trim($correctAnswerValue ?? '')),
            default => false,
        };
    }

    public function viewResults(): void
    {
        $this->redirectRoute('student.quiz.results', ['attempt' => $this->attempt->id], navigate: true);
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
            'assesses' => 'German language proficiency',
            'numberOfQuestions' => $this->questions->count(),
        ];

        return json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    public function render()
    {
        return view()->layoutData([
            'structuredData' => $this->structuredData,
        ]);
    }
}
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

<div class="py-8"
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
    <div class="max-w-3xl px-4 mx-auto">

        <!-- Navigation -->
        <div class="mb-6">
            <a href="{{ route('student.course.show', $this->quiz->lesson->course) }}"
               class="inline-flex items-center gap-1 text-[#FF6B35] hover:text-[#E55A2A] transition">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Zurück zum Kurs') }}
            </a>
        </div>

        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-1 text-xs rounded-full bg-[#FF6B35]/10 text-[#FF6B35]">
                        📝 Quiz
                    </span>
                    @if($this->quiz->time_limit)
                        <span class="px-2 py-1 text-xs text-blue-700 bg-blue-100 rounded-full">
                            ⏱️ {{ $this->quiz->time_limit }} Minuten
                        </span>
                    @endif
                </div>
                @if($quizStarted && !$quizCompleted && $this->quiz->time_limit)
                    <div class="px-3 py-1 rounded-lg {{ $this->getTimeColor() }} bg-gray-100">
                        <span class="font-mono text-sm font-bold">⏰ {{ $this->getFormattedTime() }}</span>
                    </div>
                @endif
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $this->quiz->title }}</h1>
            <p class="mt-1 text-gray-600">{{ $this->quiz->lesson->course->title }}</p>
        </div>

        @if(!$quizStarted)
            <!-- Écran de démarrage -->
            <x-card class="text-center border-0 shadow-md">
                <x-icon name="o-document-text" class="w-16 h-16 mx-auto text-[#FF6B35] mb-4" />
                <h2 class="mb-2 text-xl font-bold text-gray-900">Quiz starten</h2>
                <p class="mb-4 text-gray-600">
                    Dieses Quiz enthält {{ $this->totalQuestions }} Fragen.
                    @if($this->quiz->time_limit)
                        Du hast {{ $this->quiz->time_limit }} Minuten Zeit.
                    @endif
                </p>
                <div class="p-4 mb-6 text-left rounded-lg bg-gray-50">
                    <p class="mb-2 font-medium text-gray-900">📋 Hinweise:</p>
                    <ul class="space-y-1 text-sm text-gray-600">
                        <li>• Beantworte alle Fragen</li>
                        <li>• Du kannst zwischen den Fragen navigieren</li>
                        <li>• Bestehensgrenze: {{ $this->quiz->passing_score ?? 70 }}%</li>
                        @if($this->quiz->time_limit)
                            <li>• Das Quiz wird automatisch abgegeben, wenn die Zeit abläuft</li>
                        @endif
                    </ul>
                </div>
                <x-button
                    wire:click="startQuiz"
                    label="Quiz starten →"
                    icon="o-play"
                    class="px-8 py-3 text-lg btn-primary" />
            </x-card>

        @elseif(!$quizCompleted)
            <!-- Quiz en cours -->
            <x-card class="overflow-hidden border-0 shadow-md">
                <!-- Progress Bar -->
                <div class="mb-6">
                    <div class="flex justify-between mb-2 text-sm text-gray-600">
                        <span>Fortschritt</span>
                        <span>{{ $this->progress }}%</span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 rounded-full">
                        <div class="h-2 rounded-full bg-[#FF6B35] transition-all duration-300"
                             style="width: {{ $this->progress }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">
                        Frage {{ $this->currentQuestionIndex + 1 }} von {{ $this->totalQuestions }}
                    </p>
                </div>

                <!-- Question -->
                <div class="mb-6">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="text-sm font-medium text-gray-500">Frage {{ $this->currentQuestionIndex + 1 }}</span>
                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">
                            {{ $this->currentQuestion->points }} Punkte
                        </span>
                    </div>
                    <p class="mb-6 text-lg font-medium text-gray-900">{{ $this->currentQuestion->question }}</p>

                    <!-- Réponses selon le type -->
                    @if($this->currentQuestion->type === 'multiple_choice')
                        @php
                            // Décoder les options
                            $options = $this->currentQuestion->options;
                            if (is_string($options)) {
                                $options = json_decode($options, true);
                            }
                        @endphp
                        <div class="space-y-3">
                            @foreach($options ?? [] as $index => $option)
                                <div class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition
                                            {{ isset($this->answers[$this->currentQuestionIndex]) && $this->answers[$this->currentQuestionIndex] == $option
                                               ? 'border-[#FF6B35] bg-orange-50' : 'border-gray-200 hover:bg-gray-50' }}"
                                     wire:click="saveAnswer('{{ addslashes($option) }}')">
                                    <div class="w-6 h-6 rounded-full border flex items-center justify-center text-sm font-medium
                                                {{ isset($this->answers[$this->currentQuestionIndex]) && $this->answers[$this->currentQuestionIndex] == $option
                                                   ? 'bg-[#FF6B35] border-[#FF6B35] text-white' : 'border-gray-300 text-gray-500' }}">
                                        {{ chr(65 + $index) }}
                                    </div>
                                    <span class="flex-1">{{ $option }}</span>
                                </div>
                            @endforeach
                        </div>

                    @elseif($this->currentQuestion->type === 'true_false')
                        <div class="grid grid-cols-2 gap-4">
                            <div class="cursor-pointer" wire:click="saveAnswer('true')">
                                <div class="p-4 border-2 rounded-xl text-center transition
                                            {{ isset($this->answers[$this->currentQuestionIndex]) && $this->answers[$this->currentQuestionIndex] === 'true'
                                               ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-green-300' }}">
                                    <x-icon name="o-check-circle" class="w-8 h-8 mx-auto mb-2 text-green-600" />
                                    <span class="font-medium text-green-700">Richtig</span>
                                </div>
                            </div>
                            <div class="cursor-pointer" wire:click="saveAnswer('false')">
                                <div class="p-4 border-2 rounded-xl text-center transition
                                            {{ isset($this->answers[$this->currentQuestionIndex]) && $this->answers[$this->currentQuestionIndex] === 'false'
                                               ? 'border-red-500 bg-red-50' : 'border-gray-200 hover:border-red-300' }}">
                                    <x-icon name="o-x-circle" class="w-8 h-8 mx-auto mb-2 text-red-600" />
                                    <span class="font-medium text-red-700">Falsch</span>
                                </div>
                            </div>
                        </div>

                    @else
                        <textarea
                            wire:model.live="answers.{{ $this->currentQuestionIndex }}"
                            wire:change="saveAnswer($event.target.value)"
                            placeholder="Deine Antwort hier eingeben..."
                            rows="4"
                            class="w-full px-4 py-3 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]"></textarea>
                    @endif
                </div>

                <!-- Navigation -->
                <div class="flex items-center justify-between pt-4 border-t">
                    <x-button
                        wire:click="previousQuestion"
                        label="Zurück"
                        icon="o-arrow-left"
                        class="btn-ghost"
                        :disabled="$currentQuestionIndex === 0" />

                    <div class="flex gap-2">
                        @if($this->currentQuestionIndex < $this->totalQuestions - 1)
                            <x-button
                                wire:click="nextQuestion"
                                label="Weiter"
                                icon-right="o-arrow-right"
                                class="btn-primary" />
                        @else
                            <x-button
                                wire:click="submitQuiz"
                                label="Quiz abschließen ✓"
                                icon="o-check"
                                class="btn-success"
                                wire:confirm="Möchtest du das Quiz wirklich abschließen?" />
                        @endif
                    </div>
                </div>
            </x-card>

        @else
            <!-- Quiz terminé - Écran de résultats -->
            <x-card class="text-center border-0 shadow-md">
                @if($this->attempt->is_passed)
                    <x-icon name="o-trophy" class="w-20 h-20 mx-auto mb-4 text-yellow-500" />
                    <h2 class="mb-2 text-2xl font-bold text-green-700">Quiz bestanden! 🎉</h2>
                @else
                    <x-icon name="o-academic-cap" class="w-20 h-20 mx-auto text-[#FF6B35] mb-4" />
                    <h2 class="mb-2 text-2xl font-bold text-orange-700">Quiz abgeschlossen</h2>
                @endif

                <p class="mb-6 text-gray-600">
                    Du hast {{ $this->attempt->score }} Punkte erreicht.
                </p>

                <div class="flex flex-col justify-center gap-3 sm:flex-row">
                    <x-button
                        wire:click="viewResults"
                        label="Ergebnisse anzeigen 📊"
                        icon="o-chart-bar"
                        class="btn-primary" />

                    <x-button
                        link="{{ route('student.course.show', $this->quiz->lesson->course) }}"
                        label="Zum Kurs zurück"
                        icon="o-arrow-left"
                        class="btn-outline" />
                </div>
            </x-card>
        @endif

        <!-- Note MVP -->
        <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="font-medium text-blue-800">MVP Version</p>
                    <p class="text-sm text-blue-700">Prends ton temps pour répondre. Les résultats seront sauvegardés automatiquement.</p>
                </div>
            </div>
        </div>
    </div>
</div>
