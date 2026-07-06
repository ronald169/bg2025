<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Course;
use App\Models\Subject;
use Mary\Traits\Toast;

new
#[Title('Course Catalog')]
#[Layout('layouts.guest')]
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

    // UI state
    public bool $showFilters = false;

    public function getLevelsProperty(): array
    {
        return [
            ['id' => 'A1', 'name' => __('A1 - Beginner'), 'description' => __('Basic understanding'), 'icon' => '🌱'],
            ['id' => 'A2', 'name' => __('A2 - Elementary'), 'description' => __('Simple phrases'), 'icon' => '📖'],
            ['id' => 'B1', 'name' => __('B1 - Intermediate'), 'description' => __('Autonomy'), 'icon' => '🎯'],
            ['id' => 'B2', 'name' => __('B2 - Upper Intermediate'), 'description' => __('Fluent communication'), 'icon' => '⭐'],
            ['id' => 'C1', 'name' => __('C1 - Advanced'), 'description' => __('Advanced mastery'), 'icon' => '🏆'],
            ['id' => 'C2', 'name' => __('C2 - Mastery'), 'description' => __('Native level'), 'icon' => '👑'],
        ];
    }

    public function getPriceRangesProperty()
    {
        return [
            ['id' => 'free', 'name' => __('Free')],
            ['id' => 'paid', 'name' => __('Premium (€)')],
            ['id' => 'under50', 'name' => __('Under 50€')],
            ['id' => '50to100', 'name' => __('50€ - 100€')],
            ['id' => 'over100', 'name' => __('Over 100€')],
        ];
    }

    public function getSortOptionsProperty()
    {
        return [
            ['id' => 'popular', 'name' => __('Most Popular')],
            ['id' => 'newest', 'name' => __('Newest first')],
            ['id' => 'price_asc', 'name' => __('Price: Low to High')],
            ['id' => 'price_desc', 'name' => __('Price: High to Low')],
            ['id' => 'rating', 'name' => __('Best Rating')],
            ['id' => 'title_asc', 'name' => __('Title A-Z')],
        ];
    }

    public function getDifficultiesProperty()
    {
        return [
            ['id' => 'beginner', 'name' => __('Beginner')],
            ['id' => 'intermediate', 'name' => __('Intermediate')],
            ['id' => 'advanced', 'name' => __('Advanced')],
        ];
    }

    public function getSubjectsProperty()
    {
        return Subject::where('is_active', true)->orderBy('name')->get();
    }

    public function getCoursesProperty()
    {
        $query = Course::where('is_published', true)
            ->with(['subject', 'teacher'])
            ->latest()
            ->withCount(['lessons', 'enrollments'])
            ->withAvg('reviews', 'rating');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhere('short_description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->subjectFilter) {
            $query->where('subject_id', $this->subjectFilter);
        }

        if ($this->levelFilter) {
            $query->where('level', $this->levelFilter);
        }

        if ($this->difficultyFilter) {
            $query->where('difficulty', $this->difficultyFilter);
        }

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

    public function getFilterCountProperty()
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

    public function updated(): void
    {
        $this->resetPage();
    }

    // Helper methods to get labels from ids
    public function getLevelLabel($levelId): string
    {
        $level = collect($this->levels)->firstWhere('id', $levelId);
        return $level ? $level['name'] : $levelId;
    }

    public function getDifficultyLabel($difficultyId): string
    {
        $difficulty = collect($this->difficulties)->firstWhere('id', $difficultyId);
        return $difficulty ? $difficulty['name'] : $difficultyId;
    }

    public function getPriceLabel($priceId): string
    {
        $price = collect($this->priceRanges)->firstWhere('id', $priceId);
        return $price ? $price['name'] : $priceId;
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

    public function getStructuredDataProperty(): string
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => __('German Course Catalog'),
            'description' => __('Complete list of German courses available'),
            'numberOfItems' => $this->courses->total(),
            'itemListElement' => [],
        ];

        foreach ($this->courses as $index => $course) {
            $data['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => route('student.course.show', $course),
                'name' => $course->title,
            ];
        }

        return json_encode(
            $data,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        );
    }

    public function render()
    {
        return $this->view([
            'levels' => $this->levels,
            'difficulties' => $this->difficulties,
            'priceRanges' => $this->priceRanges,
            'sortOptions' => $this->sortOptions,
            'subjects' => $this->subjects,
            'courses' => $this->courses,
            'filterCount' => $this->filterCount,
            'structured_data' => $this->structuredData,
        ])->layoutData([
            'structuredData' => $this->structuredData,
        ]);
    }
};

?>

