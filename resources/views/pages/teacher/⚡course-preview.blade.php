<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;

new
#[Title('Preview Course - Teacher')]
#[Layout('layouts.app')]
class extends Component {

    public Course $course;
    public string $activeTab = 'overview';

    public function mount(Course $course): void
    {
        if ($course->teacher_id != auth()->id()) {
            abort(403);
        }
        $this->course = $course->load(['subject', 'teacher', 'lessons' => function($q) {
            $q->orderBy('order')->with('quiz');
        }]);
    }

    public function getAverageRatingProperty()
    {
        return round($this->course->reviews()->avg('rating') ?? 0, 1);
    }

    public function getReviewsCountProperty()
    {
        return $this->course->reviews()->count();
    }

    public function getReviewsProperty()
    {
        return $this->course->reviews()->with('user')->latest()->take(5)->get();
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

    public function getLevelBadgeClass($level): string
    {
        return match($level) {
            'A1', 'A2' => 'badge-success',
            'B1', 'B2' => 'badge-warning',
            'C1', 'C2' => 'badge-error',
            default => 'badge-ghost',
        };
    }

    public function getWhatYouWillLearnProperty()
    {
        $data = $this->course->what_you_will_learn;
        if (is_array($data)) return $data;
        return json_decode($data, true) ?? [];
    }

    public function getRequirementsProperty()
    {
        $data = $this->course->requirements;
        if (is_array($data)) return $data;
        return json_decode($data, true) ?? [];
    }

    public function render()
    {
        return $this->view([
            'averageRating' => $this->averageRating,
            'reviewsCount' => $this->reviewsCount,
            'reviews' => $this->reviews,
            'whatYouWillLearn' => $this->whatYouWillLearn,
            'requirements' => $this->requirements,
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

        {{-- Preview banner --}}
        <div class="p-3 mb-6 border rounded-lg bg-warning/10 border-warning/20">
            <div class="flex items-center gap-3">
                <x-icon name="o-eye" class="w-5 h-5 text-warning" />
                <div>
                    <p class="font-medium text-warning">{{ __('Preview mode') }}</p>
                    <p class="text-sm text-warning/80">{{ __('This is how students will see your course. To edit, go back to course management.') }}</p>
                </div>
                <div class="flex-1"></div>
                <x-button label="{{ __('Edit course') }}" icon="o-pencil" class="btn-outline btn-sm" link="{{ route('teacher.courses.edit', $course) }}" />
            </div>
        </div>

        {{-- Hero Section --}}
        <div class="p-4 mb-6 text-white bg-gradient-to-r from-primary to-secondary rounded-2xl md:p-6">
            <div class="flex flex-col gap-6 lg:flex-row">
                <div class="flex-1">
                    <div class="flex flex-wrap gap-2 mb-3">
                        @if($course->subject)
                            <x-badge :value="$course->subject->name" class="badge-soft badge-white" />
                        @endif
                        <x-badge :value="$this->getLevelLabel($course->level)" :class="$this->getLevelBadgeClass($course->level) . ' badge-soft text-white'" />
                        @if($course->price == 0)
                            <x-badge value="🇩🇪 Free" class="badge-success badge-soft" />
                        @endif
                    </div>
                    <h1 class="mb-2 text-xl font-bold md:text-3xl">{{ $course->title }}</h1>
                    <p class="mb-3 text-sm text-white/90 md:text-base">{{ $course->short_description }}</p>
                    <div class="flex flex-wrap gap-4 text-xs md:text-sm text-white/80">
                        <span class="flex items-center gap-1"><x-icon name="o-user-group" class="w-4 h-4" />{{ number_format($course->enrollments_count ?? 0) }} {{ __('students') }}</span>
                        <span class="flex items-center gap-1"><x-icon name="o-book-open" class="w-4 h-4" />{{ $course->lessons_count ?? 0 }} {{ __('lessons') }}</span>
                        <span class="flex items-center gap-1"><x-icon name="o-star" class="w-4 h-4" />{{ number_format($this->averageRating, 1) }} ({{ $reviewsCount }} {{ __('reviews') }})</span>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-4 min-w-[200px] shadow-lg">
                    @if($course->price > 0)
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900">{{ number_format($course->price, 0, ',', ' ') }} €</div>
                            <div class="mt-3">
                                <x-button label="{{ __('Buy now') }} →" icon="o-credit-card" class="w-full btn-primary" disabled />
                            </div>
                            <p class="mt-2 text-xs text-gray-500">{{ __('This is a preview, purchase is disabled') }}</p>
                        </div>
                    @else
                        <div class="text-center">
                            <div class="text-2xl font-bold text-success">{{ __('Free') }}</div>
                            <div class="mt-3">
                                <x-button label="{{ __('Enroll now') }} →" icon="o-check-circle" class="w-full btn-primary" disabled />
                            </div>
                            <p class="mt-2 text-xs text-gray-500">{{ __('Preview mode – enrollment disabled') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabs Navigation --}}
        <div class="mb-6 tabs tabs-boxed">
            <a class="tab {{ $activeTab === 'overview' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'overview')">📖 {{ __('Overview') }}</a>
            <a class="tab {{ $activeTab === 'curriculum' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'curriculum')">📚 {{ __('Curriculum') }} ({{ $course->lessons_count ?? 0 }})</a>
            <a class="tab {{ $activeTab === 'reviews' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'reviews')">⭐ {{ __('Reviews') }} ({{ $reviewsCount }})</a>
        </div>

        {{-- Tab: Overview --}}
        @if($activeTab === 'overview')
            <x-card class="shadow-sm">
                <div class="space-y-6">
                    <div>
                        <h3 class="mb-2 text-lg font-semibold">{{ __('Course description') }}</h3>
                        <div class="prose max-w-none">{!! nl2br(e($course->description)) !!}</div>
                    </div>
                    @if(count($whatYouWillLearn) > 0)
                        <div class="p-4 rounded-lg bg-base-200">
                            <h3 class="mb-2 text-lg font-semibold">✨ {{ __('What you will learn') }}</h3>
                            <ul class="grid gap-2 md:grid-cols-2">
                                @foreach($whatYouWillLearn as $item)
                                    <li class="flex items-center gap-2 text-sm"><x-icon name="o-check-circle" class="w-4 h-4 text-success" />{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if(count($requirements) > 0)
                        <div class="p-4 rounded-lg bg-base-200">
                            <h3 class="mb-2 text-lg font-semibold">📋 {{ __('Requirements') }}</h3>
                            <ul class="space-y-1">
                                @foreach($requirements as $req)
                                    <li class="flex items-center gap-2 text-sm"><x-icon name="o-arrow-right" class="w-3 h-3 text-primary" />{{ $req }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="p-4 rounded-lg bg-gradient-to-r from-base-200 to-base-100">
                        <h3 class="flex items-center gap-2 text-lg font-semibold"><x-icon name="o-user-circle" class="w-5 h-5 text-primary" />{{ __('Your instructor') }}</h3>
                        <div class="flex items-center gap-3 mt-2">
                            <div class="flex items-center justify-center w-12 h-12 text-lg font-bold rounded-full bg-primary/20 text-primary">{{ strtoupper(substr($course->teacher->name, 0, 1)) }}</div>
                            <div><div class="font-semibold">{{ $course->teacher->name }}</div><div class="text-sm text-base-content/60">{{ $course->teacher->bio ?? __('German teacher') }}</div></div>
                        </div>
                    </div>
                </div>
            </x-card>
        @endif

        {{-- Tab: Curriculum --}}
        @if($activeTab === 'curriculum')
            <x-card class="shadow-sm">
                <div class="space-y-3">
                    @forelse($course->lessons as $index => $lesson)
                        <div class="p-3 transition border rounded-lg hover:bg-base-200">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="flex items-center flex-1 gap-3">
                                    <x-icon name="o-play-circle" class="w-5 h-5 text-primary" />
                                    <div class="flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs text-base-content/60">{{ __('Lesson') }} {{ $index + 1 }}</span>
                                            <span class="font-medium">{{ $lesson->title }}</span>
                                            @if($lesson->quiz)
                                                <x-badge value="Quiz" icon="o-document-text" class="badge-soft badge-purple" />
                                            @endif
                                        </div>
                                        @if($lesson->description)
                                            <p class="mt-1 text-xs text-base-content/60">{{ Str::limit($lesson->description, 100) }}</p>
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
                            <p class="mt-2 text-base-content/60">{{ __('No lessons yet. Add lessons from course management.') }}</p>
                            <x-button label="{{ __('Manage lessons') }}" icon="o-plus" class="mt-4 btn-primary" link="{{ route('teacher.lessons.index', $course) }}" />
                        </div>
                    @endforelse
                </div>
            </x-card>
        @endif

        {{-- Tab: Reviews --}}
        @if($activeTab === 'reviews')
            <x-card class="shadow-sm">
                @if($reviewsCount > 0)
                    <div class="p-4 mb-5 rounded-lg bg-base-200">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="text-center sm:text-left">
                                <div class="text-3xl font-bold">{{ number_format($this->averageRating, 1) }}</div>
                                <div class="flex justify-center mt-1 text-warning sm:justify-start">@for($i=1;$i<=5;$i++)<x-icon name="o-star" class="w-4 h-4 {{ $i <= $this->averageRating ? 'text-warning' : 'text-base-content/30' }}" />@endfor</div>
                                <div class="text-xs text-base-content/60">{{ $reviewsCount }} {{ __('reviews') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        @foreach($reviews as $review)
                            <div class="p-3 border rounded-lg">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center justify-center w-8 h-8 text-sm font-bold rounded-full bg-primary/20 text-primary">{{ strtoupper(substr($review->user->name, 0, 1)) }}</div>
                                        <div><div class="font-medium">{{ $review->user->name }}</div><div class="flex text-warning">@for($i=1;$i<=5;$i++)<x-icon name="o-star" class="w-3 h-3 {{ $i <= $review->rating ? 'text-warning' : 'text-base-content/30' }}" />@endfor</div></div>
                                    </div>
                                    <div class="text-xs text-base-content/50">{{ $review->created_at->diffForHumans() }}</div>
                                </div>
                                @if($review->comment)<p class="mt-2 text-sm">{{ $review->comment }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-12 text-center">
                        <x-icon name="o-star" class="w-16 h-16 mx-auto text-base-content/30" />
                        <p class="mt-2 text-base-content/60">{{ __('No reviews yet') }}</p>
                    </div>
                @endif
            </x-card>
        @endif
    </div>
</div>
