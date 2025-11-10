<?php

namespace Tests\Unit\Models;

use App\Models\Proposta;
use App\Models\Candidato;
use App\Models\Instituicao;
use App\Models\Vaga;
use App\Enums\PropostaStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropostaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_remetente()
    {
        $candidato = Candidato::factory()->create();
        $instituicao = Instituicao::factory()->create();

        $proposta = Proposta::factory()->create([
            'id_remetente' => $candidato->id_candidato,
            'tipo_remetente' => 'Candidato',
            'id_destinatario' => $instituicao->id_instituicao,
            'tipo_destinatario' => 'Instituicao'
        ]);

        $this->assertInstanceOf(Candidato::class, $proposta->remetente);
        $this->assertEquals($candidato->id_candidato, $proposta->remetente->id_candidato);
    }

    /** @test */
    public function it_belongs_to_destinatario()
    {
        $candidato = Candidato::factory()->create();
        $instituicao = Instituicao::factory()->create();

        $proposta = Proposta::factory()->create([
            'id_remetente' => $instituicao->id_instituicao,
            'tipo_remetente' => 'Instituicao',
            'id_destinatario' => $candidato->id_candidato,
            'tipo_destinatario' => 'Candidato'
        ]);

        $this->assertInstanceOf(Candidato::class, $proposta->destinatario);
    }

    /** @test */
    public function it_can_reference_optional_vaga()
    {
        $vaga = Vaga::factory()->create();
        $proposta = Proposta::factory()->create(['id_vaga' => $vaga->id_vaga]);

        $this->assertInstanceOf(Vaga::class, $proposta->vaga);
        $this->assertEquals($vaga->id_vaga, $proposta->vaga->id_vaga);
    }

    /** @test */
    public function status_is_enviada_by_default()
    {
        $proposta = Proposta::factory()->create();

        $this->assertEquals(PropostaStatus::ENVIADA, $proposta->status);
    }

    /** @test */
    public function it_can_change_status_to_aceita()
    {
        $proposta = Proposta::factory()->create(['status' => PropostaStatus::ENVIADA->value]);

        $proposta->update(['status' => PropostaStatus::ACEITA->value]);

        $this->assertEquals(PropostaStatus::ACEITA, $proposta->fresh()->status);
    }

    /** @test */
    public function it_can_change_status_to_recusada()
    {
        $proposta = Proposta::factory()->create(['status' => PropostaStatus::ENVIADA->value]);

        $proposta->update(['status' => PropostaStatus::RECUSADA->value]);

        $this->assertEquals(PropostaStatus::RECUSADA, $proposta->fresh()->status);
    }

    /** @test */
    public function it_casts_data_criacao_to_datetime()
    {
        $proposta = Proposta::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $proposta->data_criacao);
    }

    /** @test */
    public function it_can_store_mensagem()
    {
        $proposta = Proposta::factory()->create([
            'mensagem' => 'Gostaria de trabalhar nesta vaga'
        ]);

        $this->assertEquals('Gostaria de trabalhar nesta vaga', $proposta->mensagem);
    }

    /** @test */
    public function it_handles_proposta_from_candidato_to_instituicao()
    {
        $candidato = Candidato::factory()->create();
        $instituicao = Instituicao::factory()->create();
        $vaga = Vaga::factory()->create(['id_instituicao' => $instituicao->id_instituicao]);

        $proposta = Proposta::factory()->create([
            'id_remetente' => $candidato->id_candidato,
            'tipo_remetente' => 'Candidato',
            'id_destinatario' => $instituicao->id_instituicao,
            'tipo_destinatario' => 'Instituicao',
            'id_vaga' => $vaga->id_vaga
        ]);

        $this->assertInstanceOf(Candidato::class, $proposta->remetente);
        $this->assertInstanceOf(Instituicao::class, $proposta->destinatario);
        $this->assertInstanceOf(Vaga::class, $proposta->vaga);
    }

    /** @test */
    public function it_handles_proposta_from_instituicao_to_candidato()
    {
        $candidato = Candidato::factory()->create();
        $instituicao = Instituicao::factory()->create();

        $proposta = Proposta::factory()->create([
            'id_remetente' => $instituicao->id_instituicao,
            'tipo_remetente' => 'Instituicao',
            'id_destinatario' => $candidato->id_candidato,
            'tipo_destinatario' => 'Candidato'
        ]);

        $this->assertInstanceOf(Instituicao::class, $proposta->remetente);
        $this->assertInstanceOf(Candidato::class, $proposta->destinatario);
    }
}
