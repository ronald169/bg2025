<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Course;
use App\Models\Progress;
use App\Models\Subject;
use App\Models\QuizAttempt;
use App\Models\LearningStreak;
use App\Models\Enrollment;
use App\Models\StudySession;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new
#[Title('My Learning Path - German Learning')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    #[Url(as: 'subject', history: true)]
    public ?int $selectedSubject = null;

    #[Url(as: 'level', history: true)]
    public ?string $selectedLevel = null;

    public string $activeTab = 'path';

    public function getUserProperty()
    {
        return Auth::user();
    }

    public function getEnrolledCoursesProperty()
    {
        return Enrollment::where('user_id', $this->user->id)
            ->with(['course' => function($q) {
                $q->with(['subject', 'lessons']);
            }])
            ->get()
            ->map(function ($enrollment) {
                $course = $enrollment->course;
                $course->progress = $enrollment->progress;
                $course->enrolled_at = $enrollment->enrolled_at;

                $completedLessonIds = Progress::where('user_id', $this->user->id)
                    ->where('is_completed', true)
                    ->pluck('lesson_id')
                    ->toArray();

                $course->next_lesson = $course->lessons()
                    ->whereNotIn('id', $completedLessonIds)
                    ->orderBy('order')
                    ->first();

                $course->lessons_count = $course->lessons()->count();
                return $course;
            });
    }

    public function getPathProgressProperty()
    {
        $subjects = Subject::where('is_active', true)->get();
        $enrolledCourses = $this->enrolledCourses;

        $progress = [];

        foreach ($subjects as $subject) {
            $subjectCourses = $enrolledCourses->filter(function($course) use ($subject) {
                return $course->subject_id === $subject->id;
            });

            if ($subjectCourses->isNotEmpty()) {
                $avgProgress = $subjectCourses->avg('progress');
                $totalCourses = $subjectCourses->count();
                $completedCourses = $subjectCourses->filter(fn($c) => $c->progress >= 100)->count();

                $progress[] = [
                    'subject' => $subject,
                    'courses' => $subjectCourses,
                    'avg_progress' => round($avgProgress),
                    'total_courses' => $totalCourses,
                    'completed_courses' => $completedCourses,
                    'next_course' => $subjectCourses->first(fn($c) => $c->progress < 100),
                ];
            }
        }

        return $progress;
    }

    public function getSkillsProperty()
    {
        $germanLevel = $this->user->german_level ?? 'A1';

        $skillsByLevel = [
            'A1' => [
                ['name' => __('Basic Vocabulary'), 'icon' => 'o-book-open', 'description' => __('First German words and sentences')],
                ['name' => __('Simple Grammar'), 'icon' => 'o-pencil', 'description' => __('Articles, present tense verbs')],
                ['name' => __('Sentence Structure'), 'icon' => 'o-chart-bar', 'description' => __('Main clauses and simple subordinate clauses')],
                ['name' => __('Pronunciation'), 'icon' => 'o-microphone', 'description' => __('Sounds and stress')],
                ['name' => __('Everyday Communication'), 'icon' => 'o-chat-bubble-left-right', 'description' => __('Introducing yourself, numbers, time')],
            ],
            'A2' => [
                ['name' => __('Expanded Vocabulary'), 'icon' => 'o-book-open', 'description' => __('Daily topics and situations')],
                ['name' => __('Past Tense'), 'icon' => 'o-pencil', 'description' => __('Perfekt and Präteritum')],
                ['name' => __('Subordinate Clauses'), 'icon' => 'o-chart-bar', 'description' => __('Because, that, if, when')],
                ['name' => __('Listening Comprehension'), 'icon' => 'o-microphone', 'description' => __('Understand simple conversations')],
                ['name' => __('Letter Writing'), 'icon' => 'o-document-text', 'description' => __('Personal and formal letters')],
            ],
            'B1' => [
                ['name' => __('Intermediate Vocabulary'), 'icon' => 'o-book-open', 'description' => __('Abstract topics and discussions')],
                ['name' => __('Subjunctive II'), 'icon' => 'o-pencil', 'description' => __('Polite forms and wishes')],
                ['name' => __('Passive Voice'), 'icon' => 'o-chart-bar', 'description' => __('Process and state passive')],
                ['name' => __('Reading Comprehension'), 'icon' => 'o-document-text', 'description' => __('Understand longer texts')],
                ['name' => __('Discussions'), 'icon' => 'o-chat-bubble-left-right', 'description' => __('Express and justify opinions')],
            ],
            'B2' => [
                ['name' => __('Advanced Vocabulary'), 'icon' => 'o-book-open', 'description' => __('Technical language and idioms')],
                ['name' => __('Subjunctive I'), 'icon' => 'o-pencil', 'description' => __('Indirect speech')],
                ['name' => __('Nominalization'), 'icon' => 'o-chart-bar', 'description' => __('Turning verbs into nouns')],
                ['name' => __('Stylistics'), 'icon' => 'o-document-text', 'description' => __('Formal and informal texts')],
                ['name' => __('Presentations'), 'icon' => 'o-presentation-chart', 'description' => __('Giving presentations')],
            ],
            'C1' => [
                ['name' => __('Academic Language'), 'icon' => 'o-book-open', 'description' => __('Academic vocabulary')],
                ['name' => __('Complex Syntax'), 'icon' => 'o-pencil', 'description' => __('Nested sentences')],
                ['name' => __('Rhetoric'), 'icon' => 'o-microphone', 'description' => __('Persuasive argumentation')],
                ['name' => __('Literary Analysis'), 'icon' => 'o-document-text', 'description' => __('Interpret literary texts')],
                ['name' => __('Debates'), 'icon' => 'o-chat-bubble-left-right', 'description' => __('Lead complex discussions')],
            ],
            'C2' => [
                ['name' => __('Nuances'), 'icon' => 'o-book-open', 'description' => __('Subtleties of language')],
                ['name' => __('Style Variations'), 'icon' => 'o-pencil', 'description' => __('Different registers')],
                ['name' => __('Language Intuition'), 'icon' => 'o-sparkles', 'description' => __('Intuitive language understanding')],
                ['name' => __('Creative Writing'), 'icon' => 'o-document-text', 'description' => __('Write your own texts')],
                ['name' => __('Native Level'), 'icon' => 'o-trophy', 'description' => __('Fluent like a native speaker')],
            ],
        ];

        $skills = $skillsByLevel[$germanLevel] ?? $skillsByLevel['A1'];

        foreach ($skills as &$skill) {
            $skill['progress'] = $this->calculateSkillProgress($skill['name']);
        }

        return $skills;
    }

    public function getRecommendationsProperty()
    {
        $enrolledIds = $this->enrolledCourses->pluck('id')->toArray();

        return Course::where('is_published', true)
            ->whereNotIn('id', $enrolledIds)
            ->with(['subject', 'teacher'])
            ->withCount('lessons')
            ->withAvg('reviews', 'rating')
            ->where('level', $this->user->german_level ?? 'A1')
            ->orderBy('enrollments_count', 'desc')
            ->take(3)
            ->get()
            ->map(function ($course) {
                $course->reason = $this->getRecommendationReason($course);
                return $course;
            });
    }

    public function getAchievementsProperty()
    {
        $streak = LearningStreak::where('user_id', $this->user->id)->first();
        $totalStudyTime = StudySession::where('user_id', $this->user->id)->sum('duration_minutes');
        $completedCourses = $this->enrolledCourses->filter(fn($c) => $c->progress >= 100)->count();

        return [
            [
                'name' => __('First Steps'),
                'description' => __('First lesson completed'),
                'icon' => 'o-rocket-launch',
                'progress' => min(100, Progress::where('user_id', $this->user->id)->count() * 20),
                'unlocked' => Progress::where('user_id', $this->user->id)->exists(),
            ],
            [
                'name' => __('Learning Streak'),
                'description' => __('Studied 7 days in a row'),
                'icon' => 'o-fire',
                'progress' => round(min(100, (($streak->current_streak ?? 0) / 7) * 100)),
                'unlocked' => ($streak->current_streak ?? 0) >= 7,
            ],
            [
                'name' => __('Course Master'),
                'description' => __('Completed an entire course'),
                'icon' => 'o-trophy',
                'progress' => min(100, $completedCourses * 100),
                'unlocked' => $completedCourses >= 1,
            ],
            [
                'name' => __('Study Time'),
                'description' => __('Reached 10 hours of study time'),
                'icon' => 'o-clock',
                'progress' => min(100, ($totalStudyTime / 600) * 100),
                'unlocked' => $totalStudyTime >= 600,
            ],
        ];
    }

    private function calculateSkillProgress($skillName): int
    {
        $completedLessons = Progress::where('user_id', $this->user->id)
            ->where('is_completed', true)
            ->count();

        $totalPoints = $this->user->total_points ?? 0;

        $progress = min(100, round(($completedLessons / 20) * 100));
        $progress = min(100, $progress + floor($totalPoints / 100));

        return $progress;
    }

    private function getRecommendationReason($course): string
    {
        $reasons = [
            __('🇩🇪 Perfect for your level ') . ($this->user->german_level ?? 'A1'),
            __('⭐ Popular with other learners'),
            __('📚 Ideal for your learning goals'),
            __('🎯 Recommended based on your progress'),
            __('🚀 Next step on your learning path'),
        ];
        return $reasons[array_rand($reasons)];
    }

    public function getOverallProgressProperty()
    {
        $path = $this->pathProgress;
        if (empty($path)) return 0;
        return round(collect($path)->avg('avg_progress'));
    }

    public function getLevelLabel($level): string
    {
        $levels = [
            'A1' => __('A1 - Beginner'),
            'A2' => __('A2 - Elementary'),
            'B1' => __('B1 - Intermediate'),
            'B2' => __('B2 - Upper Intermediate'),
            'C1' => __('C1 - Advanced'),
            'C2' => __('C2 - Mastery'),
        ];
        return $levels[$level] ?? $level;
    }

    // Helpers pour les couleurs
    public function getProgressColor($progress): string
    {
        if ($progress >= 80) return 'bg-success';
        if ($progress >= 50) return 'bg-primary';
        if ($progress >= 20) return 'bg-warning';
        return 'bg-error';
    }

    public function getLevelBadgeColor($level): string
    {
        return match($level) {
            'A1', 'A2' => 'badge-success',
            'B1', 'B2' => 'badge-warning',
            'C1', 'C2' => 'badge-error',
            default => 'badge-ghost',
        };
    }

    public function render()
    {
        return $this->view([
            'user'            => $this->user,
            'enrolledCourses' => $this->enrolledCourses,
            'pathProgress'    => $this->pathProgress,
            'skills'          => $this->skills,
            'recommendations' => $this->recommendations,
            'achievements'    => $this->achievements,
            'overallProgress' => $this->overallProgress,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold md:text-3xl">🗺️ {{ __('My Learning Path') }}</h1>
            <p class="mt-1 text-base-content/70">{{ __('Your personalized path to German success') }}</p>
        </div>

        {{-- Overall Progress Bar --}}
        <x-card class="mb-8">
            <div class="flex flex-col mb-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold">{{ __('Overall Progress') }}</h2>
                    <p class="text-sm text-base-content/70">{{ __('Complete courses to advance on your learning path') }}</p>
                </div>
                <div class="mt-2 md:mt-0">
                    <span class="text-3xl font-bold text-primary">{{ $overallProgress }}%</span>
                </div>
            </div>
            <div class="w-full h-3 rounded-full bg-base-200">
                <div class="h-3 transition-all duration-500 rounded-full bg-gradient-to-r from-primary to-secondary" style="width: {{ $overallProgress }}%"></div>
            </div>
        </x-card>

        {{-- Tabs Navigation --}}
        <div class="mb-6 tabs tabs-boxed">
            <a class="tab {{ $activeTab === 'path' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'path')">📚 {{ __('Learning Path') }}</a>
            <a class="tab {{ $activeTab === 'skills' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'skills')">⭐ {{ __('Skills') }}</a>
            <a class="tab {{ $activeTab === 'achievements' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'achievements')">🏆 {{ __('Achievements') }}</a>
            <a class="tab {{ $activeTab === 'recommendations' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'recommendations')">💡 {{ __('Recommendations') }}</a>
        </div>

        {{-- Tab: Learning Path --}}
        @if($activeTab === 'path')
            @if(count($pathProgress) > 0)
                <div class="space-y-6">
                    @foreach($pathProgress as $item)
                        <x-card class="overflow-hidden">
                            <div class="p-5 border-b bg-gradient-to-r from-base-200 to-base-100">
                                <div class="flex flex-wrap items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10">
                                            <x-icon name="o-academic-cap" class="w-5 h-5 text-primary" />
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold">{{ $item['subject']->name }}</h3>
                                            <p class="text-sm text-base-content/70">
                                                {{ $item['completed_courses'] }}/{{ $item['total_courses'] }} {{ __('courses completed') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-2xl font-bold text-primary">{{ $item['avg_progress'] }}%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="divide-y divide-base-200">
                                @foreach($item['courses'] as $course)
                                    <div class="p-5 transition hover:bg-base-200">
                                        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <h4 class="font-medium">{{ $course->title }}</h4>
                                                    <x-badge :value="$this->getLevelLabel($course->level)" :class="$this->getLevelBadgeColor($course->level) . ' badge-sm'" />
                                                </div>
                                                <div class="flex flex-wrap gap-3 text-sm text-base-content/70">
                                                    <span class="flex items-center gap-1"><x-icon name="o-book-open" class="w-4 h-4" />{{ $course->lessons_count }} {{ __('lessons') }}</span>
                                                </div>
                                                <div class="mt-3">
                                                    <div class="flex justify-between mb-1 text-sm">
                                                        <span class="text-base-content/70">{{ __('Progress') }}</span>
                                                        <span class="font-medium text-primary">{{ $course->progress }}%</span>
                                                    </div>
                                                    <div class="w-full h-2 rounded-full bg-base-200">
                                                        <div class="h-2 rounded-full {{ $this->getProgressColor($course->progress) }}" style="width: {{ $course->progress }}%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                @if($course->progress >= 100)
                                                    <div class="flex items-center gap-2 text-success">
                                                        <x-icon name="o-check-circle" class="w-5 h-5" />
                                                        <span class="text-sm font-medium">{{ __('Completed') }}</span>
                                                    </div>
                                                @else
                                                    <x-button label="{{ __('Continue') }}" icon="o-play" link="{{ route('student.course.show', $course) }}" class="btn-primary btn-sm" />
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @else
                <x-card class="py-16 text-center">
                    <x-icon name="o-map" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <h3 class="mb-2 text-xl font-semibold">{{ __('Your learning path awaits!') }}</h3>
                    <p class="mb-6 text-base-content/60">{{ __('Enroll in your first course to get started.') }}</p>
                    <x-button link="{{ route('student.catalog') }}" label="{{ __('Discover courses') }}" class="btn-primary" />
                </x-card>
            @endif

        {{-- Tab: Skills --}}
        @elseif($activeTab === 'skills')
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($skills as $skill)
                    <x-card class="transition hover:shadow-md">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10">
                                <x-icon :name="$skill['icon']" class="w-5 h-5 text-primary" />
                            </div>
                            <span class="text-xl font-bold text-primary">{{ $skill['progress'] }}%</span>
                        </div>
                        <h3 class="mb-1 font-semibold">{{ $skill['name'] }}</h3>
                        <p class="mb-3 text-sm text-base-content/70">{{ $skill['description'] }}</p>
                        <div class="w-full h-2 rounded-full bg-base-200">
                            <div class="h-2 rounded-full {{ $this->getProgressColor($skill['progress']) }}" style="width: {{ $skill['progress'] }}%"></div>
                        </div>
                    </x-card>
                @endforeach
            </div>

            <div class="p-4 mt-6 rounded-xl bg-gradient-to-r from-primary/10 to-secondary/10">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-base-content/70">{{ __('Current level') }}</p>
                        <p class="text-xl font-bold">{{ $this->getLevelLabel($user->german_level ?? 'A1') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-base-content/70">{{ __('Next goal') }}</p>
                        <p class="text-xl font-bold text-primary">
                            @php
                                $levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
                                $currentIndex = array_search($user->german_level ?? 'A1', $levels);
                                $nextLevel = $levels[$currentIndex + 1] ?? 'C2+';
                            @endphp
                            {{ $this->getLevelLabel($nextLevel) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-base-content/70">{{ __('Progress to next level') }}</p>
                        <div class="flex items-center gap-3">
                            <div class="w-32 h-2 rounded-full bg-base-200"><div class="h-2 rounded-full bg-primary" style="width: {{ $overallProgress }}%"></div></div>
                            <span class="text-sm font-medium">{{ $overallProgress }}%</span>
                        </div>
                    </div>
                </div>
            </div>

        {{-- Tab: Achievements --}}
        @elseif($activeTab === 'achievements')
            <div class="grid gap-4 md:grid-cols-2">
                @foreach($achievements as $achievement)
                    <x-card :class="$achievement['unlocked'] ? 'border-l-4 border-warning' : ''">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-xl {{ $achievement['unlocked'] ? 'bg-warning/20' : 'bg-base-200' }} flex items-center justify-center flex-shrink-0">
                                <x-icon :name="$achievement['icon']" class="w-6 h-6 {{ $achievement['unlocked'] ? 'text-warning' : 'text-base-content/40' }}" />
                            </div>
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="font-semibold">{{ $achievement['name'] }}</h3>
                                    @if($achievement['unlocked'])
                                        <x-badge value="{{ __('Unlocked') }}" class="badge-success badge-soft" />
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-base-content/70">{{ $achievement['description'] }}</p>
                                @if(!$achievement['unlocked'])
                                    <div class="mt-3">
                                        <div class="flex justify-between mb-1 text-sm"><span class="text-base-content/70">{{ __('Progress') }}</span><span class="text-primary">{{ $achievement['progress'] }}%</span></div>
                                        <div class="w-full h-2 rounded-full bg-base-200"><div class="h-2 rounded-full bg-primary" style="width: {{ $achievement['progress'] }}%"></div></div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </x-card>
                @endforeach
            </div>

        {{-- Tab: Recommendations --}}
        @elseif($activeTab === 'recommendations')
            @if($recommendations->count() > 0)
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($recommendations as $course)
                        <x-card class="overflow-hidden transition hover:shadow-md">
                            <div class="flex items-center justify-center h-24 bg-gradient-to-r from-primary/20 to-secondary/20"><span class="text-4xl">🇩🇪</span></div>
                            <div class="p-4">
                                <h3 class="mb-1 font-semibold">{{ $course->title }}</h3>
                                <p class="mb-2 text-sm text-base-content/70">{{ $course->reason }}</p>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1"><x-icon name="o-star" class="w-4 h-4 text-warning" /><span class="text-sm">{{ number_format($course->reviews_avg_rating ?? 0, 1) }}</span></div>
                                    <x-button label="{{ __('View') }}" link="{{ route('student.course.show', $course) }}" class="btn-primary btn-sm" />
                                </div>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @else
                <x-card class="py-16 text-center">
                    <x-icon name="o-sparkles" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <h3 class="mb-2 text-xl font-semibold">{{ __('No recommendations') }}</h3>
                    <p class="text-base-content/60">{{ __('Complete more courses to get personalized recommendations.') }}</p>
                </x-card>
            @endif
        @endif
    </div>
</div>
