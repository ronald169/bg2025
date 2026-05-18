<?php

namespace App\Livewire\Payment;

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Mary\Traits\Toast;
use Stripe\Checkout\Session;
use Stripe\Stripe;

new
#[Title('Complete Subscription')]
#[Layout('components.layouts.app')]
class extends Component {
    use Toast;

    public $plan = 'monthly';
    public $couponCode = '';
    public $discount = 0;
    public $couponApplied = false;

    public $plans = [
        'monthly' => [
            'name' => 'Monthly',
            'price' => 15,
            'interval' => 'month',
            'price_id' => 'price_monthly',
            'features' => [
                'Unlimited access to all courses',
                'Personalized learning path',
                'Download certificates',
                'Priority support',
                'Cancel anytime'
            ]
        ],
        'yearly' => [
            'name' => 'Yearly',
            'price' => 150,
            'interval' => 'year',
            'price_id' => 'price_yearly',
            'savings' => 30,
            'features' => [
                'All Monthly features',
                'Save $30 compared to monthly',
                '2 months free',
                'Exclusive webinars',
                'Annual achievement badge'
            ]
        ]
    ];

    public function mount($plan = 'monthly'): void
    {
        if (isset($this->plans[$plan])) {
            $this->plan = $plan;
        }
    }

    public function applyCoupon(): void
    {
        // Logique d'application du coupon (simplifiée pour MVP)
        if ($this->couponCode === 'WELCOME20') {
            $this->discount = $this->plans[$this->plan]['price'] * 0.2;
            $this->couponApplied = true;
            $this->success(__('Coupon applied! 20% discount'));
        } elseif ($this->couponCode === 'YEARLY10') {
            $this->discount = $this->plans[$this->plan]['price'] * 0.1;
            $this->couponApplied = true;
            $this->success(__('Coupon applied! 10% discount'));
        } else {
            $this->error(__('Invalid coupon code'));
        }
    }

    public function processSubscription(): void
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $user = auth()->user();
            $planData = $this->plans[$this->plan];
            $amount = $planData['price'] - $this->discount;

            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'BrainGenius ' . $planData['name'] . ' Subscription',
                            'description' => 'Unlimited access to all courses and premium features',
                        ],
                        'unit_amount' => $amount * 100,
                        'recurring' => [
                            'interval' => $planData['interval'],
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
                    'coupon' => $this->couponApplied ? $this->couponCode : null,
                    'discount' => $this->discount,
                ],
                'subscription_data' => [
                    'trial_period_days' => 7,
                    'metadata' => [
                        'user_id' => $user->id,
                    ],
                ],
            ]);

            $this->redirect($checkoutSession->url);

        } catch (\Exception $e) {
            $this->error(__('Payment error: ') . $e->getMessage());
        }
    }

    public function getDiscountedPrice()
    {
        return max(0, $this->plans[$this->plan]['price'] - $this->discount);
    }

    public function getSelectedPlan()
    {
        return $this->plans[$this->plan];
    }

    public function with(): array
    {
        return [
            'selectedPlan' => $this->getSelectedPlan(),
            'discountedPrice' => $this->getDiscountedPrice(),
        ];
    }
}; ?>

