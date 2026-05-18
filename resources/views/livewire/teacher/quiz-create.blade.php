<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use Illuminate\Support\Str;
use Mary\Traits\Toast;

new
#[Title('Quiz erstellen - Lehrer')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use Toast;

    public Course $course;
    public ?Lesson $lesson = null;

    // Quiz informations
    public $title = '';
    public $description = '';
    public $time_limit = 5;
    public $passing_score = 60;
    public $max_attempts = 3;
    public $is_published = true;

    // Questions
    public $questions = [];
    public $currentQuestionIndex = 0;
    public $showQuestionModal = false;
    public $editingQuestionId = null;

    // Question form
    public $questionText = '';
    public $questionType = 'multiple_choice';
    public $questionPoints = 1;
    public $questionOptions = ['', '', '', ''];
    public $questionCorrectAnswer = '';
    public $questionExplanation = '';

    public function mount(Course $course, $lesson = null): void
    {
        if ($course->teacher_id !== auth()->id()) {
            abort(403);
        }

        $this->course = $course;

        if ($lesson) {
            $this->lesson = Lesson::where('course_id', $course->id)->find($lesson->id);
            if (!$this->lesson) {
                abort(404);
            }
        }

        $this->initQuestions();
    }

    public function initQuestions(): void
    {
        $this->questions = [];
    }

    public function addQuestion(): void
    {
        $this->questions[] = [
            'id' => 'temp_' . uniqid(),
            'question' => '',
            'type' => 'multiple_choice',
            'points' => 1,
            'options' => ['', '', '', ''],
            'correct_answer' => [],
            'explanation' => '',
        ];
    }

    public function editQuestion($index): void
    {
        $question = $this->questions[$index];
        $this->currentQuestionIndex = $index;
        $this->editingQuestionId = $question['id'];
        $this->questionText = $question['question'];
        $this->questionType = $question['type'];
        $this->questionPoints = $question['points'];
        $this->questionOptions = $question['options'] && is_array($question['options']) ? $question['options'] : ['', '', '', ''];

        // Gérer correct_answer qui est un tableau
        $correctAnswer = $question['correct_answer'];
        if (is_array($correctAnswer)) {
            $this->questionCorrectAnswer = $correctAnswer[0] ?? '';
        } else {
            $this->questionCorrectAnswer = $correctAnswer ?? '';
        }

        $this->questionExplanation = $question['explanation'] ?? '';
        $this->showQuestionModal = true;
    }

    #[On('refreshQuestionForm')]
    public function refreshQuestionForm(): void
    {
        if ($this->questionType === 'multiple_choice') {
            if (empty($this->questionOptions) || count($this->questionOptions) < 2) {
                $this->questionOptions = ['', '', '', ''];
            }
            if (empty($this->questionCorrectAnswer)) {
                $this->questionCorrectAnswer = '';
            }
        } elseif ($this->questionType === 'true_false') {
            $this->questionOptions = [];
            if (empty($this->questionCorrectAnswer)) {
                $this->questionCorrectAnswer = '';
            }
        } elseif ($this->questionType === 'short_answer') {
            $this->questionOptions = [];
            if (empty($this->questionCorrectAnswer)) {
                $this->questionCorrectAnswer = '';
            }
        }
    }

    public function updatedQuestionType($value): void
    {
        if ($value === 'multiple_choice') {
            $this->questionOptions = ['', '', '', ''];
            $this->questionCorrectAnswer = '';
        } elseif ($value === 'true_false') {
            $this->questionOptions = [];
            $this->questionCorrectAnswer = '';
        } elseif ($value === 'short_answer') {
            $this->questionOptions = [];
            $this->questionCorrectAnswer = '';
        }
    }

    public function saveQuestion(): void
    {
        $this->validate([
            'questionText' => 'required|string|min:3',
            'questionPoints' => 'required|integer|min:1|max:100',
        ], [
            'questionText.required' => 'Bitte gib eine Frage ein.',
            'questionText.min' => 'Die Frage muss mindestens 3 Zeichen lang sein.',
            'questionPoints.required' => 'Bitte gib die Punktezahl ein.',
        ]);

        if ($this->questionType === 'multiple_choice') {
            $this->validate([
                'questionOptions' => 'required|array|min:2',
                'questionCorrectAnswer' => 'required|string',
            ], [
                'questionOptions.required' => 'Füge mindestens 2 Antwortmöglichkeiten hinzu.',
                'questionCorrectAnswer.required' => 'Wähle die richtige Antwort aus.',
            ]);

            $filledOptions = array_filter($this->questionOptions);
            if (count($filledOptions) < 2) {
                $this->error('Füge mindestens 2 Antwortmöglichkeiten hinzu.');
                return;
            }
        } elseif ($this->questionType === 'true_false') {
            $this->validate([
                'questionCorrectAnswer' => 'required|in:true,false',
            ]);
        } elseif ($this->questionType === 'short_answer') {
            $this->validate([
                'questionCorrectAnswer' => 'required|string|min:1',
            ], [
                'questionCorrectAnswer.required' => 'Bitte gib die richtige Antwort ein.',
            ]);
        }

        // Formater correct_answer comme un tableau (pour correspondre au cast du modèle)
        $formattedCorrectAnswer = $this->getFormattedCorrectAnswer();

        $questionData = [
            'id' => $this->editingQuestionId ?? 'temp_' . uniqid(),
            'question' => $this->questionText,
            'type' => $this->questionType,
            'points' => $this->questionPoints,
            'options' => $this->questionType === 'multiple_choice' ? $this->questionOptions : null,
            'correct_answer' => $formattedCorrectAnswer,
            'explanation' => $this->questionExplanation,
        ];

        if ($this->editingQuestionId && !str_starts_with((string)$this->editingQuestionId, 'temp_')) {
            // Remplacer la question existante
            foreach ($this->questions as $index => $q) {
                if ($q['id'] == $this->editingQuestionId) {
                    $this->questions[$index] = $questionData;
                    break;
                }
            }
        } else {
            $this->questions[] = $questionData;
        }

        $this->closeQuestionModal();
        $this->success('Frage gespeichert! ✅');
    }

    private function getFormattedCorrectAnswer()
    {
        if ($this->questionType === 'multiple_choice') {
            // Pour multiple choice, stocker l'index comme un tableau
            return [(string)$this->questionCorrectAnswer];
        } elseif ($this->questionType === 'true_false') {
            // Pour true/false, stocker un tableau avec 'true' ou 'false'
            return [$this->questionCorrectAnswer === 'true' ? 'true' : 'false'];
        } else {
            // Pour short_answer, stocker un tableau avec la réponse
            return [$this->questionCorrectAnswer];
        }
    }

    public function removeQuestion($index): void
    {
        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);
        $this->success('Frage entfernt');
    }

    public function closeQuestionModal(): void
    {
        $this->showQuestionModal = false;
        $this->resetQuestionForm();
    }

    public function resetQuestionForm(): void
    {
        $this->editingQuestionId = null;
        $this->currentQuestionIndex = 0;
        $this->questionText = '';
        $this->questionType = 'multiple_choice';
        $this->questionPoints = 1;
        $this->questionOptions = ['', '', '', ''];
        $this->questionCorrectAnswer = '';
        $this->questionExplanation = '';
    }

    public function saveQuiz(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'time_limit' => 'nullable|integer|min:1',
            'passing_score' => 'required|integer|min:0|max:100',
            'max_attempts' => 'required|integer|min:1',
            'questions' => 'required|array|min:1',
        ], [
            'title.required' => 'Bitte gib einen Titel ein.',
            'questions.required' => 'Füge mindestens eine Frage hinzu.',
            'questions.min' => 'Füge mindestens eine Frage hinzu.',
        ]);

        // Créer le quiz
        $quiz = Quiz::create([
            'lesson_id' => $this->lesson?->id,
            'title' => $this->title,
            'description' => $this->description,
            'time_limit' => $this->time_limit,
            'passing_score' => $this->passing_score,
            'max_attempts' => $this->max_attempts,
            'is_published' => $this->is_published,
            'order' => 0,
        ]);

        // Créer les questions
        foreach ($this->questions as $order => $questionData) {
            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => $questionData['question'],
                'type' => $questionData['type'],
                'options' => $questionData['type'] === 'multiple_choice' ? $questionData['options'] : null,
                'correct_answer' => $questionData['correct_answer'],
                'points' => $questionData['points'],
                'explanation' => $questionData['explanation'] ?? null,
                'order' => $order,
            ]);
        }

        $this->success('Quiz erfolgreich erstellt! 🎉');
        $this->redirectRoute('teacher.quizzes.preview', ['course' => $this->course, 'quiz' => $quiz], navigate: true);
    }

    public function previewAndSave(): void
    {
        if (empty($this->questions)) {
            $this->error('Füge mindestens eine Frage hinzu, bevor du das Quiz testest.');
            return;
        }
        $this->saveQuiz();
    }

    public function addOption(): void
    {
        $this->questionOptions[] = '';
    }

    public function removeOption($index): void
    {
        unset($this->questionOptions[$index]);
        $this->questionOptions = array_values($this->questionOptions);
    }

    public function getTotalPoints(): int
    {
        $total = 0;
        foreach ($this->questions as $question) {
            $total += $question['points'];
        }
        return $total;
    }
}
?>

