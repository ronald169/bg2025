<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Progress;
use App\Models\StudySession;
use App\Models\QuizAttempt;
use App\Models\LearningStreak;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Mein Dashboard - Deutsch lernen')]
#[Layout('components.layouts.dashboard-student')]
class extends Component {
    use Toast;

    public $activeTab = 'overview';

    #[Computed]
    public function user()
    {
        return Auth::user();
    }

    #[Computed]
    public function stats()
    {
        $user = $this->user;

        $totalCourses = Enrollment::where('user_id', $user->id)->count();
        $completedLessons = Progress::where('user_id', $user->id)
            ->where('is_completed', true)
            ->count();
        $totalLessons = Progress::where('user_id', $user->id)->count();
        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        $totalStudyTime = StudySession::where('user_id', $user->id)->sum('duration_minutes');
        $weeklyStudyTime = StudySession::where('user_id', $user->id)
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('duration_minutes');

        $quizCount = QuizAttempt::where('user_id', $user->id)->count();
        $avgQuizScore = QuizAttempt::where('user_id', $user->id)->avg('score') ?? 0;

        return [
            'total_courses' => $totalCourses,
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
            'progress' => $progress,
            'total_study_time' => $totalStudyTime,
            'weekly_study_time' => $weeklyStudyTime,
            'quiz_count' => $quizCount,
            'avg_quiz_score' => round($avgQuizScore, 1),
        ];
    }

    #[Computed]
    public function recentCourses()
    {
        $user = $this->user;

        return Enrollment::where('user_id', $user->id)
            ->with(['course.subject', 'course.teacher'])
            ->where('progress', '<', 100)
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($enrollment) {
                $course = $enrollment->course;
                $course->progress = $enrollment->progress;

                $lastProgress = Progress::where('user_id', $this->user->id)
                    ->whereIn('lesson_id', $course->lessons->pluck('id'))
                    ->latest('updated_at')
                    ->first();

                $course->last_accessed = $lastProgress?->updated_at;
                $course->lessons_count = $course->lessons()->count();

                return $course;
            });
    }

    #[Computed]
    public function recentActivity()
    {
        $user = $this->user;

        $lessonActivities = Progress::where('user_id', $user->id)
            ->with(['lesson.course'])
            ->where('is_completed', true)
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(function ($progress) {
                return [
                    'id' => $progress->id,
                    'type' => 'lesson',
                    'title' => $progress->lesson->title,
                    'course_title' => $progress->lesson->course->title,
                    'time' => $progress->updated_at,
                    'icon' => 'o-check-circle',
                    'color' => 'green',
                    'url' => route('student.lesson.show', [
                        'course' => $progress->lesson->course,
                        'lesson' => $progress->lesson
                    ]),
                ];
            });

        $quizActivities = QuizAttempt::where('user_id', $user->id)
            ->with(['quiz.lesson.course'])
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function ($attempt) {
                $percentage = $attempt->quiz->total_questions > 0
                    ? round(($attempt->score / $attempt->quiz->total_questions) * 100)
                    : 0;

                return [
                    'id' => $attempt->id,
                    'type' => 'quiz',
                    'title' => $attempt->quiz->title,
                    'course_title' => $attempt->quiz->course->title,
                    'time' => $attempt->created_at,
                    'percentage' => $percentage,
                    'passed' => $percentage >= ($attempt->quiz->passing_score ?? 70),
                    'icon' => 'o-document-text',
                    'color' => $percentage >= 70 ? 'green' : 'orange',
                    'url' => route('student.quiz.results', $attempt->id),
                ];
            });

        return $lessonActivities->concat($quizActivities)
            ->sortByDesc('time')
            ->take(10)
            ->values();
    }

    #[Computed]
    public function recommendedCourses()
    {
        $user = $this->user;
        $enrolledIds = Enrollment::where('user_id', $user->id)->pluck('course_id');

        return Course::where('is_published', true)
            ->whereNotIn('id', $enrolledIds)
            ->with(['subject', 'teacher'])
            ->withCount('lessons')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->take(4)
            ->get();
    }

    #[Computed]
    public function streakData()
    {
        $user = $this->user;
        $streak =$this->user->learningStreak;

        $history = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $hasStudy = StudySession::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->exists();
            $minutes = StudySession::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->sum('duration_minutes');

            $history[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'studied' => $hasStudy,
                'minutes' => $minutes,
            ];
        }

        return [
            'current' => $streak->current_streak ?? 0,
            'longest' => $streak->longest_streak ?? 0,
            'last_study' => $streak->last_study_date,
            'history' => $history,
        ];
    }

    #[Computed]
    public function weeklyProgressData()
    {
        $user = $this->user;
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $data = [];

        foreach ($days as $index => $day) {
            $date = now()->startOfWeek()->addDays($index);
            $minutes = StudySession::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->sum('duration_minutes');

            $data[] = [
                'day' => $day,
                'minutes' => $minutes,
            ];
        }

        return $data;
    }

    public function getFormattedStudyTime($minutes): string
    {
        if ($minutes < 60) return "{$minutes} min";
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
    }

    public function getGreeting(): string
    {
        $hour = now()->hour;
        if ($hour < 12) return 'Guten Morgen';
        if ($hour < 18) return 'Guten Tag';
        return 'Guten Abend';
    }

    public function getMotivationalMessage(): string
    {
        $streak = $this->streakData['current'];

        if ($streak >= 30) return 'Unglaublich! ' . $streak . ' Tage am Stück! Du bist ein Vorbild! 🔥';
        if ($streak >= 7) return 'Super! ' . $streak . ' Tage in Folge! Bleib dran! 🌟';
        if ($streak >= 3) return 'Gut gemacht! ' . $streak . ' Tage am Stück! Weiter so! 💪';
        if ($streak > 0) return 'Schön, dich wiederzusehen! ' . $streak . ' Tag(e) am Stück! 📚';
        return 'Bereit für deine erste Deutschstunde heute? 🇩🇪';
    }
}
?>

