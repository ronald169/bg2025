<?php

use App\Models\Enrollment;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Title('Payment History')]
#[Layout('components.layouts.dashboard-student')]
class extends Component {
    use WithPagination;

    public $payments = [];

    public function mount(): void
    {
        $this->payments = Enrollment::where('user_id', auth()->id())
            ->where('paid_amount', '>', 0)
            ->with('course')
            ->latest('paid_at')
            ->paginate(10);
    }
}; ?>

<div class="space-y-8">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Payment History') }}</h1>

    @if($payments->count() > 0)
        <div class="overflow-hidden bg-white shadow-sm rounded-xl">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('Course') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('Amount') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('Invoice') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3">{{ $payment->paid_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('student.course.show', $payment->course) }}" class="text-primary-600 hover:underline">
                                    {{ $payment->course->title }}
                                </a>
                            </td>
                            <td class="px-4 py-3 font-medium">${{ number_format($payment->paid_amount, 2) }}</td>
                            <td class="px-4 py-3">
                                <x-badge value="{{ __('Paid') }}" class="text-green-700 bg-green-100" />
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('payment.invoice', $payment) }}" class="text-primary-600 hover:underline">
                                    {{ __('Download') }}
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t">
                {{ $payments->links() }}
            </div>
        </div>
    @else
        <div class="p-12 text-center bg-white shadow-sm rounded-xl">
            <x-icon name="o-document-text" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
            <h3 class="mb-2 text-xl font-semibold text-gray-900">{{ __('No payments yet') }}</h3>
            <p class="text-gray-600">{{ __('Your payment history will appear here') }}</p>
            <x-button link="{{ route('student.catalog') }}" class="mt-6 btn-primary">{{ __('Browse Courses') }}</x-button>
        </div>
    @endif
</div>
