<?php

use App\Models\Course;
use App\Models\Lesson;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\{Url, Computed};
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Lektionen verwalten - Lehrer')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use WithPagination, Toast;

    public Course $course;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    public function mount(Course $course): void
    {
        if ($course->teacher_id !== auth()->id()) {
            abort(403);
        }
        $this->course = $course;
    }

    #[Computed]
    public function lessons()
    {
        return Lesson::where('course_id', $this->course->id)
            ->with('quiz')
            ->when($this->search, function($query) {
                $query->where('title', 'like', '%' . $this->search . '%');
            })
            ->orderBy('order')
            ->get();
    }

    #[Computed]
    public function totalLessons()
    {
        return $this->lessons->count();
    }

    #[Computed]
    public function publishedCount()
    {
        return $this->lessons->where('is_published', true)->count();
    }

    #[Computed]
    public function draftCount()
    {
        return $this->lessons->where('is_published', false)->count();
    }

    #[Computed]
    public function lessonsWithQuiz()
    {
        return $this->lessons->filter(function($lesson) {
            return $lesson->quiz !== null;
        })->count();
    }

    public function deleteLesson($lessonId): void
    {
        $lesson = Lesson::findOrFail($lessonId);

        if ($lesson->course_id !== $this->course->id) {
            $this->error('Nicht autorisiert.');
            return;
        }

        $lesson->delete();
        $this->success('Lektion gelöscht! 🗑️');
    }

    public function duplicateLesson($lessonId): void
    {
        $originalLesson = Lesson::findOrFail($lessonId);

        if ($originalLesson->course_id !== $this->course->id) {
            $this->error('Nicht autorisiert.');
            return;
        }

        // Créer une copie
        $newLesson = $originalLesson->replicate();
        $newLesson->title = $originalLesson->title . ' (Kopie)';
        $newLesson->slug = \Illuminate\Support\Str::slug($newLesson->title) . '-' . uniqid();
        $newLesson->order = $this->lessons->count() + 1;
        $newLesson->is_published = false;
        $newLesson->save();

        $this->success('Lektion dupliziert! 📋');
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
            $this->success('Reihenfolge aktualisiert.');
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
            $this->success('Reihenfolge aktualisiert.');
        }
    }

    public function togglePublish($lessonId): void
    {
        $lesson = Lesson::findOrFail($lessonId);
        $lesson->update(['is_published' => !$lesson->is_published]);
        $this->success($lesson->is_published ? 'Lektion veröffentlicht! 🚀' : 'Lektion als Entwurf gespeichert.');
    }

    public function createQuiz($lessonId): void
    {
        $lesson = Lesson::findOrFail($lessonId);

        if ($lesson->course_id !== $this->course->id) {
            $this->error('Nicht autorisiert.');
            return;
        }

        // Rediriger vers la création de quiz pour cette leçon
        $this->redirectRoute('teacher.quizzes.create', ['course' => $this->course, 'lesson' => $lesson], navigate: true);
    }

    public function editQuiz($quizId): void
    {
        $this->redirectRoute('teacher.quizzes.edit', ['course' => $this->course, 'quiz' => $quizId], navigate: true);
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    public function getDurationFormatted($seconds): string
    {
        if ($seconds < 60) return "{$seconds} sec";
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return $secs > 0 ? "{$minutes} min {$secs} sec" : "{$minutes} min";
    }
}
?>

