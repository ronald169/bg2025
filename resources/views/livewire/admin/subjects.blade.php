<?php

use App\Models\Subject;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Mary\Traits\Toast;

new
#[Title('Fächer verwalten - Admin')]
#[Layout('components.layouts.dashboard-admin')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    public bool $showModal = false;
    public $editingId = null;
    public string $name = '';
    public string $slug = '';
    public string $icon = '';
    public string $color = 'orange';
    public string $description = '';
    public bool $is_active = true;

    public array $colors = [
        ['id' => 'orange', 'name' => 'Orange (Deutsch)'],
        ['id' => 'blue', 'name' => 'Bleu'],
        ['id' => 'green', 'name' => 'Vert'],
        ['id' => 'red', 'name' => 'Rouge'],
        ['id' => 'yellow', 'name' => 'Jaune'],
        ['id' => 'purple', 'name' => 'Violet'],
        ['id' => 'pink', 'name' => 'Rose'],
        ['id' => 'indigo', 'name' => 'Indigo'],
        ['id' => 'teal', 'name' => 'Teal'],
    ];

    public array $icons = [
        'academic-cap' => '🎓 Académique',
        'book-open' => '📖 Livre',
        'calculator' => '🧮 Calculatrice',
        'beaker' => '🧪 Chimie',
        'globe-alt' => '🌍 Géographie',
        'language' => '🗣️ Langues',
        'musical-note' => '🎵 Musique',
        'chart-bar' => '📊 Statistiques',
        'user-group' => '👥 Société',
        'heart' => '❤️ Santé',
        'computer-desktop' => '💻 Informatique',
        'camera' => '📸 Art',
    ];

    #[Computed]
    public function subjects()
    {
        return Subject::withCount('courses')
            ->when($this->search, function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function totalSubjects()
    {
        return Subject::count();
    }

    #[Computed]
    public function activeCount()
    {
        return Subject::where('is_active', true)->count();
    }

    #[Computed]
    public function inactiveCount()
    {
        return Subject::where('is_active', false)->count();
    }

    #[Computed]
    public function totalCourses()
    {
        return Subject::withCount('courses')->get()->sum('courses_count');
    }

    public function openModal($id = null): void
    {
        if ($id) {
            $subject = Subject::findOrFail($id);
            $this->editingId = $subject->id;
            $this->name = $subject->name;
            $this->slug = $subject->slug;
            $this->icon = $subject->icon ?? '';
            $this->color = $subject->color ?? 'orange';
            $this->description = $subject->description ?? '';
            $this->is_active = $subject->is_active;
        } else {
            $this->resetForm();
        }
        $this->showModal = true;
    }

    public function updatedName($value): void
    {
        $this->slug = Str::slug($value);
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:subjects,slug,' . $this->editingId,
            'icon' => 'nullable|string',
            'color' => 'required|string',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Bitte gib einen Namen ein.',
            'slug.required' => 'Die URL-Adresse ist erforderlich.',
            'slug.unique' => 'Diese URL-Adresse wird bereits verwendet.',
        ]);

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'color' => $this->color,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            Subject::find($this->editingId)->update($data);
            $this->success('Fach erfolgreich aktualisiert! 🎉');
        } else {
            Subject::create($data);
            $this->success('Fach erfolgreich erstellt! 🎉');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function deleteSubject($id): void
    {
        $subject = Subject::findOrFail($id);

        if ($subject->courses()->count() > 0) {
            $this->error('Dieses Fach kann nicht gelöscht werden, da es mit ' . $subject->courses()->count() . ' Kursen verbunden ist.');
            return;
        }

        $subject->delete();
        $this->success('Fach gelöscht! 🗑️');
    }

    public function toggleActive($id): void
    {
        $subject = Subject::findOrFail($id);
        $subject->update(['is_active' => !$subject->is_active]);
        $this->success($subject->is_active ? 'Fach aktiviert' : 'Fach deaktiviert');
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->slug = '';
        $this->icon = '';
        $this->color = 'orange';
        $this->description = '';
        $this->is_active = true;
    }

    public function getColorClass($color): string
    {
        return match($color) {
            'orange' => 'bg-orange-100 text-orange-700',
            'blue' => 'bg-blue-100 text-blue-700',
            'green' => 'bg-green-100 text-green-700',
            'red' => 'bg-red-100 text-red-700',
            'yellow' => 'bg-yellow-100 text-yellow-700',
            'purple' => 'bg-purple-100 text-purple-700',
            'pink' => 'bg-pink-100 text-pink-700',
            'indigo' => 'bg-indigo-100 text-indigo-700',
            'teal' => 'bg-teal-100 text-teal-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function getIconClass($color): string
    {
        return match($color) {
            'orange' => 'text-orange-600',
            'blue' => 'text-blue-600',
            'green' => 'text-green-600',
            'red' => 'text-red-600',
            'yellow' => 'text-yellow-600',
            'purple' => 'text-purple-600',
            'pink' => 'text-pink-600',
            'indigo' => 'text-indigo-600',
            'teal' => 'text-teal-600',
            default => 'text-gray-600',
        };
    }
}
?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        <!-- Header -->
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">🏷️ {{ __('Fächer verwalten') }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">{{ __('Verwalte die Kursfächer und Kategorien') }}</p>
            </div>
            <div>
                <x-button wire:click="openModal" icon="o-plus" class="btn-primary">
                    {{ __('Neues Fach') }}
                </x-button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-4">
            <div class="bg-white rounded-lg p-3 text-center shadow-sm border-l-4 border-l-[#FF6B35]">
                <p class="text-xs text-gray-500">{{ __('Gesamt') }}</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->totalSubjects }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-green-500">
                <p class="text-xs text-gray-500">{{ __('Aktiv') }}</p>
                <p class="text-xl font-bold text-green-600">{{ $this->activeCount }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-yellow-500">
                <p class="text-xs text-gray-500">{{ __('Inaktiv') }}</p>
                <p class="text-xl font-bold text-yellow-600">{{ $this->inactiveCount }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-purple-500">
                <p class="text-xs text-gray-500">{{ __('Kurse') }}</p>
                <p class="text-xl font-bold text-purple-600">{{ $this->totalCourses }}</p>
            </div>
        </div>

        <!-- Search -->
        <div class="p-4 mb-5 bg-white shadow-sm rounded-xl">
            <div class="relative">
                <x-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Fächer durchsuchen...') }}"
                    icon="o-magnifying-glass"
                    class="w-full" />
                @if($search)
                    <button wire:click="clearSearch" class="absolute -translate-y-1/2 right-3 top-1/2">
                        <x-icon name="o-x-mark" class="w-4 h-4 text-gray-400 hover:text-gray-600" />
                    </button>
                @endif
            </div>
        </div>

        <!-- Subjects List -->
        @if($this->subjects->count() > 0)
            <!-- Version Desktop -->
            <div class="hidden overflow-hidden bg-white shadow-sm md:block rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-gray-50">
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Fach') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Slug') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">{{ __('Kurse') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">{{ __('Aktionen') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->subjects as $subject)
                            <tr class="transition border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $this->getColorClass($subject->color) }}">
                                            @if($subject->icon)
                                                {{-- <x-icon :name="$subject->icon" class="w-4 h-4 {{ $this->getIconClass($subject->color) }}" /> --}}
                                            @else
                                                <x-icon name="o-tag" class="w-4 h-4 {{ $this->getIconClass($subject->color) }}" />
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $subject->name }}</p>
                                            @if($subject->description)
                                                <p class="text-xs text-gray-400">{{ Str::limit($subject->description, 40) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $subject->slug }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">
                                        <x-icon name="o-academic-cap" class="w-3 h-3" />
                                        {{ $subject->courses_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 text-xs rounded-full {{ $subject->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $subject->is_active ? 'Aktiv' : 'Inaktiv' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="openModal({{ $subject->id }})" class="p-1.5 text-gray-400 hover:text-orange-600 rounded-lg transition" title="Bearbeiten">
                                            <x-icon name="o-pencil" class="w-4 h-4" />
                                        </button>
                                        <button wire:click="toggleActive({{ $subject->id }})" class="p-1.5 text-gray-400 hover:text-yellow-600 rounded-lg transition" title="{{ $subject->is_active ? 'Deaktivieren' : 'Aktivieren' }}">
                                            <x-icon :name="$subject->is_active ? 'o-eye-slash' : 'o-eye'" class="w-4 h-4" />
                                        </button>
                                        <button wire:click="deleteSubject({{ $subject->id }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg transition" title="Löschen" wire:confirm="Dieses Fach wirklich löschen?">
                                            <x-icon name="o-trash" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t bg-gray-50">
                    {{ $this->subjects->links() }}
                </div>
            </div>

            <!-- Version Mobile -->
            <div class="space-y-3 md:hidden">
                @foreach($this->subjects as $subject)
                <div class="p-4 bg-white shadow-sm rounded-xl">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $this->getColorClass($subject->color) }}">
                                @if($subject->icon)
                                    {{-- <x-icon :name="$subject->icon" class="w-5 h-5 {{ $this->getIconClass($subject->color) }}" /> --}}
                                @else
                                    <x-icon name="o-tag" class="w-5 h-5 {{ $this->getIconClass($subject->color) }}" />
                                @endif
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ $subject->name }}</p>
                                <p class="text-xs text-gray-400">{{ $subject->slug }}</p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $subject->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                            {{ $subject->is_active ? 'Aktiv' : 'Inaktiv' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between pt-2 mt-2 border-t border-gray-100">
                        <div class="flex items-center gap-1">
                            <x-icon name="o-academic-cap" class="w-4 h-4 text-gray-400" />
                            <span class="text-sm text-gray-600">{{ $subject->courses_count }} Kurse</span>
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="openModal({{ $subject->id }})" class="p-1.5 text-gray-400 hover:text-orange-600 rounded-lg transition">
                                <x-icon name="o-pencil" class="w-4 h-4" />
                            </button>
                            <button wire:click="deleteSubject({{ $subject->id }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg transition">
                                <x-icon name="o-trash" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach

                <div class="mt-4">
                    {{ $this->subjects->links() }}
                </div>
            </div>
        @else
            <div class="p-12 text-center bg-white shadow-sm rounded-xl">
                <x-icon name="o-tag" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('Keine Fächer gefunden') }}</h3>
                <p class="mb-4 text-gray-500">{{ __('Erstelle dein erstes Fach.') }}</p>
                <x-button wire:click="openModal" class="btn-primary">
                    {{ __('Erstes Fach erstellen') }}
                </x-button>
            </div>
        @endif

        <!-- Subject Modal -->
        @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="$set('showModal', false)">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="sticky top-0 p-4 bg-white border-b">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ $editingId ? __('Fach bearbeiten') : __('Neues Fach') }}
                        </h3>
                        <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                            <x-icon name="o-x-mark" class="w-6 h-6" />
                        </button>
                    </div>
                </div>

                <div class="p-5 space-y-4">
                    <x-input
                        wire:model="name"
                        label="{{ __('Fachname') }} *"
                        placeholder="{{ __('z.B. Deutsch, Mathematik, ...') }}"
                        icon="o-tag"
                        required />

                    <x-input
                        wire:model="slug"
                        label="{{ __('URL-Adresse (Slug)') }} *"
                        placeholder="{{ __('deutsch') }}"
                        icon="o-link"
                        hint="{{ __('Wird automatisch aus dem Namen generiert') }}"
                        required />

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Symbol') }}</label>
                        <select wire:model="icon" class="w-full px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                            <option value="">{{ __('Kein Symbol') }}</option>
                            @foreach($icons as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-select
                        wire:model="color"
                        label="{{ __('Farbe') }}"
                        :options="$colors"
                        required />

                    <x-textarea
                        wire:model="description"
                        label="{{ __('Beschreibung') }}"
                        placeholder="{{ __('Kurze Beschreibung des Fachs') }}"
                        rows="2"
                        icon="o-document-text" />

                    <x-toggle
                        wire:model="is_active"
                        label="{{ __('Aktiv') }}"
                        hint="{{ __('Inaktive Fächer werden nicht angezeigt') }}" />
                </div>

                <div class="flex justify-end gap-3 p-5 border-t bg-gray-50">
                    <x-button wire:click="$set('showModal', false)" class="btn-ghost">
                        {{ __('Abbrechen') }}
                    </x-button>
                    <x-button wire:click="save" class="btn-primary" spinner="save">
                        {{ $editingId ? __('Speichern') : __('Erstellen') }}
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
                    <p class="text-sm font-medium text-blue-800">MVP Version</p>
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('Prochaines fonctionnalités : réorganisation des fächer, import/export, et statistiques par fächer.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
