<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Review;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Mary\Traits\Toast;

new
#[Title('Admin Dashboard - AllemandExpress')]
#[Layout('components.layouts.dashboard-admin')]
class extends Component {
    use Toast;

    #[Computed]
    public function stats()
    {
        return [
            'total_users' => User::count(),
            'total_students' => User::where('role', 'student')->count(),
            'total_teachers' => User::where('role', 'teacher')->count(),
            'total_admins' => User::where('role', 'admin')->count(),
            'total_courses' => Course::count(),
            'published_courses' => Course::where('is_published', true)->count(),
            'draft_courses' => Course::where('is_published', false)->count(),
            'total_enrollments' => Enrollment::count(),
            'active_enrollments' => Enrollment::where('status', 'active')->count(),
            'pending_reviews' => Review::where('is_approved', false)->count(),
            'avg_rating' => round(Review::avg('rating') ?? 0, 1),
        ];
    }

    #[Computed]
    public function revenue()
    {
        $total = Enrollment::whereNotNull('paid_amount')->sum('paid_amount');
        return round($total, 2);
    }

    #[Computed]
    public function recentUsers()
    {
        return User::latest()->take(5)->get();
    }

    #[Computed]
    public function recentCourses()
    {
        return Course::with('teacher')
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function recentEnrollments()
    {
        return Enrollment::with(['user', 'course'])
            ->latest()
            ->take(10)
            ->get();
    }

    #[Computed]
    public function chartData()
    {
        $data = [];
        $maxUsers = 0;
        $maxCourses = 0;

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $usersCount = User::whereDate('created_at', $date)->count();
            $coursesCount = Course::whereDate('created_at', $date)->count();

            $data[] = [
                'date' => $date->format('D'),
                'day' => $date->format('d'),
                'users' => $usersCount,
                'courses' => $coursesCount,
            ];

            if ($usersCount > $maxUsers) $maxUsers = $usersCount;
            if ($coursesCount > $maxCourses) $maxCourses = $coursesCount;
        }

        return [
            'data' => $data,
            'max_users' => $maxUsers,
            'max_courses' => $maxCourses,
        ];
    }

    #[Computed]
    public function topCourses()
    {
        return Course::withCount('enrollments')
            ->orderBy('enrollments_count', 'desc')
            ->take(5)
            ->get();
    }

    public function getGrowthPercentage($current, $previous): int
    {
        if ($previous <= 0) return 0;
        return round(($current - $previous) / $previous * 100);
    }

