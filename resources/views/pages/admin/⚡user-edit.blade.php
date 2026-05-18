<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Mary\Traits\Toast;

new
#[Title('Edit User - Admin')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public User $user;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = '';
    public string $status = '';
    public ?string $phone = '';
    public ?string $bio = '';

    public function mount(User $user)
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->status = $user->status ?? 'active';
        $this->phone = $user->phone;
        $this->bio = $user->bio;
    }

    public function update()
    {
        $this->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $this->user->id,
            'password' => 'nullable|min:8|confirmed',
            'role'     => 'required|in:student,teacher,admin',
            'status'   => 'required|in:active,inactive',
            'phone'    => 'nullable|string|max:20',
            'bio'      => 'nullable|string|max:500',
        ]);

        $data = [
            'name'   => $this->name,
            'email'  => $this->email,
            'role'   => $this->role,
            'status' => $this->status,
            'phone'  => $this->phone,
            'bio'    => $this->bio,
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $this->user->update($data);

        $this->success(__('User updated successfully!'));
        $this->redirectRoute('admin.users');
    }

    public function render()
    {
        return $this->view();
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-3xl px-3 mx-auto md:px-4">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold md:text-3xl">✏️ {{ __('Edit User') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ __('Modify user information') }}</p>
        </div>

        <x-card class="shadow-sm">
            <x-form wire:submit="update" no-separator>

                {{-- Name --}}
                <x-input
                    label="{{ __('Full Name') }}"
                    wire:model="name"
                    icon="o-user"
                    placeholder="Max Mustermann"
                    required />

                {{-- Email --}}
                <x-input
                    label="{{ __('Email') }}"
                    type="email"
                    wire:model="email"
                    icon="o-envelope"
                    placeholder="max@example.com"
                    required />

                {{-- Password & confirmation (optional) --}}
                <div class="grid gap-4 md:grid-cols-2">
                    <x-password
                        label="{{ __('New Password') }}"
                        wire:model="password"
                        placeholder="••••••••"
                        hint="{{ __('Leave blank to keep current password') }}" />
                    <x-password
                        label="{{ __('Confirm New Password') }}"
                        wire:model="password_confirmation"
                        placeholder="••••••••" />
                </div>

                {{-- Role & Status --}}
                <div class="grid gap-4 md:grid-cols-2">
                    <x-select
                        label="{{ __('Role') }}"
                        wire:model="role"
                        :options="[
                            ['id' => 'student', 'name' => __('Student')],
                            ['id' => 'teacher', 'name' => __('Teacher')],
                            ['id' => 'admin', 'name' => __('Admin')],
                        ]"
                        option-value="id"
                        option-label="name"
                        required />
                    <x-select
                        label="{{ __('Status') }}"
                        wire:model="status"
                        :options="[
                            ['id' => 'active', 'name' => __('Active')],
                            ['id' => 'inactive', 'name' => __('Inactive')],
                        ]"
                        option-value="id"
                        option-label="name"
                        required />
                </div>

                {{-- Phone (optional) --}}
                <x-input
                    label="{{ __('Phone') }}"
                    wire:model="phone"
                    icon="o-phone"
                    placeholder="+49 123 456789" />

                {{-- Bio (optional) --}}
                <x-textarea
                    label="{{ __('Bio') }}"
                    wire:model="bio"
                    rows="3"
                    placeholder="{{ __('Short description about the user...') }}" />

                {{-- Actions --}}
                <x-slot:actions>
                    <x-button label="{{ __('Cancel') }}" link="{{ route('admin.users') }}" class="btn-ghost" />
                    <x-button label="{{ __('Update User') }}" class="btn-primary" type="submit" spinner="update" />
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>
