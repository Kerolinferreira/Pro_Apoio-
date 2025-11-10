<?php

namespace Database\Factories;

use App\Models\Instituicao;
use App\Models\User;
use App\Models\Endereco;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Instituicao>
 */
class InstituicaoFactory extends Factory
{
    protected $model = Instituicao::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_usuario' => User::factory()->instituicao(),
            'id_endereco' => Endereco::factory(),
            'cnpj' => $this->generateCNPJ(),
            'razao_social' => fake()->company(),
            'nome_fantasia' => fake()->companySuffix() . ' ' . fake()->word(),
            'codigo_inep' => fake()->optional()->numerify('########'),
            'tipo_instituicao' => fake()->randomElement([
                'Escola Pública',
                'Escola Particular',
                'Instituto',
                'Fundação',
                'ONG',
            ]),
            'niveis_oferecidos' => fake()->optional()->randomElement([
                'Educação Infantil',
                'Ensino Fundamental',
                'Ensino Médio',
                'Educação de Jovens e Adultos',
                'Ensino Superior',
            ]),
            'nome_responsavel' => fake()->name(),
            'funcao_responsavel' => fake()->randomElement([
                'Diretor',
                'Coordenador Pedagógico',
                'Gerente de RH',
                'Secretário',
            ]),
            'email_corporativo' => fake()->unique()->companyEmail(),
            'telefone_fixo' => $this->generateBrazilianLandline(),
            'celular_corporativo' => $this->generateBrazilianMobile(),
            'logo_url' => fake()->optional()->imageUrl(),
        ];
    }

    /**
     * Generate a valid Brazilian CNPJ.
     */
    private function generateCNPJ(): string
    {
        $n1 = rand(0, 9);
        $n2 = rand(0, 9);
        $n3 = rand(0, 9);
        $n4 = rand(0, 9);
        $n5 = rand(0, 9);
        $n6 = rand(0, 9);
        $n7 = rand(0, 9);
        $n8 = rand(0, 9);
        $n9 = 0;
        $n10 = 0;
        $n11 = 0;
        $n12 = 1;

        $d1 = $n12 * 2 + $n11 * 3 + $n10 * 4 + $n9 * 5 + $n8 * 6 + $n7 * 7 + $n6 * 8 + $n5 * 9 + $n4 * 2 + $n3 * 3 + $n2 * 4 + $n1 * 5;
        $d1 = 11 - ($d1 % 11);
        if ($d1 >= 10) {
            $d1 = 0;
        }

        $d2 = $d1 * 2 + $n12 * 3 + $n11 * 4 + $n10 * 5 + $n9 * 6 + $n8 * 7 + $n7 * 8 + $n6 * 9 + $n5 * 2 + $n4 * 3 + $n3 * 4 + $n2 * 5 + $n1 * 6;
        $d2 = 11 - ($d2 % 11);
        if ($d2 >= 10) {
            $d2 = 0;
        }

        return sprintf('%d%d%d%d%d%d%d%d%d%d%d%d%d%d', $n1, $n2, $n3, $n4, $n5, $n6, $n7, $n8, $n9, $n10, $n11, $n12, $d1, $d2);
    }

    /**
     * Generate a Brazilian landline number (only digits).
     */
    private function generateBrazilianLandline(): string
    {
        $ddd = rand(11, 99);
        $firstPart = rand(2000, 5999);
        $secondPart = rand(1000, 9999);
        return sprintf('%02d%d%d', $ddd, $firstPart, $secondPart);
    }

    /**
     * Generate a Brazilian mobile number (only digits).
     */
    private function generateBrazilianMobile(): string
    {
        $ddd = rand(11, 99);
        $firstPart = rand(90000, 99999);
        $secondPart = rand(1000, 9999);
        return sprintf('%02d%d%d', $ddd, $firstPart, $secondPart);
    }
}
