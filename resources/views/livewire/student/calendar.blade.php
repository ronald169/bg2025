<?php

use App\Models\StudySession;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Mary\Traits\Toast;

new
#[Title('Lernkalender - Deutsch lernen')]
#[Layout('components.layouts.dashboard-student')]
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

    #[Computed]
    public function calendarDays()
    {
        $firstDay = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $start = $firstDay->copy()->startOfWeek(Carbon::MONDAY);
        $end = $firstDay->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        // Récupérer tous les jours avec sessions d'étude
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

    #[Computed]
    public function dailySessions()
    {
        return StudySession::where('user_id', auth()->id())
            ->whereDate('date', $this->selectedDate)
            ->with('lesson.course')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function selectedDateStats()
    {
        $totalMinutes = $this->dailySessions->sum('duration_minutes');
        $totalSessions = $this->dailySessions->count();

        return [
            'total_minutes' => $totalMinutes,
            'total_sessions' => $totalSessions,
            'formatted_time' => $this->formatDuration($totalMinutes),
        ];
    }

    #[Computed]
    public function monthStats()
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
        $this->success('Heute ausgewählt! 📅');
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
            'high' => 'bg-green-500',
            'medium' => 'bg-blue-500',
            'low' => 'bg-yellow-500',
            default => 'bg-gray-200',
        };
    }
}
?>

