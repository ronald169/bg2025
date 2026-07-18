<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<ink>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-JKZP1L88VJ"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-JKZP1L88VJ');
    </script>

    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-KDJTC2ZM');</script>
    <!-- End Google Tag Manager -->

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

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
    @if(isset($structuredData))
        <script type="application/ld+json">
            {!! $structuredData !!}
        </script>
    @endif

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

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

    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}

    <link rel="stylesheet" href="{{ asset('build/assets/app-CAWMuCHf.css') }}">
    <script src="{{ asset('build/assets/app-CcNNqum8.js') }}"></script>

    <link rel="stylesheet" href="{{ asset('tinymce-custom.css') }}">
</head>
<body class="min-h-screen font-sans antialiased bg-base-200" style="font-family: 'Inter', sans-serif;">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KDJTC2ZM"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    {{-- The navbar with `sticky` and `full-width` --}}
    <x-nav sticky full-width>

        <x-slot:brand>
            {{-- Brand --}}
            <div class="text-xl font-bold text-primary">
                <a href="{{ route('home') }}">
                    <img src="{{ asset('/storage/images/logo.png') }}" alt="{{ config('app.name') }} logo" class="inline-block h-8 mr-2">
                </a>
            </div>
        </x-slot:brand>

        {{-- Right side actions --}}
        <x-slot:actions>
            <x-button label="{{ __('Courses') }}" icon="o-academic-cap" link="{{ route('student.catalog') }}" class="btn-ghost btn-sm" />

            <x-dropdown class="dropdown-end">
                {{-- Bouton déclencheur --}}
                <x-slot:trigger>
                    <button class="btn btn-ghost btn-circle">
                        @switch(app()->getLocale())
                            @case('fr')
                                🇫🇷
                                @break
                            @case('en')
                                🇬🇧
                                @break
                            @default
                                🇩🇪
                        @endswitch
                    </button>
                </x-slot:trigger>

                {{-- Menu des langues --}}
                <x-menu>
                    <x-menu-item title="Français" icon="o-flag" link="{{ route('language.switch', 'fr') }}" />
                    <x-menu-item title="English" icon="o-flag" link="{{ route('language.switch', 'en') }}" />
                    <x-menu-item title="Deutsch"  icon="o-flag" link="{{ route('language.switch', 'de') }}" />
                </x-menu>
            </x-dropdown>

            @if (!auth()->check())
                <x-button label="{{ __('Login') }}" icon="o-user" link="{{ route('login') }}" class="btn-ghost btn-sm" responsive />
            @else
                <x-dropdown label="{{ auth()->user()->name }}" class="btn-ghost btn-sm" >

                    <x-menu-item title="{{ __('Dashboard') }}" link="{{ route('dashboard.redirect') }}" />
                <x-menu-item title="{{ __('Logout') }}" icon="o-arrow-left-end-on-rectangle" link="{{ route('logout') }}" />
                </x-dropdown>

            @endif
        </x-slot:actions>
    </x-nav>


    <x-main with-nav full-width>

        {{-- The `$slot` goes here --}}
        <x-slot:content>
            <div class="-m-5 lg:-mx-10 lg:-my-5">
                {{ $slot }}
            </div>
        </x-slot:content>
    </x-main>

    {{--  TOAST area --}}
    <x-toast />

</body>
</html>
