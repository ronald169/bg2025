@php
    // Compter les messages non lus
    $unreadMessagesCount = \App\Models\Message::where('receiver_id', auth()->id())
        ->where('is_read', false)
        ->count();

    // Récupérer la progression
    $learningPath = auth()->user()->learningPath;
    $totalProgress = $learningPath ? $learningPath->overall_progress : 0;
@endphp

<nav class="flex flex-col h-full py-4">
    <div class="flex-1 space-y-1">
        <!-- Hauptnavigation -->
        <div class="px-3 mb-2">
            <p class="px-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Hauptnavigation</p>
        </div>

        <a href="{{ route('home') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200">
            <div class="flex items-center gap-3">
                <x-icon name="o-home" class="w-5 h-5" />
                <span class="font-medium">Home</span>
            </div>
        </a>

        <a href="{{ route('student.dashboard') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('student.dashboard*') ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <div class="flex items-center gap-3">
                <x-icon name="o-home" class="w-5 h-5" />
                <span class="font-medium">Dashboard</span>
            </div>
        </a>

        <a href="{{ route('student.catalog') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('student.catalog*') ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <div class="flex items-center gap-3">
                <x-icon name="o-academic-cap" class="w-5 h-5" />
                <span class="font-medium">Kurse</span>
            </div>
        </a>

        <a href="{{ route('student.progress') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('student.progress*') ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <div class="flex items-center gap-3">
                <x-icon name="o-chart-bar" class="w-5 h-5" />
                <span class="font-medium">Fortschritt</span>
            </div>
        </a>

        <a href="{{ route('student.learning-path') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('student.learning-path*') ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <div class="flex items-center gap-3">
                <x-icon name="o-map" class="w-5 h-5" />
                <span class="font-medium">Lernpfad</span>
            </div>
        </a>

        <a href="{{ route('student.quiz-history') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('student.quiz-history*') ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <div class="flex items-center gap-3">
                <x-icon name="o-document-text" class="w-5 h-5" />
                <span class="font-medium">Quiz</span>
            </div>
        </a>

        <a href="{{ route('student.messages') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('student.messages*') ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <div class="flex items-center gap-3">
                <x-icon name="o-envelope" class="w-5 h-5" />
                <span class="font-medium">Nachrichten</span>
            </div>
            @if($unreadMessagesCount > 0)
                <span class="px-2 py-0.5 text-xs font-semibold bg-red-500 text-white rounded-full">
                    {{ $unreadMessagesCount }}
                </span>
            @endif
        </a>

        <!-- Trenner -->
        <div class="relative my-4">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="px-3 text-xs text-gray-400 bg-white">Tools</span>
            </div>
        </div>

        <!-- Tools Navigation -->
        <a href="{{ route('student.calendar') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('student.calendar*') ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <div class="flex items-center gap-3">
                <x-icon name="o-calendar" class="w-5 h-5" />
                <span class="font-medium">Lernkalender</span>
            </div>
        </a>

        <a href="{{ route('student.flashcards') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('student.flashcards*') ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <div class="flex items-center gap-3">
                <x-icon name="o-rectangle-stack" class="w-5 h-5" />
                <span class="font-medium">Karteikarten</span>
            </div>
        </a>

        <a href="{{ route('student.notes') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('student.notes*') ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <div class="flex items-center gap-3">
                <x-icon name="o-pencil-square" class="w-5 h-5" />
                <span class="font-medium">Notizen</span>
            </div>
        </a>
    </div>

    <!-- Footer Sidebar -->
    <div class="pt-4 mt-auto border-t border-gray-200">
        <div class="px-4 py-2 mx-2">
            <div class="p-3 rounded-lg bg-gradient-to-r from-orange-50 to-blue-50">
                <div class="flex items-center gap-2 mb-2">
                    <x-icon name="o-sparkles" class="w-4 h-4 text-[#FF6B35]" />
                    <span class="text-xs font-medium text-gray-700">Dein Fortschritt</span>
                </div>
                <div class="w-full h-1.5 bg-gray-200 rounded-full">
                    <div class="h-1.5 rounded-full bg-[#FF6B35]" style="width: {{ $totalProgress }}%"></div>
                </div>
                <p class="mt-2 text-xs text-gray-500">{{ $totalProgress }}% abgeschlossen</p>
            </div>
        </div>

        <div class="px-4 py-3 mx-2 mt-2">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-sm">
                    🇩🇪
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-700">Niveau</p>
                    <p class="text-xs text-gray-500">{{ auth()->user()->german_level ?? 'A1' }} - Deutsch</p>
                </div>
            </div>
        </div>
    </div>
</nav>
