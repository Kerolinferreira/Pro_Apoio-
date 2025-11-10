<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Candidato;
use App\Models\Instituicao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'senha_hash' => Hash::make('password123'),
            'tipo_usuario' => 'CANDIDATO'
        ]);

        Candidato::factory()->create(['id_usuario' => $user->id_usuario]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'senha' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => ['id_usuario', 'nome', 'email', 'tipo_usuario']
            ]);
    }

    /** @test */
    public function it_fails_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'senha_hash' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'senha' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Credenciais inválidas.']);
    }

    /** @test */
    public function it_fails_login_with_non_existent_user()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'senha' => 'password123'
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Credenciais inválidas.']);
    }

    /** @test */
    public function it_can_register_candidato()
    {
        $response = $this->postJson('/api/auth/register/candidato', [
            'nome' => 'João Silva',
            'email' => 'joao@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'nome_completo' => 'João da Silva Santos',
            'cpf' => '52998224725',
            'telefone' => '11987654321',
            'data_nascimento' => '1990-05-15',
            'cep' => '01234567',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'nivel_escolaridade' => 'Ensino Superior Completo',
            'experiencia' => 'Tenho experiência com alunos com necessidades especiais há mais de 5 anos.'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'token',
                'user'
            ]);

        $this->assertDatabaseHas('usuarios', [
            'email' => 'joao@example.com',
            'tipo_usuario' => 'CANDIDATO'
        ]);

        $this->assertDatabaseHas('candidatos', [
            'nome_completo' => 'João da Silva Santos',
            'cpf' => '52998224725'
        ]);
    }

    /** @test */
    public function it_can_register_instituicao()
    {
        $response = $this->postJson('/api/auth/register/instituicao', [
            'nome' => 'Escola ABC',
            'email' => 'escola@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'razao_social' => 'Escola ABC LTDA',
            'nome_fantasia' => 'Escola ABC',
            'cnpj' => '11222333000181',
            'telefone_fixo' => '1133334444',
            'celular_corporativo' => '11987654321',
            'tipo_instituicao' => 'Pública',
            'cep' => '01234567',
            'logradouro' => 'Rua Principal',
            'bairro' => 'Centro',
            'numero' => '100',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'niveis_oferecidos' => '["Ensino Fundamental","Ensino Médio"]',
            'nome_responsavel' => 'Maria Silva',
            'funcao_responsavel' => 'Diretora',
            'termos_aceite' => true
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'token',
                'user'
            ]);

        $this->assertDatabaseHas('usuarios', [
            'email' => 'escola@example.com',
            'tipo_usuario' => 'INSTITUICAO'
        ]);

        $this->assertDatabaseHas('instituicoes', [
            'razao_social' => 'Escola ABC LTDA',
            'cnpj' => '11222333000181'
        ]);
    }

    /** @test */
    public function it_validates_required_fields_on_register()
    {
        $response = $this->postJson('/api/auth/register/candidato', [
            'email' => 'invalid-email',
            'senha' => '123' // Senha muito curta
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'email', 'senha', 'cpf', 'data_nascimento', 'cep', 'telefone', 'nivel_escolaridade']);
    }

    /** @test */
    public function it_validates_email_uniqueness_on_register()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register/candidato', [
            'nome' => 'Test User',
            'email' => 'existing@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'nome_completo' => 'Test User Full',
            'cpf' => '52998224725',
            'telefone' => '11987654321',
            'data_nascimento' => '1990-05-15',
            'cep' => '01234567',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'nivel_escolaridade' => 'Ensino Superior Completo',
            'experiencia' => 'Tenho experiência com alunos com necessidades especiais há mais de 5 anos.'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_can_logout()
    {
        $user = User::factory()->create();
        $token = $this->generateToken($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logout realizado com sucesso.']);
    }

    /** @test */
    public function it_requires_authentication_to_logout()
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    /** @test */
    public function password_confirmation_must_match_on_register()
    {
        $response = $this->postJson('/api/auth/register/candidato', [
            'nome' => 'João Silva',
            'email' => 'joao@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'DifferentPassword@123',
            'nome_completo' => 'João da Silva Santos',
            'cpf' => '52998224725',
            'telefone' => '11987654321',
            'data_nascimento' => '1990-05-15',
            'cep' => '01234567',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'nivel_escolaridade' => 'Ensino Superior Completo',
            'experiencia' => 'Tenho experiência'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['senha']);
    }

    /** @test */
    public function validates_cpf_format_on_candidato_register()
    {
        $response = $this->postJson('/api/auth/register/candidato', [
            'nome' => 'João Silva',
            'email' => 'joao@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'nome_completo' => 'João da Silva Santos',
            'cpf' => '123', // CPF inválido
            'telefone' => '11987654321',
            'data_nascimento' => '1990-05-15',
            'cep' => '01234567',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'nivel_escolaridade' => 'Ensino Superior Completo',
            'experiencia' => 'Tenho experiência'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cpf']);
    }

    /** @test */
    public function validates_cnpj_format_on_instituicao_register()
    {
        $response = $this->postJson('/api/auth/register/instituicao', [
            'nome' => 'Escola ABC',
            'email' => 'escola@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'razao_social' => 'Escola ABC LTDA',
            'nome_fantasia' => 'Escola ABC',
            'cnpj' => '123', // CNPJ inválido
            'telefone_fixo' => '1133334444',
            'celular_corporativo' => '11987654321',
            'tipo_instituicao' => 'Pública',
            'cep' => '01234567',
            'logradouro' => 'Rua Principal',
            'bairro' => 'Centro',
            'numero' => '100',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'niveis_oferecidos' => '["Ensino Fundamental"]',
            'nome_responsavel' => 'Maria Silva',
            'funcao_responsavel' => 'Diretora',
            'termos_aceite' => true
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cnpj']);
    }

    /** @test */
    public function rejects_duplicate_cpf_on_candidato_register()
    {
        // Criar primeiro candidato
        $user = User::factory()->create(['tipo_usuario' => 'CANDIDATO']);
        Candidato::factory()->create([
            'id_usuario' => $user->id_usuario,
            'cpf' => '52998224725'
        ]);

        // Tentar registrar com mesmo CPF
        $response = $this->postJson('/api/auth/register/candidato', [
            'nome' => 'Outro João',
            'email' => 'outro@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'nome_completo' => 'Outro João',
            'cpf' => '52998224725', // CPF duplicado
            'telefone' => '11987654321',
            'data_nascimento' => '1990-05-15',
            'cep' => '01234567',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'nivel_escolaridade' => 'Ensino Superior Completo',
            'experiencia' => 'Tenho experiência'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cpf']);
    }

    /** @test */
    public function rejects_duplicate_cnpj_on_instituicao_register()
    {
        // Criar primeira instituição
        $user = User::factory()->create(['tipo_usuario' => 'INSTITUICAO']);
        Instituicao::factory()->create([
            'id_usuario' => $user->id_usuario,
            'cnpj' => '11222333000181'
        ]);

        // Tentar registrar com mesmo CNPJ
        $response = $this->postJson('/api/auth/register/instituicao', [
            'nome' => 'Outra Escola',
            'email' => 'outra@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'razao_social' => 'Outra Escola LTDA',
            'nome_fantasia' => 'Outra Escola',
            'cnpj' => '11222333000181', // CNPJ duplicado
            'telefone_fixo' => '1133334444',
            'celular_corporativo' => '11987654321',
            'tipo_instituicao' => 'Pública',
            'cep' => '01234567',
            'logradouro' => 'Rua Principal',
            'bairro' => 'Centro',
            'numero' => '100',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'niveis_oferecidos' => '["Ensino Fundamental"]',
            'nome_responsavel' => 'Maria Silva',
            'funcao_responsavel' => 'Diretora',
            'termos_aceite' => true
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cnpj']);
    }

    /** @test */
    public function validates_email_format()
    {
        $response = $this->postJson('/api/auth/register/candidato', [
            'nome' => 'João Silva',
            'email' => 'not-an-email', // Email inválido
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'nome_completo' => 'João da Silva Santos',
            'cpf' => '52998224725',
            'telefone' => '11987654321',
            'data_nascimento' => '1990-05-15',
            'cep' => '01234567',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'nivel_escolaridade' => 'Ensino Superior Completo',
            'experiencia' => 'Tenho experiência'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function password_must_meet_minimum_length()
    {
        $response = $this->postJson('/api/auth/register/candidato', [
            'nome' => 'João Silva',
            'email' => 'joao@example.com',
            'senha' => '123', // Senha muito curta
            'senha_confirmation' => '123',
            'nome_completo' => 'João da Silva Santos',
            'cpf' => '52998224725',
            'telefone' => '11987654321',
            'data_nascimento' => '1990-05-15',
            'cep' => '01234567',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'nivel_escolaridade' => 'Ensino Superior Completo',
            'experiencia' => 'Tenho experiência'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['senha']);
    }

    /** @test */
    public function login_is_case_sensitive_for_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'senha_hash' => Hash::make('Password123'),
            'tipo_usuario' => 'CANDIDATO'
        ]);

        Candidato::factory()->create(['id_usuario' => $user->id_usuario]);

        // Tentar login com senha em maiúsculas
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'senha' => 'PASSWORD123' // Diferente de Password123
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Credenciais inválidas.']);
    }

    /** @test */
    public function login_email_is_case_insensitive()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'senha_hash' => Hash::make('password123'),
            'tipo_usuario' => 'CANDIDATO'
        ]);

        Candidato::factory()->create(['id_usuario' => $user->id_usuario]);

        // Login com email em maiúsculas deve funcionar
        $response = $this->postJson('/api/auth/login', [
            'email' => 'TEST@EXAMPLE.COM',
            'senha' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    /** @test */
    public function cannot_login_with_empty_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => '',
            'senha' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'senha']);
    }

    /** @test */
    public function validates_telefone_format_on_candidato_register()
    {
        $response = $this->postJson('/api/auth/register/candidato', [
            'nome' => 'João Silva',
            'email' => 'joao@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'nome_completo' => 'João da Silva Santos',
            'cpf' => '52998224725',
            'telefone' => '123', // Telefone muito curto
            'data_nascimento' => '1990-05-15',
            'cep' => '01234567',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'nivel_escolaridade' => 'Ensino Superior Completo',
            'experiencia' => 'Tenho experiência'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['telefone']);
    }

    /** @test */
    public function validates_data_nascimento_on_candidato_register()
    {
        $response = $this->postJson('/api/auth/register/candidato', [
            'nome' => 'João Silva',
            'email' => 'joao@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'nome_completo' => 'João da Silva Santos',
            'cpf' => '52998224725',
            'telefone' => '11987654321',
            'data_nascimento' => 'invalid-date',
            'cep' => '01234567',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'nivel_escolaridade' => 'Ensino Superior Completo',
            'experiencia' => 'Tenho experiência'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data_nascimento']);
    }

    /** @test */
    public function termos_aceite_is_required_for_instituicao()
    {
        $response = $this->postJson('/api/auth/register/instituicao', [
            'nome' => 'Escola ABC',
            'email' => 'escola@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'razao_social' => 'Escola ABC LTDA',
            'nome_fantasia' => 'Escola ABC',
            'cnpj' => '11222333000181',
            'telefone_fixo' => '1133334444',
            'celular_corporativo' => '11987654321',
            'tipo_instituicao' => 'Pública',
            'cep' => '01234567',
            'logradouro' => 'Rua Principal',
            'bairro' => 'Centro',
            'numero' => '100',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'niveis_oferecidos' => '["Ensino Fundamental"]',
            'nome_responsavel' => 'Maria Silva',
            'funcao_responsavel' => 'Diretora',
            'termos_aceite' => false // Deve ser true
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['termos_aceite']);
    }

    /** @test */
    public function cpf_accepts_formatted_and_unformatted_values()
    {
        // Com formatação
        $response1 = $this->postJson('/api/auth/register/candidato', [
            'nome' => 'João Silva',
            'email' => 'joao1@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'nome_completo' => 'João da Silva Santos',
            'cpf' => '529.982.247-25', // CPF formatado
            'telefone' => '11987654321',
            'data_nascimento' => '1990-05-15',
            'cep' => '01234567',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'nivel_escolaridade' => 'Ensino Superior Completo',
            'experiencia' => 'Tenho experiência'
        ]);

        $response1->assertStatus(201);

        // Sem formatação
        $response2 = $this->postJson('/api/auth/register/candidato', [
            'nome' => 'Maria Silva',
            'email' => 'maria@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'nome_completo' => 'Maria da Silva Santos',
            'cpf' => '71465775030', // CPF sem formatação
            'telefone' => '11987654321',
            'data_nascimento' => '1990-05-15',
            'cep' => '01234567',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'nivel_escolaridade' => 'Ensino Superior Completo',
            'experiencia' => 'Tenho experiência'
        ]);

        $response2->assertStatus(201);
    }

    /** @test */
    public function cnpj_accepts_formatted_and_unformatted_values()
    {
        // Com formatação
        $response1 = $this->postJson('/api/auth/register/instituicao', [
            'nome' => 'Escola ABC',
            'email' => 'escola1@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'razao_social' => 'Escola ABC LTDA',
            'nome_fantasia' => 'Escola ABC',
            'cnpj' => '11.222.333/0001-81', // CNPJ formatado
            'telefone_fixo' => '1133334444',
            'celular_corporativo' => '11987654321',
            'tipo_instituicao' => 'Pública',
            'cep' => '01234567',
            'logradouro' => 'Rua Principal',
            'bairro' => 'Centro',
            'numero' => '100',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'niveis_oferecidos' => '["Ensino Fundamental"]',
            'nome_responsavel' => 'Maria Silva',
            'funcao_responsavel' => 'Diretora',
            'termos_aceite' => true
        ]);

        $response1->assertStatus(201);

        // Sem formatação
        $response2 = $this->postJson('/api/auth/register/instituicao', [
            'nome' => 'Escola XYZ',
            'email' => 'escolaxyz@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'razao_social' => 'Escola XYZ LTDA',
            'nome_fantasia' => 'Escola XYZ',
            'cnpj' => '34028316000103', // CNPJ válido sem formatação
            'telefone_fixo' => '1133334444',
            'celular_corporativo' => '11987654321',
            'tipo_instituicao' => 'Privada',
            'cep' => '01234567',
            'logradouro' => 'Rua Principal',
            'bairro' => 'Centro',
            'numero' => '100',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'niveis_oferecidos' => '["Ensino Fundamental"]',
            'nome_responsavel' => 'João Silva',
            'funcao_responsavel' => 'Diretor',
            'termos_aceite' => true
        ]);

        $response2->assertStatus(201);
    }

    /** @test */
    public function validates_estado_is_valid_uf()
    {
        $response = $this->postJson('/api/auth/register/candidato', [
            'nome' => 'João Silva',
            'email' => 'joao@example.com',
            'senha' => 'Password@123',
            'senha_confirmation' => 'Password@123',
            'nome_completo' => 'João da Silva Santos',
            'cpf' => '52998224725',
            'telefone' => '11987654321',
            'data_nascimento' => '1990-05-15',
            'cep' => '01234567',
            'cidade' => 'São Paulo',
            'estado' => 'INVALID', // UF inválida
            'nivel_escolaridade' => 'Ensino Superior Completo',
            'experiencia' => 'Tenho experiência'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['estado']);
    }

    /** @test */
    public function login_returns_user_profile_type()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'senha_hash' => Hash::make('password123'),
            'tipo_usuario' => 'CANDIDATO'
        ]);

        Candidato::factory()->create(['id_usuario' => $user->id_usuario]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'senha' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.tipo_usuario', 'CANDIDATO');
    }

    /** @test */
    public function instituicao_login_returns_correct_user_type()
    {
        $user = User::factory()->create([
            'email' => 'escola@example.com',
            'senha_hash' => Hash::make('password123'),
            'tipo_usuario' => 'INSTITUICAO'
        ]);

        Instituicao::factory()->create(['id_usuario' => $user->id_usuario]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'escola@example.com',
            'senha' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.tipo_usuario', 'INSTITUICAO');
    }

    /**
     * Helper para gerar token JWT
     */
    protected function generateToken($user)
    {
        return \App\Helpers\JwtHelper::generateToken($user);
    }
}
