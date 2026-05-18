<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Title('Achievements')]
#[Layout('components.layouts.dashboard-student')]
class extends Component {

    public $achievements = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->achievements = [
            [
                'name' => __('First Lesson'),
                'description' => __('Complete your first lesson'),
                'icon' => 'o-rocket-launch',
                'unlocked' => $user->progress()->exists(),
                'progress' => $user->progress()->exists() ? 100 : 0,
            ],
            [
                'name' => __('7-Day Streak'),
                'description' => __('Study for 7 consecutive days'),
                'icon' => 'o-fire',
                'unlocked' => $user->learningStreak->current_streak >= 7,
                'progress' => min(100, ($user->learningStreak->current_streak / 7) * 100),
            ],
            [
                'name' => __('Course Master'),
                'description' => __('Complete an entire course'),
                'icon' => 'o-trophy',
                'unlocked' => false,
                'progress' => 0,
            ],
            [
                'name' => __('Quiz Champion'),
                'description' => __('Score 100% on a quiz'),
                'icon' => 'o-star',
                'unlocked' => false,
                'progress' => 0,
            ],
            [
                'name' => __('Early Bird'),
                'description' => __('Study before 8 AM for 5 days'),
                'icon' => 'o-sun',
                'unlocked' => false,
                'progress' => 0,
            ],
        ];
    }
}; ?>

<div class="space-y-8">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Achievements') }}</h1>

    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @foreach($achievements as $achievement)
        <div class="bg-white rounded-xl shadow-sm p-6 {{ $achievement['unlocked'] ? 'border-l-4 border-yellow-500' : '' }}">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl {{ $achievement['unlocked'] ? 'bg-yellow-100' : 'bg-gray-100' }} flex items-center justify-center">
                    <x-icon :name="$achievement['icon']" class="w-6 h-6 {{ $achievement['unlocked'] ? 'text-yellow-600' : 'text-gray-400' }}" />
                </div>
                @if($achievement['unlocked'])
                    <x-badge value="{{ __('Unlocked') }}" class="text-green-700 bg-green-100" />
                @endif
            </div>
            <h3 class="font-semibold text-gray-900">{{ $achievement['name'] }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $achievement['description'] }}</p>
            @if(!$achievement['unlocked'])
                <div class="mt-4">
                    <div class="w-full h-2 bg-gray-200 rounded-full">
                        <div class="h-2 rounded-full bg-primary-500" style="width: {{ $achievement['progress'] }}%"></div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ round($achievement['progress']) }}% complete</p>
                </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
