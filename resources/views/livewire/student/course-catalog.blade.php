<?php

namespace App\Livewire\Student;

use App\Models\Course;
use App\Models\Subject;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Course Catalog')]
#[Layout('components.layouts.guest')]
class extends Component {
    use WithPagination, Toast;

    // Filters with URL persistence
    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'subject', history: true)]
    public string $subjectFilter = '';

    #[Url(as: 'level', history: true)]
    public string $levelFilter = '';

    #[Url(as: 'price', history: true)]
    public string $priceFilter = '';

    #[Url(as: 'sort', history: true)]
    public string $sortBy = 'popular';

    #[Url(as: 'difficulty', history: true)]
    public string $difficultyFilter = '';

    // Filter options
    public array $levels = [
        'A1' => 'A1 - Beginner',
        'A2' => 'A2 - Elementary',
        'B1' => 'B1 - Intermediate',
        'B2' => 'B2 - Upper Intermediate',
        'C1' => 'C1 - Advanced',
        'C2' => 'C2 - Mastery',
    ];

    public array $priceRanges = [
        'free' => 'Free',
        'paid' => 'Premium (€)',
        'under50' => 'Under 50€',
        '50to100' => '50€ - 100€',
        'over100' => 'Over 100€',
    ];

    public array $sortOptions = [
        'popular' => 'Most Popular',
        'newest' => 'Newest first',
        'price_asc' => 'Price: Low to High',
        'price_desc' => 'Price: High to Low',
        'rating' => 'Best Rating',
        'title_asc' => 'Title A-Z',
    ];

    public array $difficulties = [
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced',
    ];

    public bool $showFilters = true;

    #[Computed]
    public function subjects()
    {
        return Subject::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function courses()
    {
        $query = Course::where('is_published', true)
            ->with(['subject', 'teacher'])
            ->withCount(['lessons', 'enrollments'])
            ->withAvg('reviews', 'rating');

        // Search filter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhere('short_description', 'like', '%' . $this->search . '%');
            });
        }

        // Subject filter
        if ($this->subjectFilter) {
            $query->where('subject_id', $this->subjectFilter);
        }

        // Level filter
        if ($this->levelFilter) {
            $query->where('level', $this->levelFilter);
        }

        // Difficulty filter
        if ($this->difficultyFilter) {
            $query->where('difficulty', $this->difficultyFilter);
        }

        // Price filter
        if ($this->priceFilter) {
            match($this->priceFilter) {
                'free' => $query->where('price', 0),
                'paid' => $query->where('price', '>', 0),
                'under50' => $query->where('price', '>', 0)->where('price', '<', 50),
                '50to100' => $query->whereBetween('price', [50, 100]),
                'over100' => $query->where('price', '>', 100),
                default => null,
            };
        }

        // Sorting
        match($this->sortBy) {
            'popular' => $query->orderBy('enrollments_count', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating' => $query->orderBy('reviews_avg_rating', 'desc'),
            'title_asc' => $query->orderBy('title', 'asc'),
            default => $query->latest(),
        };

        return $query->paginate(12);
    }

    #[Computed]
    public function filterCount()
    {
        $count = 0;
        if ($this->search) $count++;
        if ($this->subjectFilter) $count++;
        if ($this->levelFilter) $count++;
        if ($this->priceFilter) $count++;
        if ($this->difficultyFilter) $count++;
        return $count;
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'subjectFilter', 'levelFilter',
            'priceFilter', 'difficultyFilter', 'sortBy'
        ]);
        $this->resetPage();
        $this->success(__('All filters have been reset.'));
    }

    public function updated($property): void
    {
        $this->resetPage();
    }

    public function getLevelBadgeClass($level): string
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
        return $this->levels[$level] ?? $level;
    }
}
?>

{{-- SEO Meta Tags --}}
@section('meta_title', 'Course Catalog - ' . config('app.name'))
@section('meta_description', 'Browse our extensive collection of German courses for all levels from A1 to C2. Find the perfect course for your learning journey.')
@section('meta_keywords', 'German courses, learn German, A1, A2, B1, B2, C1, C2, Goethe certificate, ÖSD, TELC')
@section('og_title', 'German Course Catalog - ' . config('app.name'))
@section('og_description', 'Discover the best German courses for your level. Start learning German today!')
@section('og_image', asset('images/og-image.jpg'))
@section('canonical_url', url()->current())
@section('meta_robots', 'index,follow')

