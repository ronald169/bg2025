<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Progress;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Title('Student Progress')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {

    public User $user;
    public $selectedCourse = null;
    public $courses = [];
    public $progressData = [];

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->courses = $this->user->enrollments()->with('course')->get()->pluck('course');
        if ($this->courses->isNotEmpty()) {
            $this->selectedCourse = $this->courses->first()->id;
            $this->loadProgress();
        }
    }

    public function loadProgress(): void
    {
        $course = Course::find($this->selectedCourse);
        if ($course) {
            $this->progressData = $course->lessons()
                ->get()
                ->map(function ($lesson) {
                    $progress = Progress::where('user_id', $this->user->id)
                        ->where('lesson_id', $lesson->id)
                        ->first();
                    return [
                        'lesson' => $lesson,
                        'completed' => $progress?->is_completed ?? false,
                        'time_spent' => $progress?->time_spent ?? 0,
                        'last_accessed' => $progress?->last_accessed,
                    ];
                });
        }
    }
}; ?>

<div class="space-y-8">
    <div>
        <a href="{{ route('teacher.students.show', $this->user) }}" class="inline-block mb-2 text-primary-600 hover:text-primary-700">
            ← {{ __('Back to student') }}
        </a>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Progress') }}: {{ $user->name }}</h1>
    </div>

    @if($courses->isNotEmpty())
        <div class="p-4 bg-white shadow-sm rounded-xl">
            <label class="block mb-2 text-sm font-medium text-gray-700">{{ __('Select Course') }}</label>
            <select wire:model.live="selectedCourse" class="w-full border-gray-300 rounded-lg md:w-64">
                @foreach($courses as $course)
                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                @endforeach
            </select>
        </div>

        @if($progressData->isNotEmpty())
            <div class="overflow-hidden bg-white shadow-sm rounded-xl">
                <div class="divide-y divide-gray-100">
                    @foreach($progressData as $item)
                    <div class="p-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    @if($item['completed'])
                                        <x-icon name="o-check-circle" class="w-5 h-5 text-green-500" />
                                    @else
                                        <x-icon name="o-play-circle" class="w-5 h-5 text-gray-400" />
                                    @endif
                                    <span class="font-medium text-gray-900">{{ $item['lesson']->title }}</span>
                                </div>
                                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                                    @if($item['time_spent'] > 0)
                                        <span>{{ __('Time spent') }}: {{ floor($item['time_spent'] / 60) }} min</span>
                                    @endif
                                    @if($item['last_accessed'])
                                        <span>{{ __('Last accessed') }}: {{ $item['last_accessed']->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="p-12 text-center bg-white shadow-sm rounded-xl">
            <p class="text-gray-500">{{ __('No courses enrolled') }}</p>
        </div>
    @endif
</div>