    public function getFormattedNumber($number): string
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        }
        if ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        return (string)$number;
    }
}
?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-900 md:text-2xl">📊 {{ __('Admin Dashboard') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('Willkommen zurück, :name!', ['name' => auth()->user()->name]) }}</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-3 lg:grid-cols-6">
            <div class="bg-white rounded-lg p-3 text-center shadow-sm border-l-4 border-l-[#FF6B35]">
                <p class="text-xs text-gray-500">{{ __('Benutzer') }}</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->getFormattedNumber($this->stats['total_users']) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-blue-500">
                <p class="text-xs text-gray-500">{{ __('Studenten') }}</p>
                <p class="text-xl font-bold text-blue-600">{{ $this->getFormattedNumber($this->stats['total_students']) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-green-500">
                <p class="text-xs text-gray-500">{{ __('Lehrer') }}</p>
                <p class="text-xl font-bold text-green-600">{{ $this->getFormattedNumber($this->stats['total_teachers']) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-purple-500">
                <p class="text-xs text-gray-500">{{ __('Kurse') }}</p>
                <p class="text-xl font-bold text-purple-600">{{ $this->getFormattedNumber($this->stats['total_courses']) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-orange-500">
                <p class="text-xs text-gray-500">{{ __('Einschreibungen') }}</p>
                <p class="text-xl font-bold text-orange-600">{{ $this->getFormattedNumber($this->stats['total_enrollments']) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-green-500">
                <p class="text-xs text-gray-500">{{ __('Umsatz') }}</p>
                <p class="text-lg font-bold text-green-600">€{{ number_format($this->revenue, 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Secondary Stats -->
        <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-4">
            <div class="p-2 text-center rounded-lg bg-gray-50">
                <p class="text-xs text-gray-500">{{ __('Veröffentlicht') }}</p>
                <p class="text-lg font-bold text-green-600">{{ $this->stats['published_courses'] }}</p>
                <p class="text-xs text-gray-400">von {{ $this->stats['total_courses'] }}</p>
            </div>
            <div class="p-2 text-center rounded-lg bg-gray-50">
                <p class="text-xs text-gray-500">{{ __('Entwürfe') }}</p>
                <p class="text-lg font-bold text-yellow-600">{{ $this->stats['draft_courses'] }}</p>
            </div>
            <div class="p-2 text-center rounded-lg bg-gray-50">
                <p class="text-xs text-gray-500">{{ __('Aktive Einschreibungen') }}</p>
                <p class="text-lg font-bold text-blue-600">{{ $this->getFormattedNumber($this->stats['active_enrollments']) }}</p>
            </div>
            <div class="p-2 text-center rounded-lg bg-gray-50">
                <p class="text-xs text-gray-500">{{ __('Ø Bewertung') }}</p>
                <p class="text-lg font-bold text-yellow-600">{{ $this->stats['avg_rating'] }} ★</p>
            </div>
        </div>

        <!-- Alert for pending reviews -->
        @if($this->stats['pending_reviews'] > 0)
        <div class="p-3 mb-6 border border-yellow-200 rounded-lg bg-yellow-50">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <x-icon name="o-star" class="w-5 h-5 text-yellow-600" />
                    <div>
                        <p class="text-sm font-medium text-yellow-800">{{ __('Ausstehende Bewertungen') }}</p>
                        <p class="text-xs text-yellow-700">{{ $this->stats['pending_reviews'] }} {{ __('Bewertungen müssen überprüft werden') }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.reviews') }}" class="text-sm text-yellow-700 hover:underline">
                    {{ __('Überprüfen') }} →
                </a>
            </div>
        </div>
        @endif

        <!-- Chart Section -->
        <x-card class="mb-6 shadow-sm">
            <div class="flex items-center justify-between pb-2 mb-4 border-b">
                <div class="flex items-center gap-2">
                    <x-icon name="o-chart-bar" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Wöchentliche Aktivität') }}</h2>
                </div>
            </div>

            @php
                $maxValue = max($this->chartData['max_users'], $this->chartData['max_courses'], 1);
            @endphp

            <div class="flex items-end justify-between h-32 md:h-40">
                @foreach($this->chartData['data'] as $day)
                <div class="flex flex-col items-center w-10 md:w-12">
                    <div class="relative group">
                        <div class="w-6 md:w-8 bg-[#FF6B35]/20 rounded-t-lg" style="height: {{ max(4, ($day['users'] / $maxValue) * 80) }}px">
                            <div class="w-full rounded-t-lg bg-[#FF6B35] transition-all"
                                 style="height: {{ ($day['users'] / max($maxValue, 1)) * 100 }}%"></div>
                        </div>
                        <div class="absolute hidden px-2 py-1 mb-2 text-xs text-white transform -translate-x-1/2 bg-gray-900 rounded bottom-full left-1/2 group-hover:block whitespace-nowrap">
                            👥 {{ $day['users'] }} {{ __('neue Benutzer') }}<br>
                            📚 {{ $day['courses'] }} {{ __('neue Kurse') }}
                        </div>
                    </div>
                    <span class="mt-2 text-[10px] md:text-xs text-gray-500">{{ $day['date'] }}</span>
                </div>
                @endforeach
            </div>
            <div class="flex justify-center gap-6 pt-3 mt-4 text-xs text-gray-500 border-t">
                <span class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-[#FF6B35]"></div>
                    {{ __('Neue Benutzer') }}
                </span>
                <span class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-[#FF6B35]/20"></div>
                    {{ __('Neue Kurse') }}
                </span>
            </div>
        </x-card>

        <!-- Two Columns -->
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Recent Users -->
            <x-card class="shadow-sm">
                <div class="flex items-center justify-between pb-2 mb-3 border-b">
                    <div class="flex items-center gap-2">
                        <x-icon name="o-users" class="w-5 h-5 text-blue-500" />
                        <h2 class="font-semibold text-gray-900">{{ __('Neue Benutzer') }}</h2>
                    </div>
                    <a href="{{ route('admin.users') }}" class="text-xs text-[#FF6B35] hover:underline">
                        {{ __('Alle anzeigen') }} →
                    </a>
                </div>

                @if($this->recentUsers->count() > 0)
                    <div class="space-y-2">
                        @foreach($this->recentUsers as $user)
                        <div class="flex items-center justify-between p-2 transition rounded-lg hover:bg-gray-50">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-xs">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ Str::limit($user->name, 20) }}</p>
                                    <p class="text-xs text-gray-400">{{ $user->role }} • {{ $user->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.users.show', $user) }}" class="text-xs text-[#FF6B35] hover:underline">
                                {{ __('Details') }}
                            </a>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-6 text-center">
                        <p class="text-sm text-gray-500">{{ __('Keine neuen Benutzer') }}</p>
                    </div>
                @endif
            </x-card>

            <!-- Top Courses -->
            <x-card class="shadow-sm">
                <div class="flex items-center justify-between pb-2 mb-3 border-b">
                    <div class="flex items-center gap-2">
                        <x-icon name="o-trophy" class="w-5 h-5 text-yellow-500" />
                        <h2 class="font-semibold text-gray-900">{{ __('Beliebteste Kurse') }}</h2>
                    </div>
                    <a href="{{ route('admin.courses') }}" class="text-xs text-[#FF6B35] hover:underline">
                        {{ __('Alle anzeigen') }} →
                    </a>
                </div>

                @if($this->topCourses->count() > 0)
                    <div class="space-y-2">
                        @foreach($this->topCourses as $course)
                        <div class="flex items-center justify-between p-2 transition rounded-lg hover:bg-gray-50">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ Str::limit($course->title, 30) }}</p>
                                <p class="text-xs text-gray-400">{{ $course->teacher->name ?? 'Unbekannt' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-[#FF6B35]">{{ $course->enrollments_count }}</p>
                                <p class="text-xs text-gray-400">{{ __('Einschreibungen') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-6 text-center">
                        <p class="text-sm text-gray-500">{{ __('Keine Kurse vorhanden') }}</p>
                    </div>
                @endif
            </x-card>
        </div>

        <!-- Recent Courses - Version complète corrigée -->
        <div class="mt-6">
            <div class="overflow-hidden bg-white shadow-sm rounded-xl">
            <div class="flex items-center justify-between p-4 border-b bg-gray-50">
                <div class="flex items-center gap-2">
                    <x-icon name="o-academic-cap" class="w-5 h-5 text-green-500" />
                    <h2 class="font-semibold text-gray-900">{{ __('Neue Kurse') }}</h2>
                    <span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600">
                        {{ $this->recentCourses->count() }}
                    </span>
                </div>
                <a href="{{ route('admin.courses') }}" class="text-sm text-[#FF6B35] hover:underline">
                    {{ __('Alle anzeigen') }} →
                </a>
            </div>

            @if($this->recentCourses->count() > 0)
                <!-- Version Desktop -->
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">{{ __('Kurs') }}</th>
                                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">{{ __('Lehrer') }}</th>
                                <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">{{ __('Erstellt am') }}</th>
                                <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">{{ __('Aktion') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($this->recentCourses as $course)
                            <tr class="transition hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ Str::limit($course->title, 40) }}</p>
                                        <p class="text-xs text-gray-400">{{ $course->subject->name ?? 'Allgemein' }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white text-xs font-bold">
                                            {{ strtoupper(substr($course->teacher->name ?? '?', 0, 1)) }}
                                        </div>
                                        <span class="text-sm text-gray-700">{{ $course->teacher->name ?? 'Unbekannt' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($course->is_published)
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs text-green-700 bg-green-100 rounded-full">
                                            <x-icon name="o-check-circle" class="w-3 h-3" />
                                            Veröffentlicht
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs text-yellow-700 bg-yellow-100 rounded-full">
                                            <x-icon name="o-pencil" class="w-3 h-3" />
                                            Entwurf
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    {{ $course->created_at->format('d.m.Y') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('admin.courses.edit', $course) }}"
                                    class="inline-flex items-center gap-1 px-3 py-1 text-sm text-[#FF6B35] border border-[#FF6B35] rounded-lg hover:bg-orange-50 transition">
                                        {{ __('Bearbeiten') }}
                                        <x-icon name="o-arrow-right" class="w-3 h-3" />
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Version Mobile -->
                <div class="divide-y divide-gray-200 md:hidden">
                    @foreach($this->recentCourses as $course)
                    <div class="p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ Str::limit($course->title, 35) }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $course->subject->name ?? 'Allgemein' }}</p>
                            </div>
                            @if($course->is_published)
                                <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">Veröffentlicht</span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-700">Entwurf</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white text-xs font-bold">
                                {{ strtoupper(substr($course->teacher->name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">{{ $course->teacher->name ?? 'Unbekannt' }}</p>
                                <p class="text-xs text-gray-400">{{ $course->created_at->format('d.m.Y') }}</p>
                            </div>
                        </div>

                        <a href="{{ route('admin.courses.edit', $course) }}"
                        class="inline-flex items-center justify-center w-full gap-1 px-3 py-2 text-sm text-[#FF6B35] border border-[#FF6B35] rounded-lg hover:bg-orange-50 transition">
                            {{ __('Kurs bearbeiten') }}
                            <x-icon name="o-arrow-right" class="w-3 h-3" />
                        </a>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="py-10 text-center">
                    <x-icon name="o-academic-cap" class="w-16 h-16 mx-auto mb-3 text-gray-300" />
                    <p class="font-medium text-gray-500">{{ __('Keine Kurse vorhanden') }}</p>
                    <p class="mt-1 text-sm text-gray-400">{{ __('Les nouveaux cours apparaîtront ici.') }}</p>
                </div>
            @endif
            </div>
        </div>

        <!-- Note MVP -->
        <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">MVP Version</p>
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('Prochaines fonctionnalités : analyses avancées, export de rapports, impersonnalisation des utilisateurs et actions groupées.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
