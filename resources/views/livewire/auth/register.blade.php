<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new
#[Title('Deutsch lernen - Jetzt anmelden')]
#[Layout('components.layouts.auth')]
class extends Component {
    use Toast;

    // Données du formulaire
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $phone = '';
    public $date_of_birth;
    public string $bio = '';
    public string $role = 'student';
    public string $german_level = 'A1';
    public string $learning_goal = 'certification';
    public bool $terms_accepted = false;
    public bool $newsletter_subscribed = true;
    public bool $study_reminders = true;
    public string $motivation = '';

    /**
     * Règles de validation simplifiées
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'bio' => 'nullable|string|max:500',
            'role' => 'required|in:student,teacher',
            'german_level' => 'required_if:role,student|in:A1,A2,B1,B2,C1,C2',
            'learning_goal' => 'required_if:role,student|in:certification,conversation,travel,business',
            'terms_accepted' => 'accepted',
            'newsletter_subscribed' => 'boolean',
            'study_reminders' => 'boolean',
            'motivation' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Messages de validation
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'Bitte geben Sie Ihren vollständigen Namen ein.',
            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse ist bereits registriert.',
            'password.required' => 'Das Passwort ist erforderlich.',
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',
            'password.confirmed' => 'Die Passwortbestätigung stimmt nicht überein.',
            'terms_accepted.accepted' => 'Sie müssen die Allgemeinen Geschäftsbedingungen akzeptieren.',
            'german_level.required_if' => 'Bitte wählen Sie Ihr aktuelles Deutschniveau aus.',
            'learning_goal.required_if' => 'Bitte wählen Sie Ihr Lernziel aus.',
        ];
    }

    /**
     * Validation en temps réel désactivée pour la fluidité
     * Seule la validation à la soumission est active
     */
    public function updated($property, $value): void
    {
        // Validation uniquement pour l'email pour éviter les doublons
        if ($property === 'email') {
            $this->validateOnly('email');
        }
    }

    public function register(): void
    {
        $validated = $this->validate();

        DB::beginTransaction();
        
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password'], ['rounds' => 10]),
                'role' => $validated['role'],
                'german_level' => $validated['role'] === 'student' ? $validated['german_level'] : null,
                'learning_goal' => $validated['role'] === 'student' ? $validated['learning_goal'] : null,
                'phone' => $validated['phone'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'motivation' => $validated['motivation'] ?? null,
                'email_notifications' => $validated['newsletter_subscribed'],
                'study_reminders' => $validated['study_reminders'],
                'email_verified_at' => null,
            ]);

            $user->learningStreak()->create([
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_study_date' => null,
            ]);

            if ($validated['role'] === 'student') {
                $user->learningPath()->create([
                    'target_level' => $validated['german_level'],
                    'learning_goal' => $validated['learning_goal'],
                    'started_at' => now(),
                ]);
            }
            
            DB::commit();
            
            Auth::login($user);
            event(new Registered($user));
            
            $this->success('Willkommen! Ihr Konto wurde erfolgreich erstellt. 🇩🇪');
            
            $route = $user->isTeacher() ? 'teacher.dashboard' : 'student.dashboard';
            $this->redirectRoute($route, navigate: true);
            
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Registration error: ' . $e->getMessage());
            $this->error('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
        }
    }

    /**
     * Données statiques pour éviter les recalculs
     */
    public function getGermanLevelsProperty(): array
    {
        return [
            ['id' => 'A1', 'name' => 'A1 - Débutant', 'description' => 'Compréhension des bases', 'icon' => '🌱'],
            ['id' => 'A2', 'name' => 'A2 - Élémentaire', 'description' => 'Phrases simples', 'icon' => '📖'],
            ['id' => 'B1', 'name' => 'B1 - Intermédiaire', 'description' => 'Autonomie', 'icon' => '🎯'],
            ['id' => 'B2', 'name' => 'B2 - Avancé', 'description' => 'Communication fluide', 'icon' => '⭐'],
            ['id' => 'C1', 'name' => 'C1 - Expérimenté', 'description' => 'Maîtrise avancée', 'icon' => '🏆'],
            ['id' => 'C2', 'name' => 'C2 - Maîtrise', 'description' => 'Niveau natif', 'icon' => '👑'],
        ];
    }

    public function getLearningGoalsProperty(): array
    {
        return [
            ['id' => 'certification', 'name' => '🎓 Certification Goethe/ÖSD/TELC', 'description' => 'Préparation aux examens'],
            ['id' => 'conversation', 'name' => '💬 Conversation quotidienne', 'description' => 'Parler allemand au quotidien'],
            ['id' => 'travel', 'name' => '✈️ Voyage', 'description' => 'Se débrouiller en voyage'],
            ['id' => 'business', 'name' => '💼 Allemand professionnel', 'description' => 'Langue des affaires'],
        ];
    }

    public function getMotivationalQuoteProperty(): string
    {
        $quotes = [
            'Sprache ist der Schlüssel zur Welt. — Wilhelm von Humboldt',
            'Übung macht den Meister. — Proverbe allemand',
            'Der Weg ist das Ziel. — Konfuzius',
        ];
        return $quotes[array_rand($quotes)];
    }

    public function with(): array
    {
        return [
            'germanLevels' => $this->germanLevels,
            'learningGoals' => $this->learningGoals,
            'motivationalQuote' => $this->motivationalQuote,
            'minDate' => now()->subYears(100)->format('Y-m-d'),
            'maxDate' => now()->subYears(10)->format('Y-m-d'),
        ];
    }
}
?>

