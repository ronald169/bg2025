<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Progress;
use App\Models\QuizAttempt;
use App\Models\StudySession;
use App\Models\Lesson;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Mary\Traits\Toast;

new
#[Title('Analytics - Lehrer')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use Toast;

    #[Url(as: 'course', history: true)]
    public $selectedCourse = null;

    #[Computed]
    public function courses()
    {
        return Course::where('teacher_id', auth()->id())
            ->withCount(['enrollments', 'lessons'])
            ->get();
    }

    #[Computed]
    public function stats()
    {
        $courseIds = $this->courses->pluck('id');

        $totalStudents = Enrollment::whereIn('course_id', $courseIds)->distinct('user_id')->count('user_id');
        $totalEnrollments = Enrollment::whereIn('course_id', $courseIds)->count();
        $totalLessons = Lesson::whereIn('course_id', $courseIds)->count();
        $totalStudyTime = StudySession::whereIn('course_id', $courseIds)->sum('duration_minutes');

        $lessonIds = Lesson::whereIn('course_id', $courseIds)->pluck('id');
        $quizIds = \App\Models\Quiz::whereIn('lesson_id', $lessonIds)->pluck('id');

        $quizAttempts = QuizAttempt::whereIn('quiz_id', $quizIds)->get();
        $totalScorePercentage = 0;
        $attemptCount = 0;

        foreach ($quizAttempts as $attempt) {
            $quiz = $attempt->quiz;
            if ($quiz && $quiz->questions) {
                $totalPoints = $quiz->questions->sum('points');
                if ($totalPoints > 0) {
                    $percentage = round(($attempt->score / $totalPoints) * 100);
                    $totalScorePercentage += $percentage;
                    $attemptCount++;
                }
            }
        }

        $avgQuizScore = $attemptCount > 0 ? round($totalScorePercentage / $attemptCount) : 0;

        return [
            'total_courses' => $this->courses->count(),
            'total_students' => $totalStudents,
            'total_lessons' => $totalLessons,
            'total_enrollments' => $totalEnrollments,
            'total_study_time' => $totalStudyTime,
            'avg_quiz_score' => $avgQuizScore,
            'avg_completion_rate' => $this->calculateAvgCompletionRate($courseIds),
        ];
    }

    #[Computed]
    public function coursePerformance()
    {
        return $this->courses->map(function ($course) {
            $totalLessons = $course->lessons()->count();
            $completedLessons = Progress::whereIn('lesson_id', $course->lessons()->pluck('id'))
                ->where('is_completed', true)
                ->count();

            $avgProgress = $totalLessons > 0
                ? round(($completedLessons / $totalLessons) * 100)
                : 0;

            $completedCourses = $course->enrollments()->where('progress', '>=', 100)->count();
            $completionRate = $course->enrollments_count > 0
                ? round(($completedCourses / $course->enrollments_count) * 100)
                : 0;

            $studyTime = StudySession::where('course_id', $course->id)->sum('duration_minutes');

            return [
                'id' => $course->id,
                'title' => $course->title,
                'level' => $course->level ?? 'A1',
                'students' => $course->enrollments_count,
                'lessons' => $course->lessons_count,
                'avg_progress' => $avgProgress,
                'completion_rate' => $completionRate,
                'study_time' => $studyTime,
            ];
        });
    }

    #[Computed]
    public function topStudents()
    {
        $courseIds = $this->courses->pluck('id');

        return DB::table('users')
            ->join('enrollments', 'users.id', '=', 'enrollments.user_id')
            ->whereIn('enrollments.course_id', $courseIds)
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('ROUND(AVG(enrollments.progress)) as avg_progress'),
                DB::raw('COUNT(DISTINCT enrollments.course_id) as course_count')
            )
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('avg_progress', 'desc')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function recentActivity()
    {
        $courseIds = $this->courses->pluck('id');
        $lessonIds = Lesson::whereIn('course_id', $courseIds)->pluck('id');

        $recentEnrollments = Enrollment::whereIn('course_id', $courseIds)
            ->with(['user', 'course'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'type' => 'enrollment',
                    'user_name' => $enrollment->user->name,
                    'course_title' => $enrollment->course->title,
                    'date' => $enrollment->created_at,
                    'icon' => 'o-user-plus',
                    'color' => 'green',
                ];
            });

        $recentCompletions = Progress::whereIn('lesson_id', $lessonIds)
            ->where('is_completed', true)
            ->with(['user', 'lesson.course'])
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(function ($progress) {
                return [
                    'type' => 'completion',
                    'user_name' => $progress->user->name,
                    'lesson_title' => $progress->lesson->title,
                    'course_title' => $progress->lesson->course->title,
                    'date' => $progress->updated_at,
                    'icon' => 'o-check-circle',
                    'color' => 'blue',
                ];
            });

        return $recentEnrollments->concat($recentCompletions)
            ->sortByDesc('date')
            ->take(10)
            ->values();
    }

    private function calculateAvgCompletionRate($courseIds): int
    {
        $enrollments = Enrollment::whereIn('course_id', $courseIds)->get();
        if ($enrollments->isEmpty()) return 0;
        return round($enrollments->avg('progress'));
    }

    public function selectCourse($courseId): void
    {
        $this->selectedCourse = $courseId;
        $course = $this->courses->firstWhere('id', $courseId);
        if ($course) {
            $this->success('Kurs ausgewählt: ' . $course->title);
        }
    }

    public function clearFilter(): void
    {
        $this->selectedCourse = null;
        $this->success('Filter zurückgesetzt.');
    }

    public function getFormattedStudyTime($minutes): string
    {
        if ($minutes < 60) return "{$minutes} min";
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
    }

    public function getProgressColor($progress): string
    {
        if ($progress >= 80) return 'bg-green-500';
        if ($progress >= 50) return 'bg-blue-500';
        if ($progress >= 20) return 'bg-yellow-500';
        return 'bg-gray-400';
    }
}
?>

