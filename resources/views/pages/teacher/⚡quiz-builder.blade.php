<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\Lesson;
use App\Models\QuizQuestion;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Quiz Builder - Teacher')]
#[Layout('layouts.app')]
class extends Component {
    use WithPagination, Toast;

    public Course $course;

    // Quiz form
    public bool $showQuizModal = false;
    public $editingQuiz = null;
    public string $quizTitle = 'Quiz : ';
    public string $quizDescription = 'Évaluation finale : ';
    public ?int $quizTimeLimit = 4;
    public int $quizPassingScore = 70;
    public int $quizMaxAttempts = 3;
    public bool $quizIsPublished = true;

    // Question form
    public bool $showQuestionModal = false;
    public ?int $selectedQuizId = null;
    public $editingQuestion = null;
    public string $questionText = '';
    public string $questionType = 'multiple_choice';
    public array $questionOptions = ['', '', '', ''];
    public string $questionCorrectAnswer = '';
    public int $questionPoints = 1;
    public string $questionExplanation = '';

    // Getters
    public function getQuizzesProperty()
    {
        return Quiz::whereHas('lesson.course', fn($q) => $q->where('id', $this->course->id))
            ->orWhereHas('lesson', fn($q) => $q->where('course_id', $this->course->id))
            ->with(['questions' => fn($q) => $q->orderBy('order'), 'lesson'])
            ->get()
            ->map(function ($quiz) {
                $quiz->total_questions = $quiz->questions->count();
                $quiz->total_points = $quiz->questions->sum('points');
                return $quiz;
            });
    }

    public function getQuestionTypesProperty()
    {
        return [
            ['id' => 'multiple_choice', 'name' => __('Multiple Choice')],
            ['id' => 'true_false', 'name' => __('True / False')],
            ['id' => 'short_answer', 'name' => __('Short Answer')],
        ];
    }

    public function mount(Course $course): void
    {
        if ($course->teacher_id != auth()->id()) {
            abort(403);
        }
        $this->course = $course;
    }

    public function openQuizModal($quizId = null): void
    {
        if ($quizId) {
            $quiz = Quiz::findOrFail($quizId);
            $this->editingQuiz = $quiz;
            $this->quizTitle = $quiz->title;
            $this->quizDescription = $quiz->description ?? '';
            $this->quizTimeLimit = $quiz->time_limit;
            $this->quizPassingScore = $quiz->passing_score;
            $this->quizMaxAttempts = $quiz->max_attempts;
            $this->quizIsPublished = $quiz->is_published;
            $this->selectedLessonId = $quiz->lesson_id; // Ajout
        } else {
            $this->resetQuizForm();
            $this->editingQuiz = null;
        }
        $this->showQuizModal = true;
    }

    public function saveQuiz(): void
    {
        $this->validate([
            'quizTitle'        => 'required|string|max:255',
            'quizTimeLimit'    => 'nullable|integer|min:0',
            'quizPassingScore' => 'required|integer|min:0|max:100',
            'quizMaxAttempts'  => 'required|integer|min:1',
            'selectedLessonId' => 'required|exists:lessons,id', // Ajout
        ], [
            'quizTitle.required'        => __('Please enter a quiz title.'),
            'quizPassingScore.required' => __('Please enter the passing score.'),
            'quizMaxAttempts.required'  => __('Please enter the maximum number of attempts.'),
            'selectedLessonId.required' => __('Please select a lesson for this quiz.'),
        ]);

        $data = [
            'title'         => $this->quizTitle,
            'description'   => $this->quizDescription,
            'time_limit'    => $this->quizTimeLimit,
            'passing_score' => $this->quizPassingScore,
            'max_attempts'  => $this->quizMaxAttempts,
            'is_published'  => $this->quizIsPublished,
            'lesson_id'     => $this->selectedLessonId, // Ajout
        ];

        if ($this->editingQuiz) {
            $this->editingQuiz->update($data);
            $this->success(__('Quiz updated successfully! 🎉'));
        } else {
            Quiz::create(array_merge($data, ['order' => Quiz::count() + 1]));
            $this->success(__('Quiz created successfully! 🎉'));
        }

        $this->showQuizModal = false;
        $this->resetQuizForm();
    }

