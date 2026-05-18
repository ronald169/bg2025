<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Rule;
use Mary\Traits\Toast;

new
#[Title('Benutzer bearbeiten - Admin')]
#[Layout('components.layouts.dashboard-admin')]
class extends Component {
    use Toast;

    public User $user;

    #[Rule('required|string|max:255')]
    public string $name = '';

    public string $email = '';

    #[Rule('required|in:student,teacher,admin')]
    public string $role = '';

    #[Rule('required|in:active,inactive,pending')]
    public string $status = '';

    #[Rule('nullable|string|max:20')]
    public string $phone = '';

    #[Rule('nullable|string|max:500')]
    public string $bio = '';

    #[Rule('nullable|string|in:college,lycee,terminale')]
    public string $level = '';

    // Champs spécifiques pour les étudiants
    #[Rule('nullable|string|in:A1,A2,B1,B2,C1,C2')]
    public string $german_level = '';

    #[Rule('nullable|string|in:certification,conversation,travel,business')]
    public string $learning_goal = '';

    #[Rule('nullable|boolean')]
    public bool $study_reminders = false;

    #[Rule('nullable|string|max:1000')]
    public string $motivation = '';

    // Options
    public array $roles = [
        ['id' => 'student', 'name' => 'Student'],
        ['id' => 'teacher', 'name' => 'Lehrer'],
        ['id' => 'admin', 'name' => 'Administrator'],
    ];

    public array $statuses = [
        ['id' => 'active', 'name' => 'Aktiv'],
        ['id' => 'inactive', 'name' => 'Inaktiv'],
        ['id' => 'pending', 'name' => 'Ausstehend'],
    ];

    public array $levels = [
        ['id' => 'college', 'name' => 'College (A1-A2)'],
        ['id' => 'lycee', 'name' => 'Lycée (B1-B2)'],
        ['id' => 'terminale', 'name' => 'Terminale (C1-C2)'],
    ];

    public array $germanLevels = [
        ['id' => 'A1', 'name' => 'A1 - Débutant'],
        ['id' => 'A2', 'name' => 'A2 - Élémentaire'],
        ['id' => 'B1', 'name' => 'B1 - Intermédiaire'],
        ['id' => 'B2', 'name' => 'B2 - Avancé'],
        ['id' => 'C1', 'name' => 'C1 - Expérimenté'],
        ['id' => 'C2', 'name' => 'C2 - Maîtrise'],
    ];

