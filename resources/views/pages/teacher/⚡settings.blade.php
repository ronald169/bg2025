<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Mary\Traits\Toast;

new
#[Title('Settings - Teacher')]
#[Layout('layouts.app')]
class extends Component {
    use WithFileUploads, Toast;

    public User $user;
    public string $activeTab = 'profile';

    // Profile
    public string $name = '';
    public string $email = '';
    public ?string $phone = '';
    public ?string $bio = '';
    public $photo = null;
    public ?string $german_level = '';

    // Notifications
    public bool $notify_email = true;
    public bool $notify_announcements = true;
    public bool $notify_messages = true;
    public bool $notify_reminders = true;

    // Security
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $this->user = auth()->user();
        $this->loadProfile();
        $this->loadNotificationPreferences();
    }

    public function loadProfile(): void
    {
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone;
        $this->bio = $this->user->bio;
        $this->german_level = $this->user->german_level ?? 'B1';
    }

    public function loadNotificationPreferences(): void
    {
        $prefs = $this->user->notification_preferences ?? [];
        $this->notify_email = $prefs['email'] ?? true;
        $this->notify_announcements = $prefs['announcements'] ?? true;
        $this->notify_messages = $prefs['messages'] ?? true;
        $this->notify_reminders = $prefs['reminders'] ?? true;
    }

    public function updateProfile(): void
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
    }

    public function updateNotifications(): void
    {
        $this->user->update([
            'notification_preferences' => [
                'email'        => $this->notify_email,
                'announcements' => $this->notify_announcements,
                'messages'     => $this->notify_messages,
                'reminders'    => $this->notify_reminders,
            ],
        ]);
        $this->success(__('Notification preferences saved!'));
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($this->current_password, $this->user->password)) {
            $this->addError('current_password', __('Current password is incorrect.'));
            return;
        }

        $this->user->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->success(__('Password updated successfully!'));
    }

    public function getGermanLevelsProperty()
    {
        return [
            ['id' => 'A1', 'name' => 'A1 - Beginner'],
            ['id' => 'A2', 'name' => 'A2 - Elementary'],
            ['id' => 'B1', 'name' => 'B1 - Intermediate'],
            ['id' => 'B2', 'name' => 'B2 - Upper Intermediate'],
            ['id' => 'C1', 'name' => 'C1 - Advanced'],
            ['id' => 'C2', 'name' => 'C2 - Mastery'],
        ];
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
    <div class="max-w-4xl px-3 mx-auto md:px-4">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold md:text-3xl">⚙️ {{ __('Settings') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ __('Manage your account settings and preferences') }}</p>
        </div>

        {{-- Tabs --}}
        <div class="mb-6 tabs tabs-boxed">
            <a class="tab {{ $activeTab === 'profile' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'profile')">👤 {{ __('Profile') }}</a>
            <a class="tab {{ $activeTab === 'notifications' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'notifications')">🔔 {{ __('Notifications') }}</a>
            <a class="tab {{ $activeTab === 'security' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'security')">🔒 {{ __('Security') }}</a>
        </div>

        {{-- Tab: Profile --}}
        @if($activeTab === 'profile')
            <x-card class="shadow-sm">
                <x-form wire:submit="updateProfile" no-separator>
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
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input wire:model="name" label="{{ __('Full name') }} *" required />
                        <x-input wire:model="email" label="{{ __('Email') }} *" type="email" required />
                        <x-input wire:model="phone" label="{{ __('Phone') }}" />
                        <x-select wire:model="german_level" label="{{ __('German level') }}" :options="$germanLevels" option-value="id" option-label="name" />
                    </div>
                    <x-textarea wire:model="bio" label="{{ __('Bio') }}" rows="3" placeholder="{{ __('Tell something about yourself...') }}" />
                    <x-slot:actions>
                        <div class="flex justify-end">
                            <x-button label="{{ __('Save changes') }}" class="btn-primary" type="submit" spinner="updateProfile" />
                        </div>
                    </x-slot:actions>
                </x-form>
            </x-card>
        @endif

        {{-- Tab: Notifications --}}
        @if($activeTab === 'notifications')
            <x-card class="shadow-sm">
                <x-form wire:submit="updateNotifications" no-separator>
                    <div class="space-y-4">
                        <p class="text-sm text-base-content/70">{{ __('Choose how you want to be notified') }}</p>
                        <x-toggle wire:model="notify_email" label="{{ __('Email notifications') }}" hint="{{ __('Receive important updates by email') }}" />
                        <x-toggle wire:model="notify_announcements" label="{{ __('Course announcements') }}" hint="{{ __('Get notified about new announcements in your courses') }}" />
                        <x-toggle wire:model="notify_messages" label="{{ __('Messages') }}" hint="{{ __('Receive notifications when students message you') }}" />
                        <x-toggle wire:model="notify_reminders" label="{{ __('Reminders') }}" hint="{{ __('Get reminders about upcoming lessons and deadlines') }}" />
                    </div>
                    <x-slot:actions>
                        <div class="flex justify-end">
                            <x-button label="{{ __('Save preferences') }}" class="btn-primary" type="submit" spinner="updateNotifications" />
                        </div>
                    </x-slot:actions>
                </x-form>
            </x-card>
        @endif

        {{-- Tab: Security --}}
        @if($activeTab === 'security')
            <x-card class="shadow-sm">
                <x-form wire:submit="updatePassword" no-separator>
                    <div class="space-y-4">
                        <p class="text-sm text-base-content/70">{{ __('Change your password') }}</p>
                        <x-password wire:model="current_password" label="{{ __('Current password') }} *" required />
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-password wire:model="new_password" label="{{ __('New password') }} *" required hint="{{ __('Minimum 8 characters') }}" />
                            <x-password wire:model="new_password_confirmation" label="{{ __('Confirm new password') }} *" required />
                        </div>
                    </div>
                    <x-slot:actions>
                        <div class="flex justify-end">
                            <x-button label="{{ __('Update password') }}" class="btn-primary" type="submit" spinner="updatePassword" />
                        </div>
                    </x-slot:actions>
                </x-form>
            </x-card>
        @endif
    </div>
</div>
