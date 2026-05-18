<?php

namespace App\Livewire\Sidebar;

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public $user;
    public $unreadMessages = 0;
    public $currentStreak = 0;

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->unreadMessages = 3; // À remplacer par la vraie logique
        $this->currentStreak = $this->user->learningStreak->current_streak ?? 0;
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect(route('login'), navigate: true);
    }

    public function getNavigationProperty()
    {
        return [
            'main' => [
                [
                    'name' => __('Dashboard'),
                    'route' => 'student.dashboard',
                    'icon' => 'o-home',
                    'active' => request()->routeIs('student.dashboard'),
                ],
                [
                    'name' => __('My Courses'),
                    'route' => 'student.catalog',
                    'icon' => 'o-academic-cap',
                    'active' => request()->routeIs('student.catalog'),
                ],
                [
                    'name' => __('Learning Path'),
                    'route' => 'student.learning-path',
                    'icon' => 'o-map',
                    'active' => request()->routeIs('student.learning-path'),
                    'badge' => 'New',
                ],
                [
                    'name' => __('Progress'),
                    'route' => 'student.progress',
                    'icon' => 'o-chart-bar',
                    'active' => request()->routeIs('student.progress'),
                ],
                [
                    'name' => __('Quizzes'),
                    'route' => 'student.quizzes',
                    'icon' => 'o-document-text',
                    'active' => request()->routeIs('student.quizzes'),
                ],
                [
                    'name' => __('Messages'),
                    'route' => 'student.messages',
                    'icon' => 'o-envelope',
                    'active' => request()->routeIs('student.messages'),
                    'badge' => $this->unreadMessages,
                ],
            ],
            'secondary' => [
                [
                    'name' => __('Settings'),
                    'route' => 'profile',
                    'icon' => 'o-cog-6-tooth',
                    'active' => request()->routeIs('profile'),
                ],
                [
                    'name' => __('Help'),
                    'route' => 'student.help',
                    'icon' => 'o-question-mark-circle',
                    'active' => request()->routeIs('student.help'),
                ],
            ],
        ];
    }
}; ?>

<div class="flex flex-col h-full bg-white border-r border-gray-200">
    <!-- User Profile Section -->
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center space-x-4">
            @if($user->profile_photo_path)
                <img src="{{ Storage::url($user->profile_photo_path) }}"
                     alt="{{ $user->name }}"
                     class="object-cover w-12 h-12 rounded-xl ring-2 ring-primary-100">
            @else
                <div class="flex items-center justify-center w-12 h-12 text-lg font-bold text-white bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl ring-2 ring-primary-100">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-gray-900 truncate">{{ $user->name }}</h3>
                <p class="mt-1 text-xs text-gray-500">
                    <span class="inline-flex items-center">
                        <span class="w-2 h-2 mr-1 bg-green-500 rounded-full animate-pulse"></span>
                        {{ __('Online') }}
                    </span>
                </p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 gap-2 mt-4">
            <div class="p-2 text-center rounded-lg bg-primary-50">
                <p class="text-xs text-gray-600">{{ __('Streak') }}</p>
                <p class="text-lg font-bold text-primary-600">{{ $currentStreak }}</p>
            </div>
            <div class="p-2 text-center rounded-lg bg-secondary-50">
                <p class="text-xs text-gray-600">{{ __('Progress') }}</p>
                <p class="text-lg font-bold text-secondary-600">{{ $user->progress()->where('is_completed', true)->count() }}</p>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        @foreach($this->navigation['main'] as $item)
            <a href="{{ route($item['route']) }}"
               wire:navigate
               class="flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 group
                      {{ $item['active']
                          ? 'bg-primary-50 text-primary-700 shadow-sm'
                          : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
                <div class="flex items-center space-x-3">
                    <x-icon :name="$item['icon']"
                            class="w-5 h-5 {{ $item['active'] ? 'text-primary-600' : 'text-gray-400 group-hover:text-gray-600' }}" />
                    <span class="text-sm font-medium">{{ $item['name'] }}</span>
                </div>
                @if(isset($item['badge']))
                    @if(is_numeric($item['badge']) && $item['badge'] > 0)
                        <span class="px-2 py-1 text-xs font-semibold text-red-600 bg-red-100 rounded-full">
                            {{ $item['badge'] }}
                        </span>
                    @elseif(is_string($item['badge']))
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-primary-100 text-primary-600">
                            {{ $item['badge'] }}
                        </span>
                    @endif
                @endif
            </a>
        @endforeach

        <!-- Divider -->
        <div class="my-4 border-t border-gray-200"></div>

        <!-- Secondary Navigation -->
        @foreach($this->navigation['secondary'] as $item)
            <a href="{{ route($item['route']) }}"
               wire:navigate
               class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group
                      {{ $item['active']
                          ? 'bg-gray-100 text-gray-900'
                          : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-icon :name="$item['icon']"
                        class="w-5 h-5 mr-3 {{ $item['active'] ? 'text-gray-700' : 'text-gray-400 group-hover:text-gray-600' }}" />
                <span class="text-sm font-medium">{{ $item['name'] }}</span>
            </a>
        @endforeach
    </nav>

    <!-- Upgrade Card -->
    <div class="p-4 m-4 text-white bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl">
        <div class="flex items-center justify-between mb-3">
            <x-icon name="o-sparkles" class="w-6 h-6 text-yellow-300 animate-pulse" />
            <span class="px-2 py-1 text-xs rounded-full bg-white/20">{{ __('Premium') }}</span>
        </div>
        <h4 class="mb-1 font-semibold">{{ __('Upgrade to Premium') }}</h4>
        <p class="mb-3 text-xs text-primary-100">{{ __('Get unlimited access to all courses') }}</p>
        <a href="{{ route('pricing') }}"
           wire:navigate
           class="block py-2 text-sm font-semibold text-center transition-colors bg-white rounded-lg text-primary-600 hover:bg-opacity-90">
            {{ __('Learn More') }}
        </a>
    </div>

    <!-- Logout -->
    <div class="p-4 border-t border-gray-200">
        <button wire:click="logout"
                wire:confirm="{{ __('Are you sure you want to logout?') }}"
                class="flex items-center w-full px-4 py-3 text-red-600 transition-colors rounded-xl hover:bg-red-50">
            <x-icon name="o-arrow-right-on-rectangle" class="w-5 h-5 mr-3" />
            <span class="text-sm font-medium">{{ __('Logout') }}</span>
        </button>
    </div>
</div>
