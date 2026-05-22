<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\User;
use Mary\Traits\Toast;

new
#[Title('Edit Profile')]
#[Layout('layouts.app')]
class extends Component {
    use WithFileUploads, Toast;

    public User $user;

    // Form fields
    public string $name = '';
    public string $email = '';
    public ?string $phone = '';
    public ?string $bio = '';
    public $photo = null;
    public ?string $german_level = '';

    public function mount(): void
    {
        $this->user = auth()->user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone;
        $this->bio = $this->user->bio;
        $this->german_level = $this->user->german_level ?? 'A1';
    }

    public function getGermanLevelsProperty()
    {
        return [
            ['id' => 'A1', 'name' => __('A1 - Beginner')],
            ['id' => 'A2', 'name' => __('A2 - Elementary')],
            ['id' => 'B1', 'name' => __('B1 - Intermediate')],
            ['id' => 'B2', 'name' => __('B2 - Upper Intermediate')],
            ['id' => 'C1', 'name' => __('C1 - Advanced')],
            ['id' => 'C2', 'name' => __('C2 - Mastery')],
        ];
    }

    public function update(): void
    {
        $this->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->user->id,
            'phone' => 'nullable|string|max:20',
            'bio'   => 'nullable|string|max:500',
            'german_level' => 'nullable|string|in:A1,A2,B1,B2,C1,C2',
            'photo' => 'nullable|image|max:1024',
        ]);

        $data = [
            'name'  => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'bio'   => $this->bio,
            'german_level' => $this->german_level,
        ];

        if ($this->photo) {
            $path = $this->photo->store('avatars', 'public');
            $data['avatar'] = '/storage/' . $path;
        }

        $this->user->update($data);
        $this->success(__('Profile updated successfully!'));
        $this->redirectRoute('profile');
    }

    public function render()
    {
        return $this->view([
            'germanLevels' => $this->germanLevels,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-3xl px-3 mx-auto md:px-4">

        {{-- Navigation --}}
        <div class="mb-5">
            <a href="{{ route('profile') }}" wire:navigate class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to profile') }}
            </a>
        </div>

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold md:text-3xl">✏️ {{ __('Edit Profile') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ __('Update your personal information') }}</p>
        </div>

        <x-card class="shadow-sm">
            <x-form wire:submit="update" no-separator>
                {{-- Avatar --}}
                <div class="flex flex-col items-center gap-4 mb-4 sm:flex-row">
                    <div class="avatar">
                        <div class="flex items-center justify-center w-24 h-24 overflow-hidden rounded-full bg-primary/20">
                            @if($user->avatar)
                                <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="object-cover w-full h-full">
                            @else
                                <span class="text-4xl font-bold text-primary">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex-1">
                        <x-file wire:model="photo" label="{{ __('Profile picture') }}" accept="image/jpeg,image/png" hint="{{ __('Max 1MB') }}" />
                    </div>
                </div>

                {{-- Form fields --}}
                <div class="grid gap-4 md:grid-cols-2">
                    <x-input wire:model="name" label="{{ __('Full name') }}" required />
                    <x-input wire:model="email" label="{{ __('Email') }}" type="email" required />
                    <x-input wire:model="phone" label="{{ __('Phone') }}" />
                    <x-select wire:model="german_level" label="{!! __('German level') !!}" :options="$germanLevels" option-value="id" option-label="name" />
                </div>
                <x-textarea wire:model="bio" label="{{ __('Bio') }}" rows="3" placeholder="{{ __('Tell something about yourself...') }}" />

                <x-slot:actions>
                    <div class="flex justify-end gap-3">
                        <x-button label="{{ __('Cancel') }}" link="{{ route('profile') }}" class="btn-ghost" />
                        <x-button label="{{ __('Save changes') }}" class="btn-primary" type="submit" spinner="update" />
                    </div>
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>
