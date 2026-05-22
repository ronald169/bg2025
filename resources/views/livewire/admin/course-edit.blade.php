<?php

use App\Models\Course;
use App\Models\Subject;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Computed};
use Livewire\Attributes\Title;
use Illuminate\Support\Str;
use Mary\Traits\Toast;

new
#[Title('Kurs bearbeiten - Admin')]
#[Layout('components.layouts.dashboard-admin')]
class extends Component {
    use Toast;

    public Course $course;

    // Informations du cours
    public $title = '';
    public $slug = '';
    public $description = '';
    public $short_description = '';
    public $subject_id = '';
    public $teacher_id = '';
    public $level = 'A1';
    public $estimated_duration = 0;
    public $price = 0;
    public $sale_price = null;
    public $is_published = false;
    public $is_featured = false;

    // Listes dynamiques
    public $requirements = [];
    public $what_you_will_learn = [];
    public $newRequirement = '';
    public $newLearning = '';
    public $tags = [];
    public $newTag = '';

    // SEO
    public $meta_title = '';
    public $meta_description = '';
    public $meta_keywords = '';

    // Niveaux d'allemand
    public $levels = [
        ['id' => 'A1', 'name' => 'A1 - Débutant'],
        ['id' => 'A2', 'name' => 'A2 - Élémentaire'],
        ['id' => 'B1', 'name' => 'B1 - Intermédiaire'],
        ['id' => 'B2', 'name' => 'B2 - Avancé'],
        ['id' => 'C1', 'name' => 'C1 - Expérimenté'],
        ['id' => 'C2', 'name' => 'C2 - Maîtrise'],
    ];

    public function mount(Course $course): void
    {
        $this->course = $course;

        $this->title = $course->title;
        $this->slug = $course->slug;
        $this->description = $course->description;
        $this->short_description = $course->short_description;
        $this->subject_id = $course->subject_id;
        $this->teacher_id = $course->teacher_id;
        $this->level = $course->level ?? 'A1';
        $this->estimated_duration = $course->estimated_duration;
        $this->price = $course->price;
        $this->sale_price = $course->sale_price;
        $this->is_published = $course->is_published;
        $this->is_featured = $course->is_featured;
        $this->requirements = $course->requirements ?? [];
        $this->what_you_will_learn = $course->what_you_will_learn ?? [];
        $this->tags = $course->tags ?? [];
        $this->meta_title = $course->meta_title ?? '';
        $this->meta_description = $course->meta_description ?? '';
        $this->meta_keywords = $course->meta_keywords ?? '';

        // SEO
        $this->meta_title = $course->meta_title ?? '';
        $this->meta_description = $course->meta_description ?? '';
        $this->meta_keywords = $course->meta_keywords ?? '';
        $this->og_title = $course->og_title ?? '';
        $this->og_description = $course->og_description ?? '';
        $this->og_image = $course->og_image ?? '';
        $this->twitter_title = $course->twitter_title ?? '';
        $this->twitter_description = $course->twitter_description ?? '';
        $this->twitter_image = $course->twitter_image ?? '';
        $this->canonical_url = $course->canonical_url ?? '';
        $this->robots = $course->robots ?? 'index,follow';

    }

    /*public function updatedTitle($value): void
    {
        $this->slug = Str::slug($value);
    }*/

    public function addRequirement(): void
    {
        if ($this->newRequirement) {
            $this->requirements[] = $this->newRequirement;
            $this->newRequirement = '';
        }
    }

    public function removeRequirement($index): void
    {
        unset($this->requirements[$index]);
        $this->requirements = array_values($this->requirements);
    }

    public function addLearning(): void
    {
        if ($this->newLearning) {
            $this->what_you_will_learn[] = $this->newLearning;
            $this->newLearning = '';
        }
    }

    public function removeLearning($index): void
    {
        unset($this->what_you_will_learn[$index]);
        $this->what_you_will_learn = array_values($this->what_you_will_learn);
    }

    public function addTag(): void
    {
        if ($this->newTag && !in_array($this->newTag, $this->tags)) {
            $this->tags[] = $this->newTag;
            $this->newTag = '';
        }
    }

