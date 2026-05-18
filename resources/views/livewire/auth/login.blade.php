<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new
#[Title('Anmelden - Deutsch lernen')]
#[Layout('components.layouts.auth')]
class extends Component {
    use Toast;

    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    /**
     * Règles de validation
     */
    protected function rules(): array
    {
        return [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ];
    }

    /**
     * Messages de validation
     */
    protected function messages(): array
    {
        return [
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'password.required' => 'Bitte geben Sie Ihr Passwort ein.',
        ];
    }

    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());
            
            $this->error('Die Anmeldeinformationen sind ungültig.');
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $user = Auth::user();
        
        $this->success('Willkommen zurück, ' . $user->name . '! 🇩🇪');

        if ($user->isAdmin()) {
            $this->redirectRoute('admin.dashboard', navigate: true);
        } elseif ($user->isTeacher()) {
            $this->redirectRoute('teacher.dashboard', navigate: true);
        } else {
            $this->redirectRoute('student.dashboard', navigate: true);
        }
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return \Illuminate\Support\Str::transliterate(
            \Illuminate\Support\Str::lower($this->email) . '|' . request()->ip()
        );
    }

    public function getMotivationalQuoteProperty(): string
    {
        $quotes = [
            'Willkommen zurück! Bereit für deine Deutschstunde? 📚',
            'Schön, dich wiederzusehen! Weiter geht\'s mit Deutsch 🇩🇪',
            'Dein nächstes Abenteuer auf Deutsch wartet! 🚀',
            'Bereit für eine neue Lektion? Los geht\'s! ✨',
        ];
        return $quotes[array_rand($quotes)];
    }

    public function with(): array
    {
        return [
            'motivationalQuote' => $this->getMotivationalQuoteProperty(),
        ];
    }
}
?>

<div class="w-full max-w-md py-8 mx-auto">
    <!-- Header Mobile -->
    <div class="mb-8 text-center md:hidden">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-[#FF6B35] to-[#1E6091] rounded-2xl mb-4 shadow-lg">
            <x-icon name="o-academic-cap" class="w-8 h-8 text-white" />
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Deutsch lernen</h1>
        <p class="mt-2 text-gray-600">{{ $motivationalQuote }}</p>
    </div>

    <!-- Card Container -->
    <x-card class="overflow-hidden border-0 shadow-xl">
        <!-- Card Header -->
        <div class="bg-gradient-to-r from-[#FF6B35] to-[#1E6091] p-6 text-center text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 opacity-10 text-8xl">🇩🇪</div>
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-3 bg-white/20 backdrop-blur-sm rounded-xl">
                <x-icon name="o-lock-closed" class="w-6 h-6" />
            </div>
            <h2 class="text-xl font-bold">Willkommen zurück!</h2>
            <p class="mt-1 text-sm text-white/80">Melde dich an, um weiterzulernen</p>
        </div>

        <!-- Card Content -->
        <div class="p-6">
            @if (session('status'))
                <x-alert icon="o-check-circle" class="mb-6 alert-success" dismissible>
                    {{ session('status') }}
                </x-alert>
            @endif

            <x-form wire:submit="login" no-separator>
                <!-- Email -->
                <x-input 
                    label="E-Mail-Adresse *"
                    type="email"
                    wire:model="email"
                    icon="o-envelope"
                    placeholder="max@example.com"
                    required 
                    autofocus />
                
                <!-- Password -->
                <x-password 
                    label="Passwort *"
                    wire:model="password"
                    placeholder="••••••••"
                    required>
                    <x-slot:append>
                        <a href="{{ route('password.request') }}" 
                           wire:navigate
                           class="text-xs text-[#FF6B35] hover:text-[#E55A2A] font-medium">
                            Passwort vergessen?
                        </a>
                    </x-slot:append>
                </x-password>
                
                <!-- Remember Me & Submit -->
                <div class="flex items-center justify-between pt-2">
                    <x-checkbox 
                        label="Angemeldet bleiben"
                        wire:model="remember" />
                    
                    <x-button 
                        type="submit"
                        label="Anmelden →"
                        class="px-6 shadow-lg btn-primary"
                        spinner="login" />
                </div>
            </x-form>

            <!-- Divider -->
            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 text-gray-500 bg-white">Oder anmelden mit</span>
                </div>
            </div>

            <!-- Social Login -->
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

            <!-- Sign Up Link -->
            <div class="pt-6 text-center border-t border-gray-200">
                <p class="text-gray-600">
                    Noch kein Konto?
                    <a href="{{ route('register') }}" 
                       wire:navigate
                       class="font-semibold text-[#FF6B35] hover:text-[#E55A2A] hover:underline transition-colors">
                        Jetzt kostenlos registrieren
                    </a>
                </p>
            </div>
        </div>
    </x-card>

    <!-- Security Notice -->
    <div class="mt-6 text-center">
        <div class="inline-flex items-center px-4 py-2 text-xs text-gray-500 rounded-lg bg-gray-50">
            <x-icon name="o-shield-check" class="w-4 h-4 mr-2 text-green-500" />
            <span>Deine Daten sind sicher und verschlüsselt</span>
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