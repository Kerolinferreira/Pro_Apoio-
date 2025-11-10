<?php

namespace Tests\Unit\Models;

use App\Models\Deficiencia;
use App\Models\ExperienciaProfissional;
use App\Models\Vaga;
use App\Models\Candidato;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeficienciaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_table_name()
    {
        $deficiencia = new Deficiencia();
        $this->assertEquals('deficiencias', $deficiencia->getTable());
    }

    /** @test */
    public function it_has_correct_primary_key()
    {
        $deficiencia = new Deficiencia();
        $this->assertEquals('id_deficiencia', $deficiencia->getKeyName());
    }

    /** @test */
    public function it_does_not_use_timestamps()
    {
        $deficiencia = new Deficiencia();
        $this->assertFalse($deficiencia->timestamps);
    }

    /** @test */
    public function it_can_be_created_with_nome()
    {
        $deficiencia = Deficiencia::factory()->create([
            'nome' => 'Deficiência Visual'
        ]);

        $this->assertDatabaseHas('deficiencias', [
            'nome' => 'Deficiência Visual'
        ]);
    }

    /** @test */
    public function id_accessor_returns_id_deficiencia()
    {
        $deficiencia = Deficiencia::factory()->create();

        $this->assertEquals($deficiencia->id_deficiencia, $deficiencia->id);
    }

    /** @test */
    public function it_belongs_to_many_experiencias_profissionais()
    {
        $candidato = Candidato::factory()->create();
        $experiencia = ExperienciaProfissional::factory()->create([
            'id_candidato' => $candidato->id_candidato
        ]);
        $deficiencia = Deficiencia::factory()->create();

        $experiencia->deficiencias()->attach($deficiencia->id_deficiencia);

        $this->assertTrue($deficiencia->experienciasProfissionais->contains($experiencia));
        $this->assertInstanceOf(ExperienciaProfissional::class, $deficiencia->experienciasProfissionais->first());
    }

    /** @test */
    public function it_belongs_to_many_vagas()
    {
        $vaga = Vaga::factory()->create();
        $deficiencia = Deficiencia::factory()->create();

        $vaga->deficiencias()->attach($deficiencia->id_deficiencia);

        $this->assertTrue($deficiencia->vagas->contains($vaga));
        $this->assertInstanceOf(Vaga::class, $deficiencia->vagas->first());
    }

    /** @test */
    public function it_can_be_attached_to_multiple_vagas()
    {
        $deficiencia = Deficiencia::factory()->create(['nome' => 'Deficiência Auditiva']);
        $vagas = Vaga::factory()->count(3)->create();

        $deficiencia->vagas()->attach($vagas->pluck('id_vaga'));

        $this->assertCount(3, $deficiencia->vagas);
    }

    /** @test */
    public function it_can_be_attached_to_multiple_experiencias()
    {
        $candidato = Candidato::factory()->create();
        $deficiencia = Deficiencia::factory()->create(['nome' => 'Deficiência Física']);
        $experiencias = ExperienciaProfissional::factory()->count(2)->create([
            'id_candidato' => $candidato->id_candidato
        ]);

        $deficiencia->experienciasProfissionais()->attach($experiencias->pluck('id_experiencia_profissional'));

        $this->assertCount(2, $deficiencia->experienciasProfissionais);
    }

    /** @test */
    public function multiple_deficiencias_can_be_attached_to_single_vaga()
    {
        $vaga = Vaga::factory()->create();
        $deficiencias = Deficiencia::factory()->count(3)->create();

        $vaga->deficiencias()->attach($deficiencias->pluck('id_deficiencia'));

        foreach ($deficiencias as $deficiencia) {
            $this->assertTrue($deficiencia->vagas->contains($vaga));
        }
    }

    /** @test */
    public function nome_is_fillable()
    {
        $deficiencia = new Deficiencia();
        $deficiencia->fill(['nome' => 'Deficiência Intelectual']);

        $this->assertEquals('Deficiência Intelectual', $deficiencia->nome);
    }
}
