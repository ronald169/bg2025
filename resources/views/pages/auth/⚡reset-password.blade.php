<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Mary\Traits\Toast;

new
#[Title('Reset Password')]
#[Layout('layouts.guest')]
class extends Component {
    use Toast;

    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount($token): void
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    protected function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|string|email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required' => __('Please enter your email address.'),
            'email.email' => __('Please enter a valid email address.'),
            'password.required' => __('Please enter your new password.'),
            'password.min' => __('Password must be at least 8 characters.'),
            'password.confirmed' => __('The password confirmation does not match.'),
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

    public function resetPassword(): void
    {
        $this->validate();

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $this->success(__('Your password has been reset successfully!') . ' ' . __('You can now login with your new password.'));
            $this->redirectRoute('login');
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
                <h2 class="text-xl font-bold">{{ __('Reset Password') }}</h2>
                <p class="mt-1 text-sm text-white/80">{{ __('Create a new password for your account.') }}</p>
            </div>

            <!-- Card Content -->
            <div class="p-6">
                <x-form wire:submit="resetPassword" no-separator>
                    <!-- Email (hidden but needed) -->
                    <input type="hidden" wire:model="email" />
                    <input type="hidden" wire:model="token" />

                    <!-- Email (readonly for display) -->
                    <x-input
                        label="{{ __('Email Address') }} "
                        type="email"
                        wire:model="email"
                        icon="o-envelope"
                        readonly
                        disabled
                        class="bg-gray-100" />

                    <!-- New Password -->
                    <div class="mt-4">
                        <x-password
                            label="{{ __('New Password') }} "
                            wire:model="password"
                            placeholder="••••••••"
                            required
                            hint="{{ __('Minimum 8 characters') }}" />
                    </div>

                    <!-- Confirm New Password -->
                    <div class="mt-4">
                        <x-password
                            label="{{ __('Confirm New Password') }} "
                            wire:model="password_confirmation"
                            placeholder="••••••••"
                            required />
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-between pt-4 mt-2">
                        <a href="{{ route('login') }}"
                           wire:navigate
                           class="text-sm text-[#FF6B35] hover:text-[#E55A2A] hover:underline transition-colors">
                            ← {{ __('Back to login') }}
                        </a>

                        <x-button
                            type="submit"
                            label="{{ __('Reset Password →') }}"
                            class="px-6 shadow-lg btn-primary"
                            spinner="resetPassword" />
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
