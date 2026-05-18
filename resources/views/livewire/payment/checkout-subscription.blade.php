<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Mary\Traits\Toast;
use Stripe\Checkout\Session;
use Stripe\Stripe;

new
#[Title('Subscribe')]
#[Layout('components.layouts.app')]
class extends Component {
    use Toast;

    public $plan = 'monthly';
    public $plans = [
        'monthly' => ['name' => 'Monthly', 'price' => 15, 'price_id' => 'price_monthly'],
        'yearly' => ['name' => 'Yearly', 'price' => 150, 'price_id' => 'price_yearly'],
    ];

    public function mount($plan = 'monthly'): void
    {
        $this->plan = $plan;
    }

    public function processSubscription(): void
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $user = auth()->user();
            $planData = $this->plans[$this->plan];

            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'BrainGenius ' . $planData['name'] . ' Subscription',
                            'description' => 'Unlimited access to all courses',
                        ],
                        'unit_amount' => $planData['price'] * 100,
                        'recurring' => [
                            'interval' => $this->plan === 'monthly' ? 'month' : 'year',
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => route('payment.subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.subscription.cancel'),
                'customer_email' => $user->email,
                'metadata' => [
                    'user_id' => $user->id,
                    'plan' => $this->plan,
                ],
            ]);

            $this->redirect($checkoutSession->url);

        } catch (\Exception $e) {
            $this->error(__('Payment error: ') . $e->getMessage());
        }
    }
}; ?>

<div class="max-w-2xl mx-auto">
    <x-card title="{{ __('Complete Your Subscription') }}" class="shadow-sm">
        <div class="space-y-6">
            <div class="p-4 rounded-lg bg-gray-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">BrainGenius {{ ucfirst($this->plan) }} Plan</h3>
                        <p class="text-sm text-gray-600">{{ __('Unlimited access to all courses') }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-primary-600">${{ $this->plans[$this->plan]['price'] }}</div>
                        <div class="text-xs text-gray-500">/{{ $this->plan === 'monthly' ? 'month' : 'year' }}</div>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <h4 class="font-medium text-gray-900">{{ __('What\'s included:') }}</h4>
                <ul class="space-y-2">
                    <li class="flex items-center"><x-icon name="o-check-circle" class="w-5 h-5 mr-2 text-green-500" />{{ __('All courses unlimited access') }}</li>
                    <li class="flex items-center"><x-icon name="o-check-circle" class="w-5 h-5 mr-2 text-green-500" />{{ __('Downloadable certificates') }}</li>
                    <li class="flex items-center"><x-icon name="o-check-circle" class="w-5 h-5 mr-2 text-green-500" />{{ __('Priority email support') }}</li>
                    <li class="flex items-center"><x-icon name="o-check-circle" class="w-5 h-5 mr-2 text-green-500" />{{ __('Personalized learning path') }}</li>
                </ul>
            </div>

            <div class="pt-6 border-t">
                <x-button wire:click="processSubscription" class="w-full py-3 text-lg btn-primary" spinner="processSubscription">
                    {{ __('Subscribe Now') }}
                </x-button>
                <p class="mt-3 text-xs text-center text-gray-500">
                    {{ __('7-day free trial included. Cancel anytime.') }}
                </p>
            </div>
        </div>
    </x-card>
</div>
