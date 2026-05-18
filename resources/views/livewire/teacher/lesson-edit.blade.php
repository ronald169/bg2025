<?php
// app/Livewire/Teacher/LessonEdit.php

use App\Models\Course;
use App\Models\Lesson;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\{Title, Computed};
use Illuminate\Support\Str;
use Mary\Traits\Toast;

new
#[Title('Lektion bearbeiten - Lehrer')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use Toast;

    public Course $course;
    public Lesson $lesson;

    public $title = '';
    public $slug = '';
    public $description = '';
    public $content = '';
    public $video_url = '';
    public $duration = 0;
    public $order = 0;
    public $is_free = false;
    public $is_published = true;

    public function mount(Course $course, Lesson $lesson): void
    {
        if ($course->teacher_id !== auth()->id()) {
            abort(403);
        }

        $this->course = $course;
        $this->lesson = $lesson;

        $this->title = $lesson->title;
        $this->slug = $lesson->slug;
        $this->description = $lesson->description;
        $this->content = $lesson->content;
        $this->video_url = $lesson->video_url;
        $this->duration = $lesson->duration;
        $this->order = $lesson->order;
        $this->is_free = $lesson->is_free;
        $this->is_published = $lesson->is_published;

        // SEO
        $this->meta_title = $lesson->meta_title ?? '';
        $this->meta_description = $lesson->meta_description ?? '';
        $this->meta_keywords = $lesson->meta_keywords ?? '';
        $this->og_title = $lesson->og_title ?? '';
        $this->og_description = $lesson->og_description ?? '';
        $this->og_image = $lesson->og_image ?? '';
        $this->canonical_url = $lesson->canonical_url ?? '';
        $this->robots = $lesson->robots ?? 'index,follow';

    }

    public function updatedTitle($value): void
    {
        $this->slug = Str::slug($value);
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:lessons,slug,' . $this->lesson->id,
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

        $this->lesson->update([
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'content' => $cleanContent,
            'video_url' => $this->video_url,
            'duration' => $this->duration,
            'order' => $this->order,
            'is_free' => $this->is_free,
            'is_published' => $this->is_published,

            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'og_title' => $this->og_title,
            'og_description' => $this->og_description,
            'og_image' => $this->og_image,
            'canonical_url' => $this->canonical_url,
            'robots' => $this->robots,
        ]);

        $this->success('Lektion erfolgreich aktualisiert! 🎉');
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

    #[Computed]
    public function durationFormatted(): string
    {
        $seconds = $this->duration;
        if ($seconds < 60) return "{$seconds} sec";
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return $secs > 0 ? "{$minutes} min {$secs} sec" : "{$minutes} min";
    }
}
?>

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
        <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">✏️ {{ __('Lektion bearbeiten') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $course->title }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('student.lesson.show', ['course' => $course, 'lesson' => $lesson]) }}" target="_blank"
                   class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <x-icon name="o-eye" class="inline w-4 h-4 mr-1" />
                    {{ __('Vorschau') }}
                </a>
            </div>
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
                            required />
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

            <!-- SEO Section -->
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-chart-bar" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('SEO Optimization') }}</h2>
                </div>
                
                <div class="space-y-4">
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
                            icon="o-tag" />
                    </div>

                    <x-textarea
                        wire:model="meta_description"
                        label="{{ __('Meta Description') }}"
                        placeholder="{{ __('Short description for search engines') }}"
                        rows="2"
                        icon="o-document"
                        hint="{{ __('Recommended: 150-160 characters') }}" />

                    <div class="pt-2">
                        <div class="flex items-center gap-2 mb-3">
                            <x-icon name="o-share" class="w-4 h-4 text-blue-500" />
                            <h3 class="font-medium text-gray-900">{{ __('Social Media Sharing') }}</h3>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-input
                                wire:model="og_title"
                                label="{{ __('OG Title') }}"
                                placeholder="{{ __('Title when shared') }}"
                                icon="brands.facebook" />

                            <x-input
                                wire:model="og_image"
                                label="{{ __('OG Image URL') }}"
                                placeholder="{{ __('Image URL for sharing') }}"
                                icon="o-photo" />
                        </div>
                        <x-textarea
                            wire:model="og_description"
                            label="{{ __('OG Description') }}"
                            placeholder="{{ __('Description when shared') }}"
                            rows="2"
                            icon="o-document-text" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input
                            wire:model="canonical_url"
                            label="{{ __('Canonical URL') }}"
                            placeholder="{{ __('https://example.com/preferred-url') }}"
                            icon="o-link" />

                        <x-select
                            wire:model="robots"
                            label="{{ __('Robots Directive') }}"
                            :options="[
                                ['id' => 'index,follow', 'name' => 'index, follow'],
                                ['id' => 'noindex,follow', 'name' => 'noindex, follow'],
                                ['id' => 'index,nofollow', 'name' => 'index, nofollow'],
                                ['id' => 'noindex,nofollow', 'name' => 'noindex, nofollow']
                            ]"
                            icon="o-shield-check" />
                    </div>
                </div>
            </x-card>

            <div class="flex flex-col justify-end gap-3 pt-4 sm:flex-row">
                <a href="{{ route('teacher.lessons.index', $course) }}" class="px-4 py-2 text-center text-gray-600 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                    {{ __('Abbrechen') }}
                </a>
                <button type="submit" class="px-4 py-2 text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                    <x-icon name="o-check" class="inline w-4 h-4 mr-1" />
                    {{ __('Änderungen speichern') }}
                </button>
            </div>
        </form>

        <!-- Quiz Section -->
        @if($lesson->quiz)
        <div class="p-4 mt-6 border border-purple-200 rounded-lg bg-purple-50">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <x-icon name="o-document-text" class="w-5 h-5 text-purple-600" />
                    <div>
                        <p class="font-medium text-purple-800">{{ __('Quiz') }}</p>
                        <p class="text-sm text-purple-700">{{ $lesson->quiz->title }}</p>
                    </div>
                </div>
                <a href="{{ route('teacher.quizzes.edit', ['course' => $course, 'quiz' => $lesson->quiz]) }}"
                   class="px-3 py-1.5 text-sm text-purple-700 border border-purple-300 rounded-lg hover:bg-purple-100 transition">
                    {{ __('Quiz bearbeiten') }} →
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