<div class="py-4 md:py-8">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        <!-- Header -->
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">📊 Analytics</h1>
                <p class="text-gray-500 text-xs md:text-sm mt-0.5">Verfolge deine Kursleistungen</p>
            </div>
            @if($selectedCourse)
                <button
                    wire:click="clearFilter"
                    class="self-start px-3 py-1.5 text-xs text-[#FF6B35] border border-[#FF6B35] rounded-lg hover:bg-orange-50 transition">
                    <x-icon name="o-x-mark" class="inline w-3 h-3 mr-1" />
                    Filter zurücksetzen
                </button>
            @endif
        </div>

        <!-- Stats Cards - Grille responsive sans overflow -->
        <div class="grid grid-cols-2 gap-2 mb-6 sm:grid-cols-3 lg:grid-cols-6 md:gap-3">
            <div class="bg-white rounded-lg p-2.5 md:p-3 text-center shadow-sm border-l-3 border-l-[#FF6B35]">
                <p class="text-[10px] md:text-xs text-gray-500">Kurse</p>
                <p class="text-lg font-bold text-gray-900 md:text-xl">{{ $this->stats['total_courses'] }}</p>
            </div>
            <div class="bg-white rounded-lg p-2.5 md:p-3 text-center shadow-sm border-l-3 border-l-green-500">
                <p class="text-[10px] md:text-xs text-gray-500">Studenten</p>
                <p class="text-lg font-bold text-gray-900 md:text-xl">{{ $this->stats['total_students'] }}</p>
            </div>
            <div class="bg-white rounded-lg p-2.5 md:p-3 text-center shadow-sm border-l-3 border-l-blue-500">
                <p class="text-[10px] md:text-xs text-gray-500">Lektionen</p>
                <p class="text-lg font-bold text-gray-900 md:text-xl">{{ $this->stats['total_lessons'] }}</p>
            </div>
            <div class="bg-white rounded-lg p-2.5 md:p-3 text-center shadow-sm border-l-3 border-l-purple-500">
                <p class="text-[10px] md:text-xs text-gray-500">Einschreibungen</p>
                <p class="text-lg font-bold text-gray-900 md:text-xl">{{ $this->stats['total_enrollments'] }}</p>
            </div>
            <div class="bg-white rounded-lg p-2.5 md:p-3 text-center shadow-sm border-l-3 border-l-yellow-500">
                <p class="text-[10px] md:text-xs text-gray-500">Ø Quiz</p>
                <p class="text-lg font-bold text-yellow-600 md:text-xl">{{ $this->stats['avg_quiz_score'] }}%</p>
            </div>
            <div class="bg-white rounded-lg p-2.5 md:p-3 text-center shadow-sm border-l-3 border-l-teal-500">
                <p class="text-[10px] md:text-xs text-gray-500">Lernzeit</p>
                <p class="text-xs font-bold text-gray-900 md:text-sm">{{ $this->getFormattedStudyTime($this->stats['total_study_time']) }}</p>
            </div>
        </div>

        <!-- Course Filter -->
        @if($this->courses->count() > 0)
        <div class="p-3 mb-6 bg-white shadow-sm rounded-xl md:p-4">
            <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1.5">Kurs filtern</label>
            <select wire:model.live="selectedCourse"
                    class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                <option value="">Alle Kurse ({{ $this->courses->count() }})</option>
                @foreach($this->courses as $course)
                    <option value="{{ $course->id }}">
                        {{ Str::limit($course->title, 35) }} ({{ $course->enrollments_count }})
                    </option>
                @endforeach
            </select>
        </div>
        @endif

        <!-- Course Performance - Version cartes responsive -->
        <div class="mb-6 overflow-hidden bg-white shadow-sm rounded-xl">
            <div class="flex items-center gap-2 p-3 border-b md:p-4">
                <x-icon name="o-chart-bar" class="w-4 h-4 md:w-5 md:h-5 text-[#FF6B35]" />
                <h2 class="text-sm font-semibold text-gray-900 md:text-base">Kursleistung</h2>
            </div>

            @if($this->coursePerformance->count() > 0)
                <!-- Version Mobile: Cartes -->
                <div class="divide-y divide-gray-100 md:hidden">
                    @foreach($this->coursePerformance as $course)
                        @if(!$selectedCourse || $course['id'] == $selectedCourse)
                        <div class="p-3 space-y-2">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ Str::limit($course['title'], 30) }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-gray-100 text-gray-600">
                                            {{ $course['level'] }}
                                        </span>
                                        <span class="text-xs text-gray-500">{{ $course['lessons'] }} Lektionen</span>
                                    </div>
                                </div>
                                <span class="text-sm font-bold text-gray-900">{{ $course['students'] }} 👥</span>
                            </div>

                            <div>
                                <div class="flex justify-between mb-1 text-xs">
                                    <span class="text-gray-500">Fortschritt</span>
                                    <span class="font-medium">{{ $course['avg_progress'] }}%</span>
                                </div>
                                <div class="w-full h-1.5 bg-gray-200 rounded-full">
                                    <div class="h-1.5 rounded-full {{ $this->getProgressColor($course['avg_progress']) }}"
                                         style="width: {{ $course['avg_progress'] }}%"></div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-1">
                                <span class="px-2 py-0.5 text-[10px] rounded-full {{ $course['completion_rate'] >= 70 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    Abschluss: {{ $course['completion_rate'] }}%
                                </span>
                                <span class="text-xs text-gray-500">
                                    ⏱️ {{ $this->getFormattedStudyTime($course['study_time']) }}
                                </span>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>

                <!-- Version Desktop: Tableau compact -->
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-gray-50">
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">Kurs</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">Level</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">Studenten</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">Fortschritt</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">Abschluss</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">Lernzeit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->coursePerformance as $course)
                                @if(!$selectedCourse || $course['id'] == $selectedCourse)
                                <tr class="transition border-b hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-gray-900">{{ Str::limit($course['title'], 25) }}</p>
                                        <p class="text-xs text-gray-500">{{ $course['lessons'] }} Lektionen</p>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">{{ $course['level'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-center">{{ $course['students'] }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-medium">{{ $course['avg_progress'] }}%</span>
                                            <div class="w-16 h-1.5 bg-gray-200 rounded-full">
                                                <div class="h-1.5 rounded-full {{ $this->getProgressColor($course['avg_progress']) }}"
                                                     style="width: {{ $course['avg_progress'] }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $course['completion_rate'] >= 70 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                            {{ $course['completion_rate'] }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-center text-gray-500">
                                        {{ $this->getFormattedStudyTime($course['study_time']) }}
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-10 text-center">
                    <x-icon name="o-chart-bar" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                    <p class="text-sm text-gray-500">Keine Kurse vorhanden</p>
                </div>
            @endif
        </div>

        <!-- Two Columns - Responsive empilé sur mobile -->
        <div class="grid gap-5 md:grid-cols-2">
            <!-- Top Students -->
            <div class="overflow-hidden bg-white shadow-sm rounded-xl">
                <div class="flex items-center gap-2 p-3 border-b md:p-4">
                    <x-icon name="o-trophy" class="w-4 h-4 text-yellow-500 md:w-5 md:h-5" />
                    <h2 class="text-sm font-semibold text-gray-900 md:text-base">Beste Studenten</h2>
                </div>

                @if($this->topStudents->count() > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach($this->topStudents as $index => $student)
                        <div class="flex items-center justify-between gap-2 p-3">
                            <div class="flex items-center min-w-0 gap-2">
                                <div class="w-7 h-7 md:w-8 md:h-8 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $student->name }}</p>
                                    <p class="text-[10px] text-gray-500 truncate">{{ $student->email }}</p>
                                </div>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <p class="text-sm font-semibold text-green-600">{{ $student->avg_progress }}%</p>
                                <p class="text-[10px] text-gray-500">{{ $student->course_count }} Kurse</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center">
                        <x-icon name="o-users" class="w-10 h-10 mx-auto mb-2 text-gray-300" />
                        <p class="text-sm text-gray-500">Keine Studenten</p>
                    </div>
                @endif
            </div>

            <!-- Recent Activity -->
            <div class="overflow-hidden bg-white shadow-sm rounded-xl">
                <div class="flex items-center gap-2 p-3 border-b md:p-4">
                    <x-icon name="o-clock" class="w-4 h-4 text-blue-500 md:w-5 md:h-5" />
                    <h2 class="text-sm font-semibold text-gray-900 md:text-base">Letzte Aktivitäten</h2>
                </div>

                @if($this->recentActivity->count() > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach($this->recentActivity as $activity)
                        <div class="flex items-center gap-2 p-3">
                            <div class="w-7 h-7 md:w-8 md:h-8 rounded-full bg-{{ $activity['color'] }}-100 flex items-center justify-center flex-shrink-0">
                                <x-icon :name="$activity['icon']" class="w-3.5 h-3.5 md:w-4 md:h-4 text-{{ $activity['color'] }}-600" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $activity['user_name'] }}</p>
                                @if($activity['type'] === 'enrollment')
                                    <p class="text-xs text-gray-500 truncate">Eingeschrieben in "{{ Str::limit($activity['course_title'], 25) }}"</p>
                                @else
                                    <p class="text-xs text-gray-500 truncate">Abgeschlossen "{{ Str::limit($activity['lesson_title'], 25) }}"</p>
                                @endif
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <p class="text-[10px] text-gray-400">{{ $activity['date']->diffForHumans() }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center">
                        <x-icon name="o-clock" class="w-10 h-10 mx-auto mb-2 text-gray-300" />
                        <p class="text-sm text-gray-500">Keine Aktivitäten</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Note MVP -->
        <div class="p-3 mt-6 border border-blue-200 rounded-lg md:p-4 bg-blue-50">
            <div class="flex items-start gap-2">
                <x-icon name="o-information-circle" class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800 md:text-base">MVP Version</p>
                    <p class="text-xs text-blue-700 md:text-sm">Prochaines fonctionnalités : graphiques détaillés, export des données, analyses avancées par étudiant.</p>
                </div>
            </div>
        </div>
    </div>
</div>
