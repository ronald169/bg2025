<?php

use App\Models\Course;
use App\Models\Progress;
use App\Models\Subject;
use App\Models\QuizAttempt;
use App\Models\LearningStreak;
use App\Models\Enrollment;
use App\Models\StudySession;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Mein Lernpfad - Deutsch lernen')]
#[Layout('components.layouts.dashboard-student')]
class extends Component {
    use Toast;

    #[Url(as: 'subject', history: true)]
    public ?int $selectedSubject = null;

    #[Url(as: 'level', history: true)]
    public ?string $selectedLevel = null;

    public string $activeTab = 'path';

    #[Computed]
    public function user()
    {
        return Auth::user();
    }

    #[Computed]
    public function enrolledCourses()
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

                // Trouver la prochaine leçon non complétée
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

    #[Computed]
    public function pathProgress()
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

    #[Computed]
    public function skills()
    {
        $germanLevel = $this->user->german_level ?? 'A1';

        $skillsByLevel = [
            'A1' => [
                ['name' => 'Grundwortschatz', 'icon' => 'o-book-open', 'description' => 'Erste deutsche Wörter und Sätze'],
                ['name' => 'Einfache Grammatik', 'icon' => 'o-pencil', 'description' => 'Artikel, Verben im Präsens'],
                ['name' => 'Satzbau', 'icon' => 'o-chart-bar', 'description' => 'Hauptsätze und einfache Nebensätze'],
                ['name' => 'Aussprache', 'icon' => 'o-microphone', 'description' => 'Laute und Betonung'],
                ['name' => 'Alltagskommunikation', 'icon' => 'o-chat-bubble-left-right', 'description' => 'Sich vorstellen, Zahlen, Uhrzeit'],
            ],
            'A2' => [
                ['name' => 'Erweiterter Wortschatz', 'icon' => 'o-book-open', 'description' => 'Alltägliche Themen und Situationen'],
                ['name' => 'Vergangenheit', 'icon' => 'o-pencil', 'description' => 'Perfekt und Präteritum'],
                ['name' => 'Nebensätze', 'icon' => 'o-chart-bar', 'description' => 'Weil, dass, ob, wenn'],
                ['name' => 'Hörverstehen', 'icon' => 'o-microphone', 'description' => 'Einfache Gespräche verstehen'],
                ['name' => 'Briefe schreiben', 'icon' => 'o-document-text', 'description' => 'Persönliche und formelle Briefe'],
            ],
            'B1' => [
                ['name' => 'Mittelstufenwortschatz', 'icon' => 'o-book-open', 'description' => 'Abstrakte Themen und Diskussionen'],
                ['name' => 'Konjunktiv II', 'icon' => 'o-pencil', 'description' => 'Höflichkeitsformen und Wünsche'],
                ['name' => 'Passiv', 'icon' => 'o-chart-bar', 'description' => 'Vorgangs- und Zustandspassiv'],
                ['name' => 'Textverständnis', 'icon' => 'o-document-text', 'description' => 'Längere Texte verstehen'],
                ['name' => 'Diskussionen', 'icon' => 'o-chat-bubble-left-right', 'description' => 'Meinungen äußern und begründen'],
            ],
            'B2' => [
                ['name' => 'Fortgeschrittenenwortschatz', 'icon' => 'o-book-open', 'description' => 'Fachsprache und Idiome'],
                ['name' => 'Konjunktiv I', 'icon' => 'o-pencil', 'description' => 'Indirekte Rede'],
                ['name' => 'Nominalisierung', 'icon' => 'o-chart-bar', 'description' => 'Verben zu Nomen machen'],
                ['name' => 'Stilistik', 'icon' => 'o-document-text', 'description' => 'Formelle und informelle Texte'],
                ['name' => 'Präsentationen', 'icon' => 'o-presentation-chart', 'description' => 'Vorträge halten'],
            ],
            'C1' => [
                ['name' => 'Wissenschaftssprache', 'icon' => 'o-book-open', 'description' => 'Akademischer Wortschatz'],
                ['name' => 'Komplexe Syntax', 'icon' => 'o-pencil', 'description' => 'Verschachtelte Sätze'],
                ['name' => 'Rhetorik', 'icon' => 'o-microphone', 'description' => 'Überzeugende Argumentation'],
                ['name' => 'Literaturanalyse', 'icon' => 'o-document-text', 'description' => 'Literarische Texte interpretieren'],
                ['name' => 'Debatten', 'icon' => 'o-chat-bubble-left-right', 'description' => 'Komplexe Diskussionen führen'],
            ],
            'C2' => [
                ['name' => 'Nuancen', 'icon' => 'o-book-open', 'description' => 'Feinheiten der Sprache'],
                ['name' => 'Stilvarianten', 'icon' => 'o-pencil', 'description' => 'Verschiedene Register'],
                ['name' => 'Sprachgefühl', 'icon' => 'o-sparkles', 'description' => 'Intuitives Sprachverständnis'],
                ['name' => 'Kreatives Schreiben', 'icon' => 'o-document-text', 'description' => 'Eigene Texte verfassen'],
                ['name' => 'Muttersprachniveau', 'icon' => 'o-trophy', 'description' => 'Fließend wie ein Muttersprachler'],
            ],
        ];

        $skills = $skillsByLevel[$germanLevel] ?? $skillsByLevel['A1'];

        // Calculer la progression réelle pour chaque compétence
        foreach ($skills as &$skill) {
            $skill['progress'] = $this->calculateSkillProgress($skill['name']);
        }

        return $skills;
    }

