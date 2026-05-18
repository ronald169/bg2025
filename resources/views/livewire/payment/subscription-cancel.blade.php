<?php

namespace App\Livewire\Payment;

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Title('Subscription Cancelled')]
#[Layout('components.layouts.app')]
class extends Component {
}; ?>

<div class="max-w-2xl mx-auto text-center">
    <div class="p-12 bg-white shadow-sm rounded-xl">
        <div class="flex items-center justify-center w-20 h-20 mx-auto mb-6 bg-gray-100 rounded-full">
            <x-icon name="o-x-mark" class="w-10 h-10 text-gray-500" />
        </div>
        <h1 class="mb-4 text-2xl font-bold text-gray-900">{{ __('Subscription Cancelled') }}</h1>
        <p class="mb-6 text-gray-600">
            {{ __('Your subscription was cancelled. No charges were made.') }}
        </p>
        <div class="flex justify-center space-x-4">
            <x-button link="{{ route('payment.subscription') }}" class="btn-primary">
                {{ __('Try Again') }}
            </x-button>
            <x-button link="{{ route('student.dashboard') }}" class="btn-ghost">
                {{ __('Go to Dashboard') }}
            </x-button>
        </div>
    </div>
</div>
