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
            'experiencia' => 'Tenho experiência de 5 anos trabalhando com educação especial.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('usuarios', ['email' => 'teste@example.com']);
        $this->assertDatabaseHas('candidatos', ['cpf' => '52998224725']);
    }
}