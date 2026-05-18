<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\StudySession;
use Carbon\Carbon;
use Mary\Traits\Toast;

new
#[Title('Study Calendar - German Learning')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public $currentMonth;
    public $currentYear;
    public $selectedDate = null;

    public function mount(): void
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function getCalendarDays()
    {
        $firstDay = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $start = $firstDay->copy()->startOfWeek(Carbon::MONDAY);
        $end = $firstDay->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $sessionDates = StudySession::where('user_id', auth()->id())
            ->whereYear('date', $this->currentYear)
            ->whereMonth('date', $this->currentMonth)
            ->select('date', \DB::raw('count(*) as session_count'), \DB::raw('sum(duration_minutes) as total_minutes'))
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $days = [];
        $current = $start->copy();

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $session = $sessionDates->get($dateStr);

            $days[] = [
                'date' => $dateStr,
                'day' => $current->day,
                'is_current_month' => $current->month == $this->currentMonth,
                'is_today' => $current->isToday(),
                'is_selected' => $this->selectedDate === $dateStr,
                'has_sessions' => $session !== null,
                'session_count' => $session->session_count ?? 0,
                'total_minutes' => $session->total_minutes ?? 0,
            ];
            $current->addDay();
        }

        return $days;
    }

    public function getDailySessions()
    {
        return StudySession::where('user_id', auth()->id())
            ->whereDate('date', $this->selectedDate)
            ->with('lesson.course')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getSelectedDateStats()
    {
        $sessions = $this->getDailySessions();
        $totalMinutes = $sessions->sum('duration_minutes');
        $totalSessions = $sessions->count();

        return [
            'total_minutes' => $totalMinutes,
            'total_sessions' => $totalSessions,
            'formatted_time' => $this->formatDuration($totalMinutes),
        ];
    }

    public function getMonthStats()
    {
        $sessions = StudySession::where('user_id', auth()->id())
            ->whereYear('date', $this->currentYear)
            ->whereMonth('date', $this->currentMonth)
            ->get();

        $totalMinutes = $sessions->sum('duration_minutes');
        $totalSessions = $sessions->count();
        $studyDays = $sessions->groupBy('date')->count();

        return [
            'total_minutes' => $totalMinutes,
            'total_sessions' => $totalSessions,
            'study_days' => $studyDays,
            'formatted_time' => $this->formatDuration($totalMinutes),
            'avg_per_day' => $studyDays > 0 ? round($totalMinutes / $studyDays) : 0,
        ];
    }

    public function changeMonth($direction): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonths($direction);
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function selectDate($date): void
    {
        $this->selectedDate = $date;
        $this->dispatch('date-selected');
    }

    public function goToToday(): void
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
        $this->selectedDate = now()->format('Y-m-d');
        $this->success(__('Today selected! 📅'));
    }

    public function formatDuration($minutes): string
    {
        if ($minutes < 60) return "{$minutes} min";
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
    }

    public function getActivityLevel($minutes): string
    {
        if ($minutes >= 120) return 'high';
        if ($minutes >= 60) return 'medium';
        if ($minutes > 0) return 'low';
        return 'none';
    }

    public function getActivityColor($level): string
    {
        return match($level) {
            'high' => 'bg-success',
            'medium' => 'bg-primary',
            'low' => 'bg-warning',
            default => 'bg-base-300',
        };
    }

    public function render()
    {
        return $this->view([
            'calendarDays'       => $this->getCalendarDays(),
            'dailySessions'      => $this->getDailySessions(),
            'selectedDateStats'  => $this->getSelectedDateStats(),
            'monthStats'         => $this->getMonthStats(),
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-6xl px-3 mx-auto md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold md:text-3xl">📅 {{ __('Study Calendar') }}</h1>
                <p class="mt-1 text-sm text-base-content/70">{{ __('Track your daily learning activity') }}</p>
            </div>
            <x-button wire:click="goToToday" label="{{ __('Today') }}" icon="o-calendar-days" class="btn-outline" />
        </div>

        {{-- Cartes statistiques --}}
        <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4">
            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-primary">{{ $monthStats['study_days'] }}</div>
                <div class="text-sm text-base-content/70">{{ __('Study days') }}</div>
                <div class="text-xs text-base-content/50">{{ __('this month') }}</div>
            </x-card>
            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-primary">{{ $monthStats['total_sessions'] }}</div>
                <div class="text-sm text-base-content/70">{{ __('Study sessions') }}</div>
                <div class="text-xs text-base-content/50">{{ __('this month') }}</div>
            </x-card>
            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-primary">{{ $monthStats['formatted_time'] }}</div>
                <div class="text-sm text-base-content/70">{{ __('Study time') }}</div>
                <div class="text-xs text-base-content/50">{{ __('this month') }}</div>
            </x-card>
            <x-card class="text-center shadow-sm">
                <div class="text-2xl font-bold text-primary">{{ $this->formatDuration($monthStats['avg_per_day']) }}</div>
                <div class="text-sm text-base-content/70">{{ __('Average per day') }}</div>
                <div class="text-xs text-base-content/50">{{ __('on study days') }}</div>
            </x-card>
        </div>

        {{-- Calendrier --}}
        <x-card class="mb-6 overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b">
                <x-button wire:click="changeMonth(-1)" icon="o-chevron-left" class="btn-ghost btn-sm" />
                <h2 class="text-lg font-semibold">{{ Carbon::create($this->currentYear, $this->currentMonth, 1)->translatedFormat('F Y') }}</h2>
                <x-button wire:click="changeMonth(1)" icon="o-chevron-right" class="btn-ghost btn-sm" />
            </div>

            {{-- Jours de la semaine --}}
            <div class="grid grid-cols-7 text-center border-b bg-base-200">
                @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                    <div class="py-2 text-sm font-medium text-base-content/70">{{ __($day) }}</div>
                @endforeach
            </div>

            {{-- Grille des jours --}}
            <div class="grid grid-cols-7">
                @foreach($calendarDays as $day)
                    <div wire:click="selectDate('{{ $day['date'] }}')"
                         class="min-h-[100px] p-2 border-r border-b cursor-pointer transition hover:bg-base-200 relative
                                {{ !$day['is_current_month'] ? 'bg-base-200 text-base-content/40' : 'bg-base-100' }}
                                {{ $day['is_today'] ? 'ring-2 ring-primary ring-inset' : '' }}
                                {{ $day['is_selected'] ? 'bg-primary/10' : '' }}">
                        <div class="flex items-start justify-between">
                            <span class="text-sm font-medium {{ $day['is_today'] ? 'text-primary font-bold' : '' }}">
                                {{ $day['day'] }}
                            </span>
                            @if($day['has_sessions'])
                                <div class="w-2 h-2 rounded-full bg-success"></div>
                            @endif
                        </div>
                        @if($day['has_sessions'])
                            <div class="mt-2 text-xs">
                                <div class="flex items-center gap-1 text-success">
                                    <x-icon name="o-clock" class="w-3 h-3" />
                                    <span>{{ $this->formatDuration($day['total_minutes']) }}</span>
                                </div>
                                @if($day['session_count'] > 1)
                                    <div class="mt-1 text-xs text-base-content/50">
                                        {{ $day['session_count'] }} {{ __('sessions') }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-card>

        {{-- Sessions du jour sélectionné --}}
        <x-card class="shadow-sm">
            <div class="flex flex-wrap items-center justify-between pb-2 mb-4 border-b">
                <div>
                    <h2 class="font-semibold">📚 {{ __('Study sessions on') }} {{ Carbon::parse($selectedDate)->translatedFormat('l, F d, Y') }}</h2>
                    @if($selectedDateStats['total_sessions'] > 0)
                        <p class="mt-1 text-sm text-base-content/70">
                            {{ __('Total') }} {{ $selectedDateStats['formatted_time'] }} - {{ $selectedDateStats['total_sessions'] }} {{ __('sessions') }}
                        </p>
                    @endif
                </div>
                @if($selectedDateStats['total_sessions'] > 0)
                    <x-badge :value="$selectedDateStats['formatted_time']" icon="o-clock" class="badge-success badge-soft" />
                @endif
            </div>

            @if($dailySessions->count() > 0)
                <div class="space-y-3">
                    @foreach($dailySessions as $session)
                        <div class="flex items-center justify-between p-3 transition border rounded-lg hover:bg-base-200">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10">
                                    <x-icon name="o-book-open" class="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <p class="font-medium text-base-content">
                                        {{ $session->lesson->title ?? __('Study session') }}
                                    </p>
                                    @if($session->lesson && $session->lesson->course)
                                        <p class="text-xs text-base-content/60">{{ $session->lesson->course->title }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="flex items-center gap-1 text-sm text-base-content/70">
                                    <x-icon name="o-clock" class="w-4 h-4" />
                                    <span>{{ $this->formatDuration($session->duration_minutes) }}</span>
                                </div>
                                <p class="mt-1 text-xs text-base-content/50">
                                    {{ Carbon::parse($session->created_at)->format('H:i') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <x-icon name="o-calendar" class="w-16 h-16 mx-auto mb-3 text-base-content/30" />
                    <h3 class="mb-2 text-lg font-semibold text-base-content">{{ __('No study sessions') }}</h3>
                    <p class="mb-4 text-base-content/60">{{ Carbon::parse($selectedDate)->translatedFormat('l, F d, Y') }}</p>
                    <p class="text-sm text-base-content/50">
                        {{ $selectedDate === now()->format('Y-m-d') ? __('Start learning today! 📚') : __('No learning activity on this day.') }}
                    </p>
                </div>
            @endif
        </x-card>

        {{-- Citation motivante --}}
        <div class="p-4 mt-6 border rounded-xl bg-gradient-to-r from-primary/10 to-secondary/10 border-primary/20">
            <div class="flex items-start gap-3">
                <x-icon name="o-sparkles" class="w-5 h-5 text-primary mt-0.5" />
                <div>
                    <p class="font-medium text-base-content">🌟 {{ __('Your learning stats') }}</p>
                    <p class="text-sm text-base-content/80">
                        @if($monthStats['study_days'] >= 20)
                            {{ __('Fantastic! You are very consistent. Keep it up! 🔥') }}
                        @elseif($monthStats['study_days'] >= 10)
                            {{ __('Good work! You are on the right track. 💪') }}
                        @elseif($monthStats['study_days'] > 0)
                            {{ __('Every day counts! Try to study more regularly. 📚') }}
                        @else
                            {{ __('Start your first study session today! 🚀') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
