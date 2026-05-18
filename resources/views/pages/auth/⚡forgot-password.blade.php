<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Password;
use Mary\Traits\Toast;

new
#[Title('Forgot Password')]
#[Layout('layouts.guest')]
class extends Component {
    use Toast;

    public string $email = '';

    protected function rules(): array
    {
        return [
            'email' => 'required|string|email|exists:users,email',
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required' => __('Please enter your email address.'),
            'email.email' => __('Please enter a valid email address.'),
            'email.exists' => __("We couldn't find a user with that email address."),
        ];
    }

    public function getMotivationalQuoteProperty(): string
    {
        $quotes = [
            __('Language is the key to the world. — Wilhelm von Humboldt'),
            __('Practice makes perfect. — German proverb'),
            __('The journey is the destination. — Confucius'),
        ];
        return $quotes[array_rand($quotes)];
    }

    public function sendResetLink(): void
    {
        $this->validate();

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->success(__('Reset link sent!') . ' ' . __('We have emailed your password reset link.'));

            // Optionnel : rediriger vers login après quelques secondes
            // $this->redirectRoute('login');
        } else {
            $this->error(__($status));
        }
    }

    public function render()
    {
        return $this->view([
            'motivationalQuote' => $this->motivationalQuote,
        ]);
    }
};

?>

<div>
    <div class="w-full max-w-md py-8 mx-auto">
        <!-- Header Mobile -->
        <div class="mb-8 text-center md:hidden">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-[#FF6B35] to-[#1E6091] rounded-2xl mb-4 shadow-lg">
                <x-icon name="o-academic-cap" class="w-8 h-8 text-white" />
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('AllemandExpress') }}</h1>
            <p class="mt-2 text-gray-600">{{ $motivationalQuote }}</p>
        </div>

        <!-- Card Container -->
        <x-card class="overflow-hidden border-0 shadow-xl">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-[#FF6B35] to-[#1E6091] p-6 text-center text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 opacity-10 text-8xl">🔐</div>
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-3 bg-white/20 backdrop-blur-sm rounded-xl">
                    <x-icon name="o-key" class="w-6 h-6" />
                </div>
                <h2 class="text-xl font-bold">{{ __('Forgot Password') }}</h2>
                <p class="mt-1 text-sm text-white/80">{{ __('Reset your password') }}</p>
            </div>

            <!-- Card Content -->
            <div class="p-6">
                @if (session('status'))
                    <x-alert icon="o-check-circle" class="mb-6 alert-success" dismissible>
                        {{ session('status') }}
                    </x-alert>
                @endif

                <p class="mb-6 text-sm text-gray-600">
                    {{ __("Enter your email address and we'll send you a link to reset your password.") }}
                </p>

                <x-form wire:submit="sendResetLink" no-separator>
                    <!-- Email -->
                    <x-input
                        label="{{ __('Email Address') }} "
                        type="email"
                        wire:model="email"
                        icon="o-envelope"
                        placeholder="max@example.com"
                        required
                        autofocus />

                    <!-- Submit Button -->
                    <div class="flex items-center justify-between pt-4 mt-2">
                        <a href="{{ route('login') }}"
                           wire:navigate
                           class="text-sm text-[#FF6B35] hover:text-[#E55A2A] hover:underline transition-colors">
                            ← {{ __('Back to login') }}
                        </a>

                        <x-button
                            type="submit"
                            label="{{ __('Send reset link') }} →"
                            class="px-6 shadow-lg btn-primary"
                            spinner="sendResetLink" />
                    </div>
                </x-form>
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
