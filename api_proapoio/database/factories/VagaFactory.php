<?php

namespace Database\Factories;

use App\Models\Vaga;
use App\Models\Instituicao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vaga>
 */
class VagaFactory extends Factory
{
    protected $model = Vaga::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $temRemuneracao = fake()->boolean(80); // 80% chance de ter remuneração
        $valorRemuneracao = $temRemuneracao ? fake()->randomFloat(2, 500, 5000) : null;

        return [
            'id_instituicao' => Instituicao::factory(),
            'status' => 'ATIVA', // Padrão ATIVA conforme migration
            'aluno_nascimento_mes' => fake()->optional()->numberBetween(1, 12),
            'aluno_nascimento_ano' => fake()->optional()->numberBetween(2000, 2020),
            'necessidades_descricao' => fake()->optional()->paragraph(),
            'descricao' => fake()->optional()->paragraph(),
            'carga_horaria_semanal' => fake()->optional()->numberBetween(10, 40),
            'regime_contratacao' => fake()->optional()->randomElement([
                'CLT',
                'PJ',
                'Estágio',
                'Contrato Temporário',
                'Voluntariado',
            ]),
            'valor_remuneracao' => $valorRemuneracao,
            'remuneracao' => $valorRemuneracao,  // Mesmo valor
            'tipo_remuneracao' => $temRemuneracao ? fake()->randomElement([
                'Mensal',
                'Hora',
                'Projeto',
            ]) : 'Sem Remuneração',
            'tipo' => fake()->optional()->randomElement(['CLT', 'PJ', 'Estágio', 'Temporário']),
            'modalidade' => fake()->optional()->randomElement(['Presencial', 'Remoto', 'Híbrido']),
            'titulo' => fake()->jobTitle(),
            'titulo_vaga' => fake()->jobTitle(),
            'cidade' => fake()->city(),
            'estado' => fake()->stateAbbr(),
            'data_criacao' => now(),
        ];
    }
}
