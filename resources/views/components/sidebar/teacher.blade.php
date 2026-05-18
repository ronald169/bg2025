@php
    // Compter les messages non lus
    $unreadMessagesCount = \App\Models\Message::where('receiver_id', auth()->id())
        ->where('is_read', false)
        ->count();

    // Compter les cours de l'enseignant
    $coursesCount = auth()->user()->courses()->count();

    // Compter les étudiants
    $studentsCount = \App\Models\Enrollment::whereIn('course_id', auth()->user()->courses()->pluck('id'))
        ->distinct('user_id')
        ->count('user_id');
@endphp

<nav class="flex flex-col h-full py-4">
    <div class="flex-1 space-y-1">
        <!-- Main Navigation -->
        <div class="px-3 mb-2">
            <p class="px-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">{{ __('Main') }}</p>
        </div>

        <!-- Dashboard -->
        <a href="{{ route('teacher.dashboard') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('teacher.dashboard*') ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <div class="flex items-center gap-3">
                <x-icon name="o-home" class="w-5 h-5" />
                <span class="font-medium">{{ __('Dashboard') }}</span>
            </div>
        </a>

        <!-- My Courses -->
        <a href="{{ route('teacher.courses') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('teacher.courses*') ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <div class="flex items-center gap-3">
                <x-icon name="o-academic-cap" class="w-5 h-5" />
                <span class="font-medium">{{ __('My Courses') }}</span>
            </div>
            @if($coursesCount > 0)
                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-white/20 text-white">
                    {{ $coursesCount }}
                </span>
            @endif
        </a>

        <!-- Students -->
        <a href="{{ route('teacher.students') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('teacher.students*') ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <div class="flex items-center gap-3">
                <x-icon name="o-users" class="w-5 h-5" />
                <span class="font-medium">{{ __('Students') }}</span>
            </div>
            @if($studentsCount > 0)
                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-white/20 text-white">
                    {{ $studentsCount }}
                </span>
            @endif
        </a>

        <!-- Analytics -->
        <a href="{{ route('teacher.analytics') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('teacher.analytics*') ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <div class="flex items-center gap-3">
                <x-icon name="o-chart-bar" class="w-5 h-5" />
                <span class="font-medium">{{ __('Analytics') }}</span>
            </div>
        </a>

        <!-- Messages -->
        <a href="{{ route('teacher.messages') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('teacher.messages*') ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <div class="flex items-center gap-3">
                <x-icon name="o-envelope" class="w-5 h-5" />
                <span class="font-medium">{{ __('Messages') }}</span>
            </div>
            @if($unreadMessagesCount > 0)
                <span class="px-2 py-0.5 text-xs font-semibold bg-red-500 text-white rounded-full">
                    {{ $unreadMessagesCount }}
                </span>
            @endif
        </a>

        <!-- Schedule -->
        <a href="{{ route('teacher.schedule') }}"
           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('teacher.schedule*') ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <div class="flex items-center gap-3">
                <x-icon name="o-calendar" class="w-5 h-5" />
                <span class="font-medium">{{ __('Schedule') }}</span>
            </div>
        </a>

        <!-- Divider -->
        <div class="relative my-4">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="px-3 text-xs text-gray-400 bg-white">{{ __('Course Management') }}</span>
            </div>
        </div>

        <!-- Course Management Tools -->
        <a href="{{ route('teacher.courses.create') }}"
           class="flex items-center px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('teacher.courses.create*') ? 'bg-orange-50 text-[#FF6B35]' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <x-icon name="o-plus-circle" class="w-5 h-5 mr-3" />
            <span class="font-medium">{{ __('Create Course') }}</span>
        </a>

        {{-- <a href="{{ route('teacher.quizzes.index') }}"
           class="flex items-center px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                  {{ request()->routeIs('teacher.quizzes*') ? 'bg-orange-50 text-[#FF6B35]' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <x-icon name="o-document-text" class="w-5 h-5 mr-3" />
            <span class="font-medium">{{ __('Quiz Builder') }}</span>
        </a> --}}

        <!-- Dans la sidebar teacher, ajoutez ce lien -->
        <!-- Communication Section -->
        <div class="relative my-4">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="px-3 text-xs text-gray-400 bg-white">{{ __('Communication') }}</span>
            </div>
        </div>

        <!-- Messages -->
        <a href="{{ route('teacher.messages') }}"
        class="flex items-center px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                {{ request()->routeIs('teacher.messages*') ? 'bg-orange-50 text-[#FF6B35]' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <x-icon name="o-envelope" class="w-5 h-5 mr-3" />
            <span class="font-medium">{{ __('Messages') }}</span>
        </a>

        <!-- Announcements - NOUVEAU LIEN -->
        <a href="{{ route('teacher.announcements') }}"
        class="flex items-center px-4 py-2.5 mx-2 rounded-lg transition-all duration-200
                {{ request()->routeIs('teacher.announcements*') ? 'bg-orange-50 text-[#FF6B35]' : 'text-gray-700 hover:bg-orange-50 hover:text-[#FF6B35]' }}">
            <x-icon name="o-megaphone" class="w-5 h-5 mr-3" />
            <span class="font-medium">{{ __('Announcements') }}</span>
            @php
                $unreadAnnouncements = \App\Models\Announcement::where('teacher_id', auth()->id())
                    ->where('is_published', true)
                    ->count();
            @endphp
            @if($unreadAnnouncements > 0)
                <span class="ml-auto px-2 py-0.5 text-xs font-semibold bg-red-500 text-white rounded-full">
                    {{ $unreadAnnouncements }}
                </span>
            @endif
        </a>
    </div>

    <!-- Footer Sidebar -->
    <div class="pt-4 mt-auto border-t border-gray-200">
        <div class="px-4 py-2 mx-2">
            <div class="p-3 rounded-lg bg-gradient-to-r from-orange-50 to-blue-50">
                <div class="flex items-center gap-2 mb-2">
                    <x-icon name="o-chart-bar" class="w-4 h-4 text-[#FF6B35]" />
                    <span class="text-xs font-medium text-gray-700">{{ __('Quick Stats') }}</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ __('Total Courses') }}</span>
                        <span class="font-semibold text-gray-900">{{ $coursesCount }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ __('Total Students') }}</span>
                        <span class="font-semibold text-gray-900">{{ $studentsCount }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-4 py-3 mx-2 mt-2">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-sm">
                    👨‍🏫
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-700">{{ __('Teacher Role') }}</p>
                    <p class="text-xs text-gray-500">{{ __('Create and manage courses') }}</p>
                </div>
            </div>
        </div>
    </div>
</nav>
