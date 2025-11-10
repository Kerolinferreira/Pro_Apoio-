<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Instituicao;
use App\Models\Endereco;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstituicaoProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $instituicao;
    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'tipo_usuario' => 'INSTITUICAO',
            'senha_hash' => Hash::make('password123')
        ]);
        $this->instituicao = Instituicao::factory()->create(['id_usuario' => $this->user->id_usuario]);
        $this->token = \App\Helpers\JwtHelper::generateToken($this->user);
    }

    /** @test */
    public function it_requires_authentication_to_access_profile()
    {
        $response = $this->getJson('/api/instituicao/profile');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_show_instituicao_profile()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/instituicao/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id_instituicao',
                'id_usuario',
                'razao_social'
            ]);
    }

    /** @test */
    public function it_loads_profile_with_endereco_relationship()
    {
        $endereco = Endereco::factory()->create();
        $this->instituicao->update(['id_endereco' => $endereco->id_endereco]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/instituicao/profile');

        $response->assertStatus(200)
            ->assertJsonStructure(['endereco']);
    }

    /** @test */
    public function it_can_update_instituicao_basic_info()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/instituicao/profile', [
            'razao_social' => 'Nova Razão Social LTDA',
            'nome_fantasia' => 'Escola Nova',
            'tipo_instituicao' => 'Privada',
            'codigo_inep' => '12345678'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('instituicoes', [
            'id_instituicao' => $this->instituicao->id_instituicao,
            'razao_social' => 'Nova Razão Social LTDA',
            'nome_fantasia' => 'Escola Nova'
        ]);
    }

    /** @test */
    public function it_normalizes_cnpj_telefone_and_cep()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/instituicao/profile', [
            'cnpj' => '11.222.333/0001-81',
            'telefone_fixo' => '(11) 3333-4444',
            'celular_corporativo' => '(11) 98765-4321',
            'cep' => '01234-567',
            'current_password' => 'password123'
        ]);

        $response->assertStatus(200);

        $this->instituicao->refresh();
        $this->assertEquals('11222333000181', $this->instituicao->cnpj);
        $this->assertEquals('1133334444', $this->instituicao->telefone_fixo);
        $this->assertEquals('11987654321', $this->instituicao->celular_corporativo);
    }

    /** @test */
    public function it_requires_current_password_to_change_cnpj()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/instituicao/profile', [
            'cnpj' => '11222333000181'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    /** @test */
    public function it_requires_current_password_to_change_email_corporativo()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/instituicao/profile', [
            'email_corporativo' => 'novo@escola.com'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    /** @test */
    public function it_validates_current_password_when_changing_sensitive_data()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/instituicao/profile', [
            'cnpj' => '11222333000181',
            'current_password' => 'wrongpassword'
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Senha incorreta']);
    }

    /** @test */
    public function it_can_update_responsible_person_data()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/instituicao/profile', [
            'nome_responsavel' => 'Maria Silva',
            'funcao_responsavel' => 'Diretora Pedagógica'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('instituicoes', [
            'id_instituicao' => $this->instituicao->id_instituicao,
            'nome_responsavel' => 'Maria Silva',
            'funcao_responsavel' => 'Diretora Pedagógica'
        ]);
    }

    /** @test */
    public function it_can_update_niveis_oferecidos_as_array()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/instituicao/profile', [
            'niveis_oferecidos' => ['Ensino Fundamental', 'Ensino Médio']
        ]);

        $response->assertStatus(200);

        $this->instituicao->refresh();
        $this->assertIsArray($this->instituicao->niveis_oferecidos);
    }

    /** @test */
    public function it_can_update_niveis_oferecidos_as_json_string()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/instituicao/profile', [
            'niveis_oferecidos' => '["Ensino Fundamental","Ensino Médio"]'
        ]);

        $response->assertStatus(200);

        $this->instituicao->refresh();
        $this->assertIsArray($this->instituicao->niveis_oferecidos);
    }

    /** @test */
    public function it_can_create_endereco_when_updating_profile()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/instituicao/profile', [
            'cep' => '01234567',
            'logradouro' => 'Avenida Principal',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'numero' => '1000'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('enderecos', [
            'cep' => '01234567',
            'logradouro' => 'Avenida Principal',
            'cidade' => 'São Paulo'
        ]);
    }

    /** @test */
    public function it_can_update_existing_endereco()
    {
        $endereco = Endereco::factory()->create(['cidade' => 'Rio de Janeiro']);
        $this->instituicao->update(['id_endereco' => $endereco->id_endereco]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/instituicao/profile', [
            'cidade' => 'São Paulo'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('enderecos', [
            'id_endereco' => $endereco->id_endereco,
            'cidade' => 'São Paulo'
        ]);
    }

    /** @test */
    public function it_can_upload_logo()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/instituicao/profile/logo', [
            'logo' => $file
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['logo_url']);

        Storage::disk('public')->assertExists($response->json('logo_url'));
    }

    /** @test */
    public function it_validates_logo_upload_requirements()
    {
        Storage::fake('public');

        // Arquivo muito grande (> 2MB)
        $file = UploadedFile::fake()->create('logo.png', 3000);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/instituicao/profile/logo', [
            'logo' => $file
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_non_image_files_for_logo()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/instituicao/profile/logo', [
            'logo' => $file
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_deletes_old_logo_when_uploading_new_one()
    {
        Storage::fake('public');

        // Criar logo inicial
        $oldFile = UploadedFile::fake()->image('old_logo.png', 200, 200);
        $oldPath = $oldFile->storeAs('logos-instituicoes', 'old_logo.png', 'public');
        $this->instituicao->update(['logo_url' => $oldPath]);

        // Upload novo logo
        $newFile = UploadedFile::fake()->image('new_logo.png', 200, 200);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/instituicao/profile/logo', [
            'logo' => $newFile
        ]);

        $response->assertStatus(200);

        // Verificar que o logo antigo foi deletado
        Storage::disk('public')->assertMissing($oldPath);
    }

    /** @test */
    public function it_can_change_password()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/instituicao/profile/senha', [
            'current_password' => 'password123',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123'
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Senha atualizada com sucesso.']);

        $this->user->refresh();
        $this->assertTrue(Hash::check('NewPass123', $this->user->senha_hash));
    }

    /** @test */
    public function it_validates_current_password_when_changing()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/instituicao/profile/senha', [
            'current_password' => 'wrongpassword',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123'
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Senha atual incorreta.']);
    }

    /** @test */
    public function it_validates_password_requirements()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/instituicao/profile/senha', [
            'current_password' => 'password123',
            'password' => '123',
            'password_confirmation' => '123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_requires_password_confirmation()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/instituicao/profile/senha', [
            'current_password' => 'password123',
            'password' => 'NewPass123',
            'password_confirmation' => 'DifferentPass123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function public_endpoint_shows_instituicao_without_sensitive_data()
    {
        $endereco = Endereco::factory()->create([
            'cidade' => 'São Paulo',
            'estado' => 'SP'
        ]);
        $this->instituicao->update([
            'id_endereco' => $endereco->id_endereco,
            'razao_social' => 'Escola ABC LTDA',
            'nome_fantasia' => 'Escola ABC',
            'codigo_inep' => '12345678'
        ]);

        $response = $this->getJson("/api/instituicoes/{$this->instituicao->id_instituicao}");

        $response->assertStatus(200)
            ->assertJson([
                'razao_social' => 'Escola ABC LTDA',
                'nome_fantasia' => 'Escola ABC',
                'codigo_inep' => '12345678',
                'cidade' => 'São Paulo',
                'estado' => 'SP'
            ]);

        // Não deve conter dados sensíveis
        $data = $response->json();
        $this->assertArrayNotHasKey('cnpj', $data);
        $this->assertArrayNotHasKey('email_corporativo', $data);
        $this->assertArrayNotHasKey('telefone_fixo', $data);
    }

    /** @test */
    public function public_endpoint_returns_404_for_non_existent_instituicao()
    {
        $response = $this->getJson('/api/instituicoes/999999');
        $response->assertStatus(404);
    }

    /** @test */
    public function public_endpoint_does_not_require_authentication()
    {
        $response = $this->getJson("/api/instituicoes/{$this->instituicao->id_instituicao}");
        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_delete_account()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson('/api/instituicao/profile', [
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Conta removida com sucesso.']);

        $this->assertSoftDeleted('usuarios', [
            'id_usuario' => $this->user->id_usuario
        ]);
    }

    /** @test */
    public function it_validates_password_when_deleting_account()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson('/api/instituicao/profile', [
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Senha inválida.']);

        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $this->user->id_usuario
        ]);
    }

    /** @test */
    public function it_requires_password_to_delete_account()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson('/api/instituicao/profile', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
