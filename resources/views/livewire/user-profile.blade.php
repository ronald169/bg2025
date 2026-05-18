<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Hash;
use Mary\Traits\Toast;

new
#[Title('Mein Profil - AllemandExpress')]
#[Layout('components.layouts.app')]
class extends Component {
    use WithFileUploads, Toast;

    public $user;

    // Profile Information
    public $name = '';
    public $email = '';
    public $phone = '';
    public $bio = '';
    public $german_level = '';
    public $learning_goal = '';
    public $date_of_birth = '';
    public $study_reminders = false;
    public $motivation = '';

    // Password
    public $current_password = '';
    public $new_password = '';
    public $new_password_confirmation = '';

    // Avatar
    public $avatar;
    public $avatar_preview;

    // Options
    public $germanLevels = [
        ['id' => 'A1', 'name' => 'A1 - Débutant'],
        ['id' => 'A2', 'name' => 'A2 - Élémentaire'],
        ['id' => 'B1', 'name' => 'B1 - Intermédiaire'],
        ['id' => 'B2', 'name' => 'B2 - Avancé'],
        ['id' => 'C1', 'name' => 'C1 - Expérimenté'],
        ['id' => 'C2', 'name' => 'C2 - Maîtrise'],
    ];

    public $learningGoals = [
        ['id' => 'certification', 'name' => '🎓 Certification Goethe/ÖSD/TELC'],
        ['id' => 'conversation', 'name' => '💬 Conversation quotidienne'],
        ['id' => 'travel', 'name' => '✈️ Voyage en Allemagne'],
        ['id' => 'business', 'name' => '💼 Allemand professionnel'],
    ];

    public function mount(): void
    {
        $this->user = auth()->user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone ?? '';
        $this->bio = $this->user->bio ?? '';
        $this->german_level = $this->user->german_level ?? 'A1';
        $this->learning_goal = $this->user->learning_goal ?? 'certification';
        $this->date_of_birth = $this->user->date_of_birth?->format('Y-m-d');
        $this->study_reminders = $this->user->study_reminders ?? false;
        $this->motivation = $this->user->motivation ?? '';
    }

    public function updatedAvatar(): void
    {
        $this->validate([
            'avatar' => 'image|max:2048', // 2MB max
        ]);

        $this->avatar_preview = $this->avatar->temporaryUrl();
    }

    public function updateProfile(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
            'german_level' => 'nullable|string|in:A1,A2,B1,B2,C1,C2',
            'learning_goal' => 'nullable|string|in:certification,conversation,travel,business',
            'date_of_birth' => 'nullable|date|before:today',
            'study_reminders' => 'boolean',
            'motivation' => 'nullable|string|max:1000',
        ]);

        $updateData = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'german_level' => $this->german_level,
            'learning_goal' => $this->learning_goal,
            'date_of_birth' => $this->date_of_birth,
            'study_reminders' => $this->study_reminders,
            'motivation' => $this->motivation,
        ];

        // Gérer l'upload de l'avatar
        if ($this->avatar) {
            $path = $this->avatar->store('avatars', 'public');
            $updateData['profile_photo_path'] = $path;
        }

        $this->user->update($updateData);

        $this->success('Profil erfolgreich aktualisiert! 🎉');

        // Rafraîchir la page pour mettre à jour l'avatar dans le header
        $this->dispatch('profile-updated');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
            'new_password_confirmation' => ['required'],
        ]);

        $this->user->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->success('Passwort erfolgreich aktualisiert! 🔒');
    }

    public function getInitials(): string
    {
        $words = explode(' ', $this->user->name);
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        return substr($initials, 0, 2);
    }

    public function getMemberSince(): string
    {
        return $this->user->created_at->translatedFormat('F Y');
    }
}
?>

