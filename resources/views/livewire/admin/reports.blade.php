<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Review;
use App\Models\QuizAttempt;
use App\Models\StudySession;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Mary\Traits\Toast;

new
#[Title('Berichte & Analysen - Admin')]
#[Layout('components.layouts.dashboard-admin')]
class extends Component {
    use Toast;

    #[Url(as: 'type', history: true)]
    public string $reportType = 'users';

    public string $dateRange = 'month';
    public string $startDate = '';
    public string $endDate = '';

    public function mount(): void
    {
        $this->startDate = now()->subMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    #[Computed]
    public function reportData()
    {
        return match($this->reportType) {
            'users' => $this->getUsersReport(),
            'courses' => $this->getCoursesReport(),
            'revenue' => $this->getRevenueReport(),
            'engagement' => $this->getEngagementReport(),
            default => [],
        };
    }

    private function getUsersReport(): array
    {
        $totalUsers = User::count();
        $students = User::where('role', 'student')->count();
        $teachers = User::where('role', 'teacher')->count();
        $admins = User::where('role', 'admin')->count();

        return [
            'total' => $totalUsers,
            'students' => $students,
            'teachers' => $teachers,
            'admins' => $admins,
            'new_this_month' => User::whereBetween('created_at', [now()->startOfMonth(), now()])->count(),
            'active' => User::where('status', 'active')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
            'pending' => User::where('status', 'pending')->count(),
            'chart' => $this->getUserChartData(),
            'growth_rate' => $this->calculateGrowthRate(User::class),
        ];
    }

    private function getUserChartData(): array
    {
        $data = [];
        $maxCount = 0;

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = User::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $data[] = [
                'month' => $date->translatedFormat('M Y'),
                'count' => $count,
            ];
            if ($count > $maxCount) $maxCount = $count;
        }

        return ['data' => $data, 'max' => $maxCount];
    }

    private function getCoursesReport(): array
    {
        $totalCourses = Course::count();
        $published = Course::where('is_published', true)->count();
        $draft = Course::where('is_published', false)->count();
        $featured = Course::where('is_featured', true)->count();
        $totalEnrollments = Enrollment::count();

        return [
            'total' => $totalCourses,
            'published' => $published,
            'draft' => $draft,
            'featured' => $featured,
            'total_enrollments' => $totalEnrollments,
            'avg_enrollments' => $totalCourses > 0 ? round($totalEnrollments / $totalCourses, 1) : 0,
            'avg_progress' => round(Enrollment::avg('progress') ?? 0, 1),
            'completion_rate' => $totalEnrollments > 0 ? round((Enrollment::where('status', 'completed')->count() / $totalEnrollments) * 100, 1) : 0,
            'top_courses' => Course::with('teacher')
                ->withCount('enrollments')
                ->orderBy('enrollments_count', 'desc')
                ->take(5)
                ->get(),
            'by_level' => $this->getCoursesByLevel(),
        ];
    }

    private function getCoursesByLevel(): array
    {
        $levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $data = [];

        foreach ($levels as $level) {
            $data[] = [
                'level' => $level,
                'count' => Course::where('level', $level)->count(),
            ];
        }

        return $data;
    }

    private function getRevenueReport(): array
    {
        $totalRevenue = Enrollment::where('paid_amount', '>', 0)->sum('paid_amount');
        $thisMonthRevenue = Enrollment::whereBetween('created_at', [now()->startOfMonth(), now()])
            ->where('paid_amount', '>', 0)
            ->sum('paid_amount');

        return [
            'total' => $totalRevenue,
            'this_month' => $thisMonthRevenue,
            'avg_per_course' => Course::count() > 0 ? round($totalRevenue / Course::count(), 2) : 0,
            'avg_per_student' => User::where('role', 'student')->count() > 0
                ? round($totalRevenue / User::where('role', 'student')->count(), 2)
                : 0,
            'chart' => $this->getRevenueChartData(),
        ];
    }

    private function getRevenueChartData(): array
    {
        $data = [];
        $maxAmount = 0;

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $amount = Enrollment::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->where('paid_amount', '>', 0)
                ->sum('paid_amount');
            $data[] = [
                'month' => $date->translatedFormat('M Y'),
                'amount' => $amount,
            ];
            if ($amount > $maxAmount) $maxAmount = $amount;
        }

