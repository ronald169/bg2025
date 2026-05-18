<?php

use App\Models\Course;
use App\Models\Quiz;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Mary\Traits\Toast;

new
#[Title('Quiz Vorschau - Lehrer')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use Toast;

    public Course $course;
    public Quiz $quiz;
    public $questions = [];
    public $userAnswers = [];
    public $currentQuestionIndex = 0;
    public $showResults = false;

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
        // Vérifier que toutes les questions ont été répondues
        if (count($this->userAnswers) < count($this->questions)) {
            $this->warning('Bitte beantworte alle Fragen, bevor du das Quiz abschließt.');
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

    public function getScore(): array
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

    private function isAnswerCorrect($question, $answer): bool
    {
        // Récupérer la réponse correcte (c'est un tableau à cause du cast)
        $correctAnswerArray = $question->correct_answer;

        // Extraire la première valeur du tableau
        $correctAnswer = is_array($correctAnswerArray) ? $correctAnswerArray[0] : $correctAnswerArray;

        if ($question->type === 'multiple_choice') {
            // Pour multiple choice, l'answer est l'index (string) et correct_answer est l'index (string)
            return $answer == $correctAnswer;
        }

        if ($question->type === 'true_false') {
            return $answer == $correctAnswer;
        }

        if ($question->type === 'short_answer') {
            // Nettoyer les deux chaînes avant comparaison
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
}
?>

<div class="py-4 md:py-6">
    <div class="max-w-3xl px-3 mx-auto md:px-4">

        <!-- Navigation -->
        <div class="mb-5">
            <a href="{{ route('teacher.quizzes.edit', ['course' => $course, 'quiz' => $quiz]) }}" class="inline-flex items-center gap-1 text-sm text-[#FF6B35] hover:text-[#E55A2A] transition">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Zurück zum Quiz') }}
            </a>
        </div>

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-900 md:text-2xl">👁️ {{ __('Quiz Vorschau') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $quiz->title }}</p>
        </div>

        @if(!$showResults)
            <x-card class="border-0 shadow-sm">
                <!-- Progress -->
                <div class="mb-6">
                    <div class="flex justify-between mb-2 text-sm text-gray-600">
                        <span>{{ __('Fortschritt') }}</span>
                        <span>{{ count($this->userAnswers) }}/{{ count($questions) }} {{ __('beantwortet') }}</span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 rounded-full">
                        <div class="h-2 rounded-full bg-[#FF6B35] transition-all"
                             style="width: {{ count($questions) > 0 ? (count($this->userAnswers) / count($questions)) * 100 : 0 }}%"></div>
                    </div>
                </div>

                <!-- Current Question -->
                @php $question = $questions[$currentQuestionIndex]; @endphp
                <div class="mb-6">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="text-sm text-gray-500">{{ __('Frage') }} {{ $currentQuestionIndex + 1 }}/{{ count($questions) }}</span>
                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">{{ $question->points }} {{ __('Punkte') }}</span>
                    </div>

                    <p class="mb-6 text-lg font-medium text-gray-900">{{ $question->question }}</p>

                    <!-- Réponses selon le type -->
                    @if($question->type === 'multiple_choice')
                        <div class="space-y-3">
                            @foreach($question->options as $index => $option)
                                @if($option)
                                <div class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition
                                            {{ (isset($userAnswers[$currentQuestionIndex]) && $userAnswers[$currentQuestionIndex] == $index) ? 'border-[#FF6B35] bg-orange-50' : 'border-gray-200 hover:bg-gray-50' }}"
                                     wire:click="saveAnswer({{ $index }})">
                                    <div class="w-6 h-6 rounded-full border flex items-center justify-center text-sm font-medium
                                                {{ (isset($userAnswers[$currentQuestionIndex]) && $userAnswers[$currentQuestionIndex] == $index) ? 'bg-[#FF6B35] border-[#FF6B35] text-white' : 'border-gray-300 text-gray-500' }}">
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
                                            {{ (isset($userAnswers[$currentQuestionIndex]) && $userAnswers[$currentQuestionIndex] === 'true') ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-green-300' }}">
                                    <x-icon name="o-check-circle" class="w-8 h-8 mx-auto mb-2 text-green-600" />
                                    <span class="font-medium text-green-700">{{ __('Richtig') }}</span>
                                </div>
                            </div>
                            <div class="cursor-pointer" wire:click="saveAnswer('false')">
                                <div class="p-4 border-2 rounded-xl text-center transition
                                            {{ (isset($userAnswers[$currentQuestionIndex]) && $userAnswers[$currentQuestionIndex] === 'false') ? 'border-red-500 bg-red-50' : 'border-gray-200 hover:border-red-300' }}">
                                    <x-icon name="o-x-circle" class="w-8 h-8 mx-auto mb-2 text-red-600" />
                                    <span class="font-medium text-red-700">{{ __('Falsch') }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <textarea wire:model="userAnswers.{{ $currentQuestionIndex }}"
                                  wire:change="saveAnswer($event.target.value)"
                                  placeholder="{{ __('Deine Antwort hier...') }}"
                                  rows="4"
                                  class="w-full px-4 py-3 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]"></textarea>
                    @endif
                </div>

                <!-- Navigation -->
                <div class="flex items-center justify-between pt-4 border-t">
                    <x-button wire:click="previousQuestion"
                              label="{{ __('Zurück') }}"
                              icon="o-arrow-left"
                              class="btn-ghost"
                              :disabled="$currentQuestionIndex === 0" />

                    @if($currentQuestionIndex < count($questions) - 1)
                        <x-button wire:click="nextQuestion"
                                  label="{{ __('Weiter') }}"
                                  icon-right="o-arrow-right"
                                  class="btn-primary" />
                    @else
                        <x-button wire:click="submitQuiz"
                                  label="{{ __('Quiz abschließen') }}"
                                  icon="o-check"
                                  class="btn-success" />
                    @endif
                </div>
            </x-card>
        @else
            <!-- Résultats -->
            @php $score = $this->getScore(); @endphp
            <x-card class="text-center border-0 shadow-sm">
                @if($score['passed'])
                    <x-icon name="o-trophy" class="w-16 h-16 mx-auto mb-4 text-yellow-500" />
                    <h2 class="mb-2 text-2xl font-bold text-green-700">{{ __('Quiz bestanden!') }} 🎉</h2>
                @else
                    <x-icon name="o-academic-cap" class="w-16 h-16 mx-auto text-[#FF6B35] mb-4" />
                    <h2 class="mb-2 text-2xl font-bold text-orange-700">{{ __('Quiz abgeschlossen') }}</h2>
                @endif

                <div class="grid max-w-sm grid-cols-2 gap-4 mx-auto mb-6">
                    <div class="p-3 rounded-lg bg-gray-50">
                        <div class="text-2xl font-bold text-[#FF6B35]">{{ $score['percentage'] }}%</div>
                        <div class="text-xs text-gray-500">{{ __('Ergebnis') }}</div>
                    </div>
                    <div class="p-3 rounded-lg bg-gray-50">
                        <div class="text-2xl font-bold text-[#FF6B35]">{{ $score['earned_points'] }}/{{ $score['total_points'] }}</div>
                        <div class="text-xs text-gray-500">{{ __('Punkte') }}</div>
                    </div>
                </div>

                <div class="flex justify-center gap-3">
                    <x-button wire:click="resetQuiz"
                              label="{{ __('Quiz wiederholen') }}"
                              icon="o-arrow-path"
                              class="btn-outline" />
                    <a href="{{ route('teacher.quizzes.edit', ['course' => $course, 'quiz' => $quiz]) }}"
                       class="px-4 py-2 text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                        {{ __('Zurück zum Quiz') }}
                    </a>
                </div>
            </x-card>
        @endif
    </div>
</div>
