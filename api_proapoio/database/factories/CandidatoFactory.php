<?php

namespace Database\Factories;

use App\Models\Candidato;
use App\Models\User;
use App\Models\Endereco;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Candidato>
 */
class CandidatoFactory extends Factory
{
    protected $model = Candidato::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_usuario' => User::factory()->candidato(),
            'id_endereco' => Endereco::factory(),
            'nome_completo' => fake()->name(),
            'cpf' => $this->generateCPF(),
            'telefone' => $this->generateBrazilianPhone(),
            'data_nascimento' => fake()->date('Y-m-d', '-18 years'),  // Novo campo
            'foto_perfil_url' => fake()->optional()->imageUrl(),  // Renomeado
            'link_perfil' => fake()->optional()->url(),
            'nivel_escolaridade' => fake()->randomElement([
                'Ensino Fundamental Incompleto',
                'Ensino Fundamental Completo',
                'Ensino Médio Incompleto',
                'Ensino Médio Completo',
                'Ensino Superior Incompleto',
                'Ensino Superior Completo',
                'Pós-Graduação',
            ]),
            'curso_superior' => fake()->optional()->randomElement([
                'Pedagogia',
                'Licenciatura em Matemática',
                'Psicologia',
                'Educação Física',
            ]),
            'instituicao_ensino' => fake()->optional()->company(),
            'status' => fake()->randomElement(['ATIVO', 'INATIVO']),
        ];
    }

    /**
     * Generate a valid Brazilian CPF.
     */
    private function generateCPF(): string
    {
        $n1 = rand(0, 9);
        $n2 = rand(0, 9);
        $n3 = rand(0, 9);
        $n4 = rand(0, 9);
        $n5 = rand(0, 9);
        $n6 = rand(0, 9);
        $n7 = rand(0, 9);
        $n8 = rand(0, 9);
        $n9 = rand(0, 9);

        $d1 = $n9 * 2 + $n8 * 3 + $n7 * 4 + $n6 * 5 + $n5 * 6 + $n4 * 7 + $n3 * 8 + $n2 * 9 + $n1 * 10;
        $d1 = 11 - ($d1 % 11);
        if ($d1 >= 10) {
            $d1 = 0;
        }

        $d2 = $d1 * 2 + $n9 * 3 + $n8 * 4 + $n7 * 5 + $n6 * 6 + $n5 * 7 + $n4 * 8 + $n3 * 9 + $n2 * 10 + $n1 * 11;
        $d2 = 11 - ($d2 % 11);
        if ($d2 >= 10) {
            $d2 = 0;
        }

        return sprintf('%d%d%d.%d%d%d.%d%d%d-%d%d', $n1, $n2, $n3, $n4, $n5, $n6, $n7, $n8, $n9, $d1, $d2);
    }

    /**
     * Generate a Brazilian phone number in the format (XX) XXXXX-XXXX or (XX) XXXX-XXXX.
     */
    private function generateBrazilianPhone(): string
    {
        $ddd = rand(11, 99);
        $isMobile = rand(0, 1);

        if ($isMobile) {
            // Mobile: (XX) 9XXXX-XXXX
            $firstPart = rand(90000, 99999);
            $secondPart = rand(1000, 9999);
            return sprintf('(%02d) %d-%d', $ddd, $firstPart, $secondPart);
        } else {
            // Landline: (XX) XXXX-XXXX
            $firstPart = rand(2000, 5999);
            $secondPart = rand(1000, 9999);
            return sprintf('(%02d) %d-%d', $ddd, $firstPart, $secondPart);
        }
    }
}
