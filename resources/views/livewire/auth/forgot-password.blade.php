<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Title('Reset Password')]
#[Layout('components.layouts.auth')]
class extends Component {

    public string $email = '';
    public bool $linkSent = false;

    public function sendPasswordResetLink(): void
    {
        $data = $this->validate([
            'email' => ['required', 'string', 'email', 'max:255']
        ]);

        $status = Password::sendResetLink($data);

        if ($status == Password::RESET_LINK_SENT) {
            $this->linkSent = true;
            session()->flash('status', __($status));
            // On ne reset pas l'email pour que l'utilisateur puisse voir ce qu'il a saisi
        } else {
            $this->addError('email', __($status));
        }
    }

    public function resendLink(): void
    {
        $this->linkSent = false;
        $this->sendPasswordResetLink();
    }

}; ?>

<div class="w-full max-w-md mx-auto animate-fade-in">
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-accent-500 to-accent-600 rounded-2xl mb-4 shadow-lg">
            <x-icon name="o-key" class="w-8 h-8 text-white" />
        </div>
        <h1 class="text-2xl font-bold text-gray-900">{!! __('Reset Your Password') !!}</h1>
        <p class="text-gray-600 mt-2">{!! __('We\'ll send you a link to reset your password') !!}</p>
    </div>

    <!-- Password Reset Card -->
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
        <!-- Card Header -->
        <div class="bg-gradient-to-r from-accent-500 to-accent-600 p-6 text-center text-white">
            <h2 class="text-xl font-bold">{!! __('Forgot Password?') !!}</h2>
            <p class="text-accent-100 text-sm opacity-90">{!! __('No worries, it happens to everyone') !!}</p>
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
            @if($linkSent)
                <div class="text-center py-8">
                    <!-- Success Icon -->
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <x-icon name="o-envelope" class="w-10 h-10 text-green-600" />
                    </div>

                    <!-- Success Message -->
                    <h3 class="text-xl font-bold text-gray-900 mb-3">{!! __('Check Your Email!') !!}</h3>
                    <p class="text-gray-600 mb-6">
                        {!! __('We\'ve sent a password reset link to:') !!}<br>
                        <span class="font-semibold text-primary-600">{{ $email }}</span>
                    </p>

                    <!-- Instructions -->
                    <div class="bg-blue-50 rounded-lg p-4 mb-6 text-left">
                        <p class="text-sm text-blue-800">
                            <x-icon name="o-light-bulb" class="w-4 h-4 inline mr-1" />
                            {!! __('If you don\'t see the email, check your spam folder or') !!}
                            <button wire:click="resendLink"
                                    class="text-blue-600 hover:text-blue-700 font-medium hover:underline">
                                {!! __('click here to resend') !!}
                            </button>
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-4">
                        <x-button
                            wire:click="resendLink"
                            class="btn-primary rounded-lg px-8"
                            icon="o-arrow-path"
                            spinner="resendLink">
                            {!! __('Resend Link') !!}
                        </x-button>

                        <a href="{{ route('login') }}"
                           class="inline-block text-primary-600 hover:text-primary-700 font-medium hover:underline">
                            {!! __('Back to Login') !!}
                        </a>
                    </div>
                </div>
            @else
                <!-- Form State -->
                <x-form wire:submit="sendPasswordResetLink" class="space-y-6">
                    <!-- Instructions -->
                    <div class="text-center mb-6">
                        <p class="text-gray-600">
                            {!! __('Enter your email address and we\'ll send you a link to reset your password.') !!}
                        </p>
                    </div>

                    <!-- Email Input -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            {!! __('Email Address') !!}
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <x-icon name="o-envelope" class="w-5 h-5 text-gray-400 group-focus-within:text-primary-500" />
                            </div>
                            <x-input
                                type="email"
                                wire:model="email"
                                class="pl-10 w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                placeholder="{!! __('you@example.com') !!}"
                                required
                                autofocus />
                        </div>
                        @error('email')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Security Note -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex">
                            <x-icon name="o-shield-exclamation" class="w-5 h-5 text-yellow-600 mr-3 flex-shrink-0" />
                            <div>
                                <p class="text-sm text-yellow-800 font-medium">{!! __('Important:') !!}</p>
                                <p class="text-sm text-yellow-700 mt-1">
                                    {!! __('The reset link will expire in 60 minutes for security reasons.') !!}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <x-button
                            type="submit"
                            class="btn-primary w-full rounded-lg py-3 text-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-300"
                            spinner="sendPasswordResetLink"
                            loading-delay="200">
                            {!! __('Send Reset Link') !!}
                        </x-button>
                    </div>
                </x-form>

                <!-- Divider -->
                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500">{!! __('Remember your password?') !!}</span>
                    </div>
                </div>

                <!-- Back to Login -->
                <div class="text-center">
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center text-primary-600 hover:text-primary-700 font-medium hover:underline">
                        <x-icon name="o-arrow-left" class="w-4 h-4 mr-2" />
                        {!! __('Return to Sign In') !!}
                    </a>
                </div>
            @endif

            <!-- Additional Help -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        {!! __('Need more help?') !!}
                        <a href="#" class="text-primary-600 hover:text-primary-700 font-medium hover:underline">
                            {!! __('Contact Support') !!}
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Notice -->
    <div class="mt-6 text-center">
        <div class="inline-flex items-center text-xs text-gray-500 bg-gray-50 rounded-lg px-4 py-2">
            <x-icon name="o-lock-closed" class="w-4 h-4 text-green-500 mr-2" />
            <span>{!! __('We never share your email with third parties') !!}</span>
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

.animate-fade-in {
    animation: fade-in 0.6s ease-out;
}

.animate-bounce-in {
    animation: bounce-in 0.6s ease-out;
}

.animate-shake {
    animation: shake 0.6s ease-in-out;
}

/* Email sent animation */
.email-sent-animation {
    position: relative;
}

.email-sent-animation::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 100px;
    height: 100px;
    background: radial-gradient(circle, rgba(74, 144, 226, 0.2) 0%, rgba(74, 144, 226, 0) 70%);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    animation: pulse-ring 2s infinite;
}

@keyframes pulse-ring {
    0% {
        width: 60px;
        height: 60px;
        opacity: 0.5;
    }
    100% {
        width: 120px;
        height: 120px;
        opacity: 0;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add focus effect to email input
    const emailInput = document.querySelector('input[type="email"]');
    if (emailInput) {
        emailInput.addEventListener('focus', function() {
            this.parentElement.classList.add('ring-2', 'ring-primary-200');
        });

        emailInput.addEventListener('blur', function() {
            this.parentElement.classList.remove('ring-2', 'ring-primary-200');
        });
    }

    // Auto-focus email field
    @if(!$linkSent)
        setTimeout(() => {
            const emailField = document.querySelector('input[type="email"]');
            if (emailField) emailField.focus();
        }, 300);
    @endif
});
</script>
@endpush
