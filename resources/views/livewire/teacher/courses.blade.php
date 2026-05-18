<?php
// app/Livewire/Teacher/Courses.php

namespace App\Livewire\Teacher;

use App\Models\Course;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('My Courses - Teacher')]
#[Layout('components.layouts.dashboard-teacher')]
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

    #[Computed]
    public function courses()
    {
        return Course::where('teacher_id', auth()->id())
            ->with(['subject', 'teacher'])
            ->withCount(['lessons', 'enrollments', 'quizzes']) // Ajout de quizzes_count
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter === 'published', function ($query) {
                $query->where('is_published', true);
            })
            ->when($this->statusFilter === 'draft', function ($query) {
                $query->where('is_published', false);
            })
            ->when($this->sortBy === 'latest', fn($q) => $q->latest())
            ->when($this->sortBy === 'oldest', fn($q) => $q->oldest())
            ->when($this->sortBy === 'popular', fn($q) => $q->orderBy('enrollments_count', 'desc'))
            ->when($this->sortBy === 'title', fn($q) => $q->orderBy('title', 'asc'))
            ->paginate(12);
    }

    #[Computed]
    public function totalCourses()
    {
        return Course::where('teacher_id', auth()->id())->count();
    }

    #[Computed]
    public function publishedCount()
    {
        return Course::where('teacher_id', auth()->id())
            ->where('is_published', true)
            ->count();
    }

    #[Computed]
    public function draftCount()
    {
        return Course::where('teacher_id', auth()->id())
            ->where('is_published', false)
            ->count();
    }

    public function deleteCourse($courseId): void
    {
        $course = Course::findOrFail($courseId);

        if ($course->teacher_id !== auth()->id()) {
            $this->error('Unauthorized.');
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
            $this->success("Course '{$courseTitle}' has been deleted.");
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
            $this->error('Unauthorized.');
            return;
        }

        $course->update(['is_published' => !$course->is_published]);
        $this->success($course->is_published ? 'Course published! 🚀' : 'Course saved as draft.');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'sortBy']);
        $this->resetPage();
        $this->success('Filters reset.');
    }

    public function getStatusBadgeClass($isPublished): string
    {
        return $isPublished 
            ? 'bg-green-100 text-green-700' 
            : 'bg-yellow-100 text-yellow-700';
    }

    public function getStatusText($isPublished): string
    {
        return $isPublished ? 'Published' : 'Draft';
    }

    public function getLevelLabel($level): string
    {
        $levels = [
            'A1' => 'A1 - Beginner',
            'A2' => 'A2 - Elementary',
            'B1' => 'B1 - Intermediate',
            'B2' => 'B2 - Upper Intermediate',
            'C1' => 'C1 - Advanced',
            'C2' => 'C2 - Mastery'
        ];
        return $levels[$level] ?? $level;
    }
}
?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">
        
        <!-- Header -->
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">📚 {{ __('My Courses') }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">{{ __('Manage all your German courses') }}</p>
            </div>
            <div>
                <a href="{{ route('teacher.courses.create') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                    <x-icon name="o-plus" class="w-4 h-4" />
                    {{ __('New Course') }}
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-3 gap-3 mb-5">
            <div class="bg-white rounded-lg p-3 text-center shadow-sm border-l-4 border-l-[#FF6B35]">
                <p class="text-xs text-gray-500">{{ __('Total') }}</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->totalCourses }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-green-500">
                <p class="text-xs text-gray-500">{{ __('Published') }}</p>
                <p class="text-xl font-bold text-green-600">{{ $this->publishedCount }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-yellow-500">
                <p class="text-xs text-gray-500">{{ __('Drafts') }}</p>
                <p class="text-xl font-bold text-yellow-600">{{ $this->draftCount }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="p-4 mb-5 bg-white shadow-sm rounded-xl">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-2">
                    <button 
                        wire:click="$set('statusFilter', '')"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all
                               {{ $statusFilter === '' ? 'bg-[#FF6B35] text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ __('All') }}
                        <span class="ml-1 text-xs">({{ $this->totalCourses }})</span>
                    </button>
                    <button 
                        wire:click="$set('statusFilter', 'published')"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all
                               {{ $statusFilter === 'published' ? 'bg-[#FF6B35] text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ __('Published') }}
                        <span class="ml-1 text-xs">({{ $this->publishedCount }})</span>
                    </button>
                    <button 
                        wire:click="$set('statusFilter', 'draft')"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all
                               {{ $statusFilter === 'draft' ? 'bg-[#FF6B35] text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ __('Drafts') }}
                        <span class="ml-1 text-xs">({{ $this->draftCount }})</span>
                    </button>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <select wire:model.live="sortBy"
                            class="px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                        <option value="latest">{{ __('Latest first') }}</option>
                        <option value="oldest">{{ __('Oldest first') }}</option>
                        <option value="popular">{{ __('Most Popular') }}</option>
                        <option value="title">{{ __('Title A-Z') }}</option>
                    </select>

                    <div class="relative">
                        <x-input
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search courses...') }}"
                            icon="o-magnifying-glass"
                            class="w-full sm:w-64" />
                    </div>

                    @if($search || $statusFilter)
                        <button 
                            wire:click="clearFilters"
                            class="px-3 py-2 text-sm text-[#FF6B35] hover:underline">
                            {{ __('Reset') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Courses Grid -->
        @if($this->courses->count() > 0)
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($this->courses as $course)
                <div class="overflow-hidden transition-all duration-200 bg-white shadow-sm rounded-xl hover:shadow-md group">
                    <!-- Course Header -->
                    <div class="relative h-28 bg-gradient-to-r from-[#FF6B35]/20 to-[#1E6091]/20 flex items-center justify-center">
                        <div class="text-center">
                            <span class="text-5xl">
                                @if($course->level === 'A1' || $course->level === 'A2') 🌱
                                @elseif($course->level === 'B1' || $course->level === 'B2') 📚
                                @elseif($course->level === 'C1' || $course->level === 'C2') 🏆
                                @else 🇩🇪
                                @endif
                            </span>
                            <div class="mt-1 text-xs text-gray-500">{{ $this->getLevelLabel($course->level) }}</div>
                        </div>
                        
                        <!-- Status Badge -->
                        <div class="absolute top-3 right-3">
                            <span class="px-2 py-0.5 text-xs rounded-full {{ $this->getStatusBadgeClass($course->is_published) }}">
                                {{ $this->getStatusText($course->is_published) }}
                            </span>
                        </div>
                    </div>

                    <!-- Course Info -->
                    <div class="p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">
                                {{ $course->subject->name ?? 'German' }}
                            </span>
                            <div class="flex items-center gap-1 text-sm text-gray-500">
                                <x-icon name="o-users" class="w-3 h-3" />
                                <span class="text-xs">{{ $course->enrollments_count }}</span>
                            </div>
                        </div>

                        <h3 class="mb-1 font-semibold text-gray-900 line-clamp-1">{{ $course->title }}</h3>
                        
                        <p class="mb-3 text-sm text-gray-500 line-clamp-2">
                            {{ Str::limit($course->description, 80) }}
                        </p>

                        <!-- Stats -->
                        <div class="flex items-center justify-between mb-4 text-xs text-gray-500">
                            <span class="flex items-center gap-1">
                                <x-icon name="o-book-open" class="w-3 h-3" />
                                {{ $course->lessons_count }} {{ __('Lessons') }}
                            </span>
                            <span class="flex items-center gap-1">
                                <x-icon name="o-document-text" class="w-3 h-3" />
                                {{ $course->quizzes_count }} {{ __('Quizzes') }}
                            </span>
                            <span class="flex items-center gap-1">
                                <x-icon name="o-star" class="w-3 h-3 text-yellow-400" />
                                {{ number_format($course->average_rating ?? 0, 1) }}
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-gray-100">
                            <div class="flex gap-1">
                                <button 
                                    wire:click="togglePublish({{ $course->id }})"
                                    class="p-2 text-gray-500 transition rounded-lg hover:bg-gray-100"
                                    title="{{ $course->is_published ? __('Unpublish') : __('Publish') }}">
                                    <x-icon :name="$course->is_published ? 'o-eye-slash' : 'o-eye'" class="w-4 h-4" />
                                </button>

                                <a href="{{ route('teacher.courses.edit', $course) }}"
                                   class="p-2 text-gray-500 transition rounded-lg hover:bg-gray-100"
                                   title="{{ __('Edit Course') }}">
                                    <x-icon name="o-pencil" class="w-4 h-4" />
                                </a>

                                <a href="{{ route('teacher.announcements') }}?selectedCourse={{ $course->id }}" 
                                    class="p-2 text-purple-500 transition rounded-lg hover:bg-purple-50"
                                    title="{{ __('Announcements') }}">
                                    <x-icon name="o-megaphone" class="w-4 h-4" />
                                </a>

                                <a href="{{ route('teacher.quizzes.index', $course) }}"
                                   class="p-2 text-purple-500 transition rounded-lg hover:bg-purple-50"
                                   title="{{ __('Quiz Manager') }}">
                                    <x-icon name="o-document-text" class="w-4 h-4" />
                                </a>

                                <button 
                                    wire:click="deleteCourse({{ $course->id }})"
                                    class="p-2 text-gray-500 transition rounded-lg hover:text-red-600 hover:bg-red-50"
                                    title="{{ __('Delete') }}">
                                    <x-icon name="o-trash" class="w-4 h-4" />
                                </button>
                            </div>

                            <div class="flex gap-2">
                                @if($course->quizzes_count > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs text-purple-700 bg-purple-100 rounded-full">
                                        <x-icon name="o-document-text" class="w-3 h-3" />
                                        {{ $course->quizzes_count }} Quiz
                                    </span>
                                @endif
                                <a href="{{ route('teacher.lessons.index', $course) }}"
                                   class="px-3 py-1.5 text-sm font-medium text-white rounded-lg bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] hover:shadow-md transition">
                                    {{ __('Manage') }} →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $this->courses->links() }}
            </div>
        @else
            <div class="p-12 text-center bg-white shadow-sm rounded-xl">
                @if($search || $statusFilter)
                    <x-icon name="o-magnifying-glass" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('No courses found') }}</h3>
                    <p class="mb-4 text-gray-500">{{ __('Try different search terms or filters.') }}</p>
                    <button wire:click="clearFilters" class="text-[#FF6B35] hover:underline">
                        {{ __('Reset filters') }} →
                    </button>
                @else
                    <x-icon name="o-academic-cap" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('No courses yet') }}</h3>
                    <p class="mb-4 text-gray-500">{{ __('Create your first German course.') }}</p>
                    <a href="{{ route('teacher.courses.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                        <x-icon name="o-plus" class="w-4 h-4" />
                        {{ __('Create First Course') }}
                    </a>
                @endif
            </div>
        @endif

        <!-- Delete Confirmation Modal -->
        @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="cancelDelete">
            <div class="w-full max-w-md overflow-hidden bg-white shadow-xl rounded-xl">
                <div class="p-6">
                    <div class="flex items-center justify-center mb-4">
                        <div class="flex items-center justify-center w-16 h-16 bg-red-100 rounded-full">
                            <x-icon name="o-exclamation-triangle" class="w-8 h-8 text-red-600" />
                        </div>
                    </div>
                    <h3 class="mb-2 text-xl font-bold text-center text-gray-900">{{ __('Delete Course') }}</h3>
                    <p class="mb-6 text-center text-gray-600">
                        {{ __('Are you sure you want to delete ":course"? This action cannot be undone.', ['course' => $courseToDelete?->title]) }}
                    </p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="cancelDelete" class="px-4 py-2 text-gray-600 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </button>
                        <button wire:click="confirmDelete" class="px-4 py-2 text-white transition bg-red-600 rounded-lg hover:bg-red-700">
                            {{ __('Delete') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>