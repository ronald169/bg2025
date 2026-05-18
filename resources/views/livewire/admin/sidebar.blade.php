<?php

namespace App\Livewire\Admin;

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public $pendingUsers = 0;
    public $pendingReviews = 0;
    public $draftCourses = 0;
    public $unreadContacts = 0;

    public function mount(): void
    {
        $this->refreshCounts();
    }

    public function refreshCounts(): void
    {
        $this->pendingUsers = \App\Models\User::where('status', 'pending')->count();
        $this->pendingReviews = \App\Models\Review::where('is_approved', false)->count();
        $this->draftCourses = \App\Models\Course::where('is_published', false)->count();
        $this->unreadContacts = \App\Models\Contact::where('is_read', false)->count();
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(route('home'), navigate: true);
    }

    public function getNavigationProperty()
    {
        return [
            'main' => [
                ['name' => __('Dashboard'), 'route' => 'admin.dashboard', 'icon' => 'o-chart-bar', 'color' => 'orange'],
                ['name' => __('Users'), 'route' => 'admin.users', 'icon' => 'o-users', 'color' => 'blue', 'badge' => $this->pendingUsers],
                ['name' => __('Courses'), 'route' => 'admin.courses', 'icon' => 'o-academic-cap', 'color' => 'green', 'badge' => $this->draftCourses],
                ['name' => __('Subjects'), 'route' => 'admin.subjects', 'icon' => 'o-tag', 'color' => 'purple'],
                ['name' => __('Enrollments'), 'route' => 'admin.enrollments', 'icon' => 'o-clipboard-document-check', 'color' => 'cyan'],
                ['name' => __('Reviews'), 'route' => 'admin.reviews', 'icon' => 'o-star', 'color' => 'yellow', 'badge' => $this->pendingReviews],
                ['name' => __('Contacts'), 'route' => 'admin.contacts', 'icon' => 'o-envelope', 'color' => 'pink', 'badge' => $this->unreadContacts],
                ['name' => __('Reports'), 'route' => 'admin.reports', 'icon' => 'o-document-chart-bar', 'color' => 'red'],
            ],
            'system' => [
                ['name' => __('Settings'), 'route' => 'admin.settings', 'icon' => 'o-cog-6-tooth', 'color' => 'gray'],
                ['name' => __('Backup'), 'route' => 'admin.backup', 'icon' => 'o-cloud-arrow-up', 'color' => 'gray'],
                ['name' => __('Logs'), 'route' => 'admin.logs', 'icon' => 'o-document-text', 'color' => 'gray'],
            ],
        ];
    }

    public function getIconColorClass($color): string
    {
        return match($color) {
            'orange' => 'text-orange-400',
            'blue' => 'text-blue-400',
            'green' => 'text-green-400',
            'purple' => 'text-purple-400',
            'cyan' => 'text-cyan-400',
            'yellow' => 'text-yellow-400',
            'red' => 'text-red-400',
            'pink' => 'text-pink-400',
            default => 'text-gray-400',
        };
    }
};
?>

<div class="flex flex-col h-full bg-gradient-to-b from-gray-900 to-gray-800">
    @php
        $user = auth()->user();
    @endphp
    
    <!-- Admin Profile -->
    <div class="p-5 border-b border-gray-700">
        <div class="flex items-center gap-3">
            @if($user->profile_photo_path)
                <img src="{{ Storage::url($user->profile_photo_path) }}"
                     alt="{{ $user->name }}"
                     class="object-cover w-12 h-12 rounded-xl ring-2 ring-gray-600">
            @else
                <div class="flex items-center justify-center w-12 h-12 text-lg font-bold text-white rounded-xl bg-gradient-to-r from-[#FF6B35] to-[#1E6091] ring-2 ring-gray-600">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-white truncate">{{ $user->name }}</h3>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 mt-1 text-xs rounded-full bg-gray-700 text-gray-300">
                    <x-icon name="o-shield-check" class="w-3 h-3" />
                    {{ __('Administrator') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <!-- Main section -->
        <p class="px-3 mb-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">
            {{ __('Main navigation') }}
        </p>
        
        @foreach($this->navigation['main'] as $item)
            <a href="{{ route($item['route']) }}"
               wire:navigate
               class="flex items-center justify-between px-3 py-2.5 rounded-lg transition-all duration-200 group
                      {{ request()->routeIs($item['route'] . '*') 
                          ? 'bg-gray-800 text-white' 
                          : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <div class="flex items-center gap-3">
                    <x-icon :name="$item['icon']"
                            class="w-5 h-5 {{ request()->routeIs($item['route'] . '*') ? $this->getIconColorClass($item['color']) : 'text-gray-500 group-hover:' . $this->getIconColorClass($item['color']) }}" />
                    <span class="text-sm font-medium">{{ $item['name'] }}</span>
                </div>
                @if(isset($item['badge']) && $item['badge'] > 0)
                    <span class="px-2 py-0.5 text-xs font-semibold text-white rounded-full bg-gradient-to-r from-[#FF6B35] to-[#E55A2A]">
                        {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                    </span>
                @endif
            </a>
        @endforeach

        <!-- Divider -->
        <div class="my-4 border-t border-gray-700"></div>

        <!-- System Section -->
        <p class="px-3 mb-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">
            {{ __('System') }}
        </p>

        @foreach($this->navigation['system'] as $item)
            <a href="{{ route($item['route']) }}"
               wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group
                      {{ request()->routeIs($item['route'] . '*') 
                          ? 'bg-gray-800 text-white' 
                          : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <x-icon :name="$item['icon']"
                        class="w-5 h-5 {{ request()->routeIs($item['route'] . '*') ? 'text-gray-300' : 'text-gray-500 group-hover:text-gray-300' }}" />
                <span class="text-sm font-medium">{{ $item['name'] }}</span>
            </a>
        @endforeach
    </nav>

    <!-- System Stats -->
    <div class="p-3 mx-3 mb-3 bg-gray-800 rounded-xl">
        <div class="grid grid-cols-2 gap-2 mb-2 text-center">
            <div class="p-2 rounded-lg bg-gray-700/50">
                <p class="text-xs text-gray-400">{{ __('Users') }}</p>
                <p class="text-lg font-bold text-white">{{ number_format(\App\Models\User::count()) }}</p>
            </div>
            <div class="p-2 rounded-lg bg-gray-700/50">
                <p class="text-xs text-gray-400">{{ __('Courses') }}</p>
                <p class="text-lg font-bold text-white">{{ number_format(\App\Models\Course::count()) }}</p>
            </div>
        </div>
        <div class="text-center">
            <p class="text-xs text-gray-500">
                <x-icon name="o-clock" class="inline w-3 h-3 mr-1" />
                {{ __('Updated') }}: {{ now()->format('H:i') }}
            </p>
        </div>
    </div>

    <!-- Logout Button -->
    <div class="p-3 mt-auto border-t border-gray-700">
        <button wire:click="logout"
                wire:confirm="{{ __('Are you sure you want to logout?') }}"
                class="flex items-center w-full gap-3 px-3 py-2.5 text-red-400 transition-colors rounded-lg hover:bg-red-500 hover:text-white">
            <x-icon name="o-arrow-right-on-rectangle" class="w-5 h-5" />
            <span class="text-sm font-medium">{{ __('Logout') }}</span>
        </button>
    </div>
</div>