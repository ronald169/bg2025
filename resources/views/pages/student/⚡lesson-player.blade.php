<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Lesson;
use App\Models\Progress;
use App\Models\Enrollment;
use Mary\Traits\Toast;
use App\Traits\Seoable;

new
#[Title('Lesson - German Learning')]
#[Layout('layouts.guest')]
class extends Component {
    use Toast, Seoable;

    public Lesson $lesson;
    public string $notes = '';

    public function mount(Lesson $lesson): void
    {
        $this->lesson = $lesson->load(['course', 'course.quizzes']);

        // Vérifier l’inscription
        $isEnrolled = Enrollment::where('user_id', auth()->id())
            ->where('course_id', $this->lesson->course_id)
            ->exists();

        $ownedByTeacher = $this->lesson->course->teacher_id === auth()->id();
        $isAdmin = auth()->user() ? auth()->user()->isAdmin() : false;

        if (!$this->lesson->is_free) {
            if (!$isEnrolled && !$ownedByTeacher && !$isAdmin) {
                abort(403, 'You must be enrolled in this course to access the lesson.');
            }
        }


        $this->loadProgress();
    }

    public function loadProgress(): void
    {
        $progress = Progress::where('user_id', auth()->id())
            ->where('lesson_id', $this->lesson->id)
            ->first();

        $this->notes = $progress?->notes ?? '';
    }

    public function getIsCompletedProperty(): bool
    {
        return Progress::where('user_id', auth()->id())
            ->where('lesson_id', $this->lesson->id)
            ->where('is_completed', true)
            ->exists();
    }

    public function getNextLessonProperty()
    {
        return Lesson::where('course_id', $this->lesson->course_id)
            ->where('order', '>', $this->lesson->order)
            ->orderBy('order')
            ->first();
    }

    public function getPrevLessonProperty()
    {
        return Lesson::where('course_id', $this->lesson->course_id)
            ->where('order', '<', $this->lesson->order)
            ->orderBy('order', 'desc')
            ->first();
    }

    public function getQuizProperty()
    {
        return $this->lesson->course->quizzes()
            ->where('lesson_id', $this->lesson->id)
            ->first();
    }

    public function getHasQuizAttemptProperty(): bool
    {
        $quiz = $this->quiz;
        if (!$quiz) return false;

        return $quiz->attempts()
            ->where('user_id', auth()->id())
            ->exists();
    }

    public function getBestQuizScoreProperty()
    {
        $quiz = $this->quiz;
        if (!$quiz) return null;

        return $quiz->attempts()
            ->where('user_id', auth()->id())
            ->orderBy('score', 'desc')
            ->first();
    }

    public function getTotalQuizQuestionsProperty(): int
    {
        $quiz = $this->quiz;
        return $quiz?->questions()->count() ?? 0;
    }

    public function getQuizPercentageProperty(): ?int
    {
        if (!$this->bestQuizScore || $this->totalQuizQuestions === 0) return null;
        return round(($this->bestQuizScore->score / $this->totalQuizQuestions) * 100);
    }

    public function markComplete(): void
    {
        Progress::updateOrCreate(
            [
                'user_id'   => auth()->id(),
                'lesson_id' => $this->lesson->id,
            ],
            [
                'is_completed' => true,
                'completed_at' => now(),
                'updated_at'  => now(),
                'notes'       => $this->notes,
            ]
        );

        // Mettre à jour la progression du cours
        $totalLessons = Lesson::where('course_id', $this->lesson->course_id)->count();
        $completedLessons = Progress::where('user_id', auth()->id())
            ->whereIn('lesson_id', Lesson::where('course_id', $this->lesson->course_id)->pluck('id'))
            ->where('is_completed', true)
            ->count();

        $progressPercent = ($completedLessons / max($totalLessons, 1)) * 100;

        Enrollment::where('user_id', auth()->id())
            ->where('course_id', $this->lesson->course_id)
            ->update(['progress' => $progressPercent]);

        $this->success(__('Lesson completed! 🎉'));
    }

    public function saveNotes(): void
    {
        Progress::updateOrCreate(
            [
                'user_id'   => auth()->id(),
                'lesson_id' => $this->lesson->id,
            ],
            [
                'notes'      => $this->notes,
                'updated_at' => now(),
            ]
        );

        $this->success(__('Notes saved! 📝'));
    }

