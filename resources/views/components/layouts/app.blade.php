<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'AllemandExpress'))</title>

    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ assert('/build/assets/app-D4GsFlKT.css') }}">
    <script src="{{ assert('/build/assets/app-Bj43h_rG.js') }}"></script>
    @livewireStyles

    <style>
        .animate-pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">
    <livewire:navigation.navbar />

    <main class="min-h-screen">
        <!-- Breadcrumbs optionnels -->
        @hasSection('breadcrumbs')
            <div class="bg-white border-b">
                <div class="container px-4 py-3 mx-auto">
                    @yield('breadcrumbs')
                </div>
            </div>
        @endif

        <!-- Page Content -->
        <div class="container px-4 py-6 mx-auto">
            {{ $slot }}
        </div>
    </main>

    <livewire:navigation.footer />

    @livewireScripts

    {{-- TOAST area --}}
    <x-toast />
</body>
</html>
