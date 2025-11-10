<?php

namespace Tests\Unit\Models;

use App\Models\Vaga;
use App\Models\Instituicao;
use App\Models\VagaSalva;
use App\Enums\VagaStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VagaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_instituicao()
    {
        $instituicao = Instituicao::factory()->create();
        $vaga = Vaga::factory()->create(['id_instituicao' => $instituicao->id_instituicao]);

        $this->assertInstanceOf(Instituicao::class, $vaga->instituicao);
        $this->assertEquals($instituicao->id_instituicao, $vaga->instituicao->id_instituicao);
    }

    /** @test */
    public function it_has_many_vagas_salvas()
    {
        $vaga = Vaga::factory()->create();
        VagaSalva::factory()->count(3)->create(['id_vaga' => $vaga->id_vaga]);

        $this->assertCount(3, $vaga->vagasSalvas);
    }

    /** @test */
    public function it_can_be_created_with_valid_data()
    {
        $instituicao = Instituicao::factory()->create();

        $vaga = Vaga::factory()->create([
            'id_instituicao' => $instituicao->id_instituicao,
            'titulo' => 'Auxiliar de Sala',
            'descricao' => 'Vaga para auxiliar de sala de aula',
            'tipo' => 'Estágio',
            'modalidade' => 'Presencial',
            'status' => VagaStatus::ATIVA->value,
            'cidade' => 'São Paulo',
            'estado' => 'SP'
        ]);

        $this->assertDatabaseHas('vagas', [
            'titulo' => 'Auxiliar de Sala',
            'tipo' => 'Estágio',
            'status' => VagaStatus::ATIVA->value
        ]);
    }

    /** @test */
    public function status_is_ativa_by_default()
    {
        $vaga = Vaga::factory()->create();

        $this->assertEquals(VagaStatus::ATIVA->value, $vaga->status);
    }

    /** @test */
    public function it_casts_remuneracao_to_decimal()
    {
        $vaga = Vaga::factory()->create(['remuneracao' => 1500.50]);

        $this->assertEquals(1500.50, $vaga->remuneracao);
        $this->assertIsFloat($vaga->remuneracao);
    }

    /** @test */
    public function it_casts_data_criacao_to_datetime()
    {
        $vaga = Vaga::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $vaga->data_criacao);
    }

    /** @test */
    public function it_can_change_status()
    {
        $vaga = Vaga::factory()->create(['status' => VagaStatus::ATIVA->value]);

        $vaga->update(['status' => VagaStatus::PAUSADA->value]);

        $this->assertEquals(VagaStatus::PAUSADA->value, $vaga->fresh()->status);
    }

    /** @test */
    public function required_fields_cannot_be_null()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Vaga::factory()->create([
            'titulo' => null, // Campo obrigatório
        ]);
    }

    /** @test */
    public function it_has_scope_ativas()
    {
        Vaga::factory()->count(3)->create(['status' => VagaStatus::ATIVA->value]);
        Vaga::factory()->count(2)->create(['status' => VagaStatus::PAUSADA->value]);

        $vagasAtivas = Vaga::ativas()->get();

        $this->assertCount(3, $vagasAtivas);
    }
}
