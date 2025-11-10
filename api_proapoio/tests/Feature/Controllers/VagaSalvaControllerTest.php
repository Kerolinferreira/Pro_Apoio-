<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidato;
use App\Models\Instituicao;
use App\Models\Vaga;
use App\Models\VagaSalva;
use App\Models\Endereco;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VagaSalvaControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function candidato_pode_listar_vagas_salvas()
    {
        $candidato = $this->createCandidato();
        $vaga1 = $this->createVaga();
        $vaga2 = $this->createVaga();

        // Salvar vagas
        VagaSalva::create(['id_candidato' => $candidato->id, 'id_vaga' => $vaga1->id_vaga]);
        VagaSalva::create(['id_candidato' => $candidato->id, 'id_vaga' => $vaga2->id_vaga]);

        $response = $this->actingAs($candidato->user)
            ->getJson('/api/candidatos/me/vagas-salvas');

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'vaga' => [
                        'id_vaga',
                        'titulo_vaga',
                        'cidade',
                        'regime_contratacao',
                        'instituicao' => ['nome_fantasia']
                    ]
                ]
            ]);
    }

    /** @test */
    public function instituicao_nao_pode_listar_vagas_salvas()
    {
        $instituicao = $this->createInstituicao();

        $response = $this->actingAs($instituicao->user)
            ->getJson('/api/candidatos/me/vagas-salvas');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Apenas candidatos.']);
    }

    /** @test */
    public function lista_vazia_retorna_array_vazio()
    {
        $candidato = $this->createCandidato();

        $response = $this->actingAs($candidato->user)
            ->getJson('/api/candidatos/me/vagas-salvas');

        $response->assertOk()
            ->assertJsonCount(0);
    }

    /** @test */
    public function candidato_pode_salvar_vaga_ativa()
    {
        $candidato = $this->createCandidato();
        $vaga = $this->createVaga();

        $response = $this->actingAs($candidato->user)
            ->postJson("/api/vagas/{$vaga->id_vaga}/salvar");

        $response->assertCreated()
            ->assertJsonStructure(['id_candidato', 'id_vaga', 'vaga']);

        $this->assertDatabaseHas('vagas_salvas', [
            'id_candidato' => $candidato->id,
            'id_vaga' => $vaga->id_vaga,
        ]);
    }

    /** @test */
    public function salvar_vaga_novamente_nao_duplica()
    {
        $candidato = $this->createCandidato();
        $vaga = $this->createVaga();

        // Salvar primeira vez
        $this->actingAs($candidato->user)
            ->postJson("/api/vagas/{$vaga->id_vaga}/salvar");

        // Salvar segunda vez
        $this->actingAs($candidato->user)
            ->postJson("/api/vagas/{$vaga->id_vaga}/salvar");

        // Deve ter apenas 1 registro
        $count = VagaSalva::where('id_candidato', $candidato->id)
            ->where('id_vaga', $vaga->id_vaga)
            ->count();

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function nao_pode_salvar_vaga_pausada()
    {
        $candidato = $this->createCandidato();
        $vaga = $this->createVaga(['status' => 'PAUSADA']);

        $response = $this->actingAs($candidato->user)
            ->postJson("/api/vagas/{$vaga->id_vaga}/salvar");

        $response->assertNotFound();
    }

    /** @test */
    public function nao_pode_salvar_vaga_fechada()
    {
        $candidato = $this->createCandidato();
        $vaga = $this->createVaga(['status' => 'FECHADA']);

        $response = $this->actingAs($candidato->user)
            ->postJson("/api/vagas/{$vaga->id_vaga}/salvar");

        $response->assertNotFound();
    }

    /** @test */
    public function nao_pode_salvar_vaga_inexistente()
    {
        $candidato = $this->createCandidato();

        $response = $this->actingAs($candidato->user)
            ->postJson('/api/vagas/99999/salvar');

        $response->assertNotFound();
    }

    /** @test */
    public function instituicao_nao_pode_salvar_vaga()
    {
        $instituicao = $this->createInstituicao();
        $vaga = $this->createVaga();

        $response = $this->actingAs($instituicao->user)
            ->postJson("/api/vagas/{$vaga->id_vaga}/salvar");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Apenas candidatos.']);
    }

    /** @test */
    public function candidato_pode_remover_vaga_salva()
    {
        $candidato = $this->createCandidato();
        $vaga = $this->createVaga();

        VagaSalva::create([
            'id_candidato' => $candidato->id,
            'id_vaga' => $vaga->id_vaga
        ]);

        $response = $this->actingAs($candidato->user)
            ->deleteJson("/api/vagas/{$vaga->id_vaga}/remover");

        $response->assertOk()
            ->assertJson(['message' => 'Vaga removida dos favoritos.']);

        $this->assertDatabaseMissing('vagas_salvas', [
            'id_candidato' => $candidato->id,
            'id_vaga' => $vaga->id_vaga,
        ]);
    }

    /** @test */
    public function remover_vaga_nao_salva_retorna_404()
    {
        $candidato = $this->createCandidato();
        $vaga = $this->createVaga();

        $response = $this->actingAs($candidato->user)
            ->deleteJson("/api/vagas/{$vaga->id_vaga}/remover");

        $response->assertNotFound();
    }

    /** @test */
    public function candidato_nao_pode_remover_vaga_salva_de_outro()
    {
        $candidato1 = $this->createCandidato();
        $candidato2 = $this->createCandidato();
        $vaga = $this->createVaga();

        VagaSalva::create([
            'id_candidato' => $candidato1->id,
            'id_vaga' => $vaga->id_vaga
        ]);

        $response = $this->actingAs($candidato2->user)
            ->deleteJson("/api/vagas/{$vaga->id_vaga}/remover");

        $response->assertNotFound();

        // Vaga ainda deve estar salva para candidato1
        $this->assertDatabaseHas('vagas_salvas', [
            'id_candidato' => $candidato1->id,
            'id_vaga' => $vaga->id_vaga,
        ]);
    }

    /** @test */
    public function instituicao_nao_pode_remover_vaga_salva()
    {
        $candidato = $this->createCandidato();
        $instituicao = $this->createInstituicao();
        $vaga = $this->createVaga();

        VagaSalva::create([
            'id_candidato' => $candidato->id,
            'id_vaga' => $vaga->id_vaga
        ]);

        $response = $this->actingAs($instituicao->user)
            ->deleteJson("/api/vagas/{$vaga->id_vaga}/remover");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Apenas candidatos.']);
    }

    /** @test */
    public function vagas_salvas_sao_ordenadas_por_mais_recentes()
    {
        $candidato = $this->createCandidato();
        $vaga1 = $this->createVaga(['titulo_vaga' => 'Vaga Antiga']);
        $vaga2 = $this->createVaga(['titulo_vaga' => 'Vaga Recente']);

        // Salvar vaga1 primeiro
        VagaSalva::create(['id_candidato' => $candidato->id, 'id_vaga' => $vaga1->id_vaga]);
        sleep(1); // Garantir ordem diferente
        VagaSalva::create(['id_candidato' => $candidato->id, 'id_vaga' => $vaga2->id_vaga]);

        $response = $this->actingAs($candidato->user)
            ->getJson('/api/candidatos/me/vagas-salvas');

        $response->assertOk();
        $data = $response->json();

        // Mais recente deve vir primeiro
        $this->assertEquals('Vaga Recente', $data[0]['vaga']['titulo_vaga']);
        $this->assertEquals('Vaga Antiga', $data[1]['vaga']['titulo_vaga']);
    }

    // Helpers
    protected function createCandidato(array $overrides = []): Candidato
    {
        $user = User::create([
            'nome' => 'Candidato Teste',
            'email' => fake()->unique()->safeEmail(),
            'senha_hash' => bcrypt('password123'),
            'tipo_usuario' => 'CANDIDATO',
        ]);

        $endereco = Endereco::create([
            'cep' => '12345678',
            'logradouro' => 'Rua Teste',
            'numero' => '123',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
        ]);

        return Candidato::create(array_merge([
            'id_usuario' => $user->id_usuario,
            'cpf' => fake()->numerify('###########'),
            'data_nascimento' => '1995-01-01',
            'genero' => 'Masculino',
            'telefone' => '11987654321',
            'nivel_escolaridade' => 'Superior Completo',
            'nome_completo' => 'Candidato Teste',
            'id_endereco' => $endereco->id_endereco,
        ], $overrides));
    }

    protected function createInstituicao(): Instituicao
    {
        $user = User::create([
            'nome' => 'Instituição Teste',
            'email' => fake()->unique()->safeEmail(),
            'senha_hash' => bcrypt('password123'),
            'tipo_usuario' => 'INSTITUICAO',
        ]);

        $endereco = Endereco::create([
            'cep' => '12345678',
            'logradouro' => 'Rua Teste',
            'numero' => '123',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
        ]);

        return Instituicao::create([
            'id_usuario' => $user->id_usuario,
            'cnpj' => fake()->numerify('##############'),
            'razao_social' => 'Instituição Teste LTDA',
            'nome_fantasia' => 'Instituição Teste',
            'id_endereco' => $endereco->id_endereco,
        ]);
    }

    protected function createVaga(array $overrides = []): Vaga
    {
        $instituicao = $this->createInstituicao();

        return Vaga::create(array_merge([
            'id_instituicao' => $instituicao->id_instituicao,
            'titulo_vaga' => 'Vaga Teste',
            'titulo' => 'Vaga Teste',
            'status' => 'ATIVA',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'regime_contratacao' => 'CLT',
        ], $overrides));
    }
}
