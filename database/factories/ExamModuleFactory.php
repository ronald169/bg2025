<?php
// database/factories/ExamModuleFactory.php
namespace Database\Factories;

use App\Models\ExamModule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamModuleFactory extends Factory
{
    protected $model = ExamModule::class;

    public function definition(): array
    {
        return [
            'exam_id' => null,
            'name' => 'Lesen',
            'code' => 'lesen',
            'order' => 0,
            'duration_minutes' => 65,
            'general_instructions' => 'Das Modul Lesen hat fünf Teile. Sie lesen mehrere Texte und lösen Aufgaben dazu.',
            'has_global_numbering' => true,
        ];
    }
}