    public function deleteQuiz($quizId): void
    {
        $quiz = Quiz::findOrFail($quizId);
        $quiz->delete();
        $this->success(__('Quiz deleted! 🗑️'));
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
    }

    public function togglePublish($quizId): void
    {
        $quiz = Quiz::findOrFail($quizId);
        $quiz->update(['is_published' => !$quiz->is_published]);
        $this->success($quiz->is_published ? __('Quiz published! 🚀') : __('Quiz saved as draft.'));
    }

    public function openQuestionModal($quizId, $questionId = null): void
    {
        $this->selectedQuizId = $quizId;
        if ($questionId) {
            $question = QuizQuestion::findOrFail($questionId);
            $this->editingQuestion = $question;
            $this->questionText = $question->question;
            $this->questionType = $question->type;
            $options = $question->options;
            if (is_array($options)) {
                $this->questionOptions = array_pad($options, 4, '');
            } else {
                $this->questionOptions = ['', '', '', ''];
            }
            $correct = $question->correct_answer;
            if (is_array($correct)) {
                $this->questionCorrectAnswer = $correct[0] ?? '';
            } else {
                $this->questionCorrectAnswer = $correct ?? '';
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
            'questionText'   => 'required|string|min:3',
            'questionType'   => 'required|in:multiple_choice,true_false,short_answer',
            'questionPoints' => 'required|integer|min:1',
        ], [
            'questionText.required' => __('Please enter a question.'),
            'questionPoints.required' => __('Please enter the points.'),
        ]);

        if ($this->questionType === 'multiple_choice') {
            $this->validate(['questionCorrectAnswer' => 'required|string'], ['questionCorrectAnswer.required' => __('Please select the correct answer.')]);
            $filledOptions = array_filter($this->questionOptions);
            if (count($filledOptions) < 2) {
                $this->error(__('Please add at least 2 answer options.'));
                return;
            }
        } elseif ($this->questionType === 'true_false') {
            $this->validate(['questionCorrectAnswer' => 'required|in:true,false']);
        } else {
            $this->validate(['questionCorrectAnswer' => 'required|string|min:1'], ['questionCorrectAnswer.required' => __('Please enter the correct answer.')]);
        }

        $data = [
            'quiz_id'        => $this->selectedQuizId,
            'question'       => $this->questionText,
            'type'           => $this->questionType,
            'options'        => $this->questionType === 'multiple_choice' ? $this->questionOptions : null,
            'correct_answer' => $this->getFormattedCorrectAnswer(),
            'points'         => $this->questionPoints,
            'explanation'    => $this->questionExplanation,
        ];

        if ($this->editingQuestion) {
            $this->editingQuestion->update($data);
            $this->success(__('Question updated! ✅'));
        } else {
            $question = QuizQuestion::create($data);
            $question->update(['order' => QuizQuestion::where('quiz_id', $this->selectedQuizId)->count()]);
            $this->success(__('Question added! ✅'));
        }

        $this->showQuestionModal = false;
        $this->resetQuestionForm();
    }

