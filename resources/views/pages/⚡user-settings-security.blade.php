<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Mary\Traits\Toast;

new
#[Title('Security Settings')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';
    public bool $two_factor_enabled = false;

    public function mount(): void
    {
        $this->two_factor_enabled = auth()->user()->two_factor_enabled ?? false;
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();
        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', __('Current password is incorrect.'));
            return;
        }

        $user->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->success(__('Password updated successfully!'));
    }

    public function toggleTwoFactor(): void
    {
        // Simuler l'activation/désactivation (à adapter selon votre logique 2FA)
        $this->two_factor_enabled = !$this->two_factor_enabled;
        auth()->user()->update(['two_factor_enabled' => $this->two_factor_enabled]);
        $this->success($this->two_factor_enabled ? __('Two-factor authentication enabled.') : __('Two-factor authentication disabled.'));
    }

    public function render()
    {
        return $this->view();
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-3xl px-3 mx-auto md:px-4">

        {{-- Navigation --}}
        <div class="mb-5">
            <a href="{{ route('settings.account') }}" wire:navigate class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to settings') }}
            </a>
        </div>

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold md:text-3xl">🔒 {{ __('Security Settings') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ __('Manage your account security') }}</p>
        </div>

        {{-- Two-factor authentication --}}
        <x-card class="mb-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold">{{ __('Two-factor authentication') }}</h2>
                    <p class="text-sm text-base-content/70">{{ __('Add an extra layer of security to your account') }}</p>
                </div>
                <x-button wire:click="toggleTwoFactor" :label="$two_factor_enabled ? __('Disable') : __('Enable')" :class="$two_factor_enabled ? 'btn-outline btn-error' : 'btn-primary'" />
            </div>
            @if($two_factor_enabled)
                <div class="p-3 mt-4 border rounded-lg bg-success/10 border-success/20">
                    <p class="text-sm text-success">{{ __('Two-factor authentication is enabled. Your account is protected.') }}</p>
                </div>
            @endif
        </x-card>

        {{-- Change password --}}
        <x-card class="shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">{{ __('Change password') }}</h2>
            <x-form wire:submit="updatePassword" no-separator>
                <x-password wire:model="current_password" label="{{ __('Current password') }} *" required />
                <div class="grid gap-4 md:grid-cols-2">
                    <x-password wire:model="new_password" label="{{ __('New password') }} *" required hint="{{ __('Minimum 8 characters') }}" />
                    <x-password wire:model="new_password_confirmation" label="{{ __('Confirm new password') }} *" required />
                </div>
                <x-slot:actions>
                    <div class="flex justify-end">
                        <x-button label="{{ __('Update password') }}" class="btn-primary" type="submit" spinner="updatePassword" />
                    </div>
                </x-slot:actions>
            </x-form>
        </x-card>

        {{-- Session management (optional) --}}
        {{-- <div class="mt-6">
            <x-card class="shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">{{ __('Active sessions') }}</h2>
                <p class="mb-4 text-sm text-base-content/70">{{ __('Manage your active sessions across devices') }}</p>
                <x-button label="{{ __('Log out other devices') }}" class="btn-outline btn-warning" wire:click="logoutOtherDevices" wire:confirm="{{ __('This will log you out on all other devices. Continue?') }}" />
            </x-card>
        </div> --}}
    </div>
</div>
