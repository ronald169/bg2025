<?php

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Mary\Traits\Toast;

new
#[Title('Stundenplan - Lehrer')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use Toast;

    #[Url(as: 'date', history: true)]
    public $selectedDate = null;

    public $currentMonth = null;
    public $currentYear = null;
    public $showEventModal = false;
    public $selectedEvent = null;

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
    }

    #[Computed]
    public function calendarDays()
    {
        $firstDayOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $startOfCalendar = $firstDayOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $endOfCalendar = $firstDayOfMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        $currentDay = $startOfCalendar->copy();

        while ($currentDay <= $endOfCalendar) {
            $days[] = [
                'date' => $currentDay->format('Y-m-d'),
                'day' => $currentDay->day,
                'is_current_month' => $currentDay->month == $this->currentMonth,
                'is_today' => $currentDay->isToday(),
            ];
            $currentDay->addDay();
        }

        return $days;
    }

    #[Computed]
    public function events()
    {
        $startDate = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $courseIds = Course::where('teacher_id', Auth::id())->pluck('id');

        return Lesson::whereIn('course_id', $courseIds)
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->with('course')
            ->get()
            ->map(function ($lesson) {
                return [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'course_title' => $lesson->course->title,
                    'course_id' => $lesson->course_id,
                    'date' => Carbon::parse($lesson->scheduled_at)->format('Y-m-d'),
                    'time' => Carbon::parse($lesson->scheduled_at)->format('H:i'),
                    'duration' => $lesson->duration ?? 45,
                    'type' => 'lesson',
                    'url' => route('teacher.lessons.edit', ['course' => $lesson->course, 'lesson' => $lesson]),
                ];
            });
    }

    #[Computed]
    public function eventsByDate()
    {
        return $this->events->groupBy('date');
    }

    #[Computed]
    public function dailyEvents()
    {
        return $this->events->where('date', $this->selectedDate)->values();
    }

    #[Computed]
    public function monthName()
    {
        return Carbon::create($this->currentYear, $this->currentMonth, 1)->translatedFormat('F Y');
    }

    #[Computed]
    public function stats()
    {
        $totalEvents = $this->events->count();
        $uniqueDays = $this->events->unique('date')->count();
        $totalHours = $this->events->sum('duration') / 60;

        // Calcul des événements à venir
        $today = now()->format('Y-m-d');
        $upcomingEvents = $this->events->filter(function($event) use ($today) {
            return $event['date'] >= $today;
        })->count();

        return [
            'total_events' => $totalEvents,
            'unique_days' => $uniqueDays,
            'total_hours' => round($totalHours, 1),
            'upcoming_events' => $upcomingEvents,
        ];
    }

    public function changeMonth($direction): void
    {
        $newDate = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonths($direction);
        $this->currentMonth = $newDate->month;
        $this->currentYear = $newDate->year;
    }

    public function goToToday(): void
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
        $this->selectedDate = now()->format('Y-m-d');
        $this->success('Heute ausgewählt 📅');
    }

    public function selectDate($date): void
    {
        $this->selectedDate = $date;
    }

    public function viewEvent($eventId): void
    {
        $this->selectedEvent = $this->events->firstWhere('id', $eventId);
        $this->showEventModal = true;
    }

    public function closeModal(): void
    {
        $this->showEventModal = false;
        $this->selectedEvent = null;
    }

    public function getEventCountForDate($date)
    {
        return $this->eventsByDate->get($date, collect())->count();
    }

    public function getFormattedTime($minutes): string
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
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">📅 {{ __('Stundenplan') }}</h1>
                <p class="text-gray-500 text-xs md:text-sm mt-0.5">{{ __('Verwalte deine Unterrichtsstunden') }}</p>
            </div>
            <div class="flex gap-2">
                <button
                    wire:click="goToToday"
                    class="px-3 py-1.5 text-sm text-[#FF6B35] border border-[#FF6B35] rounded-lg hover:bg-orange-50 transition">
                    {{ __('Heute') }}
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-4">
            <div class="p-3 text-center bg-white rounded-lg shadow-sm">
                <p class="text-xs text-gray-500">📚 Lektionen</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->stats['total_events'] }}</p>
            </div>
            <div class="p-3 text-center bg-white rounded-lg shadow-sm">
                <p class="text-xs text-gray-500">📅 Tage</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->stats['unique_days'] }}</p>
            </div>
            <div class="p-3 text-center bg-white rounded-lg shadow-sm">
                <p class="text-xs text-gray-500">⏱️ Stunden</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->stats['total_hours'] }}</p>
            </div>
            <div class="p-3 text-center bg-white rounded-lg shadow-sm">
                <p class="text-xs text-gray-500">⏰ Anstehend</p>
                <p class="text-xl font-bold text-green-600">{{ $this->stats['upcoming_events'] }}</p>
            </div>
        </div>

        <!-- Calendar -->
        <div class="mb-6 overflow-hidden bg-white shadow-sm rounded-xl">
            <!-- Calendar Header -->
            <div class="flex items-center justify-between p-3 border-b bg-gray-50">
                <button wire:click="changeMonth(-1)" class="p-2 transition rounded-lg hover:bg-gray-200">
                    <x-icon name="o-chevron-left" class="w-5 h-5 text-gray-600" />
                </button>
                <h2 class="text-base font-semibold text-gray-900 md:text-lg">{{ $this->monthName }}</h2>
                <button wire:click="changeMonth(1)" class="p-2 transition rounded-lg hover:bg-gray-200">
                    <x-icon name="o-chevron-right" class="w-5 h-5 text-gray-600" />
                </button>
            </div>

            <!-- Week Days Header -->
            <div class="grid grid-cols-7 border-b">
                @foreach(['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'] as $day)
                <div class="py-2 text-xs font-medium text-center text-gray-500">
                    {{ $day }}
                </div>
                @endforeach
            </div>

            <!-- Calendar Days -->
            <div class="grid grid-cols-7">
                @foreach($this->calendarDays as $day)
                @php
                    $eventCount = $this->getEventCountForDate($day['date']);
                    $hasEvents = $eventCount > 0;
                @endphp
                <div
                    wire:click="selectDate('{{ $day['date'] }}')"
                    class="min-h-[70px] md:min-h-[90px] p-1 border-b border-r cursor-pointer hover:bg-gray-50 transition
                           {{ !$day['is_current_month'] ? 'bg-gray-50 text-gray-400' : '' }}
                           {{ $day['is_today'] ? 'bg-orange-50' : '' }}
                           {{ $selectedDate === $day['date'] ? 'ring-2 ring-[#FF6B35] ring-inset' : '' }}">

                    <div class="flex items-start justify-between">
                        <span class="text-xs md:text-sm font-medium {{ $day['is_today'] ? 'text-[#FF6B35] font-bold' : '' }}">
                            {{ $day['day'] }}
                        </span>
                        @if($hasEvents)
                            <span class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-semibold bg-[#FF6B35] text-white rounded-full">
                                {{ $eventCount }}
                            </span>
                        @endif
                    </div>

                    @if($hasEvents)
                        <div class="hidden mt-1 md:block">
                            @foreach($this->eventsByDate->get($day['date'], collect())->take(2) as $event)
                            <div
                                wire:click.stop="viewEvent({{ $event['id'] }})"
                                class="text-[10px] p-0.5 mb-0.5 bg-blue-100 text-blue-700 rounded truncate cursor-pointer hover:bg-blue-200">
                                {{ $event['time'] }} {{ Str::limit($event['title'], 15) }}
                            </div>
                            @endforeach
                            @if($eventCount > 2)
                                <div class="text-[9px] text-gray-400 pl-1">+{{ $eventCount - 2 }}</div>
                            @endif
                        </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Daily Events List -->
        <div class="overflow-hidden bg-white shadow-sm rounded-xl">
            <div class="p-3 border-b bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-900 md:text-base">
                    📅 {{ Carbon::parse($this->selectedDate)->translatedFormat('l, d. F Y') }}
                </h3>
            </div>

            <div class="p-3">
                @if($this->dailyEvents->count() > 0)
                    <div class="space-y-2">
                        @foreach($this->dailyEvents as $event)
                        <div
                            wire:click="viewEvent({{ $event['id'] }})"
                            class="flex items-center justify-between p-3 transition border rounded-lg cursor-pointer hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg">
                                    <x-icon name="o-video-camera" class="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $event['title'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $event['course_title'] }}</p>
                                    <div class="flex items-center gap-2 mt-1 text-xs text-gray-400">
                                        <span>🕐 {{ $event['time'] }}</span>
                                        <span>⏱️ {{ $this->getFormattedTime($event['duration']) }}</span>
                                    </div>
                                </div>
                            </div>
                            <x-icon name="o-chevron-right" class="w-5 h-5 text-gray-400" />
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-10 text-center">
                        <x-icon name="o-calendar" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                        <p class="text-sm text-gray-500">{{ __('Keine geplanten Lektionen') }}</p>
                        <p class="mt-1 text-xs text-gray-400">{{ __('Erstelle Lektionen mit geplantem Datum') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Event Modal -->
        @if($showEventModal && $selectedEvent)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="closeModal">
            <div class="w-full max-w-md overflow-hidden bg-white shadow-xl rounded-xl">
                <div class="p-4 border-b bg-gradient-to-r from-[#FF6B35] to-[#1E6091]">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white">{{ $selectedEvent['title'] }}</h3>
                        <button wire:click="closeModal" class="text-white/80 hover:text-white">
                            <x-icon name="o-x-mark" class="w-6 h-6" />
                        </button>
                    </div>
                </div>

                <div class="p-5 space-y-4">
                    <div class="flex items-start gap-3">
                        <x-icon name="o-academic-cap" class="w-5 h-5 text-[#FF6B35] mt-0.5" />
                        <div>
                            <p class="text-xs text-gray-500">{{ __('Kurs') }}</p>
                            <p class="font-medium text-gray-900">{{ $selectedEvent['course_title'] }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <x-icon name="o-calendar" class="w-5 h-5 text-[#FF6B35] mt-0.5" />
                        <div>
                            <p class="text-xs text-gray-500">{{ __('Datum & Uhrzeit') }}</p>
                            <p class="font-medium text-gray-900">
                                {{ Carbon::parse($selectedEvent['date'])->translatedFormat('l, d. F Y') }}
                                {{ __('um') }} {{ $selectedEvent['time'] }} {{ __('Uhr') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <x-icon name="o-clock" class="w-5 h-5 text-[#FF6B35] mt-0.5" />
                        <div>
                            <p class="text-xs text-gray-500">{{ __('Dauer') }}</p>
                            <p class="font-medium text-gray-900">{{ $this->getFormattedTime($selectedEvent['duration']) }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 p-4 border-t bg-gray-50">
                    <button wire:click="closeModal" class="px-4 py-2 text-sm text-gray-600 transition rounded-lg hover:bg-gray-100">
                        {{ __('Schließen') }}
                    </button>
                    <a href="{{ $selectedEvent['url'] }}"
                       class="px-4 py-2 text-sm text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                        {{ __('Zur Lektion') }} →
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Note MVP -->
        <div class="p-3 mt-5 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-2">
                <x-icon name="o-information-circle" class="w-4 h-4 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">MVP Version</p>
                    <p class="text-xs text-blue-700">{{ __('Prochaines fonctionnalités : événements récurrents, rappels, synchronisation Google Agenda, et système de réservation.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
