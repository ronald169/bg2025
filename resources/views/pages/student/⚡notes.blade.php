<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Note;
use App\Models\Enrollment;
use Mary\Traits\Toast;

new
#[Title('My Notes - German Learning')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public $showModal = false;
    public $editingId = null;
    public $title = '';
    public $content = '';
    public $course_id = null;
    public $search = '';

    // Getters (remplacent les anciens #[Computed])
    public function getCourses()
    {
        return Enrollment::where('user_id', auth()->id())
            ->with('course')
            ->get()
            ->pluck('course')
            ->filter();
    }

    public function getNotes()
    {
        $query = Note::where('user_id', auth()->id())
            ->with('course')
            ->latest();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->course_id) {
            $query->where('course_id', $this->course_id);
        }

        return $query->get();
    }

    public function getNotesCount()
    {
        return $this->getNotes()->count();
    }

    public function openModal($id = null): void
    {
        if ($id) {
            $note = Note::where('user_id', auth()->id())->findOrFail($id);
            $this->editingId = $note->id;
            $this->title = $note->title;
            $this->content = $note->content;
            $this->course_id = $note->course_id;
        } else {
            $this->resetForm();
        }
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string|min:3',
        ], [
            'title.required'   => __('Please enter a title.'),
            'content.required' => __('Please enter some content.'),
            'content.min'      => __('Content must be at least 3 characters long.'),
        ]);

        if ($this->editingId) {
            $note = Note::where('user_id', auth()->id())->findOrFail($this->editingId);
            $note->update([
                'title'     => $this->title,
                'content'   => $this->content,
                'course_id' => $this->course_id,
            ]);
            $this->success(__('Note updated! 📝'));
        } else {
            Note::create([
                'user_id'   => auth()->id(),
                'title'     => $this->title,
                'content'   => $this->content,
                'course_id' => $this->course_id,
            ]);
            $this->success(__('Note created! 🎉'));
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function deleteNote($id): void
    {
        $note = Note::where('user_id', auth()->id())->findOrFail($id);
        $note->delete();
        $this->success(__('Note deleted! 🗑️'));
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->content = '';
        $this->course_id = null;
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->course_id = null;
        $this->success(__('Filters reset.'));
    }

    public function getCourseName($courseId)
    {
        $course = $this->getCourses()->firstWhere('id', $courseId);
        return $course?->title ?? __('General');
    }

    public function getExcerpt($content, $length = 100): string
    {
        $cleanContent = strip_tags($content);
        if (strlen($cleanContent) <= $length) {
            return $cleanContent;
        }
        return substr($cleanContent, 0, $length) . '...';
    }

    public function render()
    {
        return $this->view([
            'courses'    => $this->getCourses(),
            'notes'      => $this->getNotes(),
            'notesCount' => $this->getNotesCount(),
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- En-tête --}}
        <div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold md:text-3xl">📝 {{ __('My Notes') }}</h1>
                <p class="mt-1 text-sm text-base-content/70">{{ __('Save and organize your learning notes') }}</p>
            </div>
            <x-button wire:click="openModal" label="{{ __('New note') }}" icon="o-plus" class="btn-primary" />
        </div>

        {{-- Filtres --}}
        <x-card class="mb-6">
            <div class="flex flex-col gap-3 md:flex-row">
                <div class="flex-1">
                    <x-input wire:model.live.debounce.300ms="search" icon="o-magnifying-glass"
                             placeholder="{{ __('Search notes...') }}" class="w-full" clearable />
                </div>
                <div class="w-full md:w-64">
                    <x-select wire:model.live="course_id"
                              :options="$courses->map(fn($c) => ['id' => $c->id, 'name' => $c->title])->prepend(['id' => '', 'name' => __('All courses')])"
                              option-value="id" option-label="name"
                              id="course_filter" name="course_filter" clearable />
                </div>
                @if($search || $course_id)
                    <x-button wire:click="clearFilters" label="{{ __('Reset') }}" icon="o-x-mark" class="btn-ghost" />
                @endif
            </div>
        </x-card>

        {{-- Statistiques --}}
        <div class="flex items-center gap-2 mb-6">
            <x-badge :value="$notesCount . ' ' . __('notes')" class="badge-soft badge-info" />
        </div>

        {{-- Grille des notes --}}
        @if($notesCount > 0)
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($notes as $note)
                    <x-card class="overflow-hidden transition-all duration-200 hover:shadow-md">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-semibold text-base-content line-clamp-1">{{ $note->title }}</h3>
                                <div class="flex items-center gap-2 mt-1">
                                    <x-badge :value="$this->getCourseName($note->course_id)" icon="o-book-open" class="badge-soft badge-neutral" />
                                </div>
                            </div>
                            <div class="flex gap-1 transition-opacity opacity-0 group-hover:opacity-100">
                                <x-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="openModal({{ $note->id }})" tooltip="{{ __('Edit') }}" />
                                <x-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="deleteNote({{ $note->id }})" wire:confirm="{{ __('Delete this note?') }}" tooltip="{{ __('Delete') }}" />
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="text-sm text-base-content/70 line-clamp-3">
                                {{ $this->getExcerpt($note->content, 120) }}
                            </p>
                        </div>
                        <div class="flex items-center justify-between pt-3 mt-4 border-t border-base-200">
                            <div class="flex items-center gap-1 text-xs text-base-content/50">
                                <x-icon name="o-calendar" class="w-3 h-3" />
                                <span>{{ $note->created_at->format('d.m.Y') }}</span>
                            </div>
                            <div class="flex items-center gap-1 text-xs text-base-content/50">
                                <x-icon name="o-clock" class="w-3 h-3" />
                                <span>{{ $note->updated_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </x-card>
                @endforeach
            </div>
        @else
            <x-card class="py-12 text-center">
                @if($search || $course_id)
                    <x-icon name="o-magnifying-glass" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <h3 class="mb-2 text-lg font-semibold text-base-content">{{ __('No notes found') }}</h3>
                    <p class="mb-4 text-base-content/60">{{ __('No notes match your search criteria.') }}</p>
                    <x-button wire:click="clearFilters" label="{{ __('Reset filters') }} →" class="btn-outline" />
                @else
                    <x-icon name="o-document-text" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <h3 class="mb-2 text-lg font-semibold text-base-content">{{ __('No notes yet') }}</h3>
                    <p class="mb-4 text-base-content/60">{{ __('Create your first note to capture important information.') }}</p>
                    <x-button wire:click="openModal" label="{{ __('Create first note →') }}" class="btn-primary" />
                @endif
            </x-card>
        @endif
    </div>

    {{-- Modal de création/édition --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="$set('showModal', false)">
            <div class="w-full max-w-2xl shadow-xl bg-base-100 rounded-xl">
                <div class="flex items-center justify-between p-5 border-b">
                    <h3 class="text-lg font-bold">
                        @if($editingId)
                            ✏️ {{ __('Edit note') }}
                        @else
                            📝 {{ __('New note') }}
                        @endif
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-base-content/50 hover:text-base-content">
                        <x-icon name="o-x-mark" class="w-6 h-6" />
                    </button>
                </div>
                <div class="p-6 space-y-5">
                    <x-input wire:model="title" label="{{ __('Title') }} *" placeholder="{{ __('e.g. Important vocabulary Lesson 3') }}" icon="o-pencil" required />
                    <x-select wire:model="course_id"
                              :options="$courses->map(fn($c) => ['id' => $c->id, 'name' => $c->title])->prepend(['id' => '', 'name' => __('General (no course)')])"
                              option-value="id" option-label="name"
                              label="{{ __('Course (optional)') }}" id="course_select" name="course_select" />
                    <x-textarea wire:model="content" label="{{ __('Content') }} *" placeholder="{{ __('Write your notes here...') }}" rows="8" required />
                </div>
                <div class="flex justify-end gap-3 p-5 border-t">
                    <x-button wire:click="$set('showModal', false)" label="{{ __('Cancel') }}" class="btn-ghost" />
                    <x-button wire:click="save" label="{{ $editingId ? __('Update') : __('Create') }}" class="btn-primary" spinner="save" />
                </div>
            </div>
        </div>
    @endif
</div>