    public function getVideoId($url): ?string
    {
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

    public function getStructuredDataProperty(): string
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Lesson',
            'name' => $this->lesson->title,
            'description' => strip_tags($this->lesson->description ?? $this->lesson->content ?? ''),
            'educationalLevel' => $this->lesson->course->level ?? 'A1',
            'about' => 'German language',
            'inLanguage' => 'de',
            'isPartOf' => [
                '@type' => 'Course',
                'name' => $this->lesson->course->title,
                'url' => route('student.course.show', $this->lesson->course),
            ],
        ];

        return json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    public function render()
    {
        return $this->view([
            'isCompleted'          => $this->isCompleted,
            'nextLesson'           => $this->nextLesson,
            'prevLesson'           => $this->prevLesson,
            'quiz'                 => $this->quiz,
            'hasQuizAttempt'       => $this->hasQuizAttempt,
            'bestQuizScore'        => $this->bestQuizScore,
            'totalQuizQuestions'   => $this->totalQuizQuestions,
            'quizPercentage'       => $this->quizPercentage,
            'videoId'              => $this->getVideoId($this->lesson->video_url),
        ])->layoutData([
            'structuredData' => $this->structuredData,
        ]);
    }
};

?>

{{-- SEO Meta Tags --}}
@section('meta_title', $this->lesson->meta_title ?? $this->lesson->title . ' - ' . $this->lesson->course->title . ' - ' . config('app.name'))
@section('meta_description', $this->lesson->meta_description ?? Str::limit(strip_tags($this->lesson->content ?? $this->lesson->description ?? ''), 160))
@section('meta_keywords', $this->lesson->meta_keywords ?? 'German lesson, ' . $this->lesson->title . ', learn German, ' . ($this->lesson->course->level ?? 'A1'))
@section('og_title', $this->lesson->og_title ?? $this->lesson->title)
@section('og_description', $this->lesson->og_description ?? Str::limit(strip_tags($this->lesson->content ?? $this->lesson->description ?? ''), 160))
@section('og_image', $this->lesson->og_image ?? ($this->lesson->course->thumbnail ? asset('storage/' . $this->lesson->course->thumbnail) : asset('images/og-image.jpg')))
@section('canonical_url', $this->lesson->canonical_url ?? url()->current())
@section('meta_robots', $this->lesson->robots ?? 'index,follow')


