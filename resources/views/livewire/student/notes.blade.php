<?php

use App\Models\Note;
use App\Models\Enrollment;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Meine Notizen - Deutsch lernen')]
#[Layout('components.layouts.dashboard-student')]
class extends Component {
    use Toast;

    public $showModal = false;
    public $editingId = null;
    public $title = '';
    public $content = '';
    public $course_id = null;
    public $search = '';

    #[Computed]
    public function courses()
    {
        return Enrollment::where('user_id', auth()->id())
            ->with('course')
            ->get()
            ->pluck('course')
            ->filter();
    }

    #[Computed]
    public function notes()
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

    #[Computed]
    public function notesCount()
    {
        return $this->notes->count();
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
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:3',
        ], [
            'title.required' => 'Bitte gib einen Titel ein.',
            'content.required' => 'Bitte gib einen Inhalt ein.',
            'content.min' => 'Der Inhalt muss mindestens 3 Zeichen lang sein.',
        ]);

        if ($this->editingId) {
            $note = Note::where('user_id', auth()->id())->findOrFail($this->editingId);
            $note->update([
                'title' => $this->title,
                'content' => $this->content,
                'course_id' => $this->course_id,
            ]);
            $this->success('Notiz aktualisiert! 📝');
        } else {
            Note::create([
                'user_id' => auth()->id(),
                'title' => $this->title,
                'content' => $this->content,
                'course_id' => $this->course_id,
            ]);
            $this->success('Notiz erstellt! 🎉');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function deleteNote($id): void
    {
        $note = Note::where('user_id', auth()->id())->findOrFail($id);
        $note->delete();
        $this->success('Notiz gelöscht! 🗑️');
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
        $this->success('Filter zurückgesetzt.');
    }

    public function getCourseName($courseId)
    {
        $course = $this->courses->firstWhere('id', $courseId);
        return $course?->title ?? 'Allgemein';
    }

    public function getExcerpt($content, $length = 100): string
    {
        $cleanContent = strip_tags($content);
        if (strlen($cleanContent) <= $length) {
            return $cleanContent;
        }
        return substr($cleanContent, 0, $length) . '...';
    }
}
?>

