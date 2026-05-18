<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;
use Illuminate\Support\Str;

new
#[Layout('components.layouts.guest')]
class extends Component {
    use Toast;

    public Course $course;

    #[Url(as: 'tab', history: true)]
    public string $activeTab = 'overview';

    public function mount(Course $course): void
    {
        $this->course = $course->load(['subject', 'teacher', 'reviews.user']);

        //$this->fill($this->course);
    }

    #[Computed]
    public function isEnrolled()
    {
        return Enrollment::where('user_id', auth()->id())
            ->where('course_id', $this->course->id)
            ->exists();
    }

    #[Computed]
    public function enrollmentProgress()
    {
        $enrollment = Enrollment::where('user_id', auth()->id())
            ->where('course_id', $this->course->id)
            ->first();

        return $enrollment->progress ?? 0;
    }

    #[Computed]
    public function averageRating()
    {
        return round($this->course->reviews()->avg('rating') ?? 0, 1);
    }

    #[Computed]
    public function reviewsCount()
    {
        return $this->course->reviews()->count();
    }

    #[Computed]
    public function totalQuizzes()
    {
        return $this->course->lessons()
            ->whereHas('quiz', function($q) {
                $q->where('is_published', true);
            })
            ->count();
    }

    #[Computed]
    public function lessons()
    {
        return $this->course->lessons()
            ->where('is_published', true)
            ->orderBy('order')
            ->get();
    }

    #[Computed]
    public function reviews()
    {
        return $this->course->reviews()
            ->with('user')
            ->latest()
            ->take(10)
            ->get();
    }

    #[Computed]
    public function firstLesson()
    {
        return $this->course->lessons()
            ->where('is_published', true)
            ->orderBy('order')
            ->first();
    }

    // Méthodes pour récupérer les données formatées
    public function getWhatYouWillLearn()
    {
        if (is_array($this->course->what_you_will_learn)) {
            return $this->course->what_you_will_learn;
        }
        return json_decode($this->course->what_you_will_learn, true) ?? [];
    }

    public function getRequirements()
    {
        if (is_array($this->course->requirements)) {
            return $this->course->requirements;
        }
        return json_decode($this->course->requirements, true) ?? [];
    }

    public function enroll(): void
    {
        if ($this->isEnrolled) {
            $this->warning(__('You are already enrolled in this course'));
            return;
        }

        Enrollment::create([
            'user_id' => auth()->id(),
            'course_id' => $this->course->id,
            'enrolled_at' => now(),
            'progress' => 0,
            'status' => 'active'
        ]);

        $this->success(__('Successfully enrolled in :course', ['course' => $this->course->title]));
    }

    public function continueLearning()
    {
        if (!$this->firstLesson) {
            $this->warning('Noch keine Lektionen verfügbar.');
            return;
        }

        return redirect()->route('student.lesson.show', [
            'course' => $this->course,
            'lesson' => $this->firstLesson
        ]);
    }

