<?php

use App\Models\Course;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Kurse verwalten - Admin')]
#[Layout('components.layouts.dashboard-admin')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'status', history: true)]
    public string $statusFilter = '';

    public bool $showDeleteModal = false;
    public $courseToDelete = null;

    #[Computed]
    public function courses()
    {
        return Course::with(['teacher', 'subject'])
            ->withCount(['lessons', 'enrollments'])
            ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->when($this->statusFilter === 'published', fn($q) => $q->where('is_published', true))
            ->when($this->statusFilter === 'draft', fn($q) => $q->where('is_published', false))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function totalCourses()
    {
        return Course::count();
    }

    #[Computed]
    public function publishedCount()
    {
        return Course::where('is_published', true)->count();
    }

    #[Computed]
    public function draftCount()
    {
        return Course::where('is_published', false)->count();
    }

    #[Computed]
    public function featuredCount()
    {
        return Course::where('is_featured', true)->count();
    }

    public function togglePublish($courseId): void
    {
        $course = Course::findOrFail($courseId);
        $course->update(['is_published' => !$course->is_published]);
        $this->success($course->is_published ? 'Kurs veröffentlicht! 🚀' : 'Kurs als Entwurf gespeichert.');
    }

    public function toggleFeature($courseId): void
    {
        $course = Course::findOrFail($courseId);
        $course->update(['is_featured' => !$course->is_featured]);
        $this->success($course->is_featured ? 'Kurs als empfohlen markiert ⭐' : 'Kurs aus Empfehlungen entfernt');
    }

    public function deleteCourse($courseId): void
    {
        $this->courseToDelete = Course::findOrFail($courseId);
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        if ($this->courseToDelete) {
            $courseTitle = $this->courseToDelete->title;
            $this->courseToDelete->delete();
            $this->success("Kurs '{$courseTitle}' wurde gelöscht.");
            $this->showDeleteModal = false;
            $this->courseToDelete = null;
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
        $this->success('Filter zurückgesetzt.');
    }

    public function getLevelLabel($level): string
    {
        $levels = [
            'A1' => 'A1 - Débutant',
            'A2' => 'A2 - Élémentaire',
            'B1' => 'B1 - Intermédiaire',
            'B2' => 'B2 - Avancé',
            'C1' => 'C1 - Expérimenté',
            'C2' => 'C2 - Maîtrise'
        ];
        return $levels[$level] ?? $level ?? 'A1';
    }
}
?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        <!-- Header -->
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">📚 {{ __('Kurse verwalten') }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">{{ __('Verwalte alle Plattform-Kurse') }}</p>
            </div>
            <div>
                <a href="{{ route('admin.courses.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                    <x-icon name="o-plus" class="w-4 h-4" />
                    {{ __('Neuer Kurs') }}
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-4">
            <div class="bg-white rounded-lg p-3 text-center shadow-sm border-l-4 border-l-[#FF6B35]">
                <p class="text-xs text-gray-500">{{ __('Gesamt') }}</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->totalCourses }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-green-500">
                <p class="text-xs text-gray-500">{{ __('Veröffentlicht') }}</p>
                <p class="text-xl font-bold text-green-600">{{ $this->publishedCount }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-yellow-500">
                <p class="text-xs text-gray-500">{{ __('Entwürfe') }}</p>
                <p class="text-xl font-bold text-yellow-600">{{ $this->draftCount }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-purple-500">
                <p class="text-xs text-gray-500">{{ __('Empfohlen') }}</p>
                <p class="text-xl font-bold text-purple-600">{{ $this->featuredCount }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="p-4 mb-5 bg-white shadow-sm rounded-xl">
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="flex-1">
                    <x-input
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Kurse suchen...') }}"
                        icon="o-magnifying-glass"
                        class="w-full" />
                </div>
                <div class="w-full sm:w-48">
                    <select wire:model.live="statusFilter" class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                        <option value="">{{ __('Alle Status') }}</option>
                        <option value="published">{{ __('Veröffentlicht') }}</option>
                        <option value="draft">{{ __('Entwurf') }}</option>
                    </select>
                </div>
                @if($search || $statusFilter)
                    <button wire:click="clearFilters" class="px-3 py-2 text-sm text-[#FF6B35] hover:underline">
                        {{ __('Zurücksetzen') }}
                    </button>
                @endif
            </div>
        </div>

        <!-- Courses List -->
        @if($this->courses->count() > 0)
            <!-- Version Desktop -->
            <div class="hidden overflow-hidden bg-white shadow-sm md:block rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-gray-50">
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Kurs') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Lehrer') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Fach') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">{{ __('Lektionen') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">{{ __('Studenten') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">{{ __('Aktionen') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->courses as $course)
                            <tr class="transition border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ Str::limit($course->title, 35) }}</p>
                                        <p class="text-xs text-gray-400">{{ $this->getLevelLabel($course->level) }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <div class="flex items-center gap-1">
                                        <div class="flex items-center justify-center w-6 h-6 text-xs font-bold bg-gray-200 rounded-full">
                                            {{ strtoupper(substr($course->teacher->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <span>{{ $course->teacher->name ?? 'Unbekannt' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $course->subject->name ?? 'Allgemein' }}</td>
                                <td class="px-4 py-3 text-sm text-center">{{ $course->lessons_count }}</td>
                                <td class="px-4 py-3 text-sm text-center">{{ $course->enrollments_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span class="inline-flex px-2 py-0.5 text-xs rounded-full {{ $course->is_published ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                            {{ $course->is_published ? 'Veröffentlicht' : 'Entwurf' }}
                                        </span>
                                        @if($course->is_featured)
                                            <span class="inline-flex px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700">
                                                <x-icon name="o-star" class="w-3 h-3 mr-0.5" />
                                                Empfohlen
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('admin.courses.edit', $course) }}" class="p-1.5 text-gray-400 hover:text-orange-600 rounded-lg transition" title="Bearbeiten">
                                            <x-icon name="o-pencil" class="w-4 h-4" />
                                        </a>
                                        <button wire:click="togglePublish({{ $course->id }})" class="p-1.5 text-gray-400 hover:text-green-600 rounded-lg transition" title="{{ $course->is_published ? 'Unveröffentlichen' : 'Veröffentlichen' }}">
                                            <x-icon :name="$course->is_published ? 'o-eye-slash' : 'o-eye'" class="w-4 h-4" />
                                        </button>
                                        <button wire:click="toggleFeature({{ $course->id }})" class="p-1.5 {{ $course->is_featured ? 'text-yellow-500' : 'text-gray-400 hover:text-yellow-500' }} rounded-lg transition" title="{{ $course->is_featured ? 'Empfehlung entfernen' : 'Als empfohlen markieren' }}">
                                            <x-icon name="o-star" class="w-4 h-4" />
                                        </button>
                                        <button wire:click="deleteCourse({{ $course->id }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg transition" title="Löschen">
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
                    {{ $this->courses->links() }}
                </div>
            </div>

            <!-- Version Mobile -->
            <div class="space-y-3 md:hidden">
                @foreach($this->courses as $course)
                <div class="p-4 bg-white shadow-sm rounded-xl">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">{{ Str::limit($course->title, 30) }}</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $this->getLevelLabel($course->level) }}</p>
                        </div>
                        <div class="flex flex-wrap justify-end gap-1">
                            <span class="px-2 py-0.5 text-xs rounded-full {{ $course->is_published ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $course->is_published ? 'Veröffentlicht' : 'Entwurf' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 mb-2">
                        <div class="flex items-center justify-center w-6 h-6 text-xs font-bold bg-gray-200 rounded-full">
                            {{ strtoupper(substr($course->teacher->name ?? 'U', 0, 1)) }}
                        </div>
                        <span class="text-xs text-gray-600">{{ $course->teacher->name ?? 'Unbekannt' }}</span>
                        <span class="text-xs text-gray-400">•</span>
                        <span class="text-xs text-gray-500">{{ $course->subject->name ?? 'Allgemein' }}</span>
                    </div>

                    <div class="flex items-center justify-between mb-3 text-xs text-gray-500">
                        <span>📚 {{ $course->lessons_count }} Lektionen</span>
                        <span>👥 {{ $course->enrollments_count }} Studenten</span>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <a href="{{ route('admin.courses.edit', $course) }}" class="p-2 text-gray-400 transition rounded-lg hover:text-orange-600">
                            <x-icon name="o-pencil" class="w-4 h-4" />
                        </a>
                        <button wire:click="togglePublish({{ $course->id }})" class="p-2 text-gray-400 transition rounded-lg hover:text-green-600">
                            <x-icon :name="$course->is_published ? 'o-eye-slash' : 'o-eye'" class="w-4 h-4" />
                        </button>
                        <button wire:click="toggleFeature({{ $course->id }})" class="p-2 {{ $course->is_featured ? 'text-yellow-500' : 'text-gray-400 hover:text-yellow-500' }} rounded-lg transition">
                            <x-icon name="o-star" class="w-4 h-4" />
                        </button>
                        <button wire:click="deleteCourse({{ $course->id }})" class="p-2 text-gray-400 transition rounded-lg hover:text-red-600">
                            <x-icon name="o-trash" class="w-4 h-4" />
                        </button>
                    </div>
                </div>
                @endforeach

                <div class="mt-4">
                    {{ $this->courses->links() }}
                </div>
            </div>
        @else
            <div class="p-12 text-center bg-white shadow-sm rounded-xl">
                <x-icon name="o-academic-cap" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('Keine Kurse gefunden') }}</h3>
                <p class="mb-4 text-gray-500">{{ __('Versuche andere Suchkriterien.') }}</p>
                <button wire:click="clearFilters" class="text-[#FF6B35] hover:underline">
                    {{ __('Filter zurücksetzen') }} →
                </button>
            </div>
        @endif

        <!-- Delete Modal -->
        @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="$set('showDeleteModal', false)">
            <div class="w-full max-w-md overflow-hidden bg-white shadow-xl rounded-xl">
                <div class="p-6 text-center">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full">
                        <x-icon name="o-exclamation-triangle" class="w-8 h-8 text-red-600" />
                    </div>
                    <h3 class="mb-2 text-xl font-bold text-gray-900">{{ __('Kurs löschen') }}</h3>
                    <p class="mb-6 text-gray-600">
                        {{ __('Bist du sicher, dass du :course löschen möchtest? Diese Aktion kann nicht rückgängig gemacht werden.', ['course' => $courseToDelete?->title]) }}
                    </p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('showDeleteModal', false)" class="px-4 py-2 text-gray-600 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                            {{ __('Abbrechen') }}
                        </button>
                        <button wire:click="confirmDelete" class="px-4 py-2 text-white transition bg-red-600 rounded-lg hover:bg-red-700">
                            {{ __('Löschen') }}
                        </button>
                    </div>
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
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('Prochaines fonctionnalités : export des cours, duplication, filtres avancés et analyse des performances.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