<div class="py-4 md:py-6">
    <div class="max-w-6xl px-3 mx-auto md:px-4">

        <!-- Navigation -->
        <div class="mb-5">
            <a href="{{ route('teacher.courses') }}" class="inline-flex items-center gap-1 text-sm text-[#FF6B35] hover:text-[#E55A2A] transition">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Zurück zu meinen Kursen') }}
            </a>
        </div>

        <!-- Header -->
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">📚 {{ __('Lektionen verwalten') }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">{{ $course->title }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('teacher.lessons.create', $course) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                    <x-icon name="o-plus" class="w-4 h-4" />
                    {{ __('Neue Lektion') }}
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-4 gap-3 mb-5">
            <div class="p-3 text-center bg-white rounded-lg shadow-sm">
                <p class="text-xs text-gray-500">📖 Lektionen</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->totalLessons }}</p>
            </div>
            <div class="p-3 text-center bg-white rounded-lg shadow-sm">
                <p class="text-xs text-gray-500">✅ Veröffentlicht</p>
                <p class="text-xl font-bold text-green-600">{{ $this->publishedCount }}</p>
            </div>
            <div class="p-3 text-center bg-white rounded-lg shadow-sm">
                <p class="text-xs text-gray-500">📝 Entwürfe</p>
                <p class="text-xl font-bold text-yellow-600">{{ $this->draftCount }}</p>
            </div>
            <div class="p-3 text-center bg-white rounded-lg shadow-sm">
                <p class="text-xs text-gray-500">🎯 Mit Quiz</p>
                <p class="text-xl font-bold text-purple-600">{{ $this->lessonsWithQuiz }}</p>
            </div>
        </div>

        <!-- Search -->
        <div class="p-3 mb-5 bg-white shadow-sm rounded-xl">
            <div class="relative">
                <x-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Lektionen suchen...') }}"
                    icon="o-magnifying-glass"
                    class="w-full" />
                @if($search)
                    <button wire:click="clearSearch" class="absolute -translate-y-1/2 right-3 top-1/2">
                        <x-icon name="o-x-mark" class="w-4 h-4 text-gray-400 hover:text-gray-600" />
                    </button>
                @endif
            </div>
        </div>

        <!-- Lessons List -->
        @if($this->lessons->count() > 0)
            <div class="overflow-hidden bg-white shadow-sm rounded-xl">
                <div class="divide-y divide-gray-100">
                    @foreach($this->lessons as $index => $lesson)
                    <div class="p-4 transition hover:bg-gray-50 group">
                        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="flex items-center justify-center w-8 h-8 text-sm font-bold text-gray-600 bg-gray-100 rounded-full">
                                        {{ $lesson->order }}
                                    </span>
                                    <h3 class="font-semibold text-gray-900">{{ $lesson->title }}</h3>
                                    <span class="px-2 py-0.5 text-xs rounded-full {{ $lesson->is_published ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        {{ $lesson->is_published ? 'Veröffentlicht' : 'Entwurf' }}
                                    </span>
                                    @if($lesson->is_free)
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">
                                            🔓 Kostenlos
                                        </span>
                                    @endif
                                    @if($lesson->quiz)
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700">
                                            🎯 Quiz vorhanden
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                    @if($lesson->duration)
                                        <span class="flex items-center gap-1">
                                            <x-icon name="o-clock" class="w-3 h-3" />
                                            {{ $this->getDurationFormatted($lesson->duration) }}
                                        </span>
                                    @endif
                                    @if($lesson->video_url)
                                        <span class="flex items-center gap-1">
                                            <x-icon name="o-video-camera" class="w-3 h-3" />
                                            {{ __('Mit Video') }}
                                        </span>
                                    @endif
                                </div>
                                @if($lesson->description)
                                    <p class="mt-2 text-sm text-gray-500 line-clamp-1">{{ $lesson->description }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-1">
                                <!-- Move buttons -->
                                <button wire:click="moveUp({{ $lesson->id }})"
                                        class="p-2 text-gray-400 transition rounded-lg hover:text-gray-600 hover:bg-gray-100"
                                        {{ $loop->first ? 'disabled' : '' }}
                                        title="{{ __('Nach oben') }}">
                                    <x-icon name="o-arrow-up" class="w-4 h-4" />
                                </button>
                                <button wire:click="moveDown({{ $lesson->id }})"
                                        class="p-2 text-gray-400 transition rounded-lg hover:text-gray-600 hover:bg-gray-100"
                                        {{ $loop->last ? 'disabled' : '' }}
                                        title="{{ __('Nach unten') }}">
                                    <x-icon name="o-arrow-down" class="w-4 h-4" />
                                </button>

                                <!-- Publish toggle -->
                                <button wire:click="togglePublish({{ $lesson->id }})"
                                        class="p-2 text-gray-400 transition rounded-lg hover:text-gray-600 hover:bg-gray-100"
                                        title="{{ $lesson->is_published ? __('Unveröffentlichen') : __('Veröffentlichen') }}">
                                    <x-icon :name="$lesson->is_published ? 'o-eye-slash' : 'o-eye'" class="w-4 h-4" />
                                </button>

                                <!-- Quiz Action -->
                                @if($lesson->quiz)
                                    <button wire:click="editQuiz({{ $lesson->quiz->id }})"
                                            class="p-2 text-purple-400 transition rounded-lg hover:text-purple-600 hover:bg-purple-50"
                                            title="{{ __('Quiz bearbeiten') }}">
                                        <x-icon name="o-document-text" class="w-4 h-4" />
                                    </button>
                                @else
                                    <button wire:click="createQuiz({{ $lesson->id }})"
                                            class="p-2 text-gray-400 transition rounded-lg hover:text-purple-600 hover:bg-purple-50"
                                            title="{{ __('Quiz erstellen') }}">
                                        <x-icon name="o-plus-circle" class="w-4 h-4" />
                                    </button>
                                @endif

                                <!-- Duplicate -->
                                <button wire:click="duplicateLesson({{ $lesson->id }})"
                                        wire:confirm="{{ __('Lektion duplizieren?') }}"
                                        class="p-2 text-gray-400 transition rounded-lg hover:text-blue-600 hover:bg-blue-50"
                                        title="{{ __('Duplizieren') }}">
                                    <x-icon name="o-document-duplicate" class="w-4 h-4" />
                                </button>

                                <!-- Edit -->
                                <a href="{{ route('teacher.lessons.edit', ['course' => $course, 'lesson' => $lesson]) }}"
                                   class="p-2 text-gray-400 transition rounded-lg hover:text-orange-600 hover:bg-orange-50"
                                   title="{{ __('Bearbeiten') }}">
                                    <x-icon name="o-pencil" class="w-4 h-4" />
                                </a>

                                <!-- Delete -->
                                <button wire:click="deleteLesson({{ $lesson->id }})"
                                        wire:confirm="{{ __('Lektion wirklich löschen?') }}"
                                        class="p-2 text-gray-400 transition rounded-lg hover:text-red-600 hover:bg-red-50"
                                        title="{{ __('Löschen') }}">
                                    <x-icon name="o-trash" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="p-12 text-center bg-white shadow-sm rounded-xl">
                @if($search)
                    <x-icon name="o-magnifying-glass" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('Keine Lektionen gefunden') }}</h3>
                    <p class="mb-4 text-gray-500">{{ __('Versuche andere Suchbegriffe.') }}</p>
                    <button wire:click="clearSearch" class="text-[#FF6B35] hover:underline">
                        {{ __('Filter zurücksetzen') }} →
                    </button>
                @else
                    <x-icon name="o-book-open" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('Noch keine Lektionen') }}</h3>
                    <p class="mb-4 text-gray-500">{{ __('Erstelle deine erste Lektion für diesen Kurs.') }}</p>
                    <a href="{{ route('teacher.lessons.create', $course) }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                        <x-icon name="o-plus" class="w-4 h-4" />
                        {{ __('Erste Lektion erstellen') }}
                    </a>
                @endif
            </div>
        @endif

        <!-- Note MVP -->
        <div class="p-3 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-2">
                <x-icon name="o-information-circle" class="w-4 h-4 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">MVP Version</p>
                    <p class="text-xs text-blue-700">{{ __('Klicke auf das Quiz-Symbol (📄) um ein Quiz zu erstellen oder zu bearbeiten.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
