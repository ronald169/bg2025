<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{!! isset($title) ? $title.' - '.config('app.name') : config('app.name') !!}</title>

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <script src="{{ asset('storage/tinymce/tinymce.min.js') }}"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <link rel="stylesheet" href="{{ asset('tinymce-custom.css') }}">

</head>
<body class="min-h-screen font-sans antialiased bg-base-200" style="font-family: 'Inter', sans-serif;">

    {{-- NAVBAR mobile uniquement --}}
    <x-nav sticky class="lg:hidden">
        <x-slot:brand>
            <div class="text-xl font-bold text-primary">AllemandExpress</div>
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
                <div class="text-2xl font-bold text-primary">AllemandExpress</div>
                <div class="text-xs text-base-content/60">Deutsch lernen online</div>
            </div>

            {{-- MENU --}}
            <x-menu activate-by-route>

                {{-- User info --}}
                @if($user = auth()->user())
                    <x-menu-separator />
                    <x-list-item :item="$user" value="name" sub-value="email" no-separator no-hover class="-mx-2 !-my-2 rounded">
                        <x-slot:actions>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <x-button icon="o-arrow-left-end-on-rectangle" class="btn-circle btn-ghost btn-xs" tooltip-left="Déconnexion" />
                            </form>
                        </x-slot:actions>
                    </x-list-item>
                    <x-menu-separator />
                @endif

                {{-- MENU ÉTUDIANT --}}
                @if(auth()->user()->role === 'student')
                    <x-menu-item title="Tableau de bord" icon="o-home" link="{{ route('student.dashboard') }}" />
                    <x-menu-item title="Mes cours" icon="o-academic-cap" link="{{ route('student.catalog') }}" />
                    <x-menu-item title="Progression" icon="o-chart-bar" link="{{ route('student.progress') }}" />
                    <x-menu-item title="Parcours" icon="o-map" link="{{ route('student.learning-path') }}" />
                    <x-menu-item title="Quiz" icon="o-document-text" link="{{ route('student.quiz-history') }}" />
                    <x-menu-item title="Flashcards" icon="o-cpu-chip" link="{{ route('student.flashcards') }}" />
                    <x-menu-item title="Notes" icon="o-document-text" link="{{ route('student.notes') }}" />
                    <x-menu-item title="Messages" icon="o-chat-bubble-left" link="{{ route('student.messages') }}" />
                    <x-menu-item title="Calendrier" icon="o-calendar" link="{{ route('student.calendar') }}" />
                    <x-menu-item title="Certificats" icon="o-document-check" link="{{ route('student.certificates') }}" />
                    <x-menu-item title="Réussites" icon="o-trophy" link="{{ route('student.achievements') }}" />
                @endif

                {{-- MENU PROFESSEUR --}}
                @if(auth()->user()->role === 'teacher')
                    <x-menu-item title="Tableau de bord" icon="o-home" link="{{ route('teacher.dashboard') }}" />
                    <x-menu-item title="Mes cours" icon="o-academic-cap" link="{{ route('teacher.courses') }}" />
                    <x-menu-item title="Étudiants" icon="o-users" link="{{ route('teacher.students') }}" />
                    {{-- <x-menu-item title="Quiz" icon="o-document-text" link="{{ route('teacher.quizzes.index') }}" /> --}}
                    <x-menu-item title="Messages" icon="o-chat-bubble-left" link="{{ route('teacher.messages') }}" />
                    <x-menu-item title="Calendrier" icon="o-calendar" link="{{ route('teacher.schedule') }}" />
                    <x-menu-item title="Annonces" icon="o-megaphone" link="{{ route('teacher.announcements') }}" />
                    <x-menu-item title="Analyses" icon="o-chart-bar" link="{{ route('teacher.analytics') }}" />
                @endif

                {{-- MENU ADMIN --}}
                @if(auth()->user()->role === 'admin')
                    <x-menu-item title="Tableau de bord" icon="o-home" link="{{ route('admin.dashboard') }}" />
                    <x-menu-item title="Utilisateurs" icon="o-users" link="{{ route('admin.users') }}" />
                    <x-menu-item title="Cours" icon="o-academic-cap" link="{{ route('admin.courses') }}" />
                    <x-menu-item title="Inscriptions" icon="o-user-plus" link="{{ route('admin.enrollments') }}" />
                    <x-menu-item title="Avis" icon="o-star" link="{{ route('admin.reviews') }}" />
                    <x-menu-item title="Rapports" icon="o-chart-bar" link="{{ route('admin.reports') }}" />
                    <x-menu-item title="Paramètres" icon="o-cog-6-tooth" link="{{ route('admin.settings') }}" />
                @endif

                {{-- Paramètres généraux (tous rôles) --}}
                <x-menu-separator />
                <x-menu-sub title="Paramètres" icon="o-cog-6-tooth">
                    <x-menu-item title="Mon profil" icon="o-user" link="{{ route('profile') }}" />
                    <x-menu-item title="Notifications" icon="o-bell" link="{{ route('settings.notifications') }}" />
                    <x-menu-item title="Sécurité" icon="o-shield-check" link="{{ route('settings.security') }}" />
                </x-menu-sub>
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
    @livewireScripts
    @stack('scripts')
</body>
</html>
