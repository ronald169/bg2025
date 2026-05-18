<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\User;
use Mary\Traits\Toast;

new
#[Title('My Profile')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public User $user;

    public function mount(): void
    {
        $this->user = auth()->user();
    }

    public function render()
    {
        return $this->view();
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-4xl px-3 mx-auto md:px-4">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold md:text-3xl">👤 {{ __('My Profile') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ __('View your personal information') }}</p>
        </div>

        {{-- Profile Card --}}
        <x-card class="shadow-sm">
            <div class="flex flex-col items-center gap-6 md:flex-row md:items-start">
                {{-- Avatar --}}
                <div class="avatar">
                    <div class="flex items-center justify-center w-32 h-32 overflow-hidden rounded-full bg-primary/20">
                        @if($user->avatar)
                            <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="object-cover w-full h-full">
                        @else
                            <span class="text-5xl font-bold text-primary">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        @endif
                    </div>
                </div>

                {{-- Info --}}
                <div class="flex-1 space-y-3">
                    <div>
                        <h2 class="text-2xl font-bold">{{ $user->name }}</h2>
                        <p class="text-base-content/70">{{ $user->email }}</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-badge :value="ucfirst($user->role)" :class="match($user->role) {
                            'admin' => 'badge-error',
                            'teacher' => 'badge-info',
                            'student' => 'badge-success',
                            default => 'badge-ghost',
                        } . ' badge-lg'" />
                        @if($user->status === 'active')
                            <x-badge value="{{ __('Active') }}" class="badge-success badge-lg" />
                        @else
                            <x-badge value="{{ __('Inactive') }}" class="badge-neutral badge-lg" />
                        @endif
                    </div>

                    @if($user->bio)
                        <div class="mt-4">
                            <h3 class="text-sm font-semibold text-base-content/70">{{ __('Bio') }}</h3>
                            <p class="mt-1">{{ $user->bio }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </x-card>

        {{-- Details Grid --}}
        <div class="grid gap-6 mt-6 md:grid-cols-2">
            {{-- Personal Information --}}
            <x-card title="{{ __('Personal Information') }}" icon="o-user" class="shadow-sm">
                <div class="space-y-3">
                    <div class="flex justify-between pb-2 border-b">
                        <span class="font-medium">{{ __('Full name') }}:</span>
                        <span>{{ $user->name }}</span>
                    </div>
                    <div class="flex justify-between pb-2 border-b">
                        <span class="font-medium">{{ __('Email') }}:</span>
                        <span>{{ $user->email }}</span>
                    </div>
                    @if($user->phone)
                        <div class="flex justify-between pb-2 border-b">
                            <span class="font-medium">{{ __('Phone') }}:</span>
                            <span>{{ $user->phone }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between pb-2 border-b">
                        <span class="font-medium">{{ __('Member since') }}:</span>
                        <span>{{ $user->created_at->format('d.m.Y') }}</span>
                    </div>
                </div>
            </x-card>

            {{-- Professional Information (for teachers) --}}
            @if($user->role === 'teacher')
                <x-card title="{{ __('Professional Information') }}" icon="o-briefcase" class="shadow-sm">
                    <div class="space-y-3">
                        <div class="flex justify-between pb-2 border-b">
                            <span class="font-medium">{{ __('German level') }}:</span>
                            <span>{{ $user->german_level ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between pb-2 border-b">
                            <span class="font-medium">{{ __('Courses taught') }}:</span>
                            <span>{{ $user->coursesTaught()->count() }}</span>
                        </div>
                        <div class="flex justify-between pb-2 border-b">
                            <span class="font-medium">{{ __('Total students') }}:</span>
                            <span>{{ $user->coursesTaught()->withCount('enrollments')->get()->sum('enrollments_count') }}</span>
                        </div>
                    </div>
                </x-card>
            @endif

            {{-- Student Information --}}
            @if($user->role === 'student')
                <x-card title="{{ __('Learning Information') }}" icon="o-academic-cap" class="shadow-sm">
                    <div class="space-y-3">
                        <div class="flex justify-between pb-2 border-b">
                            <span class="font-medium">{{ __('German level') }}:</span>
                            <span>{{ $user->german_level ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between pb-2 border-b">
                            <span class="font-medium">{{ __('Courses enrolled') }}:</span>
                            <span>{{ $user->coursesEnrolled()->count() }}</span>
                        </div>
                        <div class="flex justify-between pb-2 border-b">
                            <span class="font-medium">{{ __('Completed lessons') }}:</span>
                            <span>{{ $user->progress()->where('is_completed', true)->count() }}</span>
                        </div>
                    </div>
                </x-card>
            @endif
        </div>

        {{-- Action Buttons --}}
        <div class="flex justify-end mt-6">
            <x-button label="{{ __('Edit profile') }}" icon="o-pencil" link="{{ route('profile.edit') }}" class="btn-primary" />
        </div>
    </div>
</div>
