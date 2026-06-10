<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

    <title>{!! isset($title) ? $title.' - '.config('app.name') : config('app.name') !!}</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}

    <link rel="stylesheet" href="{{ asset('build/assets/app-BRKrW89p.css') }}">
    <script src="{{ asset('build/assets/app-CcNNqum8.js') }}"></script>

    @livewireStyles
    @stack('styles')
</head>
<body class="min-h-screen font-sans antialiased bg-base-200" style="font-family: 'Inter', sans-serif;">
    <div class="flex items-center justify-center min-h-screen p-4">
        {{ $slot }}
    </div>

    <x-toast />
    @livewireScripts
    @stack('scripts')
</body>
</html>
