<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Enrollment;
use App\Models\Course;
use Mary\Traits\Toast;

new
#[Title('Manage Enrollments - Admin')]
#[Layout('layouts.app')]
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

    // Getters
    public function getCoursesProperty()
    {
        return Course::select('id', 'title')->orderBy('title')->get();
    }

    public function getEnrollmentsProperty()
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

    public function getTotalEnrollmentsProperty()
    {
        return Enrollment::count();
    }

    public function getActiveCountProperty()
    {
        return Enrollment::where('status', 'active')->count();
    }

    public function getCompletedCountProperty()
    {
        return Enrollment::where('status', 'completed')->count();
    }

    public function getDroppedCountProperty()
    {
        return Enrollment::where('status', 'dropped')->count();
    }

    public function updateStatus($enrollmentId, $status): void
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        $enrollment->update(['status' => $status]);

        $statusLabels = [
            'active'    => __('activated'),
            'completed' => __('marked as completed'),
            'dropped'   => __('dropped'),
        ];

        $this->success(__('Enrollment has been :status.', ['status' => $statusLabels[$status]]));
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
            $this->success(__('Enrollment of ":user" for ":course" has been deleted.', ['user' => $userName, 'course' => $courseTitle]));
            $this->showDeleteModal = false;
            $this->enrollmentToDelete = null;
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'courseFilter']);
        $this->resetPage();
        $this->success(__('Filters reset.'));
    }

    public function getStatusBadgeClass($status): string
    {
        return match($status) {
            'active'    => 'badge-success',
            'completed' => 'badge-info',
            'dropped'   => 'badge-error',
            default     => 'badge-ghost',
        };
    }

    public function getStatusLabel($status): string
    {
        return match($status) {
            'active'    => __('Active'),
            'completed' => __('Completed'),
            'dropped'   => __('Dropped'),
            default     => $status,
        };
    }

    public function getProgressColor($progress): string
    {
        if ($progress >= 80) return 'bg-success';
        if ($progress >= 50) return 'bg-primary';
        if ($progress >= 20) return 'bg-warning';
        return 'bg-gray-400';
    }

    public function render()
    {
        return $this->view([
            'courses' => $this->courses,
            'enrollments' => $this->enrollments,
            'totalEnrollments' => $this->totalEnrollments,
            'activeCount' => $this->activeCount,
            'completedCount' => $this->completedCount,
            'droppedCount' => $this->droppedCount,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">📋 {{ __('Manage Enrollments') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('Manage all course enrollments') }}</p>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-4">
            <x-stat title="{{ __('Total') }}" :value="$totalEnrollments" icon="o-users" class="text-primary" />
            <x-stat title="{{ __('Active') }}" :value="$activeCount" icon="o-check-circle" class="text-success" />
            <x-stat title="{{ __('Completed') }}" :value="$completedCount" icon="o-trophy" class="text-info" />
            <x-stat title="{{ __('Dropped') }}" :value="$droppedCount" icon="o-exclamation-triangle" class="text-error" />
        </div>

        {{-- Filters --}}
        <div class="p-4 mb-5 shadow-sm bg-base-100 rounded-xl">
            <div class="flex flex-col gap-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <x-input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search student or course...') }}" icon="o-magnifying-glass" class="w-full" clearable />
                    <x-select wire:model.live="statusFilter" :options="[
                        ['id' => '', 'name' => __('All statuses')],
                        ['id' => 'active', 'name' => __('Active')],
                        ['id' => 'completed', 'name' => __('Completed')],
                        ['id' => 'dropped', 'name' => __('Dropped')],
                    ]" option-value="id" option-label="name" id="status_filter" name="status_filter" />
                    <x-select wire:model.live="courseFilter" :options="collect($courses)->prepend(['id' => '', 'title' => __('All courses')])->toArray()" option-value="id" option-label="title" id="course_filter" name="course_filter" />
                </div>
                @if($search || $statusFilter || $courseFilter)
                    <div class="flex justify-end">
                        <x-button wire:click="clearFilters" label="{{ __('Reset filters') }}" icon="o-x-mark" class="btn-ghost btn-sm" />
                    </div>
                @endif
            </div>
        </div>

        {{-- Enrollments List --}}
        @if($enrollments->count() > 0)
            {{-- Desktop table --}}
            <div class="hidden overflow-hidden shadow-sm md:block bg-base-100 rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-base-200">
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Student') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Course') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Enrolled on') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Progress') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enrollments as $enrollment)
                                <tr class="transition border-b hover:bg-base-200">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="flex items-center justify-center w-8 h-8 text-xs font-bold text-white rounded-full bg-gradient-to-r from-primary to-secondary">
                                                {{ strtoupper(substr($enrollment->user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium">{{ $enrollment->user->name }}</p>
                                                <p class="text-xs text-base-content/60">{{ $enrollment->user->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm">{{ Str::limit($enrollment->course->title, 35) }}</p>
                                        <p class="text-xs text-base-content/60">ID: #{{ $enrollment->course_id }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm">{{ $enrollment->enrolled_at->format('d.m.Y') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold {{ $enrollment->progress >= 80 ? 'text-success' : ($enrollment->progress >= 50 ? 'text-primary' : 'text-base-content/60') }}">
                                                {{ round($enrollment->progress) }}%
                                            </span>
                                            <div class="w-16 h-1.5 bg-base-200 rounded-full">
                                                <div class="h-1.5 rounded-full {{ $this->getProgressColor($enrollment->progress) }}" style="width: {{ $enrollment->progress }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-select wire:change="updateStatus({{ $enrollment->id }}, $event.target.value)"
                                                  :options="[
                                                      ['id' => 'active', 'name' => __('Active')],
                                                      ['id' => 'completed', 'name' => __('Completed')],
                                                      ['id' => 'dropped', 'name' => __('Dropped')],
                                                  ]" option-value="id" option-label="name"
                                                  :value="$enrollment->status"
                                                  class="select-sm" />
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" tooltip-left="{{ __('Delete') }}" wire:click="deleteEnrollment({{ $enrollment->id }})" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t bg-base-200">
                    {{ $enrollments->links() }}
                </div>
            </div>

            {{-- Mobile cards --}}
            <div class="space-y-3 md:hidden">
                @foreach($enrollments as $enrollment)
                    <x-card class="shadow-sm">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 text-sm font-bold text-white rounded-full bg-gradient-to-r from-primary to-secondary">
                                    {{ strtoupper(substr($enrollment->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">{{ $enrollment->user->name }}</p>
                                    <p class="text-xs text-base-content/60">{{ $enrollment->user->email }}</p>
                                </div>
                            </div>
                            <x-select wire:change="updateStatus({{ $enrollment->id }}, $event.target.value)"
                                      :options="[
                                          ['id' => 'active', 'name' => __('Active')],
                                          ['id' => 'completed', 'name' => __('Completed')],
                                          ['id' => 'dropped', 'name' => __('Dropped')],
                                      ]" option-value="id" option-label="name"
                                      :value="$enrollment->status"
                                      class="select-xs" />
                        </div>
                        <div class="mb-3">
                            <p class="text-sm font-medium">{{ Str::limit($enrollment->course->title, 40) }}</p>
                            <p class="text-xs text-base-content/60">ID: #{{ $enrollment->course_id }}</p>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-base-content/60">{{ __('Enrolled') }}: {{ $enrollment->enrolled_at->format('d.m.Y') }}</span>
                        </div>
                        <div class="mb-3">
                            <div class="flex justify-between mb-1 text-xs">
                                <span class="text-base-content/60">{{ __('Progress') }}</span>
                                <span class="font-semibold">{{ round($enrollment->progress) }}%</span>
                            </div>
                            <div class="w-full h-1.5 bg-base-200 rounded-full">
                                <div class="h-1.5 rounded-full {{ $this->getProgressColor($enrollment->progress) }}" style="width: {{ $enrollment->progress }}%"></div>
                            </div>
                        </div>
                        <div class="flex justify-end pt-2 border-t">
                            <x-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="deleteEnrollment({{ $enrollment->id }})" label="{{ __('Delete') }}" />
                        </div>
                    </x-card>
                @endforeach
                <div class="mt-4">{{ $enrollments->links() }}</div>
            </div>
        @else
            <x-card class="py-12 text-center">
                <x-icon name="o-clipboard-document-list" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                <h3 class="mb-2 text-lg font-semibold">{{ __('No enrollments found') }}</h3>
                <p class="mb-4 text-base-content/60">{{ __('Try different search criteria.') }}</p>
                <x-button wire:click="clearFilters" label="{{ __('Reset filters') }}" class="btn-outline" />
            </x-card>
        @endif

        {{-- Delete Modal --}}
        <x-modal wire:model="showDeleteModal" title="{{ __('Delete enrollment') }}" separator>
            <p>{{ __('Are you sure you want to delete the enrollment of ":user" for ":course"?', ['user' => $enrollmentToDelete?->user->name, 'course' => $enrollmentToDelete?->course->title]) }}</p>
            <x-slot:actions>
                <x-button label="{{ __('Cancel') }}" wire:click="$set('showDeleteModal', false)" class="btn-ghost" />
                <x-button label="{{ __('Delete') }}" class="btn-error" wire:click="confirmDelete" spinner />
            </x-slot:actions>
        </x-modal>
    </div>
</div>
