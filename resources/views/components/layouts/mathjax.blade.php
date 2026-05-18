<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathématiques - Corrections</title>

    <!-- MathJax -->
     <style>
        .math-display {
            overflow-x: auto;
            padding: 1rem 0;
        }

        .mjx-chtml {
            outline: none;
        }
     </style>

    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['\\(', '\\)']],
                displayMath: [['\\[', '\\]']],
                processEscapes: true
            },
            svg: {
                fontCache: 'global'
            }
        };
    </script>
    <!-- <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script> -->
    <!-- <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script> -->
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@4/tex-mml-chtml.js"></script>



    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<body class="min-h-screen font-sans antialiased bg-base-200">

    {{-- NAVBAR --}}
    <livewire:navigation.navbar />

    {{-- MAIN --}}
    <x-main>
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit lg:hidden">

            {{-- BRAND --}}

            {{-- MENU --}}
            <livewire:navigation.sidebar />
        </x-slot:sidebar>

        {{-- The `$slot` goes here --}}
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>

    {{--  TOAST area --}}
    <x-toast />
</body>
</html>
