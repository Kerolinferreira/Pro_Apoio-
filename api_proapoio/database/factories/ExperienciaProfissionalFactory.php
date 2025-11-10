<?php

namespace Database\Factories;

use App\Models\ExperienciaProfissional;
use App\Models\Candidato;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExperienciaProfissional>
 */
class ExperienciaProfissionalFactory extends Factory
{
    protected $model = ExperienciaProfissional::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_candidato' => Candidato::factory(),
            'idade_aluno' => fake()->optional()->numberBetween(3, 18),
            'tempo_experiencia' => fake()->optional()->randomElement([
                'Menos de 1 ano',
                '1-2 anos',
                '2-3 anos',
                '3-5 anos',
                'Mais de 5 anos',
            ]),
            'interesse_mesma_deficiencia' => fake()->boolean(60),
            'descricao' => fake()->optional()->paragraph(),
        ];
    }
}
