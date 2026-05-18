<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Course;
use App\Models\User;
use Mary\Traits\Toast;

new
#[Title('My Students - Teacher')]
#[Layout('layouts.app')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'course', history: true)]
    public $selectedCourse = null;

    #[Url(as: 'sort', history: true)]
    public string $sortBy = 'name';

    // Getters
    public function getCoursesProperty()
    {
        return Course::where('teacher_id', auth()->id())
            ->withCount('enrollments')
            ->orderBy('title')
            ->get();
    }

    public function getTotalStudentsProperty()
    {
        $query = User::whereHas('enrollments.course', function($q) {
            $q->where('teacher_id', auth()->id());
            if ($this->selectedCourse) {
                $q->where('id', $this->selectedCourse);
            }
        });
        return $query->count();
    }

    public function getStudentsProperty()
    {
        $students = User::whereHas('enrollments.course', function($q) {
                $q->where('teacher_id', auth()->id());
                if ($this->selectedCourse) {
                    $q->where('id', $this->selectedCourse);
                }
            })
            ->with(['enrollments' => function($q) {
                $q->whereHas('course', fn($cq) => $cq->where('teacher_id', auth()->id()))->with('course');
            }])
            ->when($this->search, function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->when($this->sortBy === 'name', fn($q) => $q->orderBy('name', 'asc'))
            ->when($this->sortBy === 'newest', fn($q) => $q->orderBy('created_at', 'desc'))
            ->when($this->sortBy === 'oldest', fn($q) => $q->orderBy('created_at', 'asc'))
            ->paginate(15);

        foreach ($students as $student) {
            $student->avg_progress = $this->getStudentProgress($student);
            $student->course_count = $this->getCourseCount($student);
            $student->best_course = $this->getBestCourse($student);
            $student->last_activity = $this->getLastActivity($student);
        }

        return $students;
    }

    private function getStudentProgress($student): int
    {
        $enrollments = $student->enrollments->filter(fn($e) => $this->selectedCourse ? $e->course_id == $this->selectedCourse : true);
        if ($enrollments->isEmpty()) return 0;
        return round($enrollments->avg('progress'));
    }

    private function getCourseCount($student): int
    {
        return $student->enrollments->filter(fn($e) => $this->selectedCourse ? $e->course_id == $this->selectedCourse : true)->count();
    }

    private function getBestCourse($student): ?string
    {
        $best = $student->enrollments
            ->filter(fn($e) => $this->selectedCourse ? $e->course_id == $this->selectedCourse : true)
            ->sortByDesc('progress')
            ->first();
        return $best?->course->title;
    }

    private function getLastActivity($student): ?string
    {
        $last = $student->progress()
            ->whereHas('lesson.course', fn($q) => $q->where('teacher_id', auth()->id()))
            ->latest('updated_at')
            ->first();
        return $last?->updated_at?->diffForHumans();
    }

    public function viewStudent($userId): void
    {
        $this->redirectRoute('teacher.students.show', $userId);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'selectedCourse', 'sortBy']);
        $this->resetPage();
        $this->success(__('Filters reset.'));
    }

    public function getProgressColor($progress): string
    {
        if ($progress >= 80) return 'bg-success';
        if ($progress >= 50) return 'bg-primary';
        if ($progress >= 20) return 'bg-warning';
        return 'bg-gray-400';
    }

    public function getProgressTextColor($progress): string
    {
        if ($progress >= 80) return 'text-success';
        if ($progress >= 50) return 'text-primary';
        if ($progress >= 20) return 'text-warning';
        return 'text-gray-500';
    }

    public function render()
    {
        return $this->view([
            'courses'        => $this->courses,
            'totalStudents'  => $this->totalStudents,
            'students'       => $this->students,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">👨‍🎓 {{ __('My Students') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('Manage and track your students progress') }}</p>
            </div>
            <div class="px-3 py-1.5 rounded-lg bg-base-200 text-center">
                <span class="text-sm text-base-content/70">{{ __('Total') }}:</span>
                <span class="ml-2 text-xl font-bold text-primary">{{ $totalStudents }}</span>
            </div>
        </div>

        {{-- Filters --}}
        <div class="p-4 mb-5 shadow-sm bg-base-100 rounded-xl">
            <div class="space-y-3">
                <div>
                    <label class="block mb-1 text-sm font-medium">{{ __('Filter by course') }}</label>
                    <x-select wire:model.live="selectedCourse" :options="collect($courses)->prepend(['id' => '', 'title' => __('All courses') . ' (' . $courses->sum('enrollments_count') . ')'])->toArray()" option-value="id" option-label="title" id="course_filter" name="course_filter" />
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <x-input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name or email...') }}" icon="o-magnifying-glass" class="w-full" clearable />
                    <x-select wire:model.live="sortBy" :options="[
                        ['id' => 'name', 'name' => __('Name A-Z')],
                        ['id' => 'newest', 'name' => __('Newest first')],
                        ['id' => 'oldest', 'name' => __('Oldest first')],
                    ]" option-value="id" option-label="name" id="sort_by" name="sort_by" />
                </div>
                @if($search || $selectedCourse)
                    <div class="flex justify-end">
                        <x-button wire:click="clearFilters" label="{{ __('Reset filters') }}" icon="o-x-mark" class="btn-ghost btn-sm" />
                    </div>
                @endif
            </div>
        </div>

        {{-- Students List --}}
        @if($students->count() > 0)
            {{-- Desktop table --}}
            <div class="hidden overflow-hidden shadow-sm md:block bg-base-100 rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-base-200">
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Student') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Email') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Courses') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Avg progress') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Last activity') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                <tr class="transition border-b hover:bg-base-200">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="flex items-center justify-center w-8 h-8 text-xs font-bold text-white rounded-full bg-gradient-to-r from-primary to-secondary">
                                                {{ strtoupper(substr($student->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium">{{ $student->name }}</p>
                                                <p class="text-xs text-base-content/60">{{ $student->german_level ?? 'A1' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">{{ $student->email }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <x-badge :value="$student->course_count" icon="o-academic-cap" class="badge-info badge-soft" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold {{ $this->getProgressTextColor($student->avg_progress) }}">{{ $student->avg_progress }}%</span>
                                            <div class="w-16 h-1.5 bg-base-200 rounded-full">
                                                <div class="h-1.5 rounded-full {{ $this->getProgressColor($student->avg_progress) }}" style="width: {{ $student->avg_progress }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-base-content/60">{{ $student->last_activity ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <x-button icon="o-eye" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('View details') }}" wire:click="viewStudent({{ $student->id }})" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t bg-base-200">
                    {{ $students->links() }}
                </div>
            </div>

            {{-- Mobile cards --}}
            <div class="space-y-3 md:hidden">
                @foreach($students as $student)
                    <x-card class="shadow-sm">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-12 h-12 text-lg font-bold text-white rounded-full bg-gradient-to-r from-primary to-secondary">
                                    {{ strtoupper(substr($student->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold">{{ $student->name }}</p>
                                    <p class="text-xs text-base-content/60">{{ $student->email }}</p>
                                    <p class="text-xs text-base-content/50 mt-0.5">{{ $student->german_level ?? 'A1' }} - {{ __('German') }}</p>
                                </div>
                            </div>
                            <x-button label="{{ __('View') }}" class="btn-primary btn-sm" wire:click="viewStudent({{ $student->id }})" />
                        </div>
                        <div class="grid grid-cols-2 gap-3 pt-3 border-t">
                            <div class="text-center">
                                <p class="text-xs text-base-content/60">{{ __('Courses') }}</p>
                                <p class="text-lg font-semibold text-info">{{ $student->course_count }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-base-content/60">{{ __('Avg progress') }}</p>
                                <p class="text-lg font-semibold {{ $this->getProgressTextColor($student->avg_progress) }}">{{ $student->avg_progress }}%</p>
                                <div class="w-full h-1.5 bg-base-200 rounded-full mt-1">
                                    <div class="h-1.5 rounded-full {{ $this->getProgressColor($student->avg_progress) }}" style="width: {{ $student->avg_progress }}%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="pt-2 mt-2 border-t">
                            <p class="text-xs text-base-content/60">{{ __('Last activity') }}</p>
                            <p class="text-sm text-base-content/80">{{ $student->last_activity ?? '-' }}</p>
                        </div>
                    </x-card>
                @endforeach
                <div class="mt-4">{{ $students->links() }}</div>
            </div>
        @else
            <x-card class="py-12 text-center">
                <x-icon name="o-users" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                <h3 class="mb-2 text-lg font-semibold">{{ __('No students found') }}</h3>
                <p class="text-base-content/60">{{ __('No students match your search criteria.') }}</p>
                @if($search || $selectedCourse)
                    <x-button wire:click="clearFilters" label="{{ __('Reset filters') }} →" class="mt-4 btn-outline" />
                @endif
            </x-card>
        @endif
    </div>
</div>
