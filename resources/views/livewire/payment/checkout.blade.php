<?php

use App\Models\Course;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Http;

new
#[Title('Checkout')]
#[Layout('components.layouts.app')]
class extends Component {
    use Toast;

    public Course $course;
    public $paymentMethod = 'card';
    public $couponCode = '';
    public $discount = 0;
    public $couponApplied = false;
    public $isEnrolled = false;

    public function mount(Course $course): void
    {
        $this->course = $course;
        $this->isEnrolled = auth()->user()->coursesEnrolled()->where('course_id', $course->id)->exists();

        if ($this->isEnrolled) {
            $this->redirect(route('student.course.show', $course), navigate: true);
        }
    }

    public function applyCoupon(): void
    {
        if ($this->couponCode === 'WELCOME20') {
            $this->discount = $this->course->current_price * 0.2;
            $this->couponApplied = true;
            $this->success(__('Coupon applied! 20% discount'));
        } else {
            $this->error(__('Invalid coupon code'));
        }
    }

    public function processPayment(): void
    {
        try {
            $amount = $this->course->current_price - $this->discount;

            if ($amount <= 0) {
                // Cours gratuit
                auth()->user()->coursesEnrolled()->attach($this->course->id, [
                    'enrolled_at' => now(),
                    'status' => 'active',
                    'paid_amount' => 0,
                ]);
                $this->success(__('Successfully enrolled!'));
                $this->redirect(route('student.course.show', $this->course), navigate: true);
                return;
            }

            // Utiliser Laravel HTTP Client au lieu de Stripe SDK
            $response = Http::withBasicAuth(config('services.stripe.secret'), '')
                ->asForm()
                ->post('https://api.stripe.com/v1/checkout/sessions', [
                    'payment_method_types' => ['card'],
                    'line_items[0][price_data][currency]' => 'usd',
                    'line_items[0][price_data][product_data][name]' => $this->course->title,
                    'line_items[0][price_data][product_data][description]' => $this->course->short_description ?? '',
                    'line_items[0][price_data][unit_amount]' => $amount * 100,
                    'line_items[0][quantity]' => 1,
                    'mode' => 'payment',
                    'success_url' => route('payment.success', ['course' => $this->course->id]) . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('payment.cancel', ['course' => $this->course->id]),
                    'metadata[course_id]' => $this->course->id,
                    'metadata[user_id]' => auth()->id(),
                ]);

            if ($response->successful()) {
                $sessionId = $response->json('id');
                $sessionUrl = $response->json('url');

                $this->redirect($sessionUrl);
            } else {
                $error = $response->json('error.message') ?? 'Unknown error';
                throw new \Exception($error);
            }

        } catch (\Exception $e) {
            $this->error(__('Payment error: ') . $e->getMessage());
            \Log::error('Stripe payment error: ' . $e->getMessage());
        }
    }

    public function getDiscountedPrice()
    {
        return max(0, $this->course->current_price - $this->discount);
    }
}; ?>

<div class="max-w-4xl mx-auto">
    <div class="grid gap-8 md:grid-cols-3">
        <!-- Course Summary -->
        <div class="md:col-span-2">
            <x-card title="{{ __('Order Summary') }}" class="shadow-sm">
                <div class="flex items-start space-x-4">
                    <div class="flex items-center justify-center w-20 h-20 rounded-lg bg-primary-100">
                        <x-icon name="o-academic-cap" class="w-10 h-10 text-primary-600" />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900">{{ $course->title }}</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ $course->short_description }}</p>
                        <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                            <span>{{ $course->lessons_count }} lessons</span>
                            <span>{{ $course->estimated_duration }} min</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-gray-900">${{ number_format($course->current_price, 2) }}</div>
                        @if($course->sale_price)
                            <div class="text-sm text-gray-400 line-through">${{ number_format($course->price, 2) }}</div>
                        @endif
                    </div>
                </div>
            </x-card>

            <x-card title="{{ __('Payment Method') }}" class="mt-6 shadow-sm">
                <div class="space-y-4">
                    <div class="flex items-center p-4 space-x-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="radio" wire:model="paymentMethod" value="card" class="text-primary-600">
                        <div class="flex items-center space-x-2">
                            <x-icon name="o-credit-card" class="w-6 h-6" />
                            <span>{{ __('Credit / Debit Card') }}</span>
                        </div>
                    </div>

                    <div class="flex items-center p-4 space-x-3 border rounded-lg opacity-50">
                        <input type="radio" disabled class="text-gray-400">
                        <div class="flex items-center space-x-2">
                            <x-icon name="o-user" class="w-6 h-6" />
                            <span>{{ __('PayPal') }} ({{ __('Coming soon') }})</span>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Payment Sidebar -->
        <div>
            <x-card title="{{ __('Total') }}" class="sticky shadow-sm top-24">
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ __('Subtotal') }}</span>
                        <span>${{ number_format($course->current_price, 2) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="text-gray-600">{{ __('Discount') }}</span>
                            @if($discount > 0)
                                <x-badge value="20% OFF" class="text-green-700 bg-green-100" />
                            @endif
                        </div>
                        <span class="text-red-600">-${{ number_format($discount, 2) }}</span>
                    </div>

                    <div class="pt-4 border-t">
                        <div class="flex justify-between text-lg font-bold">
                            <span>{{ __('Total') }}</span>
                            <span class="text-primary-600">${{ number_format($this->getDiscountedPrice(), 2) }}</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex space-x-2">
                            <x-input wire:model="couponCode" placeholder="{{ __('Coupon code') }}" class="flex-1" />
                            <x-button wire:click="applyCoupon" class="btn-ghost">{{ __('Apply') }}</x-button>
                        </div>
                        @if($couponApplied)
                            <p class="text-xs text-green-600">{{ __('Coupon applied successfully!') }}</p>
                        @endif
                    </div>

                    <x-button wire:click="processPayment" class="w-full py-3 btn-primary" spinner="processPayment">
                        {{ __('Pay Now') }}
                    </x-button>

                    <p class="text-xs text-center text-gray-500">
                        {{ __('Secure payment powered by Stripe. Your payment information is encrypted.') }}
                    </p>
                </div>
            </x-card>

            <div class="mt-4 text-xs text-center text-gray-400">
                <p>{{ __('By completing this purchase, you agree to our') }}</p>
                <a href="#" class="text-primary-600">{{ __('Terms of Service') }}</a>
                {{ __('and') }}
                <a href="#" class="text-primary-600">{{ __('Privacy Policy') }}</a>
            </div>
        </div>
    </div>
</div>
