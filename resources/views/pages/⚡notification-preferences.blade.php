<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Title('Notification Preferences')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public bool $email_notifications = true;
    public bool $push_notifications = false;
    public bool $course_announcements = true;
    public bool $message_notifications = true;
    public bool $quiz_reminders = true;
    public bool $study_reminders = true;
    public bool $achievement_notifications = true;

    public function mount(): void
    {
        $this->loadPreferences();
    }

    public function loadPreferences(): void
    {
        $prefs = auth()->user()->notification_preferences ?? [];
        $this->email_notifications = $prefs['email'] ?? true;
        $this->push_notifications = $prefs['push'] ?? false;
        $this->course_announcements = $prefs['course_announcements'] ?? true;
        $this->message_notifications = $prefs['messages'] ?? true;
        $this->quiz_reminders = $prefs['quiz_reminders'] ?? true;
        $this->study_reminders = $prefs['study_reminders'] ?? true;
        $this->achievement_notifications = $prefs['achievements'] ?? true;
    }

    public function save(): void
    {
        auth()->user()->update([
            'notification_preferences' => [
                'email' => $this->email_notifications,
                'push' => $this->push_notifications,
                'course_announcements' => $this->course_announcements,
                'messages' => $this->message_notifications,
                'quiz_reminders' => $this->quiz_reminders,
                'study_reminders' => $this->study_reminders,
                'achievements' => $this->achievement_notifications,
            ],
        ]);
        $this->success(__('Notification preferences saved!'));
    }

    public function render()
    {
        return $this->view();
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-3xl px-3 mx-auto md:px-4">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold md:text-3xl">⚙️ {{ __('Notification Preferences') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ __('Choose how you want to be notified') }}</p>
        </div>

        <x-card class="shadow-sm">
            <x-form wire:submit="save" no-separator>
                <div class="space-y-6">
                    {{-- Channels --}}
                    <div>
                        <h2 class="mb-3 text-lg font-semibold">{{ __('Notification channels') }}</h2>
                        <div class="space-y-3">
                            <x-toggle wire:model="email_notifications" label="{{ __('Email notifications') }}" hint="{{ __('Receive notifications by email') }}" />
                            <x-toggle wire:model="push_notifications" label="{{ __('Push notifications') }}" hint="{{ __('Receive notifications in your browser') }}" />
                        </div>
                    </div>

                    <div class="divider"></div>

                    {{-- Event types --}}
                    <div>
                        <h2 class="mb-3 text-lg font-semibold">{{ __('When to notify me') }}</h2>
                        <div class="space-y-3">
                            <x-toggle wire:model="course_announcements" label="{{ __('Course announcements') }}" hint="{{ __('New announcements in your courses') }}" />
                            <x-toggle wire:model="message_notifications" label="{{ __('Messages') }}" hint="{{ __('When someone sends you a message') }}" />
                            <x-toggle wire:model="quiz_reminders" label="{{ __('Quiz reminders') }}" hint="{{ __('Reminders for upcoming quizzes') }}" />
                            <x-toggle wire:model="study_reminders" label="{{ __('Study reminders') }}" hint="{{ __('Daily study reminders') }}" />
                            <x-toggle wire:model="achievement_notifications" label="{{ __('Achievements') }}" hint="{{ __('When you unlock new achievements') }}" />
                        </div>
                    </div>
                </div>

                <x-slot:actions>
                    <div class="flex justify-end">
                        <x-button label="{{ __('Save preferences') }}" class="btn-primary" type="submit" spinner="save" />
                    </div>
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>
