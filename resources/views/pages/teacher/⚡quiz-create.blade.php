<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Mary\Traits\Toast;

new
#[Title('Create Quiz - Teacher')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public Course $course;
    public ?Lesson $lesson = null;

    // Quiz information
    public string $title = '';
    public string $description = '';
    public int $time_limit = 5;
    public int $passing_score = 60;
    public int $max_attempts = 3;
    public bool $is_published = true;

    // Questions
    public array $questions = [];
    public int $currentQuestionIndex = 0;
    public bool $showQuestionModal = false;
    public $editingQuestionId = null;

    // Question form
    public string $questionText = '';
    public string $questionType = 'multiple_choice';
    public int $questionPoints = 1;
    public array $questionOptions = ['', '', '', ''];
    public string $questionCorrectAnswer = '';
    public string $questionExplanation = '';

    public function mount(Course $course, $lesson = null): void
    {
        if ($course->teacher_id != auth()->id()) {
            abort(403);
        }

        $this->course = $course;

        if ($lesson) {
            if ($lesson instanceof Lesson) {
                $this->lesson = $lesson;
            } else {
                $this->lesson = Lesson::where('course_id', $course->id)->find($lesson);
            }
            if (!$this->lesson) {
                abort(404);
            }
        }

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
        $correctAnswer = $question['correct_answer'];
        $this->questionCorrectAnswer = is_array($correctAnswer) ? ($correctAnswer[0] ?? '') : ($correctAnswer ?? '');
        $this->questionExplanation = $question['explanation'] ?? '';
        $this->showQuestionModal = true;
    }

    public function saveQuestion(): void
    {
        $this->validate([
            'questionText'   => 'required|string|min:3',
            'questionPoints' => 'required|integer|min:1|max:100',
        ], [
            'questionText.required'   => __('Please enter a question.'),
            'questionText.min'        => __('The question must be at least 3 characters long.'),
            'questionPoints.required' => __('Please enter the points.'),
        ]);

        if ($this->questionType === 'multiple_choice') {
            $filledOptions = array_filter($this->questionOptions);
            if (count($filledOptions) < 2) {
                $this->error(__('Please add at least 2 answer options.'));
                return;
            }
            $this->validate([
                'questionCorrectAnswer' => 'required|string',
            ], ['questionCorrectAnswer.required' => __('Please select the correct answer.')]);
        } elseif ($this->questionType === 'true_false') {
            $this->validate([
                'questionCorrectAnswer' => 'required|in:true,false',
            ]);
        } else {
            $this->validate([
                'questionCorrectAnswer' => 'required|string|min:1',
            ], ['questionCorrectAnswer.required' => __('Please enter the correct answer.')]);
        }

        $formattedCorrectAnswer = $this->getFormattedCorrectAnswer();

        $questionData = [
            'id'             => $this->editingQuestionId ?? 'temp_' . uniqid(),
            'question'       => $this->questionText,
            'type'           => $this->questionType,
            'points'         => $this->questionPoints,
            'options'        => $this->questionType === 'multiple_choice' ? $this->questionOptions : null,
            'correct_answer' => $formattedCorrectAnswer,
            'explanation'    => $this->questionExplanation,
        ];

        if ($this->editingQuestionId && !str_starts_with((string)$this->editingQuestionId, 'temp_')) {
            foreach ($this->questions as $idx => $q) {
                if ($q['id'] == $this->editingQuestionId) {
                    $this->questions[$idx] = $questionData;
                    break;
                }
            }
        } else {
            $this->questions[] = $questionData;
        }

        $this->closeQuestionModal();
        $this->success(__('Question saved! ✅'));
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

    public function removeQuestion($index): void
    {
        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);
        $this->success(__('Question removed'));
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
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'time_limit'    => 'nullable|integer|min:1',
            'passing_score' => 'required|integer|min:0|max:100',
            'max_attempts'  => 'required|integer|min:1',
            'questions'     => 'required|array|min:1',
        ], [
            'title.required'       => __('Please enter a title.'),
            'questions.required'   => __('Please add at least one question.'),
            'questions.min'        => __('Please add at least one question.'),
        ]);

        $quiz = Quiz::create([
            'lesson_id'     => $this->lesson?->id,
            'title'         => $this->title,
            'description'   => $this->description,
            'time_limit'    => $this->time_limit,
            'passing_score' => $this->passing_score,
            'max_attempts'  => $this->max_attempts,
            'is_published'  => $this->is_published,
            'order'         => 0,
        ]);

        foreach ($this->questions as $order => $qData) {
            QuizQuestion::create([
                'quiz_id'        => $quiz->id,
                'question'       => $qData['question'],
                'type'           => $qData['type'],
                'options'        => $qData['type'] === 'multiple_choice' ? $qData['options'] : null,
                'correct_answer' => $qData['correct_answer'],
                'points'         => $qData['points'],
                'explanation'    => $qData['explanation'] ?? null,
                'order'          => $order,
            ]);
        }

        $this->success(__('Quiz created successfully! 🎉'));
        $this->redirectRoute('teacher.quizzes.preview', ['course' => $this->course, 'quiz' => $quiz]);
    }

    public function previewAndSave(): void
    {
        if (empty($this->questions)) {
            $this->error(__('Please add at least one question before previewing.'));
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
        return array_sum(array_column($this->questions, 'points'));
    }

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
        } elseif ($this->questionType === 'short_answer') {
            $this->questionOptions = [];
        }
    }

    public function updatedQuestionType($value): void
    {
        $this->refreshQuestionForm();
    }

    public function render()
    {
        return $this->view([
            'totalPoints' => $this->getTotalPoints(),
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-5xl px-3 mx-auto md:px-4">

        {{-- Navigation --}}
        <div class="mb-5">
            @if($lesson)
                <a href="{{ route('teacher.lessons.index', $course) }}" wire:navigate class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                    <x-icon name="o-arrow-left" class="w-4 h-4" />
                    {{ __('Back to lessons') }}
                </a>
            @else
                <a href="{{ route('teacher.quizzes.index', $course) }}" wire:navigate class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                    <x-icon name="o-arrow-left" class="w-4 h-4" />
                    {{ __('Back to quizzes') }}
                </a>
            @endif
        </div>

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">📝 {{ __('Create Quiz') }}</h1>
                <p class="mt-1 text-sm text-base-content/70">
                    {{ $course->title }}
                    @if($lesson) → {{ $lesson->title }} @endif
                </p>
            </div>
            <x-button wire:click="addQuestion" label="{{ __('Add question') }}" icon="o-plus" class="btn-primary btn-sm" />
        </div>

        <x-form wire:submit="saveQuiz" class="space-y-5">
            {{-- Quiz Information --}}
            <x-card class="shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-information-circle" class="w-5 h-5 text-primary" />
                    <h2 class="font-semibold">{{ __('Quiz Information') }}</h2>
                </div>
                <div class="space-y-4">
                    <x-input wire:model="title" label="{{ __('Quiz Title') }}" placeholder="{{ __('e.g., Grammar Quiz A1') }}" icon="o-pencil" required />
                    <x-textarea wire:model="description" label="{{ __('Description') }}" placeholder="{{ __('What will be tested in this quiz?') }}" rows="2" icon="o-document-text" />
                    <div class="grid gap-4 md:grid-cols-3">
                        <x-input wire:model="time_limit" type="number" min="1" label="{{ __('Time limit (minutes)') }}" placeholder="{{ __('No limit') }}" icon="o-clock" />
                        <x-input wire:model="passing_score" type="number" min="0" max="100" label="{{ __('Passing score (%)') }}" icon="o-chart-bar" required />
                        <x-input wire:model="max_attempts" type="number" min="1" label="{{ __('Max attempts') }}" icon="o-arrow-path" required />
                    </div>
                    <x-toggle wire:model="is_published" label="{{ __('Publish quiz') }}" hint="{{ __('Published quizzes are visible to students') }}" />
                </div>
            </x-card>

            {{-- Questions List --}}
            <x-card class="shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-list-bullet" class="w-5 h-5 text-primary" />
                    <h2 class="font-semibold">{{ __('Questions') }} ({{ count($questions) }})</h2>
                    <span class="text-sm text-base-content/60">{{ __('Total') }}: {{ $totalPoints }} {{ __('points') }}</span>
                </div>
                @if(count($questions) > 0)
                    <div class="space-y-3">
                        @foreach($questions as $index => $question)
                            <div class="p-3 transition border rounded-lg hover:bg-base-200">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex flex-wrap items-center gap-2 mb-2">
                                            <span class="flex items-center justify-center w-6 h-6 text-xs font-bold rounded-full bg-base-200">{{ $index + 1 }}</span>
                                            <span class="text-sm font-medium">{{ Str::limit($question['question'], 60) ?: __('New question') }}</span>
                                            <x-badge :value="$question['points'] . ' pts'" class="badge-neutral badge-soft" />
                                            <x-badge :value="$question['type'] === 'multiple_choice' ? __('Multiple Choice') : ($question['type'] === 'true_false' ? __('True / False') : __('Short Answer'))" class="badge-info badge-soft" />
                                        </div>
                                    </div>
                                    <div class="flex gap-1">
                                        <x-button icon="o-pencil" class="btn-circle btn-ghost btn-sm" wire:click="editQuestion({{ $index }})" />
                                        <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" wire:click="removeQuestion({{ $index }})" wire:confirm="{{ __('Remove this question?') }}" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-12 text-center border-2 border-dashed rounded-lg">
                        <x-icon name="o-list-bullet" class="w-12 h-12 mx-auto mb-2 text-base-content/30" />
                        <p class="text-base-content/60">{{ __('No questions yet') }}</p>
                        <p class="text-sm text-base-content/50">{{ __('Click "Add question" to get started') }}</p>
                    </div>
                @endif
            </x-card>

            {{-- Actions --}}
            <div class="flex flex-col justify-end gap-3 pt-4 sm:flex-row">
                <a href="{{ $lesson ? route('teacher.lessons.index', $course) : route('teacher.quizzes.index', $course) }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                <x-button type="button" wire:click="previewAndSave" label="{{ __('Save & Preview') }}" icon="o-eye" class="btn-secondary" />
                <x-button type="submit" label="{{ __('Save Quiz') }}" class="btn-primary" spinner="saveQuiz" />
            </div>
        </x-form>

        {{-- Question Modal --}}
        @if($showQuestionModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="closeQuestionModal"
                 x-data="{ questionType: @entangle('questionType') }"
                 x-init="$watch('questionType', () => $wire.refreshQuestionForm())">
                <div class="bg-base-100 rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
                    <div class="sticky top-0 p-4 border-b bg-base-100">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold">{{ $editingQuestionId ? __('Edit Question') : __('New Question') }}</h3>
                            <div class="flex gap-2">
                                <x-button icon="o-arrow-path" class="btn-circle btn-ghost btn-sm" wire:click="refreshQuestionForm" tooltip="{{ __('Reset form') }}" />
                                <x-button icon="o-x-mark" class="btn-circle btn-ghost btn-sm" wire:click="closeQuestionModal" />
                            </div>
                        </div>
                    </div>
                    <div class="p-5 space-y-4">
                        <x-textarea wire:model="questionText" label="{{ __('Question') }}" rows="3" placeholder="{{ __('e.g., What does "Hallo" mean in German?') }}" required />
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-select wire:model.live="questionType" label="{{ __('Question type') }}" :options="[
                                ['id' => 'multiple_choice', 'name' => __('Multiple Choice')],
                                ['id' => 'true_false', 'name' => __('True / False')],
                                ['id' => 'short_answer', 'name' => __('Short Answer')],
                            ]" option-value="id" option-label="name" />
                            <x-input wire:model="questionPoints" type="number" min="1" max="100" label="{{ __('Points') }}" required />
                        </div>

                        {{-- Multiple choice options --}}
                        @if($questionType === 'multiple_choice')
                            <div class="space-y-2">
                                <label class="font-medium">{{ __('Answer options') }}</label>
                                @foreach($questionOptions as $idx => $opt)
                                    <div class="flex items-center gap-2">
                                        <span class="flex items-center justify-center w-8 h-8 text-sm font-bold rounded-full bg-base-200">{{ chr(65 + $idx) }}</span>
                                        <x-input wire:model.live="questionOptions.{{ $idx }}" placeholder="{{ __('Option') }} {{ chr(65 + $idx) }}" class="flex-1" />
                                        @if($idx >= 2)
                                            <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" wire:click="removeOption({{ $idx }})" />
                                        @endif
                                    </div>
                                @endforeach
                                <x-button label="{{ __('Add option') }}" icon="o-plus" class="btn-ghost btn-sm" wire:click="addOption" />
                            </div>
                            <x-select wire:model.live="questionCorrectAnswer" label="{{ __('Correct answer') }}" :options="collect($questionOptions)->filter()->map(fn($opt, $i) => ['id' => $i, 'name' => chr(65 + $i) . '. ' . $opt])->toArray()" option-value="id" option-label="name" placeholder="{{ __('Select the correct answer') }}" />
                        @endif

                        {{-- True/False --}}
                        @if($questionType === 'true_false')
                            <div>
                                <label class="font-medium">{{ __('Correct answer') }}</label>
                                <div class="flex gap-4 mt-2">
                                    <label class="flex items-center gap-2"><input type="radio" wire:model="questionCorrectAnswer" value="true" class="radio radio-primary" /> {{ __('True') }}</label>
                                    <label class="flex items-center gap-2"><input type="radio" wire:model="questionCorrectAnswer" value="false" class="radio radio-primary" /> {{ __('False') }}</label>
                                </div>
                            </div>
                        @endif

                        {{-- Short answer --}}
                        @if($questionType === 'short_answer')
                            <x-input wire:model.live="questionCorrectAnswer" label="{{ __('Correct answer') }}" placeholder="{{ __('e.g., Hello') }}" required />
                        @endif

                        <x-textarea wire:model="questionExplanation" label="{{ __('Explanation (optional)') }}" rows="2" placeholder="{{ __('Explain why this answer is correct') }}" />
                    </div>
                    <div class="flex justify-end gap-3 p-5 border-t">
                        <x-button label="{{ __('Cancel') }}" wire:click="closeQuestionModal" class="btn-ghost" />
                        <x-button label="{{ __('Save question') }}" class="btn-primary" wire:click="saveQuestion" spinner="saveQuestion" />
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
