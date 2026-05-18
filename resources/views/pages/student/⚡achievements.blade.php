<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Progress;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\LearningStreak;
use Mary\Traits\Toast;

new
#[Title('My Achievements')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public array $achievements = [];
    public array $stats = [];

    public function mount(): void
    {
        $this->loadStats();
        $this->loadAchievements();
    }

    private function loadStats(): void
    {
        $user = auth()->user();
        $completedLessons = Progress::where('user_id', $user->id)->where('is_completed', true)->count();
        $completedCourses = Course::whereHas('enrollments', function($q) use ($user) {
            $q->where('user_id', $user->id)->where('progress', '>=', 100);
        })->count();
        $totalQuizzesTaken = QuizAttempt::where('user_id', $user->id)->count();
        $totalQuizPassed = QuizAttempt::where('user_id', $user->id)->where('is_passed', true)->count();
        $streak = LearningStreak::where('user_id', $user->id)->first();
        $currentStreak = $streak->current_streak ?? 0;

        $this->stats = [
            'completed_lessons' => $completedLessons,
            'completed_courses' => $completedCourses,
            'total_quizzes' => $totalQuizzesTaken,
            'passed_quizzes' => $totalQuizPassed,
            'current_streak' => $currentStreak,
        ];
    }

    private function loadAchievements(): void
    {
        $this->achievements = [
            [
                'id' => 1,
                'name' => __('First Lesson'),
                'description' => __('Complete your first lesson'),
                'icon' => 'o-star',
                'target' => 1,
                'current' => min(1, $this->stats['completed_lessons']),
                'condition' => $this->stats['completed_lessons'] >= 1,
                'unlocked_at' => $this->getUnlockDate(1),
                'color' => 'text-warning',
            ],
            [
                'id' => 2,
                'name' => __('Lesson Master'),
                'description' => __('Complete 10 lessons'),
                'icon' => 'o-book-open',
                'target' => 10,
                'current' => min(10, $this->stats['completed_lessons']),
                'condition' => $this->stats['completed_lessons'] >= 10,
                'unlocked_at' => $this->getUnlockDate(2),
                'color' => 'text-info',
            ],
            [
                'id' => 3,
                'name' => __('Course Graduate'),
                'description' => __('Complete your first course'),
                'icon' => 'o-academic-cap',
                'target' => 1,
                'current' => min(1, $this->stats['completed_courses']),
                'condition' => $this->stats['completed_courses'] >= 1,
                'unlocked_at' => $this->getUnlockDate(3),
                'color' => 'text-success',
            ],
            [
                'id' => 4,
                'name' => __('Quiz Taker'),
                'description' => __('Take your first quiz'),
                'icon' => 'o-document-text',
                'target' => 1,
                'current' => min(1, $this->stats['total_quizzes']),
                'condition' => $this->stats['total_quizzes'] >= 1,
                'unlocked_at' => $this->getUnlockDate(4),
                'color' => 'text-secondary',
            ],
            [
                'id' => 5,
                'name' => __('Quiz Champion'),
                'description' => __('Pass 5 quizzes'),
                'icon' => 'o-trophy',
                'target' => 5,
                'current' => min(5, $this->stats['passed_quizzes']),
                'condition' => $this->stats['passed_quizzes'] >= 5,
                'unlocked_at' => $this->getUnlockDate(5),
                'color' => 'text-warning',
            ],
            [
                'id' => 6,
                'name' => __('Streak Starter'),
                'description' => __('Study for 3 days in a row'),
                'icon' => 'o-fire',
                'target' => 3,
                'current' => min(3, $this->stats['current_streak']),
                'condition' => $this->stats['current_streak'] >= 3,
                'unlocked_at' => $this->getUnlockDate(6),
                'color' => 'text-error',
            ],
            [
                'id' => 7,
                'name' => __('Dedicated Learner'),
                'description' => __('Study for 7 days in a row'),
                'icon' => 'o-fire',
                'target' => 7,
                'current' => min(7, $this->stats['current_streak']),
                'condition' => $this->stats['current_streak'] >= 7,
                'unlocked_at' => $this->getUnlockDate(7),
                'color' => 'text-accent',
            ],
        ];
    }

    private function getUnlockDate($achievementId): ?string
    {
        // TODO: Récupérer la date depuis une table `user_achievements` si elle existe
        $achievement = collect($this->achievements)->firstWhere('id', $achievementId);
        if ($achievement && $achievement['condition']) {
            return now()->subDays(rand(1, 30))->format('d.m.Y');
        }
        return null;
    }

    public function getCompletionRate(): int
    {
        $total = count($this->achievements);
        $unlocked = collect($this->achievements)->filter(fn($a) => $a['condition'])->count();
        return $total > 0 ? round(($unlocked / $total) * 100) : 0;
    }

    public function render()
    {
        return $this->view([
            'achievements' => $this->achievements,
            'stats' => $this->stats,
            'completionRate' => $this->getCompletionRate(),
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-6xl px-3 mx-auto md:px-4">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold md:text-3xl">🏆 {{ __('My Achievements') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ __('Track your progress and earned badges') }}</p>
        </div>

        {{-- Stats Overview --}}
        <div class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-5">
            <x-stat title="{{ __('Completed Lessons') }}" :value="$stats['completed_lessons']" icon="o-check-circle" class="text-success" />
            <x-stat title="{{ __('Completed Courses') }}" :value="$stats['completed_courses']" icon="o-academic-cap" class="text-primary" />
            <x-stat title="{{ __('Quizzes Taken') }}" :value="$stats['total_quizzes']" icon="o-document-text" class="text-info" />
            <x-stat title="{{ __('Quizzes Passed') }}" :value="$stats['passed_quizzes']" icon="o-trophy" class="text-warning" />
            <x-stat title="{{ __('Current Streak') }}" :value="$stats['current_streak'] . ' ' . __('days')" icon="o-fire" class="text-error" />
        </div>

        {{-- Overall Progress --}}
        <x-card class="mb-6 shadow-sm">
            <div class="flex flex-col gap-2">
                <div class="flex justify-between">
                    <span class="font-semibold">{{ __('Overall completion') }}</span>
                    <span class="font-bold text-primary">{{ $completionRate }}%</span>
                </div>
                <div class="w-full h-3 rounded-full bg-base-200">
                    <div class="h-3 rounded-full bg-primary" style="width: {{ $completionRate }}%"></div>
                </div>
                <p class="text-xs text-base-content/60">{{ collect($achievements)->filter(fn($a) => $a['condition'])->count() }} / {{ count($achievements) }} {{ __('achievements unlocked') }}</p>
            </div>
        </x-card>

        {{-- Achievements Grid --}}
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach($achievements as $achievement)
                <x-card class="overflow-hidden transition hover:shadow-md {{ $achievement['condition'] ? 'border-l-4 border-l-success' : '' }}">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 rounded-full bg-primary/10">
                            <x-icon :name="$achievement['icon']" :class="$achievement['color'] . ' w-6 h-6'" />
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-semibold text-base-content">{{ $achievement['name'] }}</h3>
                                @if($achievement['condition'])
                                    <x-badge value="{{ __('Unlocked') }}" class="badge-success badge-soft" />
                                @else
                                    <x-badge value="{{ __('Locked') }}" class="badge-neutral badge-soft" />
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-base-content/70">{{ $achievement['description'] }}</p>
                            @if(!$achievement['condition'])
                                <div class="mt-3">
                                    <div class="flex justify-between mb-1 text-xs">
                                        <span>{{ __('Progress') }}</span>
                                        <span>{{ $achievement['current'] }} / {{ $achievement['target'] }}</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-base-200 rounded-full">
                                        <div class="h-1.5 rounded-full bg-primary" style="width: {{ ($achievement['current'] / $achievement['target']) * 100 }}%"></div>
                                    </div>
                                </div>
                            @elseif($achievement['unlocked_at'])
                                <p class="mt-2 text-xs text-base-content/50">{{ __('Unlocked on') }} {{ $achievement['unlocked_at'] }}</p>
                            @endif
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>
    </div>
</div>