        return ['data' => $data, 'max' => $maxAmount];
    }

    private function getEngagementReport(): array
    {
        $totalReviews = Review::count();
        $avgRating = round(Review::avg('rating') ?? 0, 1);

        // Calculer la moyenne des scores de quiz sans utiliser score_percentage
        $quizAttempts = QuizAttempt::with('quiz.questions')->get();
        $totalPercentage = 0;
        $attemptCount = 0;

        foreach ($quizAttempts as $attempt) {
            $quiz = $attempt->quiz;
            if ($quiz && $quiz->questions) {
                $totalPoints = $quiz->questions->sum('points');
                if ($totalPoints > 0) {
                    $percentage = round(($attempt->score / $totalPoints) * 100);
                    $totalPercentage += $percentage;
                    $attemptCount++;
                }
            }
        }

        $avgQuizScore = $attemptCount > 0 ? round($totalPercentage / $attemptCount, 1) : 0;

        return [
            'total_reviews' => $totalReviews,
            'avg_rating' => $avgRating,
            'pending_reviews' => Review::where('is_approved', false)->count(),
            'total_quizzes' => QuizAttempt::count(),
            'avg_quiz_score' => $avgQuizScore,
            'total_study_time' => StudySession::sum('duration_minutes'),
            'active_students' => User::where('role', 'student')->where('status', 'active')->count(),
            'rating_distribution' => $this->getRatingDistribution(),
        ];
    }

    private function getRatingDistribution(): array
    {
        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $distribution[$i] = Review::where('rating', $i)->count();
        }
        return $distribution;
    }

    private function calculateGrowthRate($model): float
    {
        $lastMonth = $model::whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->count();
        $thisMonth = $model::whereBetween('created_at', [now()->startOfMonth(), now()])->count();

        if ($lastMonth == 0) return $thisMonth > 0 ? 100 : 0;
        return round(($thisMonth - $lastMonth) / $lastMonth * 100, 1);
    }

    public function export(): void
    {
        $this->toast(
            type: 'info',
            title: 'Export-Funktion wird in Kürze verfügbar sein! 📊',
            position: 'toast-top toast-end',
            timeout: 3000
        );
    }

    public function getFormattedStudyTime($minutes): string
    {
        if ($minutes < 60) return "{$minutes} min";
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
    }
}
?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        <!-- Header -->
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">📊 {{ __('Berichte & Analysen') }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">{{ __('Plattform-Statistiken und Einblicke') }}</p>
            </div>
            <x-button wire:click="export" icon="o-arrow-down-tray" class="btn-outline">
                {{ __('Exportieren') }}
            </x-button>
        </div>

        <!-- Report Type Tabs - Responsive -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="flex flex-wrap gap-2 sm:gap-4">
                <button wire:click="$set('reportType', 'users')"
                        class="px-3 py-2 text-sm font-medium rounded-lg transition
                               {{ $reportType === 'users' ? 'bg-[#FF6B35] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}">
                    👥 {{ __('Benutzer') }}
                </button>
                <button wire:click="$set('reportType', 'courses')"
                        class="px-3 py-2 text-sm font-medium rounded-lg transition
                               {{ $reportType === 'courses' ? 'bg-[#FF6B35] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}">
                    📚 {{ __('Kurse') }}
                </button>
                <button wire:click="$set('reportType', 'revenue')"
                        class="px-3 py-2 text-sm font-medium rounded-lg transition
                               {{ $reportType === 'revenue' ? 'bg-[#FF6B35] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}">
                    💰 {{ __('Umsatz') }}
                </button>
                <button wire:click="$set('reportType', 'engagement')"
                        class="px-3 py-2 text-sm font-medium rounded-lg transition
                               {{ $reportType === 'engagement' ? 'bg-[#FF6B35] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}">
                    ⭐ {{ __('Engagement') }}
                </button>
            </nav>
        </div>

        <!-- Users Report -->
        @if($reportType === 'users')
        <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-3 lg:grid-cols-7">
            <div class="bg-white rounded-lg p-3 text-center shadow-sm border-l-4 border-l-[#FF6B35]">
                <p class="text-xs text-gray-500">{{ __('Gesamt') }}</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format($this->reportData['total'] ?? 0) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-blue-500">
                <p class="text-xs text-gray-500">{{ __('Studenten') }}</p>
                <p class="text-xl font-bold text-blue-600">{{ number_format($this->reportData['students'] ?? 0) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-green-500">
                <p class="text-xs text-gray-500">{{ __('Lehrer') }}</p>
                <p class="text-xl font-bold text-green-600">{{ number_format($this->reportData['teachers'] ?? 0) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-purple-500">
                <p class="text-xs text-gray-500">{{ __('Admins') }}</p>
                <p class="text-xl font-bold text-purple-600">{{ number_format($this->reportData['admins'] ?? 0) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-yellow-500">
                <p class="text-xs text-gray-500">{{ __('Neu diesen Monat') }}</p>
                <p class="text-xl font-bold text-yellow-600">{{ number_format($this->reportData['new_this_month'] ?? 0) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-green-500">
                <p class="text-xs text-gray-500">{{ __('Aktiv') }}</p>
                <p class="text-xl font-bold text-green-600">{{ number_format($this->reportData['active'] ?? 0) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-gray-500">
                <p class="text-xs text-gray-500">{{ __('Wachstum') }}</p>
                <p class="text-xl font-bold text-[#FF6B35]">{{ $this->reportData['growth_rate'] ?? 0 }}%</p>
            </div>
        </div>

        <x-card class="shadow-sm">
            <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                <x-icon name="o-chart-bar" class="w-5 h-5 text-[#FF6B35]" />
                <h2 class="font-semibold text-gray-900">{{ __('Benutzerwachstum (letzte 12 Monate)') }}</h2>
            </div>
            <div class="space-y-3">
                @foreach($this->reportData['chart']['data'] ?? [] as $item)
                @php $percentage = $this->reportData['chart']['max'] > 0 ? ($item['count'] / $this->reportData['chart']['max']) * 100 : 0; @endphp
                <div>
                    <div class="flex justify-between mb-1 text-sm">
                        <span class="text-gray-600">{{ $item['month'] }}</span>
                        <span class="font-semibold text-[#FF6B35]">{{ number_format($item['count']) }}</span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 rounded-full">
                        <div class="h-2 rounded-full bg-[#FF6B35]" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </x-card>

        <!-- Courses Report -->
        @elseif($reportType === 'courses')
        <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-4 lg:grid-cols-6">
            <div class="bg-white rounded-lg p-3 text-center shadow-sm border-l-4 border-l-[#FF6B35]">
                <p class="text-xs text-gray-500">{{ __('Gesamt') }}</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format($this->reportData['total'] ?? 0) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-green-500">
                <p class="text-xs text-gray-500">{{ __('Veröffentlicht') }}</p>
                <p class="text-xl font-bold text-green-600">{{ number_format($this->reportData['published'] ?? 0) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-yellow-500">
                <p class="text-xs text-gray-500">{{ __('Entwürfe') }}</p>
                <p class="text-xl font-bold text-yellow-600">{{ number_format($this->reportData['draft'] ?? 0) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-purple-500">
                <p class="text-xs text-gray-500">{{ __('Empfohlen') }}</p>
                <p class="text-xl font-bold text-purple-600">{{ number_format($this->reportData['featured'] ?? 0) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-blue-500">
                <p class="text-xs text-gray-500">{{ __('Ø Einschreibungen') }}</p>
                <p class="text-xl font-bold text-blue-600">{{ number_format($this->reportData['avg_enrollments'] ?? 0) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-teal-500">
                <p class="text-xs text-gray-500">{{ __('Abschlussrate') }}</p>
                <p class="text-xl font-bold text-teal-600">{{ $this->reportData['completion_rate'] ?? 0 }}%</p>
            </div>
        </div>

        <!-- Courses by Level -->
        <x-card class="mb-6 shadow-sm">
            <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                <x-icon name="o-chart-bar" class="w-5 h-5 text-[#FF6B35]" />
                <h2 class="font-semibold text-gray-900">{{ __('Kurse nach Niveau') }}</h2>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-6">
                @foreach($this->reportData['by_level'] ?? [] as $level)
                <div class="p-3 text-center rounded-lg bg-gray-50">
                    <p class="text-lg font-bold text-[#FF6B35]">{{ $level['level'] }}</p>
                    <p class="text-sm text-gray-600">{{ $level['count'] }} Kurse</p>
                </div>
                @endforeach
            </div>
        </x-card>

        <x-card class="shadow-sm">
            <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                <x-icon name="o-trophy" class="w-5 h-5 text-yellow-500" />
                <h2 class="font-semibold text-gray-900">{{ __('Top 5 Kurse') }}</h2>
            </div>
            @if(count($this->reportData['top_courses'] ?? []) > 0)
                <div class="space-y-3">
                    @foreach($this->reportData['top_courses'] as $index => $course)
                    <div class="flex items-center justify-between p-3 border rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-[#FF6B35] font-bold">
                                {{ $index + 1 }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ Str::limit($course->title, 35) }}</p>
                                <p class="text-xs text-gray-500">{{ $course->teacher->name ?? 'Unbekannt' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-[#FF6B35]">{{ $course->enrollments_count }}</p>
                            <p class="text-xs text-gray-500">{{ __('Einschreibungen') }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="py-4 text-center text-gray-500">{{ __('Keine Kurse vorhanden') }}</p>
            @endif
        </x-card>

        <!-- Revenue Report -->
        @elseif($reportType === 'revenue')
        <div class="grid grid-cols-1 gap-3 mb-6 sm:grid-cols-3">
            <div class="p-4 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-green-500">
                <p class="text-xs text-gray-500">{{ __('Gesamtumsatz') }}</p>
                <p class="text-2xl font-bold text-green-600">€{{ number_format($this->reportData['total'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg p-4 text-center shadow-sm border-l-4 border-l-[#FF6B35]">
                <p class="text-xs text-gray-500">{{ __('Dieser Monat') }}</p>
                <p class="text-2xl font-bold text-[#FF6B35]">€{{ number_format($this->reportData['this_month'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="p-4 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-blue-500">
                <p class="text-xs text-gray-500">{{ __('Ø pro Kurs') }}</p>
                <p class="text-2xl font-bold text-blue-600">€{{ number_format($this->reportData['avg_per_course'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>

        <x-card class="shadow-sm">
            <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                <x-icon name="o-chart-bar" class="w-5 h-5 text-[#FF6B35]" />
                <h2 class="font-semibold text-gray-900">{{ __('Umsatzentwicklung (letzte 12 Monate)') }}</h2>
            </div>
            <div class="space-y-3">
                @foreach($this->reportData['chart']['data'] ?? [] as $item)
                @php $percentage = $this->reportData['chart']['max'] > 0 ? ($item['amount'] / $this->reportData['chart']['max']) * 100 : 0; @endphp
                <div>
                    <div class="flex justify-between mb-1 text-sm">
                        <span class="text-gray-600">{{ $item['month'] }}</span>
                        <span class="font-semibold text-green-600">€{{ number_format($item['amount'], 0, ',', '.') }}</span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 rounded-full">
                        <div class="h-2 bg-green-500 rounded-full" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </x-card>

        <!-- Engagement Report -->
        @elseif($reportType === 'engagement')
        <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-4">
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-yellow-500">
                <p class="text-xs text-gray-500">{{ __('Bewertungen') }}</p>
                <p class="text-xl font-bold text-yellow-600">{{ number_format($this->reportData['total_reviews'] ?? 0) }}</p>
            </div>
            <div class="bg-white rounded-lg p-3 text-center shadow-sm border-l-4 border-l-[#FF6B35]">
                <p class="text-xs text-gray-500">{{ __('Ø Bewertung') }}</p>
                <p class="text-xl font-bold text-[#FF6B35]">{{ $this->reportData['avg_rating'] ?? 0 }} ★</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-purple-500">
                <p class="text-xs text-gray-500">{{ __('Quiz-Versuche') }}</p>
                <p class="text-xl font-bold text-purple-600">{{ number_format($this->reportData['total_quizzes'] ?? 0) }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-blue-500">
                <p class="text-xs text-gray-500">{{ __('Lernzeit') }}</p>
                <p class="text-lg font-bold text-blue-600">{{ $this->getFormattedStudyTime($this->reportData['total_study_time'] ?? 0) }}</p>
            </div>
        </div>

        <!-- Rating Distribution -->
        <x-card class="shadow-sm">
            <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                <x-icon name="o-star" class="w-5 h-5 text-yellow-500" />
                <h2 class="font-semibold text-gray-900">{{ __('Bewertungsverteilung') }}</h2>
            </div>
            <div class="space-y-2">
                @foreach($this->reportData['rating_distribution'] ?? [] as $rating => $count)
                    @php $total = $this->reportData['total_reviews'] ?? 1; $percentage = ($count / max($total, 1)) * 100; @endphp
                    <div class="flex items-center gap-2">
                        <div class="w-12 text-sm font-medium text-gray-600">{{ $rating }} ★</div>
                        <div class="flex-1 h-2 bg-gray-200 rounded-full">
                            <div class="h-2 bg-yellow-400 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                        <div class="w-12 text-sm text-gray-500">{{ $count }}</div>
                    </div>
                @endforeach
            </div>
        </x-card>
        @endif

        <!-- Note MVP -->
        <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">MVP Version</p>
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('Prochaines fonctionnalités : export PDF/Excel, filtres personnalisés, graphiques interactifs et analyses avancées.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
