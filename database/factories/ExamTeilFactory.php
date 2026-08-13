<?php
// database/factories/ExamTeilFactory.php
namespace Database\Factories;

use App\Models\ExamTeil;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamTeilFactory extends Factory
{
    protected $model = ExamTeil::class;

    public function definition(): array
    {
        return [
            'title' => 'Teil ' . $this->faker->numberBetween(1, 5),
            'order' => 0,
            'duration_minutes' => 10,
            'instructions' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'content_image' => null,
            'audio_path' => null,
            'source' => null,
        ];
    }
}
