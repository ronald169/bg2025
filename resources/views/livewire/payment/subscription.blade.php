<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Mary\Traits\Toast;

new
#[Title('Subscription')]
#[Layout('components.layouts.dashboard-student')]
class extends Component {
    use Toast;

    public $subscription = null;
    public $plans = [
        ['id' => 'monthly', 'name' => 'Monthly', 'price' => 15, 'interval' => 'month'],
        ['id' => 'yearly', 'name' => 'Yearly', 'price' => 150, 'interval' => 'year', 'savings' => 30],
    ];

    public function mount(): void
    {
        $user = auth()->user();
        if ($user->subscribed('default')) {
            $this->subscription = $user->subscription('default');
        }
    }

    public function subscribe($planId): void
    {
        $plan = collect($this->plans)->firstWhere('id', $planId);

        $this->redirectRoute(
            'payment.subscription.checkout',
            ['plan' => $planId]
        );
    }

    public function cancelSubscription(): void
    {
        auth()->user()->subscription('default')->cancel();
        $this->subscription = auth()->user()->subscription('default');
        $this->success(__('Subscription cancelled. You will have access until the end of your billing period.'));
    }

    public function resumeSubscription(): void
    {
        auth()->user()->subscription('default')->resume();
        $this->subscription = auth()->user()->subscription('default');
        $this->success(__('Subscription resumed successfully!'));
    }
}; ?>

<div class="space-y-8">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Subscription') }}</h1>

    @if($subscription && $subscription->valid())
        <div class="p-6 border border-green-200 bg-green-50 rounded-xl">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-green-800">{{ __('Active Subscription') }}</h3>
                    <p class="mt-1 text-green-700">
                        {{ __('You are currently on the') }} <strong>{{ ucfirst($subscription->stripe_price) }}</strong> {{ __('plan') }}.
                    </p>
                    <p class="mt-2 text-sm text-green-600">
                        {{ __('Next billing date') }}: {{ $subscription->asStripeSubscription()->current_period_end ? \Carbon\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)->format('M d, Y') : 'N/A' }}
                    </p>
                </div>
                <div>
                    <x-button wire:click="cancelSubscription" class="text-red-600 btn-ghost">
                        {{ __('Cancel Subscription') }}
                    </x-button>
                </div>
            </div>
        </div>

    @elseif($subscription && $subscription->onGracePeriod())
        <div class="p-6 border border-yellow-200 bg-yellow-50 rounded-xl">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-yellow-800">{{ __('Subscription Cancelled') }}</h3>
                    <p class="mt-1 text-yellow-700">
                        {{ __('Your subscription will end on') }} <strong>{{ \Carbon\Carbon::createFromTimestamp($subscription->ends_at)->format('M d, Y') }}</strong>.
                    </p>
                    <p class="mt-2 text-sm text-yellow-600">
                        {{ __('You can resume your subscription before this date to continue uninterrupted.') }}
                    </p>
                </div>
                <div>
                    <x-button wire:click="resumeSubscription" class="btn-primary">
                        {{ __('Resume Subscription') }}
                    </x-button>
                </div>
            </div>
        </div>

    @else
        <div class="grid gap-8 md:grid-cols-2">
            @foreach($plans as $plan)
            <div class="p-8 text-center bg-white shadow-sm rounded-xl">
                <h3 class="text-2xl font-bold text-gray-900">{{ $plan['name'] }}</h3>
                <div class="mt-4">
                    <span class="text-4xl font-bold text-primary-600">${{ $plan['price'] }}</span>
                    <span class="text-gray-500">/{{ $plan['interval'] }}</span>
                </div>
                @if(isset($plan['savings']))
                    <p class="mt-2 text-sm text-green-600">{{ __('Save') }} ${{ $plan['savings'] }} {{ __('with yearly billing') }}</p>
                @endif
                <ul class="mt-6 space-y-3 text-left">
                    <li class="flex items-center"><x-icon name="o-check-circle" class="w-5 h-5 mr-2 text-green-500" />{{ __('Unlimited access to all courses') }}</li>
                    <li class="flex items-center"><x-icon name="o-check-circle" class="w-5 h-5 mr-2 text-green-500" />{{ __('Personalized learning path') }}</li>
                    <li class="flex items-center"><x-icon name="o-check-circle" class="w-5 h-5 mr-2 text-green-500" />{{ __('Priority support') }}</li>
                    <li class="flex items-center"><x-icon name="o-check-circle" class="w-5 h-5 mr-2 text-green-500" />{{ __('Download certificates') }}</li>
                </ul>
                <x-button wire:click="subscribe('{{ $plan['id'] }}')" class="w-full mt-8 btn-primary">
                    {{ __('Subscribe Now') }}
                </x-button>
            </div>
            @endforeach
        </div>

        <div class="p-4 text-sm text-blue-800 border border-blue-200 rounded-lg bg-blue-50">
            <p>{{ __('7-day free trial available! Cancel anytime.') }}</p>
        </div>
    @endif
</div>
