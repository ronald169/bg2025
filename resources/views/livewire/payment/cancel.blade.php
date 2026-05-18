<?php

use App\Models\Course;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Title('Payment Cancelled')]
#[Layout('components.layouts.app')]
class extends Component {

    public Course $course;

    public function mount(Course $course): void
    {
        $this->course = $course;
    }
}; ?>

<div class="max-w-2xl mx-auto text-center">
    <div class="p-12 bg-white shadow-sm rounded-xl">
        <div class="flex items-center justify-center w-20 h-20 mx-auto mb-6 bg-gray-100 rounded-full">
            <x-icon name="o-x-mark" class="w-10 h-10 text-gray-500" />
        </div>
        <h1 class="mb-4 text-2xl font-bold text-gray-900">{{ __('Payment Cancelled') }}</h1>
        <p class="mb-6 text-gray-600">
            {{ __('Your payment was cancelled. No charges were made.') }}
        </p>

        <div class="flex justify-center space-x-4">
            <x-button link="{{ route('payment.checkout', $course) }}" class="btn-primary">
                {{ __('Try Again') }}
            </x-button>
            <x-button link="{{ route('student.catalog') }}" class="btn-ghost">
                {{ __('Browse Other Courses') }}
            </x-button>
        </div>

        <div class="mt-6 text-sm text-gray-500">
            <p>{{ __('Need help?') }} <a href="#" class="text-primary-600 hover:underline">{{ __('Contact Support') }}</a></p>
        </div>
    </div>
</div>
