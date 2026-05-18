<?php
// database/seeders/FlashcardsTableSeeder.php

namespace Database\Seeders;

use App\Models\Flashcard;
use App\Models\FlashcardSet;
use App\Models\User;
use Illuminate\Database\Seeder;

class FlashcardsTableSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::where('role', 'student')->get();

        $vocabulary = [
            ['question' => 'der Apfel', 'answer' => 'the apple', 'example' => 'Der Apfel ist rot.'],
            ['question' => 'das Buch', 'answer' => 'the book', 'example' => 'Ich lese ein Buch.'],
            ['question' => 'die Schule', 'answer' => 'the school', 'example' => 'Die Schule ist groß.'],
            ['question' => 'laufen', 'answer' => 'to run', 'example' => 'Ich laufe jeden Morgen.'],
            ['question' => 'sprechen', 'answer' => 'to speak', 'example' => 'Sprichst du Deutsch?'],
            ['question' => 'schnell', 'answer' => 'fast', 'example' => 'Das Auto ist schnell.'],
            ['question' => 'schön', 'answer' => 'beautiful', 'example' => 'Das Wetter ist schön.'],
            ['question' => 'morgen', 'answer' => 'tomorrow', 'example' => 'Bis morgen!'],
        ];

        foreach ($students as $student) {
            $setCount = rand(1, 3);

            for ($s = 0; $s < $setCount; $s++) {
                $set = FlashcardSet::create([
                    'user_id' => $student->id,
                    'name' => 'Vocabulary Set ' . ($s + 1),
                    'description' => 'Essential German vocabulary for daily use',
                ]);

                $cardCount = rand(5, 10);
                $selectedCards = array_rand($vocabulary, min($cardCount, count($vocabulary)));

                foreach ($selectedCards as $cardIndex) {
                    $card = $vocabulary[$cardIndex];
                    Flashcard::create([
                        'flashcard_set_id' => $set->id,
                        'question' => $card['question'],
                        'answer' => $card['answer'],
                        'example' => $card['example'],
                    ]);
                }
            }
        }
    }
}
