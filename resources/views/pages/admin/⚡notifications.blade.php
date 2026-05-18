<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Mary\Traits\Toast;

new
#[Title('Notifications - Admin')]
#[Layout('layouts.app')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'type', history: true)]
    public string $typeFilter = '';

    public bool $showSendModal = false;
    public string $notificationType = 'info';
    public string $notificationTitle = '';
    public string $notificationMessage = '';
    public string $targetRole = 'all';
    public ?int $targetUserId = null;
    public string $actionUrl = '';
    public string $actionText = '';
    public string $broadcastVia = 'database';

    public function getNotificationsProperty()
    {
        $query = DatabaseNotification::query()
            ->with('notifiable')
            ->when($this->search, function($q) {
                $q->where('data->title', 'like', '%' . $this->search . '%')
                  ->orWhere('data->message', 'like', '%' . $this->search . '%');
            })
            ->when($this->typeFilter, fn($q) => $q->where('data->type', $this->typeFilter))
            ->latest();

        return $query->paginate(15);
    }

    public function getStatsProperty()
    {
        return [
            'total'   => DatabaseNotification::count(),
            'info'    => DatabaseNotification::where('data->type', 'info')->count(),
            'warning' => DatabaseNotification::where('data->type', 'warning')->count(),
            'success' => DatabaseNotification::where('data->type', 'success')->count(),
            'error'   => DatabaseNotification::where('data->type', 'error')->count(),
        ];
    }

    public function getUsersForSearchProperty()
    {
        return User::where('name', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->limit(10)
            ->get();
    }

    public function sendNotification(): void
    {
        $this->validate([
            'notificationTitle'   => 'required|string|max:255',
            'notificationMessage' => 'required|string|min:5',
            'targetRole'          => 'required|in:all,students,teachers,admins',
            'targetUserId'        => 'nullable|exists:users,id',
            'notificationType'    => 'required|in:info,warning,success,error',
            'actionUrl'           => 'nullable|url|max:500',
            'actionText'          => 'nullable|string|max:100',
        ]);

        $query = User::query();
        if ($this->targetUserId) {
            $query->where('id', $this->targetUserId);
        } else {
            switch ($this->targetRole) {
                case 'students': $query->where('role', 'student'); break;
                case 'teachers': $query->where('role', 'teacher'); break;
                case 'admins':   $query->where('role', 'admin'); break;
                default: // all
            }
        }

        $users = $query->get();
        $count = 0;

        foreach ($users as $user) {
            $user->notify(new \App\Notifications\CustomNotification(
                $this->notificationType,
                $this->notificationTitle,
                $this->notificationMessage,
                $this->actionUrl,
                $this->actionText
            ));
            $count++;
        }

        $this->success(__('Notification sent to :count user(s).', ['count' => $count]));
        $this->resetSendForm();
        $this->showSendModal = false;
    }

    public function deleteNotification($id): void
    {
        $notification = DatabaseNotification::findOrFail($id);
        $notification->delete();
        $this->success(__('Notification deleted.'));
    }

    public function deleteAllNotifications(): void
    {
        DatabaseNotification::truncate();
        $this->success(__('All notifications cleared.'));
    }

    public function resetSendForm(): void
    {
        $this->reset([
            'notificationTitle', 'notificationMessage', 'targetRole',
            'targetUserId', 'notificationType', 'actionUrl', 'actionText', 'broadcastVia'
        ]);
    }

    public function getTypeIcon($type): string
    {
        return match($type) {
            'info'    => 'o-information-circle',
            'warning' => 'o-exclamation-triangle',
            'success' => 'o-check-circle',
            'error'   => 'o-x-circle',
            default   => 'o-bell',
        };
    }

    public function getTypeColor($type): string
    {
        return match($type) {
            'info'    => 'text-info',
            'warning' => 'text-warning',
            'success' => 'text-success',
            'error'   => 'text-error',
            default   => 'text-primary',
        };
    }

    public function render()
    {
        return $this->view([
            'notifications' => $this->notifications,
            'stats'         => $this->stats,
            'users'         => $this->usersForSearch,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">🔔 {{ __('Notifications') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('Manage system notifications and broadcasts') }}</p>
            </div>
            <div class="flex gap-2">
                <x-button wire:click="$set('showSendModal', true)" label="{{ __('Send notification') }}" icon="o-paper-airplane" class="btn-primary" />
                <x-button wire:click="deleteAllNotifications" label="{{ __('Clear all') }}" icon="o-trash" class="btn-outline btn-error" wire:confirm="{{ __('Delete all notifications?') }}" />
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-5">
            <x-stat title="{{ __('Total') }}" :value="$stats['total']" icon="o-bell" class="text-primary" />
            <x-stat title="{{ __('Info') }}" :value="$stats['info']" icon="o-information-circle" class="text-info" />
            <x-stat title="{{ __('Warning') }}" :value="$stats['warning']" icon="o-exclamation-triangle" class="text-warning" />
            <x-stat title="{{ __('Success') }}" :value="$stats['success']" icon="o-check-circle" class="text-success" />
            <x-stat title="{{ __('Error') }}" :value="$stats['error']" icon="o-x-circle" class="text-error" />
        </div>

        {{-- Filters --}}
        <div class="p-4 mb-5 shadow-sm bg-base-100 rounded-xl">
            <div class="flex flex-col gap-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <x-input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search notifications...') }}" icon="o-magnifying-glass" class="w-full" clearable />
                    <x-select wire:model.live="typeFilter" :options="[
                        ['id' => '', 'name' => __('All types')],
                        ['id' => 'info', 'name' => __('Info')],
                        ['id' => 'warning', 'name' => __('Warning')],
                        ['id' => 'success', 'name' => __('Success')],
                        ['id' => 'error', 'name' => __('Error')],
                    ]" option-value="id" option-label="name" id="type_filter" name="type_filter" />
                </div>
            </div>
        </div>

        {{-- Notifications List --}}
        @if($notifications->count() > 0)
            {{-- Desktop table --}}
            <div class="hidden overflow-hidden shadow-sm md:block bg-base-100 rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-base-200">
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Type') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Title') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Message') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Recipient') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Created') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notifications as $notification)
                                @php $data = $notification->data; @endphp
                                <tr class="transition border-b hover:bg-base-200">
                                    <td class="px-4 py-3">
                                        <x-icon :name="$this->getTypeIcon($data['type'] ?? 'info')" :class="$this->getTypeColor($data['type'] ?? 'info') . ' w-5 h-5'" />
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium">{{ $data['title'] ?? '' }}</td>
                                    <td class="px-4 py-3 text-sm text-base-content/70">{{ Str::limit($data['message'] ?? '', 60) }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $notification->notifiable->name ?? __('All users') }}</td>
                                    <td class="px-4 py-3 text-sm text-base-content/60">{{ $notification->created_at->diffForHumans() }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" wire:click="deleteNotification({{ $notification->id }})" wire:confirm="{{ __('Delete this notification?') }}" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t bg-base-200">
                    {{ $notifications->links() }}
                </div>
            </div>

            {{-- Mobile cards --}}
            <div class="space-y-3 md:hidden">
                @foreach($notifications as $notification)
                    @php $data = $notification->data; @endphp
                    <x-card class="shadow-sm">
                        <div class="flex items-start gap-3">
                            <x-icon :name="$this->getTypeIcon($data['type'] ?? 'info')" :class="$this->getTypeColor($data['type'] ?? 'info') . ' w-6 h-6 flex-shrink-0'" />
                            <div class="flex-1">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold">{{ $data['title'] ?? '' }}</p>
                                        <p class="text-xs text-base-content/60">{{ $notification->created_at->diffForHumans() }}</p>
                                    </div>
                                    <x-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="deleteNotification({{ $notification->id }})" />
                                </div>
                                <p class="mt-1 text-sm text-base-content/70">{{ Str::limit($data['message'] ?? '', 80) }}</p>
                                <p class="mt-1 text-xs text-base-content/50">{{ __('To') }}: {{ $notification->notifiable->name ?? __('All users') }}</p>
                            </div>
                        </div>
                    </x-card>
                @endforeach
                <div class="mt-4">{{ $notifications->links() }}</div>
            </div>
        @else
            <x-card class="py-12 text-center">
                <x-icon name="o-bell-slash" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                <h3 class="mb-2 text-lg font-semibold">{{ __('No notifications') }}</h3>
                <p class="text-base-content/60">{{ __('No system notifications found.') }}</p>
            </x-card>
        @endif

        {{-- Send Notification Modal --}}
        <x-modal wire:model="showSendModal" title="{{ __('Send notification') }}" size="2xl" separator>
            <x-form wire:submit="sendNotification" no-separator>
                <div class="space-y-4">
                    <x-select wire:model="notificationType" label="{{ __('Notification type') }}" :options="[
                        ['id' => 'info', 'name' => __('Info')],
                        ['id' => 'warning', 'name' => __('Warning')],
                        ['id' => 'success', 'name' => __('Success')],
                        ['id' => 'error', 'name' => __('Error')],
                    ]" option-value="id" option-label="name" required />
                    <x-input wire:model="notificationTitle" label="{{ __('Title') }}" placeholder="{{ __('Important announcement') }}" required />
                    <x-textarea wire:model="notificationMessage" label="{{ __('Message') }}" rows="4" placeholder="{{ __('Your message here...') }}" required />
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-select wire:model="targetRole" label="{{ __('Send to') }}" :options="[
                            ['id' => 'all', 'name' => __('All users')],
                            ['id' => 'students', 'name' => __('Students only')],
                            ['id' => 'teachers', 'name' => __('Teachers only')],
                            ['id' => 'admins', 'name' => __('Admins only')],
                        ]" option-value="id" option-label="name" />
                        <x-choices-offline wire:model="targetUserId" label="{{ __('Specific user (optional)') }}" :options="$users" option-value="id" option-label="name" placeholder="{{ __('Select a user') }}" single clearable searchable />
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input wire:model="actionUrl" label="{{ __('Action URL (optional)') }}" placeholder="https://..." />
                        <x-input wire:model="actionText" label="{{ __('Action button text') }}" placeholder="{{ __('Learn more') }}" />
                    </div>
                    <x-select wire:model="broadcastVia" label="{{ __('Send via') }}" :options="[
                        ['id' => 'database', 'name' => __('Database only')],
                        ['id' => 'mail', 'name' => __('Email only')],
                        ['id' => 'both', 'name' => __('Both')],
                    ]" option-value="id" option-label="name" />
                </div>
                <x-slot:actions>
                    <x-button label="{{ __('Cancel') }}" wire:click="$set('showSendModal', false)" class="btn-ghost" />
                    <x-button label="{{ __('Send') }}" class="btn-primary" type="submit" spinner="sendNotification" />
                </x-slot:actions>
            </x-form>
        </x-modal>
    </div>
</div>