@push('structured_data')
@php
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'German Course Catalog',
        'description' => 'Complete list of German courses available',
        'numberOfItems' => $this->courses->total(),
        'itemListElement' => [],
    ];
    
    foreach ($this->courses as $index => $course) {
        $structuredData['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => route('student.course.show', $course),
            'name' => $course->title,
        ];
    }
@endphp
<script type="application/ld+json">
    {!! json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush


<div class="py-4 md:py-6" x-data="{ showFilters: @entangle('showFilters') }">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-900 md:text-2xl">📚 {{ __('Course Catalog') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('Discover the perfect German courses for your level') }}</p>
        </div>

        <!-- Search Bar -->
        <div class="mb-5">
            <x-input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search for courses, topics or descriptions...') }}"
                icon="o-magnifying-glass"
                class="w-full py-3 rounded-xl"
            />
        </div>

        <!-- Filters Section -->
        <div class="p-4 mb-6 bg-white shadow-sm rounded-xl">
            <!-- Mobile filter toggle -->
            <button
                @click="showFilters = !showFilters"
                class="flex items-center justify-between w-full md:hidden">
                <span class="font-medium text-gray-700">
                    <x-icon name="o-funnel" class="inline w-5 h-5 mr-2" />
                    {{ __('Filters & Sorting') }}
                    @if($this->filterCount > 0)
                        <span class="ml-2 px-2 py-0.5 text-xs bg-[#FF6B35] text-white rounded-full">
                            {{ $this->filterCount }}
                        </span>
                    @endif
                </span>
                <x-icon name="o-chevron-down" class="w-5 h-5 text-gray-500" x-bind:class="showFilters ? 'rotate-180' : ''" />
            </button>

            <!-- Filters content -->
            <div x-show="showFilters" x-collapse class="mt-4 space-y-4">
                <!-- Filter grid -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Subject filter -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Subject') }}</label>
                        <select wire:model.live="subjectFilter" class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                            <option value="">{{ __('All Subjects') }}</option>
                            @foreach($this->subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Level filter -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Level') }}</label>
                        <select wire:model.live="levelFilter" class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                            <option value="">{{ __('All Levels') }}</option>
                            @foreach($levels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Difficulty filter -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Difficulty') }}</label>
                        <select wire:model.live="difficultyFilter" class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                            <option value="">{{ __('All Difficulties') }}</option>
                            @foreach($difficulties as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Price filter -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Price') }}</label>
                        <select wire:model.live="priceFilter" class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                            <option value="">{{ __('All Prices') }}</option>
                            @foreach($priceRanges as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Sorting and actions -->
                <div class="flex flex-col gap-3 pt-3 border-t border-gray-100 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500">{{ __('Sort by') }}:</span>
                        <select wire:model.live="sortBy" class="px-3 py-1.5 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                            @foreach($sortOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($this->filterCount > 0)
                        <button
                            wire:click="clearFilters"
                            class="text-sm text-[#FF6B35] hover:underline">
                            <x-icon name="o-x-mark" class="inline w-4 h-4 mr-1" />
                            {{ __('Clear all filters') }} ({{ $this->filterCount }})
                        </button>
                    @endif
                </div>

                <!-- Active filters badges -->
                @if($this->filterCount > 0)
                    <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100">
                        @if($search)
                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs text-orange-700 bg-orange-100 rounded-full">
                                🔍 "{{ $search }}"
                                <button wire:click="$set('search', '')" class="hover:text-orange-900">
                                    <x-icon name="o-x-mark" class="w-3 h-3" />
                                </button>
                            </span>
                        @endif
                        @if($subjectFilter && $subject = $this->subjects->firstWhere('id', $subjectFilter))
                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs text-blue-700 bg-blue-100 rounded-full">
                                📚 {{ $subject->name }}
                                <button wire:click="$set('subjectFilter', '')" class="hover:text-blue-900">
                                    <x-icon name="o-x-mark" class="w-3 h-3" />
                                </button>
                            </span>
                        @endif
                        @if($levelFilter)
                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs text-green-700 bg-green-100 rounded-full">
                                🎯 {{ $levels[$levelFilter] }}
                                <button wire:click="$set('levelFilter', '')" class="hover:text-green-900">
                                    <x-icon name="o-x-mark" class="w-3 h-3" />
                                </button>
                            </span>
                        @endif
                        @if($difficultyFilter)
                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs text-purple-700 bg-purple-100 rounded-full">
                                📊 {{ $difficulties[$difficultyFilter] }}
                                <button wire:click="$set('difficultyFilter', '')" class="hover:text-purple-900">
                                    <x-icon name="o-x-mark" class="w-3 h-3" />
                                </button>
                            </span>
                        @endif
                        @if($priceFilter)
                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs text-yellow-700 bg-yellow-100 rounded-full">
                                💰 {{ $priceRanges[$priceFilter] }}
                                <button wire:click="$set('priceFilter', '')" class="hover:text-yellow-900">
                                    <x-icon name="o-x-mark" class="w-3 h-3" />
                                </button>
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Results count -->
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">
                {{ $this->courses->total() }} {{ __('courses found') }}
            </p>
        </div>

        <!-- Courses Grid -->
        @if($this->courses->count() > 0)
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach($this->courses as $course)
                <div class="overflow-hidden transition-all duration-200 bg-white shadow-sm rounded-xl hover:shadow-md group">
                    <!-- Banner -->
                    <div class="relative h-24 bg-gradient-to-r from-[#FF6B35]/20 to-[#1E6091]/20 flex items-center justify-center">
                        <span class="text-4xl">
                            @if($course->level === 'A1' || $course->level === 'A2') 🌱
                            @elseif($course->level === 'B1' || $course->level === 'B2') 📚
                            @elseif($course->level === 'C1' || $course->level === 'C2') 🏆
                            @else 🇩🇪
                            @endif
                        </span>

                        <!-- Level badge -->
                        <div class="absolute top-3 right-3">
                            <span class="px-2 py-0.5 text-xs rounded-full {{ $this->getLevelBadgeClass($course->level) }}">
                                {{ $this->getLevelLabel($course->level) }}
                            </span>
                        </div>

                        <!-- Free badge -->
                        @if($course->price == 0)
                            <div class="absolute top-3 left-3">
                                <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">
                                    🇩🇪 {{ __('Free') }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="p-4">
                        <h3 class="mb-1 text-lg font-semibold text-gray-900 line-clamp-1">{{ $course->title }}</h3>
                        <p class="mb-3 text-sm text-gray-500 line-clamp-2">{{ $course->short_description ?? Str::limit($course->description, 80) }}</p>

                        <!-- Metrics -->
                        <div class="flex items-center gap-3 mb-3 text-xs text-gray-500">
                            <span class="flex items-center gap-1">
                                <x-icon name="o-book-open" class="w-3 h-3" />
                                {{ $course->lessons_count }} {{ __('lessons') }}
                            </span>
                            <span class="flex items-center gap-1">
                                <x-icon name="o-users" class="w-3 h-3" />
                                {{ number_format($course->enrollments_count) }}
                            </span>
                            <span class="flex items-center gap-1">
                                <x-icon name="o-star" class="w-3 h-3 text-yellow-400" />
                                {{ number_format($course->reviews_avg_rating ?? 0, 1) }}
                            </span>
                        </div>

                        <!-- Price and button -->
                        <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                            <div>
                                @if($course->price > 0)
                                    <span class="text-lg font-bold text-[#FF6B35]">{{ number_format($course->price, 0, ',', ' ') }} €</span>
                                    @if($course->sale_price)
                                        <span class="ml-1 text-xs text-gray-400 line-through">{{ number_format($course->price, 0, ',', ' ') }} €</span>
                                    @endif
                                @else
                                    <span class="text-sm font-semibold text-green-600">{{ __('Free') }}</span>
                                @endif
                            </div>
                            <a href="{{ route('student.course.show', $course) }}"
                               class="px-3 py-1.5 text-sm font-medium text-white rounded-lg bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] hover:from-[#E55A2A] hover:to-[#FF6B35] transition">
                                {{ __('View Course') }} →
                            </a>
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
            <!-- Empty state -->
            <div class="p-12 text-center bg-white shadow-sm rounded-xl">
                <x-icon name="o-magnifying-glass" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('No courses found') }}</h3>
                <p class="mb-4 text-gray-500">{{ __('Try different search terms or filters') }}</p>
                <button wire:click="clearFilters" class="text-[#FF6B35] hover:underline">
                    {{ __('Reset filters') }} →
                </button>
            </div>
        @endif

        <!-- MVP Note -->
        <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">{{ __('MVP Version') }}</p>
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('Upcoming features: filters by duration, minimum rating, and personalized recommendations.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
