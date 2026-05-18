<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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

        $level = [
            'college',
            'lycee',
            'form 1',
            'form 2',
            'form 3',
            'form 4',
            'form five',
            'lower sixth',
            'upper sixth',
            'sixième',
            'cinquième',
            'quatrième',
            'troisième',
            'seconde',
            'première',
            'terminale'
        ];

        $subsystem = ['francophone', 'anglophone'];

        foreach ($subjects as $subject) {
            Subject::factory()->create([
                'name' => $subject,
                'level' => $level[rand(0, 14)],
                'sub_system' => $subsystem[rand(0,1)]
            ]);
        }
    }
}