<div class="max-w-6xl py-8 mx-auto">
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-900">{{ __('Complete Your Subscription') }}</h1>
        <p class="mt-2 text-gray-600">{{ __('Choose your plan and start learning today') }}</p>
    </div>

    <div class="grid gap-8 lg:grid-cols-3">
        <!-- Plan Selection -->
        <div class="space-y-6 lg:col-span-2">
            <x-card title="{{ __('Choose Your Plan') }}" class="shadow-sm">
                <div class="space-y-4">
                    @foreach($plans as $key => $plan)
                    <div wire:click="$set('plan', '{{ $key }}')"
                         class="border rounded-xl p-5 cursor-pointer transition-all {{ $plan === $selectedPlan ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-primary-300 hover:bg-gray-50' }}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <input type="radio"
                                       wire:model="plan"
                                       value="{{ $key }}"
                                       class="w-5 h-5 text-primary-600 focus:ring-primary-500"
                                       {{ $plan === $key ? 'checked' : '' }}>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $plan['name'] }}</h3>
                                    <p class="text-sm text-gray-500">{{ __('Billed') }} {{ $plan['interval'] }}ly</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-primary-600">${{ number_format($plan['price'], 2) }}</div>
                                @if(isset($plan['savings']))
                                    <div class="text-xs text-green-600">{{ __('Save $:amount', ['amount' => $plan['savings']]) }}</div>
                                @endif
                            </div>
                        </div>

                        @if($plan === $key)
                        <div class="mt-4 pl-9">
                            <ul class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                @foreach($plan['features'] as $feature)
                                <li class="flex items-center text-sm text-gray-600">
                                    <x-icon name="o-check-circle" class="w-4 h-4 mr-2 text-green-500" />
                                    {{ __($feature) }}
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </x-card>

            <x-card title="{{ __('Payment Summary') }}" class="shadow-sm">
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ __('Subtotal') }}</span>
                        <span class="font-medium">${{ number_format($selectedPlan['price'], 2) }}</span>
                    </div>

                    @if($discount > 0)
                    <div class="flex justify-between text-green-600">
                        <span>{{ __('Discount') }}</span>
                        <span>-${{ number_format($discount, 2) }}</span>
                    </div>
                    @endif

                    <div class="pt-4 border-t">
                        <div class="flex justify-between text-lg font-bold">
                            <span>{{ __('Total') }}</span>
                            <span class="text-primary-600">${{ number_format($discountedPrice, 2) }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Billed') }} {{ $selectedPlan['interval'] }}ly</p>
                    </div>

                    <div class="flex pt-2 space-x-2">
                        <x-input wire:model="couponCode" placeholder="{{ __('Coupon code') }}" class="flex-1" />
                        <x-button wire:click="applyCoupon" class="btn-ghost">{{ __('Apply') }}</x-button>
                    </div>
                    @if($couponApplied)
                        <p class="text-xs text-green-600">{{ __('Coupon applied successfully!') }}</p>
                    @endif
                </div>
            </x-card>
        </div>

        <!-- Order Summary -->
        <div>
            <x-card title="{{ __('Order Summary') }}" class="sticky shadow-sm top-24">
                <div class="mb-6 text-center">
                    <div class="flex items-center justify-center w-20 h-20 mx-auto mb-4 rounded-full bg-primary-100">
                        <x-icon name="o-sparkles" class="w-10 h-10 text-primary-600" />
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">BrainGenius {{ $selectedPlan['name'] }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Unlimited access to all courses') }}</p>
                </div>

                <div class="mb-6 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ __('Plan') }}</span>
                        <span class="font-medium">{{ $selectedPlan['name'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ __('Billing cycle') }}</span>
                        <span class="font-medium">{{ ucfirst($selectedPlan['interval']) }}ly</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ __('Free trial') }}</span>
                        <span class="font-medium text-green-600">{{ __('7 days') }}</span>
                    </div>
                    @if($discount > 0)
                    <div class="flex justify-between text-sm text-green-600">
                        <span>{{ __('Discount') }}</span>
                        <span>-${{ number_format($discount, 2) }}</span>
                    </div>
                    @endif
                </div>

                <div class="pt-4 border-t">
                    <div class="flex justify-between mb-4 text-lg font-bold">
                        <span>{{ __('Total due today') }}</span>
                        <span class="text-primary-600">${{ number_format($discountedPrice, 2) }}</span>
                    </div>
                    <p class="mb-4 text-xs text-center text-gray-500">
                        {{ __('Your first payment will be charged after the 7-day free trial.') }}
                    </p>

                    <x-button wire:click="processSubscription" class="w-full py-3 text-lg btn-primary" spinner="processSubscription">
                        <x-icon name="o-lock-closed" class="w-5 h-5 mr-2" />
                        {{ __('Start 7-Day Free Trial') }}
                    </x-button>

                    <p class="mt-3 text-xs text-center text-gray-400">
                        {{ __('Secure payment powered by Stripe. Cancel anytime.') }}
                    </p>
                </div>
            </x-card>

            <div class="mt-4 text-xs text-center text-gray-400">
                <p>{{ __('By subscribing, you agree to our') }}</p>
                <a href="#" class="text-primary-600 hover:underline">{{ __('Terms of Service') }}</a>
                {{ __('and') }}
                <a href="#" class="text-primary-600 hover:underline">{{ __('Privacy Policy') }}</a>
            </div>
        </div>
    </div>

    <div class="p-4 mt-8 text-sm text-blue-800 border border-blue-200 rounded-lg bg-blue-50">
        <div class="flex items-start space-x-3">
            <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
            <div>
                <p class="font-medium">{{ __('7-Day Free Trial') }}</p>
                <p>{{ __('You won\'t be charged until after your free trial ends. Cancel anytime before the trial ends and you won\'t be charged.') }}</p>
            </div>
        </div>
    </div>
</div>
