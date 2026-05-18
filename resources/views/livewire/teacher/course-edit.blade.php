<?php

use App\Models\Course;
use App\Models\Subject;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\{Title, Computed};
use Illuminate\Support\Str;
use Mary\Traits\Toast;

new
#[Title('Kurs bearbeiten - Lehrer')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use Toast;

    public Course $course;

    // Informations de base
    public $title = '';
    public $slug = '';
    public $description = '';
    public $short_description = '';
    public $subject_id = '';
    public $level = '';
    public $estimated_duration = 0;
    public $price = 0;
    public $is_published = false;

    // Listes dynamiques
    public $requirements = [];
    public $what_you_will_learn = [];
    public $newRequirement = '';
    public $newLearning = '';

    // Niveaux d'allemand A1-C2
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
        if ($course->teacher_id !== auth()->id()) {
            abort(403);
        }

        $this->course = $course;
        $this->title = $course->title;
        $this->slug = $course->slug;
        $this->description = $course->description;
        $this->short_description = $course->short_description;
        $this->subject_id = $course->subject_id;
        $this->level = $course->level;
        $this->estimated_duration = $course->estimated_duration;
        $this->price = $course->price;
        $this->is_published = $course->is_published;
        $this->requirements = $course->requirements ?? [];
        $this->what_you_will_learn = $course->what_you_will_learn ?? [];
    }

    public function updatedTitle($value): void
    {
        $this->slug = Str::slug($value);
    }

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

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:courses,slug,' . $this->course->id,
            'description' => 'required|string|min:20',
            'short_description' => 'nullable|string|max:200',
            'subject_id' => 'required|exists:subjects,id',
            'level' => 'required|in:A1,A2,B1,B2,C1,C2',
            'estimated_duration' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
        ], [
            'title.required' => 'Bitte gib einen Kurstitel ein.',
            'slug.required' => 'Die URL-Adresse ist erforderlich.',
            'slug.unique' => 'Diese URL-Adresse wird bereits verwendet.',
            'description.required' => 'Bitte gib eine Kursbeschreibung ein.',
            'description.min' => 'Die Beschreibung sollte mindestens 20 Zeichen lang sein.',
            'subject_id.required' => 'Bitte wähle ein Fach aus.',
            'level.required' => 'Bitte wähle ein Niveau aus.',
            'estimated_duration.required' => 'Bitte gib die geschätzte Dauer an.',
            'estimated_duration.min' => 'Die Dauer muss mindestens 1 Minute betragen.',
        ]);

        $this->course->update([
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'subject_id' => $this->subject_id,
            'level' => $this->level,
            'estimated_duration' => $this->estimated_duration,
            'price' => $this->price,
            'is_free' => $this->price == 0,
            'is_published' => $this->is_published,
            'requirements' => $this->requirements,
            'what_you_will_learn' => $this->what_you_will_learn,
        ]);

        $this->success('Kurs erfolgreich aktualisiert! 🎉');
    }

    public function togglePublish(): void
    {
        $this->is_published = !$this->is_published;
        $status = $this->is_published ? 'veröffentlicht' : 'als Entwurf gespeichert';
        $this->success("Kurs wurde {$status}.");
    }

    public function getSubjectsProperty()
    {
        return Subject::where('is_active', true)->orderBy('name')->get();
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
}
?>

<div class="py-4 md:py-6">
    <div class="max-w-4xl px-3 mx-auto md:px-4">

        <!-- Navigation -->
        <div class="mb-5">
            <a href="{{ route('teacher.courses') }}" class="inline-flex items-center gap-1 text-sm text-[#FF6B35] hover:text-[#E55A2A] transition">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Zurück zu meinen Kursen') }}
            </a>
        </div>

        <!-- Header -->
        <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">✏️ {{ __('Kurs bearbeiten') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('Aktualisiere deine Kursinformationen') }}</p>
            </div>
            <div class="flex gap-2">
                <!-- Status Badge -->
                <span class="px-3 py-1.5 text-sm rounded-lg {{ $is_published ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                    {{ $is_published ? 'Veröffentlicht' : 'Entwurf' }}
                </span>
                <!-- Preview Button -->
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
                            wire:model="level"
                            label="{{ __('Deutschniveau') }} *"
                            :options="$levels"
                            icon="o-chart-bar"
                            required />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-input
                                wire:model="estimated_duration"
                                type="number"
                                min="1"
                                label="{{ __('Geschätzte Dauer (Minuten)') }} *"
                                placeholder="{{ __('z.B. 120') }}"
                                icon="o-clock"
                                required />
                            <p class="mt-1 text-xs text-gray-400">
                                {{ __('Aktuell') }}: {{ $this->formattedDuration }}
                            </p>
                        </div>

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
                    </div>

                    <!-- Publish Toggle -->
                    <div class="pt-2">
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

            <!-- Danger Zone (pour les cours publiés) -->
            @if($is_published)
            <x-card class="border-0 border-l-4 shadow-sm border-l-red-500">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-exclamation-triangle" class="w-5 h-5 text-red-500" />
                    <h2 class="font-semibold text-gray-900">{{ __('Gefahrenzone') }}</h2>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-medium text-gray-900">{{ __('Kurs zurückziehen') }}</p>
                        <p class="text-sm text-gray-500">{{ __('Ziehe den Kurs zurück, um ihn für Studenten unsichtbar zu machen') }}</p>
                    </div>
                    <button type="button"
                            wire:click="togglePublish"
                            class="px-4 py-2 text-sm text-yellow-700 transition bg-yellow-100 rounded-lg hover:bg-yellow-200">
                        {{ __('Als Entwurf speichern') }}
                    </button>
                </div>
            </x-card>
            @endif

            <!-- Actions -->
            <div class="flex flex-col justify-end gap-3 pt-4 sm:flex-row">
                <a href="{{ route('teacher.courses') }}" class="px-4 py-2 text-center text-gray-600 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                    {{ __('Abbrechen') }}
                </a>
                <button type="submit" class="px-4 py-2 text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                    <x-icon name="o-check" class="inline w-4 h-4 mr-1" />
                    {{ __('Änderungen speichern') }}
                </button>
            </div>
        </form>

        <!-- Note MVP -->
        <div class="p-3 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-2">
                <x-icon name="o-information-circle" class="w-4 h-4 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">MVP Version</p>
                    <p class="text-xs text-blue-700">{{ __('Pour ajouter des leçons et des quiz, utilise le menu "Gérer" dans la liste des cours.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
