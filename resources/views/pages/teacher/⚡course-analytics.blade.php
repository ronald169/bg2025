<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Course;
use App\Models\Progress;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;

new
#[Title('Course Analytics')]
#[Layout('layouts.app')]
class extends Component {

    public Course $course;
    public array $stats = [];
    public array $lessonCompletion = [];
    public array $studentProgress = [];

    // Getters (remplacent les #[Computed] si nécessaire, mais ici on charge directement dans mount)

    public function mount(Course $course): void
    {
        if ($course->teacher_id != auth()->id()) {
            abort(403);
        }
        $this->course = $course;
        $this->loadAnalytics();
    }

    public function loadAnalytics(): void
    {
        $this->loadStats();
        $this->loadLessonCompletion();
        $this->loadStudentProgress();
    }

    private function loadStats(): void
    {
        $totalStudents = $this->course->enrollments()->count();
        $totalLessons = $this->course->lessons()->count();

        $completedLessons = Progress::whereIn('lesson_id', $this->course->lessons()->pluck('id'))
            ->where('is_completed', true)
            ->count();

        $totalProgress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        $avgStudentProgress = $this->course->enrollments()->avg('progress') ?? 0;

        $quizAttempts = QuizAttempt::whereHas('quiz.lesson', function($q) {
            $q->where('course_id', $this->course->id);
        })->count();

        $passedAttempts = QuizAttempt::whereHas('quiz.lesson', function($q) {
            $q->where('course_id', $this->course->id);
        })->where('is_passed', true)->count();

        $quizPassRate = $quizAttempts > 0 ? round(($passedAttempts / $quizAttempts) * 100) : 0;

        $this->stats = [
            'total_students' => $totalStudents,
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'total_progress' => $totalProgress,
            'avg_student_progress' => round($avgStudentProgress),
            'quiz_attempts' => $quizAttempts,
            'quiz_pass_rate' => $quizPassRate,
            'enrollment_growth' => $this->getEnrollmentGrowth(),
        ];
    }

