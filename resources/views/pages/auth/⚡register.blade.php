<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mary\Traits\Toast;

new
#[Title('Register')]
#[Layout('layouts.guest')]
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
     * Règles de validation
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
     * Messages de validation (traduits via __())
     */
    protected function messages(): array
    {
        return [
            'name.required' => __('Please enter your full name.'),
            'email.required' => __('Please enter a valid email address.'),
            'email.email' => __('Please enter a valid email address.'),
            'email.unique' => __('This email is already registered.'),
            'password.required' => __('Please enter your password.'),
            'password.min' => __('Password must be at least 8 characters.'),
            'password.confirmed' => __('The password confirmation does not match.'),
            'terms_accepted.accepted' => __('Please accept the terms.'),
            'german_level.required_if' => __('Please select your German level.'),
            'learning_goal.required_if' => __('Please select your learning goal.'),
        ];
    }

    /**
     * Validation en temps réel pour l'email uniquement
     */
    public function updated($property, $value): void
    {
        if ($property === 'email') {
            $this->validateOnly('email');
        }
    }

    /**
     * Enregistrement de l'utilisateur
     */
    public function register(): void
    {
        $validated = $this->validate();

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
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

            // Création du learning streak
            $user->learningStreak()->create([
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_study_date' => null,
            ]);

            // Création du learning path pour les étudiants
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

            $this->success(__('Your account has been created successfully!') . ' 🇩🇪');

            $route = $user->isTeacher() ? 'teacher.dashboard' : 'student.dashboard';
            $this->redirectRoute($route);

        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Registration error: ' . $e->getMessage());
            $this->error(__('An error occurred. Please try again.'));
        }
    }

    /**
     * Niveaux d'allemand
     */
    public function getGermanLevelsProperty(): array
    {
        return [
            ['id' => 'A1', 'name' => __('A1 - Beginner'), 'description' => __('Basic understanding'), 'icon' => '🌱'],
            ['id' => 'A2', 'name' => __('A2 - Elementary'), 'description' => __('Simple phrases'), 'icon' => '📖'],
            ['id' => 'B1', 'name' => __('B1 - Intermediate'), 'description' => __('Autonomy'), 'icon' => '🎯'],
            ['id' => 'B2', 'name' => __('B2 - Upper Intermediate'), 'description' => __('Fluent communication'), 'icon' => '⭐'],
            ['id' => 'C1', 'name' => __('C1 - Advanced'), 'description' => __('Advanced mastery'), 'icon' => '🏆'],
            ['id' => 'C2', 'name' => __('C2 - Mastery'), 'description' => __('Native level'), 'icon' => '👑'],
        ];
    }

    /**
     * Objectifs d'apprentissage
     */
    public function getLearningGoalsProperty(): array
    {
        return [
            ['id' => 'certification', 'name' => __('🎓 Certification (Goethe/ÖSD/TELC)'), 'description' => __('Exam preparation')],
            ['id' => 'conversation', 'name' => __('💬 Daily conversation'), 'description' => __('Speak German daily')],
            ['id' => 'travel', 'name' => __('✈️ Travel'), 'description' => __('Get by while traveling')],
            ['id' => 'business', 'name' => __('💼 Business German'), 'description' => __('Business language')],
        ];
    }

    /**
     * Citation motivante
     */
    public function getMotivationalQuoteProperty(): string
    {
        $quotes = [
            __('Language is the key to the world. — Wilhelm von Humboldt'),
            __('Practice makes perfect. — German proverb'),
            __('The journey is the destination. — Confucius'),
        ];
        return $quotes[array_rand($quotes)];
    }

    public function loginSocial(): void
    {
        $this->info(__('Social login is not implemented yet. Please use email registration for now.') . ' 🤖');
    }

    public function render()
    {
        return $this->view([
            'germanLevels' => $this->germanLevels,
            'learningGoals' => $this->learningGoals,
            'motivationalQuote' => $this->motivationalQuote,
            'minDate' => now()->subYears(100)->format('Y-m-d'),
            'maxDate' => now()->subYears(10)->format('Y-m-d'),
        ]);
    }
};

?>

