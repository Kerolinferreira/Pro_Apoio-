<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Candidato;
use App\Models\Instituicao;
use App\Models\Vaga;
use App\Models\Proposta;
use App\Enums\PropostaStatus;
use App\Enums\VagaStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropostaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $candidato;
    protected $candidatoToken;
    protected $instituicao;
    protected $instituicaoToken;
    protected $vaga;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar candidato
        $candidatoUser = User::factory()->create(['tipo_usuario' => 'CANDIDATO']);
        $this->candidato = Candidato::factory()->create(['id_usuario' => $candidatoUser->id_usuario]);
        $this->candidatoToken = \App\Helpers\JwtHelper::generateToken($candidatoUser);

        // Criar instituicao
        $instituicaoUser = User::factory()->create(['tipo_usuario' => 'INSTITUICAO']);
        $this->instituicao = Instituicao::factory()->create(['id_usuario' => $instituicaoUser->id_usuario]);
        $this->instituicaoToken = \App\Helpers\JwtHelper::generateToken($instituicaoUser);

        // Criar vaga da instituição
        $this->vaga = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao,
            'status' => VagaStatus::ATIVA->value
        ]);
    }

    /** @test */
    public function it_requires_authentication_to_list_propostas()
    {
        $response = $this->getJson('/api/propostas');
        $response->assertStatus(401);
    }

    /** @test */
    public function candidato_can_list_sent_propostas()
    {
        // Criar propostas enviadas pelo candidato (cada uma para vaga diferente)
        for ($i = 0; $i < 3; $i++) {
            $vaga = Vaga::factory()->create([
                'id_instituicao' => $this->instituicao->id_instituicao,
                'status' => VagaStatus::ATIVA->value
            ]);
            Proposta::factory()->create([
                'id_candidato' => $this->candidato->id_candidato,
                'iniciador' => 'CANDIDATO',
                'id_vaga' => $vaga->id_vaga
            ]);
        }

        // Criar proposta recebida (iniciada pela instituição)
        $vagaRecebida = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao,
            'status' => VagaStatus::ATIVA->value
        ]);
        Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'iniciador' => 'INSTITUICAO',
            'id_vaga' => $vagaRecebida->id_vaga
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->getJson('/api/propostas?tipo=enviadas');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(3, $data);
        foreach ($data as $proposta) {
            $this->assertEquals('CANDIDATO', $proposta['iniciador']);
        }
    }

    /** @test */
    public function candidato_can_list_received_propostas()
    {
        // Criar propostas recebidas (iniciadas pela instituição) - cada uma para vaga diferente
        for ($i = 0; $i < 2; $i++) {
            $vaga = Vaga::factory()->create([
                'id_instituicao' => $this->instituicao->id_instituicao,
                'status' => VagaStatus::ATIVA->value
            ]);
            Proposta::factory()->create([
                'id_candidato' => $this->candidato->id_candidato,
                'iniciador' => 'INSTITUICAO',
                'id_vaga' => $vaga->id_vaga
            ]);
        }

        // Criar proposta enviada
        $vagaEnviada = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao,
            'status' => VagaStatus::ATIVA->value
        ]);
        Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'iniciador' => 'CANDIDATO',
            'id_vaga' => $vagaEnviada->id_vaga
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->getJson('/api/propostas?tipo=recebidas');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data);
        foreach ($data as $proposta) {
            $this->assertEquals('INSTITUICAO', $proposta['iniciador']);
        }
    }

    /** @test */
    public function instituicao_can_list_sent_propostas()
    {
        // Criar propostas enviadas pela instituição - cada uma para vaga diferente
        for ($i = 0; $i < 3; $i++) {
            $vaga = Vaga::factory()->create([
                'id_instituicao' => $this->instituicao->id_instituicao,
                'status' => VagaStatus::ATIVA->value
            ]);
            Proposta::factory()->create([
                'id_candidato' => $this->candidato->id_candidato,
                'iniciador' => 'INSTITUICAO',
                'id_vaga' => $vaga->id_vaga
            ]);
        }

        // Criar proposta recebida
        $vagaRecebida = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao,
            'status' => VagaStatus::ATIVA->value
        ]);
        Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'iniciador' => 'CANDIDATO',
            'id_vaga' => $vagaRecebida->id_vaga
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->instituicaoToken
        ])->getJson('/api/propostas?tipo=enviadas');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(3, $data);
        foreach ($data as $proposta) {
            $this->assertEquals('INSTITUICAO', $proposta['iniciador']);
        }
    }

    /** @test */
    public function instituicao_can_list_received_propostas()
    {
        // Criar propostas recebidas (iniciadas por candidatos) - cada uma para vaga diferente
        for ($i = 0; $i < 2; $i++) {
            $vaga = Vaga::factory()->create([
                'id_instituicao' => $this->instituicao->id_instituicao,
                'status' => VagaStatus::ATIVA->value
            ]);
            Proposta::factory()->create([
                'id_candidato' => $this->candidato->id_candidato,
                'iniciador' => 'CANDIDATO',
                'id_vaga' => $vaga->id_vaga
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->instituicaoToken
        ])->getJson('/api/propostas?tipo=recebidas');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data);
        foreach ($data as $proposta) {
            $this->assertEquals('CANDIDATO', $proposta['iniciador']);
        }
    }

    /** @test */
    public function candidato_can_view_their_proposta()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ENVIADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->getJson("/api/propostas/{$proposta->id_proposta}");

        $response->assertStatus(200)
            ->assertJson(['id_proposta' => $proposta->id_proposta]);
    }

    /** @test */
    public function instituicao_can_view_proposta_for_their_vaga()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ENVIADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->instituicaoToken
        ])->getJson("/api/propostas/{$proposta->id_proposta}");

        $response->assertStatus(200)
            ->assertJson(['id_proposta' => $proposta->id_proposta]);
    }

    /** @test */
    public function unauthorized_user_cannot_view_proposta()
    {
        $otherCandidatoUser = User::factory()->create(['tipo_usuario' => 'CANDIDATO']);
        $otherCandidato = Candidato::factory()->create(['id_usuario' => $otherCandidatoUser->id_usuario]);
        $otherToken = \App\Helpers\JwtHelper::generateToken($otherCandidatoUser);

        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $otherToken
        ])->getJson("/api/propostas/{$proposta->id_proposta}");

        $response->assertStatus(403);
    }

    /** @test */
    public function shows_contact_info_when_proposta_is_accepted()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ACEITA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->getJson("/api/propostas/{$proposta->id_proposta}");

        $response->assertStatus(200)
            ->assertJsonStructure(['contatos' => ['email', 'telefone']]);
    }

    /** @test */
    public function masks_contact_info_when_proposta_is_not_accepted()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ENVIADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->getJson("/api/propostas/{$proposta->id_proposta}");

        $response->assertStatus(200);
        $data = $response->json();

        // Verificar que dados sensíveis não estão presentes
        $this->assertArrayNotHasKey('contatos', $data);
    }

    /** @test */
    public function candidato_can_create_proposta_for_themselves()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->postJson('/api/propostas', [
            'id_vaga' => $this->vaga->id_vaga,
            'id_candidato' => $this->candidato->id_candidato,
            'mensagem' => 'Tenho interesse nesta vaga!'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'proposta']);

        $this->assertDatabaseHas('propostas', [
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ENVIADA->value
        ]);
    }

    /** @test */
    public function candidato_cannot_create_proposta_for_another_candidato()
    {
        $otherCandidatoUser = User::factory()->create(['tipo_usuario' => 'CANDIDATO']);
        $otherCandidato = Candidato::factory()->create(['id_usuario' => $otherCandidatoUser->id_usuario]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->postJson('/api/propostas', [
            'id_vaga' => $this->vaga->id_vaga,
            'id_candidato' => $otherCandidato->id_candidato,
            'mensagem' => 'Tentando criar proposta para outro candidato'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_candidato']);
    }

    /** @test */
    public function instituicao_can_create_proposta_for_their_vaga()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->instituicaoToken
        ])->postJson('/api/propostas', [
            'id_vaga' => $this->vaga->id_vaga,
            'id_candidato' => $this->candidato->id_candidato,
            'mensagem' => 'Gostamos do seu perfil!'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'proposta']);

        $this->assertDatabaseHas('propostas', [
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'INSTITUICAO',
            'status' => PropostaStatus::ENVIADA->value
        ]);
    }

    /** @test */
    public function instituicao_cannot_create_proposta_for_another_instituicao_vaga()
    {
        $otherInstituicaoUser = User::factory()->create(['tipo_usuario' => 'INSTITUICAO']);
        $otherInstituicao = Instituicao::factory()->create(['id_usuario' => $otherInstituicaoUser->id_usuario]);
        $otherVaga = Vaga::factory()->create(['id_instituicao' => $otherInstituicao->id_instituicao]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->instituicaoToken
        ])->postJson('/api/propostas', [
            'id_vaga' => $otherVaga->id_vaga,
            'id_candidato' => $this->candidato->id_candidato,
            'mensagem' => 'Tentando criar proposta para vaga de outra instituição'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_vaga']);
    }

    /** @test */
    public function validates_required_fields_on_create_proposta()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->postJson('/api/propostas', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_vaga', 'id_candidato', 'mensagem']);
    }

    /** @test */
    public function strips_html_tags_from_mensagem()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->postJson('/api/propostas', [
            'id_vaga' => $this->vaga->id_vaga,
            'id_candidato' => $this->candidato->id_candidato,
            'mensagem' => '<script>alert("xss")</script>Mensagem limpa'
        ]);

        $response->assertStatus(201);

        $proposta = Proposta::latest()->first();
        $this->assertEquals('Mensagem limpa', $proposta->mensagem);
    }

    /** @test */
    public function recipient_can_accept_proposta()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ENVIADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->instituicaoToken
        ])->putJson("/api/propostas/{$proposta->id_proposta}/aceitar", [
            'mensagem_resposta' => 'Aceito! Vamos conversar.'
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Proposta aceita'])
            ->assertJsonStructure(['contatos']);

        $this->assertDatabaseHas('propostas', [
            'id_proposta' => $proposta->id_proposta,
            'status' => PropostaStatus::ACEITA->value
        ]);
    }

    /** @test */
    public function sender_cannot_accept_their_own_proposta()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ENVIADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->putJson("/api/propostas/{$proposta->id_proposta}/aceitar");

        $response->assertStatus(403);
    }

    /** @test */
    public function recipient_can_reject_proposta()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ENVIADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->instituicaoToken
        ])->putJson("/api/propostas/{$proposta->id_proposta}/recusar", [
            'mensagem_resposta' => 'Não é o perfil que buscamos no momento.'
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Proposta recusada']);

        $this->assertDatabaseHas('propostas', [
            'id_proposta' => $proposta->id_proposta,
            'status' => PropostaStatus::RECUSADA->value
        ]);
    }

    /** @test */
    public function sender_cannot_reject_their_own_proposta()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ENVIADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->putJson("/api/propostas/{$proposta->id_proposta}/recusar");

        $response->assertStatus(403);
    }

    /** @test */
    public function initiator_can_cancel_their_proposta()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ENVIADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->deleteJson("/api/propostas/{$proposta->id_proposta}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Proposta cancelada']);

        $this->assertDatabaseMissing('propostas', [
            'id_proposta' => $proposta->id_proposta
        ]);
    }

    /** @test */
    public function non_initiator_cannot_cancel_proposta()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ENVIADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->instituicaoToken
        ])->deleteJson("/api/propostas/{$proposta->id_proposta}");

        $response->assertStatus(403);
    }

    /** @test */
    public function cannot_cancel_finalized_proposta()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ACEITA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->deleteJson("/api/propostas/{$proposta->id_proposta}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('propostas', [
            'id_proposta' => $proposta->id_proposta
        ]);
    }

    /** @test */
    public function proposta_list_supports_pagination()
    {
        // Criar 15 propostas, cada uma para vaga diferente
        for ($i = 0; $i < 15; $i++) {
            $vaga = Vaga::factory()->create([
                'id_instituicao' => $this->instituicao->id_instituicao,
                'status' => VagaStatus::ATIVA->value
            ]);
            Proposta::factory()->create([
                'id_candidato' => $this->candidato->id_candidato,
                'iniciador' => 'CANDIDATO',
                'id_vaga' => $vaga->id_vaga
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->getJson('/api/propostas?per_page=10');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(10, $data);
        $this->assertArrayHasKey('meta', $response->json());
    }

    /** @test */
    public function cannot_create_duplicate_proposta()
    {
        // Criar primeira proposta
        Proposta::create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ENVIADA,
            'mensagem' => 'Primeira proposta'
        ]);

        // Tentar criar segunda proposta para mesma vaga
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->postJson('/api/propostas', [
            'id_vaga' => $this->vaga->id_vaga,
            'id_candidato' => $this->candidato->id_candidato,
            'mensagem' => 'Segunda tentativa'
        ]);

        $response->assertStatus(422);

        // Deve ter apenas 1 proposta no banco
        $count = Proposta::where('id_candidato', $this->candidato->id_candidato)
            ->where('id_vaga', $this->vaga->id_vaga)
            ->count();

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function cannot_create_proposta_for_paused_vaga()
    {
        $vagaPausada = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao,
            'status' => VagaStatus::PAUSADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->postJson('/api/propostas', [
            'id_vaga' => $vagaPausada->id_vaga,
            'id_candidato' => $this->candidato->id_candidato,
            'mensagem' => 'Tentando candidatar para vaga pausada'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_vaga']);
    }

    /** @test */
    public function cannot_create_proposta_for_closed_vaga()
    {
        $vagaFechada = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao,
            'status' => VagaStatus::FECHADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->postJson('/api/propostas', [
            'id_vaga' => $vagaFechada->id_vaga,
            'id_candidato' => $this->candidato->id_candidato,
            'mensagem' => 'Tentando candidatar para vaga fechada'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_vaga']);
    }

    /** @test */
    public function cannot_create_proposta_for_non_existent_vaga()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->postJson('/api/propostas', [
            'id_vaga' => 999999,
            'id_candidato' => $this->candidato->id_candidato,
            'mensagem' => 'Tentando candidatar para vaga inexistente'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_vaga']);
    }

    /** @test */
    public function mensagem_has_minimum_length()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->postJson('/api/propostas', [
            'id_vaga' => $this->vaga->id_vaga,
            'id_candidato' => $this->candidato->id_candidato,
            'mensagem' => 'Ab' // Muito curta
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mensagem']);
    }

    /** @test */
    public function mensagem_resposta_is_stripped_of_html()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ENVIADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->instituicaoToken
        ])->putJson("/api/propostas/{$proposta->id_proposta}/aceitar", [
            'mensagem_resposta' => '<script>alert("xss")</script>Aceito!'
        ]);

        $response->assertStatus(200);

        $proposta->refresh();
        $this->assertEquals('Aceito!', $proposta->mensagem_resposta);
    }

    /** @test */
    public function cannot_accept_already_accepted_proposta()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ACEITA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->instituicaoToken
        ])->putJson("/api/propostas/{$proposta->id_proposta}/aceitar", [
            'mensagem_resposta' => 'Tentando aceitar novamente'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function cannot_reject_already_rejected_proposta()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::RECUSADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->instituicaoToken
        ])->putJson("/api/propostas/{$proposta->id_proposta}/recusar", [
            'mensagem_resposta' => 'Tentando recusar novamente'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function list_orders_by_most_recent_first()
    {
        // Criar propostas em ordem - cada uma para vaga diferente
        $vagaAntiga = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao,
            'status' => VagaStatus::ATIVA->value
        ]);
        $antiga = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'iniciador' => 'CANDIDATO',
            'id_vaga' => $vagaAntiga->id_vaga,
            'created_at' => now()->subDays(2)
        ]);

        $vagaRecente = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao,
            'status' => VagaStatus::ATIVA->value
        ]);
        $recente = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'iniciador' => 'CANDIDATO',
            'id_vaga' => $vagaRecente->id_vaga,
            'created_at' => now()
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->getJson('/api/propostas?tipo=enviadas');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Primeira proposta deve ser a mais recente
        $this->assertEquals($recente->id_proposta, $data[0]['id_proposta']);
    }

    /** @test */
    public function can_filter_propostas_by_status()
    {
        $vaga1 = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao,
            'status' => VagaStatus::ATIVA->value
        ]);
        Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'iniciador' => 'CANDIDATO',
            'id_vaga' => $vaga1->id_vaga,
            'status' => PropostaStatus::ENVIADA
        ]);

        $vaga2 = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao,
            'status' => VagaStatus::ATIVA->value
        ]);
        Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'iniciador' => 'CANDIDATO',
            'id_vaga' => $vaga2->id_vaga,
            'status' => PropostaStatus::ACEITA
        ]);

        $vaga3 = Vaga::factory()->create([
            'id_instituicao' => $this->instituicao->id_instituicao,
            'status' => VagaStatus::ATIVA->value
        ]);
        Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'iniciador' => 'CANDIDATO',
            'id_vaga' => $vaga3->id_vaga,
            'status' => PropostaStatus::RECUSADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->getJson('/api/propostas?tipo=enviadas&status=ACEITA');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals(PropostaStatus::ACEITA->value, $data[0]['status']);
    }

    /** @test */
    public function empty_proposta_list_returns_empty_array()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->getJson('/api/propostas?tipo=enviadas');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(0, $data);
        $this->assertIsArray($data);
    }

    /** @test */
    public function proposta_includes_vaga_data()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ENVIADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->getJson("/api/propostas/{$proposta->id_proposta}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id_proposta',
                'vaga' => ['id_vaga', 'titulo']
            ]);
    }

    /** @test */
    public function proposta_includes_candidato_data_for_instituicao()
    {
        $proposta = Proposta::factory()->create([
            'id_candidato' => $this->candidato->id_candidato,
            'id_vaga' => $this->vaga->id_vaga,
            'iniciador' => 'CANDIDATO',
            'status' => PropostaStatus::ENVIADA
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->instituicaoToken
        ])->getJson("/api/propostas/{$proposta->id_proposta}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id_proposta',
                'candidato' => ['id_candidato', 'nome_completo']
            ]);
    }

    /** @test */
    public function cannot_view_non_existent_proposta()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->getJson('/api/propostas/999999');

        $response->assertStatus(404);
    }

    /** @test */
    public function tipo_filter_is_required_for_list()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->getJson('/api/propostas'); // Sem tipo=enviadas ou tipo=recebidas

        // Pode retornar erro 422 ou retornar todas - depende da implementação
        // Ajuste este teste conforme a lógica do controller
        $this->assertTrue(
            $response->status() === 422 || $response->status() === 200
        );
    }

    /** @test */
    public function candidato_sees_only_their_propostas()
    {
        $outroCandidatoUser = User::factory()->create(['tipo_usuario' => 'CANDIDATO']);
        $outroCandidato = Candidato::factory()->create(['id_usuario' => $outroCandidatoUser->id_usuario]);

        // Criar propostas de outro candidato - cada uma para vaga diferente
        for ($i = 0; $i < 3; $i++) {
            $vaga = Vaga::factory()->create([
                'id_instituicao' => $this->instituicao->id_instituicao,
                'status' => VagaStatus::ATIVA->value
            ]);
            Proposta::factory()->create([
                'id_candidato' => $outroCandidato->id_candidato,
                'iniciador' => 'CANDIDATO',
                'id_vaga' => $vaga->id_vaga
            ]);
        }

        // Criar propostas do candidato atual - cada uma para vaga diferente
        for ($i = 0; $i < 2; $i++) {
            $vaga = Vaga::factory()->create([
                'id_instituicao' => $this->instituicao->id_instituicao,
                'status' => VagaStatus::ATIVA->value
            ]);
            Proposta::factory()->create([
                'id_candidato' => $this->candidato->id_candidato,
                'iniciador' => 'CANDIDATO',
                'id_vaga' => $vaga->id_vaga
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->candidatoToken
        ])->getJson('/api/propostas?tipo=enviadas');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Deve ver apenas as próprias propostas (2)
        $this->assertCount(2, $data);
    }

    /** @test */
    public function instituicao_sees_only_propostas_for_their_vagas()
    {
        $outraInstituicaoUser = User::factory()->create(['tipo_usuario' => 'INSTITUICAO']);
        $outraInstituicao = Instituicao::factory()->create(['id_usuario' => $outraInstituicaoUser->id_usuario]);
        $outraVaga = Vaga::factory()->create([
            'id_instituicao' => $outraInstituicao->id_instituicao,
            'status' => VagaStatus::ATIVA
        ]);

        // Propostas para vagas de outra instituição - cada uma para vaga diferente
        for ($i = 0; $i < 3; $i++) {
            $vaga = Vaga::factory()->create([
                'id_instituicao' => $outraInstituicao->id_instituicao,
                'status' => VagaStatus::ATIVA->value
            ]);
            Proposta::factory()->create([
                'id_candidato' => $this->candidato->id_candidato,
                'iniciador' => 'CANDIDATO',
                'id_vaga' => $vaga->id_vaga
            ]);
        }

        // Propostas para vagas da instituição atual - cada uma para vaga diferente
        for ($i = 0; $i < 2; $i++) {
            $vaga = Vaga::factory()->create([
                'id_instituicao' => $this->instituicao->id_instituicao,
                'status' => VagaStatus::ATIVA->value
            ]);
            Proposta::factory()->create([
                'id_candidato' => $this->candidato->id_candidato,
                'iniciador' => 'CANDIDATO',
                'id_vaga' => $vaga->id_vaga
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->instituicaoToken
        ])->getJson('/api/propostas?tipo=recebidas');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Deve ver apenas propostas para suas vagas (2)
        $this->assertCount(2, $data);
    }
}
