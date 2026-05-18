<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Str;
use Mary\Traits\Toast;

new
#[Title('Edit Lesson - Teacher')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public Course $course;
    public Lesson $lesson;

    public string $title = '';
    public string $slug = '';
    public string $description = '';
    public string $content = '';
    public string $video_url = '';
    public float $duration_minutes = 0; // Saisie en minutes
    public int $order = 0;
    public bool $is_free = true;
    public bool $is_published = true;

    public function getFormattedDurationProperty(): string
    {
        $minutes = $this->duration_minutes;
        if ($minutes < 1) return __('0 min');
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        if ($hours > 0) {
            return $hours . 'h ' . ($mins > 0 ? $mins . ' min' : '');
        }
        return $mins . ' min';
    }

    public function mount(Course $course, Lesson $lesson): void
    {
        if ($course->teacher_id !== auth()->id() || $lesson->course_id !== $course->id) {
            abort(403);
        }
        $this->course = $course;
        $this->lesson = $lesson;
        $this->fillForm();
    }

    public function fillForm(): void
    {
        $this->title = $this->lesson->title;
        $this->slug = $this->lesson->slug;
        $this->description = $this->lesson->description;
        $this->content = $this->lesson->content;
        $this->video_url = $this->lesson->video_url ?? '';
        // Convertir les secondes stockées en minutes pour l'affichage
        $this->duration_minutes = round($this->lesson->duration / 60, 1);
        $this->order = $this->lesson->order;
        $this->is_free = $this->lesson->is_free;
        $this->is_published = $this->lesson->is_published;
    }

    public function updatedTitle($value): void
    {
        // Auto-update slug seulement s'il n'a pas été modifié manuellement
        if ($this->slug === $this->lesson->slug) {
            $this->slug = Str::slug($value);
        }
    }

    public function update(): void
    {
        $this->validate([
            'title'            => 'required|string|max:255',
            'slug'             => 'required|string|unique:lessons,slug,' . $this->lesson->id,
            'description'      => 'nullable|string|max:500',
            'content'          => 'nullable|string',
            'video_url'        => 'nullable|url',
            'duration_minutes' => 'nullable|numeric|min:0|max:1440',
            'order'            => 'required|integer|min:1',
        ], [
            'title.required'       => __('Please enter a lesson title.'),
            'slug.required'        => __('URL slug is required.'),
            'slug.unique'          => __('This URL slug is already in use.'),
            'order.required'       => __('Please specify the order.'),
            'duration_minutes.max' => __('Duration cannot exceed 24 hours (1440 minutes).'),
        ]);

        $duration_seconds = round($this->duration_minutes * 60);
        $cleanContent = $this->cleanHtmlContent($this->content);

        $this->lesson->update([
            'title'        => $this->title,
            'slug'         => $this->slug,
            'description'  => $this->description,
            'content'      => $cleanContent,
            'video_url'    => $this->video_url,
            'duration'     => $duration_seconds,
            'order'        => $this->order,
            'is_free'      => $this->is_free,
            'is_published' => $this->is_published,
        ]);

        $this->success(__('Lesson updated successfully! 🎉'));
        $this->redirectRoute('teacher.lessons.index', $this->course);
    }

    private function cleanHtmlContent($content): string
    {
        $baseUrl = url('/');
        $content = str_replace($baseUrl, '', $content);
        $content = preg_replace('/<p>\s*<\/p>/', '', $content);
        return $content;
    }

    public function render()
    {
        return $this->view([
            'formattedDuration' => $this->formattedDuration,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-5xl px-3 mx-auto md:px-4">

        {{-- Navigation --}}
        <div class="mb-5">
            <a href="{{ route('teacher.lessons.index', $course) }}" wire:navigate class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to lessons') }}
            </a>
        </div>

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-xl font-bold md:text-2xl">✏️ {{ __('Edit Lesson') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ $course->title }} / {{ $lesson->title }}</p>
        </div>

        <x-form wire:submit="update" class="space-y-5">
            <x-card class="shadow-sm">
                <div class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input wire:model="title" label="{{ __('Lesson Title') }}" placeholder="{{ __('e.g. Introduction to German Grammar') }}" icon="o-pencil" required />
                        <x-input wire:model="slug" label="{{ __('URL Slug') }}" placeholder="{{ __('introduction-german-grammar') }}" icon="o-link" hint="{{ __('Auto-generated from title') }}" required />
                    </div>
                    <x-textarea wire:model="description" label="{{ __('Short Description') }}" placeholder="{{ __('What will students learn in this lesson?') }}" rows="2" icon="o-document-text" />
                    <x-input wire:model="video_url" label="{{ __('Video URL') }}" placeholder="{{ __('https://youtube.com/... or https://vimeo.com/...') }}" icon="o-video-camera" hint="{{ __('YouTube, Vimeo or other video platforms') }}" />

                    <div>
                        <label class="block mb-1 text-sm font-medium text-base-content/70">{{ __('Lesson Content') }}</label>
                        {{-- <x-markdown wire:model="content" rows="12" placeholder="{{ __('HTML content – you can add images, tables, formatting') }}" /> --}}
                        <x-editor wire:model="content" :config="config('tinymce.config')" folder="lessons/{{ $course->id }}/{{ now()->format('Y/m') }}" disk="public" />
                        <p class="mt-1 text-xs text-base-content/50">{{ __('HTML is supported. You can add images, tables and formatting.') }}</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <x-input wire:model="duration_minutes" type="number" step="0.5" min="0" max="1440" label="{{ __('Duration (minutes)') }}" placeholder="{{ __('e.g. 10.5') }}" icon="o-clock" />
                            @if($duration_minutes > 0)
                                <p class="mt-1 text-xs text-base-content/50">{{ __('Formatted') }}: {{ $formattedDuration }}</p>
                            @endif
                        </div>
                        <x-input wire:model="order" type="number" min="1" label="{{ __('Order') }}" placeholder="{{ __('1, 2, 3...') }}" icon="o-list-bullet" required />
                        <x-toggle wire:model="is_free" label="{{ __('Free preview') }}" hint="{{ __('Free lessons can be viewed without enrollment') }}" />
                    </div>

                    <x-toggle wire:model="is_published" label="{{ __('Publish lesson') }}" hint="{{ __('Published lessons are visible to students') }}" />
                </div>
            </x-card>

            <x-slot:actions>
                <div class="flex justify-end gap-3">
                    <a href="{{ route('teacher.lessons.index', $course) }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                    <x-button label="{{ __('Update Lesson') }}" class="btn-primary" type="submit" spinner="update" />
                </div>
            </x-slot:actions>
        </x-form>
    </div>
</div>