{{-- SEO Meta Tags --}}
@section('meta_title', __('Course Catalog - ') . config('app.name'))
@section('meta_description', __('Browse our extensive collection of German courses for all levels from A1 to C2. Find the perfect course for your learning journey.'))
@section('meta_keywords', __('German courses, learn German, A1, A2, B1, B2, C1, C2, Goethe certificate, ÖSD, TELC, ECL, TestDaF, DSH, German language learning, online German courses, free German courses, paid German courses'))
@section('og_title', __('German Course Catalog - ') . config('app.name'))
@section('og_description', __('Discover the best German courses for your level. Start learning German today!'))
@section('og_image', asset('images/og-image.jpg'))
@section('canonical_url', url()->current())
@section('meta_robots', 'index,follow')


<div class="py-4 md:py-6" x-data="{ showFilters: @entangle('showFilters') }">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold md:text-3xl">📚 {{ __('Course Catalog') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ __('Discover the perfect German courses for your level') }}</p>
        </div>

        {{-- Search Bar --}}
        <div class="mb-5">
            <x-input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search for courses, topics or descriptions...') }}"
                icon="o-magnifying-glass"
                class="w-full input-lg rounded-xl"
                clearable
            />
        </div>

        {{-- Filters Section --}}
        <x-card class="mb-6">
            <!-- Mobile filter toggle -->
            <div class="flex items-center justify-between">
                <button
                    @click="showFilters = !showFilters"
                    class="flex items-center gap-2 text-sm font-medium text-base-content/70">
                    <x-icon name="o-funnel" class="w-5 h-5" />
                    {{ __('Filters & Sorting') }}
                    @if($filterCount > 0)
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white rounded-full bg-primary">
                            {{ $filterCount }}
                        </span>
                    @endif
                </button>
                <x-button
                    icon="o-chevron-down"
                    class="btn-ghost btn-sm"
                    x-bind:class="showFilters ? 'rotate-180' : ''"
                    @click="showFilters = !showFilters"
                />
            </div>

            <!-- Filters content -->
            <div x-show="showFilters" x-collapse class="mt-4 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Subject --}}
                    <x-select
                        label="{{ __('Subject') }}"
                        wire:model.live="subjectFilter"
                        :options="$subjects"
                        option-value="id"
                        option-label="name"
                        placeholder="{{ __('All Subjects') }}"
                        id="subject_filter"
                        name="subject_filter"
                        clearable
                    />

                    {{-- Level --}}
                    <x-select
                        label="{{ __('Level') }}"
                        wire:model.live="levelFilter"
                        :options="$levels"
                        option-value="id"
                        option-label="name"
                        placeholder="{{ __('All Levels') }}"
                        id="level_filter"
                        name="level_filter"
                        clearable
                    />

                    {{-- Difficulty --}}
                    {{-- <x-select
                        label="{{ __('Difficulty') }}"
                        wire:model.live="difficultyFilter"
                        :options="$difficulties"
                        option-value="id"
                        option-label="name"
                        placeholder="{{ __('All Difficulties') }}"
                        id="difficulty_filter"
                        name="difficulty_filter"
                        clearable
                    /> --}}

                    {{-- Price --}}
                    {{-- <x-select
                        label="{{ __('Price') }}"
                        wire:model.live="priceFilter"
                        :options="$priceRanges"
                        option-value="id"
                        option-label="name"
                        placeholder="{{ __('All Prices') }}"
                        id="price_filter"
                        name="price_filter"
                        clearable
                    /> --}}
                </div>

                <div class="flex flex-col gap-3 pt-3 border-t border-base-200 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-base-content/70">{{ __('Sort by') }}:</span>
                        <x-select
                            wire:model.live="sortBy"
                            :options="$sortOptions"
                            option-value="id"
                            option-label="name"
                            id="sort_by"
                            name="sort_by"
                        />
                    </div>

                    @if($filterCount > 0)
                        <x-button
                            wire:click="clearFilters"
                            :label="__('Clear all filters') . ' (' . $filterCount . ')'"
                            icon="o-x-mark"
                            class="btn-ghost btn-sm"
                        />
                    @endif
                </div>

                {{-- Active filters badges --}}
                @if($filterCount > 0)
                    <div class="flex flex-wrap gap-2 pt-2 border-t border-base-200">
                        @if($search)
                            <x-badge :value="'🔍 ' . $search" class="gap-1 badge-info badge-soft">
                                <x-button icon="o-x-mark" class="btn-xs btn-ghost" wire:click="$set('search', '')" />
                            </x-badge>
                        @endif
                        @if($subjectFilter && $subject = $subjects->firstWhere('id', $subjectFilter))
                            <x-badge :value="'📚 ' . $subject->name" class="gap-1 badge-primary badge-soft">
                                <x-button icon="o-x-mark" class="btn-xs btn-ghost" wire:click="$set('subjectFilter', '')" />
                            </x-badge>
                        @endif
                        @if($levelFilter)
                            <x-badge :value="'🎯 ' . $this->getLevelLabel($levelFilter)" class="gap-1 badge-success badge-soft">
                                <x-button icon="o-x-mark" class="btn-xs btn-ghost" wire:click="$set('levelFilter', '')" />
                            </x-badge>
                        @endif
                        @if($difficultyFilter)
                            <x-badge :value="'📊 ' . $this->getDifficultyLabel($difficultyFilter)" class="gap-1 badge-warning badge-soft">
                                <x-button icon="o-x-mark" class="btn-xs btn-ghost" wire:click="$set('difficultyFilter', '')" />
                            </x-badge>
                        @endif
                        @if($priceFilter)
                            <x-badge :value="'💰 ' . $this->getPriceLabel($priceFilter)" class="gap-1 badge-accent badge-soft">
                                <x-button icon="o-x-mark" class="btn-xs btn-ghost" wire:click="$set('priceFilter', '')" />
                            </x-badge>
                        @endif
                    </div>
                @endif
            </div>
        </x-card>

        {{-- Results count --}}
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-base-content/70">
                {{ $courses->total() }} {{ __('courses found') }}
            </p>
        </div>

        {{-- Courses Grid --}}
        @if($courses->count() > 0)
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach($courses as $course)
                <x-card class="overflow-hidden transition-all duration-200 hover:shadow-lg">
                    <div class="relative -mx-6 -mt-6 mb-4 h-32 bg-gradient-to-r from-[#FF6B35]/20 to-[#1E6091]/20 flex items-center justify-center">
                        <span class="text-5xl">
                            @if($course->level === 'A1' || $course->level === 'A2') 🌱
                            @elseif($course->level === 'B1' || $course->level === 'B2') 📚
                            @elseif($course->level === 'C1' || $course->level === 'C2') 🏆
                            @else 🇩🇪
                            @endif
                        </span>

                        <div class="absolute top-3 right-3">
                            <x-badge
                                :value="$this->getLevelLabel($course->level)"
                                :class="$this->getLevelBadgeClass($course->level) . ' badge-sm'"
                            />
                        </div>

                        {{-- @if($course->price == 0)
                            <div class="absolute top-3 left-3">
                                <x-badge value="🇩🇪 Free" class="badge-success badge-sm" />
                            </div>
                        @endif --}}
                        @if($course->subject)
                            <div class="absolute top-3 left-3">
                                <x-badge :value="$course->subject->name" class="badge-success badge-sm" />
                            </div>
                        @endif
                    </div>

                    <div class="space-y-3">
                        <h3 class="text-lg font-semibold line-clamp-1">{{ $course->title }}</h3>
                        <p class="text-sm text-base-content/70 line-clamp-2">
                            {{ $course->short_description ?? Str::limit($course->description, 80) }}
                        </p>

                        <div class="flex flex-wrap items-center gap-3 text-xs text-base-content/60">
                            <span class="flex items-center gap-1">
                                <x-icon name="o-book-open" class="w-3.5 h-3.5" />
                                {{ $course->lessons_count }} {{ __('lessons') }}
                            </span>
                            <span class="flex items-center gap-1">
                                <x-icon name="o-users" class="w-3.5 h-3.5" />
                                {{ number_format($course->enrollments_count) }}
                            </span>
                            <span class="flex items-center gap-1">
                                <x-icon name="o-star" class="w-3.5 h-3.5 text-warning" />
                                {{ number_format($course->reviews_avg_rating ?? 0, 1) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t">
                            <div>
                                @if($course->price > 0)
                                    <span class="text-xl font-bold text-primary">{{ number_format($course->price, 0, ',', ' ') }} €</span>
                                @else
                                    <span class="text-sm font-semibold text-success">{{ __('Free') }}</span>
                                @endif
                            </div>
                            <x-button
                                :label="__('View Course')"
                                class="btn-primary btn-sm"
                                link="{{ route('student.course.show', $course) }}"
                                icon-right="o-arrow-right"
                            />
                        </div>
                    </div>
                </x-card>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $courses->links(data: ['scrollTo' => false]) }}
            </div>
        @else
            <x-card class="py-12 text-center">
                <x-icon name="o-magnifying-glass" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                <h3 class="mb-2 text-lg font-semibold">{{ __('No courses found') }}</h3>
                <p class="mb-4 text-base-content/70">{{ __('Try different search terms or filters') }}</p>
                <x-button wire:click="clearFilters" :label="__('Reset filters')" class="btn-primary" />
            </x-card>
        @endif
    </div>
</div>
