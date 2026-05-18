<?php

use App\Models\Course;
use App\Models\Quiz;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Quiz Analytics')]
#[Layout('components.layouts.admin')]
class extends Component {

    use Toast, WithPagination;

    public Course $course;
    public Quiz $quiz;

    // États pour les filtres et tri
    public string $search = '';
    public string $sortBy = 'latest';
    public int $perPage = 10;

    // État pour la modale de détails de question
    public bool $showQuestionDetails = false;
    public $selectedQuestion = null;

    public function mount(Course $course, Quiz $quiz): void
    {
        $this->course = $course;
        $this->quiz = $quiz->load(['questions', 'attempts.user', 'attempts' => function ($query) {
            $query->whereNotNull('completed_at');
        }]);
    }

    // Statistiques globales
    public function getGlobalStats()
    {
        $attempts = $this->quiz->attempts;
        $totalAttempts = $attempts->count();

        return [
            'total_attempts' => $totalAttempts,
            'average_score' => $totalAttempts > 0 ? round($attempts->avg('score'), 1) : 0,
            'completion_rate' => $totalAttempts > 0 ? 100 : 0,  // À affiner
            'unique_students' => $attempts->unique('user_id')->count(),
        ];
    }

    // Distribution des scores
    public function getScoreDistribution()
    {
        $attempts = $this->quiz->attempts;

        return [
            'excellent' => $attempts->where('score', '>=', 90)->count(),
            'good' => $attempts->whereBetween('score', [70, 89])->count(),
            'average' => $attempts->whereBetween('score', [50, 69])->count(),
            'poor' => $attempts->where('score', '<', 50)->count()
        ];
    }

    // Tentatives des étudiants avec filtres
    public function getStudentAttempts()
    {
        return $this->quiz->attempts()
            ->with('user')
            ->whereNotNull('completed_at')
            ->when($this->search, function($query) {
                $query->whereHas('user', function ($q) {
                    $q->where(function ($s) {
                        $s->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%");
                    });
                });
            })
            ->orderBy($this->getSortColumn(), $this->getSortDirection())
            ->paginate($this->perPage);
    }

    private function getSortColumn()
    {
        return match ($this->sortBy) {
            'score_high' => 'score',
            'score_low' => 'score',
            'time_spent' => 'time_spent',
            default => 'completed_at'
        };
    }

    private function getSortDirection()
    {
        return match ($this->sortBy) {
            'score_low' => 'asc',
            default => 'desc',
        };
    }

