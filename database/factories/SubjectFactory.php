<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjects = [
            'Mathématiques',
            'Français',
            'Histoire-Géographie',
            'SVT',
            'Philosophie',
            'Anglais',
            'Physique-Chimie',
            'Technologie'
        ];

        $level = fake()->randomElement(['college','lycee','Form 1','form 2','form 3','form 4','form five', 'lower sixth','upper sixth', 'sixième',  'cinquième', 'quatrième', 'troisième', 'seconde', 'première', 'terminale']);

        $name = fake()->randomElement($subjects);

        return [
            'name' => $name,
            'description' => fake()->paragraph(),
            'level' => $level,
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(['book', 'calculator', 'globe', 'atom', 'pencil'])
        ];
    }
}
