<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Course;
use Mary\Traits\Toast;

new
#[Title('My Wishlist - German Learning')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public $wishlist = [];

    public function mount(): void
    {
        $this->loadWishlist();
    }

    public function loadWishlist(): void
    {
        $this->wishlist = auth()->user()->wishlist()->with(['subject', 'teacher'])->get()->toArray();
    }

    public function removeFromWishlist($courseId): void
    {
        auth()->user()->wishlist()->detach($courseId);
        $this->loadWishlist();
        $this->success(__('Removed from wishlist'));
    }

    public function enroll($courseId): void
    {
        $course = Course::find($courseId);
        if (!$course) {
            $this->error(__('Course not found.'));
            return;
        }

        // Check if already enrolled (optional)
        $alreadyEnrolled = auth()->user()->coursesEnrolled()->where('course_id', $courseId)->exists();
        if ($alreadyEnrolled) {
            $this->warning(__('You are already enrolled in this course.'));
            return;
        }

        auth()->user()->coursesEnrolled()->attach($courseId, ['enrolled_at' => now()]);
        $this->removeFromWishlist($courseId);
        $this->success(__('Enrolled successfully!'));
    }

    public function render()
    {
        return $this->view([
            'wishlist' => $this->wishlist,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">
        <h1 class="mb-6 text-2xl font-bold md:text-3xl">❤️ {{ __('My Wishlist') }}</h1>

        @if(count($wishlist) > 0)
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($wishlist as $course)
                    <x-card class="transition hover:shadow-md">
                        <div class="flex items-start justify-between">
                            <h3 class="font-semibold text-base-content">{{ $course['title'] }}</h3>
                            <x-button wire:click="removeFromWishlist({{ $course['id'] }})"
                                      icon="o-heart"
                                      class="text-error btn-ghost btn-sm"
                                      tooltip="{{ __('Remove') }}" />
                        </div>
                        <p class="mt-1 text-sm text-base-content/70">{{ $course['short_description'] ?? '' }}</p>
                        <div class="flex items-center justify-between mt-4">
                            <span class="text-sm text-base-content/60">{{ $course['lessons_count'] ?? 0 }} {{ __('lessons') }}</span>
                            @if(($course['price'] ?? 0) > 0)
                                <span class="font-bold text-primary">{{ number_format($course['price'], 0, ',', ' ') }} €</span>
                            @else
                                <span class="text-success">{{ __('Free') }}</span>
                            @endif
                        </div>
                        <x-button wire:click="enroll({{ $course['id'] }})"
                                  label="{{ __('Enroll Now') }}"
                                  class="w-full mt-4 btn-primary"
                                  spinner />
                    </x-card>
                @endforeach
            </div>
        @else
            <x-card class="py-12 text-center">
                <x-icon name="o-heart" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                <h3 class="mb-2 text-xl font-semibold text-base-content">{{ __('Your wishlist is empty') }}</h3>
                <p class="text-base-content/60">{{ __('Browse courses and add your favorites') }}</p>
                <x-button link="{{ route('student.catalog') }}" label="{{ __('Browse Courses') }}" class="mt-6 btn-primary" />
            </x-card>
        @endif
    </div>
</div>
