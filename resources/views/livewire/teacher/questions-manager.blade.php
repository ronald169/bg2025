<?php

use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new
#[Title('Question Management')]
#[Layout('components.layouts.admin')]
class extends Component {

    use Toast;

    public Course $course;
    public Quiz $quiz;
    public $questions;

    // États pour la modale
    public bool $showQuestionModal = false;
    public ?Question $currentQuestion = null;
    public string $modalTitle = 'Create Question';

    // Form fields
    public string $question = '';
    public string $type = 'multiple_choice';
    public array $options = ['', ''];
    public string $correct_answer = '';
    public int $points = 1;
    public string $explanation = '';

    public function mount(Course $course, Quiz $quiz): void
    {
        $this->course = $course;
        $this->quiz = $quiz;
        $this->loadQuestions();
    }

    public function loadQuestions(): void
    {
        $this->questions = $this->quiz->questions()
            ->orderBy('created_by')
            ->get();
    }

    public function openCreateModal(): void
    {
        $this->reset([
            'question',
            'type',
            'options',
            'correct_answer',
            'points',
            'explanation'
        ]);

        $this->currentQuestion = null;
        $this->modalTitle = __('Create Question');
        $this->showQuestionModal = true;
    }

    public function openEditModal(Question $question): void
    {
        $this->currentQuestion = $question;
        $this->question = $question->question;
        $this->type = $question->type;
        $this->points = $question->points;
        $this->explanation = $question->explanation ?? '';

        // Charger les options selon le type
        if ($question->type === 'multiple_choice' && $question->options) {
            $this->options = $question->options;
        } else {
            $this->options = ['', ''];
        }

        $this->correct_answer = $question->correct_answer;
        $this->modalTitle = __('Edit Question');
        $this->showQuestionModal = true;
    }

    public function addOption(): void
    {
        if (count($this->options) < 6) {
            $this->options[] = '';
        } else {
            $this->warning(__('Maximum 6 options allowed.'));
        }
    }

    public function removeOption($index): void
    {
        if (count($this->options) > 2) {
            unset($this->options[$index]);
            $this->options = array_values($this->options);

            // Si on supprime l'option qui était la bonne réponse, réinitialiser la bonne réponse
            if ($this->correct_answer == $index) {
                $this->correct_answer = '';
            } elseif ($this->correct_answer > $index) {
                // Ajuster l'index de la bonne réponse si nécessaire
                $this->correct_answer = $this->correct_answer - 1;
            }
        } else {
            $this->warning(__('Minimum 2 options required.'));
        }
    }

    public function saveQuestion(): void
    {
        $rules = [
            'question' => 'required|string|max:1000',
            'type' => 'required|in:multiple_choice,true_false,short_answer',
            'points' => 'required|integer|min:1|max:10',
            'explanation' => 'nullable|string|max:500'
        ];

        // Règles spécifiques selon le type
        if ($this->type === 'multiple_choice') {
            $rules['options'] = 'required|array|min:2';
            $rules['options.*'] = 'required|string|max:255';
            $rules['correct_answer'] = 'required|integer|min:0|max:' . (count($this->options) -1);

            // Valider qu'au moins une option n'est pas vide
            $validOptions = array_filter($this->options);
            if (count($validOptions) < 2) {
                $this->addError('options', __('At least two options are required.'));
                return;
            }
        } elseif ($this->type === 'true_false') {
            $rules['correct_answer'] = 'required|in:true,false';
        } else {
            $rules['correct_answer'] = 'required|string|max:255';
        }

        $datas = $this->validate($rules);

        try {
            $questionData = [
                'question' => $datas['question'],
                'type' => $datas['type'],
                'points' => $datas['points'],
                'explanation' => $datas['explanation'] ?: null,
                'correct_answer' => $datas['correct_answer']
            ];

            if ($this->type === 'multiple_choice') {
                // Filtrer les options vides
                $questionData['options'] = array_values(array_filter($this->options));
            } else {
                $questionData['options'] = null;
            }

            if ($this->currentQuestion) {
                $this->currentQuestion->update($questionData);
                $message = __('Question updated successfully!');
            } else {
                Question::create(array_merge($questionData, [
                    'quiz_id' => $this->quiz->id,
                    'order' => $this->questions->count() + 1
                ]));
                $message = __('Question created successfully!');
            }

            $this->showQuestionModal = false;
            $this->loadQuestions();
            $this->success($message);
        } catch (\Exception $e) {
            logger()->error('Error saving question: ' . $e->getMessage());
            $this->error(__('Error saving question.'));
        }
    }

