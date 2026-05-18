<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new
#[Layout('components.layouts.guest')]
class extends Component {

    public function mount(): void
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isTeacher()) {
            return redirect()->route('teacher.dashboard');
        } else {
            return redirect()->route('student.dashboard');
        }
    }
}; ?>

<div class="flex items-center justify-center min-h-screen">
    <div class="text-center">
        <div class="w-12 h-12 mx-auto border-b-2 rounded-full animate-spin border-primary-600"></div>
        <p class="mt-4 text-gray-600">{{ __('Redirecting to your dashboard...') }}</p>
    </div>
</div>
