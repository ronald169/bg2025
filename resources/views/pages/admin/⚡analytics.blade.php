<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\StudySession;
use Illuminate\Support\Facades\DB;
use Mary\Traits\Toast;

new
#[Title('Analytics - Admin')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    // Période (mois courant par défaut)
    public string $period = 'month';

    // Données globales
    public function getTotalStudentsProperty()
    {
        return User::where('role', 'student')->count();
    }

    public function getActiveStudentsProperty()
    {
        // étudiants avec au moins une session d’étude ce mois
        return StudySession::whereMonth('date', now()->month)
            ->distinct('user_id')
            ->count('user_id');
    }

    public function getNewStudentsThisMonthProperty()
    {
        return User::where('role', 'student')
            ->whereMonth('created_at', now()->month)
            ->count();
    }

    public function getTotalCoursesProperty()
    {
        return Course::where('is_published', true)->count();
    }

    public function getTotalEnrollmentsProperty()
    {
        return Enrollment::count();
    }

    public function getCompletionRateProperty()
    {
        $total = Enrollment::count();
        if ($total === 0) return 0;
        $completed = Enrollment::where('status', 'completed')->count();
        return round(($completed / $total) * 100, 1);
    }

    public function getAvgQuizScoreProperty()
    {
        return round(QuizAttempt::avg('score') ?? 0, 1);
    }

    public function getTotalStudyMinutesProperty()
    {
        return StudySession::sum('duration_minutes');
    }

    // Graphique : inscriptions par mois (derniers 12 mois)
    public function getMonthlyEnrollmentsProperty()
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = Enrollment::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $data[] = [
                'month' => $date->translatedFormat('M Y'),
                'count' => $count,
            ];
        }
        return $data;
    }

    // Graphique : top 5 cours par inscriptions
    public function getTopCoursesProperty()
    {
        return Course::withCount('enrollments')
            ->orderBy('enrollments_count', 'desc')
            ->take(5)
            ->get()
            ->map(fn($c) => [
                'title' => $c->title,
                'count' => $c->enrollments_count,
            ]);
    }

    // Graphique : répartition des niveaux d’allemand parmi les étudiants
    public function getLevelDistributionProperty()
    {
        $levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $distribution = [];
        foreach ($levels as $level) {
            $count = User::where('role', 'student')
                ->where('german_level', $level)
                ->count();
            $distribution[] = [
                'level' => $level,
                'count' => $count,
            ];
        }
        return $distribution;
    }

    // Graphique : activité quotidienne (7 derniers jours)
    public function getDailyActivityProperty()
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $minutes = StudySession::whereDate('date', $date)->sum('duration_minutes');
            $data[] = [
                'day' => $date->format('D'),
                'minutes' => $minutes,
            ];
        }
        return $data;
    }

    // Graphique : évolution des quiz tentés par mois
    public function getMonthlyQuizAttemptsProperty()
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = QuizAttempt::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $data[] = [
                'month' => $date->translatedFormat('M Y'),
                'count' => $count,
            ];
        }
        return $data;
    }

    public function render()
    {
        return $this->view([
            'totalStudents'        => $this->totalStudents,
            'activeStudents'       => $this->activeStudents,
            'newStudentsThisMonth' => $this->newStudentsThisMonth,
            'totalCourses'         => $this->totalCourses,
            'totalEnrollments'     => $this->totalEnrollments,
            'completionRate'       => $this->completionRate,
            'avgQuizScore'         => $this->avgQuizScore,
            'totalStudyMinutes'    => $this->totalStudyMinutes,
            'monthlyEnrollments'   => $this->monthlyEnrollments,
            'topCourses'           => $this->topCourses,
            'levelDistribution'    => $this->levelDistribution,
            'dailyActivity'        => $this->dailyActivity,
            'monthlyQuizAttempts'  => $this->monthlyQuizAttempts,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold md:text-3xl">📈 {{ __('Analytics') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ __('Platform performance metrics') }}</p>
        </div>

        {{-- Key metrics cards --}}
        <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-3 lg:grid-cols-4">
            <x-stat title="{{ __('Total students') }}" :value="$totalStudents" icon="o-users" class="text-primary" />
            <x-stat title="{{ __('Active students (month)') }}" :value="$activeStudents" icon="o-user-circle" class="text-success" />
            <x-stat title="{{ __('New students (month)') }}" :value="$newStudentsThisMonth" icon="o-user-plus" class="text-info" />
            <x-stat title="{{ __('Published courses') }}" :value="$totalCourses" icon="o-book-open" class="text-secondary" />
            <x-stat title="{{ __('Total enrollments') }}" :value="$totalEnrollments" icon="o-clipboard-document-list" class="text-accent" />
            <x-stat title="{{ __('Completion rate') }}" :value="$completionRate . '%'" icon="o-check-circle" class="text-warning" />
            <x-stat title="{{ __('Avg quiz score') }}" :value="$avgQuizScore . '%'" icon="o-document-text" class="text-info" />
            <x-stat title="{{ __('Study time') }}" :value="floor($totalStudyMinutes / 60) . 'h'" icon="o-clock" class="text-primary" />
        </div>

        {{-- Charts grid --}}
        <div class="grid gap-6 mb-8 lg:grid-cols-2">

            {{-- Monthly enrollments chart --}}
            <x-card title="{{ __('Monthly enrollments') }}" icon="o-chart-bar" shadow separator>
                <div class="space-y-3">
                    @foreach($monthlyEnrollments as $item)
                        @php $percentage = max($monthlyEnrollments)['count'] > 0 ? ($item['count'] / max($monthlyEnrollments)['count']) * 100 : 0; @endphp
                        <div>
                            <div class="flex justify-between mb-1 text-sm">
                                <span class="text-base-content/70">{{ $item['month'] }}</span>
                                <span class="font-semibold text-primary">{{ $item['count'] }}</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-base-200">
                                <div class="h-2 rounded-full bg-primary" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            {{-- Top courses --}}
            <x-card title="{{ __('Top courses') }}" icon="o-trophy" shadow separator>
                <div class="space-y-3">
                    @foreach($topCourses as $course)
                        <div class="flex items-center justify-between">
                            <span class="text-sm truncate">{{ $course['title'] }}</span>
                            <span class="font-semibold text-primary">{{ $course['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </x-card>

            {{-- Level distribution --}}
            <x-card title="{{ __('Student level distribution') }}" icon="o-chart-pie" shadow separator>
                <div class="space-y-3">
                    @foreach($levelDistribution as $level)
                        @php $total = collect($levelDistribution)->sum('count'); $percentage = $total > 0 ? round(($level['count'] / $total) * 100) : 0; @endphp
                        <div>
                            <div class="flex justify-between mb-1 text-sm">
                                <span>{{ $level['level'] }}</span>
                                <span>{{ $level['count'] }} ({{ $percentage }}%)</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-base-200">
                                <div class="h-2 rounded-full bg-secondary" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            {{-- Daily activity (last 7 days) --}}
            <x-card title="{{ __('Daily activity (minutes)') }}" icon="o-calendar" shadow separator>
                <div class="flex items-end justify-between h-32">
                    @foreach($dailyActivity as $day)
                        <div class="flex flex-col items-center w-10">
                            <div class="relative group">
                                <div class="w-8 rounded-t-lg bg-primary/20" style="height: {{ max(4, $day['minutes'] / 2) }}px">
                                    <div class="w-full rounded-t-lg bg-primary" style="height: {{ min(100, ($day['minutes'] / 120) * 100) }}%"></div>
                                </div>
                                <div class="absolute hidden px-2 py-1 mb-2 text-xs text-white transform -translate-x-1/2 rounded bg-base-100 bottom-full left-1/2 group-hover:block whitespace-nowrap">
                                    {{ $day['minutes'] }} min
                                </div>
                            </div>
                            <span class="mt-2 text-xs">{{ $day['day'] }}</span>
                        </div>
                    @endforeach
                </div>
            </x-card>

            {{-- Monthly quiz attempts --}}
            <x-card title="{{ __('Monthly quiz attempts') }}" icon="o-document-text" shadow separator>
                <div class="space-y-3">
                    @foreach($monthlyQuizAttempts as $item)
                        @php $max = max($monthlyQuizAttempts)['count']; $percentage = $max > 0 ? ($item['count'] / $max) * 100 : 0; @endphp
                        <div>
                            <div class="flex justify-between mb-1 text-sm">
                                <span class="text-base-content/70">{{ $item['month'] }}</span>
                                <span class="font-semibold text-secondary">{{ $item['count'] }}</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-base-200">
                                <div class="h-2 rounded-full bg-secondary" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

        </div>

        {{-- Info note --}}
        <div class="p-4 mt-6 border rounded-lg bg-info/10 border-info/20">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-info mt-0.5" />
                <div>
                    <p class="font-medium text-info">{{ __('Analytics information') }}</p>
                    <p class="text-sm text-info/80">{{ __('Data is updated in real time. Use filters to refine your view.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