    private function getEnrollmentGrowth(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = $this->course->enrollments()
                ->whereDate('created_at', '<=', $date)
                ->count();
            $data[] = [
                'date' => $date->format('D'),
                'count' => $count,
            ];
        }
        return $data;
    }

    private function loadLessonCompletion(): void
    {
        $totalStudents = $this->stats['total_students'];
        $this->lessonCompletion = $this->course->lessons()
            ->withCount(['progress as completed_count' => function($q) {
                $q->where('is_completed', true);
            }])
            ->get()
            ->map(function ($lesson) use ($totalStudents) {
                $lesson->completion_rate = $totalStudents > 0
                    ? round(($lesson->completed_count / $totalStudents) * 100)
                    : 0;
                return $lesson;
            })
            ->toArray();
    }

    private function loadStudentProgress(): void
    {
        $this->studentProgress = $this->course->students()
            ->withPivot('progress')
            ->take(10)
            ->get()
            ->map(function ($student) {
                $student->last_activity = Progress::where('user_id', $student->id)
                    ->whereIn('lesson_id', $this->course->lessons()->pluck('id'))
                    ->latest('updated_at')
                    ->first()?->updated_at;
                return $student;
            })
            ->toArray();
    }

    public function render()
    {
        return $this->view([
            'stats' => $this->stats,
            'lessonCompletion' => $this->lessonCompletion,
            'studentProgress' => $this->studentProgress,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="mb-6">
            <a href="{{ route('teacher.courses') }}" wire:navigate class="inline-flex items-center gap-1 mb-2 text-sm text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to courses') }}
            </a>
            <h1 class="text-2xl font-bold md:text-3xl">{{ __('Analytics') }}: {{ $course->title }}</h1>
            <p class="text-sm text-base-content/70">{{ __('Track your course performance') }}</p>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-4">
            <x-stat title="{{ __('Students') }}" :value="$stats['total_students']" icon="o-users" class="text-primary" />
            <x-stat title="{{ __('Lessons') }}" :value="$stats['total_lessons']" icon="o-book-open" class="text-success" />
            <x-stat title="{{ __('Avg Progress') }}" :value="$stats['avg_student_progress'] . '%'" icon="o-chart-bar" class="text-info" />
            <x-stat title="{!! __('Quiz Pass Rate') !!}" :value="$stats['quiz_pass_rate'] . '%'" icon="o-document-text" class="text-secondary" />
        </div>

        {{-- Enrollment Growth Chart (simple bar) --}}
        <x-card title="{{ __('Enrollment Growth (Last 7 days)') }}" icon="o-chart-bar" class="mb-8 shadow-sm">
            <div class="flex items-end justify-between h-32 mb-4">
                @foreach($stats['enrollment_growth'] as $day)
                    @php $maxCount = max(array_column($stats['enrollment_growth'], 'count')) ?: 1; @endphp
                    <div class="flex flex-col items-center w-10">
                        <div class="relative group">
                            <div class="w-8 rounded-t-lg bg-primary/20" style="height: {{ max(4, ($day['count'] / $maxCount) * 80) }}px">
                                <div class="w-full transition-all rounded-t-lg bg-primary" style="height: {{ ($day['count'] / max($maxCount, 1)) * 100 }}%"></div>
                            </div>
                            <div class="absolute hidden px-2 py-1 mb-2 text-xs text-white transform -translate-x-1/2 rounded bg-base-100 bottom-full left-1/2 group-hover:block whitespace-nowrap">
                                {{ $day['count'] }} {{ __('students') }}
                            </div>
                        </div>
                        <span class="mt-2 text-xs text-base-content/60">{{ $day['date'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="text-sm text-center text-base-content/70">{{ __('Total enrollments') }}: {{ $stats['total_students'] }}</div>
        </x-card>

        {{-- Lesson Completion Rate --}}
        <x-card title="{!! __('Lesson Completion Rate') !!}" icon="o-check-circle" class="mb-8 shadow-sm">
            <div class="space-y-4">
                @foreach($lessonCompletion as $lesson)
                    <div>
                        <div class="flex justify-between mb-1 text-sm">
                            <span class="text-base-content/80">{{ $lesson['title'] }}</span>
                            <span class="font-medium text-primary">{{ $lesson['completion_rate'] }}%</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-base-200">
                            <div class="h-2 rounded-full bg-primary" style="width: {{ $lesson['completion_rate'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <x-slot:actions>
                <div class="text-xs text-base-content/60">{{ __('Percentage of students who completed each lesson') }}</div>
            </x-slot:actions>
        </x-card>

        {{-- Top Students --}}
        <x-card title="{{ __('Top Students') }}" icon="o-trophy" class="shadow-sm">
            <div class="space-y-3">
                @forelse($studentProgress as $student)
                    <div class="flex items-center justify-between p-3 border rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 text-sm font-bold text-white rounded-full bg-gradient-to-r from-primary to-secondary">
                                {{ strtoupper(substr($student['name'], 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-medium">{{ $student['name'] }}</p>
                                <p class="text-xs text-base-content/60">{{ $student['email'] }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-primary">{{ $student['pivot']['progress'] }}%</p>
                            @if($student['last_activity'])
                                <p class="text-xs text-base-content/50">{{ \Carbon\Carbon::parse($student['last_activity'])->diffForHumans() }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center">
                        <x-icon name="o-users" class="w-12 h-12 mx-auto mb-2 text-base-content/30" />
                        <p class="text-base-content/60">{{ __('No students enrolled yet') }}</p>
                    </div>
                @endforelse
            </div>
            @if(count($studentProgress) > 0)
                <x-slot:actions>
                    <x-button label="{{ __('View all students') }} →" link="{{ route('teacher.students', ['course' => $course]) }}" class="btn-ghost btn-sm" />
                </x-slot:actions>
            @endif
        </x-card>
    </div>
</div>
