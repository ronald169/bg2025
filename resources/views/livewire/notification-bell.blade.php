<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public $unreadCount = 0;
    public $notifications = [];
    public $showDropdown = false;

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        $user = Auth::user();
        if ($user) {
            $this->unreadCount = $user->unreadNotifications()->count();
            $this->notifications = $user->notifications()
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($notification) {
                    $data = $notification->data;
                    return [
                        'id' => $notification->id,
                        'title' => $data['title'] ?? __('Notification'),
                        'message' => $data['message'] ?? '',
                        'type' => $data['type'] ?? 'info',
                        'is_read' => $notification->read_at !== null,
                        'time' => $notification->created_at->diffForHumans(),
                        'url' => $data['action_url'] ?? null,
                        'icon' => $data['icon'] ?? $this->getIconForType($data['type'] ?? 'info'),
                    ];
                });
        }
    }

    private function getIconForType(string $type): string
    {
        return match($type) {
            'success' => 'o-check-circle',
            'warning' => 'o-exclamation-triangle',
            'error' => 'o-x-circle',
            default => 'o-bell',
        };
    }

    public function markAsRead($notificationId): void
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
            $this->loadNotifications();
        }
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

    public function getUnreadCountProperty()
    {
        return $this->unreadCount;
    }
}; ?>

<div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
    <!-- Notification Bell Button -->
    <button @click="open = !open"
            class="relative p-2 text-gray-600 transition-colors rounded-lg hover:text-primary-600 hover:bg-gray-100 focus:outline-none">
        <x-icon name="o-bell" class="w-6 h-6" />
        @if($unreadCount > 0)
            <span class="absolute top-0 right-0 flex items-center justify-center w-5 h-5 text-xs text-white bg-red-500 rounded-full animate-pulse">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown Menu -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 z-50 mt-2 overflow-hidden bg-white border border-gray-200 shadow-xl w-80 rounded-xl"
         style="display: none;">

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50">
            <h3 class="font-semibold text-gray-900">{{ __('Notifications') }}</h3>
            @if($unreadCount > 0)
                <button wire:click="markAllAsRead"
                        class="text-xs font-medium text-primary-600 hover:text-primary-700">
                    {{ __('Mark all as read') }}
                </button>
            @endif
        </div>

        <!-- Notifications List -->
        <div class="overflow-y-auto max-h-96">
            @if($notifications->count() > 0)
                <div class="divide-y divide-gray-100">
                    @foreach($notifications as $notification)
                    <div class="p-4 hover:bg-gray-50 transition-colors {{ !$notification['is_read'] ? 'bg-blue-50/30' : '' }}">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 rounded-full {{ $notification['type'] === 'success' ? 'bg-green-100' : ($notification['type'] === 'warning' ? 'bg-yellow-100' : 'bg-blue-100') }} flex items-center justify-center">
                                    <x-icon :name="$notification['icon']"
                                            class="w-4 h-4 {{ $notification['type'] === 'success' ? 'text-green-600' : ($notification['type'] === 'warning' ? 'text-yellow-600' : 'text-blue-600') }}" />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-900">{{ $notification['title'] }}</p>
                                    <span class="text-xs text-gray-400">{{ $notification['time'] }}</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-600 line-clamp-2">{{ $notification['message'] }}</p>
                                @if($notification['url'])
                                    <a href="{{ $notification['url'] }}"
                                       wire:navigate
                                       @click="open = false"
                                       class="inline-block mt-2 text-xs text-primary-600 hover:text-primary-700">
                                        {{ __('View details') }} →
                                    </a>
                                @endif
                            </div>
                            @if(!$notification['is_read'])
                                <button wire:click="markAsRead('{{ $notification['id'] }}')"
                                        class="flex-shrink-0 text-gray-400 hover:text-gray-600">
                                    <x-icon name="o-check" class="w-4 h-4" />
                                </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center">
                    <x-icon name="o-bell-slash" class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                    <p class="text-sm text-gray-500">{{ __('No notifications') }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ __('New notifications will appear here') }}</p>
                </div>
            @endif
        </div>

        <!-- Footer -->
        @if($notifications->count() > 0)
            <div class="p-3 border-t border-gray-100 bg-gray-50">
                <a href="{{ route('notifications.index') }}"
                   wire:navigate
                   @click="open = false"
                   class="block text-sm font-medium text-center text-primary-600 hover:text-primary-700">
                    {{ __('View all notifications') }}
                </a>
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endpush