    public function getLevelBadgeColor($level): string
    {
        return match($level) {
            'A1', 'A2' => 'bg-green-100 text-green-700',
            'B1', 'B2' => 'bg-orange-100 text-orange-700',
            'C1', 'C2' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function getLevelLabel($level): string
    {
        $levels = [
            'A1' => 'A1 - Débutant',
            'A2' => 'A2 - Élémentaire',
            'B1' => 'B1 - Intermédiaire',
            'B2' => 'B2 - Avancé',
            'C1' => 'C1 - Expérimenté',
            'C2' => 'C2 - Maîtrise'
        ];
        return $levels[$level] ?? $level;
    }
    
}
?>

@section('meta_title', $this->course->meta_title ?? $this->course->title . ' - sasadf ' . config('app.name'))
@section('meta_description', $this->course->meta_description ?? Str::limit(strip_tags($this->course->description ?? ''), 160))
@section('meta_keywords', $this->course->meta_keywords ?? 'German course, learn German, ' . ($this->course->level ?? 'A1'))
@section('og_title', $this->course->og_title ?? $this->course->title)
@section('og_description', $this->course->og_description ?? strip_tags($this->course->description))
@section('og_image', $this->course->og_image ?? ($this->course->thumbnail ? asset('storage/' . $this->course->thumbnail) : asset('images/og-image.jpg')))
@section('canonical_url', $this->course->canonical_url ?? url()->current())
@section('meta_robots', $this->course->robots ?? 'index,follow')

@push('structured_data')
@php
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'Course',
        'name' => $this->course->title,
        'description' => strip_tags($this->course->description ?? ''),
        'provider' => [
            '@type' => 'Organization',
            'name' => config('app.name'),
        ],
        'hasCourseInstance' => [
            '@type' => 'CourseInstance',
            'courseMode' => 'online',
            'language' => 'de',
        ],
    ];
@endphp
<script type="application/ld+json">
    {!! json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

<div class="py-4 md:py-8">
    
    <div class="max-w-6xl px-3 mx-auto md:px-4">

        <!-- Navigation -->
        <div class="mb-5">
            <a href="{{ route('student.catalog') }}"
               class="inline-flex items-center gap-1 text-sm text-[#FF6B35] hover:text-[#E55A2A] transition">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to catalog') }}
            </a>
        </div>

        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-[#FF6B35] to-[#1E6091] rounded-2xl p-4 md:p-6 lg:p-8 text-white mb-6">
            <div class="flex flex-col gap-6 lg:flex-row">
                <div class="flex-1">
                    <!-- Badges -->
                    <div class="flex flex-wrap gap-2 mb-3">
                        @if($this->course->subject)
                            <span class="px-2 py-0.5 text-xs rounded-full bg-white/20">
                                {{ $this->course->subject->name }}
                            </span>
                        @endif
                        <span class="px-2 py-0.5 text-xs rounded-full bg-white/20">
                            {{ $this->getLevelLabel($this->course->level) }}
                        </span>
                        @if($this->course->price == 0)
                            <span class="px-2 py-0.5 text-xs rounded-full bg-green-500/80">
                                🇩🇪 {{ __('Free') }}
                            </span>
                        @endif
                    </div>

                    <h1 class="mb-2 text-xl font-bold md:text-2xl lg:text-3xl">{{ $this->course->title }}</h1>
                    <p class="mb-3 text-sm text-white/90 md:text-base">{{ $this->course->short_description }}</p>

                    <!-- Stats -->
                    <div class="flex flex-wrap gap-3 text-xs md:text-sm text-white/80">
                        <div class="flex items-center gap-1">
                            <x-icon name="o-user-group" class="w-3 h-3 md:w-4 md:h-4" />
                            <span>{{ number_format($this->course->enrollments_count ?? 0) }} {{ __('students') }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <x-icon name="o-book-open" class="w-3 h-3 md:w-4 md:h-4" />
                            <span>{{ $this->course->lessons_count ?? 0 }} {{ __('lessons') }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <x-icon name="o-document-text" class="w-3 h-3 md:w-4 md:h-4" />
                            <span>{{ $this->totalQuizzes }} {{ __('quizzes') }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="flex">
                                @for($i = 1; $i <= 5; $i++)
                                    <x-icon name="o-star" class="w-3 h-3 md:w-4 md:h-4"
                                            :class="$i <= $this->averageRating ? 'text-yellow-400' : 'text-white/30'" />
                                @endfor
                            </div>
                            <span class="ml-1">({{ $this->reviewsCount }} {{ __('reviews') }})</span>
                        </div>
                    </div>
                </div>

                <!-- Action Card -->
                <div class="bg-white rounded-xl p-4 min-w-[180px] md:min-w-[220px] shadow-lg">
                    @if($this->course->price > 0)
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900 md:text-3xl">
                                {{ number_format($this->course->price, 0, ',', ' ') }} €
                            </div>
                            @if($this->isEnrolled)
                                <div class="mt-3">
                                    <div class="mb-1 text-xs text-gray-600 md:text-sm">{{ __('Your progress') }}: {{ round($this->enrollmentProgress) }}%</div>
                                    <div class="w-full h-1.5 bg-gray-200 rounded-full">
                                        <div class="h-1.5 rounded-full bg-[#FF6B35]" style="width: {{ $this->enrollmentProgress }}%"></div>
                                    </div>
                                </div>
                                <a href="{{ route('student.lesson.show', ['course' => $this->course, 'lesson' => $this->firstLesson]) }}" 
                                   class="inline-flex items-center justify-center w-full mt-3 px-3 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:from-[#E55A2A] hover:to-[#FF6B35] transition">
                                    <x-icon name="o-play-circle" class="w-4 h-4 mr-1" />
                                    {{ __('Continue learning') }} →
                                </a>
                            @else
                                <a href="{{ route('payment.checkout', $this->course) }}" 
                                   class="inline-flex items-center justify-center w-full mt-3 px-3 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:from-[#E55A2A] hover:to-[#FF6B35] transition">
                                    <x-icon name="o-credit-card" class="w-4 h-4 mr-1" />
                                    {{ __('Buy now') }} - {{ number_format($this->course->price, 0, ',', ' ') }} €
                                </a>
                                <p class="mt-2 text-xs text-center text-gray-500">
                                    <x-icon name="o-shield-check" class="inline w-3 h-3 mr-1" />
                                    {{ __('Secure payment') }}
                                </p>
                            @endif
                        </div>
                    @else
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600 md:text-3xl">{{ __('Free') }}</div>
                            @if($this->isEnrolled)
                                <div class="mt-3">
                                    <div class="mb-1 text-xs text-gray-600 md:text-sm">{{ __('Progress') }}: {{ round($this->enrollmentProgress) }}%</div>
                                    <div class="w-full h-1.5 bg-gray-200 rounded-full">
                                        <div class="h-1.5 rounded-full bg-[#FF6B35]" style="width: {{ $this->enrollmentProgress }}%"></div>
                                    </div>
                                </div>
                                <a href="{{ route('student.lesson.show', ['course' => $this->course, 'lesson' => $this->firstLesson]) }}" 
                                   class="inline-flex items-center justify-center w-full mt-3 px-3 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:from-[#E55A2A] hover:to-[#FF6B35] transition">
                                    <x-icon name="o-play-circle" class="w-4 h-4 mr-1" />
                                    {{ __('Continue learning') }} →
                                </a>
                            @else
                                <button wire:click="enroll" 
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center justify-center w-full mt-3 px-3 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:from-[#E55A2A] hover:to-[#FF6B35] transition">
                                    <span wire:loading.remove><x-icon name="o-check-circle" class="w-4 h-4 mr-1" />{{ __('Enroll now') }}</span>
                                    <span wire:loading><x-icon name="custom.spinner" class="w-4 h-4 mr-1 animate-spin" />{{ __('Processing...') }}</span>
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="mb-5 border-b border-gray-200">
            <nav class="flex flex-wrap gap-2 sm:gap-4">
                <button wire:click="$set('activeTab', 'overview')"
                        class="px-3 py-2 text-sm font-medium rounded-lg transition
                               {{ $activeTab === 'overview' ? 'bg-[#FF6B35] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}">
                    📖 {{ __('Overview') }}
                </button>
                <button wire:click="$set('activeTab', 'curriculum')"
                        class="px-3 py-2 text-sm font-medium rounded-lg transition
                               {{ $activeTab === 'curriculum' ? 'bg-[#FF6B35] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}">
                    📚 {{ __('Curriculum') }} ({{ $this->course->lessons_count ?? 0 }})
                </button>
                <button wire:click="$set('activeTab', 'reviews')"
                        class="px-3 py-2 text-sm font-medium rounded-lg transition
                               {{ $activeTab === 'reviews' ? 'bg-[#FF6B35] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}">
                    ⭐ {{ __('Reviews') }} ({{ $this->reviewsCount }})
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-4 bg-white shadow-sm rounded-xl md:p-6">
            @if($activeTab === 'overview')
                <div class="prose max-w-none">
                    <div class="mb-5">
                        <h3 class="mb-2 text-base font-semibold text-gray-900 md:text-lg">{{ __('Course description') }}</h3>
                        <div class="text-sm leading-relaxed text-gray-700 md:text-base">
                            {!! nl2br(e($this->course->description)) !!}
                        </div>
                    </div>

                    @if(!empty($whatYouWillLearn))
                        <div class="p-3 mb-5 md:p-4 bg-gray-50 rounded-xl">
                            <h3 class="mb-2 text-base font-semibold text-gray-900 md:text-lg">✨ {{ __('What you will learn') }}</h3>
                            <ul class="grid gap-2 md:grid-cols-2">
                                @foreach($whatYouWillLearn as $item)
                                    <li class="flex items-center gap-2 text-sm text-gray-700">
                                        <x-icon name="o-check-circle" class="flex-shrink-0 w-4 h-4 text-green-600" />
                                        {{ $item }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(!empty($requirements))
                        <div class="p-3 mb-5 md:p-4 bg-gray-50 rounded-xl">
                            <h3 class="mb-2 text-base font-semibold text-gray-900 md:text-lg">📋 {{ __('Requirements') }}</h3>
                            <ul class="space-y-1">
                                @foreach($requirements as $req)
                                    <li class="flex items-center gap-2 text-sm text-gray-700">
                                        <x-icon name="o-arrow-right" class="w-3 h-3 text-[#FF6B35] flex-shrink-0" />
                                        {{ $req }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="p-3 border md:p-4 bg-gradient-to-r from-gray-50 to-white rounded-xl">
                        <h3 class="flex items-center gap-2 mb-2 text-base font-semibold text-gray-900 md:text-lg">
                            <x-icon name="o-user-circle" class="w-5 h-5 text-[#FF6B35]" />
                            {{ __('Your instructor') }}
                        </h3>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-base md:text-lg">
                                {{ strtoupper(substr($this->course->teacher->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-900 md:text-base">{{ $this->course->teacher->name }}</div>
                                <div class="text-xs text-gray-500 md:text-sm">{{ $this->course->teacher->bio ?? __('German teacher') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

            @elseif($activeTab === 'curriculum')
                <div class="space-y-2">
                    @forelse($this->lessons as $index => $lesson)
                        <div class="p-3 transition border rounded-lg md:p-4 hover:bg-gray-50">
                            <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                                <div class="flex items-center flex-1 gap-3">
                                    @if($this->isEnrolled || $lesson->is_free)
                                        <a href="{{ route('student.lesson.show', ['course' => $this->course, 'lesson' => $lesson]) }}"
                                           class="text-[#FF6B35] hover:text-[#E55A2A] flex-shrink-0">
                                            <x-icon name="o-play-circle" class="w-5 h-5 md:w-6 md:h-6" />
                                        </a>
                                    @else
                                        <x-icon name="o-lock-closed" class="flex-shrink-0 w-4 h-4 text-gray-400 md:w-5 md:h-5" />
                                    @endif
                                    
                                    <div class="flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs text-gray-500">{{ __('Lesson') }} {{ $index + 1 }}</span>
                                            @if($this->isEnrolled || $lesson->is_free)
                                                <a href="{{ route('student.lesson.show', ['course' => $this->course, 'lesson' => $lesson]) }}"
                                                   class="font-medium text-gray-900 hover:text-[#FF6B35] text-sm md:text-base">
                                                    {{ $lesson->title }}
                                                </a>
                                            @else
                                                <span class="text-sm font-medium text-gray-500 md:text-base">{{ $lesson->title }}</span>
                                            @endif
                                            
                                            @if($lesson->quiz)
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-xs text-purple-700 bg-purple-100 rounded-full">
                                                    <x-icon name="o-document-text" class="w-3 h-3" />
                                                    {{ __('Quiz') }}
                                                </span>
                                            @endif
                                            
                                            @if($lesson->is_free)
                                                <span class="px-1.5 py-0.5 text-xs text-green-700 bg-green-100 rounded-full">
                                                    {{ __('Free') }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($lesson->description)
                                            <p class="mt-1 text-xs text-gray-500 md:text-sm">{{ clean_text($lesson->description, 100) }}</p>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex-shrink-0 text-xs text-gray-500">
                                    <div class="flex items-center gap-1">
                                        <x-icon name="o-clock" class="w-3 h-3" />
                                        <span>{{ $lesson->duration ?? '15 min' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <x-icon name="o-book-open" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                            <p class="text-gray-500">{{ __('No lessons available yet') }}</p>
                        </div>
                    @endforelse
                </div>

            @elseif($activeTab === 'reviews')
                <div>
                    @if($this->reviewsCount > 0)
                        <div class="p-3 mb-5 md:p-4 bg-gray-50 rounded-xl">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                <div class="text-center sm:text-left">
                                    <div class="text-3xl font-bold text-gray-900 md:text-4xl">{{ $this->averageRating }}</div>
                                    <div class="flex justify-center mt-1 text-yellow-400 sm:justify-start">
                                        @for($i = 1; $i <= 5; $i++)
                                            <x-icon name="o-star" class="w-4 h-4 md:w-5 md:h-5"
                                                    :class="$i <= $this->averageRating ? 'text-yellow-400' : 'text-gray-300'" />
                                        @endfor
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500">{{ $this->reviewsCount }} {{ __('reviews') }}</div>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm text-gray-600">
                                        {{ $this->reviewsCount }} {{ __('students have reviewed this course') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 md:space-y-4">
                            @foreach($this->reviews as $review)
                                <div class="p-3 border rounded-lg md:p-4">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="flex items-center justify-center w-8 h-8 text-sm font-bold text-gray-600 bg-gray-200 rounded-full md:w-10 md:h-10 md:text-base">
                                                {{ strtoupper(substr($review->user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 md:text-base">{{ $review->user->name }}</div>
                                                <div class="flex text-yellow-400 mt-0.5">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <x-icon name="o-star" class="w-3 h-3 md:w-4 md:h-4"
                                                                :class="$i <= $review->rating ? 'text-yellow-400' : 'text-gray-300'" />
                                                    @endfor
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-400">{{ $review->created_at->diffForHumans() }}</div>
                                    </div>
                                    @if($review->comment)
                                        <p class="mt-2 text-sm text-gray-700 md:text-base">{{ $review->comment }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-10 text-center">
                            <x-icon name="o-chat-bubble-left-right" class="w-12 h-12 mx-auto mb-2 text-gray-300 md:w-16 md:h-16" />
                            <p class="text-gray-500">{{ __('No reviews yet') }}</p>
                            <p class="mt-1 text-xs text-gray-400">{{ __('Be the first to review this course') }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- MVP Note -->
        <div class="p-3 mt-6 border border-blue-200 rounded-lg md:p-4 bg-blue-50">
            <div class="flex items-start gap-2">
                <x-icon name="o-information-circle" class="w-4 h-4 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">{{ __('MVP Version') }}</p>
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('More features coming soon: download materials, certificates, and social sharing.') }}</p>
                </div>
            </div>
        </div>
        
    </div>
</div>