<div class="py-8">
    <div class="px-4 mx-auto max-w-7xl">

        <!-- Header -->
        <div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">📝 {{ __('Meine Notizen') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Speichere und organisiere deine Lernnotizen') }}</p>
            </div>
            <x-button
                wire:click="openModal"
                icon="o-plus"
                class="btn-primary">
                Neue Notiz
            </x-button>
        </div>

        <!-- Filters -->
        <div class="p-4 mb-6 bg-white shadow-sm rounded-xl">
            <div class="flex flex-col gap-3 md:flex-row">
                <div class="flex-1">
                    <x-input
                        wire:model.live.debounce.300ms="search"
                        icon="o-magnifying-glass"
                        placeholder="Notizen durchsuchen..."
                        class="w-full" />
                </div>
                <div class="w-full md:w-64">
                    <select
                        wire:model.live="course_id"
                        class="w-full px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                        <option value="">{{ __('Alle Kurse') }}</option>
                        @foreach($this->courses as $course)
                            <option value="{{ $course->id }}">{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                @if($search || $course_id)
                    <x-button
                        wire:click="clearFilters"
                        icon="o-x-mark"
                        class="btn-ghost">
                        Zurücksetzen
                    </x-button>
                @endif
            </div>
        </div>

        <!-- Stats -->
        <div class="flex items-center gap-2 mb-6">
            <div class="px-3 py-1 text-sm text-gray-600 bg-gray-100 rounded-full">
                {{ $this->notesCount }} Notizen
            </div>
        </div>

        <!-- Notes Grid -->
        @if($this->notesCount > 0)
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($this->notes as $note)
                <div class="overflow-hidden transition-all duration-200 bg-white shadow-sm rounded-xl hover:shadow-md group">
                    <div class="p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 line-clamp-1">{{ $note->title }}</h3>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">
                                        <x-icon name="o-book-open" class="w-3 h-3" />
                                        {{ $this->getCourseName($note->course_id) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex gap-1 transition-opacity opacity-0 group-hover:opacity-100">
                                <x-button
                                    wire:click="openModal({{ $note->id }})"
                                    icon="o-pencil"
                                    size="xs"
                                    class="btn-ghost btn-sm"
                                    tooltip="Bearbeiten" />
                                <x-button
                                    wire:click="deleteNote({{ $note->id }})"
                                    icon="o-trash"
                                    size="xs"
                                    class="text-red-500 btn-ghost btn-sm"
                                    wire:confirm="Diese Notiz wirklich löschen?"
                                    tooltip="Löschen" />
                            </div>
                        </div>

                        <div class="mt-3">
                            <p class="text-sm text-gray-600 line-clamp-3">
                                {{ $this->getExcerpt($note->content, 120) }}
                            </p>
                        </div>

                        <div class="pt-3 mt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1 text-xs text-gray-400">
                                    <x-icon name="o-calendar" class="w-3 h-3" />
                                    <span>{{ $note->created_at->format('d.m.Y') }}</span>
                                </div>
                                <div class="flex items-center gap-1 text-xs text-gray-400">
                                    <x-icon name="o-clock" class="w-3 h-3" />
                                    <span>{{ $note->updated_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="p-12 text-center bg-white shadow-sm rounded-xl">
                @if($search || $course_id)
                    <x-icon name="o-magnifying-glass" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">Keine Notizen gefunden</h3>
                    <p class="mb-4 text-gray-500">Keine Notizen entsprechen deinen Suchkriterien.</p>
                    <x-button wire:click="clearFilters" class="btn-outline">
                        Filter zurücksetzen
                    </x-button>
                @else
                    <x-icon name="o-document-text" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">Noch keine Notizen</h3>
                    <p class="mb-4 text-gray-500">Erstelle deine erste Notiz, um wichtige Informationen festzuhalten.</p>
                    <x-button wire:click="openModal" class="btn-primary">
                        Erste Notiz erstellen →
                    </x-button>
                @endif
            </div>
        @endif

        <!-- Note MVP -->
        <div class="p-4 mt-8 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="font-medium text-blue-800">MVP Version</p>
                    <p class="text-sm text-blue-700">Prochaines fonctionnalités : pièces jointes, partage de notes, tags, recherche avancée et synchronisation avec les leçons.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="$set('showModal', false)">
        <div class="w-full max-w-2xl bg-white shadow-xl rounded-xl">
            <div class="p-5 border-b bg-gradient-to-r from-gray-50 to-white rounded-t-xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">
                        @if($editingId)
                            ✏️ {{ __('Notiz bearbeiten') }}
                        @else
                            📝 {{ __('Neue Notiz') }}
                        @endif
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                        <x-icon name="o-x-mark" class="w-6 h-6" />
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-5">
                <x-input
                    wire:model="title"
                    label="Titel *"
                    placeholder="z.B. Wichtige Vokabeln Lektion 3"
                    icon="o-pencil"
                    required />

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Kurs (optional)</label>
                    <select
                        wire:model="course_id"
                        class="w-full px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                        <option value="">{{ __('Allgemein (kein Kurs)') }}</option>
                        @foreach($this->courses as $course)
                            <option value="{{ $course->id }}">{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>

                <x-textarea
                    wire:model="content"
                    label="Inhalt *"
                    placeholder="Schreibe deine Notizen hier..."
                    rows="8"
                    required />
            </div>

            <div class="flex justify-end gap-3 p-5 border-t bg-gray-50 rounded-b-xl">
                <x-button
                    wire:click="$set('showModal', false)"
                    class="btn-ghost">
                    Abbrechen
                </x-button>
                <x-button
                    wire:click="save"
                    class="btn-primary"
                    spinner="save">
                    @if($editingId)
                        Aktualisieren
                    @else
                        Erstellen
                    @endif
                </x-button>
            </div>
        </div>
    </div>
    @endif
</div>
