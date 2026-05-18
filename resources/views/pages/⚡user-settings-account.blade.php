<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new
#[Title('Account Settings')]
#[Layout('layouts.app')]
class extends Component {
    use WithFileUploads, Toast;

    public string $name = '';
    public string $email = '';
    public ?string $phone = '';
    public ?string $bio = '';
    public $avatar = null;
    public string $language = 'en';
    public string $timezone = 'UTC';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->bio = $user->bio;
        $this->language = $user->language ?? 'en';
        $this->timezone = $user->timezone ?? 'UTC';
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

    public function update(): void
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

        {{-- Navigation --}}
        <div class="mb-5">
            <a href="{{ route('settings') }}" wire:navigate class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to settings') }}
            </a>
        </div>

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold md:text-3xl">👤 {{ __('Account Settings') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ __('Manage your personal information') }}</p>
        </div>

        <x-card class="shadow-sm">
            <x-form wire:submit="update" no-separator>
                {{-- Avatar --}}
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

                {{-- Form fields --}}
                <div class="grid gap-4 md:grid-cols-2">
                    <x-input wire:model="name" label="{{ __('Full name') }} *" required />
                    <x-input wire:model="email" label="{{ __('Email') }} *" type="email" required />
                    <x-input wire:model="phone" label="{{ __('Phone') }}" />
                    <x-select wire:model="language" label="{{ __('Language') }}" :options="$languages" option-value="id" option-label="name" />
                    <x-select wire:model="timezone" label="{{ __('Timezone') }}" :options="$timezones" option-value="id" option-label="name" class="md:col-span-2" />
                </div>
                <x-textarea wire:model="bio" label="{{ __('Bio') }}" rows="3" placeholder="{{ __('Tell something about yourself...') }}" />

                {{-- Actions --}}
                <x-slot:actions>
                    <div class="flex justify-end">
                        <x-button label="{{ __('Save changes') }}" class="btn-primary" type="submit" spinner="update" />
                    </div>
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>
