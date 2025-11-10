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
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'senha' => 'password',  // Campo senha para testes
            'senha_hash' => static::$password ??= Hash::make('password'),
            'tipo_usuario' => fake()->randomElement(['CANDIDATO', 'INSTITUICAO', 'ADMINISTRADOR']),
            'termos_aceite' => true,
            'data_termos_aceite' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user is a candidate.
     */
    public function candidato(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_usuario' => 'CANDIDATO',
        ]);
    }

    /**
     * Indicate that the user is an institution.
     */
    public function instituicao(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_usuario' => 'INSTITUICAO',
        ]);
    }

    /**
     * Indicate that the user is an administrator.
     */
    public function administrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_usuario' => 'ADMINISTRADOR',
        ]);
    }
}
