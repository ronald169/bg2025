<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public array $preferences = [];
    public array $availableChannels = ['database', 'mail'];

    public function mount(): void
    {
        $user = Auth::user();
        $configPreferences = config('notifications.preferences', []);

        foreach ($configPreferences as $type => $config) {
            $pref = $user->notificationPreferences()->where('notification_type', $type)->first();

            $this->preferences[$type] = [
                'enabled' => $pref ? $pref->is_enabled : true,
                'channels' => $pref ? $pref->channels : ($config['default'] ?? ['database']),
                'label' => $config['label'] ?? $type,
                'description' => $config['description'] ?? '',
            ];
        }
    }

    public function savePreferences(): void
    {
        $user = Auth::user();

        foreach ($this->preferences as $type => $preference) {
            $user->updateNotificationPreference(
                $type,
                $preference['channels'],
                $preference['enabled']
            );
        }

        $this->dispatch('notify', message: __('Notification preferences saved successfully!'));
    }

    public function toggleChannel(string $type, string $channel): void
    {
        if (in_array($channel, $this->preferences[$type]['channels'])) {
            $this->preferences[$type]['channels'] = array_diff(
                $this->preferences[$type]['channels'],
                [$channel]
            );
        } else {
            $this->preferences[$type]['channels'][] = $channel;
        }
    }
}; ?>

<div>
    <x-card title="{!! __('Notification Preferences') !!}" separator shadow>
        <x-slot:menu>
            <x-button icon="o-check" class="btn-primary" wire:click="savePreferences">
                {!! __('Save Changes') !!}
            </x-button>
        </x-slot:menu>

        <p class="text-gray-600 mb-6">
            {!! __('Choose how and when you want to receive notifications from BrainGenius.') !!}
        </p>

        @foreach($preferences as $type => $preference)
            <div class="border rounded-lg p-4 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="font-semibold text-lg">{{ $preference['label'] }}</h3>
                        <p class="text-sm text-gray-600">{{ $preference['description'] }}</p>
                    </div>
                    <x-toggle :checked="$preference['enabled']"
                              wire:change="$set('preferences.{{ $type }}.enabled', $event.target.checked)" />
                </div>

                @if($preference['enabled'])
                <div class="mt-3">
                    <p class="text-sm text-gray-600 mb-2">{!! __('Receive via:') !!}</p>
                    <div class="flex flex-wrap gap-3">
                        @foreach($availableChannels as $channel)
                            <x-badge
                                :value="$channel"
                                :class="in_array($channel, $preference['channels']) ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'"
                                icon="{{ $channel === 'mail' ? 'o-envelope' : 'o-bell' }}"
                                class="cursor-pointer hover:opacity-80 transition-opacity"
                                wire:click="toggleChannel('{{ $type }}', '{{ $channel }}')"
                            />
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        @endforeach

        <div class="mt-6 pt-6 border-t">
            <h4 class="font-semibold mb-3">{!! __('Notification Channels') !!}</h4>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex items-center gap-2">
                    <x-icon name="o-bell" class="w-4 h-4 text-blue-500" />
                    <span>{!! __('In-app notifications - Appear in your notification center') !!}</span>
                </li>
                <li class="flex items-center gap-2">
                    <x-icon name="o-envelope" class="w-4 h-4 text-green-500" />
                    <span>{!! __('Email notifications - Sent to your registered email') !!}</span>
                </li>
            </ul>
        </div>
    </x-card>
</div>
