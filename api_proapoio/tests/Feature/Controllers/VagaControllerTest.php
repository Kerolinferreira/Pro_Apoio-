<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Instituicao;
use App\Models\Vaga;
use App\Enums\VagaStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VagaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $instituicao;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['tipo_usuario' => 'INSTITUICAO']);
        $this->instituicao = Instituicao::factory()->create(['id_usuario' => $user->id_usuario]);
        $this->token = \App\Helpers\JwtHelper::generateToken($user);
    }

    /** @test */
    public function it_can_list_vagas_without_authentication()
    {
        Vaga::factory()->count(5)->create(['status' => VagaStatus::ATIVA->value]);

        $response = $this->getJson('/api/vagas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id_vaga', 'titulo', 'tipo', 'modalidade', 'cidade', 'estado']
                ],
                'meta'
            ]);
    }

    /** @test */
    public function it_can_filter_vagas_by_cidade()
    {
        // Limpar todas as vagas para garantir resultado preciso
        \DB::table('vagas')->delete();

        Vaga::factory()->create(['cidade' => 'São Paulo', 'status' => VagaStatus::ATIVA->value]);
        Vaga::factory()->create(['cidade' => 'Rio de Janeiro', 'status' => VagaStatus::ATIVA->value]);

        $response = $this->getJson('/api/vagas?cidade=São Paulo');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('São Paulo', $data[0]['cidade']);
    }

    /** @test */
    public function it_can_filter_vagas_by_estado()
    {
        // Limpar todas as vagas para garantir resultado preciso
        \DB::table('vagas')->delete();

        Vaga::factory()->create(['estado' => 'SP', 'status' => VagaStatus::ATIVA->value]);
        Vaga::factory()->create(['estado' => 'RJ', 'status' => VagaStatus::ATIVA->value]);

        $response = $this->getJson('/api/vagas?estado=SP');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('SP', $data[0]['estado']);
    }

    /** @test */
    public function it_can_filter_vagas_by_tipo()
    {
        // Limpar todas as vagas para garantir resultado preciso
        \DB::table('vagas')->delete();

        Vaga::factory()->create(['tipo' => 'Estágio', 'status' => VagaStatus::ATIVA->value]);
        Vaga::factory()->create(['tipo' => 'CLT', 'status' => VagaStatus::ATIVA->value]);

        $response = $this->getJson('/api/vagas?tipo[]=Estágio');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('Estágio', $data[0]['tipo']);
    }

    /** @test */
    public function it_can_show_single_vaga()
    {
        $vaga = Vaga::factory()->create([
            'titulo' => 'Auxiliar de Sala',
            'status' => VagaStatus::ATIVA->value
        ]);

        $response = $this->getJson("/api/vagas/{$vaga->id_vaga}");

        $response->assertStatus(200)
            ->assertJson([
                'id_vaga' => $vaga->id_vaga,
                'titulo' => 'Auxiliar de Sala'
            ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_vaga()
    {
        $response = $this->getJson('/api/vagas/999999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_create_vaga_as_instituicao()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/vagas', [
            'titulo' => 'Nova Vaga de Apoio',
            'descricao' => 'Descrição da vaga',
            'tipo' => 'Estágio',
            'modalidade' => 'Presencial',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'remuneracao' => 1500.00
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id_vaga', 'titulo', 'descricao']);

        $this->assertDatabaseHas('vagas', [
            'titulo' => 'Nova Vaga de Apoio',
            'id_instituicao' => $this->instituicao->id_instituicao,
            'status' => VagaStatus::ATIVA->value
        ]);
    }

    /** @test */
    public function it_requires_authentication_to_create_vaga()
    {
        $response = $this->postJson('/api/vagas', [
            'titulo' => 'Nova Vaga'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/vagas', []);

        // Apenas titulo/titulo_vaga é obrigatório (validação customizada)
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['titulo']);
    }

    /** @test */
    public function it_can_update_vaga_as_owner()
    {
        $vaga = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao,
            'titulo' => 'Título Original'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson("/api/vagas/{$vaga->id_vaga}", [
            'titulo' => 'Título Atualizado',
            'descricao' => 'Nova descrição',
            'tipo' => 'CLT',
            'modalidade' => 'Remoto',
            'cidade' => 'Rio de Janeiro',
            'estado' => 'RJ'
        ]);

        $response->assertStatus(200)
            ->assertJson(['titulo' => 'Título Atualizado']);

        $this->assertDatabaseHas('vagas', [
            'id_vaga' => $vaga->id_vaga,
            'titulo' => 'Título Atualizado'
        ]);
    }

    /** @test */
    public function it_can_delete_vaga_as_owner()
    {
        $vaga = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson("/api/vagas/{$vaga->id_vaga}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Vaga removida com sucesso.']);

        $this->assertSoftDeleted('vagas', [
            'id_vaga' => $vaga->id_vaga
        ]);
    }

    /** @test */
    public function it_can_change_vaga_status()
    {
        $vaga = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao,
            'status' => VagaStatus::ATIVA->value
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->patchJson("/api/vagas/{$vaga->id_vaga}/status", [
            'status' => VagaStatus::PAUSADA->value
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('vagas', [
            'id_vaga' => $vaga->id_vaga,
            'status' => VagaStatus::PAUSADA->value
        ]);
    }

    /** @test */
    public function only_ativas_vagas_are_returned_in_public_list()
    {
        Vaga::factory()->create(['status' => VagaStatus::ATIVA->value]);
        Vaga::factory()->create(['status' => VagaStatus::PAUSADA->value]);
        Vaga::factory()->create(['status' => VagaStatus::FECHADA->value]);

        $response = $this->getJson('/api/vagas');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
    }

    /** @test */
    public function soft_deleted_vagas_are_not_shown_in_list()
    {
        $vaga = Vaga::factory()->create(['status' => VagaStatus::ATIVA->value]);
        $vaga->delete(); // Soft delete

        $response = $this->getJson('/api/vagas');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Vaga deletada não deve aparecer
        $ids = collect($data)->pluck('id_vaga')->toArray();
        $this->assertNotContains($vaga->id_vaga, $ids);
    }

    /** @test */
    public function soft_deleted_vaga_returns_404_on_show()
    {
        $vaga = Vaga::factory()->create(['status' => VagaStatus::ATIVA->value]);
        $vagaId = $vaga->id_vaga;
        $vaga->delete();

        $response = $this->getJson("/api/vagas/{$vagaId}");

        $response->assertStatus(404);
    }

    /** @test */
    public function delete_uses_soft_delete_not_hard_delete()
    {
        $vaga = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson("/api/vagas/{$vaga->id_vaga}");

        // Vaga não deve existir sem trashed
        $this->assertDatabaseMissing('vagas', [
            'id_vaga' => $vaga->id_vaga,
            'deleted_at' => null
        ]);

        // Mas deve existir com trashed
        $this->assertDatabaseHas('vagas', [
            'id_vaga' => $vaga->id_vaga
        ]);

        // Verificar que deleted_at foi preenchido
        $deletedVaga = Vaga::withTrashed()->find($vaga->id_vaga);
        $this->assertNotNull($deletedVaga->deleted_at);
    }

    /** @test */
    public function unauthorized_user_cannot_update_vaga()
    {
        $otherUser = User::factory()->create(['tipo_usuario' => 'INSTITUICAO']);
        $otherInstituicao = Instituicao::factory()->create(['id_usuario' => $otherUser->id_usuario]);
        $otherToken = \App\Helpers\JwtHelper::generateToken($otherUser);

        $vaga = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $otherToken
        ])->putJson("/api/vagas/{$vaga->id_vaga}", [
            'titulo' => 'Tentativa de atualização não autorizada',
            'descricao' => 'Teste',
            'tipo' => 'CLT',
            'modalidade' => 'Remoto'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthorized_user_cannot_delete_vaga()
    {
        $otherUser = User::factory()->create(['tipo_usuario' => 'INSTITUICAO']);
        $otherInstituicao = Instituicao::factory()->create(['id_usuario' => $otherUser->id_usuario]);
        $otherToken = \App\Helpers\JwtHelper::generateToken($otherUser);

        $vaga = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $otherToken
        ])->deleteJson("/api/vagas/{$vaga->id_vaga}");

        $response->assertStatus(403);

        // Vaga ainda deve existir
        $this->assertDatabaseHas('vagas', [
            'id_vaga' => $vaga->id_vaga
        ]);
    }

    /** @test */
    public function candidato_cannot_update_vaga()
    {
        $candidatoUser = User::factory()->create(['tipo_usuario' => 'CANDIDATO']);
        $candidato = \App\Models\Candidato::factory()->create(['id_usuario' => $candidatoUser->id_usuario]);
        $candidatoToken = \App\Helpers\JwtHelper::generateToken($candidatoUser);

        $vaga = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $candidatoToken
        ])->putJson("/api/vagas/{$vaga->id_vaga}", [
            'titulo' => 'Tentativa de candidato',
            'descricao' => 'Teste',
            'tipo' => 'CLT',
            'modalidade' => 'Remoto'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function candidato_cannot_delete_vaga()
    {
        $candidatoUser = User::factory()->create(['tipo_usuario' => 'CANDIDATO']);
        $candidato = \App\Models\Candidato::factory()->create(['id_usuario' => $candidatoUser->id_usuario]);
        $candidatoToken = \App\Helpers\JwtHelper::generateToken($candidatoUser);

        $vaga = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $candidatoToken
        ])->deleteJson("/api/vagas/{$vaga->id_vaga}");

        $response->assertStatus(403);
    }

    /** @test */
    public function list_supports_pagination()
    {
        \DB::table('vagas')->delete();

        Vaga::factory()->count(25)->create(['status' => VagaStatus::ATIVA->value]);

        $response = $this->getJson('/api/vagas?per_page=10&page=1');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(10, $data);
        $this->assertArrayHasKey('meta', $response->json());
        $this->assertEquals(25, $response->json('meta.total'));
    }

    /** @test */
    public function can_filter_by_multiple_tipos()
    {
        \DB::table('vagas')->delete();

        Vaga::factory()->create(['tipo' => 'Estágio', 'status' => VagaStatus::ATIVA->value]);
        Vaga::factory()->create(['tipo' => 'CLT', 'status' => VagaStatus::ATIVA->value]);
        Vaga::factory()->create(['tipo' => 'PJ', 'status' => VagaStatus::ATIVA->value]);
        Vaga::factory()->create(['tipo' => 'Temporário', 'status' => VagaStatus::ATIVA->value]);

        $response = $this->getJson('/api/vagas?tipo[]=Estágio&tipo[]=CLT');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data);
        $tipos = collect($data)->pluck('tipo')->toArray();
        $this->assertContains('Estágio', $tipos);
        $this->assertContains('CLT', $tipos);
    }

    /** @test */
    public function empty_filter_returns_all_ativas()
    {
        \DB::table('vagas')->delete();

        Vaga::factory()->count(5)->create(['status' => VagaStatus::ATIVA->value]);

        $response = $this->getJson('/api/vagas?cidade=&estado=&tipo=');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(5, $data);
    }

    /** @test */
    public function filter_with_no_results_returns_empty_array()
    {
        \DB::table('vagas')->delete();

        Vaga::factory()->create(['cidade' => 'São Paulo', 'status' => VagaStatus::ATIVA->value]);

        $response = $this->getJson('/api/vagas?cidade=Cidade Inexistente');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(0, $data);
    }

    /** @test */
    public function validates_status_enum_on_status_change()
    {
        $vaga = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao,
            'status' => VagaStatus::ATIVA->value
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->patchJson("/api/vagas/{$vaga->id_vaga}/status", [
            'status' => 'INVALIDO'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function titulo_is_required_on_create()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/vagas', [
            'descricao' => 'Descrição',
            'tipo' => 'Estágio',
            'modalidade' => 'Presencial'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['titulo']);
    }

    /** @test */
    public function can_filter_by_combined_estado_and_cidade()
    {
        \DB::table('vagas')->delete();

        Vaga::factory()->create(['estado' => 'SP', 'cidade' => 'São Paulo', 'status' => VagaStatus::ATIVA->value]);
        Vaga::factory()->create(['estado' => 'SP', 'cidade' => 'Campinas', 'status' => VagaStatus::ATIVA->value]);
        Vaga::factory()->create(['estado' => 'RJ', 'cidade' => 'Rio de Janeiro', 'status' => VagaStatus::ATIVA->value]);

        $response = $this->getJson('/api/vagas?estado=SP&cidade=São Paulo');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('São Paulo', $data[0]['cidade']);
        $this->assertEquals('SP', $data[0]['estado']);
    }

    /** @test */
    public function vaga_includes_instituicao_data_on_show()
    {
        $vaga = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao,
            'status' => VagaStatus::ATIVA->value
        ]);

        $response = $this->getJson("/api/vagas/{$vaga->id_vaga}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id_vaga',
                'titulo',
                'instituicao' => ['id_instituicao', 'razao_social']
            ]);
    }

    /** @test */
    public function cannot_update_non_existent_vaga()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/vagas/999999', [
            'titulo' => 'Teste',
            'descricao' => 'Teste',
            'tipo' => 'CLT',
            'modalidade' => 'Remoto'
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function cannot_delete_non_existent_vaga()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson('/api/vagas/999999');

        $response->assertStatus(404);
    }

    /** @test */
    public function remuneracao_is_stored_as_float()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/vagas', [
            'titulo' => 'Vaga com Remuneração',
            'descricao' => 'Teste',
            'tipo' => 'CLT',
            'modalidade' => 'Presencial',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'remuneracao' => '2500.50'
        ]);

        $response->assertStatus(201);
        $vagaId = $response->json('id_vaga');

        $vaga = Vaga::find($vagaId);
        $this->assertIsFloat($vaga->remuneracao);
        $this->assertEquals(2500.50, $vaga->remuneracao);
    }
}
