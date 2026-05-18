<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Mary\Traits\Toast;

new
#[Title('My Wishlist')]
#[Layout('components.layouts.dashboard-student')]
class extends Component {
    use Toast;

    public $wishlist = [];

    public function mount(): void
    {
        $this->wishlist = auth()->user()->wishlist()->with(['subject', 'teacher'])->get();
    }

    public function removeFromWishlist($courseId): void
    {
        auth()->user()->wishlist()->detach($courseId);
        $this->wishlist = auth()->user()->wishlist()->with(['subject', 'teacher'])->get();
        $this->success(__('Removed from wishlist'));
    }

    public function enroll($courseId): void
    {
        $course = \App\Models\Course::find($courseId);
        auth()->user()->coursesEnrolled()->attach($courseId, ['enrolled_at' => now()]);
        $this->removeFromWishlist($courseId);
        $this->success(__('Enrolled successfully!'));
    }
}; ?>

<div class="space-y-8">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('My Wishlist') }}</h1>

    @if($wishlist->count() > 0)
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($wishlist as $course)
            <x-card class="transition hover:shadow-md">
                <div class="flex items-start justify-between">
                    <h3 class="font-semibold text-gray-900">{{ $course->title }}</h3>
                    <x-button wire:click="removeFromWishlist({{ $course->id }})" icon="o-heart" class="text-red-500 btn-ghost" />
                </div>
                <p class="mt-1 text-sm text-gray-600">{{ $course->short_description }}</p>
                <div class="flex items-center justify-between mt-4">
                    <span class="text-sm text-gray-500">{{ $course->lessons_count }} lessons</span>
                    @if($course->price > 0)
                        <span class="font-bold text-primary-600">${{ $course->price }}</span>
                    @else
                        <span class="text-green-600">{{ __('Free') }}</span>
                    @endif
                </div>
                <x-button wire:click="enroll({{ $course->id }})" class="w-full mt-4 btn-primary">{{ __('Enroll Now') }}</x-button>
            </x-card>
            @endforeach
        </div>
    @else
        <div class="p-12 text-center bg-white shadow-sm rounded-xl">
            <x-icon name="o-heart" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
            <h3 class="mb-2 text-xl font-semibold text-gray-900">{{ __('Your wishlist is empty') }}</h3>
            <p class="text-gray-600">{{ __('Browse courses and add your favorites') }}</p>
            <x-button link="{{ route('student.catalog') }}" class="mt-6 btn-primary">{{ __('Browse Courses') }}</x-button>
        </div>
    @endif
</div>
