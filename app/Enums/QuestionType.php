<?php
// app/Enums/QuestionType.php
namespace App\Enums;

enum QuestionType: string
{
    case TRUE_FALSE = 'true_false';      // Richtig / Falsch
    case YES_NO = 'yes_no';              // Ja / Nein
    case SINGLE_CHOICE = 'single_choice'; // QCM a / b / c
    case SHORT_ANSWER = 'short_answer';   // Lettre (a-j) ou locuteur (a/b/c)
    case MEDIUM_TEXT = 'medium_text';     // Production écrite (~80 mots)
    case ORAL_TASK = 'oral_task';         // Consigne orale (Sprechen)

    public function label(): string
    {
        return match($this) {
            self::TRUE_FALSE => 'Richtig / Falsch',
            self::YES_NO => 'Ja / Nein',
            self::SINGLE_CHOICE => 'Single Choice (a/b/c)',
            self::SHORT_ANSWER => 'Short Answer (lettre)',
            self::MEDIUM_TEXT => 'Texte moyen (~80 mots)',
            self::ORAL_TASK => 'Tâche orale',
        };
    }
}
