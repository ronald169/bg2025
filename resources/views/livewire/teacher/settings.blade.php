<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Mary\Traits\Toast;

new
#[Title('Settings')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use Toast;

    public $activeTab = 'profile';

    // Profile
    public $name = '';
    public $email = '';
    public $bio = '';
    public $phone = '';

    // Notifications
    public $email_notifications = true;
    public $push_notifications = true;

    // Billing
    public $payment_method = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->bio = $user->bio;
        $this->phone = $user->phone;
        $this->email_notifications = $user->email_notifications;
        $this->push_notifications = $user->push_notifications;
    }

    public function saveProfile(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . auth()->id(),
            'bio' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
        ]);

        auth()->user()->update([
            'name' => $this->name,
            'email' => $this->email,
            'bio' => $this->bio,
            'phone' => $this->phone,
        ]);

        $this->success(__('Profile updated successfully!'));
    }

    public function saveNotifications(): void
    {
        auth()->user()->update([
            'email_notifications' => $this->email_notifications,
            'push_notifications' => $this->push_notifications,
        ]);

        $this->success(__('Notification preferences saved!'));
    }
}; ?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Settings') }}</h1>
        <p class="text-gray-600">{{ __('Manage your account settings and preferences') }}</p>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="flex space-x-8">
            <button wire:click="$set('activeTab', 'profile')"
                    class="pb-4 px-1 font-medium text-sm {{ $activeTab === 'profile' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-500 hover:text-gray-700' }}">
                {{ __('Profile') }}
            </button>
            <button wire:click="$set('activeTab', 'notifications')"
                    class="pb-4 px-1 font-medium text-sm {{ $activeTab === 'notifications' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-500 hover:text-gray-700' }}">
                {{ __('Notifications') }}
            </button>
            <button wire:click="$set('activeTab', 'billing')"
                    class="pb-4 px-1 font-medium text-sm {{ $activeTab === 'billing' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-500 hover:text-gray-700' }}">
                {{ __('Billing') }}
            </button>
        </nav>
    </div>

    <!-- Profile Tab -->
    @if($activeTab === 'profile')
    <form wire:submit="saveProfile" class="space-y-6">
        <x-card title="{{ __('Profile Information') }}" class="shadow-sm">
            <div class="space-y-4">
                <x-input
                    wire:model="name"
                    label="{{ __('Full Name') }}"
                    required />

                <x-input
                    wire:model="email"
                    type="email"
                    label="{{ __('Email Address') }}"
                    required />

                <x-input
                    wire:model="phone"
                    label="{{ __('Phone Number') }}" />

                <x-textarea
                    wire:model="bio"
                    label="{{ __('Bio') }}"
                    placeholder="{{ __('Tell us about yourself...') }}"
                    rows="3" />
            </div>
        </x-card>

        <div class="flex justify-end">
            <x-button type="submit" class="btn-primary" spinner="saveProfile">
                {{ __('Save Changes') }}
            </x-button>
        </div>
    </form>

    <!-- Notifications Tab -->
    @elseif($activeTab === 'notifications')
    <form wire:submit="saveNotifications" class="space-y-6">
        <x-card title="{{ __('Notification Preferences') }}" class="shadow-sm">
            <div class="space-y-4">
                <x-toggle
                    wire:model="email_notifications"
                    label="{{ __('Email Notifications') }}"
                    hint="{{ __('Receive updates via email') }}" />

                <x-toggle
                    wire:model="push_notifications"
                    label="{{ __('Push Notifications') }}"
                    hint="{{ __('Receive in-app notifications') }}" />
            </div>
        </x-card>

        <div class="flex justify-end">
            <x-button type="submit" class="btn-primary" spinner="saveNotifications">
                {{ __('Save Preferences') }}
            </x-button>
        </div>
    </form>

    <!-- Billing Tab -->
    @elseif($activeTab === 'billing')
    <x-card title="{{ __('Payment Method') }}" class="shadow-sm">
        <div class="py-8 text-center">
            <x-icon name="o-credit-card" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
            <p class="mb-4 text-gray-600">{{ __('No payment method on file') }}</p>
            <x-button class="btn-primary">
                {{ __('Add Payment Method') }}
            </x-button>
        </div>
    </x-card>

    <x-card title="{{ __('Billing History') }}" class="mt-6 shadow-sm">
        <div class="py-8 text-center">
            <p class="text-gray-500">{{ __('No invoices yet') }}</p>
        </div>
    </x-card>
    @endif
</div>
