<?php
// database/factories/ExamFactory.php
namespace Database\Factories;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    protected $model = Exam::class;

    public function definition(): array
    {
        return [
            'title' => 'ÖSD Zertifikat B1',
            'slug' => 'osd-zertifikat-b1-' . uniqid(),
            'subtitle' => 'Modellsatz Erwachsene',
            'level' => 'B1',
            'total_duration_minutes' => 180,
            'is_active' => true,
        ];
    }
}