<div class="py-4 md:py-6">
    <div class="max-w-5xl px-3 mx-auto md:px-4">

        <!-- Navigation -->
        <div class="mb-5">
            @if($lesson)
                <a href="{{ route('teacher.lessons.index', $course) }}" class="inline-flex items-center gap-1 text-sm text-[#FF6B35] hover:text-[#E55A2A] transition">
                    <x-icon name="o-arrow-left" class="w-4 h-4" />
                    {{ __('Zurück zu den Lektionen') }}
                </a>
            @else
                <a href="{{ route('teacher.quizzes.index', $course) }}" class="inline-flex items-center gap-1 text-sm text-[#FF6B35] hover:text-[#E55A2A] transition">
                    <x-icon name="o-arrow-left" class="w-4 h-4" />
                    {{ __('Zurück zu den Quizzen') }}
                </a>
            @endif
        </div>

        <!-- Header -->
        <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">📝 {{ __('Quiz erstellen') }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $course->title }}
                    @if($lesson)
                        → {{ $lesson->title }}
                    @endif
                </p>
            </div>
            <div class="flex gap-2">
                <x-button
                    wire:click="addQuestion"
                    icon="o-plus"
                    class="btn-primary btn-sm">
                    {{ __('Frage hinzufügen') }}
                </x-button>
            </div>
        </div>

        <form wire:submit="saveQuiz" class="space-y-5">
            <!-- Quiz Information -->
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-information-circle" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Quiz-Informationen') }}</h2>
                </div>

                <div class="space-y-4">
                    <x-input
                        wire:model="title"
                        label="{{ __('Quiz-Titel') }} *"
                        placeholder="{{ __('z.B. Grammatik Quiz A1') }}"
                        icon="o-pencil"
                        required />

                    <x-textarea
                        wire:model="description"
                        label="{{ __('Beschreibung') }}"
                        placeholder="{{ __('Was wird in diesem Quiz getestet?') }}"
                        rows="2"
                        icon="o-document-text" />

                    <div class="grid gap-4 md:grid-cols-3">
                        <x-input
                            wire:model="time_limit"
                            type="number"
                            min="1"
                            label="{{ __('Zeitlimit (Minuten)') }}"
                            placeholder="{{ __('Kein Limit') }}"
                            icon="o-clock" />

                        <x-input
                            wire:model="passing_score"
                            type="number"
                            min="0"
                            max="100"
                            label="{{ __('Bestehensgrenze (%)') }} *"
                            icon="o-chart-bar"
                            required />

                        <x-input
                            wire:model="max_attempts"
                            type="number"
                            min="1"
                            label="{{ __('Max. Versuche') }}"
                            icon="o-arrow-path"
                            required />
                    </div>

                    <x-toggle
                        wire:model="is_published"
                        label="{{ __('Quiz veröffentlichen') }}"
                        hint="{{ __('Veröffentlichte Quizze sind für Studenten sichtbar') }}" />
                </div>
            </x-card>

            <!-- Questions List -->
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-list-bullet" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Fragen') }} ({{ count($questions) }})</h2>
                    <span class="text-sm text-gray-500">Total: {{ $this->getTotalPoints() }} Punkte</span>
                </div>

                @if(count($questions) > 0)
                    <div class="space-y-3">
                        @foreach($questions as $index => $question)
                        <div class="p-3 transition border rounded-lg hover:bg-gray-50">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="flex items-center justify-center w-6 h-6 text-xs font-bold bg-gray-200 rounded-full">
                                            {{ $index + 1 }}
                                        </span>
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ Str::limit($question['question'], 60) ?: 'Neue Frage' }}
                                        </span>
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">
                                            {{ $question['points'] }} Pkt
                                        </span>
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">
                                            {{ $question['type'] === 'multiple_choice' ? 'Multiple Choice' : ($question['type'] === 'true_false' ? 'Richtig/Falsch' : 'Kurze Antwort') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <button type="button" wire:click="editQuestion({{ $index }})" class="p-1.5 text-gray-400 hover:text-orange-600 rounded-lg">
                                        <x-icon name="o-pencil" class="w-4 h-4" />
                                    </button>
                                    <button type="button" wire:click="removeQuestion({{ $index }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg">
                                        <x-icon name="o-trash" class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-12 text-center border-2 border-dashed rounded-lg">
                        <x-icon name="o-list-bullet" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                        <p class="text-gray-500">{{ __('Keine Fragen vorhanden') }}</p>
                        <p class="text-sm text-gray-400">{{ __('Klicke auf "Frage hinzufügen" um zu beginnen') }}</p>
                    </div>
                @endif
            </x-card>

            <!-- Actions -->
            <div class="flex flex-col justify-end gap-3 pt-4 sm:flex-row">
                <a href="{{ $lesson ? route('teacher.lessons.index', $course) : route('teacher.quizzes.index', $course) }}"
                   class="px-4 py-2 text-center text-gray-600 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                    {{ __('Abbrechen') }}
                </a>
                <button type="button" wire:click="previewAndSave" class="px-4 py-2 text-white transition bg-purple-600 rounded-lg hover:bg-purple-700">
                    <x-icon name="o-eye" class="inline w-4 h-4 mr-1" />
                    {{ __('Speichern & Vorschau') }}
                </button>
                <button type="submit" class="px-4 py-2 text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                    <x-icon name="o-check" class="inline w-4 h-4 mr-1" />
                    {{ __('Quiz speichern') }}
                </button>
            </div>
        </form>

        <!-- Question Modal -->
        @if($showQuestionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
             wire:click.self="closeQuestionModal"
             x-data="{
                 questionType: @entangle('questionType'),
                 init() {
                     this.$watch('questionType', () => {
                         @this.refreshQuestionForm();
                     });
                 }
             }"
             x-init="init()">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
                 @click.stop>
                <div class="sticky top-0 p-4 bg-white border-b">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ $editingQuestionId ? __('Frage bearbeiten') : __('Neue Frage') }}
                        </h3>
                        <div class="flex gap-2">
                            <button type="button" wire:click="refreshQuestionForm" class="p-1.5 text-gray-400 hover:text-[#FF6B35] rounded-lg transition" title="Aktualisieren">
                                <x-icon name="o-arrow-path" class="w-5 h-5" />
                            </button>
                            <button type="button" wire:click="closeQuestionModal" class="text-gray-400 hover:text-gray-600">
                                <x-icon name="o-x-mark" class="w-6 h-6" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-5 space-y-4">
                    <!-- Champ Question -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            {{ __('Frage') }} <span class="text-red-500">*</span>
                        </label>
                        <textarea wire:model="questionText"
                                  rows="3"
                                  class="w-full px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]"
                                  placeholder="{{ __('z.B. Was bedeutet "Hallo" auf Deutsch?') }}"></textarea>
                        @error('questionText')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Fragentyp') }}</label>
                            <select wire:model="questionType" class="w-full px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">Richtig / Falsch</option>
                                <option value="short_answer">Kurze Antwort</option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Punkte') }} <span class="text-red-500">*</span></label>
                            <input type="number" wire:model="questionPoints" min="1" max="100"
                                   class="w-full px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                            @error('questionPoints')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Multiple Choice Options -->
                    @if($questionType === 'multiple_choice')
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">{{ __('Antwortmöglichkeiten') }} <span class="text-red-500">*</span></label>
                            @foreach($questionOptions as $index => $option)
                                <div class="flex items-center gap-2">
                                    <span class="flex items-center justify-center w-8 h-8 text-sm font-bold bg-gray-100 rounded-full">
                                        {{ chr(65 + $index) }}
                                    </span>
                                    <input type="text" wire:model="questionOptions.{{ $index }}"
                                           placeholder="{{ __('Option') }} {{ chr(65 + $index) }}"
                                           class="flex-1 px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                                    @if($index >= 2)
                                        <button type="button" wire:click="removeOption({{ $index }})" class="text-red-500 hover:text-red-700">
                                            <x-icon name="o-trash" class="w-4 h-4" />
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                            <button type="button" wire:click="addOption" class="text-sm text-[#FF6B35] hover:underline">
                                + {{ __('Option hinzufügen') }}
                            </button>
                            @error('questionOptions')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Correct Answer for Multiple Choice -->
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Richtige Antwort') }} <span class="text-red-500">*</span></label>
                            <select wire:model="questionCorrectAnswer" class="w-full px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                                <option value="">{{ __('Wähle die richtige Antwort') }}</option>
                                @foreach($questionOptions as $index => $option)
                                    @if($option)
                                        <option value="{{ $index }}">{{ chr(65 + $index) }}. {{ $option }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('questionCorrectAnswer')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <!-- Correct Answer for True/False -->
                    @if($questionType === 'true_false')
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">{{ __('Richtige Antwort') }} <span class="text-red-500">*</span></label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="questionCorrectAnswer" value="true" class="text-[#FF6B35]">
                                    <span>{{ __('Richtig') }}</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="questionCorrectAnswer" value="false" class="text-[#FF6B35]">
                                    <span>{{ __('Falsch') }}</span>
                                </label>
                            </div>
                            @error('questionCorrectAnswer')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <!-- Correct Answer for Short Answer -->
                    @if($questionType === 'short_answer')
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Richtige Antwort') }} <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="questionCorrectAnswer"
                                   placeholder="{{ __('z.B. Hallo') }}"
                                   class="w-full px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                            @error('questionCorrectAnswer')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <!-- Explanation -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Erklärung (optional)') }}</label>
                        <textarea wire:model="questionExplanation"
                                  rows="2"
                                  class="w-full px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]"
                                  placeholder="{{ __('Erklärung warum diese Antwort richtig ist') }}"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 p-5 border-t bg-gray-50">
                    <button type="button" wire:click="closeQuestionModal" class="px-4 py-2 text-gray-600 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                        {{ __('Abbrechen') }}
                    </button>
                    <button type="button" wire:click="saveQuestion" class="px-4 py-2 text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                        {{ __('Frage speichern') }}
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
