<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Title('Create New Password')]
#[Layout('components.layouts.auth')]
class extends Component
{
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $token = '';
    public bool $passwordUpdated = false;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $data = $this->validate([
            'token' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'email' => ['required', 'email'],
        ]);

        $status = Password::reset(
            $data,
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            $this->passwordUpdated = true;
            Session::flash('status', __($status));

            // Redirection automatique après 3 secondes
            $this->dispatch('password-reset-success');
        } else {
            $this->addError('email', __($status));
        }
    }

    // Validation en temps réel pour le mot de passe
    public function updatedPassword($value): void
    {
        $this->validateOnly('password', [
            'password' => ['required', 'string', 'min:8'],
        ]);
    }
}; ?>

<div class="w-full max-w-md mx-auto animate-fade-in" x-data="{
    countdown: 3,
    startCountdown() {
        if (this.countdown > 0) {
            setTimeout(() => {
                this.countdown--;
                this.startCountdown();
            }, 1000);
        } else {
            window.location.href = '{{ route('login') }}';
        }
    }
}" x-init="if($wire.passwordUpdated) startCountdown()">
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl mb-4 shadow-lg">
            <x-icon name="o-lock-closed" class="w-8 h-8 text-white" />
        </div>
        <h1 class="text-2xl font-bold text-gray-900">{!! __('Create New Password') !!}</h1>
        <p class="text-gray-600 mt-2">
            @if(!$passwordUpdated)
                {!! __('Enter your new password below') !!}
            @else
                {!! __('Password successfully updated!') !!}
            @endif
        </p>
    </div>

    <!-- Password Reset Card -->
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
        <!-- Card Header -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 p-6 text-center text-white">
            <h2 class="text-xl font-bold">
                @if(!$passwordUpdated)
                    {!! __('Reset Your Password') !!}
                @else
                    {!! __('Success!') !!}
                @endif
            </h2>
            <p class="text-green-100 text-sm opacity-90">
                @if(!$passwordUpdated)
                    {!! __('Almost there! Choose a new password') !!}
                @else
                    {!! __('Your password has been updated') !!}
                @endif
            </p>
        </div>

        <!-- Card Content -->
        <div class="p-8">
            @if (session('status'))
                <x-alert icon="o-check-circle" class="alert-success mb-6 animate-bounce-in" dismissible>
                    {{ session('status') }}
                </x-alert>
            @endif

            @if ($errors->any())
                <x-alert icon="o-exclamation-triangle" class="alert-error mb-6 animate-shake" dismissible>
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <!-- Success State -->
            @if($passwordUpdated)
                <div class="text-center py-8">
                    <!-- Success Animation -->
                    <div class="relative mx-auto mb-6">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto success-animation">
                            <x-icon name="o-check" class="w-10 h-10 text-green-600" />
                        </div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-24 h-24 border-4 border-green-200 rounded-full animate-ping opacity-50"></div>
                        </div>
                    </div>

                    <!-- Success Message -->
                    <h3 class="text-xl font-bold text-gray-900 mb-3">{!! __('Password Updated!') !!}</h3>
                    <p class="text-gray-600 mb-6">
                        {!! __('Your password has been successfully reset. You can now sign in with your new password.') !!}
                    </p>

                    <!-- Countdown -->
                    <div class="bg-green-50 rounded-lg p-4 mb-6">
                        <p class="text-sm text-green-800">
                            <x-icon name="o-clock" class="w-4 h-4 inline mr-1" />
                            {!! __('Redirecting to login in') !!}
                            <span x-text="countdown" class="font-bold text-green-600"></span>
                            {!! __('seconds...') !!}
                        </p>
                    </div>

                    <!-- Manual Redirect -->
                    <div class="space-y-3">
                        <x-button
                            link="{{ route('login') }}"
                            class="btn-primary w-full rounded-lg py-3"
                            icon="o-arrow-right-on-rectangle">
                            {!! __('Go to Login Now') !!}
                        </x-button>

                        <p class="text-sm text-gray-600">
                            {!! __('Not redirecting?') !!}
                            <a href="{{ route('login') }}" class="text-primary-600 hover:text-primary-700 font-medium hover:underline">
                                {!! __('Click here') !!}
                            </a>
                        </p>
                    </div>
                </div>
            @else
                <!-- Form State -->
                <x-form wire:submit="resetPassword" class="space-y-6">
                    <!-- Email Display (readonly) -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            {!! __('Email Address') !!}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <x-icon name="o-envelope" class="w-5 h-5 text-gray-400" />
                            </div>
                            <x-input
                                type="email"
                                wire:model="email"
                                class="pl-10 w-full rounded-lg border-gray-300 bg-gray-50 cursor-not-allowed"
                                readonly />
                        </div>
                        <p class="text-xs text-gray-500">
                            {!! __('This is the email associated with your account') !!}
                        </p>
                    </div>

                    <!-- Password Fields -->
                    <div class="space-y-4">
                        <!-- New Password -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                {!! __('New Password') !!}
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <x-icon name="o-lock-closed" class="w-5 h-5 text-gray-400 group-focus-within:text-primary-500" />
                                </div>
                                <x-input
                                    type="password"
                                    wire:model="password"
                                    class="pl-10 w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                    placeholder="••••••••"
                                    required
                                    autofocus />
                            </div>
                            @error('password')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                {!! __('Confirm New Password') !!}
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <x-icon name="o-lock-closed" class="w-5 h-5 text-gray-400 group-focus-within:text-primary-500" />
                                </div>
                                <x-input
                                    type="password"
                                    wire:model="password_confirmation"
                                    class="pl-10 w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                    placeholder="••••••••"
                                    required />
                            </div>
                        </div>
                    </div>

                    <!-- Password Requirements -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-700 mb-2">{!! __('Your password must:') !!}</p>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li class="flex items-center">
                                <x-icon name="{{ strlen($password) >= 8 ? 'o-check-circle' : 'o-minus-circle' }}"
                                        class="w-4 h-4 mr-2 {{ strlen($password) >= 8 ? 'text-green-500' : 'text-gray-400' }}" />
                                {!! __('Be at least 8 characters long') !!}
                            </li>
                            <li class="flex items-center">
                                <x-icon name="{{ preg_match('/[A-Z]/', $password) ? 'o-check-circle' : 'o-minus-circle' }}"
                                        class="w-4 h-4 mr-2 {{ preg_match('/[A-Z]/', $password) ? 'text-green-500' : 'text-gray-400' }}" />
                                {!! __('Contain at least one uppercase letter') !!}
                            </li>
                            <li class="flex items-center">
                                <x-icon name="{{ preg_match('/[a-z]/', $password) ? 'o-check-circle' : 'o-minus-circle' }}"
                                        class="w-4 h-4 mr-2 {{ preg_match('/[a-z]/', $password) ? 'text-green-500' : 'text-gray-400' }}" />
                                {!! __('Contain at least one lowercase letter') !!}
                            </li>
                            <li class="flex items-center">
                                <x-icon name="{{ preg_match('/[0-9]/', $password) ? 'o-check-circle' : 'o-minus-circle' }}"
                                        class="w-4 h-4 mr-2 {{ preg_match('/[0-9]/', $password) ? 'text-green-500' : 'text-gray-400' }}" />
                                {!! __('Contain at least one number') !!}
                            </li>
                        </ul>
                    </div>

                    <!-- Security Warning -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex">
                            <x-icon name="o-exclamation-triangle" class="w-5 h-5 text-yellow-600 mr-3 flex-shrink-0" />
                            <div>
                                <p class="text-sm text-yellow-800 font-medium">{!! __('Security Tip:') !!}</p>
                                <p class="text-sm text-yellow-700 mt-1">
                                    {!! __('Choose a strong, unique password that you don\'t use on other websites.') !!}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <x-button
                            type="submit"
                            class="btn-primary w-full rounded-lg py-3 text-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-300"
                            spinner="resetPassword"
                            loading-delay="200">
                            {!! __('Reset Password') !!}
                        </x-button>
                    </div>
                </x-form>

                <!-- Divider -->
                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500">{!! __('Need help?') !!}</span>
                    </div>
                </div>

                <!-- Support Links -->
                <div class="text-center space-y-3">
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center text-primary-600 hover:text-primary-700 font-medium hover:underline">
                        <x-icon name="o-arrow-left" class="w-4 h-4 mr-2" />
                        {!! __('Back to Sign In') !!}
                    </a>

                    <p class="text-sm text-gray-600">
                        {!! __('Still having trouble?') !!}
                        <a href="#" class="text-primary-600 hover:text-primary-700 font-medium hover:underline">
                            {!! __('Contact our support team') !!}
                        </a>
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Security Notice -->
    <div class="mt-6 text-center">
        <div class="inline-flex items-center text-xs text-gray-500 bg-gray-50 rounded-lg px-4 py-2">
            <x-icon name="o-shield-check" class="w-4 h-4 text-green-500 mr-2" />
            <span>{!! __('All password changes are encrypted and secure') !!}</span>
        </div>
    </div>
