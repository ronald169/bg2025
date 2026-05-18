<?php

use App\Models\Lesson;
use App\Models\Progress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

use function Laravel\Prompts\progress;

new class extends Component {

    use Toast;

    public Quiz $quiz;
    public ?QuizAttempt $attempt = null;

    // État du quiz
    public string $status = 'not_started'; // not_started, in_progress, completed, max_attempts_reached
    public int $currentQuestionIndex = 0;
    public array $userAnswers = [];
    public int $timeRemaining = 0;
    public int $timeSpent = 0;

    // Questions et données
    public $questions;
    public $currentQuestion;

    // Statistiques des tentatives
    public $userAttemptsCount = 0;
    public $bestScore = 0;
    public $remainingAttempts = 0;

    public function mount(Quiz $quiz)
    {
        // Vérification manuelle de l'accès
        $isEnrolled = Auth::user()->coursesEnrolled()
            ->where('course_id', $quiz->lesson->course_id)
            ->exists();

        if (!$isEnrolled) {
            abort(403, __('You must be enrolled in this course to access the quiz.'));
        }

        $this->quiz = $quiz->load('questions');
        $this->questions = $this->quiz->questions;
        $this->initializeQuizStats();
        $this->initializeQuiz();
    }

    /**
     * Initialise les statistiques des tentatives
     */
    public function initializeQuizStats()
    {
        // Nombre de tentatives de l'utilisateur
        $this->userAttemptsCount = QuizAttempt::where('user_id', Auth::id())
            ->where('quiz_id', $this->quiz->id)
            ->whereNotNull('completed_at')
            ->count();

        // Meilleur score
        $this->bestScore = QuizAttempt::where('user_id', Auth::id())
            ->where('quiz_id', $this->quiz->id)
            ->max('score') ?? 0;

        // Tentatives restantes
        $this->remainingAttempts = max(0, $this->quiz->max_attempts - $this->userAttemptsCount);

        // Vérifier si l'utilisateur a atteint le maximum de tentatives
        if ($this->userAttemptsCount >= $this->quiz->max_attempts) {
            $this->status = 'max_attempts_reached';
        }
    }

    /**
     * Initialise le quiz
     */
    public function initializeQuiz()
    {
        if ($this->status === 'max_attempts_reached') {
            return;
        }

        // Vérifier s'il y a une tentative en cours
        $this->attempt = QuizAttempt::where('user_id', Auth::id())
            ->where('quiz_id', $this->quiz->id)
            ->whereNull('completed_at')
            ->first();

        if ($this->attempt) {
            // Reprendre une tentative existante
            $this->status = 'in_progress';
            $this->userAnswers = $this->attempt->answers ?? [];
            $this->timeSpent = floor($this->attempt->time_spent / 60);

            // Trouver la dernière question répondue
            $this->currentQuestionIndex = count($this->userAnswers);
        } else {
            // Nouvelle tentative
            $this->status = 'not_started';
            $this->userAnswers = [];
            $this->currentQuestionIndex = 0;
            $this->timeSpent = 0;
        }

        $this->updateCurrentQuestion();

        // Initialiser le timer si time_limit existe
        if ($this->quiz->time_limit) {
            $this->timeRemaining = $this->quiz->time_limit * 60; // Convertir en secondes
        }
    }

    /**
     * Démarre le quiz
     */
       public function startQuiz()
    {

        // Vérifier si on peut créer une nouvelle tentative
        $currentAttemptsCount = QuizAttempt::where('user_id', Auth::id())
        ->where('quiz_id', $this->quiz->id)
        ->whereNotNull('completed_at')
        ->count();

         // Vérifier les tentatives restantes AVANT de créer
        if ($currentAttemptsCount >= $this->quiz->max_attempts) {
            $this->error(__('You have reached the maximum number of attempts for this quiz.'));
            $this->initializeQuizStats(); // Recalcule les stats
            return;
        }

        // Créer une nouvelle tentative
        $this->attempt = QuizAttempt::create([
            'user_id' => Auth::id(),
            'quiz_id' => $this->quiz->id,
            'score' => 0,
            'time_spent' => 0,
            'answers' => []
        ]);

        $this->status = 'in_progress';
        $this->currentQuestionIndex = 0;
        $this->updateCurrentQuestion();
        $this->initializeQuizStats();
    }

    public function saveAnswer($answer): void
    {
        if (!$this->attempt || $this->status !== 'in_progress') {
            return;
        }

        try {
            // Récupère les réponses actuelles
            $currentAnswers = $this->attempt->answers ?? [];

            // Sauvegarde la réponse pour la question courante
            $currentAnswers[$this->currentQuestionIndex] = [
                'question_id' => $this->currentQuestion->id,
                'answer' => $answer,
                'is_correct' => $this->isAnswerCorrect($answer),
                'submitted_at' => now()->toISOString()
            ];

            // Calcule le nouveau score
            $newScore = $this->calculateCurrentScore();

            // Met à jour la tentative
            $this->attempt->update([
                'answers' => $currentAnswers,
                'score' => $newScore, // Mise à jour du score
                'time_spent' => $this->timeSpent // Sauvegarde du temps
            ]);

            // Recharge l'attempt pour rafraîchir les données
            $this->attempt = $this->attempt->fresh();

            $this->userAnswers = $currentAnswers;

            $this->dispatch('answer-saved');
        } catch (\Exception $e) {
            logger()->error('Error saving answer: ' . $e->getMessage());
            $this->error(__('Error saving your answer.'));
        }
    }

    /**
     * Calcule les statistiques détaillées du quiz
     */
    public function getQuizStats()
    {
        if (!$this->attempt || empty($this->userAnswers)) {
            return [
                'total_questions' => $this->questions->count(),
                'correct_answers' => 0,
                'incorrect_answers' => $this->questions->count(),
                'accuracy' => 0,
                'total_points' => $this->questions->sum('points'),
                'earned_points' => 0,
                'score_percentage' => 0,
                'question_stats' => [],
                'time_spent_minutes' => $this->timeSpent,
                'passed' => false
            ];
        }

        $totalQuestions = $this->questions->count();
        $correctAnswers = 0;
        $totalPoints = 0;
        $earnedPoints = 0;
        $questionStats = [];

        foreach ($this->questions as $index => $question) {
            $userAnswer = $this->userAnswers[$index] ?? null;
            $isCorrect = $userAnswer['is_correct'] ?? false;
            $userAnswerText = $userAnswer['answer'] ?? null;

            if ($isCorrect) {
                $correctAnswers++;
                $earnedPoints += $question->points;
            }

            $totalPoints += $question->points;

            $questionStats[] = [
                'question' => $question->question,
                'user_answer' => $this->formatUserAnswer($userAnswerText, $question),
                'correct_answer' => $this->formatCorrectAnswer($question),
                'is_correct' => $isCorrect,
                'points' => $question->points,
                'earned_points' => $isCorrect ? $question->points : 0,
                'explanation' => $question->explanation,
                'type' => $question->type
            ];
        }

        return [
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'incorrect_answers' => $totalQuestions - $correctAnswers,
            'accuracy' => $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0,
            'total_points' => $totalPoints,
            'score_percentage' => $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100) : 0,
            'question_stats' => $questionStats,
            'time_spent_minutes' => $this->timeSpent,
            'passed' => $earnedPoints >= ($totalPoints * 0.7) // 70% pour réussir
        ];
    }

    /**
     * Attribue les badges en fonction de la performance
     */
    private function awardBadges()
    {
        $stats = $this->getQuizStats();
        $dadges = [];

        if ($stats['accuracy'] >= 90) {
            $badges[] = ['name' => 'perfectionist', 'title' => __('Perfectionist'), 'color' => 'gold'];
        }

        if ($stats['accuracy'] >= 80) {
            $badges[] = ['name' => 'quick_learner', 'title' => __('Quick Learner'), 'color' => 'silver'];
        }

        if ($stats['time_spent_minutes'] < 5 && [$stats['total_questions'] >= 10]) {
            $badges[] = ['name' => 'speed_demon', 'title' => __('Speed Demon'), 'color' => 'bronze'];
        }

        if ($stats['passed']) {
            $badges[] = ['name' => 'quiz_master', 'title' => __('Quiz Master'), 'color' => 'purple'];
        }

        return $badges;
    }

    /**
     * Formate la réponse de l'utilisateur pour l'affichage
     */

    private function formatUserAnswer($userAnswer, $question)
    {
        if ($userAnswer === null) {
            return __('Not answered');
        }

        switch ($question->type) {
            case 'multiple_choice':
                $options = $question->formatted_options;
                return $options[$userAnswer] ?? $userAnswer;

            case 'true_false':
                return $userAnswer;

            default:
                return $userAnswer;
        }
    }

    /**
     * Récupère la leçon suivante
     */
    public function getNextLesson()
    {
        try {
            $currentLesson = $this->quiz->lesson;
            $nextLesson = Lesson::where('course_id', $currentLesson->course_id)
                ->where('order', '>', $currentLesson)
                ->orderBy('order', 'asc')
                ->first();

            return $nextLesson;
        } catch (\Exception $e) {
            logger('Error getting next lesson: ' . $e->getMessage());
            $this->error('Error getting next lesson');
            return null;
        }
    }

    /**
     * Formate la réponse correcte pour l'affichage
     */
    public function formatCorrectAnswer($question)
    {
        switch ($question->type) {
            case 'multiple_choice':
                $options = $question->formatted_options;
                return $options[$question->correct_answer] ?? $question->correct_answer;

            case 'true_false':
                return $question->correct_answer === 'true' ? __('True') : __('false');

            case 'short_answer':
                return $question->correct_answer;

            default:
                return $question->correct_answer;
        }
    }


    /**
     * Vérifie si la réponse est correcte
     */
    private function isAnswerCorrect($userAnswer)
    {
        $correctAnswer = $this->currentQuestion->correct_answer;

        switch ($this->currentQuestion->type) {
            case 'multiple_choice':
                return $userAnswer === $correctAnswer;
            case 'true_false':
                return $userAnswer === $correctAnswer;

            case 'short_answer':
                return strtolower(trim($userAnswer)) === strtolower(trim($correctAnswer));

            default:
                return false;
        }
    }

    /**
     * Marque la leçon comme complétée si le quiz est réussi
     */
    private function markLessonAsCompleted()
    {
        try {
            $stats = $this->getQuizStats();

            if ($stats && $stats['passed']) {
                // Marquer la leçon comme complétée
                $progress = Progress::firstOrCreate(
                    [
                        'user_id' => Auth::id(),
                        'lesson_id' => $this->quiz->lesson_id
                    ],
                    [
                        'time_spent' => $this->timeSpent * 60, // Convertir en secondes
                        'is_completed' => true,
                        'completed_at' => now()
                    ]
                );

                if (!$progress->is_completed) {
                    $progress->update([
                        'is_completed' => true,
                        'completed_at' => now(),
                        'time_spent' => $this->timeSpent * 60
                    ]);
                }

                return true;
            }

            return false;
        } catch (\Exception $e) {
            logger()->error('Error marking lesson as completed: ' . $e->getMessage());
            $this->error(__('Error marking lesson as completed'));
            return false;
        }
    }


    /**
     * Calcule le score actuel
     */
    public function calculateCurrentScore(): int
    {
        if (!$this->attempt || empty($this->userAnswers)) {
            return 0;
        }

        $score = 0;

        foreach ($this->userAnswers as $answer) {
            if ($answer['is_correct'] ?? false) {
                // Trouve la question pour obtenir les points
                $question = $this->questions->firstWhere('id', $answer['question_id']);
                if ($question) {
                    $score += $question->points;
                }
            }
        }

        return $score;
    }

    /**
     * Met à jour la question courante
     */
    public function updateCurrentQuestion()
    {
        if (isset($this->questions[$this->currentQuestionIndex])) {
            $this->currentQuestion = $this->questions[$this->currentQuestionIndex];
        } else {
            $this->currentQuestion = null;
        }
    }

    /**
     * Question précédente
     */
    public function previousQuestion()
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
            $this->updateCurrentQuestion();
        }
    }

    /**
     * Question suivante
     */
    public function nextQuestion()
    {
        if ($this->currentQuestionIndex < $this->questions->count() - 1) {
            $this->currentQuestionIndex++;
            $this->updateCurrentQuestion();
        }
    }

    /**
     * Termine le quiz et calcule le score final
     */
    public function finishQuiz()
    {
        if (!$this->attempt) {
            return;
        }

        try {
            // Calcule le score final
            $finalScore = $this->calculateCurrentScore();

            // Met à jour la tentative
            $this->attempt->update([
                'score' => $finalScore,
                'completed_at' => now(),
                'time_spent' => $this->timeSpent * 60
            ]);

            // Recharge l'attempt
            $this->attempt = $this->attempt->fresh();

            // Marque la leçon comme complétée si le quiz est réussi
            $lessonCompleted = $this->markLessonAsCompleted();

            if ($lessonCompleted) {
                $this->success(__('Congratulations! You passed the quiz and unlocked the next lesson.'));
            } else {
                $this->warning(__('You completed the quiz but did not reach the passing score.'));
            }

            $this->status = 'completed';
            $this->initializeQuizStats(); // Mettre à jour les stats

            $this->dispatch('quiz-completed', score: $finalScore);
        } catch (\Exception $e) {
            logger()->error('Error finishing quiz: ' . $e->getMessage());
            $this->error(__('Error completing the quiz.'));
        }
    }

    /**
     * Décremente le timer (à appeler toutes les secondes)
     */
    public function decrementTimer()
    {
        if ($this->status !== 'in_progress' || !$this->quiz->time_limit) {
            return;
        }

        if ($this->timeRemaining > 0) {
            $this->timeRemaining--;
            $this->timeSpent++;

            // Sauvegarde périodique du temps (toutes les 30 secondes)
            if ($this->timeSpent % 30 === 0 && $this->attempt) {
                $this->attempt->update(['time_spent' => $this->timeSpent]);
            }

            // Temps écoulé - fin automatique du quiz
            if ($this->timeRemaining <= 0) {
                $this->finishQuiz();
                $this->dispatch('time-up');
            }
        }
    }

    /**
     * Polling pour le timer
     */
    public function getListeners()
    {
        return [
            'decrementTimer' => 'decrementTimer',
        ];
    }

    /**
     * Met à jour le temps passé (pour les quizzes sans limite de temps)
     */
    public function updateTimeSpent()
    {
        if ($this->status === 'in_progress' && $this->attempt) {
            $this->timeSpent++; // Maintenant ça compte des minutes
            $this->attempt->update(['time_spent' => $this->timeSpent * 60]); // Convertit en secondes pour la BDD
        }
    }

    /**
     * Réinitialise les statistiques (pour testing)
     */
    public function resetAttempts()
    {
        // ⚠️ À utiliser seulement pour le développement
        if (app()->environment('local')) {
            QuizAttempt::where('user_id', Auth::id())
                ->where('quiz_id', $this->quiz->id)
                ->delete();


            $this->userAttemptsCount = 0;
            $this->initializeQuizStats();
            $this->initializeQuiz();
            $this->success(__('Quiz attempts reset successfully.'));
        }
    }

}; ?>