    private function getFormattedCorrectAnswer(): array
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
        $quizId = $question->quiz_id;
        $question->delete();
        // Reorder
        $questions = QuizQuestion::where('quiz_id', $quizId)->orderBy('order')->get();
        foreach ($questions as $index => $q) {
            $q->update(['order' => $index + 1]);
        }
        $this->success(__('Question deleted! 🗑️'));
    }

    public function moveQuestionUp($questionId): void
    {
        $question = QuizQuestion::findOrFail($questionId);
        $prev = QuizQuestion::where('quiz_id', $question->quiz_id)->where('order', '<', $question->order)->orderBy('order', 'desc')->first();
        if ($prev) {
            $prevOrder = $prev->order;
            $prev->update(['order' => $question->order]);
            $question->update(['order' => $prevOrder]);
            $this->success(__('Question order updated.'));
        }
    }

    public function moveQuestionDown($questionId): void
    {
        $question = QuizQuestion::findOrFail($questionId);
        $next = QuizQuestion::where('quiz_id', $question->quiz_id)->where('order', '>', $question->order)->orderBy('order', 'asc')->first();
        if ($next) {
            $nextOrder = $next->order;
            $next->update(['order' => $question->order]);
            $question->update(['order' => $nextOrder]);
            $this->success(__('Question order updated.'));
        }
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

    public function getOptionLetter($index): string
    {
        return chr(65 + (int)$index);
    }

    private function resetQuizForm(): void
    {
        $this->quizTitle = 'Quiz : ';
        $this->quizDescription = 'Évaluation finale : ';
        $this->quizTimeLimit = 4;
        $this->quizPassingScore = 70;
        $this->quizMaxAttempts = 3;
        $this->quizIsPublished = true;
        $this->selectedLessonId = null;
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

    public $selectedLessonId = null;

    public function getLessonsProperty()
    {
        // Récupérer les IDs des leçons qui ont déjà un quiz
        $lessonsWithQuiz = Quiz::whereNotNull('lesson_id')->pluck('lesson_id')->toArray();

        // Pour l'édition, si le quiz actuel a une leçon, on l'ajoute à la liste des leçons autorisées
        $currentLessonId = $this->editingQuiz ? $this->editingQuiz->lesson_id : null;

        $query = Lesson::where('course_id', $this->course->id)->orderBy('order');

        if ($currentLessonId) {
            // Inclure la leçon actuelle même si elle a déjà un quiz
            $query->where(function($q) use ($lessonsWithQuiz, $currentLessonId) {
                $q->whereNotIn('id', $lessonsWithQuiz)
                ->orWhere('id', $currentLessonId);
            });
        } else {
            $query->whereNotIn('id', $lessonsWithQuiz);
        }

        return $query->get(['id', 'title']);
    }

    public function render()
    {
        return $this->view([
            'quizzes' => $this->quizzes,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-6xl px-3 mx-auto md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">📝 {{ __('Quiz Builder') }}</h1>
                <p class="mt-1 text-sm text-base-content/70">{{ __('Create and manage quizzes for :course', ['course' => $course->title]) }}</p>
            </div>
            <x-button wire:click="openQuizModal" label="{{ __('Create Quiz') }}" icon="o-plus" class="btn-primary" />
        </div>

        {{-- Quizzes List --}}
        @if(count($quizzes) > 0)
            <div class="space-y-6">
                @foreach($quizzes as $quiz)
                    <x-card class="overflow-hidden">
                        <div class="flex flex-col gap-4 p-4 border-b md:flex-row md:items-center md:justify-between bg-gradient-to-r from-base-200 to-base-100">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10">
                                    <x-icon name="o-document-text" class="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <h3 class="font-semibold">{{ $quiz->title }}</h3>
                                    <p class="text-sm text-base-content/70">{{ Str::limit($quiz->description ?? __('No description'), 50) }}</p>
                                    <div class="flex flex-wrap gap-3 mt-1 text-xs text-base-content/50">
                                        <span>{{ $quiz->total_questions }} {{ __('questions') }}</span>
                                        <span>{{ $quiz->total_points }} {{ __('points') }}</span>
                                        <span>⏱️ {{ $quiz->time_limit ?? '∞' }} min</span>
                                        <span>🎯 {{ $quiz->passing_score }}%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-badge :value="$quiz->is_published ? __('Published') : __('Draft')" :class="$quiz->is_published ? 'badge-success' : 'badge-warning'" class="badge-soft" />
                                <x-button icon="{{ $quiz->is_published ? 'o-eye-slash' : 'o-eye' }}" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ $quiz->is_published ? __('Unpublish') : __('Publish') }}" wire:click="togglePublish({{ $quiz->id }})" />
                                <x-button icon="o-document-duplicate" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Duplicate') }}" wire:click="duplicateQuiz({{ $quiz->id }})" wire:confirm="{{ __('Duplicate this quiz?') }}" />
                                <x-button icon="o-eye" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Preview') }}"  link="{{ route('teacher.quizzes.preview', [$course, $quiz]) }}" />
                                <x-button icon="o-pencil" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Edit') }}" wire:click="openQuizModal({{ $quiz->id }})" />
                                <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" tooltip-left="{{ __('Delete') }}" wire:click="deleteQuiz({{ $quiz->id }})" wire:confirm="{{ __('Delete this quiz?') }}" />
                            </div>
                        </div>

                        {{-- Questions list --}}
                        <div class="divide-y divide-base-200">
                            @php $sortedQuestions = $quiz->questions->sortBy('order'); @endphp
                            @forelse($sortedQuestions as $index => $question)
                                <div class="p-4 transition hover:bg-base-200">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <div class="flex items-center gap-1">
                                                    <x-button icon="o-chevron-up" class="btn-circle btn-xs btn-ghost" wire:click="moveQuestionUp({{ $question->id }})" :disabled="$loop->first" />
                                                    <span class="w-8 text-sm font-medium text-center">{{ $question->order ?? $index + 1 }}</span>
                                                    <x-button icon="o-chevron-down" class="btn-circle btn-xs btn-ghost" wire:click="moveQuestionDown({{ $question->id }})" :disabled="$loop->last" />
                                                </div>
                                                <x-badge :value="$question->type === 'multiple_choice' ? __('Multiple Choice') : ($question->type === 'true_false' ? __('True / False') : __('Short Answer'))" class="badge-neutral badge-soft" />
                                                <span class="text-xs text-base-content/50">{{ $question->points }} {{ __('pts') }}</span>
                                            </div>
                                            <p class="mt-1">{{ $question->question }}</p>
                                            @if($question->type === 'multiple_choice' && $question->options)
                                                <div class="mt-2 space-y-1">
                                                    @foreach($question->options as $optIndex => $opt)
                                                        @if($opt)
                                                            <div class="flex items-center gap-2 text-sm">
                                                                <span class="flex items-center justify-center w-5 h-5 text-xs font-bold rounded-full bg-base-200">{{ chr(65 + $optIndex) }}</span>
                                                                <span>{{ $opt }}</span>
                                                                @if(is_array($question->correct_answer) && in_array($opt, $question->correct_answer))
                                                                    <span class="text-xs text-success">✓ {{ __('Correct') }}</span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex gap-1">
                                            <x-button icon="o-pencil" class="btn-circle btn-ghost btn-sm" wire:click="openQuestionModal({{ $quiz->id }}, {{ $question->id }})" />
                                            <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" wire:click="deleteQuestion({{ $question->id }})" wire:confirm="{{ __('Delete this question?') }}" />
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="py-8 text-center">
                                    <x-icon name="o-document-text" class="w-12 h-12 mx-auto mb-2 text-base-content/30" />
                                    <p class="text-base-content/60">{{ __('No questions yet') }}</p>
                                    <x-button wire:click="openQuestionModal({{ $quiz->id }})" label="{{ __('Add first question') }}" class="mt-3 btn-primary btn-sm" />
                                </div>
                            @endforelse
                            @if($quiz->questions->count() > 0)
                                <div class="p-3 text-center bg-base-200">
                                    <x-button wire:click="openQuestionModal({{ $quiz->id }})" label="{{ __('Add question') }}" icon="o-plus" class="btn-ghost btn-sm" />
                                </div>
                            @endif
                        </div>
                    </x-card>
                @endforeach
            </div>
        @else
            <x-card class="py-12 text-center">
                <x-icon name="o-document-text" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                <h3 class="mb-2 text-lg font-semibold">{{ __('No quizzes yet') }}</h3>
                <p class="mb-4 text-base-content/60">{{ __('Create your first quiz to test your students.') }}</p>
                <x-button wire:click="openQuizModal" label="{{ __('Create Quiz') }}" class="btn-primary" />
            </x-card>
        @endif

        {{-- Quiz Modal --}}
        <x-modal wire:model="showQuizModal" title="{{ $editingQuiz ? __('Edit Quiz') : __('Create New Quiz') }}" separator>
            <x-form wire:submit="saveQuiz" no-separator>
                <x-input wire:model="quizTitle" label="{{ __('Quiz Title') }}" placeholder="{{ __('e.g., Grammar Quiz A1') }}" icon="o-pencil" required />
                <x-textarea wire:model="quizDescription" label="{{ __('Description') }}" placeholder="{!! __('What will be tested in this quiz?') !!}" rows="2" icon="o-document-text" />
                <x-select
                    wire:model="selectedLessonId"
                    label="{{ __('Lesson') }}"
                    :options="$this->lessons->map(fn($l) => ['id' => $l->id, 'name' => $l->title])->toArray()"
                    option-value="id"
                    option-label="name"
                    placeholder="{{ __('Select a lesson') }}"
                    required
                />
                <div class="grid gap-4 md:grid-cols-3">
                    <x-input wire:model="quizTimeLimit" type="number" min="0" label="{{ __('Time limit (minutes)') }}" placeholder="{{ __('0 = no limit') }}" icon="o-clock" />
                    <x-input wire:model="quizPassingScore" type="number" min="0" max="100" label="{{ __('Passing score (%)') }}" icon="o-chart-bar" required />
                    <x-input wire:model="quizMaxAttempts" type="number" min="1" label="{{ __('Max attempts') }}" icon="o-arrow-path" required />
                </div>
                <x-toggle wire:model="quizIsPublished" label="{{ __('Publish quiz') }}" hint="{{ __('Published quizzes are visible to students') }}" />
                <x-slot:actions>
                    <x-button label="{{ __('Cancel') }}" wire:click="$set('showQuizModal', false)" class="btn-ghost" />
                    <x-button label="{{ $editingQuiz ? __('Save Changes') : __('Create Quiz') }}" class="btn-primary" type="submit" spinner="saveQuiz" />
                </x-slot:actions>
            </x-form>
        </x-modal>

        {{-- Question Modal --}}
        <x-modal wire:model="showQuestionModal" title="{{ $editingQuestion ? __('Edit Question') : __('New Question') }}" size="2xl" separator>
            <x-form wire:submit="saveQuestion" no-separator>
                <x-textarea wire:model="questionText" label="{{ __('Question') }}" rows="3" placeholder="{{ __('Enter your question...') }}" required />
                <div class="grid gap-4 md:grid-cols-2">
                    <x-select wire:model.live="questionType" label="{{ __('Question type') }}" :options="$this->questionTypes" option-value="id" option-label="name" />
                    <x-input wire:model="questionPoints" type="number" min="1" label="{{ __('Points') }}" required />
                </div>

                {{-- Multiple choice options --}}
                @if($questionType === 'multiple_choice')
                    <div class="space-y-2">
                        <label class="font-medium">{{ __('Options') }}</label>
                        @foreach($questionOptions as $index => $option)
                            <div class="flex items-center gap-2">
                                <span class="flex items-center justify-center w-8 h-8 text-sm font-bold rounded-full bg-base-200">{{ chr(65 + $index) }}</span>
                                <x-input wire:model="questionOptions.{{ $index }}" placeholder="{{ __('Option') }} {{ chr(65 + $index) }}" class="flex-1" />
                                @if($index >= 2)
                                    <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" wire:click="removeOption({{ $index }})" />
                                @endif
                            </div>
                        @endforeach
                        <x-button label="{{ __('Add option') }}" icon="o-plus" class="btn-ghost btn-sm" wire:click="addOption" />
                    </div>
                    <x-select wire:model.live="questionCorrectAnswer" label="{{ __('Correct answer') }}" :options="collect($questionOptions)->filter()->map(fn($opt, $idx) => ['id' => $opt, 'name' => chr(65 + $idx) . '. ' . $opt])->toArray()" option-value="id" option-label="name" placeholder="{{ __('Select the correct answer') }}" />
                @endif

                {{-- True/False --}}
                @if($questionType === 'true_false')
                    <div>
                        <label class="font-medium">{{ __('Correct answer') }}</label>
                        <div class="flex gap-4 mt-2">
                            <label class="flex items-center gap-2"><input type="radio" wire:model.live="questionCorrectAnswer" value="true" class="radio radio-primary" /> {{ __('True') }}</label>
                            <label class="flex items-center gap-2"><input type="radio" wire:model.live="questionCorrectAnswer" value="false" class="radio radio-primary" /> {{ __('False') }}</label>
                        </div>
                    </div>
                @endif

                {{-- Short answer --}}
                @if($questionType === 'short_answer')
                    <x-input wire:model.live="questionCorrectAnswer" label="{{ __('Correct answer') }}" placeholder="{{ __('e.g., Hallo') }}" required />
                @endif

                <x-textarea wire:model="questionExplanation" label="{{ __('Explanation (optional)') }}" rows="2" placeholder="{{ __('Explain why this answer is correct') }}" />

                <x-slot:actions>
                    <x-button label="{{ __('Cancel') }}" wire:click="$set('showQuestionModal', false)" class="btn-ghost" />
                    <x-button label="{{ $editingQuestion ? __('Save Changes') : __('Add Question') }}" class="btn-primary" type="submit" spinner="saveQuestion" />
                </x-slot:actions>
            </x-form>
        </x-modal>
    </div>
</div>
