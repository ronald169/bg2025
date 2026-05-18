<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 5 teachers
        $teachers = User::factory(5)->teacher()->create();

        // 10 students
        $students = User::factory(10)->create();

        // one admin
        User::factory()->create([
            'name' => 'Administrator BrainGenius',
            'email' => 'admin@braingenius.test',
            'role' => 'admin',
        ]);
    }
}
