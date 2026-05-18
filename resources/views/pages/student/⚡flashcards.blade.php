<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\FlashcardSet;
use App\Models\Flashcard;
use Mary\Traits\Toast;

new
#[Title('Flashcards - German Learning')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public $selectedSetId = null;
    public $currentCardIndex = 0;
    public $showAnswer = false;
    public $studyMode = 'all'; // all, known, unknown

    // Getters
    public function getSets()
    {
        return FlashcardSet::where('user_id', auth()->id())
            ->withCount('cards')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($set) {
                $knownCount = $set->cards()->wherePivot('known', true)->count();
                $set->known_percentage = $set->cards_count > 0
                    ? round(($knownCount / $set->cards_count) * 100)
                    : 0;
                return $set;
            });
    }

    public function getSelectedSet()
    {
        if (!$this->selectedSetId) return null;

        $query = FlashcardSet::with(['cards' => function($q) {
            if ($this->studyMode === 'known') {
                $q->wherePivot('known', true);
            } elseif ($this->studyMode === 'unknown') {
                $q->wherePivot('known', false);
            }
        }]);

        return $query->find($this->selectedSetId);
    }

    public function getCurrentCard()
    {
        $set = $this->getSelectedSet();
        if (!$set || $set->cards->isEmpty()) return null;
        return $set->cards[$this->currentCardIndex] ?? null;
    }

    public function getTotalCards()
    {
        $set = $this->getSelectedSet();
        return $set?->cards->count() ?? 0;
    }

    public function getProgress()
    {
        $total = $this->getTotalCards();
        if ($total === 0) return 0;
        return round(($this->currentCardIndex + 1) / $total * 100);
    }

    public function getKnownCount()
    {
        if (!$this->selectedSetId) return 0;
        return FlashcardSet::find($this->selectedSetId)?->cards()
            ->wherePivot('known', true)
            ->count() ?? 0;
    }

    public function getUnknownCount()
    {
        if (!$this->selectedSetId) return 0;
        return FlashcardSet::find($this->selectedSetId)?->cards()
            ->wherePivot('known', false)
            ->count() ?? 0;
    }

    // Actions
    public function selectSet($setId): void
    {
        $this->selectedSetId = $setId;
        $this->currentCardIndex = 0;
        $this->showAnswer = false;
        $this->studyMode = 'all';
    }

    public function backToSets(): void
    {
        $this->selectedSetId = null;
        $this->currentCardIndex = 0;
        $this->showAnswer = false;
    }

    public function nextCard(): void
    {
        if ($this->currentCardIndex < $this->getTotalCards() - 1) {
            $this->currentCardIndex++;
            $this->showAnswer = false;
        }
    }

    public function prevCard(): void
    {
        if ($this->currentCardIndex > 0) {
            $this->currentCardIndex--;
            $this->showAnswer = false;
        }
    }

    public function flipCard(): void
    {
        $this->showAnswer = !$this->showAnswer;
    }

    public function markKnown(): void
    {
        $card = $this->getCurrentCard();
        $set = $this->getSelectedSet();
        if (!$card || !$set) return;

        $set->cards()->updateExistingPivot($card->id, ['known' => true]);

        $this->success(__('Great! +1 point! 🎉'));
        $this->nextCard();
    }

    public function markUnknown(): void
    {
        $card = $this->getCurrentCard();
        $set = $this->getSelectedSet();
        if (!$card || !$set) return;

        $set->cards()->updateExistingPivot($card->id, ['known' => false]);

        $this->warning(__('Keep practicing! 📚'));
        $this->nextCard();
    }

    public function resetProgress(): void
    {
        if (!$this->selectedSetId) return;

        $set = FlashcardSet::find($this->selectedSetId);
        if ($set) {
            $set->cards()->syncWithoutDetaching(
                $set->cards->pluck('id')->mapWithKeys(fn($id) => [$id => ['known' => false]])->toArray()
            );
        }

        $this->success(__('Progress reset!'));
        $this->currentCardIndex = 0;
        $this->showAnswer = false;
    }

    public function shuffleCards(): void
    {
        $set = $this->getSelectedSet();
        if (!$set) return;

        $shuffled = $set->cards->shuffle();
        $set->setRelation('cards', $shuffled);
        $this->currentCardIndex = 0;
        $this->showAnswer = false;
        $this->success(__('Cards shuffled! 🔀'));
    }

    public function changeStudyMode($mode): void
    {
        $this->studyMode = $mode;
        $this->currentCardIndex = 0;
        $this->showAnswer = false;
    }

    public function getProgressColor($percentage): string
    {
        if ($percentage >= 80) return 'bg-success';
        if ($percentage >= 50) return 'bg-primary';
        if ($percentage >= 20) return 'bg-warning';
        return 'bg-gray-400';
    }

    public function render()
    {
        return $this->view([
            'sets'          => $this->getSets(),
            'selectedSet'   => $this->getSelectedSet(),
            'currentCard'   => $this->getCurrentCard(),
            'totalCards'    => $this->getTotalCards(),
            'progress'      => $this->getProgress(),
            'knownCount'    => $this->getKnownCount(),
            'unknownCount'  => $this->getUnknownCount(),
            'progressColor' => $this->getProgressColor($this->getProgress()),
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-4xl px-3 mx-auto md:px-4">

        {{-- En-tête --}}
        <div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold md:text-3xl">🃏 {{ __('Flashcards') }}</h1>
                <p class="mt-1 text-sm text-base-content/70">{{ __('Learn vocabulary and concepts with flashcards') }}</p>
            </div>
            @if(!$selectedSetId)
                <x-button label="{{ __('New set') }}" icon="o-plus" class="btn-primary" />
            @endif
        </div>

        @if(!$selectedSetId)
            {{-- Liste des sets --}}
            @if($sets->count() > 0)
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($sets as $set)
                        <div wire:click="selectSet({{ $set->id }})"
                             class="overflow-hidden transition-all shadow-sm cursor-pointer bg-base-100 rounded-xl hover:shadow-md">
                            <div class="h-2 bg-gradient-to-r from-primary to-secondary"></div>
                            <div class="p-5">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-base-content">{{ $set->name }}</h3>
                                        <p class="mt-1 text-sm text-base-content/70 line-clamp-2">{{ $set->description ?? __('No description') }}</p>
                                    </div>
                                    <x-icon name="o-chevron-right" class="w-5 h-5 transition text-base-content/50 group-hover:text-primary" />
                                </div>
                                <div class="flex items-center gap-3 mt-4 text-sm">
                                    <span class="flex items-center gap-1 text-base-content/60">
                                        <x-icon name="o-document-text" class="w-4 h-4" />
                                        {{ $set->cards_count }} {{ __('cards') }}
                                    </span>
                                    <span class="flex items-center gap-1 text-success">
                                        <x-icon name="o-check-circle" class="w-4 h-4" />
                                        {{ $set->known_percentage }}% {{ __('learned') }}
                                    </span>
                                </div>
                                <div class="mt-3">
                                    <div class="w-full h-1.5 bg-base-200 rounded-full">
                                        <div class="h-1.5 rounded-full transition-all {{ $this->getProgressColor($set->known_percentage) }}"
                                             style="width: {{ $set->known_percentage }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-card class="py-12 text-center">
                    <x-icon name="o-sparkles" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <h3 class="mb-2 text-lg font-semibold text-base-content">{{ __('No flashcards yet') }}</h3>
                    <p class="mb-4 text-base-content/60">{{ __('Create your first flashcard set to start learning.') }}</p>
                    <x-button label="{{ __('Create first set →') }}" class="btn-primary" />
                </x-card>
            @endif
        @else
            {{-- Session d'étude --}}
            @if($selectedSet && $totalCards > 0)
                <div class="overflow-hidden shadow-sm bg-base-100 rounded-xl">
                    {{-- En-tête du set --}}
                    <div class="p-4 border-b bg-gradient-to-r from-base-200 to-base-100">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <button wire:click="backToSets"
                                    class="flex items-center gap-1 transition text-primary hover:text-primary-focus">
                                <x-icon name="o-arrow-left" class="w-4 h-4" />
                                {{ __('Back') }}
                            </button>
                            <h2 class="font-semibold text-base-content">{{ $selectedSet->name }}</h2>
                            <div class="flex gap-1">
                                <x-button wire:click="shuffleCards" icon="o-arrows-right-left" class="btn-ghost btn-sm" tooltip="{{ __('Shuffle') }}" />
                                <x-button wire:click="resetProgress" icon="o-arrow-path" class="btn-ghost btn-sm text-error" wire:confirm="{{ __('Reset progress?') }}" tooltip="{{ __('Reset progress') }}" />
                            </div>
                        </div>
                    </div>

                    {{-- Mode d'étude --}}
                    <div class="flex flex-wrap gap-2 p-3 border-b bg-base-200">
                        <button wire:click="changeStudyMode('all')"
                                class="px-3 py-1 text-sm rounded-full transition
                                       {{ $studyMode === 'all' ? 'bg-primary text-white' : 'bg-base-100 text-base-content/70 hover:bg-base-300' }}">
                            {{ __('All') }} ({{ $totalCards }})
                        </button>
                        <button wire:click="changeStudyMode('unknown')"
                                class="px-3 py-1 text-sm rounded-full transition
                                       {{ $studyMode === 'unknown' ? 'bg-warning text-white' : 'bg-base-100 text-base-content/70 hover:bg-base-300' }}">
                            {{ __('To learn') }} ({{ $unknownCount }})
                        </button>
                        <button wire:click="changeStudyMode('known')"
                                class="px-3 py-1 text-sm rounded-full transition
                                       {{ $studyMode === 'known' ? 'bg-success text-white' : 'bg-base-100 text-base-content/70 hover:bg-base-300' }}">
                            {{ __('Learned') }} ({{ $knownCount }})
                        </button>
                    </div>

                    {{-- Barre de progression --}}
                    <div class="p-3 border-b">
                        <div class="flex justify-between mb-1 text-sm">
                            <span class="text-base-content/70">{{ __('Progress') }}</span>
                            <span class="font-medium text-primary">{{ $progress }}%</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-base-200">
                            <div class="h-2 transition-all duration-300 rounded-full bg-primary" style="width: {{ $progress }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-base-content/50">
                            {{ __('Card :current of :total', ['current' => $currentCardIndex + 1, 'total' => $totalCards]) }}
                        </p>
                    </div>

                    {{-- Flashcard --}}
                    <div class="p-6">
                        <div wire:click="flipCard" class="cursor-pointer min-h-[400px] bg-gradient-to-br from-base-200 to-base-100 rounded-2xl shadow-lg flex items-center justify-center p-8 transition-all hover:shadow-xl">
                            <div class="text-center">
                                @if(!$showAnswer)
                                    <div class="mb-4">
                                        <span class="px-3 py-1 text-xs rounded-full bg-primary/10 text-primary">{{ __('Question') }}</span>
                                    </div>
                                    <p class="text-2xl leading-relaxed text-base-content">
                                        {{ $currentCard->question }}
                                    </p>
                                @else
                                    <div class="mb-4">
                                        <span class="px-3 py-1 text-xs rounded-full bg-success/10 text-success">{{ __('Answer') }}</span>
                                    </div>
                                    <p class="text-2xl font-semibold leading-relaxed text-primary">
                                        {{ $currentCard->answer }}
                                    </p>
                                    @if($currentCard->example)
                                        <div class="p-3 mt-4 rounded-lg bg-base-300">
                                            <p class="mb-1 text-xs text-base-content/60">{{ __('Example') }}:</p>
                                            <p class="text-sm text-base-content/80">{{ $currentCard->example }}</p>
                                        </div>
                                    @endif
                                @endif
                                <p class="mt-8 text-xs text-base-content/50">
                                    <x-icon name="o-arrows-pointing-in" class="inline w-3 h-3 mr-1" />
                                    {{ __('Click to flip') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="p-6 border-t bg-base-200">
                        <div class="flex flex-wrap justify-between gap-3">
                            <x-button wire:click="prevCard" icon="o-arrow-left" class="btn-ghost" :disabled="$currentCardIndex === 0">
                                {{ __('Previous') }}
                            </x-button>

                            <div class="flex gap-2">
                                <x-button wire:click="markUnknown" class="btn-warning">
                                    <x-icon name="o-x-mark" class="w-5 h-5 mr-1" />
                                    {{ __('Not yet') }}
                                </x-button>
                                <x-button wire:click="markKnown" class="btn-success">
                                    <x-icon name="o-check" class="w-5 h-5 mr-1" />
                                    {{ __('Learned') }}
                                </x-button>
                            </div>

                            <x-button wire:click="nextCard" icon-right="o-arrow-right" class="btn-primary" :disabled="$currentCardIndex === $totalCards - 1">
                                {{ __('Next') }}
                            </x-button>
                        </div>
                    </div>
                </div>

                {{-- Fin de session --}}
                @if($currentCardIndex === $totalCards - 1 && $showAnswer && $totalCards > 0)
                    <div class="p-4 mt-4 border rounded-lg bg-success/10 border-success/20">
                        <div class="flex items-center gap-3">
                            <x-icon name="o-trophy" class="w-8 h-8 text-warning" />
                            <div>
                                <p class="font-semibold text-success">{{ __('Session completed! 🎉') }}</p>
                                <p class="text-sm text-success/80">
                                    {{ __('You have gone through all :count cards.', ['count' => $totalCards]) }}
                                    @if($unknownCount > 0)
                                        {{ __('Review the cards you haven\'t learned yet.') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <x-card class="py-12 text-center">
                    <x-icon name="o-sparkles" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <h3 class="mb-2 text-lg font-semibold text-base-content">{{ __('No cards in this set') }}</h3>
                    <p class="mb-4 text-base-content/60">{{ __('This set has no flashcards yet.') }}</p>
                    <x-button wire:click="backToSets" label="{{ __('Back to sets →') }}" class="btn-outline" />
                </x-card>
            @endif
        @endif
    </div>
</div>
