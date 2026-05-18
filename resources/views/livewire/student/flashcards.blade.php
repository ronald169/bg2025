<?php

use App\Models\FlashcardSet;
use App\Models\Flashcard;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Karteikarten - Deutsch lernen')]
#[Layout('components.layouts.dashboard-student')]
class extends Component {
    use Toast;

    public $selectedSetId = null;
    public $currentCardIndex = 0;
    public $showAnswer = false;
    public $studyMode = 'all'; // all, known, unknown

    #[Computed]
    public function sets()
    {
        return FlashcardSet::where('user_id', auth()->id())
            ->withCount('cards')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($set) {
                // Compter les cartes connues
                $knownCount = $set->cards()->wherePivot('known', true)->count();
                $set->known_percentage = $set->cards_count > 0
                    ? round(($knownCount / $set->cards_count) * 100)
                    : 0;
                return $set;
            });
    }

    #[Computed]
    public function selectedSet()
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

    #[Computed]
    public function currentCard()
    {
        if (!$this->selectedSet || $this->selectedSet->cards->isEmpty()) return null;
        return $this->selectedSet->cards[$this->currentCardIndex] ?? null;
    }

    #[Computed]
    public function totalCards()
    {
        return $this->selectedSet?->cards->count() ?? 0;
    }

    #[Computed]
    public function progress()
    {
        $total = $this->totalCards;
        if ($total === 0) return 0;
        return round(($this->currentCardIndex + 1) / $total * 100);
    }

    #[Computed]
    public function knownCount()
    {
        if (!$this->selectedSetId) return 0;
        return FlashcardSet::find($this->selectedSetId)?->cards()
            ->wherePivot('known', true)
            ->count() ?? 0;
    }

    #[Computed]
    public function unknownCount()
    {
        if (!$this->selectedSetId) return 0;
        return FlashcardSet::find($this->selectedSetId)?->cards()
            ->wherePivot('known', false)
            ->count() ?? 0;
    }

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
        if ($this->currentCardIndex < $this->totalCards - 1) {
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
        $card = $this->currentCard;
        if (!$card) return;

        // Mettre à jour le pivot
        $this->selectedSet->cards()->updateExistingPivot($card->id, ['known' => true]);

        $this->success('Sehr gut! +1 Punkt! 🎉');

        // Passer à la carte suivante
        $this->nextCard();
    }

    public function markUnknown(): void
    {
        $card = $this->currentCard;
        if (!$card) return;

        // Mettre à jour le pivot
        $this->selectedSet->cards()->updateExistingPivot($card->id, ['known' => false]);

        $this->warning('Weiter üben! 📚');

        // Passer à la carte suivante
        $this->nextCard();
    }

    public function resetProgress(): void
    {
        if (!$this->selectedSetId) return;

        $set = FlashcardSet::find($this->selectedSetId);
        if ($set) {
            $set->cards()->syncWithoutDetaching(
                $set->cards->pluck('id')->mapWithKeys(function ($id) {
                    return [$id => ['known' => false]];
                })->toArray()
            );
        }

        $this->success('Fortschritt zurückgesetzt!');
        $this->currentCardIndex = 0;
        $this->showAnswer = false;
        // Recharger la vue
        $this->dispatch('progress-reset');
    }

    public function shuffleCards(): void
    {
        if (!$this->selectedSet) return;

        $cards = $this->selectedSet->cards->shuffle();
        $this->selectedSet->setRelation('cards', $cards);
        $this->currentCardIndex = 0;
        $this->showAnswer = false;
        $this->success('Karten gemischt! 🔀');
    }

    public function changeStudyMode($mode): void
    {
        $this->studyMode = $mode;
        $this->currentCardIndex = 0;
        $this->showAnswer = false;
        // Forcer le rechargement du set
        unset($this->selectedSet);
        unset($this->totalCards);
        unset($this->currentCard);
        $this->dispatch('mode-changed');
    }

    public function getProgressColor($percentage): string
    {
        if ($percentage >= 80) return 'bg-green-500';
        if ($percentage >= 50) return 'bg-blue-500';
        if ($percentage >= 20) return 'bg-yellow-500';
        return 'bg-gray-400';
    }

    #[On('progress-reset')]
    #[On('mode-changed')]
    public function refreshData()
    {
        // Force refresh des computed properties
    }
}
?>