    public function deleteQuestion(Question $question): void
    {
        try {
            $question->delete();
            $this->loadQuestions();
            $this->success(__('Question deleted successfully!'));
        } catch (\Exception $e) {
            $this->error(__('Error deleting question.'));
        }
    }

    public function reorderQuestions($orderedIds): void
    {
        try {
            foreach ($orderedIds as $order => $id) {
                Question::where('id', $id)->update(['order' => $order + 1]);
            }
            $this->loadQuestions();
            $this->success(__('Questions reordered successfully!'));
        } catch (\Exception $e) {
            $this->error(__('Error reordering questions.'));
        }
    }

    public function getQuestionTypes(): array
    {
        return [
            ['id' => 'multiple_choice', 'name' => __('Multiple Choice')],
            ['id' => 'true_false', 'name' => __('True/False')],
            ['id' => 'short_answer', 'name' => __('Short Answer')],
        ];
    }

    public function getTotalPointsProperty(): int
    {
        return $this->questions->sum('points');
    }

}; ?>

<div>
    {{-- Navigation rapide --}}
    <div class="bg-white border-b mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center space-x-4 py-4 overflow-x-auto">
                <x-button
                    label="{!! __('Course Details') !!}"
                    icon="o-arrow-left"
                    link="/teacher/courses/{{ $course->id }}/edit"
                    class="btn-ghost btn-sm"
                    responsive
                />

                <x-button
                    label="{!! __('Manage Lessons') !!}"
                    icon="o-book-open"
                    link="/teacher/courses/{{ $course->id }}/lessons"
                    class="btn-ghost btn-sm"
                    responsive
                />

                <x-button
                    label="{!! __('Quiz Manager') !!}"
                    icon="o-question-mark-circle"
                    link="/teacher/courses/{{ $course->id }}/quizzes"
                    class="btn-ghost btn-sm"
                    responsive
                />

                <x-button
                    label="{!! __('Question Management') !!}"
                    icon="o-document-text"
                    class="btn-primary btn-sm"
                    disabled
                />
            </nav>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- En-tête --}}
        <x-header
            title="{!! __('Question Management') !!}"
            subtitle="{{ $quiz->title }}"
        >
            <x-slot:actions>
                <x-button
                    label="{!! __('Add Question') !!}"
                    icon="o-plus"
                    class="btn-primary"
                    wire:click="openCreateModal"
                />
            </x-slot:actions>
        </x-header>

        {{-- Statistiques --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <x-stat
                title="{!! __('Total Questions') !!}"
                value="{{ $questions->count() }}"
                icon="o-document-text"
            />
            <x-stat
                title="{!! __('Total Points') !!}"
                value="{{ $this->total_points }}"
                icon="o-trophy"
            />
            <x-stat
                title="{!! __('Multiple Choice') !!}"
                value="{{ $questions->where('type', 'multiple_choice')->count() }}"
                icon="o-list-bullet"
            />
            <x-stat
                title="{!! __('True/False') !!}"
                value="{{ $questions->where('type', 'true_false')->count() }}"
                icon="o-check-badge"
            />
        </div>

        {{-- Liste des questions --}}
        <x-card shadow>
            <x-slot:title>
                {!! __('Quiz Questions') !!}
            </x-slot:title>

            @if($questions->count() > 0)
                <div class="space-y-4"
                     x-data="{
                         draggedQuestion: null,
                         reorderQuestions() {
                             const orderedIds = Array.from(this.$el.querySelectorAll('.question-item'))
                                 .map(item => item.getAttribute('data-question-id'));

                             if (orderedIds.length > 0) {
                                 @this.reorderQuestions(orderedIds);
                             }
                         }
                     }"
                     x-sortable="{
                         animation: 150,
                         ghostClass: 'opacity-50',
                         onEnd: function() {
                             $data.reorderQuestions();
                         }
                     }">

                    @foreach($questions as $question)
                        <div class="question-item bg-gray-50 rounded-lg border border-gray-200 p-4 cursor-move"
                             data-question-id="{{ $question->id }}">
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                {{-- Section gauche : Contenu de la question --}}
                                <div class="flex items-start space-x-3 flex-1 min-w-0">
                                    {{-- Handle de drag --}}
                                    <div class="drag-handle pt-1 flex-shrink-0">
                                        <x-icon name="o-bars-3" class="w-5 h-5 text-gray-400 hover:text-primary transition-colors" />
                                    </div>

                                    {{-- Numéro --}}
                                    <div class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-semibold text-xs flex-shrink-0">
                                        {{ $loop->iteration }}
                                    </div>

                                    {{-- Détails de la question --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-2">
                                            <h3 class="font-semibold text-gray-900 text-base">
                                                {{ $question->question }}
                                            </h3>
                                            <x-badge
                                                :value="ucfirst(str_replace('_', ' ', $question->type))"
                                                class="badge-info badge-xs"
                                            />
                                            <x-badge
                                                :value="$question->points . ' ' . __('points')"
                                                class="badge-warning badge-xs"
                                            />
                                        </div>

                                        {{-- Affichage selon le type --}}
                                        @if($question->type === 'multiple_choice')
                                            <div class="space-y-1 text-sm text-gray-600">
                                                @foreach($question->formatted_options as $index => $option)
                                                    <div class="flex items-center space-x-2">
                                                        <span class="w-4 h-4 rounded-full border border-gray-300 flex items-center justify-center text-xs
                                                            {{ $index == $question->correct_answer ? 'bg-green-500 border-green-500 text-white' : '' }}">
                                                            {{ chr(65 + $index) }}
                                                        </span>
                                                        <span class="{{ $index == $question->correct_answer ? 'text-green-600 font-medium' : '' }}">
                                                            {{ $option }}
                                                            @if($index == $question->correct_answer)
                                                                <x-icon name="o-check" class="w-3 h-3 text-green-500 ml-1 inline" />
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif($question->type === 'true_false')
                                            <div class="text-sm text-gray-600">
                                                <span class="font-medium">
                                                    {!! __('Correct answer') !!}:
                                                    <span class="{{ $question->correct_answer === 'true' ? 'text-green-600' : 'text-red-600' }}">
                                                        {{ $question->correct_answer === 'true' ? __('True') : __('False') }}
                                                    </span>
                                                </span>
                                            </div>
                                        @else
                                            <div class="text-sm text-gray-600">
                                                <span class="font-medium">
                                                    {!! __('Correct answer') !!}:
                                                    <span class="text-green-600">"{{ $question->correct_answer }}"</span>
                                                </span>
                                            </div>
                                        @endif

                                        {{-- Dans la boucle foreach des questions --}}
                                        @if($question->explanation)
                                            <div class="mt-2 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                                <div class="flex items-start space-x-2">
                                                    <x-icon name="o-light-bulb" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" />
                                                    <div>
                                                        <strong class="text-sm text-blue-700">{!! __('Explanation') !!}:</strong>
                                                        <p class="text-sm text-blue-600 mt-1">{{ $question->explanation }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Section droite : Actions --}}
                                <div class="flex flex-wrap gap-1 sm:gap-2 justify-end">
                                    <x-button
                                        icon="o-pencil"
                                        class="btn-ghost btn-sm btn-square"
                                        tooltip="{!! __('Edit question') !!}"
                                        wire:click="openEditModal({{ $question->id }})"
                                    />
                                    <x-button
                                        icon="o-trash"
                                        class="btn-ghost btn-sm btn-square text-red-500 hover:text-red-700"
                                        tooltip="{!! __('Delete question') !!}"
                                        wire:click="deleteQuestion({{ $question->id }})"
                                        spinner
                                    />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-alert icon="o-document-text" title="{!! __('No questions yet') !!}" class="bg-blue-50">
                    <p class="text-gray-600 mb-4">
                        {!! __('Add questions to your quiz to assess your students knowledge.') !!}
                    </p>
                    <x-slot:actions>
                        <x-button
                            label="{!! __('Add First Question') !!}"
                            icon="o-plus"
                            wire:click="openCreateModal"
                            class="btn-primary"
                        />
                    </x-slot:actions>
                </x-alert>
            @endif
        </x-card>

        {{-- Modale création/édition question --}}
        <x-modal wire:model="showQuestionModal" title="{{ $modalTitle }}" class="">
            <x-form wire:submit="saveQuestion">
                <div class="space-y-4">
                    {{-- Type de question --}}
                    <x-select
                        label="{!! __('Question Type') !!}"
                        wire:model.live="type"
                        :options="$this->getQuestionTypes()"
                        required
                    />

                    {{-- Question --}}
                    <x-textarea
                        label="{!! __('Question Text') !!}"
                        wire:model="question"
                        required
                        placeholder="{!! __('Enter your question here...') !!}"
                        rows="3"
                    />

                    {{-- Points --}}
                    <x-input
                        label="{!! __('Points') !!}"
                        wire:model="points"
                        type="number"
                        min="1"
                        max="10"
                        required
                    />

                    {{-- Options pour choix multiple - VERSION BOUTONS --}}
                    @if($type === 'multiple_choice')
                        <div class="space-y-3">
                            <label class="block text-sm font-medium text-gray-700">
                                {!! __('Options') !!}
                                <span class="text-xs text-gray-500 ml-2">
                                    ({!! __('Click the checkmark to mark as correct answer') !!})
                                </span>
                            </label>

                            @foreach($options as $index => $option)
                                <div class="flex items-center space-x-2 p-2 rounded-lg border border-gray-200
                                    {{ $correct_answer == $index ? 'bg-green-50 border-green-200' : '' }}"
                                    wire:key="option-{{ $index }}">

                                    {{-- Lettre de l'option --}}
                                    <div class="w-6 h-6 rounded-full border border-gray-300 flex items-center justify-center text-xs font-medium bg-white
                                        {{ $correct_answer == $index ? 'bg-green-500 border-green-500 text-white' : '' }}">
                                        {{ chr(65 + $index) }}
                                    </div>

                                    {{-- Champ de saisie --}}
                                    <x-input
                                        wire:model="options.{{ $index }}"
                                        placeholder="{!! __('Option text...') !!}"
                                        class="flex-1"
                                    />

                                    {{-- Bouton pour sélectionner comme bonne réponse --}}
                                    <button
                                        type="button"
                                        wire:click="$set('correct_answer', {{ $index }})"
                                        class="w-8 h-8 rounded-full border-2 flex items-center justify-center transition-colors
                                            {{ $correct_answer == $index ? 'bg-green-500 border-green-500 text-white' : 'border-gray-300 hover:border-green-400' }}"
                                        title="{!! __('Mark as correct answer') !!}"
                                    >
                                        @if($correct_answer == $index)
                                            <x-icon name="o-check" class="w-4 h-4" />
                                        @else
                                            <x-icon name="o-plus" class="w-4 h-4 text-gray-400" />
                                        @endif
                                    </button>

                                    {{-- Bouton supprimer --}}
                                    @if(count($options) > 1)
                                        <x-button
                                            icon="o-trash"
                                            wire:click="removeOption({{ $index }})"
                                            class="btn-ghost btn-sm text-red-500"
                                        />
                                    @endif
                                </div>
                            @endforeach

                            {{-- Bouton ajouter option --}}
                            @if(count($options) < 6)
                                <x-button
                                    label="{!! __('Add Option') !!}"
                                    icon="o-plus"
                                    wire:click="addOption"
                                    class="btn-outline btn-sm"
                                />
                            @else
                                <p class="text-xs text-gray-500 text-center">
                                    {!! __('Maximum 6 options reached') !!}
                                </p>
                            @endif
                        </div>
                    @endif

                    {{-- Réponse correcte pour vrai/faux --}}
                    @if($type === 'true_false')
                        <x-radio
                            label="{!! __('Correct Answer') !!}"
                            wire:model="correct_answer"
                            :options="[
                                ['id' => 'true', 'name' => __('True')],
                                ['id' => 'false', 'name' => __('False')],
                            ]"
                            option-value="id"
                            option-label="name"
                        />
                    @endif

                    {{-- Réponse correcte pour réponse courte --}}
                    @if($type === 'short_answer')
                        <x-input
                            label="{!! __('Correct Answer') !!}"
                            wire:model="correct_answer"
                            required
                            placeholder="{!! __('Enter the correct answer...') !!}"
                        />
                    @endif

                    {{-- Explication --}}
                    <x-textarea
                        label="{!! __('Explanation') !!}"
                        wire:model="explanation"
                        placeholder="{!! __('Optional explanation for the correct answer...') !!}"
                        rows="2"
                    />
                </div>

                {{-- Actions --}}
                <x-slot:actions>
                    <x-button
                        label="{!! __('Cancel') !!}"
                        wire:click="$set('showQuestionModal', false)"
                        class="btn-ghost"
                    />
                    <x-button
                        label="{!! __('Save Question') !!}"
                        type="submit"
                        class="btn-primary"
                        spinner
                    />
                </x-slot:actions>
            </x-form>
        </x-modal>
    </div>
    @section('script')
        <script>
            document.addEventListener('livewire:initialized', () => {
                // Validation des options
                Livewire.on('validateOptions', (options) => {
                    const validOptions = options.filter(option => option.trim() !== '');
                    if (validOptions.length < 2) {
                        alert('{{ __("At least two options are required.") }}');
                        return false;
                    }

                    return true;
                });
            });
        </script>
    @endsection
</div>
