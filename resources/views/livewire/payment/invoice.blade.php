<?php

namespace App\Livewire\Payment;

use App\Models\Enrollment;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Barryvdh\DomPDF\Facade\Pdf;

new
#[Title('Invoice')]
#[Layout('layouts.app')]
class extends Component {

    public Enrollment $enrollment;

    public function mount(Enrollment $enrollment): void
    {
        $this->enrollment = $enrollment->load(['user', 'course']);

        if ($this->enrollment->user_id !== auth()->id()) {
            abort(403);
        }
    }

    public function download(): void
    {
        $pdf = PDF::loadView('pdf.invoice', [
            'enrollment' => $this->enrollment,
        ]);

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'invoice-' . $this->enrollment->id . '.pdf'
        );
    }
}; ?>

<div class="max-w-4xl mx-auto">
    <div class="overflow-hidden bg-white shadow-sm rounded-xl">
        <!-- Header -->
        <div class="p-8 text-white bg-gradient-to-r from-primary-500 to-primary-600">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-3xl font-bold">{{ __('INVOICE') }}</h1>
                    <p class="mt-1 text-primary-100">#{{ str_pad($enrollment->id, 6, '0', STR_PAD_LEFT) }}</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold">BrainGenius</div>
                    <p class="text-sm text-primary-100">123 Learning Street<br>Paris, France</p>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Bill To -->
            <div class="mb-8">
                <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('Bill To') }}</h3>
                <div class="p-4 rounded-lg bg-gray-50">
                    <p class="font-medium">{{ $enrollment->user->name }}</p>
                    <p class="text-gray-600">{{ $enrollment->user->email }}</p>
                    <p class="text-gray-600">{{ $enrollment->user->phone ?? __('No phone') }}</p>
                </div>
            </div>

            <!-- Invoice Details -->
            <div class="mb-8">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">{{ __('Invoice Details') }}</h3>
                <div class="overflow-hidden border rounded-lg">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            32
                                <th class="px-4 py-3 text-left">{{ __('Description') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t">
                                <td class="px-4 py-3">
                                    <p class="font-medium">{{ $enrollment->course->title }}</p>
                                    <p class="text-sm text-gray-500">{{ __('Course enrollment') }}</p>
                                </td>
                                <td class="px-4 py-3 font-medium text-right">
                                    ${{ number_format($enrollment->paid_amount, 2) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr class="border-t">
                                <td class="px-4 py-3 font-bold text-right">{{ __('Total') }}</td>
                                <td class="px-4 py-3 font-bold text-right">
                                    ${{ number_format($enrollment->paid_amount, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="grid gap-6 mb-8 md:grid-cols-2">
                <div>
                    <h3 class="mb-2 text-sm font-semibold text-gray-500">{{ __('Payment Date') }}</h3>
                    <p class="text-gray-900">{{ $enrollment->paid_at?->format('F d, Y') ?? __('Pending') }}</p>
                </div>
                <div>
                    <h3 class="mb-2 text-sm font-semibold text-gray-500">{{ __('Payment Method') }}</h3>
                    <p class="text-gray-900">{{ __('Credit Card') }}</p>
                </div>
            </div>

            <!-- Thank You -->
            <div class="pt-8 text-center border-t">
                <p class="text-gray-600">{{ __('Thank you for choosing BrainGenius!') }}</p>
                <p class="mt-2 text-sm text-gray-500">{{ __('This invoice was generated automatically.') }}</p>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end p-8 space-x-3 border-t bg-gray-50">
            <x-button wire:click="download" icon="o-arrow-down-tray" class="btn-primary">
                {{ __('Download PDF') }}
            </x-button>
            <x-button link="{{ route('payment.history') }}" class="btn-ghost">
                {{ __('Back to History') }}
            </x-button>
        </div>
    </div>
</div>
