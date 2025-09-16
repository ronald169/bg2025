<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Add
            'role' => 'student',
            'phone' => fake()->phoneNumber(),
            'date_of_birth' => fake()->dateTimeBetween('2000-01-01', '2010-01-01')->format('Y-m-d'),
            'level' => fake()->randomElement(['college','lycee','Form 1','form 2','form 3','form 4','form five', 'lower sixth','upper sixth', 'sixième',  'cinquième', 'quatrième', 'troisième', 'seconde', 'première', 'terminale']),
            'bio' => fake()->paragraph()
        ];
    }

    public function teacher()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'teacher',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