<div class="py-8">
    <div class="max-w-4xl px-4 mx-auto">

        <!-- Header -->
        <div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">🃏 {{ __('Karteikarten') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Lerne Vokabeln und Konzepte mit Karteikarten') }}</p>
            </div>
            @if(!$selectedSetId)
                <x-button icon="o-plus" class="btn-primary">
                    Neue Karteikarten
                </x-button>
            @endif
        </div>

        @if(!$selectedSetId)
            <!-- Liste des sets -->
            @if($this->sets->count() > 0)
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($this->sets as $set)
                    <div
                        wire:click="selectSet({{ $set->id }})"
                        class="overflow-hidden transition-all bg-white shadow-sm cursor-pointer rounded-xl hover:shadow-md group">
                        <div class="h-2 bg-gradient-to-r from-[#FF6B35] to-[#1E6091]"></div>
                        <div class="p-5">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $set->name }}</h3>
                                    <p class="mt-1 text-sm text-gray-500 line-clamp-2">{{ $set->description ?? 'Keine Beschreibung' }}</p>
                                </div>
                                <x-icon name="o-chevron-right" class="w-5 h-5 text-gray-400 group-hover:text-[#FF6B35] transition" />
                            </div>

                            <div class="flex items-center gap-3 mt-4 text-sm">
                                <span class="flex items-center gap-1 text-gray-500">
                                    <x-icon name="o-document-text" class="w-4 h-4" />
                                    {{ $set->cards_count }} Karten
                                </span>
                                <span class="flex items-center gap-1 text-green-600">
                                    <x-icon name="o-check-circle" class="w-4 h-4" />
                                    {{ $set->known_percentage }}% gelernt
                                </span>
                            </div>

                            <div class="mt-3">
                                <div class="w-full h-1.5 bg-gray-200 rounded-full">
                                    <div class="h-1.5 rounded-full {{ $this->getProgressColor($set->known_percentage) }}"
                                         style="width: {{ $set->known_percentage }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="p-12 text-center bg-white shadow-sm rounded-xl">
                    <x-icon name="o-sparkles" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">Noch keine Karteikarten</h3>
                    <p class="mb-4 text-gray-500">Erstelle deine ersten Karteikarten, um Vokabeln zu lernen.</p>
                    <x-button class="btn-primary">
                        Erste Karteikarten erstellen →
                    </x-button>
                </div>
            @endif
        @else
            <!-- Session d'étude -->
            @if($this->selectedSet && $this->totalCards > 0)
                <div class="overflow-hidden bg-white shadow-sm rounded-xl">
                    <!-- Header -->
                    <div class="p-4 border-b bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <button
                                wire:click="backToSets"
                                class="flex items-center gap-1 text-[#FF6B35] hover:text-[#E55A2A] transition">
                                <x-icon name="o-arrow-left" class="w-4 h-4" />
                                Zurück
                            </button>
                            <h2 class="font-semibold text-gray-900">{{ $this->selectedSet->name }}</h2>
                            <div class="flex gap-1">
                                <x-button
                                    wire:click="shuffleCards"
                                    icon="o-arrows-right-left"
                                    size="xs"
                                    class="btn-ghost btn-sm"
                                    tooltip="Mischen" />
                                <x-button
                                    wire:click="resetProgress"
                                    icon="o-arrow-path"
                                    size="xs"
                                    class="text-red-500 btn-ghost btn-sm"
                                    wire:confirm="Fortschritt wirklich zurücksetzen?"
                                    tooltip="Fortschritt zurücksetzen" />
                            </div>
                        </div>
                    </div>

                    <!-- Study Mode Selector -->
                    <div class="flex flex-wrap gap-2 p-3 border-b bg-gray-50">
                        <button
                            wire:click="changeStudyMode('all')"
                            class="px-3 py-1 text-sm rounded-full transition
                                   {{ $studyMode === 'all' ? 'bg-[#FF6B35] text-white' : 'bg-white text-gray-600 hover:bg-gray-100' }}">
                            Alle ({{ $this->totalCards }})
                        </button>
                        <button
                            wire:click="changeStudyMode('unknown')"
                            class="px-3 py-1 text-sm rounded-full transition
                                   {{ $studyMode === 'unknown' ? 'bg-yellow-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' }}">
                            Zu lernen ({{ $this->unknownCount }})
                        </button>
                        <button
                            wire:click="changeStudyMode('known')"
                            class="px-3 py-1 text-sm rounded-full transition
                                   {{ $studyMode === 'known' ? 'bg-green-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' }}">
                            Gelernt ({{ $this->knownCount }})
                        </button>
                    </div>

                    <!-- Progress Bar -->
                    <div class="p-3 border-b">
                        <div class="flex justify-between mb-1 text-sm">
                            <span class="text-gray-600">Fortschritt</span>
                            <span class="font-medium text-[#FF6B35]">{{ $this->progress }}%</span>
                        </div>
                        <div class="w-full h-2 bg-gray-200 rounded-full">
                            <div class="h-2 rounded-full bg-[#FF6B35] transition-all duration-300"
                                 style="width: {{ $this->progress }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-gray-400">
                            Karte {{ $this->currentCardIndex + 1 }} von {{ $this->totalCards }}
                        </p>
                    </div>

                    <!-- Flashcard -->
                    <div class="p-6">
                        <div
                            wire:click="flipCard"
                            class="cursor-pointer min-h-[400px] bg-gradient-to-br from-gray-50 to-white rounded-2xl shadow-lg
                                   flex items-center justify-center p-8 transition-all hover:shadow-xl">
                            <div class="text-center">
                                @if(!$showAnswer)
                                    <div class="mb-4">
                                        <span class="px-3 py-1 text-xs bg-[#FF6B35]/10 text-[#FF6B35] rounded-full">
                                            Frage
                                        </span>
                                    </div>
                                    <p class="text-2xl leading-relaxed text-gray-800">
                                        {{ $this->currentCard->question }}
                                    </p>
                                @else
                                    <div class="mb-4">
                                        <span class="px-3 py-1 text-xs text-green-700 bg-green-100 rounded-full">
                                            Antwort
                                        </span>
                                    </div>
                                    <p class="text-2xl text-[#FF6B35] font-semibold leading-relaxed">
                                        {{ $this->currentCard->answer }}
                                    </p>
                                    @if($this->currentCard->example)
                                        <div class="p-3 mt-4 bg-gray-100 rounded-lg">
                                            <p class="mb-1 text-xs text-gray-500">Beispiel:</p>
                                            <p class="text-sm text-gray-600">{{ $this->currentCard->example }}</p>
                                        </div>
                                    @endif
                                @endif
                                <p class="mt-8 text-xs text-gray-400">
                                    <x-icon name="o-arrows-pointing-in" class="inline w-3 h-3 mr-1" />
                                    {{ __('Zum Umklappen klicken') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="p-6 border-t bg-gray-50">
                        <div class="flex flex-wrap justify-between gap-3">
                            <x-button
                                wire:click="prevCard"
                                icon="o-arrow-left"
                                class="btn-ghost"
                                :disabled="$currentCardIndex === 0">
                                Zurück
                            </x-button>

                            <div class="flex gap-2">
                                <x-button
                                    wire:click="markUnknown"
                                    class="text-white bg-yellow-500 border-0 hover:bg-yellow-600">
                                    <x-icon name="o-x-mark" class="w-5 h-5 mr-1" />
                                    Noch nicht
                                </x-button>
                                <x-button
                                    wire:click="markKnown"
                                    class="text-white bg-green-500 border-0 hover:bg-green-600">
                                    <x-icon name="o-check" class="w-5 h-5 mr-1" />
                                    Gelernt
                                </x-button>
                            </div>

                            <x-button
                                wire:click="nextCard"
                                icon-right="o-arrow-right"
                                class="btn-primary"
                                :disabled="$currentCardIndex === $this->totalCards - 1">
                                Weiter
                            </x-button>
                        </div>
                    </div>
                </div>

                <!-- Session terminée -->
                @if($currentCardIndex === $this->totalCards - 1 && $showAnswer && $this->totalCards > 0)
                    <div class="p-4 mt-4 border border-green-200 rounded-lg bg-green-50">
                        <div class="flex items-center gap-3">
                            <x-icon name="o-trophy" class="w-8 h-8 text-yellow-500" />
                            <div>
                                <p class="font-semibold text-green-800">Session abgeschlossen! 🎉</p>
                                <p class="text-sm text-green-700">
                                    Du hast alle {{ $this->totalCards }} Karten durchgearbeitet.
                                    @if($this->unknownCount > 0)
                                        Wiederhole die Karten, die du noch nicht kennst.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="p-12 text-center bg-white shadow-sm rounded-xl">
                    <x-icon name="o-sparkles" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">Keine Karten in diesem Set</h3>
                    <p class="mb-4 text-gray-500">Dieses Set enthält noch keine Karteikarten.</p>
                    <x-button wire:click="backToSets" class="btn-outline">
                        Zurück zu den Sets
                    </x-button>
                </div>
            @endif
        @endif

        <!-- Note MVP -->
        <div class="p-4 mt-8 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="font-medium text-blue-800">MVP Version</p>
                    <p class="text-sm text-blue-700">Prochaines fonctionnalités : création/modification de sets, import/export, statistiques détaillées, mode révision espacée (leitner system).</p>
                </div>
            </div>
        </div>
    </div>
</div>
