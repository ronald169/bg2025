<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Review;
use Mary\Traits\Toast;

new
#[Title('Manage Reviews - Admin')]
#[Layout('layouts.app')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'status', history: true)]
    public string $statusFilter = '';

    #[Url(as: 'rating', history: true)]
    public string $ratingFilter = '';

    public bool $showDeleteModal = false;
    public $reviewToDelete = null;

    // Getters (remplacent #[Computed])
    public function getReviewsProperty()
    {
        return Review::with(['user', 'course'])
            ->when($this->search, function ($query) {
                $query->whereHas('user', fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%'))
                    ->orWhereHas('course', fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
                    ->orWhere('comment', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter === 'pending', fn($q) => $q->where('is_approved', false))
            ->when($this->statusFilter === 'approved', fn($q) => $q->where('is_approved', true))
            ->when($this->ratingFilter, fn($q) => $q->where('rating', $this->ratingFilter))
            ->latest()
            ->paginate(15);
    }

    public function getTotalReviewsProperty()
    {
        return Review::count();
    }

    public function getPendingCountProperty()
    {
        return Review::where('is_approved', false)->count();
    }

    public function getApprovedCountProperty()
    {
        return Review::where('is_approved', true)->count();
    }

    public function getAverageRatingProperty()
    {
        return round(Review::avg('rating') ?? 0, 1);
    }

    public function getRatingDistributionProperty()
    {
        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $distribution[$i] = Review::where('rating', $i)->count();
        }
        return $distribution;
    }

    public function approveReview($reviewId): void
    {
        $review = Review::findOrFail($reviewId);
        $review->update([
            'is_approved' => true,
            'approved_at' => now(),
        ]);
        $this->success(__('Review approved! ✅'));
    }

    public function deleteReview($reviewId): void
    {
        $this->reviewToDelete = Review::findOrFail($reviewId);
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        if ($this->reviewToDelete) {
            $userName = $this->reviewToDelete->user->name;
            $this->reviewToDelete->delete();
            $this->success(__("Review from ':user' has been deleted.", ['user' => $userName]));
            $this->showDeleteModal = false;
            $this->reviewToDelete = null;
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'ratingFilter']);
        $this->resetPage();
        $this->success(__('Filters reset.'));
    }

    public function render()
    {
        return $this->view([
            'reviews' => $this->reviews,
            'totalReviews' => $this->totalReviews,
            'pendingCount' => $this->pendingCount,
            'approvedCount' => $this->approvedCount,
            'averageRating' => $this->averageRating,
            'ratingDistribution' => $this->ratingDistribution,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">⭐ {{ __('Manage Reviews') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('Moderate and manage user reviews') }}</p>
            </div>
            <div class="px-3 py-1.5 rounded-lg bg-base-200 text-center">
                <span class="text-sm text-base-content/70">{{ __('Total') }}:</span>
                <span class="ml-2 text-xl font-bold text-primary">{{ $totalReviews }}</span>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-4">
            <x-stat title="{{ __('Total') }}" :value="$totalReviews" icon="o-star" class="text-primary" />
            <x-stat title="{{ __('Pending') }}" :value="$pendingCount" icon="o-clock" class="text-warning" />
            <x-stat title="{{ __('Approved') }}" :value="$approvedCount" icon="o-check-circle" class="text-success" />
            <x-stat title="{{ __('Average rating') }}" :value="$averageRating . ' ★'" icon="o-chart-bar" class="text-secondary" />
        </div>

        {{-- Rating Distribution --}}
        @if($totalReviews > 0)
            <div class="p-4 mb-5 shadow-sm bg-base-100 rounded-xl">
                <h3 class="mb-3 text-sm font-semibold">{{ __('Rating distribution') }}</h3>
                <div class="space-y-2">
                    @foreach($ratingDistribution as $rating => $count)
                        @php $percentage = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0; @endphp
                        <div class="flex items-center gap-2">
                            <div class="w-12 text-sm font-medium">{{ $rating }} ★</div>
                            <div class="flex-1 h-2 rounded-full bg-base-200">
                                <div class="h-2 rounded-full bg-warning" style="width: {{ $percentage }}%"></div>
                            </div>
                            <div class="w-12 text-sm text-base-content/60">{{ $count }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Filters --}}
        <div class="p-4 mb-5 shadow-sm bg-base-100 rounded-xl">
            <div class="flex flex-col gap-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <x-input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search reviews...') }}" icon="o-magnifying-glass" class="w-full" clearable />
                    <x-select wire:model.live="statusFilter" :options="[
                        ['id' => '', 'name' => __('All statuses')],
                        ['id' => 'pending', 'name' => __('Pending')],
                        ['id' => 'approved', 'name' => __('Approved')],
                    ]" option-value="id" option-label="name" id="status_filter" name="status_filter" />
                    <x-select wire:model.live="ratingFilter" :options="[
                        ['id' => '', 'name' => __('All ratings')],
                        ['id' => '5', 'name' => '5 ★★★★★'],
                        ['id' => '4', 'name' => '4 ★★★★☆'],
                        ['id' => '3', 'name' => '3 ★★★☆☆'],
                        ['id' => '2', 'name' => '2 ★★☆☆☆'],
                        ['id' => '1', 'name' => '1 ★☆☆☆☆'],
                    ]" option-value="id" option-label="name" id="rating_filter" name="rating_filter" />
                </div>
                @if($search || $statusFilter || $ratingFilter)
                    <div class="flex justify-end">
                        <x-button wire:click="clearFilters" label="{{ __('Reset filters') }} →" icon="o-x-mark" class="btn-ghost btn-sm" />
                    </div>
                @endif
            </div>
        </div>

        {{-- Reviews List --}}
        @if($reviews->count() > 0)
            {{-- Desktop table --}}
            <div class="hidden overflow-hidden shadow-sm md:block bg-base-100 rounded-xl">
                <div class="divide-y divide-base-200">
                    @foreach($reviews as $review)
                        <div class="p-5 transition hover:bg-base-200">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-3 mb-2">
                                        <div class="flex items-center gap-2">
                                            <div class="flex items-center justify-center w-10 h-10 text-sm font-bold text-white rounded-full bg-gradient-to-r from-primary to-secondary">
                                                {{ strtoupper(substr($review->user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-medium">{{ $review->user->name }}</p>
                                                <p class="text-xs text-base-content/60">{{ $review->user->email }}</p>
                                            </div>
                                        </div>
                                        <div class="flex text-warning">
                                            @for($i = 1; $i <= 5; $i++)
                                                <x-icon name="o-star" class="w-4 h-4" :class="$i <= $review->rating ? 'text-warning' : 'text-base-content/30'" />
                                            @endfor
                                        </div>
                                        <span class="text-xs text-base-content/50">{{ $review->created_at->diffForHumans() }}</span>
                                        @if(!$review->is_approved)
                                            <x-badge value="{{ __('Pending') }}" class="badge-warning badge-soft" />
                                        @else
                                            <x-badge value="{{ __('Approved') }}" class="badge-success badge-soft" />
                                        @endif
                                    </div>
                                    <div class="ml-12">
                                        <p class="text-sm font-medium">{{ __('Course') }}: {{ $review->course->title }}</p>
                                        <p class="mt-2 italic text-base-content/80">"{{ $review->comment }}"</p>
                                    </div>
                                </div>
                                <div class="flex gap-2 ml-4">
                                    @if(!$review->is_approved)
                                        <x-button label="{{ __('Approve') }}" icon="o-check" class="btn-success btn-sm" wire:click="approveReview({{ $review->id }})" />
                                    @endif
                                    <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" tooltip-left="{{ __('Delete') }}" wire:click="deleteReview({{ $review->id }})" />
                                </div>
                            </div>
                            @if($review->is_approved && $review->approved_at)
                                <div class="mt-2 ml-12 text-xs text-success">{{ __('Approved on') }} {{ $review->approved_at->format('d.m.Y H:i') }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="p-4 border-t bg-base-200">
                    {{ $reviews->links() }}
                </div>
            </div>

            {{-- Mobile cards --}}
            <div class="space-y-3 md:hidden">
                @foreach($reviews as $review)
                    <x-card class="shadow-sm">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="flex items-center justify-center w-10 h-10 text-sm font-bold text-white rounded-full bg-gradient-to-r from-primary to-secondary">
                                    {{ strtoupper(substr($review->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">{{ $review->user->name }}</p>
                                    <p class="text-xs text-base-content/60">{{ $review->user->email }}</p>
                                </div>
                            </div>
                            <div class="flex gap-1">
                                @if(!$review->is_approved)
                                    <x-button icon="o-check" class="btn-ghost btn-sm text-success" wire:click="approveReview({{ $review->id }})" />
                                @endif
                                <x-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="deleteReview({{ $review->id }})" />
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mb-2">
                            <div class="flex text-warning">
                                @for($i = 1; $i <= 5; $i++)
                                    <x-icon name="o-star" class="w-3 h-3" :class="$i <= $review->rating ? 'text-warning' : 'text-base-content/30'" />
                                @endfor
                            </div>
                            <span class="text-xs text-base-content/50">{{ $review->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mb-1 text-xs font-medium">{{ __('Course') }}: {{ $review->course->title }}</p>
                        <p class="mb-2 text-sm italic text-base-content/80">"{{ Str::limit($review->comment, 100) }}"</p>
                        @if(!$review->is_approved)
                            <x-badge value="{{ __('Pending') }}" class="badge-warning badge-soft" />
                        @else
                            <x-badge value="{{ __('Approved') }}" class="badge-success badge-soft" />
                        @endif
                    </x-card>
                @endforeach
                <div class="mt-4">{{ $reviews->links() }}</div>
            </div>
        @else
            <x-card class="py-12 text-center">
                <x-icon name="o-star" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                <h3 class="mb-2 text-lg font-semibold">{{ __('No reviews found') }}</h3>
                <p class="mb-4 text-base-content/60">{{ __('Try different search criteria.') }}</p>
                <x-button wire:click="clearFilters" label="{{ __('Reset filters') }} →" class="btn-outline" />
            </x-card>
        @endif

        {{-- Delete Modal --}}
        <x-modal wire:model="showDeleteModal" title="{{ __('Delete review') }}" separator>
            <p>{{ __('Are you sure you want to delete this review? This action cannot be undone.') }}</p>
            <x-slot:actions>
                <x-button label="{{ __('Cancel') }}" wire:click="$set('showDeleteModal', false)" class="btn-ghost" />
                <x-button label="{{ __('Delete') }}" class="btn-error" wire:click="confirmDelete" spinner />
            </x-slot:actions>
        </x-modal>
    </div>
</div>
