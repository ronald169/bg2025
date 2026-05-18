<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Course;
use App\Models\Enrollment;
use Mary\Traits\Toast;

new
#[Title('Course Details')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public Course $course;
    public string $activeTab = 'overview';

    public function mount(Course $course): void
    {
        $this->course = $course->load(['subject', 'teacher']);
    }

    public function getIsEnrolledProperty(): bool
    {
        return Enrollment::where('user_id', auth()->id())
            ->where('course_id', $this->course->id)
            ->exists();
    }

    public function getEnrollmentProgressProperty(): int
    {
        $enrollment = Enrollment::where('user_id', auth()->id())
            ->where('course_id', $this->course->id)
            ->first();

        return $enrollment?->progress ?? 0;
    }

    public function getTotalQuizzesProperty(): int
    {
        return $this->course->lessons()
            ->whereHas('quiz', fn($q) => $q->where('is_published', true))
            ->count();
    }

    public function getLessonsProperty()
    {
        return $this->course->lessons()
            ->where('is_published', true)
            ->orderBy('order')
            ->get();
    }

    public function getFirstLessonProperty()
    {
        return $this->course->lessons()
            ->where('is_published', true)
            ->orderBy('order')
            ->first();
    }

    public function getWhatYouWillLearnProperty(): array
    {
        $data = $this->course->what_you_will_learn;
        if (is_array($data)) return $data;
        return json_decode($data, true) ?? [];
    }

    public function getRequirementsProperty(): array
    {
        $data = $this->course->requirements;
        if (is_array($data)) return $data;
        return json_decode($data, true) ?? [];
    }

    public function getLevelLabel(string $level): string
    {
        return [
            'A1' => 'A1 - Beginner',
            'A2' => 'A2 - Elementary',
            'B1' => 'B1 - Intermediate',
            'B2' => 'B2 - Upper Intermediate',
            'C1' => 'C1 - Advanced',
            'C2' => 'C2 - Mastery',
        ][$level] ?? $level;
    }

    public function getLevelBadgeClass(string $level): string
    {
        return match($level) {
            'A1', 'A2' => 'badge-success',
            'B1', 'B2' => 'badge-warning',
            'C1', 'C2' => 'badge-error',
            default => 'badge-ghost',
        };
    }

    public function enroll(): void
    {
        if ($this->isEnrolled) {
            $this->warning(__('You are already enrolled in this course.'));
            return;
        }

        Enrollment::create([
            'user_id'    => auth()->id(),
            'course_id'  => $this->course->id,
            'enrolled_at'=> now(),
            'progress'   => 0,
            'status'     => 'active',
        ]);

        $this->success(__('Successfully enrolled in :course', ['course' => $this->course->title]));
    }

    public function continueLearning()
    {
        if (!$this->firstLesson) {
            $this->warning(__('No lessons available yet.'));
            return;
        }

        return $this->redirectRoute('student.lesson.show', [
            'course' => $this->course,
            'lesson' => $this->firstLesson,
        ]);
    }

    public function render()
    {
        return $this->view([
            'lessons'            => $this->lessons,
            'firstLesson'        => $this->firstLesson,
            'isEnrolled'         => $this->isEnrolled,
            'enrollmentProgress' => $this->enrollmentProgress,
            'totalQuizzes'       => $this->totalQuizzes,
            'whatYouWillLearn'   => $this->whatYouWillLearn,
            'requirements'       => $this->requirements,
            'levelLabel'         => $this->getLevelLabel($this->course->level),
            'levelBadgeClass'    => $this->getLevelBadgeClass($this->course->level),
        ]);
    }
};

?>

