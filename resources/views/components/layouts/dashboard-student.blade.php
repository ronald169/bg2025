<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'AllemandExpress') }} - @yield('title', 'Student Dashboard')</title>

    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ assert('/build/assets/app-D4GsFlKT.css') }}">
    <script src="{{ assert('/build/assets/app-Bj43h_rG.js') }}"></script>

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

        /* Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        .hover-lift:hover {
            transform: translateY(-4px);
            transition: transform 0.3s ease;
        }

        /* Glass effect */
        .glass-effect {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>

    @stack('styles')
</head>
<body class="h-full antialiased">
    <!-- Header pour dashboard étudiant -->
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm" x-data="{ mobileMenuOpen: false }">
        <div class="container px-4 mx-auto">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('student.dashboard') }}" class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 shadow-lg bg-gradient-to-br from-[#FF6B35] to-[#1E6091] rounded-xl">
                            <x-icon name="o-academic-cap" class="w-6 h-6 text-white" />
                        </div>
                        <span class="text-xl font-bold text-gray-900">{{ config('app.name', 'AllemandExpress') }}</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <nav class="items-center hidden space-x-1 md:flex">
                    <a href="{{ route('student.dashboard') }}"
                       class="px-4 py-2 rounded-lg text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35] font-medium transition-colors {{ request()->routeIs('student.dashboard*') ? 'bg-orange-50 text-[#FF6B35]' : '' }}">
                        <x-icon name="o-home" class="inline w-5 h-5 mr-2" />
                        Dashboard
                    </a>
                    <a href="{{ route('student.catalog') }}"
                       class="px-4 py-2 rounded-lg text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35] font-medium transition-colors {{ request()->routeIs('student.catalog*') ? 'bg-orange-50 text-[#FF6B35]' : '' }}">
                        <x-icon name="o-academic-cap" class="inline w-5 h-5 mr-2" />
                        Kurse
                    </a>
                    <a href="{{ route('student.progress') }}"
                       class="px-4 py-2 rounded-lg text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35] font-medium transition-colors {{ request()->routeIs('student.progress*') ? 'bg-orange-50 text-[#FF6B35]' : '' }}">
                        <x-icon name="o-chart-bar" class="inline w-5 h-5 mr-2" />
                        Fortschritt
                    </a>
                </nav>

                <!-- Right Section -->
                <div class="flex items-center gap-4">
                    <!-- Notifications -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="relative p-2 text-gray-600 transition-colors rounded-lg hover:text-[#FF6B35] hover:bg-gray-100">
                            <x-icon name="o-bell" class="w-6 h-6" />
                            @if(auth()->user()->unreadNotifications()->count() > 0)
                                <span class="absolute flex items-center justify-center w-4 h-4 text-xs text-white bg-red-500 rounded-full top-1 right-1">
                                    {{ auth()->user()->unreadNotifications()->count() }}
                                </span>
                            @endif
                        </button>

                        <div x-show="open" @click.outside="open = false"
                             class="absolute right-0 z-50 mt-2 bg-white border border-gray-200 shadow-xl w-80 rounded-xl">
                            <div class="p-4 border-b">
                                <h3 class="font-semibold text-gray-900">Benachrichtigungen</h3>
                            </div>
                            <div class="overflow-y-auto max-h-96">
                                <livewire:notification-center />
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-3 p-2 transition-colors rounded-lg hover:bg-gray-100">
                            <div class="flex items-center gap-3">
                                @if(auth()->user()->profile_photo_path)
                                    <img src="{{ Storage::url(auth()->user()->profile_photo_path) }}"
                                         alt="{{ auth()->user()->name }}"
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

                        <div x-show="open" @click.outside="open = false"
                             class="absolute right-0 z-50 w-56 py-2 mt-2 bg-white border border-gray-200 shadow-xl rounded-xl">
                            <a href="{{ route('profile') }}" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50">
                                <x-icon name="o-user-circle" class="w-5 h-5 mr-3" />
                                Mein Profil
                            </a>
                            <a href="/student/settings" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50">
                                <x-icon name="o-cog-6-tooth" class="w-5 h-5 mr-3" />
                                Einstellungen
                            </a>
                            <hr class="my-2 border-gray-200">
                            <a href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                               class="flex items-center px-4 py-3 text-red-600 hover:bg-red-50">
                                <x-icon name="o-arrow-right-on-rectangle" class="w-5 h-5 mr-3" />
                                Abmelden
                            </a>
                        </div>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 text-gray-600 rounded-lg md:hidden hover:text-[#FF6B35] hover:bg-gray-100">
                        <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg x-show="mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                 class="py-4 border-t border-gray-200 md:hidden">
                <nav class="flex flex-col space-y-2">
                    <a href="{{ route('student.dashboard') }}"
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-orange-50 hover:text-[#FF6B35]">
                        <x-icon name="o-home" class="w-5 h-5 mr-3" />
                        Dashboard
                    </a>
                    <a href="{{ route('student.catalog') }}"
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-orange-50 hover:text-[#FF6B35]">
                        <x-icon name="o-academic-cap" class="w-5 h-5 mr-3" />
                        Kurse
                    </a>
                    <a href="{{ route('student.progress') }}"
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-orange-50 hover:text-[#FF6B35]">
                        <x-icon name="o-chart-bar" class="w-5 h-5 mr-3" />
                        Fortschritt
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content Area avec Sidebar -->
    <div class="flex min-h-screen">
        <!-- Sidebar - visible sur desktop -->
        <div class="hidden lg:block fixed left-0 top-16 h-[calc(100vh-4rem)] w-64 z-30 overflow-y-auto border-r border-gray-200 bg-white">
            <x-sidebar.student />
        </div>

        <!-- Mobile Sidebar Toggle -->
        <button onclick="toggleMobileSidebar()"
                class="fixed z-40 flex items-center justify-center text-white transition-colors rounded-full shadow-lg lg:hidden bottom-6 right-6 w-14 h-14 bg-gradient-to-r from-[#FF6B35] to-[#1E6091] hover:from-[#E55A2A] hover:to-[#15507b]">
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
                        <x-icon name="o-academic-cap" class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-white/80">Student</p>
                    </div>
                </div>
            </div>
            <x-sidebar.student />
        </div>

        <!-- Main Content -->
        <main class="flex-1 min-h-screen lg:ml-64">
            <div class="container px-4 py-6 mx-auto">
                {{ $slot }}
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="py-6 mt-auto bg-white border-t border-gray-200 lg:ml-64">
        <div class="container px-4 mx-auto">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <p class="text-sm text-gray-600">
                    &copy; {{ date('Y') }} {{ config('app.name', 'AllemandExpress') }}. Alle Rechte vorbehalten.
                </p>
                <div class="flex gap-4 mt-4 md:mt-0">
                    <a href="#" class="text-sm text-gray-500 hover:text-gray-700">Hilfe</a>
                    <a href="#" class="text-sm text-gray-500 hover:text-gray-700">AGB</a>
                    <a href="#" class="text-sm text-gray-500 hover:text-gray-700">Datenschutz</a>
                </div>
            </div>
        </div>
    </footer>

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
                        overlay.classList.add('hidden');
                        document.body.style.overflow = 'auto';
                    }
                });
            });
        });
    </script>

    @stack('scripts')
    @livewireScripts

    {{-- TOAST area --}}
    <x-toast />
</body>
</html>
