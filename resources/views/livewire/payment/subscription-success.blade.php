<?php

namespace App\Livewire\Payment;

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Stripe\Checkout\Session;
use Stripe\Stripe;

new
#[Title('Subscription Successful')]
#[Layout('components.layouts.app')]
class extends Component {

    public $sessionId;
    public $status = 'processing';
    public $plan = '';

    public function mount(): void
    {
        $this->sessionId = request()->get('session_id');

        if ($this->sessionId) {
            $this->verifySubscription();
        }
    }

    public function verifySubscription(): void
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = Session::retrieve($this->sessionId);

            if ($session->payment_status === 'paid') {
                $this->status = 'success';
                $this->plan = $session->metadata->plan ?? 'monthly';

                // Mettre à jour l'utilisateur
                $user = auth()->user();
                $user->update([
                    'trial_ends_at' => now()->addDays(7),
                ]);
            } else {
                $this->status = 'failed';
            }
        } catch (\Exception $e) {
            $this->status = 'error';
        }
    }
}; ?>

<div class="max-w-2xl mx-auto text-center">
    @if($status === 'success')
        <div class="p-12 bg-white shadow-sm rounded-xl">
            <div class="flex items-center justify-center w-20 h-20 mx-auto mb-6 bg-green-100 rounded-full">
                <x-icon name="o-check" class="w-10 h-10 text-green-600" />
            </div>
            <h1 class="mb-4 text-2xl font-bold text-gray-900">{{ __('Subscription Activated!') }}</h1>
            <p class="mb-6 text-gray-600">
                {{ __('Thank you for subscribing to BrainGenius! You now have unlimited access to all courses.') }}
            </p>
            <div class="p-6 mb-8 rounded-lg bg-gray-50">
                <p class="text-gray-600">{{ __('Your subscription includes:') }}</p>
                <ul class="mt-3 space-y-2 text-left">
                    <li class="flex items-center"><x-icon name="o-check-circle" class="w-5 h-5 mr-2 text-green-500" />{{ __('Unlimited access to all courses') }}</li>
                    <li class="flex items-center"><x-icon name="o-check-circle" class="w-5 h-5 mr-2 text-green-500" />{{ __('Downloadable certificates') }}</li>
                    <li class="flex items-center"><x-icon name="o-check-circle" class="w-5 h-5 mr-2 text-green-500" />{{ __('Priority support') }}</li>
                </ul>
            </div>
            <div class="flex justify-center space-x-4">
                <x-button link="{{ route('student.dashboard') }}" class="btn-primary">
                    {{ __('Go to Dashboard') }}
                </x-button>
                <x-button link="{{ route('student.catalog') }}" class="btn-ghost">
                    {{ __('Browse Courses') }}
                </x-button>
            </div>
        </div>

    @elseif($status === 'failed')
        <div class="p-12 bg-white shadow-sm rounded-xl">
            <div class="flex items-center justify-center w-20 h-20 mx-auto mb-6 bg-red-100 rounded-full">
                <x-icon name="o-x-mark" class="w-10 h-10 text-red-600" />
            </div>
            <h1 class="mb-4 text-2xl font-bold text-gray-900">{{ __('Subscription Failed') }}</h1>
            <p class="mb-6 text-gray-600">
                {{ __('There was an issue processing your subscription. Please try again.') }}
            </p>
            <x-button link="{{ route('payment.subscription') }}" class="btn-primary">
                {{ __('Try Again') }}
            </x-button>
        </div>

    @else
        <div class="p-12 bg-white shadow-sm rounded-xl">
            <div class="w-12 h-12 mx-auto mb-6 border-b-2 rounded-full animate-spin border-primary-600"></div>
            <h1 class="mb-4 text-2xl font-bold text-gray-900">{{ __('Processing...') }}</h1>
            <p class="text-gray-600">{{ __('Please wait while we confirm your subscription.') }}</p>
        </div>
    @endif
</div>
