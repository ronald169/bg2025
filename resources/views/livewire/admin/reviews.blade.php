<?php

use App\Models\Review;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Bewertungen verwalten - Admin')]
#[Layout('components.layouts.dashboard-admin')]
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

    #[Computed]
    public function reviews()
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

    #[Computed]
    public function totalReviews()
    {
        return Review::count();
    }

    #[Computed]
    public function pendingCount()
    {
        return Review::where('is_approved', false)->count();
    }

    #[Computed]
    public function approvedCount()
    {
        return Review::where('is_approved', true)->count();
    }

    #[Computed]
    public function averageRating()
    {
        return round(Review::avg('rating') ?? 0, 1);
    }

    #[Computed]
    public function ratingDistribution()
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
        $this->success('Bewertung wurde freigegeben! ✅');
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
            $this->success("Bewertung von '{$userName}' wurde gelöscht.");
            $this->showDeleteModal = false;
            $this->reviewToDelete = null;
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'ratingFilter']);
        $this->resetPage();
        $this->success('Filter zurückgesetzt.');
    }

    public function getRatingStars($rating): string
    {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            $stars .= $i <= $rating ? '★' : '☆';
        }
        return $stars;
    }
}
?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        <!-- Header -->
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">⭐ {{ __('Bewertungen verwalten') }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">{{ __('Moderiere und verwalte Benutzerbewertungen') }}</p>
            </div>
            <div class="px-3 py-1.5 bg-gray-100 rounded-lg text-center">
                <span class="text-sm text-gray-600">{{ __('Gesamt') }}:</span>
                <span class="text-xl font-bold text-[#FF6B35] ml-2">{{ $this->totalReviews }}</span>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-4">
            <div class="bg-white rounded-lg p-3 text-center shadow-sm border-l-4 border-l-[#FF6B35]">
                <p class="text-xs text-gray-500">{{ __('Gesamt') }}</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->totalReviews }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-yellow-500">
                <p class="text-xs text-gray-500">{{ __('Ausstehend') }}</p>
                <p class="text-xl font-bold text-yellow-600">{{ $this->pendingCount }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-green-500">
                <p class="text-xs text-gray-500">{{ __('Freigegeben') }}</p>
                <p class="text-xl font-bold text-green-600">{{ $this->approvedCount }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-purple-500">
                <p class="text-xs text-gray-500">{{ __('Ø Bewertung') }}</p>
                <p class="text-xl font-bold text-purple-600">{{ $this->averageRating }} ★</p>
            </div>
        </div>

        <!-- Rating Distribution -->
        @if($this->totalReviews > 0)
        <div class="p-4 mb-5 bg-white shadow-sm rounded-xl">
            <h3 class="mb-3 text-sm font-semibold text-gray-700">{{ __('Verteilung der Bewertungen') }}</h3>
            <div class="space-y-2">
                @foreach($this->ratingDistribution as $rating => $count)
                    @php $percentage = $this->totalReviews > 0 ? round(($count / $this->totalReviews) * 100) : 0; @endphp
                    <div class="flex items-center gap-2">
                        <div class="w-12 text-sm font-medium text-gray-600">{{ $rating }} ★</div>
                        <div class="flex-1 h-2 bg-gray-200 rounded-full">
                            <div class="h-2 bg-yellow-400 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                        <div class="w-12 text-sm text-gray-500">{{ $count }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Filters -->
        <div class="p-4 mb-5 bg-white shadow-sm rounded-xl">
            <div class="flex flex-col gap-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="relative">
                        <x-input
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Bewertungen durchsuchen...') }}"
                            icon="o-magnifying-glass"
                            class="w-full" />
                    </div>

                    <select wire:model.live="statusFilter" class="px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                        <option value="">{{ __('Alle Status') }}</option>
                        <option value="pending">{{ __('Ausstehend') }}</option>
                        <option value="approved">{{ __('Freigegeben') }}</option>
                    </select>

                    <select wire:model.live="ratingFilter" class="px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                        <option value="">{{ __('Alle Bewertungen') }}</option>
                        <option value="5">5 ★★★★★</option>
                        <option value="4">4 ★★★★☆</option>
                        <option value="3">3 ★★★☆☆</option>
                        <option value="2">2 ★★☆☆☆</option>
                        <option value="1">1 ★☆☆☆☆</option>
                    </select>
                </div>

                @if($search || $statusFilter || $ratingFilter)
                    <div class="flex justify-end">
                        <button wire:click="clearFilters" class="text-sm text-[#FF6B35] hover:underline">
                            {{ __('Filter zurücksetzen') }} →
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Reviews List -->
        @if($this->reviews->count() > 0)
            <!-- Version Desktop -->
            <div class="hidden overflow-hidden bg-white shadow-sm md:block rounded-xl">
                <div class="divide-y divide-gray-100">
                    @foreach($this->reviews as $review)
                    <div class="p-5 transition hover:bg-gray-50">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-3 mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-sm">
                                            {{ strtoupper(substr($review->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $review->user->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $review->user->email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex text-yellow-400">
                                        @for($i = 1; $i <= 5; $i++)
                                            <x-icon name="o-star" class="w-4 h-4"
                                                    :class="$i <= $review->rating ? 'text-yellow-400' : 'text-gray-300'" />
                                        @endfor
                                    </div>
                                    <span class="text-xs text-gray-400">{{ $review->created_at->diffForHumans() }}</span>
                                    @if(!$review->is_approved)
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-700">
                                            {{ __('Ausstehend') }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">
                                            {{ __('Freigegeben') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="ml-12">
                                    <p class="text-sm font-medium text-gray-700">
                                        <span class="text-gray-500">{{ __('Kurs') }}:</span>
                                        {{ $review->course->title }}
                                    </p>
                                    <p class="mt-2 italic text-gray-600">"{{ $review->comment }}"</p>
                                </div>
                            </div>
                            <div class="flex gap-2 ml-4">
                                @if(!$review->is_approved)
                                    <button wire:click="approveReview({{ $review->id }})"
                                            class="px-3 py-1.5 text-sm text-white bg-green-600 rounded-lg hover:bg-green-700 transition"
                                            title="Freigeben">
                                        <x-icon name="o-check" class="inline w-4 h-4 mr-1" />
                                        {{ __('Freigeben') }}
                                    </button>
                                @endif
                                <button wire:click="deleteReview({{ $review->id }})"
                                        class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg transition"
                                        title="Löschen">
                                    <x-icon name="o-trash" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                        @if($review->is_approved && $review->approved_at)
                            <div class="mt-2 ml-12 text-xs text-green-600">
                                {{ __('Freigegeben am') }} {{ $review->approved_at->format('d.m.Y H:i') }}
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                <div class="p-4 border-t bg-gray-50">
                    {{ $this->reviews->links() }}
                </div>
            </div>

            <!-- Version Mobile -->
            <div class="space-y-3 md:hidden">
                @foreach($this->reviews as $review)
                <div class="p-4 bg-white shadow-sm rounded-xl">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-sm">
                                {{ strtoupper(substr($review->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $review->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $review->user->email }}</p>
                            </div>
                        </div>
                        <div class="flex gap-1">
                            @if(!$review->is_approved)
                                <button wire:click="approveReview({{ $review->id }})" class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg">
                                    <x-icon name="o-check" class="w-4 h-4" />
                                </button>
                            @endif
                            <button wire:click="deleteReview({{ $review->id }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg">
                                <x-icon name="o-trash" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 mb-2">
                        <div class="flex text-yellow-400">
                            @for($i = 1; $i <= 5; $i++)
                                <x-icon name="o-star" class="w-3 h-3"
                                        :class="$i <= $review->rating ? 'text-yellow-400' : 'text-gray-300'" />
                            @endfor
                        </div>
                        <span class="text-xs text-gray-400">{{ $review->created_at->diffForHumans() }}</span>
                    </div>

                    <p class="mb-1 text-xs font-medium text-gray-500">{{ $review->course->title }}</p>
                    <p class="mb-2 text-sm italic text-gray-600">"{{ Str::limit($review->comment, 100) }}"</p>

                    @if(!$review->is_approved)
                        <span class="inline-block px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-700">
                            {{ __('Ausstehend') }}
                        </span>
                    @else
                        <span class="inline-block px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">
                            {{ __('Freigegeben') }}
                        </span>
                    @endif
                </div>
                @endforeach

                <div class="mt-4">
                    {{ $this->reviews->links() }}
                </div>
            </div>
        @else
            <div class="p-12 text-center bg-white shadow-sm rounded-xl">
                <x-icon name="o-star" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('Keine Bewertungen gefunden') }}</h3>
                <p class="mb-4 text-gray-500">{{ __('Versuche andere Suchkriterien.') }}</p>
                <button wire:click="clearFilters" class="text-[#FF6B35] hover:underline">
                    {{ __('Filter zurücksetzen') }} →
                </button>
            </div>
        @endif

        <!-- Delete Modal -->
        @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="$set('showDeleteModal', false)">
            <div class="w-full max-w-md overflow-hidden bg-white shadow-xl rounded-xl">
                <div class="p-6 text-center">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full">
                        <x-icon name="o-exclamation-triangle" class="w-8 h-8 text-red-600" />
                    </div>
                    <h3 class="mb-2 text-xl font-bold text-gray-900">{{ __('Bewertung löschen') }}</h3>
                    <p class="mb-6 text-gray-600">
                        {{ __('Bist du sicher, dass du diese Bewertung löschen möchtest? Diese Aktion kann nicht rückgängig gemacht werden.') }}
                    </p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('showDeleteModal', false)" class="px-4 py-2 text-gray-600 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                            {{ __('Abbrechen') }}
                        </button>
                        <button wire:click="confirmDelete" class="px-4 py-2 text-white transition bg-red-600 rounded-lg hover:bg-red-700">
                            {{ __('Löschen') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Note MVP -->
        <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">MVP Version</p>
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('Prochaines fonctionnalités : réponses aux avis, notifications automatiques, et analyses détaillées.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
