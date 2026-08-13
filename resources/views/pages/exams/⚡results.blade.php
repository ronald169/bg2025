<?php

use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Mary\Traits\Toast;
use App\Models\ExamAttempt;
use App\Models\ExamResponse;
use App\Models\ExamGrading;
use Illuminate\Support\Facades\Auth;

new
#[Title('Résultats de l\'examen')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast;

    public ExamAttempt $attempt;
    public $exam;
    public $teile = [];
    public $totalScore = 0;
    public $maxScore = 0;
    public $scorePercentage = 0;
    public $gradings = [];

    public function mount(ExamAttempt $attempt): void
    {
        $this->attempt = $attempt;
        $this->exam = $attempt->exam;

        // Vérifier que l'utilisateur est bien le propriétaire de la tentative ou un admin/teacher
        if (Auth::id() !== $attempt->user_id && !Auth::user()->hasRole(['admin', 'teacher'])) {
            abort(403, __('Vous n\'êtes pas autorisé à voir ces résultats.'));
        }

        $this->loadResults();
    }

    private function loadResults(): void
    {
        $this->teile = [];
        $this->totalScore = 0;
        $this->maxScore = 0;

        foreach ($this->exam->teile as $teil) {
            $teilData = [
                'title' => $teil->title,
                'questions' => [],
                'score' => 0,
                'max_score' => 0,
                'is_submitted' => isset($this->attempt->teil_states[$teil->id]) &&
                                  $this->attempt->teil_states[$teil->id]['status'] === 'submitted',
            ];

            foreach ($teil->questions as $question) {
                $response = ExamResponse::where('exam_attempt_id', $this->attempt->id)
                    ->where('exam_question_id', $question->id)
                    ->first();

                $score = $response->score_obtained ?? 0;
                $maxScore = $question->points;

                $questionData = [
                    'id' => $question->id,
                    'text' => $question->question_text,
                    'type' => $question->type,
                    'score' => $score,
                    'max_score' => $maxScore,
                    'is_auto_graded' => $response ? $response->is_auto_graded : false,
                    'is_teacher_graded' => $response ? $response->is_teacher_graded : false,
                ];

                // Récupérer la correction IA pour les essais
                if ($question->type === 'essay' && $response && $response->grading) {
                    $grading = $response->grading;
                    $questionData['ai_score'] = $grading->ai_score;
                    $questionData['ai_feedback'] = $grading->ai_feedback;
                    $questionData['teacher_score'] = $grading->teacher_score;
                    $questionData['teacher_feedback'] = $grading->teacher_feedback;
                    $questionData['status'] = $grading->status;
                }

                $teilData['questions'][] = $questionData;
                $teilData['score'] += $score;
                $teilData['max_score'] += $maxScore;
            }

            $this->teile[] = $teilData;
            $this->totalScore += $teilData['score'];
            $this->maxScore += $teilData['max_score'];
        }

        $this->scorePercentage = $this->maxScore > 0 ? round(($this->totalScore / $this->maxScore) * 100, 1) : 0;
    }

    public function render()
    {
        return $this->view([
            'attempt' => $this->attempt,
            'exam' => $this->exam,
            'teile' => $this->teile,
            'totalScore' => $this->totalScore,
            'maxScore' => $this->maxScore,
            'scorePercentage' => $this->scorePercentage,
        ]);
    }
};
?>
<div class="container mx-auto p-6 max-w-6xl">
    <x-header title="{{ $exam->title }}" subtitle="{{ __('Résultats de l\'examen') }}" separator>
        <x-slot:actions>
            <x-button label="{{ __('Retour') }}" link="{{ route('dashboard.redirect') }}" icon="o-arrow-left" class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="stat bg-base-100 shadow rounded-box p-4 text-center">
            <div class="stat-title">{{ __('Score total') }}</div>
            <div class="stat-value text-3xl font-bold text-primary">{{ $totalScore }} / {{ $maxScore }}</div>
        </div>
        <div class="stat bg-base-100 shadow rounded-box p-4 text-center">
            <div class="stat-title">{{ __('Pourcentage') }}</div>
            <div class="stat-value text-3xl font-bold {{ $scorePercentage >= 60 ? 'text-success' : 'text-error' }}">{{ $scorePercentage }}%</div>
        </div>
        <div class="stat bg-base-100 shadow rounded-box p-4 text-center">
            <div class="stat-title">{{ __('Teile') }}</div>
            <div class="stat-value text-3xl font-bold">{{ count($teile) }}</div>
        </div>
        <div class="stat bg-base-100 shadow rounded-box p-4 text-center">
            <div class="stat-title">{{ __('Questions') }}</div>
            <div class="stat-value text-3xl font-bold">{{ collect($teile)->sum(fn($t) => count($t['questions'])) }}</div>
        </div>
    </div>

    <!-- Détails par Teil -->
    <div class="space-y-4">
        @foreach($teile as $index => $teil)
            <x-card class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box">
                <div class="collapse-title text-xl font-medium flex justify-between items-center">
                    <span>{{ $teil['title'] }}</span>
                    <span class="text-sm">
                        {{ $teil['score'] }} / {{ $teil['max_score'] }}
                        @if($teil['is_submitted'])
                            <span class="badge badge-success ml-2">{{ __('Soumis') }}</span>
                        @else
                            <span class="badge badge-warning ml-2">{{ __('Non soumis') }}</span>
                        @endif
                    </span>
                </div>
                <div class="collapse-content">
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Question') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Score') }}</th>
                                    <th>{{ __('Statut') }}</th>
                                    <th>{{ __('Feedback') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teil['questions'] as $q)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ Str::limit($q['text'], 80) }}</td>
                                        <td><span class="badge badge-ghost">{{ $q['type'] }}</span></td>
                                        <td>{{ $q['score'] }} / {{ $q['max_score'] }}</td>
                                        <td>
                                            @if($q['is_teacher_graded'])
                                                <span class="badge badge-info">{{ __('Corrigé par professeur') }}</span>
                                            @elseif($q['is_auto_graded'])
                                                <span class="badge badge-success">{{ __('Auto-corrigé') }}</span>
                                            @else
                                                <span class="badge badge-warning">{{ __('En attente') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($q['ai_feedback']))
                                                <div class="tooltip" data-tip="{{ $q['ai_feedback'] }}">
                                                    <x-icon name="o-chat-bubble-left" class="w-5 h-5 text-info" />
                                                </div>
                                            @elseif(isset($q['teacher_feedback']))
                                                <div class="tooltip" data-tip="{{ $q['teacher_feedback'] }}">
                                                    <x-icon name="o-chat-bubble-left" class="w-5 h-5 text-primary" />
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-bold">
                                    <td colspan="3">{{ __('Total') }}</td>
                                    <td>{{ $teil['score'] }} / {{ $teil['max_score'] }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </x-card>
        @endforeach
    </div>

    <div class="mt-6 text-center">
        <x-button label="{{ __('Retour à l\'accueil') }}" link="{{ route('dashboard.redirect') }}" class="btn-primary" />
    </div>
</div>
