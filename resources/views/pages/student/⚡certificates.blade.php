<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('My Certificates - German Learning')]
#[Layout('layouts.app')]
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

    public function render()
    {
        return $this->view([
            'certificates' => $this->certificates,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">
        <h1 class="mb-6 text-2xl font-bold md:text-3xl">🎓 {{ __('My Certificates') }}</h1>

        @if($certificates->count() > 0)
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($certificates as $cert)
                    <x-card class="overflow-hidden transition hover:shadow-md">
                        {{-- Certificate header --}}
                        <div class="p-6 text-center text-white bg-gradient-to-r from-primary to-secondary">
                            <x-icon name="o-document-text" class="w-12 h-12 mx-auto mb-3" />
                            <h2 class="text-xl font-bold">{{ __('Certificate of Completion') }}</h2>
                            <p class="mt-2 text-white/80">{{ __('Awarded to') }}</p>
                            <p class="mt-1 text-2xl font-bold">{{ auth()->user()->name }}</p>
                        </div>
                        {{-- Certificate body --}}
                        <div class="p-5 text-center">
                            <p class="text-base-content/70">{{ __('for successfully completing') }}</p>
                            <h3 class="mt-1 text-lg font-semibold text-base-content">{{ $cert->title }}</h3>
                            <p class="mt-3 text-sm text-base-content/60">
                                {{ __('Completed on') }} {{ \Carbon\Carbon::parse($cert->completed_at)->format('M d, Y') }}
                            </p>
                            <x-button label="{{ __('Download Certificate') }}" icon="o-arrow-down-tray" class="w-full mt-5 btn-primary" />
                        </div>
                    </x-card>
                @endforeach
            </div>
        @else
            <x-card class="py-12 text-center">
                <x-icon name="o-document-text" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                <h3 class="mb-2 text-xl font-semibold text-base-content">{{ __('No certificates yet') }}</h3>
                <p class="text-base-content/60">{{ __('Complete a course to earn your certificate') }}</p>
                <x-button link="{{ route('student.catalog') }}" label="{{ __('Browse Courses →') }}" class="mt-6 btn-primary" />
            </x-card>
        @endif
    </div>
</div>
