<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ assert('/build/assets/app-D4GsFlKT.css') }}">
    <script src="{{ assert('/build/assets/app-Bj43h_rG.js') }}"></script>

</head>
<body class="min-h-screen font-sans antialiased bg-base-200/50">

    {{-- NAVBAR mobile only --}}
    <x-nav sticky class="shadow-sm lg:hidden bg-base-100">
        <x-slot:brand>
            <x-app-brand />
        </x-slot:brand>
        <x-slot:actions>
            <label for="main-drawer" class="lg:hidden me-3">
                <x-icon name="o-bars-3" class="cursor-pointer" />
            </label>
        </x-slot:actions>
    </x-nav>

    {{-- MAIN --}}
    <x-main with-nav>
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">

            {{-- BRAND --}}
            <x-app-brand class="px-5 pt-4" />

            {{-- MENU --}}
            <x-menu activate-by-route class="text-base">

                {{-- Dashboard --}}
                <x-menu-item title="{!! __('Dashboard') !!}" icon="o-chart-bar" link="{{ route('student.dashboard') }}" />

                {{-- Navigation Étudiant --}}
                @auth
                    @if(auth()->user()->isStudent())
                        <x-menu-item title="{!! __('Course Catalog') !!}" icon="o-book-open" link="{{ route('catalog') }}" />
                        <x-menu-item title="{!! __('My Courses') !!}" icon="o-bookmark" link="{{ route('my-courses') }}" />
                        <x-menu-item title="{!! __('Progress') !!}" icon="o-chart-bar" link="{{ route('progress') }}" />
                    @endif

                    {{-- Navigation Professeur --}}
                    @if(auth()->user()->isTeacher() || auth()->user()->isAdmin())
                        <x-menu-item title="{!! __('Teacher Dashboard') !!}" icon="o-academic-cap" link="{{ route('teacher.dashboard') }}" />
                        <x-menu-item title="{!! __('My Courses') !!}" icon="o-book-open" link="{{ route('teacher.courses') }}" />
                        <x-menu-item title="{!! __('Create Course') !!}" icon="o-plus-circle" link="{{ route('teacher.courses.create') }}" />

                        @if(auth()->user()->isAdmin())
                            <x-menu-item title="{!! __('Administration') !!}" icon="o-cog-6-tooth" link="{{ route('admin.dashboard') }}" />
                        @endif
                    @endif
                @endauth

                {{-- Séparateur --}}
                <x-menu-separator />

                {{-- Profil Utilisateur --}}
                @auth
                    <x-list-item
                        :item="auth()->user()"
                        value="name"
                        sub-value="email"
                        no-separator
                        no-hover
                        class="-mx-2 !-my-2 rounded"
                    >
                        <x-slot:avatar>
                            <x-avatar :image="auth()->user()->profile_photo ?? null" class="!w-10 !h-10">
                                <x-slot:fallback class="text-white bg-primary">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </x-slot:fallback>
                            </x-avatar>
                        </x-slot:avatar>

                        <x-slot:actions>
                            <x-button
                                icon="o-power"
                                class="btn-circle btn-ghost btn-xs"
                                tooltip-left="{!! __('Logout') !!}"
                                no-wire-navigate
                                link="/logout"
                            />
                        </x-slot:actions>
                    </x-list-item>

                    <x-menu-separator />

                    {{-- Liens Profil --}}
                    <x-menu-item title="{!! __('Profile') !!}" icon="o-user" link="{{ route('profile') }}" />
                    <x-menu-item title="{!! __('Settings') !!}" icon="o-cog-6-tooth" link="{{ route('settings') }}" />
                @endauth

                {{-- Sélecteur de Langue --}}
                <div class="px-4 py-2">
                    <livewire:language-switcher />
                </div>
            </x-menu>
        </x-slot:sidebar>

        {{-- CONTENU PRINCIPAL --}}
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>

    {{-- TOAST area --}}
    <x-toast />

</body>
</html>
