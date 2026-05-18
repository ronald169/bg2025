<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Notifications\DatabaseNotification;
use Mary\Traits\Toast;

new
#[Title('Notifications')]
#[Layout('layouts.app')]
class extends Component {
    use WithPagination, Toast;

    public string $activeTab = 'list';

    // Preferences
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

    public function getNotificationsProperty()
    {
        return auth()->user()->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getUnreadCountProperty()
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function markAsRead($id): void
    {
        auth()->user()->notifications()->findOrFail($id)->markAsRead();
        $this->success(__('Notification marked as read.'));
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->success(__('All notifications marked as read.'));
    }

    public function deleteNotification($id): void
    {
        auth()->user()->notifications()->findOrFail($id)->delete();
        $this->success(__('Notification deleted.'));
    }

    public function deleteAllNotifications(): void
    {
        auth()->user()->notifications()->delete();
        $this->success(__('All notifications cleared.'));
    }

    public function savePreferences(): void
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
        return $this->view([
            'notifications' => $this->notifications,
            'unreadCount'   => $this->unreadCount,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-5xl px-3 mx-auto md:px-4">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold md:text-3xl">🔔 {{ __('Notifications') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ __('Manage your notifications and preferences') }}</p>
        </div>

        {{-- Tabs --}}
        <div class="mb-6 tabs tabs-boxed">
            <a class="tab {{ $activeTab === 'list' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'list')">📋 {{ __('Notifications list') }}</a>
            <a class="tab {{ $activeTab === 'preferences' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'preferences')">⚙️ {{ __('Preferences') }}</a>
        </div>

        {{-- Tab: Notifications List --}}
        @if($activeTab === 'list')
            {{-- Stats --}}
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div class="flex items-center gap-3">
                    <x-badge :value="$notifications->total() . ' ' . __('total')" class="badge-neutral badge-soft" />
                    @if($unreadCount > 0)
                        <x-badge :value="$unreadCount . ' ' . __('unread')" class="badge-primary badge-soft" />
                    @endif
                </div>
                <div class="flex gap-2">
                    @if($unreadCount > 0)
                        <x-button wire:click="markAllAsRead" label="{{ __('Mark all as read') }}" icon="o-check" class="btn-outline btn-sm" />
                    @endif
                    @if($notifications->total() > 0)
                        <x-button wire:click="deleteAllNotifications" label="{{ __('Clear all') }}" icon="o-trash" class="btn-outline btn-sm text-error" wire:confirm="{{ __('Delete all notifications?') }}" />
                    @endif
                </div>
            </div>

            @if($notifications->total() > 0)
                <div class="space-y-3">
                    @foreach($notifications as $notification)
                        @php
                            $data = $notification->data;
                            $typeIcon = match($data['type'] ?? 'info') {
                                'info' => 'o-information-circle',
                                'warning' => 'o-exclamation-triangle',
                                'success' => 'o-check-circle',
                                'error' => 'o-x-circle',
                                default => 'o-bell',
                            };
                            $typeColor = match($data['type'] ?? 'info') {
                                'info' => 'text-info',
                                'warning' => 'text-warning',
                                'success' => 'text-success',
                                'error' => 'text-error',
                                default => 'text-primary',
                            };
                        @endphp
                        <div class="p-4 transition border rounded-lg hover:bg-base-200 {{ $notification->read_at ? 'bg-base-100' : 'bg-primary/5 border-primary/30' }}">
                            <div class="flex items-start gap-3">
                                <x-icon :name="$typeIcon" :class="$typeColor . ' w-5 h-5 flex-shrink-0 mt-0.5'" />
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="font-semibold">{{ $data['title'] ?? __('Notification') }}</h3>
                                        <span class="text-xs text-base-content/50">{{ $notification->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-base-content/80">{{ $data['message'] ?? '' }}</p>
                                    @if(!empty($data['action_url']) && !empty($data['action_text']))
                                        <a href="{{ $data['action_url'] }}" class="inline-block mt-2 text-sm text-primary hover:underline">
                                            {{ $data['action_text'] }} →
                                        </a>
                                    @endif
                                </div>
                                <div class="flex gap-1">
                                    @if(!$notification->read_at)
                                        <x-button icon="o-check" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Mark as read') }}" wire:click="markAsRead({{ $notification->id }})" />
                                    @endif
                                    <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" tooltip-left="{{ __('Delete') }}" wire:click="deleteNotification({{ $notification->id }})" wire:confirm="{{ __('Delete this notification?') }}" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6">{{ $notifications->links() }}</div>
            @else
                <x-card class="py-12 text-center">
                    <x-icon name="o-bell-slash" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <h3 class="mb-2 text-lg font-semibold">{{ __('No notifications') }}</h3>
                    <p class="text-base-content/60">{{ __('You have no notifications at the moment.') }}</p>
                </x-card>
            @endif
        @endif

        {{-- Tab: Preferences --}}
        @if($activeTab === 'preferences')
            <x-card class="shadow-sm">
                <x-form wire:submit="savePreferences" no-separator>
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
                            <x-button label="{{ __('Save preferences') }}" class="btn-primary" type="submit" spinner="savePreferences" />
                        </div>
                    </x-slot:actions>
                </x-form>
            </x-card>
        @endif
    </div>
</div>