<div class="w-full max-w-3xl py-8 mx-auto">
    <!-- Header -->
    <div class="mb-8 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-[#FF6B35] to-[#1E6091] rounded-2xl mb-4 shadow-lg">
            <x-icon name="o-academic-cap" class="w-10 h-10 text-white" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900">Deutsch lernen mit AllemandExpress</h1>
        <p class="mt-2 text-gray-600">{{ $motivationalQuote }}</p>
    </div>

    <!-- Formulaire Mary-UI optimisé -->
    <x-card class="overflow-hidden border-0 shadow-xl">
        <div class="bg-gradient-to-r from-[#FF6B35] to-[#1E6091] p-6 text-center text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 opacity-10 text-8xl">🇩🇪</div>
            <h2 class="text-xl font-bold">Ihre Deutsch-Reise beginnt hier</h2>
            <p class="mt-1 text-sm text-white/80">Füllen Sie das Formular aus und starten Sie noch heute</p>
        </div>

        <div class="p-6">
            <x-form wire:submit="register" no-separator>
                <!-- Section Informations personnelles -->
                <div class="space-y-4">
                    <h3 class="flex items-center gap-2 pb-2 text-lg font-semibold text-gray-900 border-b">
                        <x-icon name="o-user" class="w-5 h-5 text-[#FF6B35]" />
                        Persönliche Informationen
                    </h3>
                    
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input 
                            label="Vollständiger Name *"
                            wire:model="name"
                            icon="o-user"
                            placeholder="Max Mustermann"
                            required 
                            autofocus />
                        
                        <x-input 
                            label="E-Mail-Adresse *"
                            type="email"
                            wire:model="email"
                            icon="o-envelope"
                            placeholder="max@example.com"
                            required />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input 
                            label="Telefonnummer"
                            wire:model="phone"
                            icon="o-phone"
                            placeholder="+49 123 456789" />
                        
                        <x-input 
                            label="Geburtsdatum"
                            type="date"
                            wire:model="date_of_birth"
                            icon="o-calendar"
                            :min="$minDate"
                            :max="$maxDate" />
                    </div>

                    <x-textarea 
                        label="Was motiviert Sie, Deutsch zu lernen?"
                        wire:model="motivation"
                        placeholder="z.B. Ich möchte in Deutschland studieren, mein Goethe-Zertifikat machen..."
                        rows="2" />
                </div>

                <!-- Section Rôle - Optimisée sans animation -->
                <div class="mt-6 space-y-4">
                    <h3 class="pb-2 text-lg font-semibold text-gray-900 border-b">Ich bin</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="relative cursor-pointer" wire:click="$set('role', 'student')">
                            <div class="p-4 border-2 rounded-xl text-center transition-all duration-150
                                        {{ $role === 'student' ? 'border-[#FF6B35] bg-orange-50 text-[#FF6B35]' : 'border-gray-200 hover:border-gray-400' }}">
                                <x-icon name="o-academic-cap" class="w-8 h-8 mx-auto mb-2" />
                                <div class="font-semibold">Student/in</div>
                                <p class="mt-1 text-xs text-gray-500">Deutsch lernen</p>
                            </div>
                        </div>
                        
                        <div class="relative cursor-pointer" wire:click="$set('role', 'teacher')">
                            <div class="p-4 border-2 rounded-xl text-center transition-all duration-150
                                        {{ $role === 'teacher' ? 'border-[#1E6091] bg-blue-50 text-[#1E6091]' : 'border-gray-200 hover:border-gray-400' }}">
                                <x-icon name="custom.chalkboard-teacher" class="w-8 h-8 mx-auto mb-2" />
                                <div class="font-semibold">Lehrer/in</div>
                                <p class="mt-1 text-xs text-gray-500">Deutsch unterrichten</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Niveau d'allemand - Affichage conditionnel optimisé -->
                @if($role === 'student')
                <div class="mt-6 space-y-4">
                    <h3 class="flex items-center gap-2 pb-2 text-lg font-semibold text-gray-900 border-b">
                        🇩🇪 Ihr aktuelles Deutschniveau
                    </h3>
                    
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        @foreach($germanLevels as $level)
                        <div class="relative cursor-pointer" wire:click="$set('german_level', '{{ $level['id'] }}')">
                            <div class="p-3 border rounded-lg text-center transition-all duration-150
                                        {{ $german_level === $level['id'] ? 'border-[#FF6B35] bg-orange-50' : 'border-gray-200 hover:border-gray-400' }}">
                                <div class="mb-1 text-2xl">{{ $level['icon'] }}</div>
                                <div class="font-bold text-gray-900">{{ $level['id'] }}</div>
                                <div class="text-xs text-gray-600">{{ $level['name'] }}</div>
                                <div class="mt-1 text-xs text-gray-400">{{ $level['description'] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Section Objectif d'apprentissage -->
                <div class="mt-6 space-y-4">
                    <h3 class="pb-2 text-lg font-semibold text-gray-900 border-b">Ihr Lernziel</h3>
                    
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach($learningGoals as $goal)
                        <div class="relative cursor-pointer" wire:click="$set('learning_goal', '{{ $goal['id'] }}')">
                            <div class="p-3 border rounded-lg transition-all duration-150
                                        {{ $learning_goal === $goal['id'] ? 'border-[#FF6B35] bg-orange-50' : 'border-gray-200 hover:border-gray-400' }}">
                                <div class="font-medium text-gray-900">{{ $goal['name'] }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ $goal['description'] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Section Mot de passe -->
                <div class="mt-6 space-y-4">
                    <h3 class="pb-2 text-lg font-semibold text-gray-900 border-b">Sicherheit</h3>
                    
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-password 
                            label="Passwort *"
                            wire:model="password"
                            placeholder="••••••••"
                            required 
                            hint="Mindestens 8 Zeichen" />
                        
                        <x-password 
                            label="Passwort bestätigen *"
                            wire:model="password_confirmation"
                            placeholder="••••••••"
                            required />
                    </div>
                </div>

                <!-- Section Conditions -->
                <div class="pt-4 space-y-4">
                    <x-checkbox 
                        label="Ich akzeptiere die Allgemeinen Geschäftsbedingungen und Datenschutzerklärung *"
                        wire:model="terms_accepted" />
                    
                    <x-checkbox 
                        label="Ja, ich möchte Lern-Tipps und Neuigkeiten erhalten"
                        wire:model="newsletter_subscribed" />
                    
                    <x-checkbox 
                        label="Erinnere mich an meine tägliche Lern-Session"
                        wire:model="study_reminders" />
                </div>

                <!-- Bouton de soumission -->
                <div class="pt-4">
                    <x-button 
                        type="submit"
                        label="🇩🇪 Deutsch lernen beginnen →"
                        class="w-full py-3 text-lg font-semibold shadow-lg btn-primary"
                        spinner="register"
                    />
                    <p class="mt-3 text-xs text-center text-gray-500">
                        30 Tage kostenlos testen. Jederzeit kündbar.
                    </p>
                </div>
            </x-form>

            <!-- Divider -->
            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 text-gray-500 bg-white">Oder registrieren mit</span>
                </div>
            </div>

            <!-- Social Signup -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <a href="#" class="flex items-center justify-center px-4 py-2 text-gray-700 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                    <x-icon name="brands.google" class="w-5 h-5 mr-2" />
                    Google
                </a>
                <a href="#" class="flex items-center justify-center px-4 py-2 text-gray-700 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                    <x-icon name="brands.facebook" class="w-5 h-5 mr-2" />
                    Facebook
                </a>
            </div>

            <!-- Login Link -->
            <div class="pt-6 text-center border-t border-gray-200">
                <p class="text-gray-600">
                    Bereits ein Konto?
                    <a href="{{ route('login') }}" 
                       wire:navigate
                       class="font-semibold text-[#FF6B35] hover:text-[#E55A2A] hover:underline transition-colors">
                        Jetzt anmelden
                    </a>
                </p>
            </div>
        </div>
    </x-card>

    <!-- Security Notice -->
    <div class="mt-6 text-center">
        <div class="inline-flex items-center px-4 py-2 text-xs text-gray-500 rounded-lg bg-gray-50">
            <x-icon name="o-shield-check" class="w-4 h-4 mr-2 text-green-500" />
            <span>Ihre Daten sind sicher und werden verschlüsselt übertragen</span>
        </div>
    </div>
</div>

@push('styles')
<style>
.btn-primary {
    background: linear-gradient(135deg, #FF6B35 0%, #E55A2A 100%);
}
.btn-primary:hover {
    background: linear-gradient(135deg, #E55A2A 0%, #FF6B35 100%);
}
</style>
@endpush