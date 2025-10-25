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
        $response = $this->postJson('/api/register/candidato', [
            'nome' => 'Teste User',
            'email' => 'teste@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'cpf' => '12345678909',
            'cep' => '01001000',
            'telefone' => '11999999999',
            'escolaridade' => 'Superior Completo',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'teste@example.com']);
        $this->assertDatabaseHas('candidatos', ['cpf' => '12345678909']);
    }
}