    #[Computed]
    public function recommendations()
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

    #[Computed]
    public function achievements()
    {
        $streak = LearningStreak::where('user_id', $this->user->id)->first();
        $totalStudyTime = StudySession::where('user_id', $this->user->id)->sum('duration_minutes');
        $completedCourses = $this->enrolledCourses->filter(fn($c) => $c->progress >= 100)->count();

        return [
            [
                'name' => 'Erste Schritte',
                'description' => 'Erste Lektion abgeschlossen',
                'icon' => 'o-rocket-launch',
                'progress' => min(100, Progress::where('user_id', $this->user->id)->count() * 20),
                'unlocked' => Progress::where('user_id', $this->user->id)->exists(),
            ],
            [
                'name' => 'Lernstreak',
                'description' => '7 Tage am Stück gelernt',
                'icon' => 'o-fire',
                'progress' => round(min(100, (($streak->current_streak ?? 0) / 7) * 100)),
                'unlocked' => ($streak->current_streak ?? 0) >= 7,
            ],
            [
                'name' => 'Kursmeister',
                'description' => 'Einen ganzen Kurs abgeschlossen',
                'icon' => 'o-trophy',
                'progress' => min(100, $completedCourses * 100),
                'unlocked' => $completedCourses >= 1,
            ],
            [
                'name' => 'Lernzeit',
                'description' => '10 Stunden Lernzeit erreicht',
                'icon' => 'o-clock',
                'progress' => min(100, ($totalStudyTime / 600) * 100),
                'unlocked' => $totalStudyTime >= 600,
            ],
        ];
    }

    private function calculateSkillProgress($skillName): int
    {
        // Calcul basé sur les cours complétés et les quiz réussis
        $completedLessons = Progress::where('user_id', $this->user->id)
            ->where('is_completed', true)
            ->count();

        $totalPoints = $this->user->total_points ?? 0;

        // Progression simplifiée pour le MVP
        $progress = min(100, round(($completedLessons / 20) * 100));
        $progress = min(100, $progress + floor($totalPoints / 100));

        return $progress;
    }

    private function getRecommendationReason($course): string
    {
        $reasons = [
            '🇩🇪 Perfekt für dein Niveau ' . ($this->user->german_level ?? 'A1'),
            '⭐ Beliebt bei anderen Lernenden',
            '📚 Ideal für deine Lernziele',
            '🎯 Empfohlen basierend auf deinen Fortschritten',
            '🚀 Nächster Schritt auf deinem Lernpfad',
        ];
        return $reasons[array_rand($reasons)];
    }

    #[Computed]
    public function overallProgress()
    {
        if (empty($this->pathProgress)) return 0;
        return round(collect($this->pathProgress)->avg('avg_progress'));
    }

    public function getLevelLabel($level): string
    {
        $levels = [
            'A1' => 'A1 - Débutant',
            'A2' => 'A2 - Élémentaire',
            'B1' => 'B1 - Intermédiaire',
            'B2' => 'B2 - Avancé',
            'C1' => 'C1 - Expérimenté',
            'C2' => 'C2 - Maîtrise'
        ];
        return $levels[$level] ?? $level;
    }
}
?>

