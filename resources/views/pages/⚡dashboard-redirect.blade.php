<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Dashboard')]
#[Layout('layouts.app')]
class extends Component {

    public function mount()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            $this->redirectRoute('admin.dashboard');
        } elseif ($user->isTeacher()) {
            $this->redirectRoute('teacher.dashboard');
        } elseif ($user->isStudent()) {
            $this->redirectRoute('student.dashboard');
        } else {
            // Fallback
            $this->redirectRoute('login');
        }
    }

};

?>

<div class="flex items-center justify-center min-h-screen">
    <div class="text-center">
        <div class="loading loading-spinner loading-lg text-primary"></div>
        <p class="mt-4 text-base-content/70">{{ __('Redirecting to your dashboard...') }}</p>
    </div>
</div>
