<?php

namespace Database\Factories;

use App\Models\Endereco;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Endereco>
 */
class EnderecoFactory extends Factory
{
    protected $model = Endereco::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cep' => fake()->numerify('########'),
            'logradouro' => fake()->streetName(),
            'bairro' => fake()->word(),
            'cidade' => fake()->city(),
            'estado' => fake()->stateAbbr(),
            'numero' => fake()->buildingNumber(),
            'complemento' => fake()->optional()->secondaryAddress(),
            'ponto_referencia' => fake()->optional()->sentence(),
        ];
    }
}