</div>

@push('styles')
<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes bounce-in {
    0% {
        opacity: 0;
        transform: scale(0.95);
    }
    60% {
        opacity: 1;
        transform: scale(1.02);
    }
    100% {
        transform: scale(1);
    }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

@keyframes success-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.animate-fade-in {
    animation: fade-in 0.6s ease-out;
}

.animate-bounce-in {
    animation: bounce-in 0.6s ease-out;
}

.animate-shake {
    animation: shake 0.6s ease-in-out;
}

.success-animation {
    animation: success-pulse 1s ease-in-out;
}

/* Password strength indicator */
.password-strength {
    height: 4px;
    border-radius: 2px;
    transition: all 0.3s ease;
    margin-top: 8px;
}

.password-strength.weak {
    background-color: #ef4444;
    width: 25%;
}

.password-strength.fair {
    background-color: #f59e0b;
    width: 50%;
}

.password-strength.good {
    background-color: #3b82f6;
    width: 75%;
}

.password-strength.strong {
    background-color: #10b981;
    width: 100%;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update password strength indicator
    const passwordInput = document.querySelector('input[wire\\:model="password"]');
    if (passwordInput) {
        const strengthIndicator = document.createElement('div');
        strengthIndicator.className = 'password-strength';
        passwordInput.parentNode.appendChild(strengthIndicator);

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;

            // Length
            if (password.length >= 8) strength += 25;

            // Contains uppercase
            if (/[A-Z]/.test(password)) strength += 25;

            // Contains lowercase
            if (/[a-z]/.test(password)) strength += 25;

            // Contains number
            if (/[0-9]/.test(password)) strength += 25;

            // Update indicator
            strengthIndicator.className = 'password-strength';
            if (strength <= 25) {
                strengthIndicator.classList.add('weak');
            } else if (strength <= 50) {
                strengthIndicator.classList.add('fair');
            } else if (strength <= 75) {
                strengthIndicator.classList.add('good');
            } else {
                strengthIndicator.classList.add('strong');
            }
        });
    }

    // Auto-focus password field on load
    @if(!$passwordUpdated)
        setTimeout(() => {
            const passwordField = document.querySelector('input[wire\\:model="password"]');
            if (passwordField) passwordField.focus();
        }, 300);
    @endif
});
</script>
@endpush