    public function removeTag($index): void
    {
        unset($this->tags[$index]);
        $this->tags = array_values($this->tags);
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:courses,slug,' . $this->course->id,
            'description' => 'required|string|min:20',
            'short_description' => 'nullable|string|max:200',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'level' => 'required|in:A1,A2,B1,B2,C1,C2',
            'estimated_duration' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
        ], [
            'title.required' => 'Bitte gib einen Kurstitel ein.',
            'slug.required' => 'Die URL-Adresse ist erforderlich.',
            'slug.unique' => 'Diese URL-Adresse wird bereits verwendet.',
            'description.required' => 'Bitte gib eine Kursbeschreibung ein.',
            'description.min' => 'Die Beschreibung sollte mindestens 20 Zeichen lang sein.',
            'subject_id.required' => 'Bitte wähle ein Fach aus.',
            'teacher_id.required' => 'Bitte wähle einen Lehrer aus.',
            'level.required' => 'Bitte wähle ein Niveau aus.',
            'estimated_duration.required' => 'Bitte gib die geschätzte Dauer an.',
            'estimated_duration.min' => 'Die Dauer muss mindestens 1 Minute betragen.',
            'sale_price.lt' => 'Der Angebotspreis muss niedriger sein als der reguläre Preis.',
        ]);

        $this->course->update([
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'subject_id' => $this->subject_id,
            'teacher_id' => $this->teacher_id,
            'level' => $this->level,
            'estimated_duration' => $this->estimated_duration,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'is_free' => $this->price == 0,
            'is_published' => $this->is_published,
            'is_featured' => $this->is_featured,
            'requirements' => $this->requirements,
            'what_you_will_learn' => $this->what_you_will_learn,
            'tags' => $this->tags,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'og_title' => $this->og_title,
            'og_description' => $this->og_description,
            'og_image' => $this->og_image,
            'twitter_title' => $this->twitter_title,
            'twitter_description' => $this->twitter_description,
            'twitter_image' => $this->twitter_image,
            'canonical_url' => $this->canonical_url,
            'robots' => $this->robots,
        ]);

        $this->success('Kurs erfolgreich aktualisiert! 🎉');
    }

    public function getSubjectsProperty()
    {
        return Subject::orderBy('name')->get();
    }

    public function getTeachersProperty()
    {
        return User::where('role', 'teacher')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    #[Computed]
    public function formattedDuration()
    {
        $hours = floor($this->estimated_duration / 60);
        $minutes = $this->estimated_duration % 60;

        if ($hours > 0) {
            return "{$hours}h " . ($minutes > 0 ? "{$minutes}min" : "");
        }
        return "{$minutes}min";
    }

    #[Computed]
    public function currentPrice()
    {
        if ($this->sale_price && $this->sale_price > 0 && $this->sale_price < $this->price) {
            return $this->sale_price;
        }
        return $this->price;
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

    public function togglePublish(): void
    {
        $this->is_published = !$this->is_published;
        $status = $this->is_published ? 'veröffentlicht' : 'als Entwurf gespeichert';
        $this->success("Kurs wurde {$status}.");
    }

    public function toggleFeatured(): void
    {
        $this->is_featured = !$this->is_featured;
        $status = $this->is_featured ? 'als empfohlen markiert' : 'aus Empfehlungen entfernt';
        $this->success("Kurs wurde {$status}.");
    }
}
?>

<div class="py-4 md:py-6">
    <div class="max-w-5xl px-3 mx-auto md:px-4">

        <!-- Navigation -->
        <div class="mb-5">
            <a href="{{ route('admin.courses') }}" class="inline-flex items-center gap-1 text-sm text-[#FF6B35] hover:text-[#E55A2A] transition">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Zurück zu den Kursen') }}
            </a>
        </div>

        <!-- Header -->
        <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">✏️ {{ __('Kurs bearbeiten') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $course->title }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('student.course.show', $course) }}" target="_blank"
                   class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <x-icon name="o-eye" class="inline w-4 h-4 mr-1" />
                    {{ __('Vorschau') }}
                </a>
            </div>
        </div>

        <form wire:submit="save" class="space-y-5">
            <!-- Basic Information -->
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-information-circle" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Allgemeine Informationen') }}</h2>
                </div>

                <div class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input
                            wire:model="title"
                            label="{{ __('Kurstitel') }} *"
                            placeholder="{{ __('z.B. Deutsch A1 für Anfänger') }}"
                            icon="o-pencil"
                            required />

                        <x-input
                            wire:model="slug"
                            label="{{ __('URL-Adresse (Slug)') }} *"
                            placeholder="{{ __('deutsch-a1-anfaenger') }}"
                            icon="o-link"
                            hint="{{ __('Wird automatisch aus dem Titel generiert') }}"
                            required />
                    </div>

                    <x-textarea
                        wire:model="short_description"
                        label="{{ __('Kurzbeschreibung') }}"
                        placeholder="{{ __('Eine kurze Beschreibung des Kurses (max. 200 Zeichen)') }}"
                        rows="2"
                        icon="o-document-text" />

                    <x-textarea
                        wire:model="description"
                        label="{{ __('Vollständige Beschreibung') }} *"
                        placeholder="{{ __('Detaillierte Beschreibung des Kurses, Lernziele, Methoden usw.') }}"
                        rows="5"
                        icon="o-document"
                        required />

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-select
                            wire:model="subject_id"
                            label="{{ __('Fach') }} *"
                            :options="$this->subjects->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->toArray()"
                            placeholder="{{ __('Fach auswählen') }}"
                            icon="o-academic-cap"
                            required />

                        <x-select
                            wire:model="teacher_id"
                            label="{{ __('Lehrer') }} *"
                            :options="$this->teachers->map(fn($t) => ['id' => $t->id, 'name' => $t->name . ' (' . $t->email . ')'])->toArray()"
                            placeholder="{{ __('Lehrer auswählen') }}"
                            icon="o-user"
                            required />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-select
                            wire:model="level"
                            label="{{ __('Deutschniveau') }} *"
                            :options="$levels"
                            icon="o-chart-bar"
                            required />

                        <x-input
                            wire:model="estimated_duration"
                            type="number"
                            min="1"
                            label="{{ __('Geschätzte Dauer (Minuten)') }} *"
                            placeholder="{{ __('z.B. 120') }}"
                            icon="o-clock"
                            hint="{{ __('Aktuell') }}: {{ $this->formattedDuration }}"
                            required />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-input
                                wire:model="price"
                                type="number"
                                step="0.01"
                                min="0"
                                label="{{ __('Preis (€)') }}"
                                placeholder="{{ __('0 für kostenlosen Kurs') }}"
                                icon="o-currency-euro" />
                            <p class="mt-1 text-xs text-gray-400">
                                {{ $price == 0 ? 'Kostenloser Kurs' : 'Premium Kurs' }}
                            </p>
                        </div>
                        <div>
                            <x-input
                                wire:model="sale_price"
                                type="number"
                                step="0.01"
                                min="0"
                                label="{{ __('Angebotspreis (€)') }}"
                                placeholder="{{ __('Optional') }}"
                                icon="o-tag" />
                            <p class="mt-1 text-xs text-gray-400">
                                {{ __('Aktueller Preis') }}: €{{ number_format($this->currentPrice, 2) }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                            <div>
                                <label class="font-medium text-gray-900">{{ __('Kurs veröffentlichen') }}</label>
                                <p class="text-xs text-gray-500">{{ __('Veröffentlichte Kurse sind für Studenten sichtbar') }}</p>
                            </div>
                            <button type="button"
                                    wire:click="togglePublish"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none
                                           {{ $is_published ? 'bg-[#FF6B35]' : 'bg-gray-300' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                                             {{ $is_published ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                            <div>
                                <label class="font-medium text-gray-900">{{ __('Empfohlener Kurs') }}</label>
                                <p class="text-xs text-gray-500">{{ __('Empfohlene Kurse werden auf der Startseite angezeigt') }}</p>
                            </div>
                            <button type="button"
                                    wire:click="toggleFeatured"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none
                                           {{ $is_featured ? 'bg-[#FF6B35]' : 'bg-gray-300' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                                             {{ $is_featured ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Requirements -->
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-clipboard-document-list" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Voraussetzungen') }}</h2>
                </div>

                <div class="space-y-4">
                    <div class="flex gap-2">
                        <x-input
                            wire:model="newRequirement"
                            placeholder="{{ __('Voraussetzung hinzufügen...') }}"
                            class="flex-1"
                            icon="o-plus-circle" />
                        <x-button wire:click="addRequirement" icon="o-plus" class="btn-primary btn-sm">
                            {{ __('Hinzufügen') }}
                        </x-button>
                    </div>

                    @if(count($requirements) > 0)
                        <div class="mt-3 space-y-2">
                            @foreach($requirements as $index => $req)
                            <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                                <div class="flex items-center gap-2">
                                    <x-icon name="o-check-circle" class="w-4 h-4 text-green-500" />
                                    <span class="text-sm text-gray-700">{{ $req }}</span>
                                </div>
                                <button type="button" wire:click="removeRequirement({{ $index }})" class="text-red-500 transition hover:text-red-700">
                                    <x-icon name="o-trash" class="w-4 h-4" />
                                </button>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm italic text-gray-400">{{ __('Keine Voraussetzungen hinzugefügt') }}</p>
                    @endif
                </div>
            </x-card>

            <!-- What You'll Learn -->
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-sparkles" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Lernziele') }}</h2>
                </div>

                <div class="space-y-4">
                    <div class="flex gap-2">
                        <x-input
                            wire:model="newLearning"
                            placeholder="{{ __('Lernziel hinzufügen...') }}"
                            class="flex-1"
                            icon="o-plus-circle" />
                        <x-button wire:click="addLearning" icon="o-plus" class="btn-primary btn-sm">
                            {{ __('Hinzufügen') }}
                        </x-button>
                    </div>

                    @if(count($what_you_will_learn) > 0)
                        <div class="mt-3 space-y-2">
                            @foreach($what_you_will_learn as $index => $item)
                            <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                                <div class="flex items-center gap-2">
                                    <x-icon name="o-star" class="w-4 h-4 text-yellow-500" />
                                    <span class="text-sm text-gray-700">{{ $item }}</span>
                                </div>
                                <button type="button" wire:click="removeLearning({{ $index }})" class="text-red-500 transition hover:text-red-700">
                                    <x-icon name="o-trash" class="w-4 h-4" />
                                </button>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm italic text-gray-400">{{ __('Keine Lernziele hinzugefügt') }}</p>
                    @endif
                </div>
            </x-card>

            <!-- Tags -->
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-tag" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Tags') }}</h2>
                </div>

                <div class="space-y-4">
                    <div class="flex gap-2">
                        <x-input
                            wire:model="newTag"
                            placeholder="{{ __('Tag hinzufügen...') }}"
                            class="flex-1"
                            icon="o-plus-circle" />
                        <x-button wire:click="addTag" icon="o-plus" class="btn-primary btn-sm">
                            {{ __('Hinzufügen') }}
                        </x-button>
                    </div>

                    @if(count($tags) > 0)
                        <div class="flex flex-wrap gap-2 mt-3">
                            @foreach($tags as $index => $tag)
                            <span class="inline-flex items-center gap-1 px-3 py-1 text-sm text-gray-700 bg-gray-100 rounded-full">
                                {{ $tag }}
                                <button type="button" wire:click="removeTag({{ $index }})" class="text-red-500 hover:text-red-700">
                                    <x-icon name="o-x-mark" class="w-3 h-3" />
                                </button>
                            </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm italic text-gray-400">{{ __('Keine Tags hinzugefügt') }}</p>
                    @endif
                </div>
            </x-card>

            <!-- SEO Section -->
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-chart-bar" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('SEO Optimization') }}</h2>
                    <span class="text-xs text-gray-400">{{ __('Improve your search engine ranking') }}</span>
                </div>

                <div class="space-y-4">
                    <!-- Meta Tags -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input
                            wire:model="meta_title"
                            label="{{ __('Meta Title') }}"
                            placeholder="{{ __('Title for search engines') }}"
                            icon="o-document-text"
                            hint="{{ __('Recommended: 50-60 characters') }}" />

                        <x-input
                            wire:model="meta_keywords"
                            label="{{ __('Meta Keywords') }}"
                            placeholder="{{ __('Keywords separated by commas') }}"
                            icon="o-tag"
                            hint="{{ __('e.g., German course, learn German, A1') }}" />
                    </div>

                    <x-textarea
                        wire:model="meta_description"
                        label="{{ __('Meta Description') }}"
                        placeholder="{{ __('Short description for search engines') }}"
                        rows="2"
                        icon="o-document"
                        hint="{{ __('Recommended: 150-160 characters') }}" />

                    <!-- Open Graph (Facebook, LinkedIn) -->
                    <div class="pt-2">
                        <div class="flex items-center gap-2 mb-3">
                            <x-icon name="o-share" class="w-4 h-4 text-blue-500" />
                            <h3 class="font-medium text-gray-900">{{ __('Open Graph (Facebook, LinkedIn)') }}</h3>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-input
                                wire:model="og_title"
                                label="{{ __('OG Title') }}"
                                                placeholder="{{ __('Title when shared on social media') }}"
                                icon="brands.facebook" />

                            <x-input
                                wire:model="og_image"
                                label="{{ __('OG Image URL') }}"
                                placeholder="{{ __('Image URL for social sharing') }}"
                                icon="o-photo" />
                        </div>
                        <x-textarea
                            wire:model="og_description"
                            label="{{ __('OG Description') }}"
                            placeholder="{{ __('Description when shared on social media') }}"
                            rows="2"
                            icon="o-document-text" />
                    </div>

                    <!-- Twitter Card -->
                    <div class="pt-2">
                        <div class="flex items-center gap-2 mb-3">
                            <x-icon name="o-share" class="w-4 h-4 text-sky-500" />
                            <h3 class="font-medium text-gray-900">{{ __('Twitter Card') }}</h3>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-input
                                wire:model="twitter_title"
                                label="{{ __('Twitter Title') }}"
                                placeholder="{{ __('Title for Twitter sharing') }}"
                                icon="brands.twitter" />

                            <x-input
                                wire:model="twitter_image"
                                label="{{ __('Twitter Image URL') }}"
                                placeholder="{{ __('Image URL for Twitter') }}"
                                icon="o-photo" />
                        </div>
                        <x-textarea
                            wire:model="twitter_description"
                            label="{{ __('Twitter Description') }}"
                            placeholder="{{ __('Description for Twitter sharing') }}"
                            rows="2"
                            icon="o-document-text" />
                    </div>

                    <!-- Advanced SEO -->
                    <div class="pt-2">
                        <div class="flex items-center gap-2 mb-3">
                            <x-icon name="o-cog-6-tooth" class="w-4 h-4 text-gray-500" />
                            <h3 class="font-medium text-gray-900">{{ __('Advanced Settings') }}</h3>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-input
                                wire:model="canonical_url"
                                label="{{ __('Canonical URL') }}"
                                placeholder="{{ __('https://example.com/preferred-url') }}"
                                icon="o-link"
                                hint="{{ __('Leave empty to use current URL') }}" />

                            <x-select
                                wire:model="robots"
                                label="{{ __('Robots Directive') }}"
                                :options="[
                                    ['id' => 'index,follow', 'name' => 'index, follow (default)'],
                                    ['id' => 'noindex,follow', 'name' => 'noindex, follow'],
                                    ['id' => 'index,nofollow', 'name' => 'index, nofollow'],
                                    ['id' => 'noindex,nofollow', 'name' => 'noindex, nofollow']
                                ]"
                                icon="o-shield-check" />
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Danger Zone -->
            <x-card class="border-0 border-l-4 shadow-sm border-l-red-500">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-exclamation-triangle" class="w-5 h-5 text-red-500" />
                    <h2 class="font-semibold text-gray-900">{{ __('Gefahrenzone') }}</h2>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-medium text-gray-900">{{ __('Kurs löschen') }}</p>
                        <p class="text-sm text-gray-500">{{ __('Diese Aktion kann nicht rückgängig gemacht werden') }}</p>
                    </div>
                    <button type="button"
                            wire:click="deleteCourse"
                            wire:confirm="{{ __('Bist du sicher, dass du diesen Kurs löschen möchtest?') }}"
                            class="px-4 py-2 text-sm text-white transition bg-red-600 rounded-lg hover:bg-red-700">
                        {{ __('Kurs endgültig löschen') }}
                    </button>
                </div>
            </x-card>

            <!-- Actions -->
            <div class="flex flex-col justify-end gap-3 pt-4 sm:flex-row">
                <a href="{{ route('admin.courses') }}" class="px-4 py-2 text-center text-gray-600 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                    {{ __('Abbrechen') }}
                </a>
                <button type="submit" class="px-4 py-2 text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                    <x-icon name="o-check" class="inline w-4 h-4 mr-1" />
                    {{ __('Änderungen speichern') }}
                </button>
            </div>
        </form>
    </div>
</div>