    public array $learningGoals = [
        ['id' => 'certification', 'name' => '🎓 Certification Goethe/ÖSD/TELC'],
        ['id' => 'conversation', 'name' => '💬 Conversation quotidienne'],
        ['id' => 'travel', 'name' => '✈️ Voyage en Allemagne'],
        ['id' => 'business', 'name' => '💼 Allemand professionnel'],
    ];

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->status = $user->status;
        $this->phone = $user->phone ?? '';
        $this->bio = $user->bio ?? '';
        $this->level = $user->level ?? '';
        $this->german_level = $user->german_level ?? 'A1';
        $this->learning_goal = $user->learning_goal ?? 'certification';
        $this->study_reminders = $user->study_reminders ?? false;
        $this->motivation = $user->motivation ?? '';
    }

    public function updatedEmail()
    {
        $this->validateOnly('email');
    }

    public function save(): void
    {
        $this->validate([
            'email' => 'required|email|unique:users,email,' . $this->user->id,
        ]);

        // Vérifier l'unicité de l'email
        $existingUser = User::where('email', $this->email)
            ->where('id', '!=', $this->user->id)
            ->first();

        if ($existingUser) {
            $this->addError('email', 'Diese E-Mail-Adresse wird bereits verwendet.');
            return;
        }

        $this->user->update([
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'level' => $this->level,
            'german_level' => $this->role === 'student' ? $this->german_level : null,
            'learning_goal' => $this->role === 'student' ? $this->learning_goal : null,
            'study_reminders' => $this->role === 'student' ? $this->study_reminders : false,
            'motivation' => $this->role === 'student' ? $this->motivation : null,
        ]);

        $this->success('Benutzer erfolgreich aktualisiert! 🎉');
        $this->redirectRoute('admin.users.show', $this->user, navigate: true);
    }

    public function getRoleBadgeClass($role): string
    {
        return match($role) {
            'admin' => 'bg-red-100 text-red-700',
            'teacher' => 'bg-blue-100 text-blue-700',
            'student' => 'bg-green-100 text-green-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    // Ajoutez ces méthodes au composant

    public function sendPasswordReset(): void
    {
        // Vérifier que l'utilisateur a une adresse email valide
        if (!$this->email) {
            $this->error('Keine gültige E-Mail-Adresse vorhanden.');
            return;
        }

        // Envoyer le lien de réinitialisation
        \Illuminate\Support\Facades\Password::sendResetLink(['email' => $this->email]);

        $this->success('Eine Passwort-Reset-E-Mail wurde an ' . $this->email . ' gesendet.');
    }

    public function deleteUser(): void
    {
        $userName = $this->user->name;
        $this->user->delete();
        $this->success("Benutzer '{$userName}' wurde gelöscht.");
        $this->redirectRoute('admin.users', navigate: true);
    }
}
?>

<div class="py-4 md:py-6">
    <div class="max-w-4xl px-3 mx-auto md:px-4">

        <!-- Navigation -->
        <div class="mb-5">
            <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center gap-1 text-sm text-[#FF6B35] hover:text-[#E55A2A] transition">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Zurück zum Benutzer') }}
            </a>
        </div>

        <!-- Header -->
        <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">✏️ {{ __('Benutzer bearbeiten') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $user->name }}</p>
            </div>
            <div class="flex gap-2">
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full {{ $this->getRoleBadgeClass($user->role) }}">
                    <x-icon name="o-user" class="w-3 h-3" />
                    {{ ucfirst($user->role) }}
                </span>
                <span class="px-2 py-1 text-xs rounded-full {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : ($user->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700') }}">
                    {{ $user->status === 'active' ? 'Aktiv' : ($user->status === 'pending' ? 'Ausstehend' : 'Inaktiv') }}
                </span>
            </div>
        </div>

        <form wire:submit="save" class="space-y-5">
            <!-- Basic Information -->
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-user-circle" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Allgemeine Informationen') }}</h2>
                </div>

                <div class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input
                            wire:model="name"
                            label="{{ __('Vollständiger Name') }} *"
                            placeholder="{{ __('z.B. Max Mustermann') }}"
                            icon="o-user"
                            required />

                        <x-input
                            wire:model="email"
                            type="email"
                            label="{{ __('E-Mail-Adresse') }} *"
                            placeholder="{{ __('max@example.com') }}"
                            icon="o-envelope"
                            required />
                        @error('email')
                            <p class="-mt-3 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-select
                            wire:model="role"
                            label="{{ __('Rolle') }} *"
                            :options="$roles"
                            icon="o-user-group"
                            required />

                        <x-select
                            wire:model="status"
                            label="{{ __('Status') }} *"
                            :options="$statuses"
                            icon="o-circle-stack"
                            required />
                    </div>

                    <x-input
                        wire:model="phone"
                        label="{{ __('Telefonnummer') }}"
                        placeholder="{{ __('+49 123 456789') }}"
                        icon="o-phone" />

                    <x-select
                        wire:model="level"
                        label="{{ __('Schulniveau') }}"
                        :options="$levels"
                        icon="o-academic-cap"
                        placeholder="{{ __('Nicht angegeben') }}" />

                    <x-textarea
                        wire:model="bio"
                        label="{{ __('Biografie') }}"
                        placeholder="{{ __('Kurze Beschreibung des Benutzers') }}"
                        rows="3"
                        icon="o-document-text" />
                </div>
            </x-card>

            <!-- Student Specific Information (visible seulement pour le rôle étudiant) -->
            @if($role === 'student')
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-academic-cap" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Deutsch Lerninformationen') }}</h2>
                    <span class="text-xs text-gray-400">({{ __('Nur für Studenten') }})</span>
                </div>

                <div class="space-y-4">
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

                    <x-toggle
                        wire:model="study_reminders"
                        label="{{ __('Lern-Erinnerungen aktivieren') }}"
                        hint="{{ __('Der Benutzer erhält tägliche Lern-Erinnerungen') }}" />

                    <x-textarea
                        wire:model="motivation"
                        label="{{ __('Motivation') }}"
                        placeholder="{{ __('Warum möchte dieser Benutzer Deutsch lernen?') }}"
                        rows="2"
                        icon="o-sparkles" />
                </div>
            </x-card>
            @endif

            <!-- Password Section -->
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-lock-closed" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Passwort zurücksetzen') }}</h2>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                        <div>
                            <p class="font-medium text-gray-900">{{ __('Passwort ändern') }}</p>
                            <p class="text-xs text-gray-500">{{ __('Sende eine Passwort-Reset-E-Mail an den Benutzer') }}</p>
                        </div>
                        <button type="button"
                                wire:click="sendPasswordReset"
                                class="px-3 py-1.5 text-sm text-white bg-[#FF6B35] rounded-lg hover:bg-[#E55A2A] transition">
                            {{ __('Reset-E-Mail senden') }}
                        </button>
                    </div>
                </div>
            </x-card>

            <!-- Danger Zone -->
            <x-card class="border-0 border-l-4 shadow-sm border-l-red-500">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-exclamation-triangle" class="w-5 h-5 text-red-500" />
                    <h2 class="font-semibold text-gray-900">{{ __('Gefahrenzone') }}</h2>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-medium text-gray-900">{{ __('Benutzer löschen') }}</p>
                        <p class="text-sm text-gray-500">{{ __('Diese Aktion kann nicht rückgängig gemacht werden') }}</p>
                    </div>
                    <button type="button"
                            wire:click="deleteUser"
                            wire:confirm="{{ __('Bist du sicher, dass du diesen Benutzer löschen möchtest?') }}"
                            class="px-4 py-2 text-sm text-white transition bg-red-600 rounded-lg hover:bg-red-700">
                        {{ __('Benutzer endgültig löschen') }}
                    </button>
                </div>
            </x-card>

            <!-- Actions -->
            <div class="flex flex-col justify-end gap-3 pt-4 sm:flex-row">
                <a href="{{ route('admin.users.show', $user) }}" class="px-4 py-2 text-center text-gray-600 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                    {{ __('Abbrechen') }}
                </a>
                <button type="submit" class="px-4 py-2 text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                    <x-icon name="o-check" class="inline w-4 h-4 mr-1" />
                    {{ __('Änderungen speichern') }}
                </button>
            </div>
        </form>
    </div>
</div>
