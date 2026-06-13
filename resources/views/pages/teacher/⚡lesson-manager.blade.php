<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Str;
use Mary\Traits\Toast;

new
#[Title('Manage Lessons - Teacher')]
#[Layout('layouts.app')]
class extends Component {
    use WithPagination, Toast;

    public Course $course;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    public function mount(Course $course): void
    {
        if ($course->teacher_id != auth()->id()) {
            abort(403);
        }
        $this->course = $course;
    }

    // Getters
    public function getLessonsProperty()
    {
        return Lesson::where('course_id', $this->course->id)
            ->with('quiz')
            ->when($this->search, function($query) {
                $query->where('title', 'like', '%' . $this->search . '%');
            })
            ->orderBy('order')
            ->get();
    }

    public function getTotalLessonsProperty()
    {
        return $this->lessons->count();
    }

    public function getPublishedCountProperty()
    {
        return $this->lessons->where('is_published', true)->count();
    }

    public function getDraftCountProperty()
    {
        return $this->lessons->where('is_published', false)->count();
    }

    public function getLessonsWithQuizProperty()
    {
        return $this->lessons->filter(fn($lesson) => $lesson->quiz !== null)->count();
    }

    public function deleteLesson($lessonId): void
    {
        $lesson = Lesson::findOrFail($lessonId);
        if ($lesson->course_id !== $this->course->id) {
            $this->error(__('Unauthorized.'));
            return;
        }
        $lesson->delete();
        $this->success(__('Lesson deleted! 🗑️'));
    }

    public function duplicateLesson($lessonId): void
    {
        $originalLesson = Lesson::findOrFail($lessonId);
        if ($originalLesson->course_id !== $this->course->id) {
            $this->error(__('Unauthorized.'));
            return;
        }

        $newLesson = $originalLesson->replicate();
        $newLesson->title = $originalLesson->title . ' (Copy)';
        $newLesson->slug = Str::slug($newLesson->title) . '-' . uniqid();
        $newLesson->order = $this->totalLessons + 1;
        $newLesson->is_published = false;
        $newLesson->save();

        $this->success(__('Lesson duplicated! 📋'));
    }

