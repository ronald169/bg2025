<?php

use App\Models\QuizAttempt;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Quiz-Ergebnisse - Deutsch lernen')]
#[Layout('components.layouts.dashboard-student')]
class extends Component {
    use Toast;

    public QuizAttempt $attempt;
    public $showSolutions = false;

    public function mount(QuizAttempt $attempt): void
    {
        // Vérifier que l'utilisateur est propriétaire
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        $this->attempt = $attempt->load(['quiz.lesson.course', 'quiz.questions']);
    }

    #[Computed]
    public function score()
    {
        return $this->attempt->score;
    }

    #[Computed]
    public function passed()
    {
        return $this->attempt->is_passed;
    }

    #[Computed]
    public function percentage()
    {
        $totalPoints = $this->attempt->quiz->questions->sum('points');
        if ($totalPoints === 0) return 0;
        return round(($this->score / $totalPoints) * 100);
    }

    #[Computed]
    public function totalPoints()
    {
        return $this->attempt->quiz->questions->sum('points');
    }

    #[Computed]
    public function totalQuestions()
    {
        return $this->attempt->quiz->questions->count();
    }

    #[Computed]
    public function timeTaken()
    {
        if ($this->attempt->started_at && $this->attempt->completed_at) {
            $seconds = $this->attempt->started_at->diffInSeconds($this->attempt->completed_at);

            $minutes = floor($seconds / 60);
            $secs = $seconds % 60;

            if ($minutes > 0) {
                return $minutes . ' min';
            }

            return $secs . ' sec';
        }
        return '-';
    }

    #[Computed]
    public function questionsWithAnswers()
    {
        $answers = $this->attempt->answers ?? [];
        $results = [];

        foreach ($this->attempt->quiz->questions as $index => $question) {
            // Décoder les options si nécessaire
            $options = $question->options;
            if (is_string($options)) {
                $options = json_decode($options, true);
            }

            $userAnswer = $answers[$index] ?? null;
            $correctAnswer = $question->correct_answer;
            if (is_string($correctAnswer)) {
                $correctAnswer = json_decode($correctAnswer, true);
            }

            $isCorrect = $this->isAnswerCorrect($question, $userAnswer);

            // Formater les réponses pour l'affichage
            $formattedUserAnswer = $this->formatAnswer($question, $userAnswer, $options);
            $formattedCorrectAnswer = $this->formatAnswer($question, $correctAnswer[0] ?? null, $options);

            $results[] = [
                'id' => $question->id,
                'question' => $question->question,
                'type' => $question->type,
                'options' => $options,
                'user_answer' => $userAnswer,
                'formatted_user_answer' => $formattedUserAnswer,
                'correct_answer' => $correctAnswer,
                'formatted_correct_answer' => $formattedCorrectAnswer,
                'is_correct' => $isCorrect,
                'points' => $question->points,
                'explanation' => $question->explanation,
            ];
        }

        return $results;
    }

    #[Computed]
    public function correctCount()
    {
        return collect($this->questionsWithAnswers)->where('is_correct', true)->count();
    }

