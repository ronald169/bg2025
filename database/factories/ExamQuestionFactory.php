<?php
// database/factories/ExamQuestionFactory.php
namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\ExamQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamQuestionFactory extends Factory
{
    protected $model = ExamQuestion::class;

    public function definition(): array
    {
        return [
            'teil_id' => 1,
            'sort_order' => 0,
            'question_type' => QuestionType::SINGLE_CHOICE->value,
            'content' => $this->faker->sentence(),
            'image_path' => null,
            'points' => 1,
            'options' => null,
            'correct_answer' => null,
            'correct_answer_explanation' => null,
        ];
    }
}
