<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Candidato;
use App\Models\Endereco;
use App\Models\ExperienciaProfissional;
use App\Models\ExperienciaPessoal;
use App\Models\Deficiencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidatoProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $candidato;
    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'tipo_usuario' => 'CANDIDATO',
            'senha_hash' => Hash::make('password123')
        ]);
        $this->candidato = Candidato::factory()->create(['id_usuario' => $this->user->id_usuario]);
        $this->token = \App\Helpers\JwtHelper::generateToken($this->user);
    }

    /** @test */
    public function it_requires_authentication_to_access_profile()
    {
        $response = $this->getJson('/api/candidato/profile');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_show_candidato_profile()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/candidato/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id_candidato',
                'id_usuario',
                'nome_completo'
            ]);
    }

    /** @test */
    public function it_loads_profile_with_relationships()
    {
        $endereco = Endereco::factory()->create();
        $this->candidato->update(['id_endereco' => $endereco->id_endereco]);

        ExperienciaProfissional::factory()->create(['id_candidato' => $this->candidato->id_candidato]);
        ExperienciaPessoal::factory()->create(['id_candidato' => $this->candidato->id_candidato]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/candidato/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'endereco',
                'experiencias_profissionais',
                'experiencias_pessoais'
            ]);
    }

    /** @test */
    public function it_can_update_candidato_basic_info()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/candidato/profile', [
            'link_perfil' => 'https://linkedin.com/in/joao',
            'nivel_escolaridade' => 'Ensino Superior Completo',
            'curso_superior' => 'Pedagogia',
            'instituicao_ensino' => 'USP'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('candidatos', [
            'id_candidato' => $this->candidato->id_candidato,
            'nivel_escolaridade' => 'Ensino Superior Completo',
            'curso_superior' => 'Pedagogia'
        ]);
    }

    /** @test */
    public function it_normalizes_telefone_and_cpf()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/candidato/profile', [
            'telefone' => '(11) 98765-4321',
            'cpf' => '529.982.247-25',
            'current_password' => 'password123'
        ]);

        $response->assertStatus(200);

        $this->candidato->refresh();
        $this->assertEquals('11987654321', $this->candidato->telefone);
        $this->assertEquals('52998224725', $this->candidato->cpf);
    }

    /** @test */
    public function it_requires_current_password_to_change_sensitive_data()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/candidato/profile', [
            'cpf' => '52998224725'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    /** @test */
    public function it_validates_current_password_when_changing_sensitive_data()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/candidato/profile', [
            'cpf' => '52998224725',
            'current_password' => 'wrongpassword'
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Senha incorreta']);
    }

    /** @test */
    public function it_can_create_endereco_when_updating_profile()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/candidato/profile', [
            'cep' => '01234567',
            'logradouro' => 'Rua Teste',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'numero' => '123'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('enderecos', [
            'cep' => '01234567',
            'logradouro' => 'Rua Teste',
            'cidade' => 'São Paulo'
        ]);
    }

    /** @test */
    public function it_can_update_existing_endereco()
    {
        $endereco = Endereco::factory()->create(['cidade' => 'Rio de Janeiro']);
        $this->candidato->update(['id_endereco' => $endereco->id_endereco]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/candidato/profile', [
            'cidade' => 'São Paulo'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('enderecos', [
            'id_endereco' => $endereco->id_endereco,
            'cidade' => 'São Paulo'
        ]);
    }

    /** @test */
    public function it_can_upload_profile_photo()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('profile.jpg', 200, 200);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/candidato/profile/foto', [
            'foto' => $file
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['foto_url']);

        Storage::disk('public')->assertExists($response->json('foto_url'));
    }

    /** @test */
    public function it_validates_photo_upload_requirements()
    {
        Storage::fake('public');

        // Arquivo muito grande (> 2MB)
        $file = UploadedFile::fake()->create('profile.jpg', 3000);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/candidato/profile/foto', [
            'foto' => $file
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_non_image_files()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/candidato/profile/foto', [
            'foto' => $file
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_create_experiencia_profissional()
    {
        $deficiencia = Deficiencia::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/candidato/profile/experiencias-profissionais', [
            'idade_aluno' => 10,
            'tempo_experiencia' => '2 anos',
            'candidatar_mesma_deficiencia' => true,
            'comentario' => 'Experiência com crianças',
            'deficiencia_ids' => [$deficiencia->id_deficiencia]
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('experiencias_profissionais', [
            'id_candidato' => $this->candidato->id_candidato,
            'idade_aluno' => 10,
            'tempo_experiencia' => '2 anos'
        ]);
    }

    /** @test */
    public function it_can_create_multiple_experiencias_profissionais()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/candidato/profile/experiencias-profissionais', [
            'experiencias' => [
                [
                    'idade_aluno' => 10,
                    'tempo_experiencia' => '2 anos',
                    'comentario' => 'Primeira experiência'
                ],
                [
                    'idade_aluno' => 15,
                    'tempo_experiencia' => '1 ano',
                    'comentario' => 'Segunda experiência'
                ]
            ]
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('experiencias_profissionais', [
            'id_candidato' => $this->candidato->id_candidato,
            'idade_aluno' => 10
        ]);

        $this->assertDatabaseHas('experiencias_profissionais', [
            'id_candidato' => $this->candidato->id_candidato,
            'idade_aluno' => 15
        ]);
    }

    /** @test */
    public function it_strips_html_from_experiencia_comentario()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/candidato/profile/experiencias-profissionais', [
            'idade_aluno' => 10,
            'comentario' => '<script>alert("xss")</script>Comentário limpo'
        ]);

        $response->assertStatus(201);

        $experiencia = ExperienciaProfissional::latest()->first();
        $this->assertEquals('Comentário limpo', $experiencia->descricao);
    }

    /** @test */
    public function it_can_attach_deficiencias_to_experiencia_profissional()
    {
        $deficiencias = Deficiencia::factory()->count(2)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/candidato/profile/experiencias-profissionais', [
            'idade_aluno' => 10,
            'deficiencia_ids' => $deficiencias->pluck('id_deficiencia')->toArray()
        ]);

        $response->assertStatus(201);

        $experiencia = ExperienciaProfissional::latest()->first();
        $this->assertCount(2, $experiencia->deficiencias);
    }

    /** @test */
    public function it_can_delete_experiencia_profissional()
    {
        $experiencia = ExperienciaProfissional::factory()->create([
            'id_candidato' => $this->candidato->id_candidato
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson("/api/candidato/profile/experiencias-profissionais/{$experiencia->id_experiencia_profissional}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Experiência profissional removida.']);

        $this->assertDatabaseMissing('experiencias_profissionais', [
            'id_experiencia_profissional' => $experiencia->id_experiencia_profissional
        ]);
    }

    /** @test */
    public function candidato_cannot_delete_another_candidato_experiencia()
    {
        $otherCandidato = Candidato::factory()->create();
        $experiencia = ExperienciaProfissional::factory()->create([
            'id_candidato' => $otherCandidato->id_candidato
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson("/api/candidato/profile/experiencias-profissionais/{$experiencia->id_experiencia_profissional}");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_create_experiencia_pessoal()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/candidato/profile/experiencias-pessoais', [
            'interesse_atuar' => true,
            'descricao' => 'Tenho um familiar com deficiência'
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('experiencias_pessoais', [
            'id_candidato' => $this->candidato->id_candidato,
            'interesse_atuar' => true
        ]);
    }

    /** @test */
    public function it_strips_html_from_experiencia_pessoal_descricao()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/candidato/profile/experiencias-pessoais', [
            'descricao' => '<b>Bold</b> text'
        ]);

        $response->assertStatus(201);

        $experiencia = ExperienciaPessoal::latest()->first();
        $this->assertEquals('Bold text', $experiencia->descricao);
    }

    /** @test */
    public function it_can_delete_experiencia_pessoal()
    {
        $experiencia = ExperienciaPessoal::factory()->create([
            'id_candidato' => $this->candidato->id_candidato
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson("/api/candidato/profile/experiencias-pessoais/{$experiencia->id_experiencia_pessoal}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Experiência pessoal removida.']);

        $this->assertDatabaseMissing('experiencias_pessoais', [
            'id_experiencia_pessoal' => $experiencia->id_experiencia_pessoal
        ]);
    }

    /** @test */
    public function candidato_cannot_delete_another_candidato_experiencia_pessoal()
    {
        $otherCandidato = Candidato::factory()->create();
        $experiencia = ExperienciaPessoal::factory()->create([
            'id_candidato' => $otherCandidato->id_candidato
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson("/api/candidato/profile/experiencias-pessoais/{$experiencia->id_experiencia_pessoal}");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_change_password()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/candidato/profile/senha', [
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
        ])->putJson('/api/candidato/profile/senha', [
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
        ])->putJson('/api/candidato/profile/senha', [
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
        ])->putJson('/api/candidato/profile/senha', [
            'current_password' => 'password123',
            'password' => 'NewPass123',
            'password_confirmation' => 'DifferentPass123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_can_delete_account()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson('/api/candidato/profile', [
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
        ])->deleteJson('/api/candidato/profile', [
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
        ])->deleteJson('/api/candidato/profile', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_can_update_nome_completo_and_personal_data()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/candidato/profile', [
            'nome_completo' => 'João da Silva Santos',
            'data_nascimento' => '1990-05-15',
            'genero' => 'Masculino'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('candidatos', [
            'id_candidato' => $this->candidato->id_candidato,
            'nome_completo' => 'João da Silva Santos',
            'data_nascimento' => '1990-05-15',
            'genero' => 'Masculino'
        ]);
    }

    /** @test */
    public function it_validates_data_nascimento_format()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/candidato/profile', [
            'data_nascimento' => 'invalid-date'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data_nascimento']);
    }

    /** @test */
    public function it_can_sync_deficiencias_atuadas()
    {
        // Criar deficiências de teste
        $deficiencia1 = \App\Models\Deficiencia::factory()->create();
        $deficiencia2 = \App\Models\Deficiencia::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/candidato/profile', [
            'deficiencias_atuadas' => [$deficiencia1->id_deficiencia, $deficiencia2->id_deficiencia]
        ]);

        $response->assertStatus(200);

        // Verificar relacionamento many-to-many
        $this->assertDatabaseHas('candidato_deficiencia', [
            'id_candidato' => $this->candidato->id_candidato,
            'id_deficiencia' => $deficiencia1->id_deficiencia
        ]);

        $this->assertDatabaseHas('candidato_deficiencia', [
            'id_candidato' => $this->candidato->id_candidato,
            'id_deficiencia' => $deficiencia2->id_deficiencia
        ]);
    }

    /** @test */
    public function it_validates_deficiencias_atuadas_must_exist()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/candidato/profile', [
            'deficiencias_atuadas' => [999999] // ID que não existe
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['deficiencias_atuadas.0']);
    }
}