    #[Computed]
    public function earnedPoints()
    {
        return collect($this->questionsWithAnswers)->sum(function ($q) {
            return $q['is_correct'] ? $q['points'] : 0;
        });
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

    private function formatAnswer($question, $answer, $options = null): string
    {
        // Si la réponse est null ou vide
        if ($answer === null || $answer === '') {
            return 'Nicht beantwortet';
        }

        // Si on a des options et que c'est une question à choix multiples
        if ($question->type === 'multiple_choice' && $options) {
            // Chercher l'index de la réponse dans les options
            $index = array_search($answer, $options);
            if ($index !== false) {
                $letter = chr(65 + $index);
                return "{$letter}. {$answer}";
            }
            return $answer;
        }

        if ($question->type === 'true_false') {
            return $answer === 'true' ? 'Richtig' : 'Falsch';
        }

        return $answer;
    }

    public function retakeQuiz(): void
    {
        $this->redirectRoute('student.quiz.show', $this->attempt->quiz, navigate: true);
    }

    public function toggleSolutions(): void
    {
        $this->showSolutions = !$this->showSolutions;
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
}
?>

<div class="py-8">
    <div class="max-w-4xl px-4 mx-auto">

        <!-- Navigation -->
        <div class="mb-6">
            <a href="{{ route('student.course.show', $this->attempt->quiz->lesson->course) }}"
               class="inline-flex items-center gap-1 text-[#FF6B35] hover:text-[#E55A2A] transition">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Zurück zum Kurs') }}
            </a>
        </div>

        <!-- Result Card -->
        <div class="mb-6 overflow-hidden bg-white shadow-sm rounded-xl">
            <div class="p-8 text-center {{ $this->passed ? 'bg-gradient-to-r from-green-50 to-emerald-50' : 'bg-gradient-to-r from-red-50 to-orange-50' }}">
                @if($this->passed)
                    <div class="flex items-center justify-center w-20 h-20 mx-auto mb-4 bg-green-500 rounded-full shadow-lg">
                        <x-icon name="o-check" class="w-10 h-10 text-white" />
                    </div>
                    <h1 class="text-2xl font-bold text-green-800">{{ __('Herzlichen Glückwunsch!') }} 🎉</h1>
                    <p class="mt-2 text-green-600">{{ __('Du hast das Quiz bestanden!') }}</p>
                @else
                    <div class="flex items-center justify-center w-20 h-20 mx-auto mb-4 bg-red-500 rounded-full shadow-lg">
                        <x-icon name="o-x-mark" class="w-10 h-10 text-white" />
                    </div>
                    <h1 class="text-2xl font-bold text-red-800">{{ __('Nicht bestanden') }}</h1>
                    <p class="mt-2 text-red-600">{{ __('Übe weiter und versuche es erneut!') }} 📚</p>
                @endif
            </div>

            <div class="p-6">
                <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4">
                    <div class="p-3 text-center rounded-lg bg-gray-50">
                        <p class="text-xs text-gray-500">Ergebnis</p>
                        <p class="text-xl font-bold {{ $this->getScoreColor($this->percentage) }}">
                            {{ $this->percentage }}%
                        </p>
                    </div>
                    <div class="p-3 text-center rounded-lg bg-gray-50">
                        <p class="text-xs text-gray-500">Punkte</p>
                        <p class="text-xl font-bold text-gray-900">
                            {{ $this->earnedPoints }}/{{ $this->totalPoints }}
                        </p>
                    </div>
                    <div class="p-3 text-center rounded-lg bg-gray-50">
                        <p class="text-xs text-gray-500">Fragen</p>
                        <p class="text-xl font-bold text-gray-900">
                            {{ $this->correctCount }}/{{ $this->totalQuestions }}
                        </p>
                    </div>
                    <div class="p-3 text-center rounded-lg bg-gray-50">
                        <p class="text-xs text-gray-500">Zeit</p>
                        <p class="text-xl font-bold text-gray-900">
                            {{ $this->timeTaken }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap justify-center gap-3">
                    <x-button
                        link="{{ route('student.course.show', $this->attempt->quiz->lesson->course) }}"
                        icon="o-arrow-left"
                        class="btn-ghost">
                        Zum Kurs
                    </x-button>

                    @if(!$this->passed)
                        <x-button
                            wire:click="retakeQuiz"
                            icon="o-arrow-path"
                            class="btn-primary">
                            Quiz wiederholen
                        </x-button>
                    @endif

                    <x-button
                        wire:click="toggleSolutions"
                        :icon="$showSolutions ? 'o-eye-slash' : 'o-eye'"
                        class="btn-outline">
                        @if($showSolutions)
                            Lösungen ausblenden
                        @else
                            Lösungen anzeigen
                        @endif
                    </x-button>
                </div>
            </div>
        </div>

        <!-- Detailed Results -->
        @if($showSolutions)
        <x-card class="shadow-sm">
            <div class="flex items-center justify-between pb-2 mb-4 border-b">
                <div class="flex items-center gap-2">
                    <x-icon name="o-document-text" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Detaillierte Ergebnisse') }}</h2>
                </div>
                <span class="text-xs text-gray-500">{{ __('Klicke auf eine Frage für mehr Details') }}</span>
            </div>

            <div class="space-y-3">
                @foreach($this->questionsWithAnswers as $index => $question)
                <div x-data="{ expanded: false }" class="overflow-hidden border rounded-lg">
                    <div
                        @click="expanded = !expanded"
                        class="flex items-center justify-between p-4 cursor-pointer transition
                               {{ $question['is_correct'] ? 'hover:bg-green-50' : 'hover:bg-red-50' }}
                               {{ $question['is_correct'] ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-red-500' }}">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full
                                        {{ $question['is_correct'] ? 'bg-green-100' : 'bg-red-100' }}">
                                @if($question['is_correct'])
                                    <x-icon name="o-check" class="w-4 h-4 text-green-600" />
                                @else
                                    <x-icon name="o-x-mark" class="w-4 h-4 text-red-600" />
                                @endif
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Frage {{ $index + 1 }}</p>
                                <p class="text-sm text-gray-600 line-clamp-1">{{ $question['question'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-1 text-xs rounded-full {{ $question['is_correct'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $question['points'] }} Punkte
                            </span>
                            <x-icon name="o-chevron-down" class="w-5 h-5 text-gray-400 transition-transform"
                                    x-bind:class="expanded ? 'rotate-180' : ''" />
                        </div>
                    </div>

                    <div x-show="expanded" x-collapse class="p-4 border-t bg-gray-50">
                        <!-- Question complète -->
                        <div class="mb-3">
                            <p class="mb-1 text-sm font-medium text-gray-700">📝 Frage:</p>
                            <p class="text-gray-900">{{ $question['question'] }}</p>
                        </div>

                        <!-- Réponse de l'utilisateur -->
                        <div class="mb-3">
                            <p class="mb-1 text-sm font-medium text-gray-700">✏️ Deine Antwort:</p>
                            <p class="px-3 py-2 rounded-lg {{ $question['is_correct'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $question['formatted_user_answer'] }}
                            </p>
                        </div>

                        <!-- Réponse correcte (si incorrect) -->
                        @if(!$question['is_correct'])
                            <div class="mb-3">
                                <p class="mb-1 text-sm font-medium text-gray-700">✓ Richtige Antwort:</p>
                                <p class="px-3 py-2 text-green-800 bg-green-100 rounded-lg">
                                    {{ $question['formatted_correct_answer'] }}
                                </p>
                            </div>
                        @endif

                        <!-- Explication -->
                        @if($question['explanation'])
                            <div class="p-3 border border-blue-200 rounded-lg bg-blue-50">
                                <p class="flex items-center gap-1 mb-1 text-sm font-medium text-blue-800">
                                    <x-icon name="o-light-bulb" class="w-4 h-4" />
                                    Erklärung:
                                </p>
                                <p class="text-sm text-blue-700">{{ $question['explanation'] }}</p>
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </x-card>
        @endif

        <!-- Performance Feedback -->
        @if(!$this->passed)
        <div class="p-4 mt-6 border border-yellow-200 rounded-lg bg-yellow-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-light-bulb" class="w-5 h-5 text-yellow-600 mt-0.5" />
                <div>
                    <p class="font-medium text-yellow-800">{{ __('Tipps zur Verbesserung') }}</p>
                    <ul class="mt-2 space-y-1 text-sm text-yellow-700">
                        <li>• 📚 {{ __('Wiederhole die Kursinhalte zu den Themen, die du nicht verstanden hast') }}</li>
                        <li>• 📝 {{ __('Mache dir Notizen während des Lernens') }}</li>
                        <li>• 🃏 {{ __('Übe mit Karteikarten, um wichtige Konzepte zu lernen') }}</li>
                        <li>• 💬 {{ __('Frage im Kursforum nach, wenn du etwas nicht verstehst') }}</li>
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <!-- Actions supplémentaires -->
        <div class="flex justify-center gap-3 mt-6">
            <x-button
                link="{{ route('student.quiz-history') }}"
                icon="o-clock"
                class="btn-outline">
                Alle Quiz-Ergebnisse
            </x-button>
            @if($this->passed && $this->percentage >= 90)
                <x-button
                    link="#"
                    icon="o-share"
                    class="btn-primary">
                    Ergebnis teilen 🎉
                </x-button>
            @endif
        </div>

        <!-- Note MVP -->
        <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="font-medium text-blue-800">MVP Version</p>
                    <p class="text-sm text-blue-700">Prochaines fonctionnalités : comparaison avec la moyenne, recommandations personnalisées, export des résultats, et partage sur les réseaux sociaux.</p>
                </div>
            </div>
        </div>
    </div>
</div>
