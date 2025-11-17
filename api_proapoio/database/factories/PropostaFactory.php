<?php

namespace Database\Factories;

use App\Models\Proposta;
use App\Models\Vaga;
use App\Models\Candidato;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Proposta>
 */
class PropostaFactory extends Factory
{
    protected $model = Proposta::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_vaga' => Vaga::factory(),
            'id_candidato' => Candidato::factory(),
            'iniciador' => fake()->randomElement(['CANDIDATO', 'INSTITUICAO']),
            'status' => \App\Enums\PropostaStatus::ENVIADA,
            'mensagem' => fake()->optional()->paragraph(),
            'mensagem_resposta' => null,
            'data_envio' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'data_resposta' => null,
            'data_criacao' => now(),
            'id_remetente' => fake()->optional()->randomNumber(),
            'tipo_remetente' => fake()->optional()->randomElement(['CANDIDATO', 'INSTITUICAO']),
            'id_destinatario' => fake()->optional()->randomNumber(),
            'tipo_destinatario' => fake()->optional()->randomElement(['CANDIDATO', 'INSTITUICAO']),
        ];
    }

    /**
     * Indicate that the proposta is enviada.
     */
    public function enviada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Enums\PropostaStatus::ENVIADA,
            'mensagem_resposta' => null,
            'data_resposta' => null,
        ]);
    }

    /**
     * Indicate that the proposta was accepted.
     */
    public function aceita(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Enums\PropostaStatus::ACEITA,
            'mensagem_resposta' => fake()->paragraph(),
            'data_resposta' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the proposta was rejected.
     */
    public function recusada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Enums\PropostaStatus::RECUSADA,
            'mensagem_resposta' => fake()->paragraph(),
            'data_resposta' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the proposta was initiated by a candidate.
     */
    public function deCandidato(): static
    {
        return $this->state(fn (array $attributes) => [
            'iniciador' => 'CANDIDATO',
        ]);
    }

    /**
     * Indicate that the proposta was initiated by an institution.
     */
    public function deInstituicao(): static
    {
        return $this->state(fn (array $attributes) => [
            'iniciador' => 'INSTITUICAO',
        ]);
    }
}
