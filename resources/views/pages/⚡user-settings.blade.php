<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Hash;
use Mary\Traits\Toast;

new
#[Title('Settings')]
#[Layout('layouts.app')]
class extends Component {
    use WithFileUploads, Toast;

    #[Url(as: 'tab', history: true)]
    public string $activeTab = 'account';

    // Account settings
    public string $name = '';
    public string $email = '';
    public ?string $phone = '';
    public ?string $bio = '';
    public $avatar = null;
    public ?string $language = 'en';
    public ?string $timezone = 'UTC';

    // Security settings
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $this->loadAccountSettings();
    }

    public function loadAccountSettings(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->bio = $user->bio;
        $this->language = $user->language ?? 'en';
        $this->timezone = $user->timezone ?? 'UTC';
    }

    public function updateAccount(): void
    {
        $this->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . auth()->id(),
            'phone'    => 'nullable|string|max:20',
            'bio'      => 'nullable|string|max:500',
            'avatar'   => 'nullable|image|max:1024',
            'language' => 'required|string|in:en,fr,de',
            'timezone' => 'required|string',
        ]);

        $data = [
            'name'     => $this->name,
            'email'    => $this->email,
            'phone'    => $this->phone,
            'bio'      => $this->bio,
            'language' => $this->language,
            'timezone' => $this->timezone,
        ];

        if ($this->avatar) {
            $path = $this->avatar->store('avatars', 'public');
            $data['avatar'] = '/storage/' . $path;
        }

        auth()->user()->update($data);
        $this->success(__('Account settings updated!'));
    }

    public function updateSecurity(): void
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

    public function getLanguagesProperty()
    {
        return [
            ['id' => 'en', 'name' => 'English'],
            ['id' => 'fr', 'name' => 'Français'],
            ['id' => 'de', 'name' => 'Deutsch'],
        ];
    }

    public function getTimezonesProperty()
    {
        return [
            ['id' => 'UTC', 'name' => 'UTC'],
            ['id' => 'Europe/Paris', 'name' => 'Paris (GMT+1)'],
            ['id' => 'Europe/Berlin', 'name' => 'Berlin (GMT+1)'],
            ['id' => 'America/New_York', 'name' => 'New York (GMT-5)'],
            ['id' => 'Asia/Tokyo', 'name' => 'Tokyo (GMT+9)'],
        ];
    }

    public function render()
    {
        return $this->view([
            'languages' => $this->languages,
            'timezones' => $this->timezones,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-4xl px-3 mx-auto md:px-4">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold md:text-3xl">⚙️ {{ __('Settings') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ __('Manage your account settings') }}</p>
        </div>

        {{-- Tabs --}}
        <div class="mb-6 tabs tabs-boxed">
            <a href="{{ route('settings.account') }}" wire:navigate class="tab {{ $activeTab === 'account' ? 'tab-active' : '' }}">👤 {{ __('Account') }}</a>
            <a href="{{ route('settings.security') }}" wire:navigate class="tab {{ $activeTab === 'security' ? 'tab-active' : '' }}">🔒 {{ __('Security') }}</a>
        </div>

        {{-- Tab: Account --}}
        @if($activeTab === 'account')
            <x-card class="shadow-sm">
                <x-form wire:submit="updateAccount" no-separator>
                    <div class="flex flex-col items-center gap-4 mb-4 sm:flex-row">
                        <div class="avatar">
                            <div class="flex items-center justify-center w-24 h-24 overflow-hidden rounded-full bg-primary/20">
                                @if(auth()->user()->avatar)
                                    <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" class="object-cover w-full h-full">
                                @else
                                    <span class="text-4xl font-bold text-primary">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex-1">
                            <x-file wire:model="avatar" label="{{ __('Profile picture') }}" accept="image/jpeg,image/png" hint="{{ __('Max 1MB') }}" />
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input wire:model="name" label="{{ __('Full name') }}" required />
                        <x-input wire:model="email" label="{{ __('Email') }}" type="email" required />
                        <x-input wire:model="phone" label="{{ __('Phone') }}" />
                        <x-select wire:model="language" label="{{ __('Language') }}" :options="$languages" option-value="id" option-label="name" />
                        <x-select wire:model="timezone" label="{{ __('Timezone') }}" :options="$timezones" option-value="id" option-label="name" class="md:col-span-2" />
                    </div>
                    <x-textarea wire:model="bio" label="{{ __('Bio') }}" rows="3" placeholder="{{ __('Tell something about yourself...') }}" />
                    <x-slot:actions>
                        <div class="flex justify-end">
                            <x-button label="{{ __('Save changes') }}" class="btn-primary" type="submit" spinner="updateAccount" />
                        </div>
                    </x-slot:actions>
                </x-form>
            </x-card>
        @endif

        {{-- Tab: Security --}}
        @if($activeTab === 'security')
            <x-card class="shadow-sm">
                <x-form wire:submit="updateSecurity" no-separator>
                    <div class="space-y-4">
                        <p class="text-sm text-base-content/70">{{ __('Change your password') }}</p>
                        <x-password wire:model="current_password" label="{{ __('Current password') }}" required />
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-password wire:model="new_password" label="{{ __('New password') }}" required hint="{{ __('Minimum 8 characters') }}" />
                            <x-password wire:model="new_password_confirmation" label="{{ __('Confirm new password') }}" required />
                        </div>
                    </div>
                    <x-slot:actions>
                        <div class="flex justify-end">
                            <x-button label="{{ __('Update password') }}" class="btn-primary" type="submit" spinner="updateSecurity" />
                        </div>
                    </x-slot:actions>
                </x-form>
            </x-card>
        @endif
    </div>
</div>
