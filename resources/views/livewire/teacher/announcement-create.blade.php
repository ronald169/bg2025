<?php

use App\Models\Announcement;
use App\Models\Course;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Mary\Traits\Toast;

new
#[Title('Create Announcement')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use Toast;

    public $courses = [];
    public $title = '';
    public $content = '';
    public $course_id = null;
    public $is_important = false;
    public $send_email = true;

    public function mount(): void
    {
        $this->courses = Course::where('teacher_id', auth()->id())->get();
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'course_id' => 'required|exists:courses,id',
        ]);

        Announcement::create([
            'teacher_id' => auth()->id(),
            'course_id' => $this->course_id,
            'title' => $this->title,
            'content' => $this->content,
            'is_important' => $this->is_important,
            'send_email' => $this->send_email,
        ]);

        $this->success(__('Announcement created successfully!'));
        $this->redirectRoute('teacher.announcements', navigate: true);
    }
}; ?>

<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('teacher.announcements') }}" class="inline-block mb-2 text-primary-600 hover:text-primary-700">
            ← {{ __('Back to announcements') }}
        </a>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Create Announcement') }}</h1>
        <p class="text-gray-600">{{ __('Share important information with your students') }}</p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <x-card title="{{ __('Announcement Details') }}" class="shadow-sm">
            <div class="space-y-4">
                <x-select
                    wire:model="course_id"
                    label="{{ __('Course') }}"
                    :options="$courses->map(fn($c) => ['id' => $c->id, 'name' => $c->title])->toArray()"
                    placeholder="{{ __('Select a course') }}"
                    required />

                <x-input
                    wire:model="title"
                    label="{{ __('Title') }}"
                    placeholder="{{ __('Announcement title') }}"
                    required />

                <x-textarea
                    wire:model="content"
                    label="{{ __('Content') }}"
                    placeholder="{{ __('Write your announcement here...') }}"
                    rows="6"
                    required />

                <div class="space-y-2">
                    <x-toggle
                        wire:model="is_important"
                        label="{{ __('Mark as important') }}"
                        hint="{{ __('Important announcements are highlighted') }}" />

                    <x-toggle
                        wire:model="send_email"
                        label="{{ __('Send email notification') }}"
                        hint="{{ __('Students will receive an email') }}" />
                </div>
            </div>
        </x-card>

        <div class="flex justify-end space-x-3">
            <x-button link="{{ route('teacher.announcements') }}" class="btn-ghost">
                {{ __('Cancel') }}
            </x-button>
            <x-button type="submit" class="btn-primary" spinner="save">
                {{ __('Create Announcement') }}
            </x-button>
        </div>
    </form>
</div>
