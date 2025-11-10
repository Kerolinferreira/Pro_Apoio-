<?php

namespace Database\Factories;

use App\Models\VagaSalva;
use App\Models\Candidato;
use App\Models\Vaga;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VagaSalva>
 */
class VagaSalvaFactory extends Factory
{
    protected $model = VagaSalva::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_candidato' => Candidato::factory(),
            'id_vaga' => Vaga::factory(),
        ];
    }
}
