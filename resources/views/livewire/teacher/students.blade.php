<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Meine Studenten - Lehrer')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'course', history: true)]
    public $selectedCourse = null;

    #[Url(as: 'sort', history: true)]
    public string $sortBy = 'name';

    #[Computed]
    public function courses()
    {
        return Course::where('teacher_id', auth()->id())
            ->withCount('enrollments')
            ->orderBy('title')
            ->get();
    }

    #[Computed]
    public function totalStudents()
    {
        $query = User::whereHas('enrollments.course', function($q) {
            $q->where('teacher_id', auth()->id());
            if ($this->selectedCourse) {
                $q->where('id', $this->selectedCourse);
            }
        });

        return $query->count();
    }

    #[Computed]
    public function students()
    {
        $students = User::whereHas('enrollments.course', function($q) {
                $q->where('teacher_id', auth()->id());
                if ($this->selectedCourse) {
                    $q->where('id', $this->selectedCourse);
                }
            })
            ->with(['enrollments' => function($q) {
                $q->whereHas('course', function($cq) {
                    $cq->where('teacher_id', auth()->id());
                })->with('course');
            }])
            ->when($this->search, function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->when($this->sortBy === 'name', fn($q) => $q->orderBy('name', 'asc'))
            ->when($this->sortBy === 'newest', fn($q) => $q->orderBy('created_at', 'desc'))
            ->when($this->sortBy === 'oldest', fn($q) => $q->orderBy('created_at', 'asc'))
            ->paginate(15);

        foreach ($students as $student) {
            $student->avg_progress = $this->getStudentProgress($student);
            $student->course_count = $this->getCourseCount($student);
            $student->best_course = $this->getBestCourse($student);
            $student->last_activity = $this->getLastActivity($student);
        }

        return $students;
    }

    private function getStudentProgress($student): int
    {
        $enrollments = $student->enrollments->filter(function($e) {
            if ($this->selectedCourse) {
                return $e->course_id == $this->selectedCourse;
            }
            return true;
        });

        if ($enrollments->isEmpty()) return 0;
        return round($enrollments->avg('progress'));
    }

    private function getCourseCount($student): int
    {
        return $student->enrollments->filter(function($e) {
            if ($this->selectedCourse) {
                return $e->course_id == $this->selectedCourse;
            }
            return true;
        })->count();
    }

    private function getBestCourse($student): ?string
    {
        $bestEnrollment = $student->enrollments
            ->filter(function($e) {
                if ($this->selectedCourse) {
                    return $e->course_id == $this->selectedCourse;
                }
                return true;
            })
            ->sortByDesc('progress')
            ->first();

        return $bestEnrollment?->course->title;
    }

    private function getLastActivity($student): ?string
    {
        $lastProgress = $student->progress()
            ->whereHas('lesson.course', function($q) {
                $q->where('teacher_id', auth()->id());
            })
            ->latest('updated_at')
            ->first();

        if ($lastProgress && $lastProgress->updated_at) {
            return $lastProgress->updated_at->diffForHumans();
        }

        return null;
    }

    public function viewStudent($userId): void
    {
        $this->redirectRoute('teacher.students.show', $userId, navigate: true);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'selectedCourse', 'sortBy']);
        $this->resetPage();
        $this->success('Filter zurückgesetzt.');
    }

    public function getProgressColor($progress): string
    {
        if ($progress >= 80) return 'bg-green-500';
        if ($progress >= 50) return 'bg-blue-500';
        if ($progress >= 20) return 'bg-yellow-500';
        return 'bg-gray-400';
    }

    public function getProgressTextColor($progress): string
    {
        if ($progress >= 80) return 'text-green-600';
        if ($progress >= 50) return 'text-blue-600';
        if ($progress >= 20) return 'text-yellow-600';
        return 'text-gray-600';
    }
}
?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        <!-- Header -->
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">👨‍🎓 {{ __('Meine Studenten') }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">{{ __('Verwalte und verfolge den Fortschritt deiner Studenten') }}</p>
            </div>
            <div class="bg-gray-100 rounded-lg px-3 py-1.5 text-center sm:text-right">
                <span class="text-sm text-gray-600">{{ __('Gesamt') }}:</span>
                <span class="text-xl font-bold text-[#FF6B35] ml-2">{{ $this->totalStudents }}</span>
            </div>
        </div>

        <!-- Filters - Version responsive -->
        <div class="p-4 mb-5 bg-white shadow-sm rounded-xl">
            <div class="space-y-3">
                <!-- Course Select -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Kurs filtern') }}</label>
                    <select wire:model.live="selectedCourse"
                            class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                        <option value="">{{ __('Alle Kurse') }} ({{ $this->courses->sum('enrollments_count') }})</option>
                        @foreach($this->courses as $course)
                            @if($course->enrollments_count > 0)
                                <option value="{{ $course->id }}">
                                    {{ Str::limit($course->title, 35) }} ({{ $course->enrollments_count }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <!-- Search and Sort - 2 colonnes sur mobile -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Suche') }}</label>
                        <div class="relative">
                            <x-input
                                wire:model.live.debounce.300ms="search"
                                placeholder="{{ __('Name oder E-Mail...') }}"
                                icon="o-magnifying-glass"
                                class="w-full" />
                        </div>
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Sortieren nach') }}</label>
                        <select wire:model.live="sortBy"
                                class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                            <option value="name">{{ __('Name A-Z') }}</option>
                            <option value="newest">{{ __('Neueste zuerst') }}</option>
                            <option value="oldest">{{ __('Älteste zuerst') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Reset filters button -->
                @if($search || $selectedCourse)
                    <div class="flex justify-end">
                        <button
                            wire:click="clearFilters"
                            class="px-3 py-1.5 text-sm text-gray-600 hover:text-[#FF6B35] transition">
                            <x-icon name="o-x-mark" class="inline w-4 h-4 mr-1" />
                            {{ __('Filter zurücksetzen') }}
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Students List - Version responsive -->
        @if($this->students->count() > 0)

            <!-- Version Desktop: Tableau -->
            <div class="hidden overflow-hidden bg-white shadow-sm md:block rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-gray-50">
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Student') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('E-Mail') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">{{ __('Kurse') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">{{ __('Ø Fortschritt') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Letzte Aktivität') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">{{ __('Aktionen') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->students as $student)
                            <tr class="transition border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-xs">
                                            {{ strtoupper(substr($student->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $student->name }}</p>
                                            <p class="text-xs text-gray-400">{{ $student->german_level ?? 'A1' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $student->email }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium text-blue-700 bg-blue-100 rounded-full">
                                        <x-icon name="o-academic-cap" class="w-3 h-3" />
                                        {{ $student->course_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold {{ $this->getProgressTextColor($student->avg_progress) }}">
                                            {{ $student->avg_progress }}%
                                        </span>
                                        <div class="w-16 h-1.5 bg-gray-200 rounded-full">
                                            <div class="h-1.5 rounded-full {{ $this->getProgressColor($student->avg_progress) }}"
                                                 style="width: {{ $student->avg_progress }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-500">
                                        {{ $student->last_activity ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        wire:click="viewStudent({{ $student->id }})"
                                        class="px-2 py-1 text-sm text-[#FF6B35] hover:bg-orange-50 rounded-lg transition">
                                        <x-icon name="o-eye" class="inline w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination desktop -->
                <div class="p-4 border-t bg-gray-50">
                    {{ $this->students->links() }}
                </div>
            </div>

            <!-- Version Mobile: Cartes -->
            <div class="space-y-3 md:hidden">
                @foreach($this->students as $student)
                <div class="p-4 bg-white shadow-sm rounded-xl">
                    <!-- En-tête avec avatar et nom -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-lg">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ $student->name }}</p>
                                <p class="text-xs text-gray-500">{{ $student->email }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $student->german_level ?? 'A1' }} - Deutsch</p>
                            </div>
                        </div>
                        <button
                            wire:click="viewStudent({{ $student->id }})"
                            class="px-3 py-1.5 text-sm font-medium text-[#FF6B35] border border-[#FF6B35] rounded-lg hover:bg-orange-50 transition">
                            {{ __('Details') }}
                        </button>
                    </div>

                    <!-- Statistiques -->
                    <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-100">
                        <div class="text-center">
                            <p class="text-xs text-gray-500">{{ __('Kurse') }}</p>
                            <p class="text-lg font-semibold text-blue-600">{{ $student->course_count }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-500">{{ __('Ø Fortschritt') }}</p>
                            <p class="text-lg font-semibold {{ $this->getProgressTextColor($student->avg_progress) }}">
                                {{ $student->avg_progress }}%
                            </p>
                            <div class="w-full h-1.5 bg-gray-200 rounded-full mt-1">
                                <div class="h-1.5 rounded-full {{ $this->getProgressColor($student->avg_progress) }}"
                                     style="width: {{ $student->avg_progress }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Dernière activité -->
                    <div class="pt-2 mt-2 border-t border-gray-100">
                        <p class="text-xs text-gray-500">{{ __('Letzte Aktivität') }}</p>
                        <p class="text-sm text-gray-600">{{ $student->last_activity ?? '-' }}</p>
                    </div>
                </div>
                @endforeach

                <!-- Pagination mobile -->
                <div class="mt-4">
                    {{ $this->students->links() }}
                </div>
            </div>

        @else
            <!-- Empty state -->
            <div class="p-8 text-center bg-white shadow-sm rounded-xl md:p-12">
                @if($search)
                    <x-icon name="o-magnifying-glass" class="w-16 h-16 mx-auto mb-3 text-gray-300" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('Keine Studenten gefunden') }}</h3>
                    <p class="mb-4 text-sm text-gray-500">{{ __('Versuche andere Suchbegriffe.') }}</p>
                    <button wire:click="clearFilters" class="text-[#FF6B35] hover:underline">
                        {{ __('Filter zurücksetzen') }} →
                    </button>
                @elseif($selectedCourse)
                    <x-icon name="o-users" class="w-16 h-16 mx-auto mb-3 text-gray-300" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('Keine Studenten in diesem Kurs') }}</h3>
                    <p class="mb-4 text-sm text-gray-500">{{ __('Dieser Kurs hat noch keine eingeschriebenen Studenten.') }}</p>
                    <button wire:click="$set('selectedCourse', null)" class="text-[#FF6B35] hover:underline">
                        {{ __('Alle Kurse anzeigen') }} →
                    </button>
                @else
                    <x-icon name="o-users" class="w-16 h-16 mx-auto mb-3 text-gray-300" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('Noch keine Studenten') }}</h3>
                    <p class="text-sm text-gray-500">{{ __('Wenn sich Studenten für deine Kurse anmelden, werden sie hier angezeigt.') }}</p>
                @endif
            </div>
        @endif

        <!-- Note MVP -->
        <div class="p-3 mt-6 border border-blue-200 rounded-lg md:p-4 bg-blue-50">
            <div class="flex items-start gap-2">
                <x-icon name="o-information-circle" class="w-4 h-4 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">MVP Version</p>
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('Prochaines fonctionnalités : suivi détaillé des progrès, messagerie directe, carnets de notes et analyses individuelles.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