    public function moveUp($lessonId): void
    {
        $lesson = Lesson::findOrFail($lessonId);
        $prevLesson = Lesson::where('course_id', $this->course->id)
            ->where('order', '<', $lesson->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($prevLesson) {
            $prevOrder = $prevLesson->order;
            $prevLesson->update(['order' => $lesson->order]);
            $lesson->update(['order' => $prevOrder]);
            $this->success(__('Order updated.'));
        }
    }

    public function moveDown($lessonId): void
    {
        $lesson = Lesson::findOrFail($lessonId);
        $nextLesson = Lesson::where('course_id', $this->course->id)
            ->where('order', '>', $lesson->order)
            ->orderBy('order', 'asc')
            ->first();

        if ($nextLesson) {
            $nextOrder = $nextLesson->order;
            $nextLesson->update(['order' => $lesson->order]);
            $lesson->update(['order' => $nextOrder]);
            $this->success(__('Order updated.'));
        }
    }

    public function togglePublish($lessonId): void
    {
        $lesson = Lesson::findOrFail($lessonId);
        $lesson->update(['is_published' => !$lesson->is_published]);
        $this->success($lesson->is_published ? __('Lesson published! 🚀') : __('Lesson saved as draft.'));
    }

    public function createQuiz($lessonId): void
    {
        $lesson = Lesson::findOrFail($lessonId);
        if ($lesson->course_id !== $this->course->id) {
            $this->error(__('Unauthorized.'));
            return;
        }
        $this->redirectRoute('teacher.quizzes.create', ['course' => $this->course, 'lesson' => $lesson]);
    }

    public function editQuiz($quizId): void
    {
        $this->redirectRoute('teacher.quizzes.edit', ['course' => $this->course, 'quiz' => $quizId]);
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    public function formatDuration($seconds): string
    {
        if ($seconds < 60) return "{$seconds} sec";
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return $secs > 0 ? "{$minutes} min {$secs} sec" : "{$minutes} min";
    }

    public function render()
    {
        return $this->view([
            'lessons' => $this->lessons,
            'totalLessons' => $this->totalLessons,
            'publishedCount' => $this->publishedCount,
            'draftCount' => $this->draftCount,
            'lessonsWithQuiz' => $this->lessonsWithQuiz,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-6xl px-3 mx-auto md:px-4">

        {{-- Navigation --}}
        <div class="mb-5">
            <a href="{{ route('teacher.courses') }}" wire:navigate class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to my courses') }}
            </a>
        </div>

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">📚 {{ __('Manage Lessons') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ $course->title }}</p>
            </div>
            <div class="flex gap-2">
                <x-button icon="o-plus" label="{{ __('New Lesson') }}" link="{{ route('teacher.lessons.create', $course) }}" class="btn-primary" />
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-4 gap-3 mb-5">
            <x-stat title="{{ __('Lessons') }}" :value="$totalLessons" icon="o-book-open" class="text-primary" />
            <x-stat title="{{ __('Published') }}" :value="$publishedCount" icon="o-check-circle" class="text-success" />
            <x-stat title="{{ __('Drafts') }}" :value="$draftCount" icon="o-pencil" class="text-warning" />
            <x-stat title="{{ __('With Quiz') }}" :value="$lessonsWithQuiz" icon="o-document-text" class="text-secondary" />
        </div>

        {{-- Search --}}
        <div class="p-3 mb-5 shadow-sm bg-base-100 rounded-xl">
            <div class="relative">
                <x-input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search lessons...') }}" icon="o-magnifying-glass" class="w-full" clearable />
            </div>
        </div>

        {{-- Lessons List --}}
        @if($lessons->count() > 0)
            <div class="overflow-hidden shadow-sm bg-base-100 rounded-xl">
                <div class="divide-y divide-base-200">
                    @foreach($lessons as $index => $lesson)
                        <div class="p-4 transition hover:bg-base-200 group">
                            <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="flex items-center justify-center w-8 h-8 text-sm font-bold rounded-full bg-base-200">{{ $lesson->order }}</span>
                                        <h3 class="font-semibold">{{ $lesson->title }}</h3>
                                        @if($lesson->is_published)
                                            <x-badge value="{{ __('Published') }}" class="badge-success badge-soft" />
                                        @else
                                            <x-badge value="{{ __('Draft') }}" class="badge-warning badge-soft" />
                                        @endif
                                        @if($lesson->is_free)
                                            <x-badge value="{{ __('Free') }}" icon="o-lock-open" class="badge-info badge-soft" />
                                        @endif
                                        @if($lesson->quiz)
                                            <x-badge value="{{ __('Quiz') }}" icon="o-document-text" class="badge-secondary badge-soft" />
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-base-content/60">
                                        @if($lesson->duration)
                                            <span class="flex items-center gap-1"><x-icon name="o-clock" class="w-3 h-3" />{{ $this->formatDuration($lesson->duration) }}</span>
                                        @endif
                                        @if($lesson->video_url)
                                            <span class="flex items-center gap-1"><x-icon name="o-video-camera" class="w-3 h-3" />{{ __('With video') }}</span>
                                        @endif
                                    </div>
                                    @if($lesson->description)
                                        <p class="mt-2 text-sm text-base-content/70 line-clamp-1">{{ clean_text($lesson->description) }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1">
                                    <x-button icon="o-arrow-up" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Move up') }}" wire:click="moveUp({{ $lesson->id }})" :disabled="$loop->first" />
                                    <x-button icon="o-arrow-down" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Move down') }}" wire:click="moveDown({{ $lesson->id }})" :disabled="$loop->last" />
                                    <x-button :icon="$lesson->is_published ? 'o-eye-slash' : 'o-eye'" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ $lesson->is_published ? __('Unpublish') : __('Publish') }}" wire:click="togglePublish({{ $lesson->id }})" />
                                    {{-- @if($lesson->quiz)
                                        <x-button icon="o-document-text" class="btn-circle btn-ghost btn-sm text-secondary" tooltip-left="{{ __('Edit quiz') }}" wire:click="editQuiz({{ $lesson->quiz->id }})" />
                                    @else
                                        <x-button icon="o-plus-circle" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Create quiz') }}" wire:click="createQuiz({{ $lesson->id }})" />
                                    @endif --}}
                                    <x-button icon="o-document-duplicate" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Duplicate') }}" wire:click="duplicateLesson({{ $lesson->id }})" wire:confirm="{{ __('Duplicate lesson?') }}" />
                                    <x-button icon="o-pencil" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Edit') }}" link="{{ route('teacher.lessons.edit', ['course' => $course, 'lesson' => $lesson]) }}" />
                                    <x-button icon="o-eye" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Preview') }}" link="{{ route('teacher.lessons.preview', ['course' => $course, 'lesson' => $lesson]) }}" />
                                    <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" tooltip-left="{{ __('Delete') }}" wire:click="deleteLesson({{ $lesson->id }})" wire:confirm="{{ __('Delete this lesson?') }}" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <x-card class="py-12 text-center">
                @if($search)
                    <x-icon name="o-magnifying-glass" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <h3 class="mb-2 text-lg font-semibold">{{ __('No lessons found') }}</h3>
                    <p class="mb-4 text-base-content/60">{{ __('Try different search terms.') }}</p>
                    <x-button wire:click="clearSearch" label="{{ __('Clear search') }} →" class="btn-outline" />
                @else
                    <x-icon name="o-book-open" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <h3 class="mb-2 text-lg font-semibold">{{ __('No lessons yet') }}</h3>
                    <p class="mb-4 text-base-content/60">{{ __('Create your first lesson for this course.') }}</p>
                    <x-button label="{{ __('Create first lesson') }}" link="{{ route('teacher.lessons.create', $course) }}" class="btn-primary" />
                @endif
            </x-card>
        @endif
    </div>
</div>
