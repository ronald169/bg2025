<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'AllemandExpress')) - {{ __('Teacher Dashboard') }}</title>

    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ assert('/build/assets/app-D4GsFlKT.css') }}">
    <script src="{{ assert('/build/assets/app-Bj43h_rG.js') }}"></script>
    @livewireStyles

    <style>
        :root {
            --color-primary: 255 107 53; /* #FF6B35 */
            --color-secondary: 30 96 145; /* #1E6091 */
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F9FAFB;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .animate-pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes bounce-in {
            0% {
                opacity: 0;
                transform: scale(0.95);
            }
            60% {
                opacity: 1;
                transform: scale(1.02);
            }
            100% {
                transform: scale(1);
            }
        }

        .animate-bounce-in {
            animation: bounce-in 0.6s ease-out;
        }
    </style>

    <script src="{{ asset('storage/tinymce/tinymce.min.js') }}" ></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased bg-gray-50">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">
        <div class="container px-4 mx-auto">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('teacher.dashboard') }}" class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 shadow-lg bg-gradient-to-br from-[#FF6B35] to-[#1E6091] rounded-xl">
                            <x-icon name="o-academic-cap" class="w-6 h-6 text-white" />
                        </div>
                        <span class="text-xl font-bold text-gray-900">{{ config('app.name', 'AllemandExpress') }}</span>
                        <span class="px-2 py-1 ml-2 text-xs font-semibold rounded-full bg-orange-100 text-[#FF6B35]">
                            {{ __('Teacher') }}
                        </span>
                    </a>
                </div>

                <!-- Right Section -->
                <div class="flex items-center gap-4">
                    <!-- Quick Actions -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center gap-2 px-3 py-2 text-white transition-colors rounded-lg bg-gradient-to-r from-[#FF6B35] to-[#1E6091] hover:shadow-md">
                            <x-icon name="o-plus" class="w-4 h-4" />
                            <span class="hidden text-sm font-medium sm:inline">{{ __('New') }}</span>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-transition
                             class="absolute right-0 z-50 w-56 py-2 mt-2 bg-white border border-gray-200 shadow-xl rounded-xl">
                            <a href="{{ route('teacher.courses.create') }}" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50">
                                <x-icon name="o-academic-cap" class="w-5 h-5 mr-3 text-[#FF6B35]" />
                                {{ __('New Course') }}
                            </a>
                            {{-- <a href="{{ route('teacher.quizzes.create') }}" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50">
                                <x-icon name="o-document-text" class="w-5 h-5 mr-3 text-[#1E6091]" />
                                {{ __('New Quiz') }}
                            </a> --}}
                        </div>
                    </div>

                    <!-- Notifications -->
                    <livewire:notification-bell />

                    <!-- User Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-3 p-2 transition-colors rounded-lg hover:bg-gray-100">
                            <div class="flex items-center gap-3">
                                @if(auth()->user()->profile_photo_path)
                                    <img src="{{ Storage::url(auth()->user()->profile_photo_path) }}"
                                         class="object-cover w-8 h-8 rounded-full">
                                @else
                                    <div class="flex items-center justify-center w-8 h-8 font-bold text-white rounded-full bg-gradient-to-br from-[#FF6B35] to-[#1E6091]">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="hidden text-sm font-medium text-gray-700 md:inline">
                                    {{ auth()->user()->name }}
                                </span>
                            </div>
                            <x-icon name="o-chevron-down" class="w-4 h-4 text-gray-500" />
                        </button>

                        <div x-show="open" @click.outside="open = false" x-transition
                             class="absolute right-0 z-50 w-56 py-2 mt-2 bg-white border border-gray-200 shadow-xl rounded-xl">
                            <a href="{{ route('profile') }}" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50">
                                <x-icon name="o-user-circle" class="w-5 h-5 mr-3" />
                                {{ __('Profile') }}
                            </a>
                            <a href="{{ route('teacher.settings') }}" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50">
                                <x-icon name="o-cog-6-tooth" class="w-5 h-5 mr-3" />
                                {{ __('Settings') }}
                            </a>
                            <hr class="my-2 border-gray-200">
                            <a href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                               class="flex items-center px-4 py-3 text-red-600 hover:bg-red-50">
                                <x-icon name="o-arrow-right-on-rectangle" class="w-5 h-5 mr-3" />
                                {{ __('Logout') }}
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                @csrf
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content with Sidebar -->
    <div class="flex min-h-screen">
        <!-- Desktop Sidebar -->
        <div class="hidden lg:block fixed left-0 top-16 h-[calc(100vh-4rem)] w-64 z-30 overflow-y-auto border-r border-gray-200 bg-white">
            <x-sidebar.teacher />
        </div>

        <!-- Mobile Sidebar Toggle -->
        <button onclick="toggleMobileSidebar()"
                class="fixed z-40 flex items-center justify-center text-white transition-colors rounded-full shadow-lg lg:hidden bottom-6 right-6 w-14 h-14 bg-gradient-to-r from-[#FF6B35] to-[#1E6091] hover:shadow-xl">
            <x-icon name="o-bars-3" class="w-6 h-6" />
        </button>

        <!-- Mobile Sidebar Overlay -->
        <div id="mobile-sidebar-overlay"
             class="fixed inset-0 z-30 hidden bg-black/50 lg:hidden"
             onclick="toggleMobileSidebar()">
        </div>

        <!-- Mobile Sidebar -->
        <div id="mobile-sidebar"
             class="fixed top-0 left-0 z-40 w-64 h-full transition-transform duration-300 ease-in-out transform -translate-x-full bg-white shadow-xl lg:hidden">
            <div class="p-4 border-b bg-gradient-to-r from-[#FF6B35] to-[#1E6091]">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-white/20">
                        <x-icon name="custom.chalkboard-teacher" class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-white/80">{{ __('Teacher') }}</p>
                    </div>
                </div>
            </div>
            <x-sidebar.teacher />
        </div>

        <!-- Main Content -->
        <main class="flex-1 min-h-screen lg:ml-64">
            <!-- Page Header -->
            @hasSection('page-header')
                <div class="bg-white border-b">
                    <div class="container px-4 py-6 mx-auto">
                        @yield('page-header')
                    </div>
                </div>
            @endif

            <!-- Breadcrumbs -->
            @hasSection('breadcrumbs')
                <div class="bg-gray-100 border-b">
                    <div class="container px-4 py-3 mx-auto">
                        @yield('breadcrumbs')
                    </div>
                </div>
            @endif

            <!-- Flash Messages -->
            <div class="container px-4 pt-4 mx-auto">
                @if(session('success'))
                    <x-alert icon="o-check-circle" class="mb-4 alert-success animate-bounce-in" dismissible>
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
            <div class="container px-4 py-8 mx-auto">
                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts

    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('mobile-sidebar');
            const overlay = document.getElementById('mobile-sidebar-overlay');

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
            const mobileLinks = document.querySelectorAll('#mobile-sidebar a');
            mobileLinks.forEach(link => {
                link.addEventListener('click', () => {
                    const sidebar = document.getElementById('mobile-sidebar');
                    const overlay = document.getElementById('mobile-sidebar-overlay');
                    if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
                        sidebar.classList.add('-translate-x-full');
                        sidebar.classList.remove('translate-x-0');
                        overlay?.classList.add('hidden');
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
