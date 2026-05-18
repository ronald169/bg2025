<?php

namespace App\Livewire;

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public $user = null;
    public $unreadCount = 0;
    public $navigation = [];
    public $mobileMenuOpen = false;
    public $langOpen = false;
    public $userMenuOpen = false;

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->loadNavigation();
        if ($this->user) {
            $this->unreadCount = $this->user->unreadNotifications()->count();
        }
    }

    public function loadNavigation(): void
    {
        $this->navigation = [
            ['name' => __('Features'), 'route' => '#features', 'icon' => 'o-sparkles'],
            ['name' => __('Courses'), 'route' => '#courses', 'icon' => 'o-academic-cap'],
            ['name' => __('How it works'), 'route' => '#how-it-works', 'icon' => 'o-cog-6-tooth'],
            ['name' => __('Pricing'), 'route' => '#pricing', 'icon' => 'o-currency-dollar'],
            ['name' => __('Contact'), 'route' => '#contact', 'icon' => 'o-envelope'],
        ];
    }

    public function switchLanguage($locale): void
    {
        if (in_array($locale, ['en', 'fr'])) {
            session(['locale' => $locale]);
            app()->setLocale($locale);
            $this->dispatch('language-changed', locale: $locale);
        }
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(route('home'), navigate: true);
    }

    public function getLanguagesProperty()
    {
        return [
            ['code' => 'fr', 'name' => 'Français', 'flag' => '🇫🇷'],
            ['code' => 'en', 'name' => 'English', 'flag' => '🇬🇧'],
        ];
    }
}; ?>

