<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\User;
use App\Models\Course;
use App\Models\Progress;
use Mary\Traits\Toast;

new
#[Title('Student Progress - Teacher')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public User $user;
    public $selectedCourse = null;
    public array $courses = [];
    public array $progressData = [];

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->courses = $this->user->enrollments()->with('course')->get()->pluck('course')->toArray();
        if (!empty($this->courses)) {
            $this->selectedCourse = $this->courses[0]['id'] ?? null;
            if ($this->selectedCourse) {
                $this->loadProgress();
            }
        }
    }

    public function loadProgress(): void
    {
        $course = Course::find($this->selectedCourse);
        if ($course) {
            $this->progressData = $course->lessons()
                ->orderBy('order')
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
                })
                ->toArray();
        }
    }

    public function getFormattedTime($seconds): string
    {
        if ($seconds < 60) return "{$seconds} sec";
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return $secs > 0 ? "{$minutes} min {$secs} sec" : "{$minutes} min";
    }

    public function render()
    {
        return $this->view([
            'courses' => $this->courses,
            'progressData' => $this->progressData,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-4xl px-3 mx-auto md:px-4">

        {{-- Navigation --}}
        <div class="mb-5">
            <a href="{{ route('teacher.students.show', $user) }}" wire:navigate class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to student') }}
            </a>
        </div>

        <h1 class="mb-2 text-2xl font-bold md:text-3xl">{{ __('Progress') }}: {{ $user->name }}</h1>

        @if(!empty($courses))
            <div class="p-4 mb-6 shadow-sm bg-base-100 rounded-xl">
                <label class="block mb-2 text-sm font-medium">{{ __('Select course') }}</label>
                <x-select wire:model.live="selectedCourse" :options="collect($courses)->map(fn($c) => ['id' => $c['id'], 'name' => $c['title']])->toArray()" option-value="id" option-label="name" id="course_select" name="course_select" class="md:w-64" />
            </div>

            @if(!empty($progressData))
                <x-card class="shadow-sm">
                    <div class="divide-y divide-base-200">
                        @foreach($progressData as $item)
                            <div class="p-4 transition hover:bg-base-200">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex-1">
                                        <div class="flex flex-wrap items-center gap-3">
                                            @if($item['completed'])
                                                <x-icon name="o-check-circle" class="w-5 h-5 text-success" />
                                            @else
                                                <x-icon name="o-play-circle" class="w-5 h-5 text-base-content/40" />
                                            @endif
                                            <span class="font-medium">{{ $item['lesson']->title }}</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-base-content/60">
                                            @if($item['time_spent'] > 0)
                                                <span>{{ __('Time spent') }}: {{ $this->getFormattedTime($item['time_spent']) }}</span>
                                            @endif
                                            @if($item['last_accessed'])
                                                <span>{{ __('Last accessed') }}: {{ \Carbon\Carbon::parse($item['last_accessed'])->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @else
                <x-card class="py-12 text-center">
                    <x-icon name="o-book-open" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <p class="text-base-content/60">{{ __('No lessons found for this course') }}</p>
                </x-card>
            @endif
        @else
            <x-card class="py-12 text-center">
                <x-icon name="o-academic-cap" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                <p class="text-base-content/60">{{ __('No courses enrolled') }}</p>
            </x-card>
        @endif
    </div>
</div>
