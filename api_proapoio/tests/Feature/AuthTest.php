<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes de autenticação básicos.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function candidato_can_register()
    {
        // Criar uma deficiência para usar no teste
        $deficiencia = \App\Models\Deficiencia::firstOrCreate(
            ['nome' => 'Autismo'],
            ['nome' => 'Autismo']
        );

        $response = $this->postJson('/api/auth/register/candidato', [
            'nome' => 'Teste User',
            'email' => 'teste@example.com',
            'senha' => 'Password123',
            'senha_confirmation' => 'Password123',
            'cpf' => '52998224725', // CPF Válido para testes
            'data_nascimento' => '1990-01-01',
            'cep' => '01001000',
            'telefone' => '11999999999',
            'nivel_escolaridade' => 'Superior Completo',
            'curso_superior' => 'Pedagogia',
            'instituicao_ensino' => 'USP',
            'experiencias_profissionais' => [
                [
                    'idade_aluno' => 10,
                    'tempo_experiencia' => '5 anos',
                    'candidatar_mesma_deficiencia' => true,
                    'comentario' => 'Tenho experiência de 5 anos trabalhando com educação especial e alunos com autismo.',
                    'deficiencia_ids' => [$deficiencia->id_deficiencia]
                ]
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('usuarios', ['email' => 'teste@example.com']);
        $this->assertDatabaseHas('candidatos', ['cpf' => '52998224725']);
    }
}