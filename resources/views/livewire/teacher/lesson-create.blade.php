<?php
// app/Livewire/Teacher/LessonCreate.php

use App\Models\Course;
use App\Models\Lesson;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Str;
use Mary\Traits\Toast;

new
#[Title('Lektion erstellen - Lehrer')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use Toast;

    public Course $course;

    public $title = '';
    public $slug = '';
    public $description = '';
    public $content = '';
    public $video_url = '';
    public $duration = 0;
    public $order = 0;
    public $is_free = true;
    public $is_published = true;

    public function mount(Course $course): void
    {
        if ($course->teacher_id !== auth()->id()) {
            abort(403);
        }
        $this->course = $course;
        $this->order = Lesson::where('course_id', $course->id)->count() + 1;
    }

    public function updatedTitle($value): void
    {
        $this->slug = Str::slug($value);
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:lessons,slug',
            'description' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'duration' => 'nullable|integer|min:0',
            'order' => 'required|integer|min:1',
        ], [
            'title.required' => 'Bitte gib einen Titel ein.',
            'slug.required' => 'Die URL-Adresse ist erforderlich.',
            'slug.unique' => 'Diese URL-Adresse wird bereits verwendet.',
            'order.required' => 'Bitte gib eine Reihenfolge an.',
        ]);

        // Nettoyer le contenu HTML
        $cleanContent = $this->cleanHtmlContent($this->content);

        Lesson::create([
            'course_id' => $this->course->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'content' => $cleanContent,
            'video_url' => $this->video_url,
            'duration' => $this->duration,
            'order' => $this->order,
            'is_free' => $this->is_free,
            'is_published' => $this->is_published,
        ]);

        $this->success('Lektion erfolgreich erstellt! 🎉');
        $this->redirectRoute('teacher.lessons.index', $this->course, navigate: true);
    }

    private function cleanHtmlContent($content): string
    {
        // Remplacer les URLs absolues par des relatives
        $baseUrl = url('/');
        $content = str_replace($baseUrl, '', $content);

        // Nettoyer les balises vides
        $content = preg_replace('/<p>\s*<\/p>/', '', $content);

        return $content;
    }

    public function getDurationFormatted(): string
    {
        $seconds = $this->duration;
        if ($seconds < 60) return "{$seconds} sec";
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return $secs > 0 ? "{$minutes} min {$secs} sec" : "{$minutes} min";
    }
}
?>

<!-- resources/views/livewire/teacher/lesson-create.blade.php -->
<div class="py-4 md:py-6">
    <div class="max-w-5xl px-3 mx-auto md:px-4">

        <!-- Navigation -->
        <div class="mb-5">
            <a href="{{ route('teacher.lessons.index', $course) }}" class="inline-flex items-center gap-1 text-sm text-[#FF6B35] hover:text-[#E55A2A] transition">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Zurück zu den Lektionen') }}
            </a>
        </div>

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-900 md:text-2xl">➕ {{ __('Neue Lektion erstellen') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $course->title }}</p>
        </div>

        <form wire:submit="save" class="space-y-5">
            <x-card class="border-0 shadow-sm">
                <div class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input
                            wire:model="title"
                            label="{{ __('Lektionstitel') }} *"
                            placeholder="{{ __('z.B. Einführung in die deutsche Grammatik') }}"
                            icon="o-pencil"
                            required />

                        <x-input
                            wire:model="slug"
                            label="{{ __('URL-Adresse (Slug)') }} *"
                            placeholder="{{ __('einfuhrung-deutsche-grammatik') }}"
                            icon="o-link"
                            hint="{{ __('Wird automatisch aus dem Titel generiert') }}"
                            />
                    </div>

                    <x-textarea
                        wire:model="description"
                        label="{{ __('Kurzbeschreibung') }}"
                        placeholder="{{ __('Was werden die Studenten in dieser Lektion lernen?') }}"
                        rows="2"
                        icon="o-document-text" />

                    <x-input
                        wire:model="video_url"
                        label="{{ __('Video URL') }}"
                        placeholder="{{ __('https://youtube.com/... oder https://vimeo.com/...') }}"
                        icon="o-video-camera"
                        hint="{{ __('YouTube, Vimeo oder andere Video-Plattformen') }}" />

                    <!-- Rich Text Editor -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            {{ __('Lektionsinhalt') }}
                        </label>
                        <x-editor
                            wire:model="content"
                            :config="config('tinymce.config')"
                            folder="{{ 'lessons/' . $course->id . '/' . now()->format('Y/m') }}"
                            disk="public" />

                        <p class="mt-1 text-xs text-gray-400">
                            {{ __('HTML wird unterstützt. Du kannst Bilder, Tabellen und Formatierungen hinzufügen.') }}
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <x-input
                                wire:model="duration"
                                type="number"
                                min="0"
                                label="{{ __('Dauer (Sekunden)') }}"
                                placeholder="{{ __('z.B. 600 für 10 Minuten') }}"
                                icon="o-clock" />
                            @if($duration > 0)
                                <p class="mt-1 text-xs text-gray-400">{{ __('Aktuell') }}: {{ $this->durationFormatted }}</p>
                            @endif
                        </div>

                        <x-input
                            wire:model="order"
                            type="number"
                            min="1"
                            label="{{ __('Reihenfolge') }} *"
                            placeholder="{{ __('z.B. 1, 2, 3...') }}"
                            icon="o-list-bullet"
                            required />

                        <x-toggle
                            wire:model="is_free"
                            label="{{ __('Kostenlose Vorschau') }}"
                            hint="{{ __('Kostenlose Lektionen können ohne Anmeldung angesehen werden') }}" />
                    </div>

                    <x-toggle
                        wire:model="is_published"
                        label="{{ __('Lektion veröffentlichen') }}"
                        hint="{{ __('Veröffentlichte Lektionen sind für Studenten sichtbar') }}" />
                </div>
            </x-card>

            <div class="flex flex-col justify-end gap-3 pt-4 sm:flex-row">
                <a href="{{ route('teacher.lessons.index', $course) }}" class="px-4 py-2 text-center text-gray-600 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                    {{ __('Abbrechen') }}
                </a>
                <button type="submit" class="px-4 py-2 text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                    <x-icon name="o-check" class="inline w-4 h-4 mr-1" />
                    {{ __('Lektion erstellen') }}
                </button>
            </div>
        </form>
    </div>
</div>
