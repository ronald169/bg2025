<?php
// app/Livewire/Teacher/QuizBuilder.php

namespace App\Livewire\Teacher;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Quiz Builder - Teacher')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use WithPagination, Toast;

    public Course $course;
    public $quizzes = [];
    public $showQuizModal = false;
    public $editingQuiz = null;
    public $showQuestionModal = false;
    public $editingQuestion = null;

    // Quiz form
    public $quizTitle = '';
    public $quizDescription = '';
    public $quizTimeLimit = null;
    public $quizPassingScore = 70;
    public $quizMaxAttempts = 1;
    public $quizIsPublished = false;

    // Question form
    public $questionText = '';
    public $questionType = 'multiple_choice';
    public $questionOptions = ['', '', '', ''];
    public $questionCorrectAnswer = '';
    public $questionPoints = 1;
    public $questionExplanation = '';

    public $selectedQuizId = null;

    public function mount(Course $course): void
    {
        if ($course->teacher_id !== auth()->id()) {
            abort(403);
        }
        $this->course = $course;
        $this->loadQuizzes();
    }

    public function loadQuizzes(): void
    {
        $this->quizzes = Quiz::whereHas('lesson.course', function($q) {
                $q->where('id', $this->course->id);
            })
            ->orWhereHas('lesson', function($q) {
                $q->where('course_id', $this->course->id);
            })
            ->with(['questions' => function($q) {
                $q->orderBy('order');
            }, 'lesson'])
            ->get()
            ->map(function ($quiz) {
                $quiz->total_questions = $quiz->questions->count();
                $quiz->total_points = $quiz->questions->sum('points');
                return $quiz;
            });
    }

    public function openQuizModal($quizId = null): void
    {
        if ($quizId) {
            $quiz = Quiz::findOrFail($quizId);
            $this->editingQuiz = $quiz;
            $this->quizTitle = $quiz->title;
            $this->quizDescription = $quiz->description;
            $this->quizTimeLimit = $quiz->time_limit;
            $this->quizPassingScore = $quiz->passing_score;
            $this->quizMaxAttempts = $quiz->max_attempts;
            $this->quizIsPublished = $quiz->is_published;
        } else {
            $this->resetQuizForm();
            $this->editingQuiz = null;
        }
        $this->showQuizModal = true;
    }

    public function saveQuiz(): void
    {
        $this->validate([
            'quizTitle' => 'required|string|max:255',
            'quizTimeLimit' => 'nullable|integer|min:0',
            'quizPassingScore' => 'required|integer|min:0|max:100',
            'quizMaxAttempts' => 'required|integer|min:1',
        ], [
            'quizTitle.required' => __('Please enter a quiz title.'),
            'quizPassingScore.required' => __('Please enter the passing score.'),
            'quizMaxAttempts.required' => __('Please enter the maximum number of attempts.'),
        ]);

        $data = [
            'title' => $this->quizTitle,
            'description' => $this->quizDescription,
            'time_limit' => $this->quizTimeLimit,
            'passing_score' => $this->quizPassingScore,
            'max_attempts' => $this->quizMaxAttempts,
            'is_published' => $this->quizIsPublished,
        ];

        if ($this->editingQuiz) {
            $this->editingQuiz->update($data);
            $this->success(__('Quiz updated successfully! 🎉'));
        } else {
            Quiz::create(array_merge($data, [
                'lesson_id' => null,
                'order' => Quiz::count() + 1,
            ]));
            $this->success(__('Quiz created successfully! 🎉'));
        }

        $this->showQuizModal = false;
        $this->resetQuizForm();
        $this->loadQuizzes();
    }

    public function deleteQuiz($quizId): void
    {
        $quiz = Quiz::findOrFail($quizId);
        $quiz->delete();
        $this->success(__('Quiz deleted! 🗑️'));
        $this->loadQuizzes();
    }

    public function openQuestionModal($quizId, $questionId = null): void
    {
        $this->selectedQuizId = $quizId;

        if ($questionId) {
            $question = QuizQuestion::findOrFail($questionId);
            $this->editingQuestion = $question;
            $this->questionText = $question->question;
            $this->questionType = $question->type;
            $this->questionOptions = $question->options && is_array($question->options) ? $question->options : ['', '', '', ''];
            
            $correctAnswer = $question->correct_answer;
            if (is_array($correctAnswer)) {
                $this->questionCorrectAnswer = $correctAnswer[0] ?? '';
            } else {
                $this->questionCorrectAnswer = $correctAnswer ?? '';
            }
            
            $this->questionPoints = $question->points;
            $this->questionExplanation = $question->explanation ?? '';
        } else {
            $this->resetQuestionForm();
            $this->editingQuestion = null;
        }
        $this->showQuestionModal = true;
    }

    public function saveQuestion(): void
    {
        $this->validate([
            'questionText' => 'required|string|min:3',
            'questionType' => 'required|in:multiple_choice,true_false,short_answer',
            'questionPoints' => 'required|integer|min:1',
        ], [
            'questionText.required' => __('Please enter a question.'),
            'questionText.min' => __('The question must be at least 3 characters long.'),
            'questionPoints.required' => __('Please enter the points.'),
        ]);

        if ($this->questionType === 'multiple_choice') {
            $this->validate([
                'questionCorrectAnswer' => 'required|string',
            ], [
                'questionCorrectAnswer.required' => __('Please select the correct answer.'),
            ]);
            
            $filledOptions = array_filter($this->questionOptions);
            if (count($filledOptions) < 2) {
                $this->error(__('Please add at least 2 answer options.'));
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
                'questionCorrectAnswer.required' => __('Please enter the correct answer.'),
            ]);
        }

        $formattedCorrectAnswer = $this->getFormattedCorrectAnswer();

        $data = [
            'quiz_id' => $this->selectedQuizId,
            'question' => $this->questionText,
            'type' => $this->questionType,
            'options' => $this->questionType === 'multiple_choice' ? $this->questionOptions : null,
            'correct_answer' => $formattedCorrectAnswer,
            'points' => $this->questionPoints,
            'explanation' => $this->questionExplanation,
        ];

        if ($this->editingQuestion) {
            $this->editingQuestion->update($data);
            $this->success(__('Question updated! ✅'));
        } else {
            $question = QuizQuestion::create($data);
            // Set order to the end
            $question->update(['order' => QuizQuestion::where('quiz_id', $this->selectedQuizId)->count()]);
            $this->success(__('Question added! ✅'));
        }

        $this->showQuestionModal = false;
        $this->resetQuestionForm();
        $this->loadQuizzes();
    }

    private function getFormattedCorrectAnswer()
    {
        if ($this->questionType === 'multiple_choice') {
            return [(string)$this->questionCorrectAnswer];
        } elseif ($this->questionType === 'true_false') {
            return [$this->questionCorrectAnswer === 'true' ? 'true' : 'false'];
        } else {
            return [$this->questionCorrectAnswer];
        }
    }

    public function deleteQuestion($questionId): void
    {
        $question = QuizQuestion::findOrFail($questionId);
        $question->delete();
        
        // Reorder remaining questions
        $questions = QuizQuestion::where('quiz_id', $question->quiz_id)->orderBy('order')->get();
        foreach ($questions as $index => $q) {
            $q->update(['order' => $index + 1]);
        }
        
        $this->success(__('Question deleted! 🗑️'));
        $this->loadQuizzes();
    }

    public function moveQuestionUp($questionId): void
    {
        $question = QuizQuestion::findOrFail($questionId);
        $prevQuestion = QuizQuestion::where('quiz_id', $question->quiz_id)
            ->where('order', '<', $question->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($prevQuestion) {
            $prevOrder = $prevQuestion->order;
            $prevQuestion->update(['order' => $question->order]);
            $question->update(['order' => $prevOrder]);
            $this->success(__('Question order updated.'));
            $this->loadQuizzes();
        }
    }

    public function moveQuestionDown($questionId): void
    {
        $question = QuizQuestion::findOrFail($questionId);
        $nextQuestion = QuizQuestion::where('quiz_id', $question->quiz_id)
            ->where('order', '>', $question->order)
            ->orderBy('order', 'asc')
            ->first();

        if ($nextQuestion) {
            $nextOrder = $nextQuestion->order;
            $nextQuestion->update(['order' => $question->order]);
            $question->update(['order' => $nextOrder]);
            $this->success(__('Question order updated.'));
            $this->loadQuizzes();
        }
    }

    public function duplicateQuiz($quizId): void
    {
        $originalQuiz = Quiz::with('questions')->findOrFail($quizId);

        $newQuiz = $originalQuiz->replicate();
        $newQuiz->title = $originalQuiz->title . ' (Copy)';
        $newQuiz->is_published = false;
        $newQuiz->save();

        foreach ($originalQuiz->questions as $question) {
            $newQuestion = $question->replicate();
            $newQuestion->quiz_id = $newQuiz->id;
            $newQuestion->save();
        }

        $this->success(__('Quiz duplicated! 📋'));
        $this->loadQuizzes();
    }

    public function togglePublish($quizId): void
    {
        $quiz = Quiz::findOrFail($quizId);
        $quiz->update(['is_published' => !$quiz->is_published]);
        $this->success($quiz->is_published ? __('Quiz published! 🚀') : __('Quiz saved as draft.'));
        $this->loadQuizzes();
    }

    private function resetQuizForm(): void
    {
        $this->quizTitle = '';
        $this->quizDescription = '';
        $this->quizTimeLimit = null;
        $this->quizPassingScore = 70;
        $this->quizMaxAttempts = 1;
        $this->quizIsPublished = false;
    }

    private function resetQuestionForm(): void
    {
        $this->questionText = '';
        $this->questionType = 'multiple_choice';
        $this->questionOptions = ['', '', '', ''];
        $this->questionCorrectAnswer = '';
        $this->questionPoints = 1;
        $this->questionExplanation = '';
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

    public function getQuestionTypesProperty()
    {
        return [
            ['id' => 'multiple_choice', 'name' => __('Multiple Choice')],
            ['id' => 'true_false', 'name' => __('True / False')],
            ['id' => 'short_answer', 'name' => __('Short Answer')],
        ];
    }

    public function getOptionLetter($index): string
    {
        return chr(65 + (int)$index);
    }
}
?>


<!-- resources/views/livewire/teacher/quiz-builder.blade.php -->

<div class="py-4 md:py-6">
    <div class="max-w-6xl px-3 mx-auto md:px-4">
        
        <!-- Header -->
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">📝 {{ __('Quiz Builder') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('Create and manage quizzes for :course', ['course' => $course->title]) }}</p>
            </div>
            <div>
                <x-button wire:click="openQuizModal" icon="o-plus" class="btn-primary">
                    {{ __('Create Quiz') }}
                </x-button>
            </div>
        </div>

        <!-- Quizzes List -->
        @if(count($quizzes) > 0)
            <div class="space-y-6">
                @foreach($quizzes as $quizItem)
                <div class="overflow-hidden bg-white shadow-sm rounded-xl">
                    <div class="p-4 border-b bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-[#FF6B35]/10 flex items-center justify-center">
                                    <x-icon name="o-document-text" class="w-5 h-5 text-[#FF6B35]" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $quizItem->title }}</h3>
                                    <p class="text-sm text-gray-500">{{ Str::limit($quizItem->description ?? __('No description'), 50) }}</p>
                                    <div class="flex flex-wrap items-center gap-3 mt-1 text-xs text-gray-400">
                                        <span>{{ $quizItem->total_questions }} {{ __('questions') }}</span>
                                        <span>{{ $quizItem->total_points }} {{ __('points') }}</span>
                                        <span>⏱️ {{ $quizItem->time_limit ?? '∞' }} min</span>
                                        <span>🎯 {{ $quizItem->passing_score }}%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 text-xs rounded-full {{ $quizItem->is_published ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ $quizItem->is_published ? __('Published') : __('Draft') }}
                                </span>
                                <button wire:click="togglePublish({{ $quizItem->id }})" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg transition" title="{{ $quizItem->is_published ? __('Unpublish') : __('Publish') }}">
                                    <x-icon :name="$quizItem->is_published ? 'o-eye-slash' : 'o-eye'" class="w-4 h-4" />
                                </button>
                                <button wire:click="duplicateQuiz({{ $quizItem->id }})" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg transition" title="{{ __('Duplicate') }}">
                                    <x-icon name="o-document-duplicate" class="w-4 h-4" />
                                </button>
                                <button wire:click="openQuizModal({{ $quizItem->id }})" class="p-1.5 text-gray-400 hover:text-orange-600 rounded-lg transition" title="{{ __('Edit') }}">
                                    <x-icon name="o-pencil" class="w-4 h-4" />
                                </button>
                                <button wire:click="deleteQuiz({{ $quizItem->id }})" wire:confirm="{{ __('Delete this quiz?') }}" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg transition" title="{{ __('Delete') }}">
                                    <x-icon name="o-trash" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Questions List -->
                    <div class="divide-y divide-gray-100">
                        @php $sortedQuestions = $quizItem->questions->sortBy('order'); @endphp
                        
                        @forelse($sortedQuestions as $index => $question)
                        <div class="p-4 transition hover:bg-gray-50">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <!-- Order indicator with move buttons -->
                                        <div class="flex items-center gap-1">
                                            <button wire:click="moveQuestionUp({{ $question->id }})" 
                                                    class="p-1 text-gray-400 hover:text-gray-600 transition {{ $loop->first ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                    {{ $loop->first ? 'disabled' : '' }}
                                                    title="{{ __('Move up') }}">
                                                <x-icon name="o-chevron-up" class="w-4 h-4" />
                                            </button>
                                            <span class="w-8 text-sm font-medium text-center text-gray-500">{{ $question->order ?? $index + 1 }}</span>
                                            <button wire:click="moveQuestionDown({{ $question->id }})" 
                                                    class="p-1 text-gray-400 hover:text-gray-600 transition {{ $loop->last ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                    {{ $loop->last ? 'disabled' : '' }}
                                                    title="{{ __('Move down') }}">
                                                <x-icon name="o-chevron-down" class="w-4 h-4" />
                                            </button>
                                        </div>
                                        
                                        <span class="text-xs font-medium text-gray-500">Q{{ $question->order ?? $index + 1 }}</span>
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">
                                            {{ $question->type === 'multiple_choice' ? __('Multiple Choice') : ($question->type === 'true_false' ? __('True / False') : __('Short Answer')) }}
                                        </span>
                                        <span class="text-xs text-gray-500">{{ $question->points }} {{ __('pts') }}</span>
                                    </div>
                                    <p class="mt-1 text-gray-900">{{ $question->question }}</p>
                                    @if($question->type === 'multiple_choice' && $question->options)
                                        <div class="mt-2 space-y-1">
                                            @foreach($question->options as $optionIndex => $option)
                                                @if($option)
                                                <div class="flex items-center gap-2 text-sm">
                                                    <span class="flex items-center justify-center w-5 h-5 text-xs font-bold bg-gray-100 rounded-full">
                                                        {{ chr(65 + $optionIndex) }}
                                                    </span>
                                                    <span class="text-gray-600">{{ $option }}</span>
                                                    @if(is_array($question->correct_answer) && in_array($option, $question->correct_answer))
                                                        <span class="text-xs text-green-600">✓ {{ __('Correct') }}</span>
                                                    @elseif(!is_array($question->correct_answer) && $question->correct_answer == $optionIndex)
                                                        <span class="text-xs text-green-600">✓ {{ __('Correct') }}</span>
                                                    @endif
                                                </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="flex space-x-1">
                                    <x-button wire:click="openQuestionModal({{ $quizItem->id }}, {{ $question->id }})"
                                              icon="o-pencil"
                                              size="xs"
                                              class="btn-ghost" />
                                    <x-button wire:click="deleteQuestion({{ $question->id }})"
                                              icon="o-trash"
                                              size="xs"
                                              class="text-red-500 btn-ghost"
                                              wire:confirm="{{ __('Delete this question?') }}" />
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="p-6 text-center">
                            <x-icon name="o-document-text" class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                            <p class="text-gray-500">{{ __('No questions yet') }}</p>
                            <x-button wire:click="openQuestionModal({{ $quizItem->id }})" class="mt-3 btn-primary">
                                {{ __('Add First Question') }}
                            </x-button>
                        </div>
                        @endforelse

                        @if($quizItem->questions->count() > 0)
                        <div class="p-4 text-center bg-gray-50">
                            <x-button wire:click="openQuestionModal({{ $quizItem->id }})" icon="o-plus" class="btn-ghost btn-sm">
                                {{ __('Add Question') }}
                            </x-button>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="p-12 text-center bg-white shadow-sm rounded-xl">
                <x-icon name="o-document-text" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('No quizzes yet') }}</h3>
                <p class="mb-4 text-gray-500">{{ __('Create your first quiz to test your students') }}</p>
                <x-button wire:click="openQuizModal" class="btn-primary">
                    {{ __('Create Quiz') }}
                </x-button>
            </div>
        @endif

        <!-- Quiz Modal -->
        @if($showQuizModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="showQuizModal = false">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="sticky top-0 p-4 bg-white border-b">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ $editingQuiz ? __('Edit Quiz') : __('Create New Quiz') }}
                        </h3>
                        <button wire:click="$set('showQuizModal', false)" class="text-gray-400 hover:text-gray-600">
                            <x-icon name="o-x-mark" class="w-6 h-6" />
                        </button>
                    </div>
                </div>

                <div class="p-5 space-y-4">
                    <x-input
                        wire:model="quizTitle"
                        label="{{ __('Quiz Title') }} *"
                        placeholder="{{ __('e.g., Grammar Quiz A1') }}"
                        icon="o-pencil"
                        required />

                    <x-textarea
                        wire:model="quizDescription"
                        label="{{ __('Description') }}"
                        placeholder="{{ __('What will be tested in this quiz?') }}"
                        rows="2"
                        icon="o-document-text" />

                    <div class="grid gap-4 md:grid-cols-3">
                        <x-input
                            wire:model="quizTimeLimit"
                            type="number"
                            min="0"
                            label="{{ __('Time Limit (minutes)') }}"
                            placeholder="{{ __('0 = no limit') }}"
                            icon="o-clock" />

                        <x-input
                            wire:model="quizPassingScore"
                            type="number"
                            min="0"
                            max="100"
                            label="{{ __('Passing Score (%)') }} *"
                            icon="o-chart-bar"
                            required />

                        <x-input
                            wire:model="quizMaxAttempts"
                            type="number"
                            min="1"
                            label="{{ __('Max Attempts') }}"
                            icon="o-arrow-path"
                            required />
                    </div>

                    <x-toggle
                        wire:model="quizIsPublished"
                        label="{{ __('Publish Quiz') }}"
                        hint="{{ __('Published quizzes are visible to students') }}" />
                </div>

                <div class="flex justify-end gap-3 p-5 border-t bg-gray-50">
                    <x-button wire:click="$set('showQuizModal', false)" class="btn-ghost">
                        {{ __('Cancel') }}
                    </x-button>
                    <x-button wire:click="saveQuiz" class="btn-primary" spinner="saveQuiz">
                        {{ $editingQuiz ? __('Save Changes') : __('Create Quiz') }}
                    </x-button>
                </div>
            </div>
        </div>
        @endif

        <!-- Question Modal -->
        @if($showQuestionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="showQuestionModal = false"
             x-data="{ 
                 questionType: @entangle('questionType'),
                 init() {
                     this.$watch('questionType', () => {
                         @this.refreshQuestionForm();
                     });
                 }
             }"
             x-init="init()">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="sticky top-0 p-4 bg-white border-b">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ $editingQuestion ? __('Edit Question') : __('New Question') }}
                        </h3>
                        <button wire:click="$set('showQuestionModal', false)" class="text-gray-400 hover:text-gray-600">
                            <x-icon name="o-x-mark" class="w-6 h-6" />
                        </button>
                    </div>
                </div>

                <div class="p-5 space-y-4">
                    <textarea wire:model="questionText"
                              rows="3"
                              class="w-full px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]"
                              placeholder="{{ __('Enter your question...') }}"></textarea>
                    @error('questionText')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="grid gap-4 md:grid-cols-2">
                        <select wire:model="questionType" class="px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                            <option value="multiple_choice">{{ __('Multiple Choice') }}</option>
                            <option value="true_false">{{ __('True / False') }}</option>
                            <option value="short_answer">{{ __('Short Answer') }}</option>
                        </select>

                        <input type="number" wire:model="questionPoints" min="1"
                               class="px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]"
                               placeholder="{{ __('Points') }}">
                        @error('questionPoints')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Multiple Choice Options -->
                    @if($questionType === 'multiple_choice')
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">{{ __('Options') }} *</label>
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
                                + {{ __('Add Option') }}
                            </button>
                        </div>

                        <!-- Correct Answer for Multiple Choice -->
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Correct Answer') }} *</label>
                            <select wire:model="questionCorrectAnswer" class="w-full px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                                <option value="">{{ __('Select the correct answer') }}</option>
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
                            <label class="block mb-2 text-sm font-medium text-gray-700">{{ __('Correct Answer') }} *</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="questionCorrectAnswer" value="true" class="text-[#FF6B35]">
                                    <span>{{ __('True') }}</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="questionCorrectAnswer" value="false" class="text-[#FF6B35]">
                                    <span>{{ __('False') }}</span>
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
                            <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Correct Answer') }} *</label>
                            <input type="text" wire:model="questionCorrectAnswer"
                                   placeholder="{{ __('e.g., Hallo') }}"
                                   class="w-full px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                            @error('questionCorrectAnswer')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <!-- Explanation -->
                    <textarea wire:model="questionExplanation"
                              rows="2"
                              class="w-full px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]"
                              placeholder="{{ __('Explanation (optional)') }}"></textarea>
                </div>

                <div class="flex justify-end gap-3 p-5 border-t bg-gray-50">
                    <x-button wire:click="$set('showQuestionModal', false)" class="btn-ghost">
                        {{ __('Cancel') }}
                    </x-button>
                    <x-button wire:click="saveQuestion" class="btn-primary" spinner="saveQuestion">
                        {{ $editingQuestion ? __('Save Changes') : __('Add Question') }}
                    </x-button>
                </div>
            </div>
        </div>
        @endif

        <!-- Note MVP -->
        <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">{{ __('MVP Version') }}</p>
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('Upcoming features: question bank, random questions, timed quizzes, and detailed analytics.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>