<div class="py-8">
    <div class="max-w-6xl px-4 mx-auto">

        <!-- Header -->
        <div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">📅 {{ __('Lernkalender') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('Verfolge deine tägliche Lernaktivität') }}</p>
            </div>
            <x-button
                wire:click="goToToday"
                icon="o-calendar-days"
                class="btn-outline">
                Heute
            </x-button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4">
            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->monthStats['study_days'] }}</div>
                <div class="text-sm text-gray-500">Lerntage</div>
                <div class="text-xs text-gray-400">diesen Monat</div>
            </x-card>

            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->monthStats['total_sessions'] }}</div>
                <div class="text-sm text-gray-500">Lerneinheiten</div>
                <div class="text-xs text-gray-400">diesen Monat</div>
            </x-card>

            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->monthStats['formatted_time'] }}</div>
                <div class="text-sm text-gray-500">Lernzeit</div>
                <div class="text-xs text-gray-400">diesen Monat</div>
            </x-card>

            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->formatDuration($this->monthStats['avg_per_day']) }}</div>
                <div class="text-sm text-gray-500">Ø pro Tag</div>
                <div class="text-xs text-gray-400">an Lerntagen</div>
            </x-card>
        </div>

        <!-- Calendar -->
        <div class="mb-6 overflow-hidden bg-white shadow-sm rounded-xl">
            <div class="flex items-center justify-between p-4 border-b bg-gradient-to-r from-gray-50 to-white">
                <x-button wire:click="changeMonth(-1)" icon="o-chevron-left" class="btn-ghost btn-sm" />
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ Carbon::create($this->currentYear, $this->currentMonth, 1)->translatedFormat('F Y') }}
                </h2>
                <x-button wire:click="changeMonth(1)" icon="o-chevron-right" class="btn-ghost btn-sm" />
            </div>

            <div class="grid grid-cols-7 text-center border-b bg-gray-50">
                @foreach(['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'] as $day)
                <div class="py-2 text-sm font-medium text-gray-600">{{ $day }}</div>
                @endforeach
            </div>

            <div class="grid grid-cols-7">
                @foreach($this->calendarDays as $day)
                <div
                    wire:click="selectDate('{{ $day['date'] }}')"
                    class="min-h-[100px] p-2 border-r border-b cursor-pointer transition hover:bg-gray-50 relative
                           {{ !$day['is_current_month'] ? 'bg-gray-50 text-gray-400' : 'bg-white' }}
                           {{ $day['is_today'] ? 'ring-2 ring-[#FF6B35] ring-inset' : '' }}
                           {{ $day['is_selected'] ? 'bg-orange-50' : '' }}">

                    <div class="flex items-start justify-between">
                        <span class="text-sm font-medium {{ $day['is_today'] ? 'text-[#FF6B35] font-bold' : '' }}">
                            {{ $day['day'] }}
                        </span>
                        @if($day['has_sessions'])
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        @endif
                    </div>

                    @if($day['has_sessions'])
                        <div class="mt-2 text-xs">
                            <div class="flex items-center gap-1 text-green-600">
                                <x-icon name="o-clock" class="w-3 h-3" />
                                <span>{{ $this->formatDuration($day['total_minutes']) }}</span>
                            </div>
                            @if($day['session_count'] > 1)
                                <div class="mt-1 text-xs text-gray-400">
                                    {{ $day['session_count'] }} Sessions
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Daily Sessions -->
        <x-card class="border-0 shadow-sm">
            <div class="flex items-center justify-between pb-2 mb-4 border-b">
                <div>
                    <h2 class="font-semibold text-gray-900">
                        📚 {{ __('Lerneinheiten vom') }} {{ Carbon::parse($this->selectedDate)->translatedFormat('l, d. F Y') }}
                    </h2>
                    @if($this->selectedDateStats['total_sessions'] > 0)
                        <p class="mt-1 text-sm text-gray-500">
                            Insgesamt {{ $this->selectedDateStats['formatted_time'] }} - {{ $this->selectedDateStats['total_sessions'] }} Lerneinheiten
                        </p>
                    @endif
                </div>
                @if($this->selectedDateStats['total_sessions'] > 0)
                    <div class="px-3 py-1 text-sm text-green-700 bg-green-100 rounded-full">
                        🎯 {{ $this->selectedDateStats['formatted_time'] }}
                    </div>
                @endif
            </div>

            @if($this->dailySessions->count() > 0)
                <div class="space-y-3">
                    @foreach($this->dailySessions as $session)
                    <div class="flex items-center justify-between p-3 transition border rounded-lg hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-[#FF6B35]/10 flex items-center justify-center">
                                <x-icon name="o-book-open" class="w-5 h-5 text-[#FF6B35]" />
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ $session->lesson->title ?? 'Lerneinheit' }}
                                </p>
                                @if($session->lesson && $session->lesson->course)
                                    <p class="text-xs text-gray-500">{{ $session->lesson->course->title }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center gap-1 text-sm text-gray-600">
                                <x-icon name="o-clock" class="w-4 h-4" />
                                <span>{{ $this->formatDuration($session->duration_minutes) }}</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-400">
                                {{ Carbon::parse($session->created_at)->format('H:i') }}
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <x-icon name="o-calendar" class="w-16 h-16 mx-auto mb-3 text-gray-300" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">Keine Lerneinheiten</h3>
                    <p class="mb-4 text-gray-500">
                        {{ Carbon::parse($this->selectedDate)->translatedFormat('l, d. F Y') }}
                    </p>
                    <p class="text-sm text-gray-400">
                        {{ $this->selectedDate === now()->format('Y-m-d')
                            ? 'Beginne noch heute mit dem Lernen! 📚'
                            : 'Keine Lernaktivitäten an diesem Tag.' }}
                    </p>
                </div>
            @endif
        </x-card>

        <!-- Motivation Quote -->
        <div class="p-4 mt-6 border bg-gradient-to-r from-orange-50 to-blue-50 rounded-xl">
            <div class="flex items-start gap-3">
                <x-icon name="o-sparkles" class="w-5 h-5 text-[#FF6B35] mt-0.5" />
                <div>
                    <p class="font-medium text-gray-900">🌟 {{ __('Deine Lernstatistik') }}</p>
                    <p class="text-sm text-gray-600">
                        @if($this->monthStats['study_days'] >= 20)
                            Fantastisch! Du lernst sehr konsequent. Bleib dran! 🔥
                        @elseif($this->monthStats['study_days'] >= 10)
                            Gute Arbeit! Du bist auf dem richtigen Weg. 💪
                        @elseif($this->monthStats['study_days'] > 0)
                            Jeder Tag zählt! Versuche, regelmäßiger zu lernen. 📚
                        @else
                            Beginne noch heute mit deiner ersten Lerneinheit! 🚀
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Note MVP -->
        <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="font-medium text-blue-800">MVP Version</p>
                    <p class="text-sm text-blue-700">Prochaines fonctionnalités : export du calendrier, objectifs mensuels, rappels, et synchronisation avec d'autres applications.</p>
                </div>
            </div>
        </div>
    </div>
</div>
