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

        return [
            'description' => fake()->paragraph(),
            'sub_system' => fake()->randomElement(['francophone', 'anglophone']),
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(['book-open', 'calculator', 'backward', 'chart-pie', 'pencil'])
        ];
    }
}
