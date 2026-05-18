<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Mary\Traits\Toast;

new
#[Title('Schedule - Teacher')]
#[Layout('layouts.app')]
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

    // Getters
    public function getCalendarDaysProperty()
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

    public function getEventsProperty()
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

    public function getEventsByDateProperty()
    {
        return $this->events->groupBy('date');
    }

    public function getDailyEventsProperty()
    {
        return $this->events->where('date', $this->selectedDate)->values();
    }

    public function getMonthNameProperty()
    {
        return Carbon::create($this->currentYear, $this->currentMonth, 1)->translatedFormat('F Y');
    }

    public function getStatsProperty()
    {
        $totalEvents = $this->events->count();
        $uniqueDays = $this->events->unique('date')->count();
        $totalHours = $this->events->sum('duration') / 60;
        $upcomingEvents = $this->events->filter(fn($e) => $e['date'] >= now()->format('Y-m-d'))->count();

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
        $this->success(__('Today selected 📅'));
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

    public function formatDuration($minutes): string
    {
        if ($minutes < 60) return "{$minutes} min";
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
    }

    public function render()
    {
        return $this->view([
            'calendarDays'   => $this->calendarDays,
            'eventsByDate'   => $this->eventsByDate,
            'dailyEvents'    => $this->dailyEvents,
            'monthName'      => $this->monthName,
            'stats'          => $this->stats,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">📅 {{ __('Schedule') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('Manage your lessons schedule') }}</p>
            </div>
            <x-button wire:click="goToToday" label="{{ __('Today') }}" class="btn-outline btn-sm" />
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-4">
            <x-stat title="{{ __('Lessons') }}" :value="$stats['total_events']" icon="o-book-open" class="text-primary" />
            <x-stat title="{{ __('Days') }}" :value="$stats['unique_days']" icon="o-calendar" class="text-info" />
            <x-stat title="{{ __('Hours') }}" :value="$stats['total_hours']" icon="o-clock" class="text-warning" />
            <x-stat title="{{ __('Upcoming') }}" :value="$stats['upcoming_events']" icon="o-arrow-trending-up" class="text-success" />
        </div>

        {{-- Calendar --}}
        <x-card class="mb-6 overflow-hidden shadow-sm">
            {{-- Calendar Header --}}
            <div class="flex items-center justify-between p-3 border-b">
                <x-button icon="o-chevron-left" class="btn-ghost btn-sm" wire:click="changeMonth(-1)" />
                <h2 class="text-base font-semibold md:text-lg">{{ $monthName }}</h2>
                <x-button icon="o-chevron-right" class="btn-ghost btn-sm" wire:click="changeMonth(1)" />
            </div>

            {{-- Week days header --}}
            <div class="grid grid-cols-7 py-2 text-xs font-medium text-center border-b">
                <div>{{ __('Mon') }}</div><div>{{ __('Tue') }}</div><div>{{ __('Wed') }}</div>
                <div>{{ __('Thu') }}</div><div>{{ __('Fri') }}</div><div>{{ __('Sat') }}</div><div>{{ __('Sun') }}</div>
            </div>

            {{-- Calendar days grid --}}
            <div class="grid grid-cols-7">
                @foreach($calendarDays as $day)
                    @php $eventCount = $this->getEventCountForDate($day['date']); @endphp
                    <div wire:click="selectDate('{{ $day['date'] }}')"
                         class="min-h-[70px] md:min-h-[90px] p-1 border-b border-r cursor-pointer hover:bg-base-200 transition
                                {{ !$day['is_current_month'] ? 'bg-base-200 text-base-content/40' : '' }}
                                {{ $day['is_today'] ? 'bg-primary/10' : '' }}
                                {{ $selectedDate === $day['date'] ? 'ring-2 ring-primary ring-inset' : '' }}">
                        <div class="flex items-start justify-between">
                            <span class="text-xs md:text-sm font-medium {{ $day['is_today'] ? 'text-primary font-bold' : '' }}">{{ $day['day'] }}</span>
                            @if($eventCount > 0)
                                <span class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-semibold bg-primary text-white rounded-full">{{ $eventCount }}</span>
                            @endif
                        </div>
                        @if($eventCount > 0)
                            <div class="hidden md:block mt-1 space-y-0.5">
                                @foreach($eventsByDate->get($day['date'], collect())->take(2) as $event)
                                    <div wire:click.stop="viewEvent({{ $event['id'] }})"
                                         class="text-[10px] p-0.5 bg-info/20 text-info rounded truncate cursor-pointer hover:bg-info/30">
                                        {{ $event['time'] }} {{ Str::limit($event['title'], 15) }}
                                    </div>
                                @endforeach
                                @if($eventCount > 2)
                                    <div class="text-[9px] text-base-content/50 pl-1">+{{ $eventCount - 2 }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-card>

        {{-- Daily Events List --}}
        <x-card class="shadow-sm">
            <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                <x-icon name="o-calendar" class="w-5 h-5 text-primary" />
                <h3 class="font-semibold">📅 {{ Carbon::parse($selectedDate)->translatedFormat('l, F d, Y') }}</h3>
            </div>

            @if($dailyEvents->count() > 0)
                <div class="space-y-3">
                    @foreach($dailyEvents as $event)
                        <div wire:click="viewEvent({{ $event['id'] }})" class="flex items-center justify-between p-3 transition border rounded-lg cursor-pointer hover:bg-base-200">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-info/20">
                                    <x-icon name="o-video-camera" class="w-5 h-5 text-info" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium">{{ $event['title'] }}</p>
                                    <p class="text-xs text-base-content/60">{{ $event['course_title'] }}</p>
                                    <div class="flex gap-2 mt-1 text-xs text-base-content/50">
                                        <span>🕐 {{ $event['time'] }}</span>
                                        <span>⏱️ {{ $this->formatDuration($event['duration']) }}</span>
                                    </div>
                                </div>
                            </div>
                            <x-icon name="o-chevron-right" class="w-5 h-5 text-base-content/40" />
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <x-icon name="o-calendar" class="w-12 h-12 mx-auto mb-2 text-base-content/30" />
                    <p class="text-sm text-base-content/60">{{ __('No scheduled lessons') }}</p>
                    <p class="text-xs text-base-content/50">{{ __('Create lessons with a scheduled date') }}</p>
                </div>
            @endif
        </x-card>

        {{-- Event Modal --}}
        @if($showEventModal && $selectedEvent)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="closeModal">
                <div class="w-full max-w-md shadow-xl bg-base-100 rounded-xl" @click.stop>
                    <div class="p-4 border-b bg-gradient-to-r from-primary to-secondary rounded-t-xl">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-white">{{ $selectedEvent['title'] }}</h3>
                            <x-button icon="o-x-mark" class="text-white btn-circle btn-ghost btn-sm" wire:click="closeModal" />
                        </div>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="flex gap-3"><x-icon name="o-academic-cap" class="w-5 h-5 text-primary mt-0.5" /><div><p class="text-xs text-base-content/60">{{ __('Course') }}</p><p class="font-medium">{{ $selectedEvent['course_title'] }}</p></div></div>
                        <div class="flex gap-3"><x-icon name="o-calendar" class="w-5 h-5 text-primary mt-0.5" /><div><p class="text-xs text-base-content/60">{{ __('Date & time') }}</p><p class="font-medium">{{ Carbon::parse($selectedEvent['date'])->translatedFormat('l, F d, Y') }} {{ __('at') }} {{ $selectedEvent['time'] }}</p></div></div>
                        <div class="flex gap-3"><x-icon name="o-clock" class="w-5 h-5 text-primary mt-0.5" /><div><p class="text-xs text-base-content/60">{{ __('Duration') }}</p><p class="font-medium">{{ $this->formatDuration($selectedEvent['duration']) }}</p></div></div>
                    </div>
                    <div class="flex justify-end gap-3 p-4 border-t">
                        <x-button label="{{ __('Close') }}" wire:click="closeModal" class="btn-ghost" />
                        <a href="{{ $selectedEvent['url'] }}" class="btn btn-primary">{{ __('Go to lesson') }} →</a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