<div class="w-full max-w-3xl py-8 mx-auto">
    <!-- Header -->
    <div class="mb-8 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-[#FF6B35] to-[#1E6091] rounded-2xl mb-4 shadow-lg">
            <x-icon name="o-academic-cap" class="w-10 h-10 text-white" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900">{{ __('AllemandExpress') }}</h1>
        <p class="mt-2 text-gray-600">{{ $motivationalQuote }}</p>
    </div>

    <!-- Formulaire Mary-UI -->
    <x-card class="overflow-hidden border-0 shadow-xl">
        <div class="bg-gradient-to-r from-[#FF6B35] to-[#1E6091] p-6 text-center text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 opacity-10 text-8xl">🇩🇪</div>
            <h2 class="text-xl font-bold">{{ __('Register') }}</h2>
            <p class="mt-1 text-sm text-white/80">{{ __('Create your account') }}</p>
        </div>

        <div class="p-6">
            <x-form wire:submit="register" no-separator>
                <!-- Section Informations personnelles -->
                <div class="space-y-4">
                    <h3 class="flex items-center gap-2 pb-2 text-lg font-semibold text-gray-900 border-b">
                        <x-icon name="o-user" class="w-5 h-5 text-[#FF6B35]" />
                        {{ __('Personal Information') }}
                    </h3>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input
                            label="{{ __('Full Name') }} "
                            wire:model="name"
                            icon="o-user"
                            placeholder="Max Mustermann"
                            required
                            autofocus />

                        <x-input
                            label="{{ __('Email Address') }} "
                            type="email"
                            wire:model="email"
                            icon="o-envelope"
                            placeholder="max@example.com"
                            required />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input
                            label="{{ __('Phone Number') }}"
                            wire:model="phone"
                            icon="o-phone"
                            placeholder="+49 123 456789" />

                        <x-datepicker
                            label="{{ __('Date of Birth') }}"
                            type="date"
                            wire:model="date_of_birth"
                            icon="o-calendar"
                            :min="$minDate"
                            :max="$maxDate" />
                    </div>

                    <x-textarea
                        label="{!! __('What motivates you to learn German?') !!}"
                        wire:model="motivation"
                        placeholder="e.g., I want to study in Germany, get my Goethe certificate..."
                        rows="2" />
                </div>

                <!-- Section Rôle -->
                <div class="mt-6 space-y-4">
                    <h3 class="pb-2 text-lg font-semibold text-gray-900 border-b">{{ __('I am') }}</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="relative cursor-pointer" wire:click="$set('role', 'student')">
                            <div class="p-4 border-2 rounded-xl text-center transition-all duration-150
                                        {{ $role === 'student' ? 'border-[#FF6B35] bg-orange-50 text-[#FF6B35]' : 'border-gray-200 hover:border-gray-400' }}">
                                <x-icon name="o-academic-cap" class="w-8 h-8 mx-auto mb-2" />
                                <div class="font-semibold">{{ __('Student') }}</div>
                                <p class="mt-1 text-xs text-gray-500">{{ __('Learn German') }}</p>
                            </div>
                        </div>

                        <div class="relative cursor-pointer" wire:click="$set('role', 'teacher')">
                            <div class="p-4 border-2 rounded-xl text-center transition-all duration-150
                                        {{ $role === 'teacher' ? 'border-[#1E6091] bg-blue-50 text-[#1E6091]' : 'border-gray-200 hover:border-gray-400' }}">
                                <x-icon name="o-user-group" class="w-8 h-8 mx-auto mb-2" />
                                <div class="font-semibold">{{ __('Teacher') }}</div>
                                <p class="mt-1 text-xs text-gray-500">{{ __('Teach German') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Niveau d'allemand (étudiant uniquement) -->
                @if($role === 'student')
                <div class="mt-6 space-y-4">
                    <h3 class="flex items-center gap-2 pb-2 text-lg font-semibold text-gray-900 border-b">
                        🇩🇪 {{ __('Your current German level') }}
                    </h3>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        @foreach($germanLevels as $level)
                        <div class="relative cursor-pointer" wire:click="$set('german_level', '{{ $level['id'] }}')">
                            <div class="p-3 border rounded-lg text-center transition-all duration-150
                                        {{ $german_level === $level['id'] ? 'border-[#FF6B35] bg-orange-50' : 'border-gray-200 hover:border-gray-400' }}">
                                <div class="mb-1 text-2xl">{{ $level['icon'] }}</div>
                                <div class="font-bold text-gray-900">{{ $level['id'] }}</div>
                                <div class="text-xs text-gray-600">{{ $level['name'] }}</div>
                                <div class="mt-1 text-xs text-gray-400">{{ __($level['description']) }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Section Objectif d'apprentissage -->
                <div class="mt-6 space-y-4">
                    <h3 class="pb-2 text-lg font-semibold text-gray-900 border-b">{{ __('Your learning goal') }}</h3>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach($learningGoals as $goal)
                        <div class="relative cursor-pointer" wire:click="$set('learning_goal', '{{ $goal['id'] }}')">
                            <div class="p-3 border rounded-lg transition-all duration-150
                                        {{ $learning_goal === $goal['id'] ? 'border-[#FF6B35] bg-orange-50' : 'border-gray-200 hover:border-gray-400' }}">
                                <div class="font-medium text-gray-900">{{ __($goal['name']) }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ __($goal['description']) }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Section Sécurité -->
                <div class="mt-6 space-y-4">
                    <h3 class="pb-2 text-lg font-semibold text-gray-900 border-b">{{ __('Security') }}</h3>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-password
                            label="{{ __('Password') }} "
                            wire:model="password"
                            placeholder="••••••••"
                            required
                            hint="{{ __('Minimum 8 characters') }}" />

                        <x-password
                            label="{{ __('Confirm Password') }} "
                            wire:model="password_confirmation"
                            placeholder="••••••••"
                            required />
                    </div>
                </div>

                <!-- Section Conditions -->
                <div class="pt-4 space-y-4">
                    <x-checkbox
                        label="{!! __('I agree to the terms and privacy policy') !!}"
                        wire:model="terms_accepted" />

                    <x-checkbox
                        label="{!! __('Yes, I want to receive learning tips and news') !!}"
                        wire:model="newsletter_subscribed" />

                    <x-checkbox
                        label="{!! __('Remind me of my daily learning session') !!}"
                        wire:model="study_reminders" />
                </div>

                <!-- Bouton de soumission -->
                <div class="pt-4">
                    <x-button
                        type="submit"
                        label="🇩🇪 {{ __('Register') }}"
                        class="w-full py-3 text-lg font-semibold shadow-lg btn-primary"
                        spinner="register"
                    />
                    <p class="mt-3 text-xs text-center text-gray-500">
                        {{ __('30-day free trial. Cancel anytime.') }}
                    </p>
                </div>
            </x-form>

            <!-- Divider -->
            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 text-gray-500 bg-white">{{ __('Or register with') }}</span>
                </div>
            </div>

            <!-- Social Signup -->
            <div class="flex items-center justify-center gap-3 mb-6 ">
                <x-button label="{{ __('Google') }}" icon="fab.google" wire:click="loginSocial" class="btn-primary" />

                <x-button label="{{ __('Facebook') }}" icon="fab.facebook" wire:click="loginSocial" class="btn-primary" />
            </div>

            <!-- Login Link -->
            <div class="pt-6 text-center border-t border-gray-200">
                <p class="text-gray-600">
                    {{ __("Already have an account?") }}
                    <a href="{{ route('login') }}"
                       wire:navigate
                       class="font-semibold text-[#FF6B35] hover:text-[#E55A2A] hover:underline transition-colors">
                        {{ __('Sign in') }}
                    </a>
                </p>
            </div>
        </div>
    </x-card>

    <!-- Security Notice -->
    <div class="mt-6 text-center">
        <div class="inline-flex items-center px-4 py-2 text-xs text-gray-500 rounded-lg bg-gray-50">
            <x-icon name="o-shield-check" class="w-4 h-4 mr-2 text-green-500" />
            <span>{{ __('Your data is secure and encrypted') }}</span>
        </div>
    </div>
</div>

@push('styles')
<style>
.btn-primary {
    background: linear-gradient(135deg, #FF6B35 0%, #1E6091 100%);
}
.btn-primary:hover {
    background: linear-gradient(135deg, #1E6091 0%, #FF6B35 100%);
}
</style>
@endpush
