<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

    <title>{!! isset($title) ? $title.' - '.config('app.name') : config('app.name') !!}</title>

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <script src="{{ asset('storage/tinymce/tinymce.min.js') }}"></script>

    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}

    <link rel="stylesheet" href="{{ asset('build/assets/app-BRKrW89p.css') }}">
    <script src="{{ asset('build/assets/app-CcNNqum8.js') }}"></script>

    @stack('styles')

    <link rel="stylesheet" href="{{ asset('tinymce-custom.css') }}">

</head>
<body class="min-h-screen font-sans antialiased bg-base-200" style="font-family: 'Inter', sans-serif;">

    {{-- NAVBAR mobile uniquement --}}
    <x-nav sticky class="lg:hidden">
        <x-slot:brand>
            <div class="text-xl font-bold text-primary">
                <a href="{{ route('home') }}">
                    <img src="{{ asset('/storage/images/logo.png') }}" alt="{{ config('app.name') }} logo" class="inline-block h-8 mr-2">
                </a>
            </div>
        </x-slot:brand>
        <x-slot:actions>
            <label for="main-drawer" class="lg:hidden me-3">
                <x-icon name="o-bars-3" class="cursor-pointer" />
            </label>
        </x-slot:actions>
    </x-nav>

    {{-- MAIN (avec sidebar intégrée) --}}
    <x-main with-nav full-width>
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">

            {{-- BRAND --}}
            <div class="px-5 pt-5">
                <div class="text-2xl font-bold text-primary">
                    <a href="{{ route('home') }}">
                        <img src="{{ asset('/storage/images/logo.png') }}" alt="{{ config('app.name') }} logo" class="inline-block h-8 mr-2">
                    </a>
                </div>
                <div class="text-xs text-base-content/60">{{ __('Deutsch lernen online') }}</div>
            </div>

            {{-- MENU --}}
            <x-menu activate-by-route>

                {{-- User info --}}
                @if($user = auth()->user())
                    <x-menu-separator />
                    <x-list-item :item="$user" value="name" sub-value="email" no-separator no-hover class="-mx-2 !-my-2 rounded">
                        <x-slot:actions>
                            <x-button link="{{ route('logout') }}" icon="o-arrow-left-end-on-rectangle" class="btn-circle btn-ghost btn-xs" tooltip-left="{!!Str::limit(__('Logout'), 20) !!}" />
                        </x-slot:actions>
                    </x-list-item>
                    <x-menu-separator />
                @endif

                {{-- MENU ÉTUDIANT --}}
                @if(auth()->user()->role === 'student')
                    <x-menu-item title="{{__('Dashboard')}}" icon="o-home" link="{{ route('student.dashboard') }}" />
                    <x-menu-item title="{{__('My Courses')}}" icon="o-academic-cap" link="{{ route('student.catalog') }}" />
                    <x-menu-item title="{{__('Progression')}}" icon="o-chart-bar" link="{{ route('student.progress') }}" />
                    <x-menu-item title="{!! __('Learning Path') !!}" icon="o-map" link="{{ route('student.learning-path') }}" />
                    <x-menu-item title="{{__('Quiz')}}" icon="o-document-text" link="{{ route('student.quiz-history') }}" />
                    {{-- <x-menu-item title="{{__('Flashcards')}}" icon="o-cpu-chip" link="{{ route('student.flashcards') }}" /> --}}
                    {{-- <x-menu-item title="{{__('Notes')}}" icon="o-document-text" link="{{ route('student.notes') }}" /> --}}
                    <x-menu-item title="{{__('Messages')}}" icon="o-chat-bubble-left" link="{{ route('student.messages') }}" />
                    {{-- <x-menu-item title="{{__('Calendar')}}" icon="o-calendar" link="{{ route('student.calendar') }}" /> --}}
                    {{-- <x-menu-item title="{{__('Certificats')}}" icon="o-document-check" link="{{ route('student.certificates') }}" /> --}}
                    <x-menu-item title="{{__('Achievement')}}" icon="o-trophy" link="{{ route('student.achievements') }}" />
                @endif

                {{-- MENU PROFESSEUR --}}
                @if(auth()->user()->role === 'teacher')
                    <x-menu-item title="{{__('Dashboard')}}" icon="o-home" link="{{ route('teacher.dashboard') }}" />
                    <x-menu-item title="{{__('My Courses')}}" icon="o-academic-cap" link="{{ route('teacher.courses') }}" />
                    <x-menu-item title="{{__('Students')}}" icon="o-users" link="{{ route('teacher.students') }}" />
                    {{-- <x-menu-item title="{{__('Quiz')}}" icon="o-document-text" link="{{ route('teacher.quizzes.index') }}" /> --}}
                    <x-menu-item title="{{__('Messages')}}" icon="o-chat-bubble-left" link="{{ route('teacher.messages') }}" />
                    {{-- <x-menu-item title="{{__('Calendar')}}" icon="o-calendar" link="{{ route('teacher.schedule') }}" /> --}}
                    {{-- <x-menu-item title="{{__('Annonces')}}" icon="o-megaphone" link="{{ route('teacher.announcements') }}" /> --}}
                    <x-menu-item title="{{__('Analyses')}}" icon="o-chart-bar" link="{{ route('teacher.analytics') }}" />
                @endif

                {{-- MENU ADMIN --}}
                @if(auth()->user()->role === 'admin')
                    <x-menu-item title="{{__('Dashboard')}}" icon="o-home" link="{{ route('admin.dashboard') }}" />
                    <x-menu-item title="{{__('Users')}}" icon="o-users" link="{{ route('admin.users') }}" />
                    <x-menu-item title="{{__('Subjects')}}" icon="o-bookmark" link="{{ route('admin.subjects') }}" />
                    <x-menu-item title="{{__('Courses')}}" icon="o-academic-cap" link="{{ route('admin.courses') }}" />
                    <x-menu-item title="{{__('Subscriptions')}}" icon="o-user-plus" link="{{ route('admin.enrollments') }}" />
                    {{-- <x-menu-item title="{{__('Reviews')}}" icon="o-star" link="{{ route('admin.reviews') }}" /> --}}
                    <x-menu-item title="{{__('Analytics')}}" icon="o-chart-bar" link="{{ route('admin.analytics') }}" />
                    <x-menu-item title="{{__('Rapports')}}" icon="o-document-text" link="{{ route('admin.reports') }}" />
                    <x-menu-item title="{{__('Contact')}}" icon="o-envelope" link="{{ route('admin.contacts') }}" />
                    <x-menu-item title="{{__('Settings')}}" icon="o-cog-6-tooth" link="{{ route('admin.settings') }}" />
                @endif

                {{-- Paramètres généraux (tous rôles) --}}
                <x-menu-separator />
                <x-menu-sub title="{{__('Settings')}}" icon="o-cog-6-tooth">
                    <x-menu-item title="{{__('My Profile')}}" icon="o-user" link="{{ route('profile') }}" />
                    {{-- <x-menu-item title="{{__('Notifications')}}" icon="o-bell" link="{{ route('settings.notifications') }}" /> --}}
                    <x-menu-item title="{{__('Security')}}" icon="o-shield-check" link="{{ route('settings.security') }}" />
                </x-menu-sub>
                <x-menu-item link="{{ route('logout') }}" class="btn-warning" icon="o-arrow-left-end-on-rectangle" title="{{ __('Logout') }}" />
            </x-menu>
        </x-slot:sidebar>

        {{-- CONTENU PRINCIPAL --}}
        <x-slot:content>
            {{-- Messages flash --}}
            @if(session('success'))
                <x-alert title="Succès" icon="o-check-circle" class="mb-4 shadow-lg alert-success">
                    {{ session('success') }}
                </x-alert>
            @endif
            @if(session('error'))
                <x-alert title="Erreur" icon="o-exclamation-triangle" class="mb-4 shadow-lg alert-error">
                    {{ session('error') }}
                </x-alert>
            @endif
            @if($errors->any())
                <x-alert title="Erreur de validation" icon="o-exclamation-circle" class="mb-4 shadow-lg alert-warning">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            {{-- Contenu dynamique --}}
            {{ $slot }}
        </x-slot:content>
    </x-main>

    <x-toast />
</body>
</html>
