<?php

namespace App\Livewire\Navigation;

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public array $navigation = [];
    public array $userMenu = [];
    public bool $mobileMenuOpen = false;
    public string $currentLocale;

    public function mount(): void
    {
        $this->currentLocale = app()->getLocale();
        $this->loadNavigation();
        $this->loadUserMenu();
    }

    private function loadNavigation(): void
    {
        $this->navigation = [
            ['name' => __('Home'), 'type' => 'route', 'route' => 'home', 'icon' => 'o-home'],
            ['name' => __('Courses'), 'type' => 'route', 'route' => 'student.catalog', 'icon' => 'o-academic-cap'],
            ['name' => __('Pricing'), 'type' => 'anchor', 'href' => '#pricing', 'icon' => 'o-currency-dollar'],
            ['name' => __('Contact'), 'type' => 'anchor', 'href' => '#contact', 'icon' => 'o-envelope'],
        ];
    }

    private function loadUserMenu(): void
    {
        if (Auth::check()) {
            $user = Auth::user();

            $this->userMenu = [
                [
                    'name' => $user->name,
                    'type' => 'user_info',
                    'email' => $user->email,
                    'avatar' => $user->profile_photo_path
                ],
                ['type' => 'divider'],
                [
                    'name' => __('Dashboard'),
                    'route' => match($user->role) {
                        'admin' => 'admin.dashboard',
                        'teacher' => 'teacher.dashboard',
                        default => 'student.dashboard'
                    },
                    'icon' => 'o-home'
                ],
                ['name' => __('Profile'), 'route' => 'profile', 'icon' => 'o-user-circle'],
                ['name' => __('Settings'), 'route' => 'profile', 'icon' => 'o-cog-6-tooth'],
                ['type' => 'divider'],
                ['name' => __('Logout'), 'action' => 'logout', 'icon' => 'o-arrow-right-on-rectangle'],
            ];
        }
    }

    public function switchLanguage(string $locale): void
    {
        if (in_array($locale, ['en', 'fr', 'de'])) {
            session(['locale' => $locale]);
            app()->setLocale($locale);
            $this->currentLocale = $locale;
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

    public function getLanguagesProperty(): array
    {
        return [
            ['code' => 'fr', 'name' => 'Français', 'flag' => '🇫🇷'],
            ['code' => 'en', 'name' => 'English', 'flag' => '🇬🇧'],
            ['code' => 'de', 'name' => 'Deutsch', 'flag' => '🇩🇪'],
        ];
    }
};
?>

<nav class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm"
     x-data="{ mobileMenuOpen: false, langOpen: false }">

    <div class="container px-4 mx-auto">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3 group">
                    <div class="flex items-center justify-center w-10 h-10 transition-all duration-300 transform shadow-lg bg-gradient-to-br from-[#FF6B35] to-[#1E6091] rounded-xl group-hover:shadow-xl group-hover:scale-110">
                        <x-icon name="o-academic-cap" class="w-6 h-6 text-white" />
                    </div>
                    <span class="hidden text-xl font-bold text-gray-900 sm:block">{{ config('app.name', 'AllemandExpress') }}</span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="items-center hidden space-x-1 md:flex">
                @foreach($navigation as $item)
                    @if($item['type'] === 'route')
                        <a href="{{ route($item['route']) }}"
                           wire:navigate
                           class="px-4 py-2 rounded-lg text-gray-700 hover:text-[#FF6B35] hover:bg-orange-50 font-medium transition-colors">
                            {{ $item['name'] }}
                        </a>
                    @else
                        <a href="{{ $item['href'] }}"
                           class="px-4 py-2 rounded-lg text-gray-700 hover:text-[#FF6B35] hover:bg-orange-50 font-medium transition-colors">
                            {{ $item['name'] }}
                        </a>
                    @endif
                @endforeach
            </div>

            <!-- Right Section -->
            <div class="flex items-center gap-4">
                <!-- Language Switcher -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="flex items-center gap-2 px-3 py-2 transition-colors rounded-lg hover:bg-gray-100">
                        @foreach($this->languages as $lang)
                            @if($lang['code'] === $currentLocale)
                                <span class="text-lg">{{ $lang['flag'] }}</span>
                                <span class="hidden text-sm font-medium text-gray-700 sm:inline">{{ $lang['name'] }}</span>
                            @endif
                        @endforeach
                        <x-icon name="o-chevron-down" class="w-4 h-4 text-gray-500" />
                    </button>

                    <div x-show="open"
                         @click.outside="open = false"
                         x-transition
                         class="absolute right-0 z-50 w-48 py-2 mt-2 bg-white border border-gray-200 shadow-xl rounded-xl">
                        @foreach($this->languages as $lang)
                        <button wire:click="switchLanguage('{{ $lang['code'] }}')"
                                @click="open = false"
                                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors {{ $currentLocale === $lang['code'] ? 'bg-orange-50 text-[#FF6B35]' : 'text-gray-700' }}">
                            <span class="text-lg">{{ $lang['flag'] }}</span>
                            <span class="flex-1 font-medium text-left">{{ $lang['name'] }}</span>
                            @if($currentLocale === $lang['code'])
                                <x-icon name="o-check" class="w-5 h-5 text-[#FF6B35]" />
                            @endif
                        </button>
                        @endforeach
                    </div>
                </div>

                <!-- User Menu / Auth Buttons -->
                @auth
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center gap-2 p-2 transition-colors rounded-lg hover:bg-gray-100">
                            @if(auth()->user()->profile_photo_path)
                                <img src="{{ Storage::url(auth()->user()->profile_photo_path) }}"
                                     alt="{{ auth()->user()->name }}"
                                     class="object-cover w-8 h-8 rounded-full">
                            @else
                                <div class="flex items-center justify-center w-8 h-8 font-bold text-white rounded-full bg-gradient-to-br from-[#FF6B35] to-[#1E6091]">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                            @endif
                            <span class="hidden text-sm font-medium text-gray-700 md:inline">{{ auth()->user()->name }}</span>
                            <x-icon name="o-chevron-down" class="w-4 h-4 text-gray-500" />
                        </button>

                        <div x-show="open"
                             @click.outside="open = false"
                             x-transition
                             class="absolute right-0 z-50 w-64 py-2 mt-2 bg-white border border-gray-200 shadow-xl rounded-xl">
                            @foreach($userMenu as $item)
                                @if(($item['type'] ?? '') === 'divider')
                                    <hr class="my-2 border-gray-200">
                                @elseif(($item['type'] ?? '') === 'user_info')
                                    <div class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if($item['avatar'])
                                                <img src="{{ Storage::url($item['avatar']) }}" class="object-cover w-10 h-10 rounded-full">
                                            @else
                                                <div class="flex items-center justify-center w-10 h-10 font-bold text-white rounded-full bg-gradient-to-br from-[#FF6B35] to-[#1E6091]">
                                                    {{ strtoupper(substr($item['name'], 0, 1)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <p class="font-semibold text-gray-900">{{ $item['name'] }}</p>
                                                <p class="text-xs text-gray-500">{{ $item['email'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @elseif(isset($item['action']) && $item['action'] === 'logout')
                                    <button wire:click="logout"
                                            wire:confirm="{{ __('Are you sure you want to logout?') }}"
                                            class="flex items-center w-full gap-3 px-4 py-3 text-red-600 transition-colors hover:bg-red-50">
                                        <x-icon name="o-arrow-right-on-rectangle" class="w-5 h-5" />
                                        <span class="font-medium">{{ $item['name'] }}</span>
                                    </button>
                                @else
                                    <a href="{{ route($item['route']) }}"
                                       wire:navigate
                                       @click="open = false"
                                       class="flex items-center gap-3 px-4 py-3 text-gray-700 transition-colors hover:bg-gray-50">
                                        <x-icon :name="$item['icon']" class="w-5 h-5 text-gray-400" />
                                        <span class="font-medium">{{ $item['name'] }}</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-3">
                        <a href="{{ route('login') }}" wire:navigate class="px-4 py-2 font-medium text-gray-700 transition-colors hover:text-[#FF6B35]">
                            {{ __('Login') }}
                        </a>
                        <a href="{{ route('register') }}" wire:navigate class="px-6 py-2 font-medium text-white transition-colors rounded-lg shadow-md bg-gradient-to-r from-[#FF6B35] to-[#1E6091] hover:shadow-lg">
                            {{ __('Sign Up') }}
                        </a>
                    </div>
                @endauth

                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 transition-colors rounded-lg md:hidden hover:bg-gray-100">
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
        <div x-show="mobileMenuOpen" x-cloak x-transition class="py-4 border-t border-gray-200 md:hidden">
            <div class="flex flex-col space-y-2">
                @foreach($navigation as $item)
                    @if($item['type'] === 'route')
                        <a href="{{ route($item['route']) }}"
                           wire:navigate
                           @click="mobileMenuOpen = false"
                           class="px-4 py-3 rounded-lg text-gray-700 hover:text-[#FF6B35] hover:bg-orange-50 font-medium transition-colors">
                            {{ $item['name'] }}
                        </a>
                    @else
                        <a href="{{ $item['href'] }}"
                           @click="mobileMenuOpen = false"
                           class="px-4 py-3 rounded-lg text-gray-700 hover:text-[#FF6B35] hover:bg-orange-50 font-medium transition-colors">
                            {{ $item['name'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</nav>