<div class="py-4 md:py-8">
    <div class="max-w-5xl px-3 mx-auto md:px-4">

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-900 md:text-2xl">👤 {{ __('Mein Profil') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('Verwalte deine persönlichen Informationen') }}</p>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <!-- Main Content (2/3) -->
            <div class="space-y-6 md:col-span-2">
                <!-- Personal Information -->
                <x-card class="border-0 shadow-sm">
                    <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                        <x-icon name="o-user-circle" class="w-5 h-5 text-[#FF6B35]" />
                        <h2 class="font-semibold text-gray-900">{{ __('Persönliche Informationen') }}</h2>
                    </div>

                    <form wire:submit="updateProfile" class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-input
                                wire:model="name"
                                label="{{ __('Vollständiger Name') }} *"
                                placeholder="{{ __('Dein Name') }}"
                                icon="o-user"
                                required />

                            <x-input
                                wire:model="email"
                                type="email"
                                label="{{ __('E-Mail-Adresse') }} *"
                                placeholder="{{ __('deine@email.com') }}"
                                icon="o-envelope"
                                required />
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <x-input
                                wire:model="phone"
                                label="{{ __('Telefonnummer') }}"
                                placeholder="{{ __('+49 123 456789') }}"
                                icon="o-phone" />

                            <x-input
                                wire:model="date_of_birth"
                                type="date"
                                label="{{ __('Geburtsdatum') }}"
                                icon="o-calendar" />
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <x-select
                                wire:model="german_level"
                                label="{{ __('Deutschniveau') }}"
                                :options="$germanLevels"
                                icon="o-chart-bar" />

                            <x-select
                                wire:model="learning_goal"
                                label="{{ __('Lernziel') }}"
                                :options="$learningGoals"
                                icon="custom.target" />
                        </div>

                        <x-textarea
                            wire:model="bio"
                            label="{{ __('Über mich') }}"
                            placeholder="{{ __('Erzähle etwas über dich...') }}"
                            rows="3"
                            icon="o-document-text" />

                        <x-textarea
                            wire:model="motivation"
                            label="{{ __('Motivation') }}"
                            placeholder="{{ __('Was motiviert dich, Deutsch zu lernen?') }}"
                            rows="2"
                            icon="o-sparkles" />

                        <x-toggle
                            wire:model="study_reminders"
                            label="{{ __('Lern-Erinnerungen aktivieren') }}"
                            hint="{{ __('Erhalte tägliche Erinnerungen zum Lernen') }}" />

                        <div class="pt-2">
                            <x-button type="submit" class="btn-primary" spinner="updateProfile">
                                <x-icon name="o-check" class="w-4 h-4 mr-1" />
                                {{ __('Änderungen speichern') }}
                            </x-button>
                        </div>
                    </form>
                </x-card>

                <!-- Change Password -->
                <x-card class="border-0 shadow-sm">
                    <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                        <x-icon name="o-lock-closed" class="w-5 h-5 text-[#FF6B35]" />
                        <h2 class="font-semibold text-gray-900">{{ __('Passwort ändern') }}</h2>
                    </div>

                    <form wire:submit="updatePassword" class="space-y-4">
                        <x-input
                            wire:model="current_password"
                            type="password"
                            label="{{ __('Aktuelles Passwort') }} *"
                            placeholder="{{ __('••••••••') }}"
                            icon="o-key"
                            required />

                        <div class="grid gap-4 md:grid-cols-2">
                            <x-input
                                wire:model="new_password"
                                type="password"
                                label="{{ __('Neues Passwort') }} *"
                                placeholder="{{ __('••••••••') }}"
                                icon="o-lock-closed"
                                required />

                            <x-input
                                wire:model="new_password_confirmation"
                                type="password"
                                label="{{ __('Neues Passwort bestätigen') }} *"
                                placeholder="{{ __('••••••••') }}"
                                icon="o-lock-closed"
                                required />
                        </div>

                        <div class="pt-2">
                            <x-button type="submit" class="btn-primary" spinner="updatePassword">
                                <x-icon name="o-key" class="w-4 h-4 mr-1" />
                                {{ __('Passwort aktualisieren') }}
                            </x-button>
                        </div>
                    </form>
                </x-card>
            </div>

            <!-- Sidebar (1/3) -->
            <div class="space-y-6">
                <!-- Profile Photo -->
                <x-card class="border-0 shadow-sm">
                    <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                        <x-icon name="o-camera" class="w-5 h-5 text-[#FF6B35]" />
                        <h2 class="font-semibold text-gray-900">{{ __('Profilbild') }}</h2>
                    </div>

                    <div class="text-center">
                        <div class="relative w-32 h-32 mx-auto mb-4">
                            @if($avatar_preview)
                                <img src="{{ $avatar_preview }}" class="object-cover w-full h-full rounded-full ring-4 ring-[#FF6B35]/20">
                            @elseif($user->profile_photo_path)
                                <img src="{{ Storage::url($user->profile_photo_path) }}" class="object-cover w-full h-full rounded-full ring-4 ring-[#FF6B35]/20">
                            @else
                                <div class="flex items-center justify-center w-full h-full rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] ring-4 ring-[#FF6B35]/20">
                                    <span class="text-4xl font-bold text-white">{{ $this->getInitials() }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <input type="file" wire:model="avatar" accept="image/*" class="hidden" id="avatar-upload">
                            <label for="avatar-upload" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-[#FF6B35] border border-[#FF6B35] rounded-lg cursor-pointer hover:bg-orange-50 transition">
                                <x-icon name="o-camera" class="w-4 h-4" />
                                {{ __('Bild hochladen') }}
                            </label>
                        </div>

                        @error('avatar')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <p class="text-xs text-gray-400">
                            {{ __('Empfohlen: Quadratisches Bild, max. 2MB') }}
                        </p>
                    </div>
                </x-card>

                <!-- Stats Card -->
                <x-card class="border-0 shadow-sm">
                    <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                        <x-icon name="o-chart-bar" class="w-5 h-5 text-[#FF6B35]" />
                        <h2 class="font-semibold text-gray-900">{{ __('Statistiken') }}</h2>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ __('Mitglied seit') }}</span>
                            <span class="font-medium text-gray-900">{{ $this->getMemberSince() }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ __('Kurse') }}</span>
                            <span class="font-medium text-gray-900">{{ $user->coursesEnrolled()->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ __('Abgeschlossene Lektionen') }}</span>
                            <span class="font-medium text-gray-900">{{ \App\Models\Progress::where('user_id', $user->id)->where('is_completed', true)->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ __('Quiz-Versuche') }}</span>
                            <span class="font-medium text-gray-900">{{ \App\Models\QuizAttempt::where('user_id', $user->id)->count() }}</span>
                        </div>
                    </div>
                </x-card>

                <!-- Account Actions -->
                <x-card class="border-0 border-l-4 shadow-sm border-l-red-500">
                    <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                        <x-icon name="o-exclamation-triangle" class="w-5 h-5 text-red-500" />
                        <h2 class="font-semibold text-gray-900">{{ __('Konto') }}</h2>
                    </div>

                    <div class="space-y-3">
                        <button type="button"
                                wire:click="$dispatch('logout')"
                                class="flex items-center justify-center w-full gap-2 px-4 py-2 text-red-600 transition border border-red-300 rounded-lg hover:bg-red-50">
                            <x-icon name="o-arrow-right-on-rectangle" class="w-4 h-4" />
                            {{ __('Abmelden') }}
                        </button>
                    </div>
                </x-card>
            </div>
        </div>

        <!-- Note MVP -->
        <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">MVP Version</p>
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('Prochaines fonctionnalités : préférences de notification, thème sombre, et export des données.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