<div class="py-8">
    <div class="px-4 mx-auto max-w-7xl">

        <!-- Header avec bienvenue -->
        <div class="bg-gradient-to-r from-[#FF6B35] to-[#1E6091] rounded-2xl p-6 text-white mb-8">
            <div class="flex flex-col items-start justify-between md:flex-row md:items-center">
                <div>
                    <h1 class="text-2xl font-bold">{{ $this->getGreeting() }}, {{ $this->user->name }}! 👋</h1>
                    <p class="mt-1 text-white/80">{{ $this->getMotivationalMessage() }}</p>
                </div>
                <div class="px-4 py-2 mt-4 text-center rounded-lg md:mt-0 bg-white/20">
                    <div class="text-2xl font-bold">{{ $this->streakData['current'] }}</div>
                    <div class="text-xs">Tage am Stück 🔥</div>
                </div>
            </div>
        </div>

        <!-- Statistiques rapides -->
        <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-4">
            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->stats['total_courses'] }}</div>
                <div class="text-sm text-gray-500">Kurse</div>
            </x-card>

            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->stats['completed_lessons'] }}</div>
                <div class="text-sm text-gray-500">Lektionen</div>
                <div class="text-xs text-gray-400">{{ $this->stats['total_lessons'] }} total</div>
            </x-card>

            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->getFormattedStudyTime($this->stats['total_study_time']) }}</div>
                <div class="text-sm text-gray-500">Studienstunden</div>
                <div class="text-xs text-gray-400">Diese Woche: {{ $this->getFormattedStudyTime($this->stats['weekly_study_time']) }}</div>
            </x-card>

            <x-card class="text-center border-0 shadow-sm">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $this->stats['avg_quiz_score'] }}%</div>
                <div class="text-sm text-gray-500">Quiz Ø</div>
                <div class="text-xs text-gray-400">{{ $this->stats['quiz_count'] }} Quiz absolviert</div>
            </x-card>
        </div>

        <!-- Grille principale -->
        <div class="grid gap-8 lg:grid-cols-3">
            <!-- Colonne gauche (2/3) -->
            <div class="space-y-8 lg:col-span-2">

                <!-- Activité hebdomadaire -->
                <x-card title="📊 Wöchentliche Aktivität" class="border-0 shadow-sm">
                    <div class="flex items-end justify-between h-32 mb-4">
                        @foreach($this->weeklyProgressData as $day)
                        <div class="flex flex-col items-center w-10">
                            <div class="relative group">
                                <div class="w-8 bg-[#FF6B35]/20 rounded-t-lg" style="height: {{ max(4, $day['minutes'] * 2) }}px">
                                    <div class="w-full bg-[#FF6B35] rounded-t-lg transition-all"
                                         style="height: {{ min(100, ($day['minutes'] / 120) * 100) }}%"></div>
                                </div>
                                <div class="absolute hidden px-2 py-1 mb-2 text-xs text-white transform -translate-x-1/2 bg-gray-900 rounded bottom-full left-1/2 group-hover:block whitespace-nowrap">
                                    {{ $day['minutes'] }} min
                                </div>
                            </div>
                            <span class="mt-2 text-xs text-gray-500">{{ $day['day'] }}</span>
                        </div>
                        @endforeach
                    </div>
                    <div class="flex justify-between pt-4 text-sm text-gray-500 border-t">
                        <span>Total: {{ $this->getFormattedStudyTime($this->stats['weekly_study_time']) }}</span>
                        <span>Täglich Ø: {{ $this->getFormattedStudyTime(round($this->stats['weekly_study_time'] / 7)) }}</span>
                    </div>
                </x-card>

                <!-- Cours en cours -->
                <x-card title="📚 Meine Kurse" class="border-0 shadow-sm">
                    @if($this->recentCourses->count() > 0)
                        <div class="space-y-4">
                            @foreach($this->recentCourses as $course)
                            <div class="flex flex-col justify-between p-4 transition border rounded-lg md:flex-row md:items-center hover:bg-gray-50">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900">{{ $course->title }}</h4>
                                    <div class="flex items-center gap-3 mt-1 text-sm text-gray-500">
                                        <span>{{ $course->subject->name ?? 'Deutsch' }}</span>
                                        <span>{{ $course->lessons_count }} Lektionen</span>
                                        @if($course->last_accessed)
                                            <span>Zuletzt: {{ $course->last_accessed->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-2">
                                        <div class="flex justify-between mb-1 text-xs">
                                            <span>Fortschritt</span>
                                            <span class="font-medium text-[#FF6B35]">{{ $course->progress }}%</span>
                                        </div>
                                        <div class="w-full h-2 bg-gray-200 rounded-full">
                                            <div class="h-2 rounded-full bg-[#FF6B35]" style="width: {{ $course->progress }}%"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 md:mt-0 md:ml-4">
                                    <x-button
                                        label="Weiterlernen →"
                                        link="{{ route('student.course.show', $course) }}"
                                        class="btn-primary btn-sm" />
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-8 text-center">
                            <x-icon name="o-academic-cap" class="w-12 h-12 mx-auto text-gray-300" />
                            <p class="mt-2 text-gray-500">Noch keine Kurse angemeldet</p>
                            <x-button link="{{ route('student.catalog') }}" class="mt-4 btn-primary">
                                Kurse entdecken →
                            </x-button>
                        </div>
                    @endif
                </x-card>
            </div>

            <!-- Colonne droite (1/3) -->
            <div class="space-y-8">

                <!-- Streak Card -->
                <x-card class="bg-gradient-to-r from-[#FF6B35] to-[#1E6091] text-white border-0 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-white/80">Lernstreak</p>
                            <p class="text-4xl font-bold">{{ $this->streakData['current'] }}</p>
                            <p class="text-xs text-white/80">Tage</p>
                        </div>
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-white/20">
                            <x-icon name="o-fire" class="w-8 h-8" />
                        </div>
                    </div>

                    <div class="flex justify-between pt-4 text-sm border-t border-white/30">
                        <span>Rekord: {{ $this->streakData['longest'] }}</span>
                        <span>Letzte Aktivität: {{ $this->streakData['last_study']?->diffForHumans() ?? 'Nie' }}</span>
                    </div>

                    <!-- Calendrier miniature -->
                    <div class="grid grid-cols-7 gap-1 mt-4">
                        @foreach($this->streakData['history'] as $day)
                            <div class="text-center">
                                <div class="w-8 h-8 mx-auto rounded-full flex items-center justify-center text-xs
                                    {{ $day['studied'] ? 'bg-white/30 text-white' : 'bg-white/10 text-white/50' }}">
                                    {{ substr($day['day'], 0, 1) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>

                <!-- Activité récente -->
                <x-card title="🕒 Letzte Aktivitäten" class="border-0 shadow-sm">
                    @if($this->recentActivity->count() > 0)
                        <div class="space-y-3">
                            @foreach($this->recentActivity->take(5) as $activity)
                            <a href="{{ $activity['url'] }}" class="block p-3 transition rounded-lg hover:bg-gray-50">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-{{ $activity['color'] }}-100 flex items-center justify-center">
                                        <x-icon :name="$activity['icon']" class="w-4 h-4 text-{{ $activity['color'] }}-600" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $activity['title'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $activity['course_title'] }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-400">{{ $activity['time']->diffForHumans() }}</p>
                                        @if($activity['type'] === 'quiz')
                                            <p class="text-xs {{ $activity['passed'] ? 'text-green-600' : 'text-orange-600' }}">
                                                {{ $activity['percentage'] }}%
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    @else
                        <div class="py-6 text-center">
                            <x-icon name="o-clock" class="w-10 h-10 mx-auto text-gray-300" />
                            <p class="mt-2 text-sm text-gray-500">Keine Aktivitäten</p>
                            <p class="text-xs text-gray-400">Beginne mit einer Lektion!</p>
                        </div>
                    @endif
                </x-card>

                <!-- Cours recommandés -->
                @if($this->recommendedCourses->count() > 0)
                <x-card title="💡 Empfohlen für dich" class="border-0 shadow-sm">
                    <div class="space-y-3">
                        @foreach($this->recommendedCourses->take(3) as $course)
                        <a href="{{ route('student.course.show', $course) }}" class="block p-3 transition border rounded-lg hover:bg-gray-50">
                            <h4 class="text-sm font-medium text-gray-900">{{ $course->title }}</h4>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs text-gray-500">{{ $course->subject->name ?? 'Deutsch' }}</span>
                                <span class="text-xs text-[#FF6B35]">{{ $course->lessons_count }} Lektionen</span>
                            </div>
                        </a>
                        @endforeach
                    </div>
                    <div class="mt-4 text-center">
                        <a href="{{ route('student.catalog') }}" class="text-sm text-[#FF6B35] hover:underline">
                            Alle Kurse anzeigen →
                        </a>
                    </div>
                </x-card>
                @endif
            </div>
        </div>

        <!-- Note MVP -->
        <div class="p-4 mt-8 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="font-medium text-blue-800">MVP Version</p>
                    <p class="text-sm text-blue-700">Weitere Funktionen folgen bald: Zertifikate, detaillierte Analysen und persönliche Empfehlungen!</p>
                </div>
            </div>
        </div>
    </div>
</div>
