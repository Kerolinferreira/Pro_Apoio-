<?php

namespace Database\Factories;

use App\Models\Deficiencia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deficiencia>
 */
class DeficienciaFactory extends Factory
{
    protected $model = Deficiencia::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->randomElement([
                'Deficiência Física',
                'Deficiência Visual',
                'Deficiência Auditiva',
                'Deficiência Intelectual',
                'Transtorno do Espectro Autista',
                'Deficiência Múltipla',
            ]),
        ];
    }
}
