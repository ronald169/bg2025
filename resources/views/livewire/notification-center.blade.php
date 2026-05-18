<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

new class extends Component {
    public $notifications;
    public $unreadCount;
    public $showAll = false;

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        $user = Auth::user();
        $query = $user->notifications()->latest();

        if (!$this->showAll) {
            $query->take(10);
        }

        $this->notifications = $query->get();
        $this->unreadCount = $user->unreadNotifications()->count();
    }

    public function markAsRead(string $id): void
    {
        $notification = DatabaseNotification::findOrFail($id);
        $notification->markAsRead();

        $this->loadNotifications();
        $this->dispatch('notification-read');
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);
        $this->loadNotifications();
        $this->dispatch('notify', message: __('All notifications marked as read'));
    }

    public function clearAll(): void
    {
        Auth::user()->notifications()->delete();
        $this->loadNotifications();
        $this->dispatch('notify', message: __('All notifications cleared'));
    }

    public function toggleShowAll(): void
    {
        $this->showAll = !$this->showAll;
        $this->loadNotifications();
    }
}; ?>

<div>
    <x-card title="{!! __('Notifications') !!}" separator shadow>
        <x-slot:menu>
            <div class="flex items-center gap-2">
                @if($unreadCount > 0)
                    <x-badge value="{{ $unreadCount }}" class="bg-red-500 text-white" />
                    <x-button icon="o-check" class="btn-ghost" wire:click="markAllAsRead">
                        {!! __('Mark all as read') !!}
                    </x-button>
                @endif
                <x-button icon="o-trash" class="btn-ghost text-red-500" wire:click="clearAll">
                    {!! __('Clear all') !!}
                </x-button>
            </div>
        </x-slot:menu>

        @if($notifications->isEmpty())
            <div class="text-center py-8">
                <x-icon name="o-bell-slash" class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                <p class="text-gray-500">{!! __('No notifications yet') !!}</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $isUnread = is_null($notification->read_at);
                    @endphp

                    <div class="border rounded-lg p-4 {{ $isUnread ? 'bg-blue-50 border-blue-200' : 'bg-white' }} hover:shadow-sm transition-shadow">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <x-icon name="{{ $data['icon'] ?? 'o-bell' }}"
                                        class="w-6 h-6 {{ $isUnread ? 'text-blue-500' : 'text-gray-400' }}" />
                            </div>

                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <h4 class="font-semibold {{ $isUnread ? 'text-blue-900' : 'text-gray-900' }}">
                                        {{ $data['title'] ?? 'Notification' }}
                                    </h4>
                                    <span class="text-xs text-gray-500">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <p class="text-gray-600 mt-1">{{ $data['message'] ?? '' }}</p>

                                @if(isset($data['action_url']))
                                    <div class="mt-3 flex items-center gap-3">
                                        <x-button :link="$data['action_url']" size="xs" class="btn-primary">
                                            {{ $data['action_text'] ?? 'View' }}
                                        </x-button>

                                        @if($isUnread)
                                            <x-button size="xs" class="btn-ghost"
                                                      wire:click="markAsRead('{{ $notification->id }}')">
                                                {!! __('Mark as read') !!}
                                            </x-button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(!$showAll && $notifications->count() >= 10)
                <div class="text-center mt-6">
                    <x-button wire:click="toggleShowAll" class="btn-ghost">
                        {!! __('Load more notifications') !!}
                    </x-button>
                </div>
            @endif
        @endif
    </x-card>
</div>
