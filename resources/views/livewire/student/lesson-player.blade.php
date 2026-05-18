<?php

use App\Models\Lesson;
use App\Models\Progress;
use App\Models\Enrollment;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;
use App\Traits\Seoable;

new
#[Title('Lektion - Deutsch lernen')]
#[Layout('components.layouts.guest')]
class extends Component {
    use Toast, Seoable;

    public Lesson $lesson;
    public $notes = '';

    public function mount(Lesson $lesson): void
    {
        $this->lesson = $lesson->load(['course', 'course.quizzes']);

        // Vérifier l'inscription
        $isEnrolled = Enrollment::where('user_id', auth()->id())
            ->where('course_id', $this->lesson->course_id)
            ->exists();

        $ownedByTeacher = $this->lesson->course->teacher_id === auth()->id();

        // ✅ Ajout de la vérification administrateur
        $isAdmin = auth()->user()->isAdmin(); // ou auth()->user()->role === 'admin'

        if (!$isEnrolled && !$ownedByTeacher && !$isAdmin) {
            abort(403, 'Du musst dich zuerst für diesen Kurs anmelden.');
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

    #[Computed]
    public function isCompleted()
    {
        return Progress::where('user_id', auth()->id())
            ->where('lesson_id', $this->lesson->id)
            ->where('is_completed', true)
            ->exists();
    }

    #[Computed]
    public function nextLesson()
    {
        return Lesson::where('course_id', $this->lesson->course_id)
            ->where('order', '>', $this->lesson->order)
            ->orderBy('order')
            ->first();
    }

    #[Computed]
    public function prevLesson()
    {
        return Lesson::where('course_id', $this->lesson->course_id)
            ->where('order', '<', $this->lesson->order)
            ->orderBy('order', 'desc')
            ->first();
    }

    #[Computed]
    public function quiz()
    {
        return $this->lesson->course->quizzes()
            ->where('lesson_id', $this->lesson->id)
            ->first();
    }

    #[Computed]
    public function hasQuizAttempt()
    {
        $quiz = $this->quiz;
        if (!$quiz) return false;

        return $quiz->attempts()
            ->where('user_id', auth()->id())
            ->exists();
    }

    #[Computed]
    public function bestQuizScore()
    {
        $quiz = $this->quiz;
        if (!$quiz) return null;

        return $quiz->attempts()
            ->where('user_id', auth()->id())
            ->orderBy('score', 'desc')
            ->first();
    }

    public function markComplete(): void
    {
        Progress::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'lesson_id' => $this->lesson->id,
            ],
            [
                'is_completed' => true,
                'completed_at' => now(),
                'updated_at' => now(),
                'notes' => $this->notes,
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

        $this->success('Lektion abgeschlossen! 🎉');
    }

    public function saveNotes(): void
    {
        Progress::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'lesson_id' => $this->lesson->id,
            ],
            [
                'notes' => $this->notes,
                'updated_at' => now(),
            ]
        );

        $this->success('Notizen gespeichert! 📝');
    }

