<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Title('My Certificates')]
#[Layout('components.layouts.dashboard-student')]
class extends Component {

    public $certificates = [];

    public function mount(): void
    {
        $this->certificates = auth()->user()->coursesEnrolled()
            ->wherePivot('status', 'completed')
            ->get()
            ->map(function ($course) {
                $course->completed_at = $course->pivot->completed_at;
                return $course;
            });
    }
}; ?>

<div class="space-y-8">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('My Certificates') }}</h1>

    @if($certificates->count() > 0)
        <div class="grid gap-6 md:grid-cols-2">
            @foreach($certificates as $cert)
            <div class="overflow-hidden bg-white shadow-sm rounded-xl">
                <div class="p-8 text-center text-white bg-gradient-to-r from-primary-500 to-primary-600">
                    <x-icon name="o-document-text" class="w-12 h-12 mx-auto mb-4" />
                    <h2 class="text-xl font-bold">{{ __('Certificate of Completion') }}</h2>
                    <p class="mt-2 text-primary-100">{{ __('Awarded to') }}</p>
                    <p class="mt-1 text-2xl font-bold">{{ auth()->user()->name }}</p>
                </div>
                <div class="p-6 text-center">
                    <p class="text-gray-600">for successfully completing</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">{{ $cert->title }}</h3>
                    <p class="mt-4 text-sm text-gray-500">Completed on {{ \Carbon\Carbon::parse($cert->completed_at)->format('M d, Y') }}</p>
                    <x-button class="mt-6 btn-primary">{{ __('Download Certificate') }}</x-button>
                </div>
            </div>
            @endforeach
        </div>
    @else
        <div class="p-12 text-center bg-white shadow-sm rounded-xl">
            <x-icon name="o-document-text" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
            <h3 class="mb-2 text-xl font-semibold text-gray-900">{{ __('No certificates yet') }}</h3>
            <p class="text-gray-600">{{ __('Complete a course to earn your certificate') }}</p>
        </div>
    @endif
</div>
