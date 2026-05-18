<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'AllemandExpress'))</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ assert('/build/assets/app-D4GsFlKT.css') }}">
    <script src="{{ assert('/build/assets/app-Bj43h_rG.js') }}"></script>
    @livewireStyles

</head>
<body class="font-sans antialiased">
    <div class="flex items-center justify-center min-h-screen p-4 bg-gray-100">
        {{ $slot }}
    </div>
    @livewireScripts

    <x-toast />
</body>
</html>
