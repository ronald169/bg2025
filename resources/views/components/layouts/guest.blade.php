<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">


    {{-- SEO Meta Tags --}}
    <title>@yield('meta_title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description')">
    <meta name="keywords" content="@yield('meta_keywords')">
    <meta name="robots" content="@yield('meta_robots', 'index,follow')">
    <link rel="canonical" href="@yield('canonical_url', url()->current())">

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('og_title', config('app.name'))">
    <meta property="og:description" content="@yield('og_description')">
    <meta property="og:image" content="@yield('og_image', asset('images/og-image.jpg'))">
    <meta property="og:type" content="website">
    <meta property="og:url" content="@yield('canonical_url', url()->current())">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', config('app.name'))">
    <meta name="twitter:url" content="{{ $twitter_url ?? url()->current() }}">
    <meta name="twitter:description" content="@yield('og_description')">
    <meta name="twitter:image" content="@yield('og_image', asset('images/og-image.jpg'))">

    {{-- Structured Data --}}
    @yield('structured_data')

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ assert('/build/assets/app-D4GsFlKT.css') }}">
    <script src="{{ assert('/build/assets/app-Bj43h_rG.js') }}"></script>
    @livewireStyles

    <!-- Custom Styles -->
    <style>
        /* Améliorations responsives */
        .container {
            width: 100%;
            margin-left: auto;
            margin-right: auto;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        @media (min-width: 640px) {
            .container {
                max-width: 640px;
            }
        }
        @media (min-width: 768px) {
            .container {
                max-width: 768px;
            }
        }
        @media (min-width: 1024px) {
            .container {
                max-width: 1024px;
            }
        }
        @media (min-width: 1280px) {
            .container {
                max-width: 1280px;
            }
        }

        /* Animations */
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Skip to content link for accessibility */
        .skip-to-content {
            position: absolute;
            left: -9999px;
            top: -9999px;
            z-index: 9999;
        }
        .skip-to-content:focus {
            left: 1rem;
            top: 1rem;
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 0 0 2px #FF6B35;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">
    <!-- Accessibility skip link -->
    <a href="#main-content" class="skip-to-content">{{ __('Skip to main content') }}</a>

    <!-- Navbar -->
    <livewire:navigation.navbar />

    <!-- Main Content -->
    <main id="main-content" class="min-h-screen animate-fade-in">
        <!-- Breadcrumbs (optional) -->
        @hasSection('breadcrumbs')
            <div class="bg-white border-b">
                <div class="container px-4 py-3 mx-auto text-sm">
                    @yield('breadcrumbs')
                </div>
            </div>
        @endif

        <!-- Flash Messages -->
        <div class="container px-4 pt-4 mx-auto">
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
        {{ $slot }}
    </main>

    <!-- Footer -->
    <livewire:navigation.footer />

    @livewireScripts

    <!-- Toast notifications -->
    <x-toast />

    <script>
        // Mobile menu handling
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.querySelector('[data-mobile-menu-button]');
            const mobileMenu = document.querySelector('[data-mobile-menu]');

            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', () => {
                    mobileMenu.classList.toggle('hidden');
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
