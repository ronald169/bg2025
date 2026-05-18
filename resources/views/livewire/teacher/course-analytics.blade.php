<?php

use App\Models\Course;
use App\Models\Progress;
use App\Models\QuizAttempt;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;

new
#[Title('Course Analytics')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {

    public Course $course;
    public $stats = [];
    public $lessonCompletion = [];
    public $studentProgress = [];

    public function mount(Course $course): void
    {
        if ($course->teacher_id !== auth()->id()) {
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

        $quizPassRate = $quizAttempts > 0
            ? round(($this->course->enrollments()->where('is_passed', true)->count() / $quizAttempts) * 100)
            : 0;

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
        $this->lessonCompletion = $this->course->lessons()
            ->withCount(['progress as completed_count' => function($q) {
                $q->where('is_completed', true);
            }])
            ->get()
            ->map(function ($lesson) {
                $lesson->completion_rate = $this->stats['total_students'] > 0
                    ? round(($lesson->completed_count / $this->stats['total_students']) * 100)
                    : 0;
                return $lesson;
            });
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
                    ->latest('last_accessed')
                    ->first()?->last_accessed;
                return $student;
            });
    }
}; ?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('teacher.courses') }}" class="inline-block mb-2 text-primary-600 hover:text-primary-700">
                ← {{ __('Back to courses') }}
            </a>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Analytics') }}: {{ $course->title }}</h1>
            <p class="text-gray-600">{{ __('Track your course performance') }}</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-card class="border-0 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">{{ __('Students') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_students'] }}</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-primary-100">
                    <x-icon name="o-users" class="w-6 h-6 text-primary-600" />
                </div>
            </div>
        </x-card>

        <x-card class="border-0 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">{{ __('Lessons') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_lessons'] }}</p>
                    <p class="text-xs text-gray-400">{{ $stats['completed_lessons'] }} {{ __('completed') }}</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg">
                    <x-icon name="o-book-open" class="w-6 h-6 text-green-600" />
                </div>
            </div>
        </x-card>

        <x-card class="border-0 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">{{ __('Avg Progress') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['avg_student_progress'] }}%</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg">
                    <x-icon name="o-chart-bar" class="w-6 h-6 text-blue-600" />
                </div>
            </div>
        </x-card>

        <x-card class="border-0 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">{{ __('Quiz Pass Rate') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['quiz_pass_rate'] }}%</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg">
                    <x-icon name="o-document-text" class="w-6 h-6 text-purple-600" />
                </div>
            </div>
        </x-card>
    </div>

    <!-- Lesson Completion Chart -->
    <x-card title="{{ __('Lesson Completion Rate') }}" class="shadow-sm">
        <div class="space-y-4">
            @foreach($lessonCompletion as $lesson)
            <div>
                <div class="flex justify-between mb-1 text-sm">
                    <span class="text-gray-700">{{ $lesson->title }}</span>
                    <span class="font-medium text-primary-600">{{ $lesson->completion_rate }}%</span>
                </div>
                <div class="w-full h-2 bg-gray-200 rounded-full">
                    <div class="h-2 rounded-full bg-primary-500" style="width: {{ $lesson->completion_rate }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </x-card>

    <!-- Student Progress -->
    <x-card title="{{ __('Top Students') }}" class="shadow-sm">
        <div class="space-y-3">
            @foreach($studentProgress as $student)
            <div class="flex items-center justify-between p-3 border rounded-lg">
                <div class="flex items-center space-x-3">
                    @if($student->profile_photo_path)
                        <img src="{{ Storage::url($student->profile_photo_path) }}" class="object-cover w-10 h-10 rounded-full">
                    @else
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary-100">
                            <span class="font-bold text-primary-600">{{ strtoupper(substr($student->name, 0, 1)) }}</span>
                        </div>
                    @endif
                    <div>
                        <p class="font-medium text-gray-900">{{ $student->name }}</p>
                        <p class="text-xs text-gray-500">{{ $student->email }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-primary-600">{{ $student->pivot->progress }}%</p>
                    @if($student->last_activity)
                        <p class="text-xs text-gray-500">{{ $student->last_activity->diffForHumans() }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </x-card>

    <div class="p-4 text-sm text-blue-800 border border-blue-200 rounded-lg bg-blue-50">
        <div class="flex items-start space-x-3">
            <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
            <div>
                <p class="font-medium">{{ __('Coming Soon') }}</p>
                <p>{{ __('Detailed charts, export options, and advanced analytics will be available in future updates.') }}</p>
            </div>
        </div>
    </div>
</div>