<div class="py-4 md:py-6">
    <div class="max-w-5xl px-3 mx-auto md:px-4">

        {{-- Fil d’Ariane --}}
        <div class="mb-5">
            <a href="{{ route('student.course.show', $this->lesson->course) }}" wire:navigate
               class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to course') }}
            </a>
        </div>

        {{-- En-tête --}}
        <div class="mb-6">
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <x-badge :value="__('Lesson ') . $this->lesson->order" class="badge-primary badge-soft" />
                @if($isCompleted)
                    <x-badge value="Completed" icon="o-check-circle" class="badge-success badge-soft" />
                @endif
            </div>
            <h1 class="text-2xl font-bold md:text-3xl">{{ $this->lesson->title }}</h1>
            <p class="mt-1 text-base-content/70">{{ $this->lesson->course->title }}</p>
        </div>

        {{-- Lecteur vidéo --}}
        <div class="">
            <div class="flex items-center justify-center bg-gray-900 aspect-video">
                @if($this->lesson->video_url && $videoId)
                    <iframe
                        src="https://www.youtube.com/embed/{{ $videoId }}"
                        class="w-full h-full"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                @elseif($this->lesson->video_url)
                    <video controls class="w-full h-full">
                        <source src="{{ $this->lesson->video_url }}" type="video/mp4">
                    </video>
                @else
                    <div class="text-center text-white text-base-content/50">
                        <x-icon name="o-video-camera" class="w-16 h-16 mx-auto mb-3" />
                        <p>{{ __('Video coming soon') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Contenu texte --}}
        @if($this->lesson->content)
        <div class="my-6">
            <x-card class="rounded-none max-sm:-mx-3">
                <div class="prose max-w-none" id="tinymce-content">
                    {!! $this->lesson->content !!}
                </div>
            </x-card>
        </div>
        @endif

        {{-- Notes et actions (grille) --}}
        <div class="grid gap-6 md:grid-cols-2">
            {{-- Bloc Notes --}}
            <x-card title="📝 {{ __('My notes') }}" separator class="rounded-none max-sm:-mx-3">
                <x-textarea
                    wire:model="notes"
                    placeholder="{{ __('Write your notes here...') }}"
                    rows="5"
                    class="font-mono text-sm"
                />
                <x-slot:actions>
                    <x-button wire:click="saveNotes" spinner="saveNotes"
                        label="{{ __('Save notes') }}" icon="o-document-check"
                        class="btn-primary btn-sm" />
                </x-slot:actions>
            </x-card>

            {{-- Bloc Actions --}}
            <x-card title="⚡ {{ __('Actions') }}" separator class="rounded-none max-sm:-mx-3">
                <div class="space-y-4">
                    @if(!$isCompleted)
                        <x-button wire:click="markComplete" spinner="markComplete"
                            label="{{ __('Complete lesson') }}" icon="o-check-circle"
                            class="w-full btn-success" />
                    @else
                        <div class="p-3 text-center rounded-lg bg-success/20 text-success-content">
                            <x-icon name="o-check-circle" class="w-8 h-8 mx-auto mb-1" />
                            <p class="font-medium">{{ __('Lesson completed!') }}</p>
                            <p class="text-xs">{{ __('Keep going! 🎉') }}</p>
                        </div>
                    @endif

                    {{-- Quiz --}}
                    @if($quiz)
                        <div class="pt-3 mt-2 border-t">
                            <h4 class="flex items-center gap-2 mb-2 font-semibold">
                                <x-icon name="o-document-text" class="w-5 h-5 text-primary" />
                                {{ __('Quiz') }}
                            </h4>
                            <p class="mb-3 text-sm text-base-content/70">
                                {{ __('Test your knowledge with this quiz') }}
                            </p>

                            @if($hasQuizAttempt && $bestQuizScore && $quizPercentage !== null)
                                <div class="p-3 mb-3 rounded-lg bg-base-200">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-base-content/70">{{ __('Best score') }}</span>
                                        <span class="font-semibold {{ $quizPercentage >= 70 ? 'text-success' : 'text-warning' }}">
                                            {{ $quizPercentage }}% ({{ $bestQuizScore->score }}/{{ $totalQuizQuestions }})
                                        </span>
                                    </div>
                                </div>
                            @endif

                            <x-button
                                link="{{ route('student.quiz.show', $quiz) }}"
                                :label="$hasQuizAttempt ? __('Retake quiz') : __('Start quiz')"
                                icon="o-document-text"
                                :class="$hasQuizAttempt ? 'btn-outline' : 'btn-primary'"
                                class="w-full"
                            />

                            @if($quiz->time_limit)
                                <p class="mt-2 text-xs text-base-content/60">
                                    <x-icon name="o-clock" class="inline w-3 h-3 mr-1" />
                                    {{ __('Time limit') }}: {{ $quiz->time_limit }} {{ __('minutes') }}
                                </p>
                            @endif
                        </div>
                    @endif

                    {{-- Navigation entre leçons --}}
                    <div class="flex justify-between pt-3 mt-2 border-t">
                        @if($prevLesson)
                            <x-button
                                link="{{ route('student.lesson.show', ['course' => $this->lesson->course, 'lesson' => $prevLesson]) }}"
                                icon="o-arrow-left"
                                class="btn-ghost">
                                {{ __('Previous') }}
                            </x-button>
                        @else
                            <div></div>
                        @endif

                        @if($nextLesson)
                            <x-button
                                link="{{ route('student.lesson.show', ['course' => $this->lesson->course, 'lesson' => $nextLesson]) }}"
                                icon-right="o-arrow-right"
                                class="btn-primary">
                                {{ __('Next lesson') }}
                            </x-button>
                        @else
                            <x-button
                                link="{{ route('student.course.show', $this->lesson->course) }}"
                                class="btn-outline">
                                {{ __('Back to course') }}
                            </x-button>
                        @endif
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>