    // Temps formaté
    public function formatTimeSpent($seconds)
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$remainingSeconds}s";
        }

        return "{$remainingSeconds}s";
    }

    // Pourcentage de score
    public function getScorePercentage($score)
    {
        $maxScore = $this->quiz->questions->sum('points');

        return $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;
    }

    // Analyse détaillée par question
    public function getQuestionAnalysis()
    {
        $questions = $this->quiz->questions;
        $totalAttempts = $this->quiz->attempts->count();

        if ($totalAttempts === 0) {
            return collect();
        }

        return $questions->map(function ($question) use ($totalAttempts) {
            $correctAnswers = $this->getCorrectAnswersCount($question);
            $successRate = $totalAttempts > 0 ? round(($correctAnswers / $totalAttempts) * 100) : 0;

            return [
                'question' => $question,
                'correct_answers' => $correctAnswers,
                'total_attempts' => $totalAttempts,
                'success_rate' => $successRate,
                'difficulty' => $this->getDifficultyLevel($successRate),
                'most_common_wrong_answer' => $this->getMostCommonWrongAnswer($question)
            ];
        })->sortByDesc('success_rate');
    }

    // Compte les bonnes réponses pour une question
    private function getCorrectAnswersCount($question)
    {
        return $this->quiz->attempts
            ->filter(function ($attempt) use ($question) {
                $answers = $attempt->answer ?? [];
                $questionAnswer = $answers[$question->id] ?? null;

                return $questionAnswer && ($questionAnswer['is_correct'] ?? false);
            })
            ->count();
    }

    // Niveau de difficulté basé sur le taux de réussite
    private function getDifficultyLevel($successRate)
    {
        if ($successRate >= 80) return 'easy';
        if ($successRate >= 60) return 'medium';
        if ($successRate >= 80) return 'hard';
        return 'very_hard';
    }

    // Réponse incorrecte la plus commune
    private function getMostCommonWrongAnswer($question)
    {
        $wrongAnswers = collect();

        foreach ($this->quiz->attempts as $attempt) {
            $answers = $attempt->answers ?? [];
            $questionAnswer = $answers[$question->id] ?? null;

            if ($questionAnswer && !($questionAnswer['is_correct'] ?? false)) {
                $wrongAnswers->push($questionAnswer['answer'] ?? 'No answer');
            }
        }

        if ($wrongAnswers->isEmpty()) {
            return null;
        }

        return $wrongAnswers->countBy()->sortDesc()->keys()->first();
    }

    // Ouvrir les détails d'une question
    public function openQuestionDetails($questionId)
    {
        $this->selectedQuestion = $this->quiz->questions->firstWhere('id', $questionId);
        $this->showQuestionDetails = true;
    }

    // Formatage des réponses pour l'affichage
    public function formatAnswer($answer, $question)
    {
        if ($question->type === 'multiple_choice') {
            $options = $question->formatted_options;
            return $options[$answer] ?? $answer;
        }

        if ($question->type === 'true_false') {
            return $answer === 'true' ? __('True') : __('False');
        }

        return $answer;
    }

    // Données pour le graphique de distribution des scores
    public function getScoreChartData()
    {
        $distribution = $this->getScoreDistribution();
        $total = array_sum($distribution);

        if ($total === 0) {
            return null;
        }

        return [
            'labels' => [
                __('Excellent') . ' (90-100%)',
                __('Good') . ' (70-89%)',
                __('Average') . ' (50-69%)',
                __('Needs Improvement') . ' (<50%)'
            ],
            'datasets' => [
                [
                    'data' => [
                        $distribution['excellent'],
                        $distribution['good'],
                        $distribution['average'],
                        $distribution['poor'],
                    ],
                    'backgroundColor' => [
                        '#10B981', // green
                        '#3B82F6', // blue
                        '#F59E0B', // yellow
                        '#EF4444'  // red
                    ]
                ]
            ]
        ];
    }

    // Données pour le graphique de progression dans le temps
    public function getTimeSeriesData()
    {
        $attempts = $this->quiz->attempts()
            ->whereNotNull('completed_at')
            ->orderBy('completed_at')
            ->get();

        if ($attempts->isEmpty()) {
            return null;
        }

        $grouped = $attempts->groupBy(function ($attempt) {
            return $attempt->completed_at->format('Y-m-d');
        });

        $labels = [];
        $scores = [];
        $counts = [];

        foreach ($grouped as $date => $dayAttempts) {
            $labels[] = $date;
            $scores[] = round($dayAttempts->avg('score'));
            $counts[] = $dayAttempts->count();
        }

        return [
            'labels' => $labels,
            'scores' => $scores,
            'counts' => $counts
        ];
    }

    // Export des données en CSV
    public function exportToCsv()
    {
        $attempts = $this->getStudentAttempts()->getCollection();
        $questions = $this->quiz->questions;

        $fileName = "quiz-analytics-{$this->quiz->id}-" . now()->format('Y-m-d') . '.csv';

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, per-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($attempts, $questions) {
            $file = fopen('php://output', 'w');

            // En-têtes
            fputcsv($file, [
                __('Student Name'),
                __('Student Email'),
                __('Score'),
                __('Score Percentage'),
                __('Time Spent'),
                __('Completed At'),
                __('Status')
            ]);

            // Données
            foreach ($attempts as $attempt) {
                $status = $this->getScorePercentage($attempt->score) >= 70 ? __('Passed') : __('Failled');

                fputcsv($file, [
                    $attempt->user->name,
                    $attempt->user->email,
                    $attempt->score,
                    $this->getScorePercentage($attempt->score) . '%',
                    $this->formatTimeSpent($attempt->time_spent),
                    $attempt->completed_at->format('Y-m-d H:i:s'),
                    $status
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Export de l'analyse des questions
    public function exportQuestionAnalysis()
    {
        $analysis = $this->getQuestionAnalysis();
        $fileName = "question-analysis-{$this->quiz->id}-" . now()->format('Y-m-d') . ".csv";

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($analysis) {
            $file = fopen('php://output', 'w');

            // En-têtes
            fputcsv($file, [
                __('Question Number'),
                __('Question Text'),
                __('Question Type'),
                __('Points'),
                __('Correct Answers'),
                __('Total Attempts'),
                __('Success Rate'),
                __('Difficulty Level'),
                __('Most Common Wrong Answer')
            ]);

            // Données
            foreach ($analysis as $index => $item) {
                fputcsv($file, [
                    $index + 1,
                    $item['question']->question,
                    ucfirst(str_replace('_', ' ', $item['question']->type)),
                    $item['question']->points,
                    $item['correct_answers'],
                    $item['total_attempts'],
                    $item['success_rate'] . '%',
                    $this->getDifficultyLabel($item['difficulty']),
                    $item['most_common_wrong_answer'] ?: __('None')
                ]);
            }


            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Libellé de difficulté
    private function getDifficultyLabel($difficulty)
    {
        return match($difficulty) {
            'easy' => __('Easy'),
            'medium' => __('Medium'),
            'hard' => __('Hard'),
            'very_hard' => __('Very Hard'),
            default => __('Unknown')
        };
    }

}; ?>

<div>
    {{-- Navigation rapide --}}
    <div class="bg-white border-b mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center space-x-4 py-4 overflow-x-auto">
                <x-button
                    label="{!! __('Course Details') !!}"
                    icon="o-arrow-left"
                    link="/teacher/courses/{{ $course->id }}/edit"
                    class="btn-ghost btn-sm"
                    responsive
                />

                <x-button
                    label="{!! __('Quiz Manager') !!}"
                    icon="o-question-mark-circle"
                    link="{{ route('teacher.quizzes.index', ['course' => $course]) }}"
                    class="btn-ghost btn-sm"
                    responsive
                />

                <x-button
                    label="{!! __('Question Management') !!}"
                    icon="o-document-text"
                    link="{{ route('teacher.quizzes.questions.index', ['course' => $course, 'quiz' => $quiz]) }}"
                    class="btn-ghost btn-sm"
                    responsive
                />

                <x-button
                    label="{!! __('Quiz Analytics') !!}"
                    icon="o-chart-bar"
                    class="btn-primary btn-sm"
                    disabled
                />
            </nav>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- En-tête --}}
        <x-header
            title="{!! __('Quiz Analytics') !!}"
            subtitle="{{ $quiz->title }}"
        >
            <x-slot:actions>
                <x-button
                    label="{!! __('Back to Questions') !!}"
                    icon="o-arrow-left"
                    link="{{ route('teacher.quizzes.questions.index', ['course' => $course, 'quiz' => $quiz]) }}"
                    class="btn-ghost"
                />
            </x-slot:actions>
        </x-header>

        {{-- Statistiques globales --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <x-stat
                title="{!! __('Total Attempts') !!}"
                value="{{ $this->getGlobalStats()['total_attempts'] }}"
                icon="o-flag"
                description="{!! __('Completed attempts') !!}"
            />
            <x-stat
                title="{!! __('Average Score') !!}"
                value="{{ $this->getGlobalStats()['average_score'] }}%"
                icon="o-trophy"
                description="{!! __('Across all attempts') !!}"
            />
            <x-stat
                title="{!! __('Unique Students') !!}"
                value="{{ $this->getGlobalStats()['unique_students'] }}"
                icon="o-user-group"
                description="{!! __('Participated students') !!}"
            />
            <x-stat
                title="{!! __('Completion Rate') !!}"
                value="{{ $this->getGlobalStats()['completion_rate'] }}%"
                icon="o-check-badge"
                description="{!! __('Quiz completion') !!}"
            />
        </div>

        {{-- Section Graphiques --}}
        @if($this->getGlobalStats()['total_attempts'] > 0)
            <x-card shadow class="mb-8">
                <x-slot:title>
                    {!! __('Visualizations') !!}
                </x-slot:title>

                <x-slot:actions>
                    <div class="flex space-x-2">
                        <x-button
                            label="{!! __('Export Attempts') !!}"
                            icon="o-arrow-down-tray"
                            wire:click="exportToCsv"
                            class="btn-outline btn-sm"
                        />
                        <x-button
                            label="{!! __('Export Question Analysis') !!}"
                            icon="o-document-arrow-down"
                            wire:click="exportQuestionAnalysis"
                            class="btn-outline btn-sm"
                        />
                    </div>
                </x-slot:actions>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {{-- Graphique de distribution des scores --}}
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{!! __('Score Distribution') !!}</h3>
                        @php
                            $chartData = $this->getScoreChartData();
                        @endphp

                        @if($chartData)
                            <div class="bg-white p-4 rounded-lg border">
                                <div x-data="{
                                    chart: null,
                                    init() {
                                        const ctx = this.$refs.canvas.getContext('2d');
                                        this.chart = new Chart(ctx, {
                                            type: 'doughnut',
                                            data: @js($chartData),
                                            options: {
                                                responsive: true,
                                                plugins: {
                                                    legend: {
                                                        position: 'bottom',
                                                    },
                                                    tooltip: {
                                                        callbacks: {
                                                            label: function(context) {
                                                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                                const value = context.raw;
                                                                const percentage = Math.round((value / total) * 100);
                                                                return `${value} (${percentage}%)`;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        });
                                    }
                                }" class="h-64 flex items-center justify-center">
                                    <canvas x-ref="canvas"></canvas>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <x-icon name="o-chart-bar" class="w-12 h-12 mx-auto mb-4 text-gray-300" />
                                <p>{!! __('No data available for chart') !!}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Graphique de progression dans le temps --}}
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{!! __('Performance Over Time') !!}</h3>
                        @php
                            $timeSeriesData = $this->getTimeSeriesData();
                        @endphp

                        @if($timeSeriesData)
                            <div class="bg-white p-4 rounded-lg border">
                                <div x-data="{
                                    chart: null,
                                    init() {
                                        const ctx = this.$refs.canvas.getContext('2d');
                                        this.chart = new Chart(ctx, {
                                            type: 'line',
                                            data: {
                                                labels: @js($timeSeriesData['labels']),
                                                datasets: [
                                                    {
                                                        label: '{!! __('Average Score') !!}',
                                                        data: @js($timeSeriesData['scores']),
                                                        borderColor: '#3B82F6',
                                                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                                        fill: true,
                                                        tension: 0.4,
                                                        yAxisID: 'y'
                                                    },
                                                    {
                                                        label: '{!! __('Attempts Count') !!}',
                                                        data: @js($timeSeriesData['counts']),
                                                        borderColor: '#10B981',
                                                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                                        fill: true,
                                                        tension: 0.4,
                                                        yAxisID: 'y1',
                                                        type: 'bar'
                                                    }
                                                ]
                                            },
                                            options: {
                                                responsive: true,
                                                interaction: {
                                                    mode: 'index',
                                                    intersect: false,
                                                },
                                                scales: {
                                                    y: {
                                                        type: 'linear',
                                                        display: true,
                                                        position: 'left',
                                                        title: {
                                                            display: true,
                                                            text: '{!! __('Score') !!} (%)'
                                                        },
                                                        min: 0,
                                                        max: 100
                                                    },
                                                    y1: {
                                                        type: 'linear',
                                                        display: true,
                                                        position: 'right',
                                                        title: {
                                                            display: true,
                                                            text: '{!! __('Attempts') !!}'
                                                        },
                                                        grid: {
                                                            drawOnChartArea: false,
                                                        },
                                                    }
                                                }
                                            }
                                        });
                                    }
                                }" class="h-64 flex items-center justify-center">
                                    <canvas x-ref="canvas"></canvas>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <x-icon name="o-chart-bar" class="w-12 h-12 mx-auto mb-4 text-gray-300" />
                                <p>{!! __('No time series data available') !!}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Légende et explications --}}
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-600">
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">{!! __('Chart Insights') !!}</h4>
                        <ul class="space-y-1">
                            <li>• {!! __('Score distribution shows how students performed overall') !!}</li>
                            <li>• {!! __('Performance over time tracks trends and improvements') !!}</li>
                            <li>• {!! __('Use exports for detailed analysis in spreadsheet software') !!}</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">{!! __('Export Options') !!}</h4>
                        <ul class="space-y-1">
                            <li>• <strong>{!! __('Export Attempts') !!}:</strong> {!! __('Detailed student performance data') !!}</li>
                            <li>• <strong>{!! __('Export Question Analysis') !!}:</strong> {!! __('Question-by-question statistics') !!}</li>
                        </ul>
                    </div>
                </div>
            </x-card>
        @endif

        {{-- Distribution des scores --}}
        <x-card shadow class="mb-8">
            <x-slot:title>
                {!! __('Score Distribution') !!}
            </x-slot:title>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @php
                    $distribution = $this->getScoreDistribution();
                    $total = array_sum($distribution);
                @endphp

                <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
                    <div class="text-2xl font-bold text-green-600">{{ $distribution['excellent'] }}</div>
                    <div class="text-sm text-green-700">{!! __('Excellent') !!} (90-100%)</div>
                    @if($total > 0)
                        <div class="text-xs text-green-600 mt-1">
                            {{ round(($distribution['excellent'] / $total) * 100) }}%
                        </div>
                    @endif
                </div>

                <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="text-2xl font-bold text-blue-600">{{ $distribution['good'] }}</div>
                    <div class="text-sm text-blue-700">{!! __('Good') !!} (70-89%)</div>
                    @if($total > 0)
                        <div class="text-xs text-blue-600 mt-1">
                            {{ round(($distribution['good'] / $total) * 100) }}%
                        </div>
                    @endif
                </div>

                <div class="text-center p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                    <div class="text-2xl font-bold text-yellow-600">{{ $distribution['average'] }}</div>
                    <div class="text-sm text-yellow-700">{!! __('Average') !!} (50-69%)</div>
                    @if($total > 0)
                        <div class="text-xs text-yellow-600 mt-1">
                            {{ round(($distribution['average'] / $total) * 100) }}%
                        </div>
                    @endif
                </div>

                <div class="text-center p-4 bg-red-50 rounded-lg border border-red-200">
                    <div class="text-2xl font-bold text-red-600">{{ $distribution['poor'] }}</div>
                    <div class="text-sm text-red-700">{!! __('Needs Improvement') !!} (<50%)</div>
                    @if($total > 0)
                        <div class="text-xs text-red-600 mt-1">
                            {{ round(($distribution['poor'] / $total) * 100) }}%
                        </div>
                    @endif
                </div>
            </div>
        </x-card>

        {{-- Section des tentatives étudiantes --}}
        @if($this->getGlobalStats()['total_attempts'] > 0)
            <x-card shadow class="mb-8">
                <x-slot:title>
                    {!! __('Student Attempts') !!}
                    <span class="text-sm font-normal text-gray-500 ml-2">
                        ({{ $this->getStudentAttempts()->total() }} {!! __('results') !!})
                    </span>
                </x-slot:title>

                {{-- Filtres et recherche --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    {{-- Recherche --}}
                    <div class="md:col-span-2">
                        <x-input
                            label="{!! __('Search students...') !!}"
                            wire:model.live.debounce.500ms="search"
                            icon="o-magnifying-glass"
                            placeholder="{!! __('Search by name or email...') !!}"
                        />
                    </div>

                    {{-- Tri --}}
                    <div>
                        <x-select
                            label="{!! __('Sort by') !!}"
                            wire:model.live="sortBy"
                            :options="[
                                ['id' => 'latest', 'name' => __('Latest First')],
                                ['id' => 'score_high', 'name' => __('Highest Score')],
                                ['id' => 'score_low', 'name' => __('Lowest Score')],
                                ['id' => 'time_spent', 'name' => __('Time Spent')],
                            ]"
                        />
                    </div>
                </div>

                {{-- Liste des tentatives --}}
                <div class="space-y-4">
                    @foreach($this->getStudentAttempts() as $attempt)
                        <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                {{-- Informations étudiant --}}
                                <div class="flex items-center space-x-3 flex-1 min-w-0">
                                    {{-- Avatar --}}
                                    <div class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-semibold text-sm">
                                        {{ substr($attempt->user->name, 0, 1) }}
                                    </div>

                                    {{-- Détails --}}
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-900 truncate">
                                            {{ $attempt->user->name }}
                                        </h4>
                                        <p class="text-sm text-gray-600 truncate">
                                            {{ $attempt->user->email }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ $attempt->completed_at->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
                                </div>

                                {{-- Statistiques de la tentative --}}
                                <div class="flex flex-wrap items-center gap-4 text-sm">
                                    {{-- Score --}}
                                    <div class="text-center">
                                        <div class="text-2xl font-bold
                                            {{ $this->getScorePercentage($attempt->score) >= 70 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $this->getScorePercentage($attempt->score) }}%
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $attempt->score }}/{{ $quiz->questions->sum('points') }} {!! __('points') !!}
                                        </div>
                                    </div>

                                    {{-- Temps passé --}}
                                    <div class="text-center">
                                        <div class="flex items-center space-x-1 text-gray-600">
                                            <x-icon name="o-clock" class="w-4 h-4" />
                                            <span class="font-medium">{{ $this->formatTimeSpent($attempt->time_spent) }}</span>
                                        </div>
                                        <div class="text-xs text-gray-500">{!! __('Time spent') !!}</div>
                                    </div>

                                    {{-- Statut --}}
                                    <div class="text-center">
                                        @if($this->getScorePercentage($attempt->score) >= 70)
                                            <x-badge value="{!! __('Passed') !!}" class="badge-success" />
                                        @else
                                            <x-badge value="{!! __('Failed') !!}" class="badge-error" />
                                        @endif
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex space-x-2">
                                        <x-button
                                            icon="o-eye"
                                            class="btn-ghost btn-sm btn-square"
                                            tooltip="{!! __('View details') !!}"
                                            wire:click="viewAttemptDetails({{ $attempt->id }})"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $this->getStudentAttempts()->links() }}
                </div>
            </x-card>
        @else
            {{-- Message si pas de données --}}
            <x-alert icon="o-chart-bar" title="{!! __('No data available') !!}" class="bg-blue-50">
                <p class="text-gray-600 mb-4">
                    {!! __('No students have completed this quiz yet. Analytics will appear here once students start taking the quiz.') !!}
                </p>
            </x-alert>
        @endif

        {{-- Section Analyse par Question --}}
        @if($this->getGlobalStats()['total_attempts'] > 0)
            <x-card shadow class="mb-8">
                <x-slot:title>
                    {!! __('Question Analysis') !!}
                    <span class="text-sm font-normal text-gray-500 ml-2">
                        ({!! __('Performance by question') !!})
                    </span>
                </x-slot:title>

                <x-slot:actions>
                    <x-button
                        label="{!! __('View All Questions') !!}"
                        icon="o-document-text"
                        link="{{ route('teacher.quizzes.questions.index', ['course' => $course, 'quiz' => $quiz]) }}"
                        class="btn-ghost btn-sm"
                    />
                </x-slot:actions>

                <div class="space-y-4">
                    @foreach($this->getQuestionAnalysis() as $analysis)
                        @php
                            $question = $analysis['question'];
                            $difficultyColor = match($analysis['difficulty']) {
                                'easy' => 'text-green-600 bg-green-50 border-green-200',
                                'medium' => 'text-blue-600 bg-blue-50 border-blue-200',
                                'hard' => 'text-orange-600 bg-orange-50 border-orange-200',
                                'very_hard' => 'text-red-600 bg-red-50 border-red-200',
                                default => 'text-gray-600 bg-gray-50 border-gray-200'
                            };

                            $difficultyLabel = match($analysis['difficulty']) {
                                'easy' => __('Easy'),
                                'medium' => __('Medium'),
                                'hard' => __('Hard'),
                                'very_hard' => __('Very Hard'),
                                default => __('Unknown')
                            };
                        @endphp

                        <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors cursor-pointer"
                            wire:click="openQuestionDetails({{ $question->id }})">

                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                {{-- Question et statistiques --}}
                                <div class="flex items-start space-x-4 flex-1 min-w-0">
                                    {{-- Numéro de question --}}
                                    <div class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-semibold text-sm flex-shrink-0">
                                        {{ $loop->iteration }}
                                    </div>

                                    {{-- Contenu de la question --}}
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                                            {{ $question->question }}
                                        </h4>

                                        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600">
                                            {{-- Type de question --}}
                                            <x-badge
                                                :value="ucfirst(str_replace('_', ' ', $question->type))"
                                                class="badge-info badge-xs"
                                            />

                                            {{-- Points --}}
                                            <span class="flex items-center">
                                                <x-icon name="o-trophy" class="w-4 h-4 mr-1" />
                                                {{ $question->points }} {!! __('points') !!}
                                            </span>

                                            {{-- Réponses correctes --}}
                                            <span class="flex items-center">
                                                <x-icon name="o-check-badge" class="w-4 h-4 mr-1 text-green-500" />
                                                {{ $analysis['correct_answers'] }}/{{ $analysis['total_attempts'] }} {!! __('correct') !!}
                                            </span>
                                        </div>

                                        {{-- Réponse incorrecte la plus commune --}}
                                        @if($analysis['most_common_wrong_answer'])
                                            <div class="mt-2 text-sm text-red-600">
                                                <strong>{!! __('Common wrong answer') !!}:</strong>
                                                "{{ $this->formatAnswer($analysis['most_common_wrong_answer'], $question) }}"
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Indicateurs de performance --}}
                                <div class="flex flex-col items-end space-y-3 min-w-0">
                                    {{-- Taux de réussite --}}
                                    <div class="text-center">
                                        <div class="text-2xl font-bold
                                            {{ $analysis['success_rate'] >= 70 ? 'text-green-600' :
                                            ($analysis['success_rate'] >= 50 ? 'text-orange-600' : 'text-red-600') }}">
                                            {{ $analysis['success_rate'] }}%
                                        </div>
                                        <div class="text-xs text-gray-500">{!! __('Success rate') !!}</div>
                                    </div>

                                    {{-- Niveau de difficulté --}}
                                    <x-badge
                                        :value="$difficultyLabel"
                                        class="{{ $difficultyColor }} text-xs"
                                    />

                                    {{-- Barre de progression --}}
                                    <div class="w-32">
                                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                                            <span>0%</span>
                                            <span>100%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full transition-all duration-300
                                                {{ $analysis['success_rate'] >= 70 ? 'bg-green-500' :
                                                ($analysis['success_rate'] >= 50 ? 'bg-orange-500' : 'bg-red-500') }}"
                                                style="width: {{ $analysis['success_rate'] }}%">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif
    </div>
</div>
