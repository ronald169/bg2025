<?php

namespace App\Livewire\Sidebar;

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public $pendingUsers = 0;
    public $pendingReviews = 0;
    public $draftCourses = 0;

    public function mount(): void
    {
        $this->pendingUsers = \App\Models\User::where('status', 'pending')->count();
        $this->pendingReviews = \App\Models\Review::where('is_approved', false)->count();
        $this->draftCourses = \App\Models\Course::where('is_published', false)->count();
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
                    'route' => 'admin.dashboard',
                    'icon' => 'o-chart-bar',
                    'active' => request()->routeIs('admin.dashboard'),
                ],
                [
                    'name' => __('Users'),
                    'route' => 'admin.users',
                    'icon' => 'o-users',
                    'active' => request()->routeIs('admin.users*'),
                    'badge' => $this->pendingUsers,
                ],
                [
                    'name' => __('Courses'),
                    'route' => 'admin.courses',
                    'icon' => 'o-academic-cap',
                    'active' => request()->routeIs('admin.courses*'),
                    'badge' => $this->draftCourses,
                ],
                [
                    'name' => __('Subjects'),
                    'route' => 'admin.subjects',
                    'icon' => 'o-tag',
                    'active' => request()->routeIs('admin.subjects*'),
                ],
                [
                    'name' => __('Enrollments'),
                    'route' => 'admin.enrollments',
                    'icon' => 'o-clipboard-document-check',
                    'active' => request()->routeIs('admin.enrollments*'),
                ],
                [
                    'name' => __('Reviews'),
                    'route' => 'admin.reviews',
                    'icon' => 'o-star',
                    'active' => request()->routeIs('admin.reviews*'),
                    'badge' => $this->pendingReviews,
                ],
                [
                    'name' => __('Reports'),
                    'route' => 'admin.reports',
                    'icon' => 'o-document-chart-bar',
                    'active' => request()->routeIs('admin.reports*'),
                ],
            ],
            'system' => [
                [
                    'name' => __('Settings'),
                    'route' => 'admin.settings',
                    'icon' => 'o-cog-6-tooth',
                    'active' => request()->routeIs('admin.settings'),
                ],
                [
                    'name' => __('Backup'),
                    'route' => 'admin.backup',
                    'icon' => 'o-cloud-arrow-up',
                    'active' => request()->routeIs('admin.backup'),
                ],
                [
                    'name' => __('Logs'),
                    'route' => 'admin.logs',
                    'icon' => 'o-document-text',
                    'active' => request()->routeIs('admin.logs'),
                ],
            ],
        ];
    }

}; ?>

<div class="flex flex-col h-full text-white bg-gradient-to-b from-gray-900 to-gray-800">
    @php
        $user = auth()->user();
    @endphp
    <!-- Admin Profile -->
    <div class="p-6 border-b border-gray-700">
        <div class="flex items-center space-x-4">
            @if($user->profile_photo_path)
                <img src="{{ Storage::url($user->profile_photo_path) }}"
                     alt="{{ $user->name }}"
                     class="object-cover w-12 h-12 rounded-xl ring-2 ring-gray-600">
            @else
                <div class="flex items-center justify-center w-12 h-12 text-lg font-bold text-white bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl ring-2 ring-gray-600">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-white truncate">{{ $user->name }}</h3>
                <span class="inline-flex items-center text-xs bg-gray-700 text-gray-300 px-2 py-0.5 rounded-full mt-1">
                    <x-icon name="o-users" class="w-3 h-3 mr-1" />
                    {{ __('Administrator') }}
                </span>
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
                          ? 'bg-gray-800 text-white'
                          : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <div class="flex items-center space-x-3">
                    <x-icon :name="$item['icon']"
                            class="w-5 h-5 {{ $item['active'] ? 'text-primary-400' : 'text-gray-500 group-hover:text-primary-400' }}" />
                    <span class="text-sm font-medium">{{ $item['name'] }}</span>
                </div>
                @if(isset($item['badge']) && $item['badge'] > 0)
                    <span class="px-2 py-1 text-xs font-semibold text-white rounded-full bg-primary-500">
                        {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                    </span>
                @endif
            </a>
        @endforeach

        <!-- Divider -->
        <div class="my-4 border-t border-gray-700"></div>

        <!-- System Section -->
        <p class="px-4 mb-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">
            {{ __('System') }}
        </p>

        @foreach($this->navigation['system'] as $item)
            <a href="{{ route($item['route']) }}"
               wire:navigate
               class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group
                      {{ $item['active']
                          ? 'bg-gray-800 text-white'
                          : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <x-icon :name="$item['icon']"
                        class="w-5 h-5 mr-3 {{ $item['active'] ? 'text-primary-400' : 'text-gray-500 group-hover:text-primary-400' }}" />
                <span class="text-sm font-medium">{{ $item['name'] }}</span>
            </a>
        @endforeach
    </nav>

    <!-- System Stats -->
    <div class="p-4 m-4 bg-gray-800 rounded-xl">
        <div class="grid grid-cols-2 gap-2 mb-3 text-center">
            <div>
                <p class="text-xs text-gray-400">{{ __('Users') }}</p>
                <p class="font-bold text-white">{{ \App\Models\User::count() }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">{{ __('Courses') }}</p>
                <p class="font-bold text-white">{{ \App\Models\Course::count() }}</p>
            </div>
        </div>
        <div class="text-xs text-center text-gray-400">
            {{ __('Last updated') }}: {{ now()->format('H:i') }}
        </div>
    </div>

    <!-- Logout -->
    <div class="p-4 border-t border-gray-700">
        <button wire:click="logout"
                wire:confirm="{{ __('Are you sure you want to logout?') }}"
                class="flex items-center w-full px-4 py-3 text-red-400 transition-colors rounded-xl hover:bg-red-500 hover:text-white">
            <x-icon name="o-arrow-right-on-rectangle" class="w-5 h-5 mr-3" />
            <span class="text-sm font-medium">{{ __('Logout') }}</span>
        </button>
    </div>
</div>
