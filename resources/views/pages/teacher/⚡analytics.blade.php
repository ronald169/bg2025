<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Progress;
use App\Models\QuizAttempt;
use App\Models\StudySession;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;
use Mary\Traits\Toast;

new
#[Title('Analytics - Teacher')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    #[Url(as: 'course', history: true)]
    public $selectedCourse = null;

    // Getters
    public function getCoursesProperty()
    {
        return Course::where('teacher_id', auth()->id())
            ->withCount(['enrollments', 'lessons'])
            ->get();
    }

    public function getStatsProperty()
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
                    $totalScorePercentage += round(($attempt->score / $totalPoints) * 100);
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

    public function getCoursePerformanceProperty()
    {
        return $this->courses->map(function ($course) {
            $totalLessons = $course->lessons()->count();
            $completedLessons = Progress::whereIn('lesson_id', $course->lessons()->pluck('id'))
                ->where('is_completed', true)
                ->count();

            $avgProgress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

            $completedCourses = $course->enrollments()->where('progress', '>=', 100)->count();
            $completionRate = $course->enrollments_count > 0 ? round(($completedCourses / $course->enrollments_count) * 100) : 0;

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

    public function getTopStudentsProperty()
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

    public function getRecentActivityProperty()
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
            $this->success(__('Course selected: ') . $course->title);
        }
    }

    public function clearFilter(): void
    {
        $this->selectedCourse = null;
        $this->success(__('Filter reset.'));
    }

    public function formatStudyTime($minutes): string
    {
        if ($minutes < 60) return "{$minutes} min";
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
    }

    public function getProgressColor($progress): string
    {
        if ($progress >= 80) return 'bg-success';
        if ($progress >= 50) return 'bg-primary';
        if ($progress >= 20) return 'bg-warning';
        return 'bg-gray-400';
    }

    public function render()
    {
        return $this->view([
            'courses'           => $this->courses,
            'stats'             => $this->stats,
            'coursePerformance' => $this->coursePerformance,
            'topStudents'       => $this->topStudents,
            'recentActivity'    => $this->recentActivity,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">📊 {{ __('Analytics') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('Track your course performance') }}</p>
            </div>
            @if($selectedCourse)
                <x-button wire:click="clearFilter" label="{{ __('Reset filter') }}" icon="o-x-mark" class="btn-outline btn-sm" />
            @endif
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-3 lg:grid-cols-6">
            <x-stat title="{{ __('Courses') }}" :value="$stats['total_courses']" icon="o-academic-cap" class="text-primary" />
            <x-stat title="{{ __('Students') }}" :value="$stats['total_students']" icon="o-users" class="text-success" />
            <x-stat title="{{ __('Lessons') }}" :value="$stats['total_lessons']" icon="o-book-open" class="text-info" />
            <x-stat title="{{ __('Enrollments') }}" :value="$stats['total_enrollments']" icon="o-user-plus" class="text-secondary" />
            <x-stat title="{{ __('Avg quiz') }}" :value="$stats['avg_quiz_score'] . '%'" icon="o-document-text" class="text-warning" />
            <x-stat title="{{ __('Study time') }}" :value="$this->formatStudyTime($stats['total_study_time'])" icon="o-clock" class="text-accent" />
        </div>

        {{-- Course filter --}}
        @if($courses->count() > 0)
            <div class="p-4 mb-6 shadow-sm bg-base-100 rounded-xl">
                <label class="block mb-2 text-sm font-medium">{{ __('Filter by course') }}</label>
                <x-select wire:model.live="selectedCourse" :options="collect($courses)->prepend(['id' => '', 'title' => __('All courses') . ' (' . $courses->count() . ')'])->toArray()" option-value="id" option-label="title" id="course_filter" name="course_filter" />
            </div>
        @endif

        {{-- Course Performance --}}
        <x-card title="{{ __('Course Performance') }}" icon="o-chart-bar" class="mb-6 shadow-sm">
            @if($coursePerformance->count() > 0)
                {{-- Mobile cards --}}
                <div class="space-y-4 md:hidden">
                    @foreach($coursePerformance as $course)
                        @if(!$selectedCourse || $course['id'] == $selectedCourse)
                            <div class="p-3 border rounded-lg">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold">{{ Str::limit($course['title'], 30) }}</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <x-badge :value="$course['level']" class="badge-neutral badge-soft" />
                                            <span class="text-xs text-base-content/60">{{ $course['lessons'] }} {{ __('lessons') }}</span>
                                        </div>
                                    </div>
                                    <span class="font-bold">{{ $course['students'] }} 👥</span>
                                </div>
                                <div class="mt-2">
                                    <div class="flex justify-between mb-1 text-xs"><span>{{ __('Progress') }}</span><span>{{ $course['avg_progress'] }}%</span></div>
                                    <div class="w-full h-1.5 bg-base-200 rounded-full"><div class="h-1.5 rounded-full {{ $this->getProgressColor($course['avg_progress']) }}" style="width: {{ $course['avg_progress'] }}%"></div></div>
                                </div>
                                <div class="flex justify-between mt-2 text-xs">
                                    <x-badge :value="__('Completion') . ': ' . $course['completion_rate'] . '%'" :class="$course['completion_rate'] >= 70 ? 'badge-success' : 'badge-warning'" class="badge-soft" />
                                    <span>⏱️ {{ $this->formatStudyTime($course['study_time']) }}</span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Desktop table --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-base-200">
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Course') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Level') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Students') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Progress') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Completion') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Study time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($coursePerformance as $course)
                                @if(!$selectedCourse || $course['id'] == $selectedCourse)
                                    <tr class="border-b hover:bg-base-200">
                                        <td class="px-4 py-3">
                                            <p class="font-medium">{{ Str::limit($course['title'], 25) }}</p>
                                            <p class="text-xs text-base-content/60">{{ $course['lessons'] }} {{ __('lessons') }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-center"><x-badge :value="$course['level']" class="badge-neutral badge-soft" /></td>
                                        <td class="px-4 py-3 font-medium text-center">{{ $course['students'] }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2"><span class="text-xs font-medium">{{ $course['avg_progress'] }}%</span><div class="w-16 h-1.5 bg-base-200 rounded-full"><div class="h-1.5 rounded-full {{ $this->getProgressColor($course['avg_progress']) }}" style="width: {{ $course['avg_progress'] }}%"></div></div></div>
                                        </td>
                                        <td class="px-4 py-3 text-center"><x-badge :value="$course['completion_rate'] . '%'" :class="$course['completion_rate'] >= 70 ? 'badge-success' : 'badge-warning'" class="badge-soft" /></td>
                                        <td class="px-4 py-3 text-sm text-center">{{ $this->formatStudyTime($course['study_time']) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 text-center"><x-icon name="o-chart-bar" class="w-16 h-16 mx-auto text-base-content/30" /><p class="mt-2">{{ __('No courses yet') }}</p></div>
            @endif
        </x-card>

        {{-- Two columns --}}
        <div class="grid gap-6 md:grid-cols-2">
            {{-- Top Students --}}
            <x-card title="{{ __('Top Students') }}" icon="o-trophy" class="shadow-sm">
                @if($topStudents->count() > 0)
                    <div class="space-y-3">
                        @foreach($topStudents as $index => $student)
                            <div class="flex items-center justify-between p-2 border rounded-lg">
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center justify-center w-8 h-8 font-bold rounded-full bg-primary/20 text-primary">{{ $index + 1 }}</div>
                                    <div><p class="font-medium">{{ $student->name }}</p><p class="text-xs text-base-content/60">{{ $student->email }}</p></div>
                                </div>
                                <div class="text-right"><span class="font-semibold text-success">{{ $student->avg_progress }}%</span><p class="text-xs text-base-content/60">{{ $student->course_count }} {{ __('courses') }}</p></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center"><x-icon name="o-users" class="w-12 h-12 mx-auto text-base-content/30" /><p>{{ __('No students yet') }}</p></div>
                @endif
            </x-card>

            {{-- Recent Activity --}}
            <x-card title="{{ __('Recent Activity') }}" icon="o-clock" class="shadow-sm">
                @if($recentActivity->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentActivity as $activity)
                            <div class="flex items-center gap-3 p-2 border rounded-lg">
                                <div class="w-8 h-8 rounded-full bg-{{ $activity['color'] }}/20 flex items-center justify-center"><x-icon :name="$activity['icon']" class="w-4 h-4 text-{{ $activity['color'] }}/70" /></div>
                                <div class="flex-1"><p class="text-sm font-medium">{{ $activity['user_name'] }}</p><p class="text-xs text-base-content/60">{{ $activity['type'] === 'enrollment' ? __('Enrolled in') . ' "' . Str::limit($activity['course_title'], 25) . '"' : __('Completed') . ' "' . Str::limit($activity['lesson_title'], 25) . '"' }}</p></div>
                                <div class="text-xs text-right text-base-content/50">{{ $activity['date']->diffForHumans() }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center"><x-icon name="o-clock" class="w-12 h-12 mx-auto text-base-content/30" /><p>{{ __('No activity yet') }}</p></div>
                @endif
            </x-card>
        </div>
    </div>
</div>