    public function getVideoId($url): ?string
    {
        if (empty($url)) {
            return null;
        }

        // Convertir les URLs youtu.be en format standard
        if (preg_match('/youtu\.be\/([^?&]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Extraire l'ID des URLs youtube.com
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $params);

        if (isset($params['v'])) {
            return $params['v'];
        }

        // Pour les URLs embed et shorts
        if (preg_match('/youtube\.com\/(?:embed|shorts)\/([^?&]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
?>

{{-- SEO Meta Tags --}}
@section('meta_title', $this->lesson->meta_title ?? $this->lesson->title . ' - ' . $this->lesson->course->title . ' - ' . config('app.name'))
@section('meta_description', $this->lesson->meta_description ?? Str::limit(strip_tags($this->lesson->content ?? $this->lesson->description ?? ''), 160))
@section('meta_keywords', $this->lesson->meta_keywords ?? 'German lesson, ' . $this->lesson->title . ', learn German, ' . ($this->lesson->course->level ?? 'A1'))
@section('og_title', $this->lesson->og_title ?? $this->lesson->title)
@section('og_description', $this->lesson->og_description ?? strip_tags($this->lesson->content ?? $this->lesson->description ?? ''))
@section('og_image', $this->lesson->og_image ?? ($this->lesson->course->thumbnail ? asset('storage/' . $this->lesson->course->thumbnail) : asset('images/og-image.jpg')))
@section('canonical_url', $this->lesson->canonical_url ?? url()->current())
@section('meta_robots', $this->lesson->robots ?? 'index,follow')

@push('structured_data')
@php
    $structuredData = [
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
@endphp
<script type="application/ld+json">
    {!! json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush


<div class="py-8">
    <div class="max-w-4xl px-4 mx-auto">

        <!-- Navigation -->
        <div class="mb-6">
            <a href="{{ route('student.course.show', $this->lesson->course) }}"
               class="inline-flex items-center gap-1 text-[#FF6B35] hover:text-[#E55A2A] transition">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Zurück zum Kurs') }}
            </a>
        </div>

        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 mb-2">
                <span class="px-2 py-1 text-xs rounded-full bg-[#FF6B35]/10 text-[#FF6B35]">
                    Lektion {{ $this->lesson->order }}
                </span>
                @if($this->isCompleted)
                    <span class="px-2 py-1 text-xs text-green-700 bg-green-100 rounded-full">
                        ✓ Abgeschlossen
                    </span>
                @endif
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $this->lesson->title }}</h1>
            <p class="mt-1 text-gray-600">{{ $this->lesson->course->title }}</p>
        </div>

        <!-- Video Player -->
        <div class="p-0 mb-6 overflow-hidden border-0">
            <div class="flex items-center justify-center bg-gray-900 aspect-video">
                @if($this->lesson->video_url)
                    @php
                        $videoId = $this->getVideoId($this->lesson->video_url);
                    @endphp
                    @if($videoId)
                        <iframe
                            src="https://www.youtube.com/embed/{{ $videoId }}"
                            class="w-full h-full"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen>
                        </iframe>
                    @else
                        <video controls class="w-full h-full">
                            <source src="{{ $this->lesson->video_url }}" type="video/mp4">
                        </video>
                    @endif
                @else
                    <div class="text-center text-gray-500">
                        <x-icon name="o-video-camera" class="w-16 h-16 mx-auto mb-3 text-gray-600" />
                        <p>Video kommt bald</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Content -->
        <div class="mb-6 border-0">
            <div class="prose max-w-none">
                {!! nl2br($this->lesson->content) !!}
            </div>
        </div>

        <!-- Notes & Actions -->
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Notes -->
            <x-card title="📝 Meine Notizen" class="border-0 shadow-sm">
                <x-textarea
                    wire:model="notes"
                    placeholder="Schreibe deine Notizen hier..."
                    rows="5"
                    class="font-mono text-sm" />
                <x-slot:actions>
                    <x-button
                        wire:click="saveNotes"
                        loading="lazy"
                        spinner="saveNotes"
                        class="btn-primary btn-sm">
                        Notizen speichern
                    </x-button>
                </x-slot:actions>
            </x-card>

            <!-- Actions -->
            <x-card title="⚡ Aktionen" class="border-0 shadow-sm">
                <div class="space-y-4">
                    @if(!$this->isCompleted)
                        <x-button
                            wire:click="markComplete"
                            loading="lazy"
                            class="w-full btn-success"
                            icon="o-check-circle"
                            spinner="markComplete">
                            Lektion abschließen ✓
                        </x-button>
                    @else
                        <div class="p-4 text-center rounded-lg bg-green-50">
                            <x-icon name="o-check-circle" class="w-8 h-8 mx-auto mb-2 text-green-600" />
                            <p class="font-medium text-green-700">Lektion abgeschlossen!</p>
                            <p class="mt-1 text-xs text-green-600">Weiter so! 🎉</p>
                        </div>
                    @endif

                    <!-- Quiz Button -->
                    @if($this->quiz)
                        <div class="pt-4 mt-2 border-t">
                            <h4 class="flex items-center gap-2 mb-2 font-medium text-gray-900">
                                <x-icon name="o-document-text" class="w-5 h-5 text-[#FF6B35]" />
                                Quiz
                            </h4>
                            <p class="mb-3 text-sm text-gray-600">
                                Teste dein Wissen mit diesem Quiz
                            </p>

                            @if($this->hasQuizAttempt && $this->bestQuizScore)
                                @php
                                    $totalQuestions = $this->quiz->questions()->count();
                                    $percentage = $totalQuestions > 0 ? round(($this->bestQuizScore->score / $totalQuestions) * 100) : 0;
                                @endphp
                                <div class="p-3 mb-3 rounded-lg bg-gray-50">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">Beste Punktzahl</span>
                                        <span class="font-semibold {{ $percentage >= 70 ? 'text-green-600' : 'text-orange-600' }}">
                                            {{ $percentage }}% ({{ $this->bestQuizScore->score }}/{{ $totalQuestions }})
                                        </span>
                                    </div>
                                </div>
                            @endif

                            <x-button
                                link="{{ route('student.quiz.show', $this->quiz) }}"
                                class="w-full {{ $this->hasQuizAttempt ? 'btn-outline' : 'btn-primary' }}"
                                icon="o-document-text">
                                @if($this->hasQuizAttempt)
                                    Quiz wiederholen
                                @else
                                    Quiz starten →
                                @endif
                            </x-button>

                            @if($this->quiz->time_limit)
                                <p class="mt-2 text-xs text-gray-500">
                                    <x-icon name="o-clock" class="inline w-3 h-3 mr-1" />
                                    Zeitlimit: {{ $this->quiz->time_limit }} Minuten
                                </p>
                            @endif
                        </div>
                    @endif

                    <!-- Navigation -->
                    <div class="flex justify-between pt-4 mt-4 border-t">
                        @if($this->prevLesson)
                            <x-button
                                link="{{ route('student.lesson.show', ['course' => $this->lesson->course, 'lesson' => $this->prevLesson]) }}"
                                icon="o-arrow-left"
                                class="btn-ghost">
                                Vorherige
                            </x-button>
                        @else
                            <div></div>
                        @endif

                        @if($this->nextLesson)
                            <x-button
                                link="{{ route('student.lesson.show', ['course' => $this->lesson->course, 'lesson' => $this->nextLesson]) }}"
                                icon="o-arrow-right"
                                icon-right="o-arrow-right"
                                class="btn-primary">
                                Nächste Lektion
                            </x-button>
                        @else
                            <x-button
                                link="{{ route('student.course.show', $this->lesson->course) }}"
                                class="btn-outline">
                                Zum Kurs zurück
                            </x-button>
                        @endif
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Note MVP -->
        <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="font-medium text-blue-800">MVP Version</p>
                    <p class="text-sm text-blue-700">Prochaines fonctionnalités : quiz interactifs, signets, suivi du temps et ressources téléchargeables.</p>
                </div>
            </div>
        </div>
    </div>

</div>