<div class="py-8">
    <div class="px-4 mx-auto max-w-7xl">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">🗺️ {{ __('Mein Lernpfad') }}</h1>
            <p class="mt-1 text-gray-600">{{ __('Dein personalisierter Weg zum Deutsch-Erfolg') }}</p>
        </div>

        <!-- Overall Progress Bar -->
        <div class="p-6 mb-8 bg-white shadow-sm rounded-xl">
            <div class="flex flex-col mb-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Gesamtfortschritt</h2>
                    <p class="text-sm text-gray-500">Schließe Kurse ab, um auf deinem Lernpfad voranzukommen</p>
                </div>
                <div class="mt-2 md:mt-0">
                    <span class="text-3xl font-bold text-[#FF6B35]">{{ $this->overallProgress }}%</span>
                </div>
            </div>
            <div class="w-full h-3 bg-gray-200 rounded-full">
                <div class="h-3 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] transition-all duration-500"
                     style="width: {{ $this->overallProgress }}%"></div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="flex gap-2 pb-2 mb-6 border-b border-gray-200">
            <button wire:click="$set('activeTab', 'path')"
                    class="px-4 py-2 rounded-lg font-medium transition
                           {{ $activeTab === 'path' ? 'bg-[#FF6B35] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                📚 Lernpfad
            </button>
            <button wire:click="$set('activeTab', 'skills')"
                    class="px-4 py-2 rounded-lg font-medium transition
                           {{ $activeTab === 'skills' ? 'bg-[#FF6B35] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                ⭐ Kompetenzen
            </button>
            <button wire:click="$set('activeTab', 'achievements')"
                    class="px-4 py-2 rounded-lg font-medium transition
                           {{ $activeTab === 'achievements' ? 'bg-[#FF6B35] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                🏆 Errungenschaften
            </button>
            <button wire:click="$set('activeTab', 'recommendations')"
                    class="px-4 py-2 rounded-lg font-medium transition
                           {{ $activeTab === 'recommendations' ? 'bg-[#FF6B35] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                💡 Empfehlungen
            </button>
        </div>

        <!-- Tab: Learning Path -->
        @if($activeTab === 'path')
            @if(!empty($this->pathProgress))
                <div class="space-y-6">
                    @foreach($this->pathProgress as $item)
                    <div class="overflow-hidden bg-white shadow-sm rounded-xl">
                        <div class="p-5 border-b bg-gradient-to-r from-gray-50 to-white">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-[#FF6B35]/10 flex items-center justify-center">
                                        <x-icon name="o-academic-cap" class="w-5 h-5 text-[#FF6B35]" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $item['subject']->name }}</h3>
                                        <p class="text-sm text-gray-500">
                                            {{ $item['completed_courses'] }}/{{ $item['total_courses'] }} Kurse abgeschlossen
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-bold text-[#FF6B35]">{{ $item['avg_progress'] }}%</span>
                                </div>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-100">
                            @foreach($item['courses'] as $course)
                            <div class="p-5 transition hover:bg-gray-50">
                                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h4 class="font-medium text-gray-900">{{ $course->title }}</h4>
                                            <span class="px-2 py-0.5 text-xs rounded-full {{ getLevelBadgeColor($course->level) }}">
                                                {{ $this->getLevelLabel($course->level) }}
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap gap-3 text-sm text-gray-500">
                                            <span class="flex items-center gap-1">
                                                <x-icon name="o-book-open" class="w-4 h-4" />
                                                {{ $course->lessons_count }} Lektionen
                                            </span>
                                        </div>
                                        <div class="mt-3">
                                            <div class="flex justify-between mb-1 text-sm">
                                                <span class="text-gray-600">Fortschritt</span>
                                                <span class="font-medium text-[#FF6B35]">{{ $course->progress }}%</span>
                                            </div>
                                            <div class="w-full h-2 bg-gray-200 rounded-full">
                                                <div class="h-2 rounded-full {{ getProgressColor($course->progress) }}"
                                                     style="width: {{ $course->progress }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        @if($course->progress >= 100)
                                            <div class="flex items-center gap-2 text-green-600">
                                                <x-icon name="o-check-circle" class="w-5 h-5" />
                                                <span class="text-sm font-medium">Abgeschlossen</span>
                                            </div>
                                        @else
                                            <x-button
                                                label="Weiterlernen →"
                                                icon="o-play"
                                                link="{{ route('student.course.show', $course) }}"
                                                class="btn-primary btn-sm" />
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="py-16 text-center bg-white border rounded-xl">
                    <x-icon name="o-map" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <h3 class="mb-2 text-xl font-semibold text-gray-900">Dein Lernpfad wartet!</h3>
                    <p class="mb-6 text-gray-500">Melde dich für deinen ersten Kurs an, um zu beginnen.</p>
                    <x-button link="{{ route('student.catalog') }}" class="btn-primary">
                        Kurse entdecken →
                    </x-button>
                </div>
            @endif

        <!-- Tab: Skills -->
        @elseif($activeTab === 'skills')
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($this->skills as $skill)
                <div class="p-5 transition bg-white shadow-sm rounded-xl hover:shadow-md">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-lg bg-[#FF6B35]/10 flex items-center justify-center">
                            <x-icon :name="$skill['icon']" class="w-5 h-5 text-[#FF6B35]" />
                        </div>
                        <span class="text-xl font-bold text-[#FF6B35]">{{ $skill['progress'] }}%</span>
                    </div>
                    <h3 class="mb-1 font-semibold text-gray-900">{{ $skill['name'] }}</h3>
                    <p class="mb-3 text-sm text-gray-500">{{ $skill['description'] }}</p>
                    <div class="w-full h-2 bg-gray-200 rounded-full">
                        <div class="h-2 rounded-full {{ getProgressColor($skill['progress']) }}"
                             style="width: {{ $skill['progress'] }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Niveau actuel -->
            <div class="mt-6 p-4 bg-gradient-to-r from-[#FF6B35]/10 to-[#1E6091]/10 rounded-xl">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Aktuelles Niveau</p>
                        <p class="text-xl font-bold text-gray-900">{{ $this->getLevelLabel($this->user->german_level ?? 'A1') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Nächstes Ziel</p>
                        <p class="text-xl font-bold text-[#FF6B35]">
                            @php
                                $levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
                                $currentIndex = array_search($this->user->german_level ?? 'A1', $levels);
                                $nextLevel = $levels[$currentIndex + 1] ?? 'C2+';
                            @endphp
                            {{ $this->getLevelLabel($nextLevel) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Fortschritt zum nächsten Level</p>
                        <div class="flex items-center gap-3">
                            <div class="w-32 h-2 bg-gray-200 rounded-full">
                                <div class="h-2 rounded-full bg-[#FF6B35]" style="width: {{ $this->overallProgress }}%"></div>
                            </div>
                            <span class="text-sm font-medium">{{ $this->overallProgress }}%</span>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Tab: Achievements -->
        @elseif($activeTab === 'achievements')
            <div class="grid gap-4 md:grid-cols-2">
                @foreach($this->achievements as $achievement)
                <div class="bg-white rounded-xl shadow-sm overflow-hidden {{ $achievement['unlocked'] ? 'border-l-4 border-yellow-500' : '' }}">
                    <div class="p-5">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-xl {{ $achievement['unlocked'] ? 'bg-yellow-100' : 'bg-gray-100' }}
                                        flex items-center justify-center flex-shrink-0">
                                <x-icon :name="$achievement['icon']"
                                        class="w-6 h-6 {{ $achievement['unlocked'] ? 'text-yellow-600' : 'text-gray-400' }}" />
                            </div>
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="font-semibold text-gray-900">{{ $achievement['name'] }}</h3>
                                    @if($achievement['unlocked'])
                                        <span class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded-full">
                                            Freigeschaltet
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-gray-500">{{ $achievement['description'] }}</p>

                                @if(!$achievement['unlocked'])
                                    <div class="mt-3">
                                        <div class="flex justify-between mb-1 text-sm">
                                            <span class="text-gray-600">Fortschritt</span>
                                            <span class="text-[#FF6B35]">{{ $achievement['progress'] }}%</span>
                                        </div>
                                        <div class="w-full h-2 bg-gray-200 rounded-full">
                                            <div class="h-2 rounded-full bg-[#FF6B35]" style="width: {{ $achievement['progress'] }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

        <!-- Tab: Recommendations -->
        @elseif($activeTab === 'recommendations')
            @if($this->recommendations->count() > 0)
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($this->recommendations as $course)
                    <div class="overflow-hidden transition bg-white shadow-sm rounded-xl hover:shadow-md">
                        <div class="h-24 bg-gradient-to-r from-[#FF6B35]/20 to-[#1E6091]/20 flex items-center justify-center">
                            <span class="text-4xl">🇩🇪</span>
                        </div>
                        <div class="p-4">
                            <h3 class="mb-1 font-semibold text-gray-900">{{ $course->title }}</h3>
                            <p class="mb-2 text-sm text-gray-500">{{ $course->reason }}</p>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1">
                                    <x-icon name="o-star" class="w-4 h-4 text-yellow-400" />
                                    <span class="text-sm">{{ number_format($course->reviews_avg_rating ?? 0, 1) }}</span>
                                </div>
                                <x-button
                                    label="Ansehen →"
                                    link="{{ route('student.course.show', $course) }}"
                                    class="btn-primary btn-sm" />
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="py-16 text-center bg-white border rounded-xl">
                    <x-icon name="o-sparkles" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <h3 class="mb-2 text-xl font-semibold text-gray-900">Keine Empfehlungen</h3>
                    <p class="text-gray-500">Schließe weitere Kurse ab, um personalisierte Empfehlungen zu erhalten.</p>
                </div>
            @endif
        @endif

        <!-- Note MVP -->
        <div class="p-4 mt-8 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="font-medium text-blue-800">MVP Version</p>
                    <p class="text-sm text-blue-700">Prochaines fonctionnalités : arbre de compétences détaillé, objectifs personnalisés, comparaison avec la communauté, et analyses avancées.</p>
                </div>
            </div>
        </div>
    </div>
</div>
