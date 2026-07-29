<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    protected string $apiKey;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.deepseek.api_key');
        $this->apiUrl = config('services.deepseek.api_url', 'https://api.deepseek.com/v1/chat/completions');
    }

    /**
     * Envoie une requête à l'API DeepSeek pour évaluer une production écrite.
     *
     * @param string $studentText Le texte de l'étudiant.
     * @param string $level Le niveau de l'examen (A1, A2, B1, etc.).
     * @param string $taskDescription La description de la tâche d'écriture.
     * @return array|null Les résultats de l'évaluation ou null en cas d'échec.
     */
    public function gradeEssay(string $studentText, string $level, string $taskDescription): ?array
    {
        $prompt = $this->buildPrompt($studentText, $level, $taskDescription);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, [
                'model' => 'deepseek-chat', // ou le modèle que vous souhaitez utiliser
                'messages' => [
                    ['role' => 'system', 'content' => 'Vous êtes un examinateur expert du Goethe-Institut.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object'], // Demande une réponse JSON
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                return json_decode($content, true);
            }

            Log::error('Erreur API DeepSeek', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Exception lors de l\'appel à DeepSeek', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Construit le prompt pour l'API DeepSeek.
     */
    private function buildPrompt(string $studentText, string $level, string $taskDescription): string
    {
        return <<<PROMPT
Vous êtes un examinateur expert du Goethe-Institut pour le niveau {$level}.

Voici le sujet de la tâche d'écriture :
{$taskDescription}

Voici le texte de l'étudiant à évaluer :
---
{$studentText}
---

Évaluez ce texte selon les critères du Goethe-Institut :
1. **Cohérence** (structure, organisation des idées, cohésion du texte)
2. **Lexique** (richesse du vocabulaire, précision)
3. **Grammaire** (correction grammaticale, structures utilisées)
4. **Orthographe** (correction orthographique)

Pour chaque critère, attribuez une note sur 5 points (0 = très faible, 5 = excellent).
Donnez également une note globale sur 20 points et un feedback général constructif en français.

Votre réponse doit être au format JSON strict, avec les clés suivantes :
- "score" : (note globale sur 20, en nombre décimal)
- "critiques" : (objet avec les clés "coherence", "lexique", "grammaire", "orthographe", chacune avec une note sur 5)
- "feedback" : (texte du feedback général en français)

Exemple de réponse :
{
  "score": 14.5,
  "critiques": {
    "coherence": 4,
    "lexique": 3.5,
    "grammaire": 3,
    "orthographe": 4
  },
  "feedback": "Votre texte est bien structuré..."
}
PROMPT;
    }
}
