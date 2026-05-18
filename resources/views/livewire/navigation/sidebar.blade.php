<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component {

    public function logout(): void
    {
        Auth::guard('web')->logout();
        Session::invalidate();
        Session::regenerateToken();

        $this->redirect('/');
    }
}; ?>

<div>
    <x-menu activate-by-route>

        {{-- User --}}
        @if($user = auth()->user())
            <x-menu-separator />

            <x-list-item :item="$user" value="name" sub-value="email" no-separator no-hover class="-mx-2 !-my-2 rounded">
                <x-slot:actions>
                    <x-button icon="o-power" class="btn-circle btn-ghost btn-xs" tooltip-left="logoff" no-wire-navigate wire:click="logout" />
                </x-slot:actions>
            </x-list-item>

            <x-menu-separator />
        @else
            <x-menu-item title="{!!__('Login')!!}" link="{{route('login')}}" />
        @endif

        <x-menu-item title="Hello" icon="o-sparkles" link="/" />

        <livewire:language-switcher />
    </x-menu>
</div>
