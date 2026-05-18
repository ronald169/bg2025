<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Mary\Traits\Toast;

new
#[Title('Admin Dashboard - German Learning')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    // Getters (remplacent #[Computed])
    public function getStatsProperty()
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

    public function getRevenueProperty()
    {
        // Utilisation de la colonne paid_amount sur la table enrollments
        return round(Enrollment::sum('paid_amount'), 2);
    }

    public function getRecentUsersProperty()
    {
        return User::latest()->take(5)->get();
    }

    public function getRecentCoursesProperty()
    {
        return Course::with('teacher')
            ->latest()
            ->take(5)
            ->get();
    }

    public function getRecentEnrollmentsProperty()
    {
        return Enrollment::with(['user', 'course'])
            ->latest()
            ->take(10)
            ->get();
    }

    public function getChartDataProperty()
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

    public function getTopCoursesProperty()
    {
        return Course::withCount('enrollments')
            ->orderBy('enrollments_count', 'desc')
            ->take(5)
            ->get();
    }

    public function formatNumber($number): string
    {
        if ($number >= 1000000) return round($number / 1000000, 1) . 'M';
        if ($number >= 1000) return round($number / 1000, 1) . 'K';
        return (string)$number;
    }

    public function render()
    {
        return $this->view([
            'stats' => $this->stats,
            'revenue' => $this->revenue,
            'recentUsers' => $this->recentUsers,
            'recentCourses' => $this->recentCourses,
            'recentEnrollments' => $this->recentEnrollments,
            'chartData' => $this->chartData,
            'topCourses' => $this->topCourses,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-xl font-bold md:text-2xl">📊 {{ __('Admin Dashboard') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ __('Welcome back, :name!', ['name' => auth()->user()->name]) }}</p>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-3 lg:grid-cols-6">
            <div class="p-3 text-center border-l-4 rounded-lg shadow-sm bg-base-100 border-l-primary">
                <p class="text-xs text-base-content/70">{{ __('Users') }}</p>
                <p class="text-xl font-bold text-base-content">{{ $this->formatNumber($stats['total_users']) }}</p>
            </div>
            <div class="p-3 text-center border-l-4 rounded-lg shadow-sm bg-base-100 border-l-info">
                <p class="text-xs text-base-content/70">{{ __('Students') }}</p>
                <p class="text-xl font-bold text-info">{{ $this->formatNumber($stats['total_students']) }}</p>
            </div>
            <div class="p-3 text-center border-l-4 rounded-lg shadow-sm bg-base-100 border-l-success">
                <p class="text-xs text-base-content/70">{{ __('Teachers') }}</p>
                <p class="text-xl font-bold text-success">{{ $this->formatNumber($stats['total_teachers']) }}</p>
            </div>
            <div class="p-3 text-center border-l-4 rounded-lg shadow-sm bg-base-100 border-l-secondary">
                <p class="text-xs text-base-content/70">{{ __('Courses') }}</p>
                <p class="text-xl font-bold text-secondary">{{ $this->formatNumber($stats['total_courses']) }}</p>
            </div>
            <div class="p-3 text-center border-l-4 rounded-lg shadow-sm bg-base-100 border-l-warning">
                <p class="text-xs text-base-content/70">{{ __('Enrollments') }}</p>
                <p class="text-xl font-bold text-warning">{{ $this->formatNumber($stats['total_enrollments']) }}</p>
            </div>
            <div class="p-3 text-center border-l-4 rounded-lg shadow-sm bg-base-100 border-l-accent">
                <p class="text-xs text-base-content/70">{{ __('Revenue') }}</p>
                <p class="text-lg font-bold text-accent">€{{ number_format($revenue, 0, ',', ' ') }}</p>
            </div>
        </div>

        {{-- Secondary Stats --}}
        <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-4">
            <div class="p-2 text-center rounded-lg bg-base-200">
                <p class="text-xs text-base-content/70">{{ __('Published') }}</p>
                <p class="text-lg font-bold text-success">{{ $stats['published_courses'] }}</p>
                <p class="text-xs text-base-content/50">of {{ $stats['total_courses'] }}</p>
            </div>
            <div class="p-2 text-center rounded-lg bg-base-200">
                <p class="text-xs text-base-content/70">{{ __('Drafts') }}</p>
                <p class="text-lg font-bold text-warning">{{ $stats['draft_courses'] }}</p>
            </div>
            <div class="p-2 text-center rounded-lg bg-base-200">
                <p class="text-xs text-base-content/70">{{ __('Active enrollments') }}</p>
                <p class="text-lg font-bold text-info">{{ $this->formatNumber($stats['active_enrollments']) }}</p>
            </div>
            <div class="p-2 text-center rounded-lg bg-base-200">
                <p class="text-xs text-base-content/70">{{ __('Avg rating') }}</p>
                <p class="text-lg font-bold text-warning">{{ $stats['avg_rating'] }} ★</p>
            </div>
        </div>

        {{-- Pending reviews alert --}}
        @if($stats['pending_reviews'] > 0)
            <div class="p-3 mb-6 border rounded-lg border-warning/20 bg-warning/10">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <x-icon name="o-star" class="w-5 h-5 text-warning" />
                        <div>
                            <p class="text-sm font-medium text-warning">{{ __('Pending reviews') }}</p>
                            <p class="text-xs text-warning/80">{{ $stats['pending_reviews'] }} {{ __('reviews need approval') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.reviews') }}" class="text-sm text-warning hover:underline">{{ __('Review') }} →</a>
                </div>
            </div>
        @endif

        {{-- Chart Section --}}
        <x-card class="mb-6 shadow-sm">
            <div class="flex items-center justify-between pb-2 mb-4 border-b">
                <div class="flex items-center gap-2">
                    <x-icon name="o-chart-bar" class="w-5 h-5 text-primary" />
                    <h2 class="font-semibold">{{ __('Weekly Activity') }}</h2>
                </div>
            </div>

            @php
                $maxValue = max($chartData['max_users'], $chartData['max_courses'], 1);
            @endphp

            <div class="flex items-end justify-between h-32 md:h-40">
                @foreach($chartData['data'] as $day)
                    <div class="flex flex-col items-center w-10 md:w-12">
                        <div class="relative group">
                            <div class="w-6 rounded-t-lg md:w-8 bg-primary/20" style="height: {{ max(4, ($day['users'] / $maxValue) * 80) }}px">
                                <div class="w-full transition-all rounded-t-lg bg-primary" style="height: {{ ($day['users'] / max($maxValue, 1)) * 100 }}%"></div>
                            </div>
                            <div class="absolute hidden px-2 py-1 mb-2 text-xs text-white transform -translate-x-1/2 rounded bg-base-100 bottom-full left-1/2 group-hover:block whitespace-nowrap">
                                👥 {{ $day['users'] }} {{ __('new users') }}<br>
                                📚 {{ $day['courses'] }} {{ __('new courses') }}
                            </div>
                        </div>
                        <span class="mt-2 text-[10px] md:text-xs text-base-content/60">{{ $day['date'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-center gap-6 pt-3 mt-4 text-xs border-t text-base-content/60">
                <span class="flex items-center gap-1"><div class="w-3 h-3 rounded-full bg-primary"></div>{{ __('New users') }}</span>
                <span class="flex items-center gap-1"><div class="w-3 h-3 rounded-full bg-primary/20"></div>{{ __('New courses') }}</span>
            </div>
        </x-card>

        {{-- Two columns: Recent Users & Top Courses --}}
        <div class="grid gap-6 md:grid-cols-2">
            {{-- Recent Users --}}
            <x-card class="shadow-sm">
                <div class="flex items-center justify-between pb-2 mb-3 border-b">
                    <div class="flex items-center gap-2">
                        <x-icon name="o-users" class="w-5 h-5 text-info" />
                        <h2 class="font-semibold">{{ __('Recent Users') }}</h2>
                    </div>
                    <a href="{{ route('admin.users') }}" class="text-xs text-primary hover:underline">{{ __('All users') }} →</a>
                </div>
                @if($recentUsers->count() > 0)
                    <div class="space-y-2">
                        @foreach($recentUsers as $user)
                            <div class="flex items-center justify-between p-2 transition rounded-lg hover:bg-base-200">
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center justify-center w-8 h-8 text-xs font-bold text-white rounded-full bg-gradient-to-r from-primary to-secondary">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">{{ Str::limit($user->name, 20) }}</p>
                                        <p class="text-xs text-base-content/60">{{ $user->role }} • {{ $user->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <a href="{{ route('admin.users.show', $user) }}" class="text-xs text-primary hover:underline">{{ __('View') }}</a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-6 text-center"><p class="text-sm text-base-content/60">{{ __('No recent users') }}</p></div>
                @endif
            </x-card>

            {{-- Top Courses --}}
            <x-card class="shadow-sm">
                <div class="flex items-center justify-between pb-2 mb-3 border-b">
                    <div class="flex items-center gap-2">
                        <x-icon name="o-trophy" class="w-5 h-5 text-warning" />
                        <h2 class="font-semibold">{{ __('Top Courses') }}</h2>
                    </div>
                    <a href="{{ route('admin.courses') }}" class="text-xs text-primary hover:underline">{{ __('All courses') }} →</a>
                </div>
                @if($topCourses->count() > 0)
                    <div class="space-y-2">
                        @foreach($topCourses as $course)
                            <div class="flex items-center justify-between p-2 transition rounded-lg hover:bg-base-200">
                                <div class="flex-1">
                                    <p class="text-sm font-medium">{{ Str::limit($course->title, 30) }}</p>
                                    <p class="text-xs text-base-content/60">{{ $course->teacher->name ?? __('Unknown') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-primary">{{ $course->enrollments_count }}</p>
                                    <p class="text-xs text-base-content/60">{{ __('enrollments') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-6 text-center"><p class="text-sm text-base-content/60">{{ __('No courses yet') }}</p></div>
                @endif
            </x-card>
        </div>

        {{-- Recent Courses (full table) --}}
        <div class="mt-6">
            <x-card class="shadow-sm">
                <div class="flex items-center justify-between pb-2 mb-3 border-b">
                    <div class="flex items-center gap-2">
                        <x-icon name="o-academic-cap" class="w-5 h-5 text-success" />
                        <h2 class="font-semibold">{{ __('Recent Courses') }}</h2>
                        <x-badge :value="$recentCourses->count()" class="badge-soft badge-neutral" />
                    </div>
                    <a href="{{ route('admin.courses') }}" class="text-xs text-primary hover:underline">{{ __('All courses') }} →</a>
                </div>

                @if($recentCourses->count() > 0)
                    {{-- Desktop table --}}
                    <div class="hidden overflow-x-auto md:block">
                        <table class="w-full">
                            <thead class="bg-base-200">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left uppercase">{{ __('Course') }}</th>
                                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left uppercase">{{ __('Teacher') }}</th>
                                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center uppercase">{{ __('Status') }}</th>
                                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left uppercase">{{ __('Created') }}</th>
                                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center uppercase">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base-200">
                                @foreach($recentCourses as $course)
                                    <tr class="transition hover:bg-base-200">
                                        <td class="px-4 py-3">
                                            <div>
                                                <p class="font-medium">{{ Str::limit($course->title, 40) }}</p>
                                                <p class="text-xs text-base-content/60">{{ $course->subject->name ?? __('General') }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <div class="flex items-center justify-center w-8 h-8 text-xs font-bold text-white rounded-full bg-gradient-to-r from-primary to-secondary">
                                                    {{ strtoupper(substr($course->teacher->name ?? '?', 0, 1)) }}
                                                </div>
                                                <span class="text-sm">{{ $course->teacher->name ?? __('Unknown') }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($course->is_published)
                                                <x-badge value="{{ __('Published') }}" icon="o-check-circle" class="badge-success badge-soft" />
                                            @else
                                                <x-badge value="{{ __('Draft') }}" icon="o-pencil" class="badge-warning badge-soft" />
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-base-content/60">{{ $course->created_at->format('d.m.Y') }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <a href="{{ route('admin.courses.edit', $course) }}" class="inline-flex items-center gap-1 px-3 py-1 text-sm transition border rounded-lg text-primary border-primary hover:bg-primary/10">
                                                {{ __('Edit') }} <x-icon name="o-arrow-right" class="w-3 h-3" />
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile view --}}
                    <div class="divide-y divide-base-200 md:hidden">
                        @foreach($recentCourses as $course)
                            <div class="p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h3 class="font-semibold">{{ Str::limit($course->title, 35) }}</h3>
                                        <p class="text-xs text-base-content/60 mt-0.5">{{ $course->subject->name ?? __('General') }}</p>
                                    </div>
                                    @if($course->is_published)
                                        <x-badge value="{{ __('Published') }}" class="badge-success badge-soft" />
                                    @else
                                        <x-badge value="{{ __('Draft') }}" class="badge-warning badge-soft" />
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="flex items-center justify-center w-8 h-8 text-xs font-bold text-white rounded-full bg-gradient-to-r from-primary to-secondary">
                                        {{ strtoupper(substr($course->teacher->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">{{ $course->teacher->name ?? __('Unknown') }}</p>
                                        <p class="text-xs text-base-content/60">{{ $course->created_at->format('d.m.Y') }}</p>
                                    </div>
                                </div>
                                <a href="{{ route('admin.courses.edit', $course) }}" class="inline-flex items-center justify-center w-full gap-1 px-3 py-2 text-sm transition border rounded-lg text-primary border-primary hover:bg-primary/10">
                                    {{ __('Edit course') }} <x-icon name="o-arrow-right" class="w-3 h-3" />
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-10 text-center">
                        <x-icon name="o-academic-cap" class="w-16 h-16 mx-auto mb-3 text-base-content/30" />
                        <p class="font-medium text-base-content/70">{{ __('No courses yet') }}</p>
                        <p class="mt-1 text-sm text-base-content/60">{{ __('New courses will appear here.') }}</p>
                    </div>
                @endif
            </x-card>
        </div>

    </div>
</div>
