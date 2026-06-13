<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Course;
use App\Models\Lesson;
use Mary\Traits\Toast;

new
#[Title('Preview Lesson - Teacher')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public Course $course;
    public Lesson $lesson;

    public function mount(Course $course, Lesson $lesson): void
    {
        if ($course->teacher_id != auth()->id() || $lesson->course_id != $course->id) {
            abort(403);
        }
        $this->course = $course;
        $this->lesson = $lesson;
    }

    public function getNextLessonProperty()
    {
        return Lesson::where('course_id', $this->course->id)
            ->where('order', '>', $this->lesson->order)
            ->orderBy('order')
            ->first();
    }

    public function getPrevLessonProperty()
    {
        return Lesson::where('course_id', $this->course->id)
            ->where('order', '<', $this->lesson->order)
            ->orderBy('order', 'desc')
            ->first();
    }

    public function getVideoIdProperty(): ?string
    {
        $url = $this->lesson->video_url;
        if (empty($url)) return null;

        if (preg_match('/youtu\.be\/([^?&]+)/', $url, $matches)) {
            return $matches[1];
        }
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $params);
        if (isset($params['v'])) {
            return $params['v'];
        }
        if (preg_match('/youtube\.com\/(?:embed|shorts)\/([^?&]+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function getDurationFormatted(): string
    {
        $seconds = $this->lesson->duration;
        if ($seconds < 60) return "{$seconds} sec";
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return $secs > 0 ? "{$minutes} min {$secs} sec" : "{$minutes} min";
    }

    public function render()
    {
        return $this->view([
            'nextLesson' => $this->nextLesson,
            'prevLesson' => $this->prevLesson,
            'videoId'    => $this->videoId,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-5xl px-3 mx-auto md:px-4">

        {{-- Fil d'Ariane --}}
        <div class="mb-5">
            <a href="{{ route('teacher.lessons.index', $course) }}" wire:navigate class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to lessons') }}
            </a>
        </div>

        {{-- En-tête --}}
        <div class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <x-badge :value="'Lesson ' . $lesson->order" class="badge-primary badge-soft" />
                        @if($lesson->is_free)
                            <x-badge value="{{ __('Free preview') }}" class="badge-info badge-soft" />
                        @endif
                        @if($lesson->duration)
                            <x-badge :value="$this->getDurationFormatted()" icon="o-clock" class="badge-neutral badge-soft" />
                        @endif
                    </div>
                    <h1 class="text-2xl font-bold md:text-3xl">{{ $lesson->title }}</h1>
                    <p class="mt-1 text-sm text-base-content/70">{{ $course->title }}</p>
                </div>
                <div class="p-3 rounded-lg bg-base-200">
                    <p class="text-sm font-medium">{{ __('Preview mode') }}</p>
                    <p class="text-xs text-base-content/60">{{ __('This is how students will see the lesson') }}</p>
                </div>
            </div>
        </div>

        {{-- Vidéo (si disponible) --}}
        @if($lesson->video_url)
            <x-card class="p-0 mb-6 overflow-hidden shadow-sm">
                <div class="flex items-center justify-center bg-gray-900 aspect-video">
                    @if($videoId)
                        <iframe src="https://www.youtube.com/embed/{{ $videoId }}" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    @else
                        <video controls class="w-full h-full">
                            <source src="{{ $lesson->video_url }}" type="video/mp4">
                        </video>
                    @endif
                </div>
            </x-card>
        @endif

        {{-- Contenu texte --}}
        @if($lesson->content)
            <x-card class="mb-6 shadow-sm">
                <div class="prose max-w-none" id="tinymce-content">
                    {!! $lesson->content !!}
                </div>
            </x-card>
        @endif

        {{-- Notes (désactivées en mode preview) --}}
        <div class="grid gap-6 md:grid-cols-2">
            <x-card title="📝 {{ __('Notes (disabled in preview)') }}" class="shadow-sm">
                <p class="text-sm italic text-base-content/60">{{ __('Students can take personal notes during the lesson. This feature is disabled in preview mode.') }}</p>
                <x-textarea rows="5" disabled class="bg-base-200" placeholder="{{ __('This is a preview – notes are not saved.') }}" />
            </x-card>

            <x-card title="⚡ {{ __('Actions') }}" class="shadow-sm">
                <div class="space-y-4">
                    <div class="p-3 text-center rounded-lg bg-base-200">
                        <p class="font-medium text-base-content/80">{{ __('Lesson completion') }}</p>
                        <p class="text-sm text-base-content/60">{{ __('In preview mode, completion is not recorded.') }}</p>
                    </div>

                    @if($lesson->quiz)
                        <div class="pt-3 mt-2 border-t">
                            <h4 class="flex items-center gap-2 mb-2 font-semibold">
                                <x-icon name="o-document-text" class="w-5 h-5 text-primary" />
                                {{ __('Quiz') }}
                            </h4>
                            <p class="mb-3 text-sm text-base-content/60">
                                {{ __('This lesson has an associated quiz. Students can take it after watching the lesson.') }}
                            </p>
                            <x-button label="{{ __('Preview quiz') }} →" icon="o-eye" class="w-full btn-outline" link="{{ route('teacher.quizzes.preview', ['course' => $course, 'quiz' => $lesson->quiz]) }}" />
                        </div>
                    @endif

                    {{-- Navigation entre leçons --}}
                    <div class="flex justify-between pt-3 mt-2 border-t">
                        @if($prevLesson)
                            <x-button link="{{ route('teacher.lessons.preview', ['course' => $course, 'lesson' => $prevLesson]) }}" icon="o-arrow-left" class="btn-ghost">
                                {{ __('Previous lesson') }}
                            </x-button>
                        @else
                            <div></div>
                        @endif
                        @if($nextLesson)
                            <x-button link="{{ route('teacher.lessons.preview', ['course' => $course, 'lesson' => $nextLesson]) }}" icon-right="o-arrow-right" class="btn-primary">
                                {{ __('Next lesson') }}
                            </x-button>
                        @else
                            <x-button link="{{ route('teacher.courses') }}" label="{{ __('Back to courses') }}" class="btn-outline" />
                        @endif
                    </div>
                </div>
            </x-card>
        </div>

        {{-- Message d'information --}}
        <div class="p-4 mt-6 border rounded-lg bg-info/10 border-info/20">
            <div class="flex items-start gap-3">
                <x-icon name="o-eye" class="w-5 h-5 text-info mt-0.5" />
                <div>
                    <p class="font-medium text-info">{{ __('Preview mode') }}</p>
                    <p class="text-sm text-info/80">{{ __('This is a preview of how students will see the lesson. No progress will be saved and notes cannot be stored.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
