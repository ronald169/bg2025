<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Review;
use App\Models\QuizAttempt;
use App\Models\StudySession;
use Illuminate\Support\Facades\DB;
use Mary\Traits\Toast;

new
#[Title('Reports & Analytics - Admin')]
#[Layout('layouts.app')]
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

    // Getters pour chaque rapport
    public function getReportDataProperty()
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
        $this->info(__('Export feature will be available soon! 📊'));
    }

    public function formatStudyTime($minutes): string
    {
        if ($minutes < 60) return "{$minutes} min";
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
    }

    public function render()
    {
        return $this->view([
            'reportData' => $this->reportData,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">📊 {{ __('Reports & Analytics') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('Platform statistics and insights') }}</p>
            </div>
            <x-button wire:click="export" label="{{ __('Export') }}" icon="o-arrow-down-tray" class="btn-outline" />
        </div>

        {{-- Report Type Tabs --}}
        <div class="mb-6 tabs tabs-boxed">
            <a class="tab {{ $reportType === 'users' ? 'tab-active' : '' }}" wire:click="$set('reportType', 'users')">👥 {{ __('Users') }}</a>
            <a class="tab {{ $reportType === 'courses' ? 'tab-active' : '' }}" wire:click="$set('reportType', 'courses')">📚 {{ __('Courses') }}</a>
            <a class="tab {{ $reportType === 'revenue' ? 'tab-active' : '' }}" wire:click="$set('reportType', 'revenue')">💰 {{ __('Revenue') }}</a>
            <a class="tab {{ $reportType === 'engagement' ? 'tab-active' : '' }}" wire:click="$set('reportType', 'engagement')">⭐ {{ __('Engagement') }}</a>
        </div>

        {{-- Users Report --}}
        @if($reportType === 'users')
            <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-3 lg:grid-cols-7">
                <x-stat title="{{ __('Total') }}" :value="number_format($reportData['total'] ?? 0)" icon="o-users" class="text-primary" />
                <x-stat title="{{ __('Students') }}" :value="number_format($reportData['students'] ?? 0)" icon="o-academic-cap" class="text-info" />
                <x-stat title="{{ __('Teachers') }}" :value="number_format($reportData['teachers'] ?? 0)" icon="o-user-group" class="text-success" />
                <x-stat title="{{ __('Admins') }}" :value="number_format($reportData['admins'] ?? 0)" icon="o-shield-check" class="text-error" />
                <x-stat title="{{ __('New this month') }}" :value="number_format($reportData['new_this_month'] ?? 0)" icon="o-plus-circle" class="text-warning" />
                <x-stat title="{{ __('Active') }}" :value="number_format($reportData['active'] ?? 0)" icon="o-check-circle" class="text-success" />
                <x-stat title="{{ __('Growth') }}" :value="($reportData['growth_rate'] ?? 0) . '%'" icon="o-chart-bar" class="text-secondary" />
            </div>

            <x-card class="shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-chart-bar" class="w-5 h-5 text-primary" />
                    <h2 class="font-semibold">{{ __('User growth (last 12 months)') }}</h2>
                </div>
                <div class="space-y-3">
                    @foreach($reportData['chart']['data'] ?? [] as $item)
                        @php $percentage = ($reportData['chart']['max'] > 0) ? ($item['count'] / $reportData['chart']['max']) * 100 : 0; @endphp
                        <div>
                            <div class="flex justify-between mb-1 text-sm">
                                <span class="text-base-content/70">{{ $item['month'] }}</span>
                                <span class="font-semibold text-primary">{{ number_format($item['count']) }}</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-base-200">
                                <div class="h-2 rounded-full bg-primary" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

        {{-- Courses Report --}}
        @elseif($reportType === 'courses')
            <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-4 lg:grid-cols-6">
                <x-stat title="{{ __('Total') }}" :value="number_format($reportData['total'] ?? 0)" icon="o-book-open" class="text-primary" />
                <x-stat title="{{ __('Published') }}" :value="number_format($reportData['published'] ?? 0)" icon="o-check-circle" class="text-success" />
                <x-stat title="{{ __('Drafts') }}" :value="number_format($reportData['draft'] ?? 0)" icon="o-pencil" class="text-warning" />
                <x-stat title="{{ __('Featured') }}" :value="number_format($reportData['featured'] ?? 0)" icon="o-star" class="text-secondary" />
                <x-stat title="{{ __('Avg enrollments') }}" :value="number_format($reportData['avg_enrollments'] ?? 0, 1)" icon="o-users" class="text-info" />
                <x-stat title="{{ __('Completion rate') }}" :value="($reportData['completion_rate'] ?? 0) . '%'" icon="o-trophy" class="text-accent" />
            </div>

            {{-- Courses by Level --}}
            <x-card class="mb-6 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-chart-bar" class="w-5 h-5 text-primary" />
                    <h2 class="font-semibold">{{ __('Courses by level') }}</h2>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-6">
                    @foreach($reportData['by_level'] ?? [] as $level)
                        <div class="p-3 text-center rounded-lg bg-base-200">
                            <p class="text-lg font-bold text-primary">{{ $level['level'] }}</p>
                            <p class="text-sm text-base-content/70">{{ $level['count'] }} {{ __('courses') }}</p>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <x-card class="shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-trophy" class="w-5 h-5 text-warning" />
                    <h2 class="font-semibold">{{ __('Top 5 courses') }}</h2>
                </div>
                @if(count($reportData['top_courses'] ?? []) > 0)
                    <div class="space-y-3">
                        @foreach($reportData['top_courses'] as $index => $course)
                            <div class="flex items-center justify-between p-3 border rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-8 h-8 font-bold rounded-full bg-primary/20 text-primary">{{ $index + 1 }}</div>
                                    <div>
                                        <p class="font-medium">{{ Str::limit($course->title, 35) }}</p>
                                        <p class="text-xs text-base-content/60">{{ $course->teacher->name ?? __('Unknown') }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-primary">{{ $course->enrollments_count }}</p>
                                    <p class="text-xs text-base-content/60">{{ __('enrollments') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="py-4 text-center text-base-content/60">{{ __('No courses available') }}</p>
                @endif
            </x-card>

        {{-- Revenue Report --}}
        @elseif($reportType === 'revenue')
            <div class="grid grid-cols-1 gap-3 mb-6 sm:grid-cols-3">
                <x-stat title="{{ __('Total revenue') }}" :value="'€' . number_format($reportData['total'] ?? 0, 0, ',', ' ')" icon="o-currency-euro" class="text-success" />
                <x-stat title="{{ __('This month') }}" :value="'€' . number_format($reportData['this_month'] ?? 0, 0, ',', ' ')" icon="o-calendar" class="text-primary" />
                <x-stat title="{{ __('Average per course') }}" :value="'€' . number_format($reportData['avg_per_course'] ?? 0, 0, ',', ' ')" icon="o-academic-cap" class="text-info" />
            </div>

            <x-card class="shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-chart-bar" class="w-5 h-5 text-success" />
                    <h2 class="font-semibold">{{ __('Revenue trend (last 12 months)') }}</h2>
                </div>
                <div class="space-y-3">
                    @foreach($reportData['chart']['data'] ?? [] as $item)
                        @php $percentage = ($reportData['chart']['max'] > 0) ? ($item['amount'] / $reportData['chart']['max']) * 100 : 0; @endphp
                        <div>
                            <div class="flex justify-between mb-1 text-sm">
                                <span class="text-base-content/70">{{ $item['month'] }}</span>
                                <span class="font-semibold text-success">€{{ number_format($item['amount'], 0, ',', ' ') }}</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-base-200">
                                <div class="h-2 rounded-full bg-success" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

        {{-- Engagement Report --}}
        @elseif($reportType === 'engagement')
            <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-4">
                <x-stat title="{{ __('Reviews') }}" :value="number_format($reportData['total_reviews'] ?? 0)" icon="o-star" class="text-warning" />
                <x-stat title="{{ __('Avg rating') }}" :value="($reportData['avg_rating'] ?? 0) . ' ★'" icon="o-star" class="text-primary" />
                <x-stat title="{{ __('Quiz attempts') }}" :value="number_format($reportData['total_quizzes'] ?? 0)" icon="o-document-text" class="text-secondary" />
                <x-stat title="{{ __('Study time') }}" :value="$this->formatStudyTime($reportData['total_study_time'] ?? 0)" icon="o-clock" class="text-info" />
            </div>

            <x-card class="shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-star" class="w-5 h-5 text-warning" />
                    <h2 class="font-semibold">{{ __('Rating distribution') }}</h2>
                </div>
                <div class="space-y-2">
                    @foreach($reportData['rating_distribution'] ?? [] as $rating => $count)
                        @php $total = $reportData['total_reviews'] ?? 1; $percentage = ($count / max($total, 1)) * 100; @endphp
                        <div class="flex items-center gap-2">
                            <div class="w-12 text-sm font-medium text-base-content/70">{{ $rating }} ★</div>
                            <div class="flex-1 h-2 rounded-full bg-base-200">
                                <div class="h-2 rounded-full bg-warning" style="width: {{ $percentage }}%"></div>
                            </div>
                            <div class="w-12 text-sm text-base-content/60">{{ $count }}</div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif
    </div>
</div>
