<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'AllemandExpress')) - {{ __('Admin') }}</title>

    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ assert('/build/assets/app-D4GsFlKT.css') }}">
    <script src="{{ assert('/build/assets/app-Bj43h_rG.js') }}"></script>
    @livewireStyles

    <style>
        /* Custom scrollbar pour la sidebar */
        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-track {
            background: #1f2937;
        }

        ::-webkit-scrollbar-thumb {
            background: #4b5563;
            border-radius: 2px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }
    </style>
</head>
<body class="h-full antialiased bg-gray-900">
    <div class="flex min-h-screen">
        <!-- Desktop Sidebar -->
        <div class="hidden w-64 bg-gray-900 lg:block">
            <livewire:admin.sidebar />
        </div>

        <!-- Mobile Sidebar Toggle -->
        <button onclick="toggleAdminMobileSidebar()"
                class="fixed z-40 flex items-center justify-center text-white transition-colors rounded-full shadow-lg lg:hidden bottom-6 right-6 w-14 h-14 bg-gradient-to-r from-[#FF6B35] to-[#1E6091] hover:shadow-xl">
            <x-icon name="o-bars-3" class="w-6 h-6" />
        </button>

        <!-- Mobile Sidebar Overlay -->
        <div id="admin-mobile-sidebar-overlay"
             class="fixed inset-0 z-30 hidden bg-black/50 lg:hidden"
             onclick="toggleAdminMobileSidebar()">
        </div>

        <!-- Mobile Sidebar -->
        <div id="admin-mobile-sidebar"
             class="fixed top-0 left-0 z-40 w-64 h-full transition-transform duration-300 ease-in-out transform -translate-x-full bg-gray-900 shadow-xl lg:hidden">
            <livewire:admin.sidebar />
        </div>

        <!-- Main Content -->
        <main class="flex-1 min-h-screen bg-gray-50">
            <!-- Top Header -->
            <div class="sticky top-0 z-20 bg-white border-b border-gray-200 shadow-sm">
                <div class="flex items-center justify-between px-4 py-3 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button onclick="toggleAdminMobileSidebar()" class="p-2 text-gray-600 rounded-lg lg:hidden hover:bg-gray-100">
                            <x-icon name="o-bars-3" class="w-5 h-5" />
                        </button>
                        <h1 class="text-lg font-semibold text-gray-900 lg:text-xl">
                            @yield('page-title', __('Administration'))
                        </h1>
                    </div>

                    <!-- Top Right Actions -->
                    <div class="flex items-center gap-3">
                        <!-- Notifications -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="relative p-2 text-gray-600 rounded-lg hover:bg-gray-100">
                                <x-icon name="o-bell" class="w-5 h-5" />
                                {{-- @if($pendingNotificationsCount > 0)
                                    <span class="absolute w-2 h-2 bg-red-500 rounded-full top-1 right-1"></span>
                                @endif --}}
                            </button>
                        </div>

                        <!-- User Menu -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100">
                                @if(auth()->user()->profile_photo_path)
                                    <img src="{{ Storage::url(auth()->user()->profile_photo_path) }}"
                                         class="object-cover w-8 h-8 rounded-full">
                                @else
                                    <div class="flex items-center justify-center w-8 h-8 font-bold text-white rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091]">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="hidden text-sm font-medium text-gray-700 lg:inline">{{ auth()->user()->name }}</span>
                                <x-icon name="o-chevron-down" class="w-4 h-4 text-gray-500" />
                            </button>

                            <div x-show="open" @click.outside="open = false" x-transition
                                 class="absolute right-0 z-50 w-56 py-2 mt-2 bg-white border border-gray-200 shadow-xl rounded-xl">
                                <a href="{{ route('profile') }}" class="flex items-center gap-3 px-4 py-2 text-gray-700 hover:bg-gray-50">
                                    <x-icon name="o-user-circle" class="w-5 h-5" />
                                    {{ __('Profil') }}
                                </a>
                                <hr class="my-2 border-gray-200">
                                <button wire:click="logout" wire:confirm="{{ __('Sind Sie sicher?') }}"
                                        class="flex items-center w-full gap-3 px-4 py-2 text-red-600 hover:bg-red-50">
                                    <x-icon name="o-arrow-right-on-rectangle" class="w-5 h-5" />
                                    {{ __('Abmelden') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Breadcrumbs -->
            @hasSection('breadcrumbs')
                <div class="px-4 py-2 text-sm text-gray-500 bg-gray-100 border-b lg:px-8">
                    @yield('breadcrumbs')
                </div>
            @endif

            <!-- Flash Messages -->
            <div class="px-4 pt-4 lg:px-8">
                @if(session('success'))
                    <x-alert icon="o-check-circle" class="mb-4 alert-success" dismissible>
                        {{ session('success') }}
                    </x-alert>
                @endif
                @if(session('error'))
                    <x-alert icon="o-exclamation-triangle" class="mb-4 alert-error" dismissible>
                        {{ session('error') }}
                    </x-alert>
                @endif
            </div>

            <!-- Page Content -->
            <div class="p-4 lg:p-8">
                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts

    <script>
        function toggleAdminMobileSidebar() {
            const sidebar = document.getElementById('admin-mobile-sidebar');
            const overlay = document.getElementById('admin-mobile-sidebar-overlay');

            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        }

        // Fermer le menu mobile quand on clique sur un lien
        document.addEventListener('DOMContentLoaded', function() {
            const mobileLinks = document.querySelectorAll('#admin-mobile-sidebar a, #admin-mobile-sidebar button');
            mobileLinks.forEach(link => {
                link.addEventListener('click', () => {
                    const sidebar = document.getElementById('admin-mobile-sidebar');
                    const overlay = document.getElementById('admin-mobile-sidebar-overlay');
                    if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
                        sidebar.classList.add('-translate-x-full');
                        sidebar.classList.remove('translate-x-0');
                        overlay.classList.add('hidden');
                        document.body.style.overflow = 'auto';
                    }
                });
            });
        });
    </script>

    @stack('scripts')

    {{-- TOAST area --}}
    <x-toast />
</body>
</html>