<header class="sticky top-0 z-50 mb-16 bg-white border-b border-gray-200 shadow-sm"
        x-data="{ mobileMenuOpen: false, langOpen: false, userMenuOpen: false }">
    <div class="container px-4 mx-auto">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="/" class="flex items-center space-x-3 group">
                <div class="flex items-center justify-center w-10 h-10 transition shadow-lg bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl group-hover:shadow-xl">
                    <x-icon name="o-academic-cap" class="w-6 h-6 text-white" />
                </div>
                <span class="hidden text-xl font-bold text-gray-900 sm:block">BrainGenius</span>
            </a>

            <!-- Desktop Navigation -->
            <nav class="items-center hidden space-x-1 md:flex">
                @foreach($navigation as $item)
                    @if(str_starts_with($item['route'], '#'))
                        <a href="{{ $item['route'] }}"
                           class="px-4 py-2 font-medium text-gray-700 transition-colors rounded-lg hover:text-primary-600 hover:bg-primary-50">
                            {{ $item['name'] }}
                        </a>
                    @else
                        <a href="{{ route($item['route']) }}"
                           wire:navigate
                           class="px-4 py-2 font-medium text-gray-700 transition-colors rounded-lg hover:text-primary-600 hover:bg-primary-50">
                            {{ $item['name'] }}
                        </a>
                    @endif
                @endforeach
            </nav>

            <!-- Right Section -->
            <div class="flex items-center space-x-4">
                <!-- Language Switcher -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="flex items-center px-3 py-2 space-x-2 transition-colors rounded-lg hover:bg-gray-100">
                        @foreach($this->languages as $lang)
                            @if($lang['code'] === app()->getLocale())
                                <span class="text-lg">{{ $lang['flag'] }}</span>
                                <span class="hidden text-sm font-medium text-gray-700 sm:inline">{{ $lang['name'] }}</span>
                            @endif
                        @endforeach
                        <x-icon name="o-chevron-down" class="w-4 h-4 text-gray-500" />
                    </button>

                    <div x-show="open" @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute right-0 z-50 w-48 py-2 mt-2 bg-white border border-gray-200 shadow-xl rounded-xl">
                        @foreach($this->languages as $lang)
                        <button wire:click="switchLanguage('{{ $lang['code'] }}')"
                                @click="open = false"
                                class="w-full flex items-center space-x-3 px-4 py-3 hover:bg-gray-50 transition-colors {{ app()->getLocale() === $lang['code'] ? 'bg-primary-50 text-primary-600' : 'text-gray-700' }}">
                            <span class="text-lg">{{ $lang['flag'] }}</span>
                            <span class="flex-1 font-medium text-left">{{ $lang['name'] }}</span>
                            @if(app()->getLocale() === $lang['code'])
                                <x-icon name="o-check" class="w-5 h-5 text-primary-600" />
                            @endif
                        </button>
                        @endforeach
                    </div>
                </div>

                <!-- Notification Bell -->
                @auth
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="relative p-2 text-gray-600 transition-colors rounded-lg hover:text-primary-600 hover:bg-gray-100">
                        <x-icon name="o-bell" class="w-6 h-6" />
                        @if($unreadCount > 0)
                            <span class="absolute top-0 right-0 flex items-center justify-center w-5 h-5 text-xs text-white bg-red-500 rounded-full animate-pulse">
                                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                            </span>
                        @endif
                    </button>
                    <div x-show="open" @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 z-50 mt-2 bg-white border border-gray-200 shadow-xl w-80 rounded-xl">
                        <livewire:notification-bell />
                    </div>
                </div>
                @endauth

                <!-- User Menu / Auth Buttons -->
                @auth
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center p-2 space-x-3 transition-colors rounded-lg hover:bg-gray-100">
                            @if($user->profile_photo_path)
                                <img src="{{ Storage::url($user->profile_photo_path) }}"
                                     alt="{{ $user->name }}"
                                     class="object-cover w-8 h-8 rounded-full">
                            @else
                                <div class="flex items-center justify-center w-8 h-8 font-bold text-white rounded-full bg-gradient-to-br from-primary-500 to-primary-600">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                            <span class="hidden text-sm font-medium text-gray-700 md:inline">{{ $user->name }}</span>
                            <x-icon name="o-chevron-down" class="w-4 h-4 text-gray-500" />
                        </button>

                        <div x-show="open" @click.outside="open = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute right-0 z-50 w-56 py-2 mt-2 bg-white border border-gray-200 shadow-xl rounded-xl">
                            @if($user->isStudent())
                                <a href="{{ route('student.dashboard') }}" wire:navigate
                                   class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50">
                                    <x-icon name="o-home" class="w-5 h-5 mr-3" />
                                    {{ __('Dashboard') }}
                                </a>
                            @elseif($user->isTeacher())
                                <a href="{{ route('teacher.dashboard') }}" wire:navigate
                                   class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50">
                                    <x-icon name="o-home" class="w-5 h-5 mr-3" />
                                    {{ __('Dashboard') }}
                                </a>
                            @elseif($user->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" wire:navigate
                                   class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50">
                                    <x-icon name="o-home" class="w-5 h-5 mr-3" />
                                    {{ __('Dashboard') }}
                                </a>
                            @endif

                            <a href="{{ route('profile') }}" wire:navigate
                               class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50">
                                <x-icon name="o-user-circle" class="w-5 h-5 mr-3" />
                                {{ __('My Profile') }}
                            </a>

                            <a href="{{ route('payment.subscription') }}" wire:navigate
                               class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50">
                                <x-icon name="o-credit-card" class="w-5 h-5 mr-3" />
                                {{ __('Subscription') }}
                            </a>

                            <a href="{{ route('payment.history') }}" wire:navigate
                               class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50">
                                <x-icon name="o-document-text" class="w-5 h-5 mr-3" />
                                {{ __('Payment History') }}
                            </a>

                            <div class="my-2 border-t"></div>

                            <button wire:click="logout"
                                    wire:confirm="{{ __('Are you sure you want to logout?') }}"
                                    class="flex items-center w-full px-4 py-3 text-red-600 hover:bg-red-50">
                                <x-icon name="o-arrow-right-on-rectangle" class="w-5 h-5 mr-3" />
                                {{ __('Logout') }}
                            </button>
                        </div>
                    </div>
                @else
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('login') }}" wire:navigate
                           class="px-4 py-2 font-medium text-gray-700 transition-colors hover:text-primary-600">
                            {{ __('Login') }}
                        </a>
                        <a href="{{ route('register') }}" wire:navigate
                           class="px-6 py-2 font-medium text-white transition-colors rounded-lg shadow-md bg-primary-600 hover:bg-primary-700 hover:shadow-lg">
                            {{ __('Sign Up') }}
                        </a>
                    </div>
                @endauth

                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                        class="p-2 transition-colors rounded-lg md:hidden hover:bg-gray-100">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="mobileMenuOpen" class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-cloak
             class="py-4 border-t border-gray-200 md:hidden">
            <div class="flex flex-col space-y-2">
                @foreach($navigation as $item)
                    @if(str_starts_with($item['route'], '#'))
                        <a href="{{ $item['route'] }}"
                           @click="mobileMenuOpen = false"
                           class="px-4 py-3 font-medium text-gray-700 transition-colors rounded-lg hover:text-primary-600 hover:bg-primary-50">
                            {{ $item['name'] }}
                        </a>
                    @else
                        <a href="{{ route($item['route']) }}"
                           wire:navigate
                           @click="mobileMenuOpen = false"
                           class="px-4 py-3 font-medium text-gray-700 transition-colors rounded-lg hover:text-primary-600 hover:bg-primary-50">
                            {{ $item['name'] }}
                        </a>
                    @endif
                @endforeach

                <div class="pt-4 mt-2 border-t border-gray-200">
                    <p class="px-4 mb-2 text-sm font-semibold text-gray-500">{{ __('Language') }}</p>
                    <div class="grid grid-cols-2 gap-2 px-4">
                        @foreach($this->languages as $lang)
                        <button wire:click="switchLanguage('{{ $lang['code'] }}')"
                                @click="mobileMenuOpen = false"
                                class="flex items-center space-x-2 px-4 py-3 rounded-lg {{ app()->getLocale() === $lang['code'] ? 'bg-primary-50 text-primary-600' : 'text-gray-700 hover:bg-gray-50' }} transition-colors">
                            <span class="text-lg">{{ $lang['flag'] }}</span>
                            <span class="text-sm font-medium">{{ $lang['name'] }}</span>
                        </button>
                        @endforeach
                    </div>
                </div>

                @guest
                <div class="pt-4 border-t border-gray-200">
                    <a href="{{ route('login') }}"
                       wire:navigate
                       @click="mobileMenuOpen = false"
                       class="block px-4 py-3 text-gray-700 hover:text-primary-600">
                        {{ __('Login') }}
                    </a>
                    <a href="{{ route('register') }}"
                       wire:navigate
                       @click="mobileMenuOpen = false"
                       class="block px-4 py-3 font-medium text-primary-600">
                        {{ __('Sign Up') }}
                    </a>
                </div>
                @endguest
            </div>
        </div>
    </div>
</header>

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush
