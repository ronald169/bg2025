<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Course;
use Mary\Traits\Toast;

new
#[Title('My Courses - Teacher')]
#[Layout('layouts.app')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'status', history: true)]
    public string $statusFilter = '';

    #[Url(as: 'sort', history: true)]
    public string $sortBy = 'latest';

    public bool $showDeleteModal = false;
    public $courseToDelete = null;

    // Getters
    public function getCoursesProperty()
    {
        return Course::where('teacher_id', auth()->id())
            ->with(['subject', 'teacher'])
            ->withCount(['lessons', 'enrollments', 'quizzes'])
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter === 'published', fn($q) => $q->where('is_published', true))
            ->when($this->statusFilter === 'draft', fn($q) => $q->where('is_published', false))
            ->when($this->sortBy === 'latest', fn($q) => $q->latest())
            ->when($this->sortBy === 'oldest', fn($q) => $q->oldest())
            ->when($this->sortBy === 'popular', fn($q) => $q->orderBy('enrollments_count', 'desc'))
            ->when($this->sortBy === 'title', fn($q) => $q->orderBy('title', 'asc'))
            ->paginate(12);
    }

    public function getTotalCoursesProperty()
    {
        return Course::where('teacher_id', auth()->id())->count();
    }

    public function getPublishedCountProperty()
    {
        return Course::where('teacher_id', auth()->id())->where('is_published', true)->count();
    }

    public function getDraftCountProperty()
    {
        return Course::where('teacher_id', auth()->id())->where('is_published', false)->count();
    }

    public function deleteCourse($courseId): void
    {
        $course = Course::findOrFail($courseId);
        if ($course->teacher_id !== auth()->id()) {
            $this->error(__('Unauthorized.'));
            return;
        }
        $this->courseToDelete = $course;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        if ($this->courseToDelete) {
            $courseTitle = $this->courseToDelete->title;
            $this->courseToDelete->delete();
            $this->success(__('Course ":course" has been deleted.', ['course' => $courseTitle]));
            $this->showDeleteModal = false;
            $this->courseToDelete = null;
            $this->resetPage();
        }
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->courseToDelete = null;
    }

    public function togglePublish($courseId): void
    {
        $course = Course::findOrFail($courseId);
        if ($course->teacher_id !== auth()->id()) {
            $this->error(__('Unauthorized.'));
            return;
        }
        $course->update(['is_published' => !$course->is_published]);
        $this->success($course->is_published ? __('Course published! 🚀') : __('Course saved as draft.'));
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'sortBy']);
        $this->resetPage();
        $this->success(__('Filters reset.'));
    }

    public function getLevelLabel($level): string
    {
        $levels = [
            'A1' => 'A1 - Beginner',
            'A2' => 'A2 - Elementary',
            'B1' => 'B1 - Intermediate',
            'B2' => 'B2 - Upper Intermediate',
            'C1' => 'C1 - Advanced',
            'C2' => 'C2 - Mastery',
        ];
        return $levels[$level] ?? $level;
    }

    public function render()
    {
        return $this->view([
            'courses' => $this->courses,
            'totalCourses' => $this->totalCourses,
            'publishedCount' => $this->publishedCount,
            'draftCount' => $this->draftCount,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">📚 {{ __('My Courses') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('Manage all your German courses') }}</p>
            </div>
            <x-button label="{{ __('New Course') }}" icon="o-plus" link="{{ route('teacher.courses.create') }}" class="btn-primary" />
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-3 gap-3 mb-5">
            <x-stat title="{{ __('Total') }}" :value="$totalCourses" icon="o-book-open" class="text-primary" />
            <x-stat title="{{ __('Published') }}" :value="$publishedCount" icon="o-check-circle" class="text-success" />
            <x-stat title="{{ __('Drafts') }}" :value="$draftCount" icon="o-pencil" class="text-warning" />
        </div>

        {{-- Filters --}}
        <div class="p-4 mb-5 shadow-sm bg-base-100 rounded-xl">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-2">
                    <x-button wire:click="$set('statusFilter', '')" :label="__('All') . ' (' . $totalCourses . ')'" :class="$statusFilter === '' ? 'btn-primary' : 'btn-ghost'" class="btn-sm" />
                    <x-button wire:click="$set('statusFilter', 'published')" :label="__('Published') . ' (' . $publishedCount . ')'" :class="$statusFilter === 'published' ? 'btn-primary' : 'btn-ghost'" class="btn-sm" />
                    <x-button wire:click="$set('statusFilter', 'draft')" :label="__('Drafts') . ' (' . $draftCount . ')'" :class="$statusFilter === 'draft' ? 'btn-primary' : 'btn-ghost'" class="btn-sm" />
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <x-select wire:model.live="sortBy" :options="[
                        ['id' => 'latest', 'name' => __('Latest first')],
                        ['id' => 'oldest', 'name' => __('Oldest first')],
                        ['id' => 'popular', 'name' => __('Most Popular')],
                        ['id' => 'title', 'name' => __('Title A-Z')],
                    ]" option-value="id" option-label="name" id="sort_by" name="sort_by" class="w-40" />
                    <x-input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search courses...') }}" icon="o-magnifying-glass" class="w-full sm:w-64" clearable />
                    @if($search || $statusFilter)
                        <x-button wire:click="clearFilters" label="{{ __('Reset') }}" icon="o-x-mark" class="btn-ghost btn-sm" />
                    @endif
                </div>
            </div>
        </div>

        {{-- Courses Grid --}}
        @if($courses->count() > 0)
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($courses as $course)
                    <x-card class="overflow-hidden transition-all duration-200 hover:shadow-lg">
                        {{-- Header with level icon --}}
                        <div class="relative flex items-center justify-center mb-4 -mx-6 -mt-6 h-28 bg-gradient-to-r from-primary/20 to-secondary/20">
                            <div class="text-center">
                                <span class="text-5xl">
                                    @if($course->level === 'A1' || $course->level === 'A2') 🌱
                                    @elseif($course->level === 'B1' || $course->level === 'B2') 📚
                                    @elseif($course->level === 'C1' || $course->level === 'C2') 🏆
                                    @else 🇩🇪
                                    @endif
                                </span>
                                <div class="mt-1 text-xs text-base-content/60">{{ $this->getLevelLabel($course->level) }}</div>
                            </div>
                            <div class="absolute top-3 right-3">
                                @if($course->is_published)
                                    <x-badge value="{{ __('Published') }}" class="badge-success badge-soft" />
                                @else
                                    <x-badge value="{{ __('Draft') }}" class="badge-warning badge-soft" />
                                @endif
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <x-badge :value="$course->subject->name ?? __('German')" class="badge-soft badge-neutral" />
                                <div class="flex items-center gap-1 text-sm text-base-content/60">
                                    <x-icon name="o-users" class="w-3 h-3" />
                                    <span class="text-xs">{{ $course->enrollments_count }}</span>
                                </div>
                            </div>
                            <h3 class="font-semibold line-clamp-1">{{ $course->title }}</h3>
                            <p class="text-sm text-base-content/70 line-clamp-2">{{ Str::limit($course->description, 80) }}</p>
                            <div class="flex items-center justify-between text-xs text-base-content/60">
                                <span class="flex items-center gap-1"><x-icon name="o-book-open" class="w-3 h-3" />{{ $course->lessons_count }} {{ __('Lessons') }}</span>
                                <span class="flex items-center gap-1"><x-icon name="o-document-text" class="w-3 h-3" />{{ $course->quizzes_count }} {{ __('Quizzes') }}</span>
                                <span class="flex items-center gap-1"><x-icon name="o-star" class="w-3 h-3 text-warning" />{{ number_format($course->average_rating ?? 0, 1) }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 pt-3 mt-3 border-t">
                            <div class="flex gap-1">
                                <x-button icon="{{ $course->is_published ? 'o-eye-slash' : 'o-eye' }}" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ $course->is_published ? __('Unpublish') : __('Publish') }}" wire:click="togglePublish({{ $course->id }})" />
                                <x-button icon="o-pencil" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Edit Course') }}" link="{{ route('teacher.courses.edit', $course) }}" />
                                <x-button icon="o-megaphone" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Announcements') }}" link="{{ route('teacher.announcements') }}?selectedCourse={{ $course->id }}" />
                                <x-button icon="o-chart-bar" class="btn-circle text-info btn-ghost btn-sm" tooltip-left="{{ __('Course Analytics') }}" link="{{ route('teacher.courses.analytics', $course) }}" />
                                <x-button icon="o-document-text" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Quiz Manager') }}" link="{{ route('teacher.quizzes.index', $course) }}" />
                                <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" tooltip-left="{{ __('Delete') }}" wire:click="deleteCourse({{ $course->id }})" />
                            </div>
                            <x-button label="{{ __('Manage') }}" link="{{ route('teacher.lessons.index', $course) }}" class="btn-primary btn-sm" />
                        </div>
                        <x:slot:actions>
                                <x-button label="{{ __('Preview') }}" class="btn-sm" link="{{ route('teacher.courses.preview', $course) }}" />
                        </x:slot:actions>
                    </x-card>
                @endforeach
            </div>
            <div class="mt-8">{{ $courses->links() }}</div>
        @else
            <x-card class="py-12 text-center">
                @if($search || $statusFilter)
                    <x-icon name="o-magnifying-glass" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <h3 class="mb-2 text-lg font-semibold">{{ __('No courses found') }}</h3>
                    <p class="mb-4 text-base-content/60">{{ __('Try different search terms or filters.') }}</p>
                    <x-button wire:click="clearFilters" label="{{ __('Reset filters') }}" class="btn-outline" />
                @else
                    <x-icon name="o-academic-cap" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <h3 class="mb-2 text-lg font-semibold">{{ __('No courses yet') }}</h3>
                    <p class="mb-4 text-base-content/60">{{ __('Create your first German course.') }}</p>
                    <x-button label="{{ __('Create First Course') }}" icon="o-plus" link="{{ route('teacher.courses.create') }}" class="btn-primary" />
                @endif
            </x-card>
        @endif

        {{-- Delete Modal --}}
        <x-modal wire:model="showDeleteModal" title="{{ __('Delete Course') }}" separator>
            <p>{{ __('Are you sure you want to delete ":course"? This action cannot be undone.', ['course' => $courseToDelete?->title]) }}</p>
            <x-slot:actions>
                <x-button label="{{ __('Cancel') }}" wire:click="cancelDelete" class="btn-ghost" />
                <x-button label="{{ __('Delete') }}" class="btn-error" wire:click="confirmDelete" spinner />
            </x-slot:actions>
        </x-modal>
    </div>
</div>
