<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Course;
use Mary\Traits\Toast;

new
#[Title('Manage Courses - Admin')]
#[Layout('layouts.app')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'status', history: true)]
    public string $statusFilter = '';

    public bool $showDeleteModal = false;
    public $courseToDelete = null;

    // Getters (remplacent #[Computed])
    public function getCoursesProperty()
    {
        return Course::with(['teacher', 'subject'])
            ->withCount(['lessons', 'enrollments'])
            ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->when($this->statusFilter === 'published', fn($q) => $q->where('is_published', true))
            ->when($this->statusFilter === 'draft', fn($q) => $q->where('is_published', false))
            ->latest()
            ->paginate(15);
    }

    public function getTotalCoursesProperty()
    {
        return Course::count();
    }

    public function getPublishedCountProperty()
    {
        return Course::where('is_published', true)->count();
    }

    public function getDraftCountProperty()
    {
        return Course::where('is_published', false)->count();
    }

    public function getFeaturedCountProperty()
    {
        return Course::where('is_featured', true)->count();
    }

    public function togglePublish($courseId): void
    {
        $course = Course::findOrFail($courseId);
        $course->update(['is_published' => !$course->is_published]);
        $this->success($course->is_published ? __('Course published! 🚀') : __('Course saved as draft.'));
    }

    public function toggleFeature($courseId): void
    {
        $course = Course::findOrFail($courseId);
        $course->update(['is_featured' => !$course->is_featured]);
        $this->success($course->is_featured ? __('Course marked as featured ⭐') : __('Course removed from featured'));
    }

    public function deleteCourse($courseId): void
    {
        $this->courseToDelete = Course::findOrFail($courseId);
        $this->showDeleteModal = true;
    }

    public function cloneCourse($courseId): void
    {
        $originalCourse = Course::with(['lessons', 'subject', 'teacher'])->findOrFail($courseId);

        // Créer une copie du cours
        $newCourse = $originalCourse->replicate();
        $newCourse->title = $originalCourse->title . ' (Copy)';
        $newCourse->slug = $originalCourse->slug . '-' . Str::random(5);
        $newCourse->is_published = false; // Toujours en brouillon par défaut
        $newCourse->is_featured = false;
        $newCourse->created_at = now();
        $newCourse->updated_at = now();
        $newCourse->save();

        // Optionnel : cloner aussi les leçons ?
        // if ($originalCourse->lessons->count()) {
        //     foreach ($originalCourse->lessons as $lesson) {
        //         $newLesson = $lesson->replicate();
        //         $newLesson->course_id = $newCourse->id;
        //         $newLesson->save();
        //     }
        // }

        $this->success(__('Course cloned successfully!'));
        $this->resetPage();
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

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
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
        return $levels[$level] ?? $level ?? 'A1';
    }

    public function render()
    {
        return $this->view([
            'courses' => $this->courses,
            'totalCourses' => $this->totalCourses,
            'publishedCount' => $this->publishedCount,
            'draftCount' => $this->draftCount,
            'featuredCount' => $this->featuredCount,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">📚 {{ __('Manage Courses') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('Manage all platform courses') }}</p>
            </div>
            <div>
                <x-button icon="o-plus" label="{{ __('New Course') }}" link="{{ route('admin.courses.create') }}" class="btn-primary" />
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-4">
            <x-stat title="{{ __('Total') }}" :value="$totalCourses" icon="o-book-open" class="text-primary" />
            <x-stat title="{{ __('Published') }}" :value="$publishedCount" icon="o-check-circle" class="text-success" />
            <x-stat title="{{ __('Drafts') }}" :value="$draftCount" icon="o-pencil" class="text-warning" />
            <x-stat title="{{ __('Featured') }}" :value="$featuredCount" icon="o-star" class="text-secondary" />
        </div>

        {{-- Filters --}}
        <div class="p-4 mb-5 shadow-sm bg-base-100 rounded-xl">
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="flex-1">
                    <x-input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search courses...') }}" icon="o-magnifying-glass" class="w-full" clearable />
                </div>
                <div class="w-full sm:w-48">
                    <x-select wire:model.live="statusFilter" :options="[
                        ['id' => '', 'name' => __('All statuses')],
                        ['id' => 'published', 'name' => __('Published')],
                        ['id' => 'draft', 'name' => __('Draft')],
                    ]" option-value="id" option-label="name" id="status_filter" name="status_filter" />
                </div>
                @if($search || $statusFilter)
                    <x-button wire:click="clearFilters" label="{{ __('Reset') }}" icon="o-x-mark" class="btn-ghost btn-sm" />
                @endif
            </div>
        </div>

        {{-- Courses List --}}
        @if($courses->count() > 0)
            {{-- Desktop table --}}
            <div class="hidden overflow-hidden shadow-sm md:block bg-base-100 rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-base-200">
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Course') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Teacher') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Subject') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Lessons') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Students') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($courses as $course)
                                <tr class="transition border-b hover:bg-base-200">
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-sm font-medium">{{ Str::limit($course->title, 35) }}</p>
                                            <p class="text-xs text-base-content/60">{{ $this->getLevelLabel($course->level) }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center gap-1">
                                            <div class="flex items-center justify-center w-6 h-6 text-xs font-bold rounded-full bg-primary/20 text-primary">
                                                {{ strtoupper(substr($course->teacher->name ?? '?', 0, 1)) }}
                                            </div>
                                            <span>{{ $course->teacher->name ?? __('Unknown') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">{{ $course->subject->name ?? __('General') }}</td>
                                    <td class="px-4 py-3 text-sm text-center">{{ $course->lessons_count }}</td>
                                    <td class="px-4 py-3 text-sm text-center">{{ $course->enrollments_count }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            @if($course->is_published)
                                                <x-badge value="{{ __('Published') }}" class="badge-success badge-soft" />
                                            @else
                                                <x-badge value="{{ __('Draft') }}" class="badge-warning badge-soft" />
                                            @endif
                                            @if($course->is_featured)
                                                <x-badge value="{{ __('Featured') }}" icon="o-star" class="badge-secondary badge-soft" />
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <x-button icon="o-pencil" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Edit') }}" link="{{ route('admin.courses.edit', $course) }}" />
                                            <x-button icon="o-document-duplicate" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Clone') }}" wire:click="cloneCourse({{ $course->id }})" />
                                            <x-button icon="{{ $course->is_published ? 'o-eye-slash' : 'o-eye' }}" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ $course->is_published ? __('Unpublish') : __('Publish') }}" wire:click="togglePublish({{ $course->id }})" />
                                            <x-button icon="o-star" class="btn-circle btn-ghost btn-sm {{ $course->is_featured ? 'text-warning' : '' }}" tooltip-left="{{ $course->is_featured ? __('Remove featured') : __('Mark featured') }}" wire:click="toggleFeature({{ $course->id }})" />
                                            <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" tooltip-left="{{ __('Delete') }}" wire:click="deleteCourse({{ $course->id }})" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t bg-base-200">
                    {{ $courses->links() }}
                </div>
            </div>

            {{-- Mobile cards --}}
            <div class="space-y-3 md:hidden">
                @foreach($courses as $course)
                    <x-card class="shadow-sm">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="text-sm font-semibold">{{ Str::limit($course->title, 30) }}</h3>
                                <p class="text-xs text-base-content/60 mt-0.5">{{ $this->getLevelLabel($course->level) }}</p>
                            </div>
                            <div class="flex flex-wrap justify-end gap-1">
                                @if($course->is_published)
                                    <x-badge value="{{ __('Published') }}" class="badge-success badge-soft" />
                                @else
                                    <x-badge value="{{ __('Draft') }}" class="badge-warning badge-soft" />
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mb-2">
                            <div class="flex items-center justify-center w-6 h-6 text-xs font-bold rounded-full bg-primary/20 text-primary">
                                {{ strtoupper(substr($course->teacher->name ?? '?', 0, 1)) }}
                            </div>
                            <span class="text-xs text-base-content/70">{{ $course->teacher->name ?? __('Unknown') }}</span>
                            <span class="text-xs text-base-content/50">•</span>
                            <span class="text-xs text-base-content/60">{{ $course->subject->name ?? __('General') }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-3 text-xs text-base-content/60">
                            <span>📚 {{ $course->lessons_count }} {{ __('lessons') }}</span>
                            <span>👥 {{ $course->enrollments_count }} {{ __('students') }}</span>
                        </div>
                        <div class="flex justify-end gap-2 pt-2 border-t">
                            <x-button icon="o-pencil" class="btn-ghost btn-sm" link="{{ route('admin.courses.edit', $course) }}" />
                            <x-button icon="o-document-duplicate" class="btn-ghost btn-sm" wire:click="cloneCourse({{ $course->id }})" />
                            <x-button icon="{{ $course->is_published ? 'o-eye-slash' : 'o-eye' }}" class="btn-ghost btn-sm" wire:click="togglePublish({{ $course->id }})" />
                            <x-button icon="o-star" class="btn-ghost btn-sm {{ $course->is_featured ? 'text-warning' : '' }}" wire:click="toggleFeature({{ $course->id }})" />
                            <x-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="deleteCourse({{ $course->id }})" />
                        </div>
                    </x-card>
                @endforeach
                <div class="mt-4">{{ $courses->links() }}</div>
            </div>
        @else
            <x-card class="py-12 text-center">
                <x-icon name="o-academic-cap" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                <h3 class="mb-2 text-lg font-semibold">{{ __('No courses found') }}</h3>
                <p class="mb-4 text-base-content/60">{{ __('Try different search criteria.') }}</p>
                <x-button wire:click="clearFilters" label="{{ __('Reset filters') }} →" class="btn-outline" />
            </x-card>
        @endif

        {{-- Delete Modal --}}
        <x-modal wire:model="showDeleteModal" title="{{ __('Delete course') }}" separator>
            <p>{{ __('Are you sure you want to delete ":course"? This action cannot be undone.', ['course' => $courseToDelete?->title]) }}</p>
            <x-slot:actions>
                <x-button label="{{ __('Cancel') }}" wire:click="$set('showDeleteModal', false)" />
                <x-button label="{{ __('Delete') }}" class="btn-error" wire:click="confirmDelete" spinner />
            </x-slot:actions>
        </x-modal>
    </div>
</div>