{{-- resources/views/livewire/quiz-component.blade.php --}}
<div class="quiz-container max-w-4xl mx-auto p-6" wire:key="quiz-{{ $quiz->id }}">
    {{-- Titre de la page --}}
    @section('title', __('Quiz for:') . ' ' . $quiz->lesson->title ?? config('app.name'))

    @php
        $stats = $this->getQuizStats();
        $isPassed = $stats['passed'] ?? false;
    @endphp

    {{-- Score en temps réel --}}
    <div class="text-center mb-4">
        <div class="inline-block bg-primary/10 text-primary text-lg px-4 py-2 rounded-full">
            <strong>{!! __('Score') !!}:</strong>
            {{ $attempt->score ?? 0 }}/{{ $quiz->max_score }}
        </div>
    </div>

    {{-- Indicateur de réponses --}}
    <div class="flex items-center justify-center space-x-2 mb-4">
        @foreach($questions as $index => $question)
            @php
                $isAnswered = isset($userAnswers[$index]);
                $isCurrent = $index === $currentQuestionIndex;
                $isCorrect = $userAnswers[$index]['is_correct'] ?? false;
            @endphp

            <div class="w-3 h-3 rounded-full cursor-pointer transition-all duration-200
                {{ $isCurrent ? 'bg-primary scale-125' : '' }}
                {{ !$isCurrent && $isAnswered && $isCorrect ? 'bg-green-500' : '' }}
                {{ !$isCurrent && $isAnswered && !$isCorrect ? 'bg-red-500' : '' }}
                {{ !$isCurrent && !$isAnswered ? 'bg-gray-300' : '' }}"
                wire:click="$set('currentQuestionIndex', {{ $index }})">
            </div>
        @endforeach
    </div>

    {{-- État : Maximum de tentatives atteint --}}
    @if($status === 'max_attempts_reached')
        <div class="text-center py-12">
            <x-icon name="o-no-symbol" class="w-16 h-16 text-warning mx-auto mb-4" />
            <h1 class="text-3xl font-bold text-gray-900 mb-4">{!! __('Maximum attempts reached') !!}</h1>
            <p class="text-gray-600 mb-6 max-w-2xl mx-auto">
                {!! __('You have used all your attempts for this quiz.') !!}
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 max-w-2xl mx-auto">
                <div class="bg-gray-50 p-6 rounded-lg">
                    <x-icon name="o-flag" class="w-8 h-8 text-primary mx-auto mb-2" />
                    <div class="text-2xl font-bold text-gray-900">{{ $userAttemptsCount }}/{{ $quiz->max_attempts }}</div>
                    <div class="text-sm text-gray-600">{!! __('Attempts used') !!}</div>
                </div>

                <div class="bg-gray-50 p-6 rounded-lg">
                    <x-icon name="o-trophy" class="w-8 h-8 text-primary mx-auto mb-2" />
                    <div class="text-2xl font-bold text-gray-900">{{ $bestScore }}/{{ $quiz->max_score }}</div>
                    <div class="text-sm text-gray-600">{!! __('Best score') !!}</div>
                </div>
            </div>

            <x-button
                label="{!! __('Back to lesson') !!}"
                icon="o-arrow-left"
                class="btn-ghost"
                link="{{ route('student.lesson.show', ['course' => $quiz->lesson->course, 'lesson' => $quiz->lesson]) }}"
            />

             {{-- Bouton de reset pour le développement --}}
            @env('local')
                <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm text-yellow-800 mb-2">🧪 Développement uniquement</p>
                    <x-button
                        label="{!! __('Reset attempts') !!}"
                        icon="o-arrow-path"
                        class="btn-warning btn-sm"
                        wire:click="resetAttempts"
                        spinner
                    />
                </div>
            @endenv
        </div>
    @endif

    {{-- État : Non démarré --}}
    @if($status === 'not_started')
        <div class="text-center py-12">
            <x-icon name="o-question-mark-circle" class="w-16 h-16 text-primary mx-auto mb-4" />
            <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $quiz->title }}</h1>
            <p class="text-gray-600 mb-6 max-w-2xl mx-auto">{{ $quiz->description }}</p>

            {{-- Statistiques des tentatives --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8 max-w-3xl mx-auto">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <x-icon name="o-question-mark-circle" class="w-8 h-8 text-primary mx-auto mb-2" />
                    <div class="text-2xl font-bold text-gray-900">{{ $quiz->question_count }}</div>
                    <div class="text-sm text-gray-600">{!! __('Questions') !!}</div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <x-icon name="o-clock" class="w-8 h-8 text-primary mx-auto mb-2" />
                    <div class="text-2xl font-bold text-gray-900">
                        {{ $quiz->time_limit ? $quiz->time_limit . ' ' . __('min') : __('Unlimited') }}
                    </div>
                    <div class="text-sm text-gray-600">{!! __('Duration') !!}</div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <x-icon name="o-flag" class="w-8 h-8 text-primary mx-auto mb-2" />
                    <div class="text-2xl font-bold text-gray-900">{{ $remainingAttempts }}/{{ $quiz->max_attempts }}</div>
                    <div class="text-sm text-gray-600">{!! __('Attempts left') !!}</div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <x-icon name="o-trophy" class="w-8 h-8 text-primary mx-auto mb-2" />
                    <div class="text-2xl font-bold text-gray-900">{{ $bestScore }}/{{ $quiz->max_score }}</div>
                    <div class="text-sm text-gray-600">{!! __('Best score') !!}</div>
                </div>
            </div>

            {{-- Résumé exécutif --}}
            <div class="bg-gradient-to-r from-blue-50 to-indigo-100 rounded-lg shadow-lg p-6 mb-8 border border-blue-200">
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <x-icon name="o-sparkles" class="w-6 h-6 text-blue-500 mr-2" />
                    {!! __('Performance Summary') !!}
                </h3>

                @if($isPassed)
                    <div class="text-green-700 bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center">
                            <x-icon name="o-check-circle" class="w-6 h-6 text-green-600 mr-2" />
                            <span class="font-semibold">{!! __('Excellent work!') !!}</span>
                        </div>
                        <p class="mt-2 text-green-800">
                            {!! __('You demonstrated strong understanding of the concepts with :accuracy% accuracy. Keep up the great work!', ['accuracy' => $stats['accuracy']]) !!}
                        </p>
                    </div>
                @else
                    <div class="text-orange-700 bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center">
                            <x-icon name="o-light-bulb" class="w-6 h-6 text-orange-600 mr-2" />
                            <span class="font-semibold">{!! __('Good effort!') !!}</span>
                        </div>
                        <p class="mt-2 text-orange-800">
                            {!! __('You scored :score% but need :needed% more to pass. Review the questions below and try again!', ['score' => $stats['score_percentage'], 'needed' => 70 - $stats['score_percentage']]) !!}
                        </p>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                    <div class="flex items-center">
                        <x-icon name="o-check-badge" class="w-4 h-4 text-green-500 mr-2" />
                        <span><strong>{{ $stats['correct_answers'] }}</strong> {!! __('questions answered correctly') !!}</span>
                    </div>
                    <div class="flex items-center">
                        <x-icon name="o-clock" class="w-4 h-4 text-blue-500 mr-2" />
                        <span><strong>{{ $stats['time_spent_minutes'] }}</strong> {!! __('minutes spent') !!}</span>
                    </div>
                    <div class="flex items-center">
                        <x-icon name="o-chart-bar" class="w-4 h-4 text-purple-500 mr-2" />
                        <span><strong>{{ $stats['accuracy'] }}%</strong> {!! __('overall accuracy') !!}</span>
                    </div>
                    <div class="flex items-center">
                        <x-icon name="o-trophy" class="w-4 h-4 text-yellow-500 mr-2" />
                        <span><strong>{{ $stats['earned_points'] }}/{{ $stats['total_points'] }}</strong> {!! __('points earned') !!}</span>
                    </div>
                </div>
            </div>

            @if($remainingAttempts > 0)
                <x-button
                    label="{!! __('Start quiz') !!}"
                    icon="o-play"
                    class="btn-primary btn-lg"
                    wire:click="startQuiz"
                    spinner
                />
            @else
                <x-button
                    label="{!! __('No attempts left') !!}"
                    icon="o-no-symbol"
                    class="btn-disabled btn-lg"
                    disabled
                />
            @endif
        </div>
    @endif

    {{-- État : En cours --}}
    @if($status === 'in_progress' && $currentQuestion)
        <div class="bg-white rounded-lg shadow-lg p-6">

            {{-- En-tête du quiz --}}
            <div class="flex justify-between items-center mb-6 pb-4 border-b">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $quiz->title }}</h2>
                    <p class="text-gray-600">{!! __('Question') !!} {{ $currentQuestionIndex + 1 }} {!! __('on') !!} {{ $quiz->question_count }}</p>
                </div>

                {{-- Timer en MINUTES --}}
                @if($quiz->time_limit)
                    <div class="text-right">
                        <div class="text-sm text-gray-600">{!! __('Time remaining') !!}</div>
                        <div class="text-2xl font-bold
                            {{ $timeRemaining <= 60 ? 'text-red-600' : 'text-primary' }}"
                            wire:poll.1000ms="decrementTimer">
                            {{ floor($timeRemaining / 60) }}:{{ sprintf('%02d', $timeRemaining % 60) }} {{-- Format MM:SS --}}
                        </div>
                        @if($timeRemaining <= 60)
                            <div class="text-xs text-red-600 mt-1">
                                {!! __('Less than 1 minute left!') !!}
                            </div>
                        @endif
                    </div>
                @else
                    <div class="text-right">
                        <div class="text-sm text-gray-600">{!! __('Time spent') !!}</div>
                        <div class="text-2xl font-bold text-primary"
                            wire:poll.60000ms="updateTimeSpent"> {{-- Polling chaque minute --}}
                            {{ $timeSpent }} {!! __('min') !!} {{-- Affiche en minutes --}}
                        </div>
                    </div>
                @endif
            </div>

            {{-- Section comparaison --}}
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h3 class="text-xl font-bold text-gray-900 mb-4">{!! __('How You Compare') !!}</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center p-4 border border-gray-200 rounded-lg">
                        <div class="text-2xl font-bold text-green-600">{{ $stats['accuracy'] }}%</div>
                        <div class="text-sm text-gray-600">{!! __('Your Accuracy') !!}</div>
                        <div class="text-xs text-gray-500 mt-1">+5% vs average</div>
                    </div>
                    <div class="text-center p-4 border border-gray-200 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600">{{ $stats['time_spent_minutes'] }}min</div>
                        <div class="text-sm text-gray-600">{!! __('Your Time') !!}</div>
                        <div class="text-xs text-gray-500 mt-1">-2min vs average</div>
                    </div>
                    <div class="text-center p-4 border border-gray-200 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600">{{ $stats['score_percentage'] }}%</div>
                        <div class="text-sm text-gray-600">{!! __('Your Score') !!}</div>
                        <div class="text-xs text-gray-500 mt-1">+8% vs average</div>
                    </div>
                </div>
            </div>

            {{-- Barre de progression --}}
            <div class="mb-6">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>{!! __('Progress') !!}</span>
                    <span>{{ $quiz->question_count > 0 ? round(($currentQuestionIndex / $quiz->question_count) * 100) : 0 }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div
                        class="bg-primary h-2 rounded-full transition-all duration-300"
                        style="width: {{ $quiz->question_count > 0 ? ($currentQuestionIndex / $quiz->question_count) * 100 : 0 }}%"
                    ></div>
                </div>
            </div>

            {{-- Question actuelle --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ $currentQuestion->question }}
                </h3>

                {{-- Indicateur de points --}}
                <div class="inline-block bg-primary/10 text-primary text-sm px-3 py-1 rounded-full mb-4">
                    {{ $currentQuestion->points }} {!! __('point(s)') !!}
                </div>

                {{-- Interface des réponses INTERACTIVE --}}
                <div class="space-y-3 mt-6">
                    @if($currentQuestion->is_multiple_choice)
                        {{-- Questions à choix multiples --}}
                        @foreach($currentQuestion->formatted_options as $key => $option)
                            @php
                                $userAnswer = $userAnswers[$currentQuestionIndex]['answer'] ?? null;
                                $isSelected = $userAnswer === (string)$key;
                                $isCorrect = $userAnswers[$currentQuestionIndex]['is_correct'] ?? false;
                                $showResults = $status === 'completed';
                            @endphp

                            <div class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-all duration-200
                                {{ $isSelected ? 'bg-primary/10 border-primary' : '' }}
                                {{ $showResults && $isSelected && $isCorrect ? 'bg-green-50 border-green-200' : '' }}
                                {{ $showResults && $isSelected && !$isCorrect ? 'bg-red-50 border-red-200' : '' }}"
                                wire:click="saveAnswer('{{ $key }}')">

                                <div class="w-6 h-6 rounded-full border border-gray-300 mr-3 flex items-center justify-center
                                    {{ $isSelected ? 'bg-primary border-primary text-white' : '' }}
                                    {{ $showResults && $isSelected && $isCorrect ? 'bg-green-500 border-green-500 text-white' : '' }}
                                    {{ $showResults && $isSelected && !$isCorrect ? 'bg-red-500 border-red-500 text-white' : '' }}">
                                    {{ chr(65 + $key) }}
                                </div>

                                <span class="text-gray-700 {{ $isSelected ? 'font-medium' : '' }}">
                                    {{ $option }}
                                </span>

                                {{-- Indicateur de correction --}}
                                @if($showResults && $isSelected)
                                    @if($isCorrect)
                                        <x-icon name="o-check" class="w-5 h-5 text-green-500 ml-auto" />
                                    @else
                                        <x-icon name="o-x-mark" class="w-5 h-5 text-red-500 ml-auto" />
                                    @endif
                                @endif
                            </div>
                        @endforeach

                    @elseif($currentQuestion->is_true_false)
                        {{-- Vrai/Faux --}}
                        @php
                            $userAnswer = $userAnswers[$currentQuestionIndex]['answer'] ?? null;
                            $showResults = $status === 'completed';
                        @endphp

                        <div class="grid grid-cols-2 gap-4">
                            {{-- Bouton Vrai --}}
                            <div class="flex items-center justify-center p-6 border border-green-200 rounded-lg hover:bg-green-50 cursor-pointer text-center transition-all duration-200
                                {{ $userAnswer === 'true' ? 'bg-green-100 border-green-400' : '' }}
                                {{ $showResults && $userAnswer === 'true' && ($userAnswers[$currentQuestionIndex]['is_correct'] ?? false) ? 'bg-green-100 border-green-400' : '' }}
                                {{ $showResults && $userAnswer === 'true' && !($userAnswers[$currentQuestionIndex]['is_correct'] ?? false) ? 'bg-red-100 border-red-400' : '' }}"
                                wire:click="saveAnswer('true')">

                                <x-icon name="o-check" class="w-6 h-6 text-green-600 mr-2" />
                                <span class="text-green-700 font-medium">{!! __('True') !!}</span>
                            </div>

                            {{-- Bouton Faux --}}
                            <div class="flex items-center justify-center p-6 border border-red-200 rounded-lg hover:bg-red-50 cursor-pointer text-center transition-all duration-200
                                {{ $userAnswer === 'false' ? 'bg-red-100 border-red-400' : '' }}
                                {{ $showResults && $userAnswer === 'false' && ($userAnswers[$currentQuestionIndex]['is_correct'] ?? false) ? 'bg-red-100 border-red-400' : '' }}
                                {{ $showResults && $userAnswer === 'false' && !($userAnswers[$currentQuestionIndex]['is_correct'] ?? false) ? 'bg-green-100 border-green-400' : '' }}"
                                wire:click="saveAnswer('false')">

                                <x-icon name="o-x-mark" class="w-6 h-6 text-red-600 mr-2" />
                                <span class="text-red-700 font-medium">{!! __('False') !!}</span>
                            </div>
                        </div>

                    @else
                        {{-- Réponse courte --}}
                        @php
                            $userAnswer = $userAnswers[$currentQuestionIndex]['answer'] ?? '';
                            $showResults = $status === 'completed';
                        @endphp

                        <div class="border border-gray-200 rounded-lg p-4
                            {{ $showResults ? 'bg-gray-50' : '' }}">
                            <input
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent
                                    {{ $showResults ? 'bg-white' : '' }}"
                                placeholder="{!! __('Type your answer here...') !!}"
                                value="{{ $userAnswer }}"
                                {{ $showResults ? 'disabled' : '' }}
                                wire:model.debounce.500ms="userAnswers.{{ $currentQuestionIndex }}.answer"
                                wire:change="saveAnswer($event.target.value)"
                            >

                            {{-- Affichage de la correction --}}
                            @if($showResults)
                                <div class="mt-3 p-3 rounded-lg
                                    {{ ($userAnswers[$currentQuestionIndex]['is_correct'] ?? false) ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                    <div class="flex items-center">
                                        @if($userAnswers[$currentQuestionIndex]['is_correct'] ?? false)
                                            <x-icon name="o-check" class="w-5 h-5 mr-2" />
                                            <span class="font-medium">{!! __('Correct!') !!}</span>
                                        @else
                                            <x-icon name="o-x-mark" class="w-5 h-5 mr-2" />
                                            <span class="font-medium">{!! __('Incorrect') !!}</span>
                                        @endif
                                    </div>
                                    <div class="mt-2 text-sm">
                                        <strong>{!! __('Correct answer') !!}:</strong> {{ $currentQuestion->correct_answer }}
                                    </div>
                                    @if($currentQuestion->explanation)
                                        <div class="mt-2 text-sm">
                                            <strong>{!! __('Explanation') !!}:</strong> {{ $currentQuestion->explanation }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Navigation --}}
            <div class="flex justify-between items-center pt-4 border-t">
                <div>
                    @if($currentQuestionIndex > 0)
                        <x-button
                            label="{!! __('Previous') !!}"
                            icon="o-arrow-left"
                            class="btn-ghost"
                            wire:click="previousQuestion"
                        />
                    @endif
                </div>

                <div class="flex space-x-2">
                    @if($currentQuestionIndex < $quiz->question_count - 1)
                        <x-button
                            label="{!! __('Next') !!}"
                            icon-right="o-arrow-right"
                            class="btn-primary"
                            wire:click="nextQuestion"
                        />
                    @else
                        <x-button
                            label="{!! __('Finish quiz') !!}"
                            icon="o-check"
                            class="btn-success"
                            wire:click="finishQuiz"
                        />
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- État : Terminé --}}
    @if($status === 'completed')

        <div class="max-w-6xl mx-auto py-8">
            {{-- En-tête des résultats --}}
            <div class="text-center mb-8">
                <x-icon name="o-check-circle" class="w-20 h-20 {{ $isPassed ? 'text-success' : 'text-warning' }} mx-auto mb-4" />
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    {{ $isPassed ? __('Quiz Passed!') : __('Quiz Completed') }}
                </h1>
                <p class="text-xl text-gray-600 mb-6">
                    {{ $isPassed ? __('Congratulations! You passed the quiz.') : __('Review your results below.') }}
                </p>
            </div>

            {{-- Statistiques principales --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <x-icon name="o-trophy" class="w-12 h-12 text-yellow-500 mx-auto mb-3" />
                    <div class="text-3xl font-bold text-gray-900">{{ $stats['score_percentage'] ?? 0 }}%</div>
                    <div class="text-sm text-gray-600">{!! __('Final Score') !!}</div>
                </div>

                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <x-icon name="o-check-badge" class="w-12 h-12 text-green-500 mx-auto mb-3" />
                    <div class="text-3xl font-bold text-gray-900">{{ $stats['correct_answers'] ?? 0 }}/{{ $stats['total_questions'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">{!! __('Correct Answers') !!}</div>
                </div>

                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <x-icon name="o-clock" class="w-12 h-12 text-blue-500 mx-auto mb-3" />
                    <div class="text-3xl font-bold text-gray-900">{{ $stats['time_spent_minutes'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">{!! __('Minutes') !!}</div>
                </div>

                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <x-icon name="o-chart-bar" class="w-12 h-12 text-purple-500 mx-auto mb-3" />
                    <div class="text-3xl font-bold text-gray-900">{{ $stats['accuracy'] ?? 0 }}%</div>
                    <div class="text-sm text-gray-600">{!! __('Accuracy') !!}</div>
                </div>
            </div>

            {{-- Ajoutez cette section après les statistiques principales --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                {{-- Graphique de répartition des réponses --}}
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">{!! __('Answers Distribution') !!}</h4>
                    <div class="flex items-center justify-center h-48">
                        <div class="relative w-32 h-32">
                            {{-- Cercle des correctes --}}
                            <div class="absolute inset-0 rounded-full border-8 border-green-500"></div>
                            {{-- Cercle des incorrectes --}}
                            <div class="absolute inset-0 rounded-full border-8 border-red-500"
                                style="clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%);
                                        transform: rotate({{ ($stats['correct_answers'] / $stats['total_questions']) * 360 }}deg);">
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-900">{{ $stats['correct_answers'] }}</div>
                                    <div class="text-sm text-gray-600">{!! __('Correct') !!}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-center space-x-4 mt-4">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">{!! __('Correct') !!} ({{ $stats['correct_answers'] }})</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">{!! __('Incorrect') !!} ({{ $stats['incorrect_answers'] }})</span>
                        </div>
                    </div>
                </div>

                {{-- Temps par question --}}
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">{!! __('Performance Insights') !!}</h4>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>{!! __('Average time per question') !!}</span>
                                <span>{{ $stats['time_spent_minutes'] > 0 ? round(($stats['time_spent_minutes'] * 60) / $stats['total_questions']) : 0 }}s</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: 60%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>{!! __('Success rate') !!}</span>
                                <span>{{ $stats['accuracy'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ $stats['accuracy'] }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Barre de progression du score --}}
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">{!! __('Score Overview') !!}</h3>
                    <span class="text-2xl font-bold {{ $isPassed ? 'text-green-600' : 'text-orange-600' }}">
                        {{ $stats['earned_points'] ?? 0 }}/{{ $stats['total_points'] ?? 0 }} {!! __('points') !!}
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div class="bg-gradient-to-r from-blue-500 to-green-500 h-4 rounded-full transition-all duration-1000"
                        style="width: {{ $stats['score_percentage'] ?? 0 }}%">
                    </div>
                </div>
                <div class="flex justify-between text-sm text-gray-600 mt-2">
                    <span>0%</span>
                    <span class="{{ $isPassed ? 'text-green-600 font-bold' : '' }}">70% {!! __('to pass') !!}</span>
                    <span>100%</span>
                </div>
            </div>

            {{-- Section badges --}}
            @if(count($badges = $this->awardBadges()) > 0)
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg shadow-lg p-6 mb-8 border border-purple-200">
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <x-icon name="o-trophy" class="w-6 h-6 text-yellow-500 mr-2" />
                    {!! __('Badges Earned') !!}
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($badges as $badge)
                        <div class="text-center p-4 bg-white rounded-lg border-2 border-{{ $badge['color'] }}-300 shadow-sm">
                            <x-icon name="o-shield-check" class="w-8 h-8 text-{{ $badge['color'] }}-500 mx-auto mb-2" />
                            <div class="font-semibold text-gray-900 text-sm">{{ $badge['title'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Détail question par question --}}
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h3 class="text-xl font-bold text-gray-900 mb-6">{!! __('Question Review') !!}</h3>

                <div class="space-y-6">
                    @foreach($stats['question_stats'] as $index => $questionStat)
                        <div class="border border-gray-200 rounded-lg p-6 {{ $questionStat['is_correct'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                            <div class="flex items-start justify-between mb-4">
                                <h4 class="text-lg font-semibold text-gray-900">
                                    {!! __('Question') !!} {{ $index + 1 }}
                                </h4>
                                <div class="flex items-center space-x-2">
                                    @if($questionStat['is_correct'])
                                        <x-icon name="o-check-circle" class="w-6 h-6 text-green-500" />
                                        <span class="text-green-600 font-medium">+{{ $questionStat['earned_points'] }} {!! __('points') !!}</span>
                                    @else
                                        <x-icon name="o-x-circle" class="w-6 h-6 text-red-500" />
                                        <span class="text-red-600 font-medium">0/{{ $questionStat['points'] }} {!! __('points') !!}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Question --}}
                            <div class="mb-4">
                                <p class="text-gray-700 font-medium">{{ $questionStat['question'] }}</p>
                            </div>

                            {{-- Réponses --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div class="bg-white rounded-lg p-4 border {{ $questionStat['is_correct'] ? 'border-green-300' : 'border-red-300' }}">
                                    <div class="text-sm font-medium text-gray-600 mb-2">{!! __('Your Answer') !!}</div>
                                    <div class="text-gray-900">{{ $questionStat['user_answer'] }}</div>
                                </div>

                                @if(!$questionStat['is_correct'])
                                    <div class="bg-green-50 rounded-lg p-4 border border-green-300">
                                        <div class="text-sm font-medium text-gray-600 mb-2">{!! __('Correct Answer') !!}</div>
                                        <div class="text-gray-900">{{ $questionStat['correct_answer'] }}</div>
                                    </div>
                                @endif
                            </div>

                            {{-- Explication --}}
                            @if($questionStat['explanation'])
                                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                                    <div class="text-sm font-medium text-blue-700 mb-2">{!! __('Explanation') !!}</div>
                                    <div class="text-blue-800">{{ $questionStat['explanation'] }}</div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                @if($isPassed && $nextLesson = $this->getNextLesson())
                    <x-button
                        label="{!! __('Next Lesson') !!}"
                        icon="o-arrow-right"
                        class="btn-primary btn-lg"
                        link="{{ route('student.lesson.show', ['course' => $quiz->lesson->course, 'lesson' => $nextLesson]) }}"
                    />
                @endif

                <x-button
                    label="{!! __('Back to Lesson') !!}"
                    icon="o-arrow-left"
                    class="btn-ghost btn-lg"
                    link="{{ route('student.lesson.show', ['course' => $quiz->lesson->course, 'lesson' => $quiz->lesson]) }}"
                />

                <x-button
                    label="{!! __('Retry Quiz') !!}"
                    icon="o-arrow-path"
                    class="btn-outline btn-lg"
                    wire:click="initializeQuiz"
                />
            </div>
        </div>
    @endif
</div>
