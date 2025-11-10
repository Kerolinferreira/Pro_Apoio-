<?php

namespace Database\Factories;

use App\Models\ExperienciaPessoal;
use App\Models\Candidato;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExperienciaPessoal>
 */
class ExperienciaPessoalFactory extends Factory
{
    protected $model = ExperienciaPessoal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_candidato' => Candidato::factory(),
            'interesse_atuar' => fake()->boolean(70), // 70% de chance de ter interesse
            'descricao' => fake()->optional()->paragraph(),
        ];
    }
}
