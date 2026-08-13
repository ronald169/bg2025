<?php
namespace App\Services;

use App\Models\ExamModule;
use App\Models\ExamTeil;
use App\Models\ExamQuestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeilToModuleService
{
    /**
     * Attache un Teil existant à un Module avec un ordre donné.
     * Les questions du Teil deviennent automatiquement accessibles depuis le Module.
     */
    public function attach(ExamModule $module, ExamTeil $teil, int $pivotOrder): void
    {
        // Évite les doublons
        if ($module->teils()->where('exam_teils.id', $teil->id)->exists()) {
            return;
        }

        DB::transaction(function () use ($module, $teil, $pivotOrder) {
            // 1. Attache le contenu (texte, audio, image) via la pivot
            $module->teils()->attach($teil->id, ['order' => $pivotOrder]);

            // 2. Réordonne les autres teils si besoin (évite les conflits d'ordre)
            $this->reorderTeils($module);
        });
    }

    /**
     * Détache un Teil d'un Module.
     * Les questions restent intactes (partagées avec d'autres modules éventuels).
     */
    public function detach(ExamModule $module, ExamTeil $teil): void
    {
        DB::transaction(function () use ($module, $teil) {
            $module->teils()->detach($teil->id);
            $this->reorderTeils($module);
        });
    }

    /**
     * Change l'ordre d'un Teil dans un Module.
     */
    public function moveTeil(ExamModule $module, ExamTeil $teil, int $newOrder): void
    {
        $module->teils()->updateExistingPivot($teil->id, ['order' => $newOrder]);
        $this->reorderTeils($module);
    }

    /**
     * Récupère toutes les questions d'un module avec leur numéro global calculé.
     * Ex: Lesen questions 1 à 30, Hören 1 à 25...
     *
     * @return Collection<ExamQuestion> Chaque question a un attribut `global_number`
     */
    public function getQuestionsWithGlobalNumbering(ExamModule $module): Collection
    {
        $questions = collect();
        $globalNumber = 1;

        // Récupère les teils dans l'ordre du module
        $teils = $module->teils()->orderByPivot('order')->get();

        foreach ($teils as $teil) {
            $teilQuestions = $teil->questions()
                ->orderBy('sort_order')
                ->get();

            foreach ($teilQuestions as $question) {
                // Injection dynamique du numéro global pour l'affichage
                $question->global_number = $globalNumber++;
                $questions->push($question);
            }
        }

        return $questions;
    }

    /**
     * Récupère une question spécifique par son numéro global dans un module.
     */
    public function findQuestionByGlobalNumber(ExamModule $module, int $globalNumber): ?ExamQuestion
    {
        $questions = $this->getQuestionsWithGlobalNumbering($module);

        return $questions->firstWhere('global_number', $globalNumber);
    }

    /**
     * Calcule le score maximum possible pour un module (somme des points des questions).
     */
    public function calculateMaxScore(ExamModule $module): float
    {
        return $this->getQuestionsWithGlobalNumbering($module)->sum('points');
    }

    /**
     * Réordonne les teils d'un module pour avoir des ordres consécutifs (0, 1, 2...).
     */
    private function reorderTeils(ExamModule $module): void
    {
        $teils = $module->teils()
            ->orderByPivot('order')
            ->get();

        foreach ($teils as $index => $teil) {
            $module->teils()->updateExistingPivot($teil->id, ['order' => $index]);
        }
    }

    /**
     * Duplique un Teil et ses questions pour créer une variante indépendante.
     * Utile si tu veux réutiliser un Teil mais modifier certaines questions.
     */
    public function duplicateTeilWithQuestions(ExamTeil $originalTeil, array $newAttributes = []): ExamTeil
    {
        return DB::transaction(function () use ($originalTeil, $newAttributes) {
            // Clone le Teil
            $newTeil = $originalTeil->replicate();
            $newTeil->fill($newAttributes);
            $newTeil->save();

            // Clone les questions
            foreach ($originalTeil->questions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->teil_id = $newTeil->id;
                $newQuestion->save();
            }

            return $newTeil;
        });
    }
}
