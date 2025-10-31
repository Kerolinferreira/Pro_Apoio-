<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase; // Reseta o banco de dados a cada teste
    use WithFaker;

    /**
     * @test
     * @description Testa o registro bem-sucedido de um candidato.
     */
    public function it_successfully_registers_a_candidato(): void
    {
        $candidatoData = [
            'nome' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'cpf' => '15823635002', // CPF válido
            'cep' => '12345-678',
            'telefone' => '11987654321',
            'data_nascimento' => '1995-05-10',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'nivel_escolaridade' => 'Superior Completo',
            'curso_superior' => 'Ciência da Computação',
            'instituicao_ensino' => 'Universidade Teste',
            'experiencia' => $this->faker->paragraph,
        ];

        $response = $this->postJson('/api/auth/register/candidato', $candidatoData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'nome', 'email', 'tipo_usuario'],
                'token',
            ])
            ->assertJsonPath('user.email', strtolower($candidatoData['email']));

        $this->assertDatabaseHas('users', [
            'email' => strtolower($candidatoData['email']),
            'tipo_usuario' => 'CANDIDATO',
        ]);

        $this->assertDatabaseHas('candidatos', [
            'cpf' => '15823635002',
        ]);
    }

    /**
     * @test
     * @description Testa a falha no registro de candidato com e-mail duplicado.
     */
    public function it_fails_to_register_candidato_with_duplicate_email(): void
    {
        // Cria um usuário primeiro
        $existingUser = User::factory()->create(['tipo_usuario' => 'CANDIDATO']);

        $candidatoData = [
            'nome' => $this->faker->name,
            'email' => $existingUser->email, // Usa o mesmo email
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'cpf' => '15823635002',
            'cep' => '12345-678',
            'telefone' => '11987654321',
            'data_nascimento' => '1995-05-10',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'nivel_escolaridade' => 'Médio Completo',
            'experiencia' => 'Nenhuma',
        ];

        $response = $this->postJson('/api/auth/register/candidato', $candidatoData);

        $response->assertStatus(422) // Unprocessable Entity
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * @test
     * @description Testa o login bem-sucedido de um usuário.
     */
    public function it_successfully_logs_in_a_user(): void
    {
        $password = 'password123';
        $user = User::factory()->create([
            'senha_hash' => Hash::make($password),
            'tipo_usuario' => 'CANDIDATO',
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => $password,
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'token'])
            ->assertJsonPath('user.tipo_usuario', 'candidato'); // Verifica se o tipo vem em minúsculo
    }

    /**
     * @test
     * @description Testa a falha no login com senha incorreta.
     */
    public function it_fails_to_log_in_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $loginData = ['email' => $user->email, 'password' => 'wrong-password'];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