<div class="py-4 md:py-8">
    <div class="max-w-6xl px-3 mx-auto md:px-4">

        {{-- Fil d’Ariane --}}
        <div class="mb-5">
            <a href="{{ route('student.catalog') }}" wire:navigate
               class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to catalog') }}
            </a>
        </div>

        {{-- Hero Section --}}
        <div class="bg-gradient-to-r from-[#FF6B35] to-[#1E6091] rounded-2xl p-4 md:p-6 text-white mb-6">
            <div class="flex flex-col gap-6 lg:flex-row">
                <div class="flex-1">
                    {{-- Badges --}}
                    <div class="flex flex-wrap gap-2 mb-3">
                        @if($course->subject)
                            <x-badge :value="$course->subject->name" class="badge-soft badge-white" />
                        @endif
                        <x-badge :value="$levelLabel" :class="$levelBadgeClass . ' badge-soft '" />
                        @if($course->price == 0)
                            <x-badge value="🇩🇪 Free" class="badge-success badge-soft" />
                        @endif
                    </div>

                    <h1 class="mb-2 text-xl font-bold md:text-3xl">{{ $course->title }}</h1>
                    <p class="mb-3 text-sm text-white/90 md:text-base">{{ $course->short_description }}</p>

                    {{-- Stats --}}
                    <div class="flex flex-wrap gap-4 text-xs md:text-sm text-white/80">
                        <div class="flex items-center gap-1">
                            <x-icon name="o-user-group" class="w-4 h-4" />
                            <span>{{ number_format($course->enrollments_count ?? 0) }} {{ __('students') }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <x-icon name="o-book-open" class="w-4 h-4" />
                            <span>{{ $course->lessons_count ?? 0 }} {{ __('lessons') }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <x-icon name="o-document-text" class="w-4 h-4" />
                            <span>{{ $totalQuizzes }} {{ __('quizzes') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Action Card --}}
                <div class="bg-white rounded-xl p-4 min-w-[200px] shadow-lg">
                    @if($course->price > 0)
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900">{{ number_format($course->price, 0, ',', ' ') }} €</div>
                            @if($isEnrolled)
                                <div class="mt-3">
                                    <div class="text-xs text-gray-600">{{ __('Your progress') }}: {{ round($enrollmentProgress) }}%</div>
                                    <div class="w-full h-1.5 bg-gray-200 rounded-full mt-1">
                                        <div class="h-1.5 rounded-full bg-primary" style="width: {{ $enrollmentProgress }}%"></div>
                                    </div>
                                </div>
                                <x-button label="{!! __('Continue learning') !!}" icon="o-play-circle"
                                    class="w-full mt-3 btn-primary" wire:click="continueLearning" />
                            @else
                                <x-button label="{{ __('Buy now') }}" icon="o-credit-card"
                                    class="w-full mt-3 btn-primary"
                                    link="{{ route('payment.checkout', $course) }}" />
                                <p class="mt-2 text-xs text-center text-gray-500">
                                    <x-icon name="o-shield-check" class="inline w-3 h-3 mr-1" />
                                    {{ __('Secure payment') }}
                                </p>
                            @endif
                        </div>
                    @else
                        <div class="text-center">
                            <div class="text-2xl font-bold text-success">{{ __('Free') }}</div>
                            @if($isEnrolled)
                                <div class="mt-3">
                                    <div class="text-xs text-gray-600">{{ __('Progress') }}: {{ round($enrollmentProgress) }}%</div>
                                    <div class="w-full h-1.5 bg-gray-200 rounded-full mt-1">
                                        <div class="h-1.5 rounded-full bg-primary" style="width: {{ $enrollmentProgress }}%"></div>
                                    </div>
                                </div>
                                <x-button label="{!! __('Continue learning') !!}" icon="o-play-circle"
                                    class="w-full mt-3 btn-primary" wire:click="continueLearning" />
                            @else
                                <x-button label="{!! __('Enroll now') !!}" icon="o-check-circle"
                                    class="w-full mt-3 btn-primary" wire:click="enroll" spinner />
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabs Navigation --}}
        <div class="mb-5 tabs tabs-boxed">
            <a class="tab {{ $activeTab === 'overview' ? 'tab-active' : '' }}"
               wire:click="$set('activeTab', 'overview')">
                📖 {{ __('Overview') }}
            </a>
            <a class="tab {{ $activeTab === 'curriculum' ? 'tab-active' : '' }}"
               wire:click="$set('activeTab', 'curriculum')">
                📚 {{ __('Curriculum') }} ({{ $course->lessons_count ?? 0 }})
            </a>
        </div>

        {{-- Tab Content --}}
        <x-card class="p-4 md:p-6">
            @if($activeTab === 'overview')
                <div class="prose max-w-none">
                    <div class="mb-5">
                        <h3 class="text-lg font-semibold">{{ __('Course description') }}</h3>
                        <div class="mt-2 text-base-content/80">
                            {!! nl2br(e($course->description)) !!}
                        </div>
                    </div>

                    @if(count($whatYouWillLearn) > 0)
                        <div class="p-4 mb-5 bg-base-200 rounded-xl">
                            <h3 class="text-lg font-semibold">✨ {{ __('What you will learn') }}</h3>
                            <ul class="grid gap-2 mt-2 md:grid-cols-2">
                                @foreach($whatYouWillLearn as $item)
                                    <li class="flex items-center gap-2 text-sm">
                                        <x-icon name="o-check-circle" class="w-4 h-4 text-success" />
                                        {{ $item }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(count($requirements) > 0)
                        <div class="p-4 mb-5 bg-base-200 rounded-xl">
                            <h3 class="text-lg font-semibold">📋 {{ __('Requirements') }}</h3>
                            <ul class="mt-2 space-y-1">
                                @foreach($requirements as $req)
                                    <li class="flex items-center gap-2 text-sm">
                                        <x-icon name="o-arrow-right" class="w-3 h-3 text-primary" />
                                        {{ $req }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="p-4 bg-gradient-to-r from-base-200 to-base-100 rounded-xl">
                        <h3 class="flex items-center gap-2 text-lg font-semibold">
                            <x-icon name="o-user-circle" class="w-5 h-5 text-primary" />
                            {{ __('Your instructor') }}
                        </h3>
                        <div class="flex items-center gap-3 mt-2">
                            <div class="flex items-center justify-center w-12 h-12 text-lg font-bold rounded-full bg-primary/20 text-primary">
                                {{ strtoupper(substr($course->teacher->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-semibold">{{ $course->teacher->name }}</div>
                                <div class="text-sm text-base-content/60">{{ $course->teacher->bio ?? __('German teacher') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

            @elseif($activeTab === 'curriculum')
                <div class="space-y-3">
                    @forelse($lessons as $index => $lesson)
                        <div class="p-3 transition border rounded-lg hover:bg-base-200">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="flex items-center flex-1 gap-3">
                                    @if($isEnrolled || $lesson->is_free)
                                        <a href="{{ route('student.lesson.show', ['course' => $course, 'lesson' => $lesson]) }}"
                                           class="text-primary hover:text-primary-focus">
                                            <x-icon name="o-play-circle" class="w-5 h-5" />
                                        </a>
                                    @else
                                        <x-icon name="o-lock-closed" class="w-4 h-4 text-base-content/40" />
                                    @endif
                                    <div class="flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs text-base-content/60">{{ __('Lesson') }} {{ $index + 1 }}</span>
                                            @if($isEnrolled || $lesson->is_free)
                                                <a href="{{ route('student.lesson.show', ['course' => $course, 'lesson' => $lesson]) }}"
                                                   class="font-medium hover:text-primary">
                                                    {{ $lesson->title }}
                                                </a>
                                            @else
                                                <span class="font-medium text-base-content/80">{{ $lesson->title }}</span>
                                            @endif
                                            @if($lesson->quiz)
                                                <x-badge value="Quiz" icon="o-document-text" class="badge-soft badge-purple" />
                                            @endif
                                            @if($lesson->is_free)
                                                <x-badge value="Free" class="badge-soft badge-success" />
                                            @endif
                                        </div>
                                        @if($lesson->description)
                                            <p class="mt-1 text-xs text-base-content/60">{{ clean_text($lesson->description, 100) }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 text-xs text-base-content/60">
                                    <x-icon name="o-clock" class="w-3 h-3" />
                                    <span>{{ $lesson->duration ?? '15 min' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <x-icon name="o-book-open" class="w-12 h-12 mx-auto text-base-content/30" />
                            <p class="mt-2 text-base-content/60">{{ __('No lessons available yet') }}</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </x-card>

    </div>
</div>
