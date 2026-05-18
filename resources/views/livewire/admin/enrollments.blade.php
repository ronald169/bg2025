<?php

use App\Models\Enrollment;
use App\Models\Course;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Einschreibungen verwalten - Admin')]
#[Layout('components.layouts.dashboard-admin')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'status', history: true)]
    public string $statusFilter = '';

    #[Url(as: 'course', history: true)]
    public string $courseFilter = '';

    public bool $showDeleteModal = false;
    public $enrollmentToDelete = null;

    #[Computed]
    public function courses()
    {
        return Course::select('id', 'title')->orderBy('title')->get();
    }

    #[Computed]
    public function enrollments()
    {
        return Enrollment::with(['user', 'course'])
            ->when($this->search, function($query) {
                $query->whereHas('user', function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                })->orWhereHas('course', function($q) {
                    $q->where('title', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->courseFilter, fn($q) => $q->where('course_id', $this->courseFilter))
            ->latest('enrolled_at')
            ->paginate(15);
    }

    #[Computed]
    public function totalEnrollments()
    {
        return Enrollment::count();
    }

    #[Computed]
    public function activeCount()
    {
        return Enrollment::where('status', 'active')->count();
    }

    #[Computed]
    public function completedCount()
    {
        return Enrollment::where('status', 'completed')->count();
    }

    #[Computed]
    public function droppedCount()
    {
        return Enrollment::where('status', 'dropped')->count();
    }

    public function updateStatus($enrollmentId, $status): void
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        $enrollment->update(['status' => $status]);

        $statusLabels = [
            'active' => 'aktiviert',
            'completed' => 'als abgeschlossen markiert',
            'dropped' => 'abgebrochen',
        ];

        $this->success("Einschreibung wurde {$statusLabels[$status]}.");
    }

    public function deleteEnrollment($enrollmentId): void
    {
        $this->enrollmentToDelete = Enrollment::findOrFail($enrollmentId);
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        if ($this->enrollmentToDelete) {
            $userName = $this->enrollmentToDelete->user->name;
            $courseTitle = $this->enrollmentToDelete->course->title;
            $this->enrollmentToDelete->delete();
            $this->success("Einschreibung von '{$userName}' für '{$courseTitle}' wurde gelöscht.");
            $this->showDeleteModal = false;
            $this->enrollmentToDelete = null;
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'courseFilter']);
        $this->resetPage();
        $this->success('Filter zurückgesetzt.');
    }

    public function getStatusBadgeClass($status): string
    {
        return match($status) {
            'active' => 'bg-green-100 text-green-700',
            'completed' => 'bg-blue-100 text-blue-700',
            'dropped' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function getStatusLabel($status): string
    {
        return match($status) {
            'active' => 'Aktiv',
            'completed' => 'Abgeschlossen',
            'dropped' => 'Abgebrochen',
            default => $status,
        };
    }

    public function getProgressColor($progress): string
    {
        if ($progress >= 80) return 'bg-green-500';
        if ($progress >= 50) return 'bg-blue-500';
        if ($progress >= 20) return 'bg-yellow-500';
        return 'bg-gray-400';
    }
}
?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        <!-- Header -->
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">📋 {{ __('Einschreibungen verwalten') }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">{{ __('Verwalte alle Kurseinschreibungen') }}</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-4">
            <div class="bg-white rounded-lg p-3 text-center shadow-sm border-l-4 border-l-[#FF6B35]">
                <p class="text-xs text-gray-500">{{ __('Gesamt') }}</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->totalEnrollments }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-green-500">
                <p class="text-xs text-gray-500">{{ __('Aktiv') }}</p>
                <p class="text-xl font-bold text-green-600">{{ $this->activeCount }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-blue-500">
                <p class="text-xs text-gray-500">{{ __('Abgeschlossen') }}</p>
                <p class="text-xl font-bold text-blue-600">{{ $this->completedCount }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-red-500">
                <p class="text-xs text-gray-500">{{ __('Abgebrochen') }}</p>
                <p class="text-xl font-bold text-red-600">{{ $this->droppedCount }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="p-4 mb-5 bg-white shadow-sm rounded-xl">
            <div class="flex flex-col gap-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="relative">
                        <x-input
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Student oder Kurs suchen...') }}"
                            icon="o-magnifying-glass"
                            class="w-full" />
                    </div>

                    <select wire:model.live="statusFilter" class="px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                        <option value="">{{ __('Alle Status') }}</option>
                        <option value="active">{{ __('Aktiv') }}</option>
                        <option value="completed">{{ __('Abgeschlossen') }}</option>
                        <option value="dropped">{{ __('Abgebrochen') }}</option>
                    </select>

                    <select wire:model.live="courseFilter" class="px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                        <option value="">{{ __('Alle Kurse') }}</option>
                        @foreach($this->courses as $course)
                            <option value="{{ $course->id }}">{{ Str::limit($course->title, 40) }}</option>
                        @endforeach
                    </select>
                </div>

                @if($search || $statusFilter || $courseFilter)
                    <div class="flex justify-end">
                        <button wire:click="clearFilters" class="text-sm text-[#FF6B35] hover:underline">
                            {{ __('Filter zurücksetzen') }} →
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Enrollments List -->
        @if($this->enrollments->count() > 0)
            <!-- Version Desktop -->
            <div class="hidden overflow-hidden bg-white shadow-sm md:block rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-gray-50">
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Student') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Kurs') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Eingeschrieben am') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Fortschritt') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">{{ __('Aktionen') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->enrollments as $enrollment)
                            <tr class="transition border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-xs">
                                            {{ strtoupper(substr($enrollment->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $enrollment->user->name }}</p>
                                            <p class="text-xs text-gray-400">{{ $enrollment->user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-900">{{ Str::limit($enrollment->course->title, 35) }}</p>
                                    <p class="text-xs text-gray-400">ID: #{{ $enrollment->course_id }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $enrollment->enrolled_at->format('d.m.Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold {{ $enrollment->progress >= 80 ? 'text-green-600' : ($enrollment->progress >= 50 ? 'text-blue-600' : 'text-gray-600') }}">
                                            {{ $enrollment->progress }}%
                                        </span>
                                        <div class="w-16 h-1.5 bg-gray-200 rounded-full">
                                            <div class="h-1.5 rounded-full {{ $this->getProgressColor($enrollment->progress) }}"
                                                 style="width: {{ $enrollment->progress }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <select wire:change="updateStatus({{ $enrollment->id }}, $event.target.value)"
                                            class="px-2 py-1 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35] {{ $this->getStatusBadgeClass($enrollment->status) }}">
                                        <option value="active" {{ $enrollment->status === 'active' ? 'selected' : '' }}>{{ __('Aktiv') }}</option>
                                        <option value="completed" {{ $enrollment->status === 'completed' ? 'selected' : '' }}>{{ __('Abgeschlossen') }}</option>
                                        <option value="dropped" {{ $enrollment->status === 'dropped' ? 'selected' : '' }}>{{ __('Abgebrochen') }}</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center">
                                        <button wire:click="deleteEnrollment({{ $enrollment->id }})"
                                                class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg transition"
                                                title="Löschen">
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
                    {{ $this->enrollments->links() }}
                </div>
            </div>

            <!-- Version Mobile -->
            <div class="space-y-3 md:hidden">
                @foreach($this->enrollments as $enrollment)
                <div class="p-4 bg-white shadow-sm rounded-xl">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-sm">
                                {{ strtoupper(substr($enrollment->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $enrollment->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $enrollment->user->email }}</p>
                            </div>
                        </div>
                        <select wire:change="updateStatus({{ $enrollment->id }}, $event.target.value)"
                                class="px-2 py-1 text-xs border rounded-lg {{ $this->getStatusBadgeClass($enrollment->status) }}">
                            <option value="active" {{ $enrollment->status === 'active' ? 'selected' : '' }}>{{ __('Aktiv') }}</option>
                            <option value="completed" {{ $enrollment->status === 'completed' ? 'selected' : '' }}>{{ __('Abgeschlossen') }}</option>
                            <option value="dropped" {{ $enrollment->status === 'dropped' ? 'selected' : '' }}>{{ __('Abgebrochen') }}</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <p class="text-sm font-medium text-gray-900">{{ Str::limit($enrollment->course->title, 40) }}</p>
                        <p class="text-xs text-gray-400">ID: #{{ $enrollment->course_id }}</p>
                    </div>

                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-gray-500">{{ __('Eingeschrieben') }}: {{ $enrollment->enrolled_at->format('d.m.Y') }}</span>
                    </div>

                    <div class="mb-3">
                        <div class="flex justify-between mb-1 text-xs">
                            <span class="text-gray-500">{{ __('Fortschritt') }}</span>
                            <span class="font-semibold">{{ $enrollment->progress }}%</span>
                        </div>
                        <div class="w-full h-1.5 bg-gray-200 rounded-full">
                            <div class="h-1.5 rounded-full {{ $this->getProgressColor($enrollment->progress) }}"
                                 style="width: {{ $enrollment->progress }}%"></div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2 border-t border-gray-100">
                        <button wire:click="deleteEnrollment({{ $enrollment->id }})"
                                class="p-2 text-gray-400 transition rounded-lg hover:text-red-600">
                            <x-icon name="o-trash" class="w-4 h-4" />
                            {{ __('Löschen') }}
                        </button>
                    </div>
                </div>
                @endforeach

                <div class="mt-4">
                    {{ $this->enrollments->links() }}
                </div>
            </div>
        @else
            <div class="p-12 text-center bg-white shadow-sm rounded-xl">
                <x-icon name="o-clipboard-document-list" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('Keine Einschreibungen gefunden') }}</h3>
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
                    <h3 class="mb-2 text-xl font-bold text-gray-900">{{ __('Einschreibung löschen') }}</h3>
                    <p class="mb-6 text-gray-600">
                        {{ __('Bist du sicher, dass du die Einschreibung von :student für :course löschen möchtest?', [
                            'student' => $enrollmentToDelete?->user->name,
                            'course' => $enrollmentToDelete?->course->title
                        ]) }}
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
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('Prochaines fonctionnalités : export des données, filtres avancés, analyses des inscriptions et notifications automatiques.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
