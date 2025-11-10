<?php

namespace Tests\Unit\Models;

use App\Models\Candidato;
use App\Models\User;
use App\Models\ExperienciaPessoal;
use App\Models\ExperienciaProfissional;
use App\Models\Deficiencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidatoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_user()
    {
        $user = User::factory()->create(['tipo_usuario' => 'Candidato']);
        $candidato = Candidato::factory()->create(['id_usuario' => $user->id_usuario]);

        $this->assertInstanceOf(User::class, $candidato->user);
        $this->assertEquals($user->id_usuario, $candidato->user->id_usuario);
    }

    /** @test */
    public function it_has_many_experiencias_pessoais()
    {
        $candidato = Candidato::factory()->create();
        ExperienciaPessoal::factory()->count(3)->create(['id_candidato' => $candidato->id_candidato]);

        $this->assertCount(3, $candidato->experienciasPessoais);
        $this->assertInstanceOf(ExperienciaPessoal::class, $candidato->experienciasPessoais->first());
    }

    /** @test */
    public function it_has_many_experiencias_profissionais()
    {
        $candidato = Candidato::factory()->create();
        ExperienciaProfissional::factory()->count(2)->create(['id_candidato' => $candidato->id_candidato]);

        $this->assertCount(2, $candidato->experienciasProfissionais);
        $this->assertInstanceOf(ExperienciaProfissional::class, $candidato->experienciasProfissionais->first());
    }

    /** @test */
    public function it_has_many_deficiencias()
    {
        $candidato = Candidato::factory()->create();
        $deficiencias = Deficiencia::factory()->count(2)->create();

        $candidato->deficiencias()->attach($deficiencias->pluck('id_deficiencia'));

        $this->assertCount(2, $candidato->deficiencias);
        $this->assertInstanceOf(Deficiencia::class, $candidato->deficiencias->first());
    }

    /** @test */
    public function it_can_be_created_with_complete_profile()
    {
        $candidato = Candidato::factory()->create([
            'nome_completo' => 'Maria Santos',
            'cpf' => '52998224725',
            'telefone' => '11987654321',
            'data_nascimento' => '1990-05-15',
            'escolaridade' => 'Ensino Superior Completo'
        ]);

        $this->assertDatabaseHas('candidatos', [
            'nome_completo' => 'Maria Santos',
            'cpf' => '52998224725',
            'escolaridade' => 'Ensino Superior Completo'
        ]);
    }

    /** @test */
    public function it_formats_cpf_attribute()
    {
        $candidato = Candidato::factory()->create(['cpf' => '529.982.247-25']);

        // CPF deve ser armazenado sem formatação
        $this->assertEquals('52998224725', $candidato->cpf);
    }

    /** @test */
    public function it_formats_telefone_attribute()
    {
        $candidato = Candidato::factory()->create(['telefone' => '(11) 98765-4321']);

        // Telefone deve ser armazenado sem formatação
        $this->assertEquals('11987654321', $candidato->telefone);
    }

    /** @test */
    public function foto_perfil_url_returns_null_when_empty()
    {
        $candidato = Candidato::factory()->create(['foto_perfil_url' => null]);

        $this->assertNull($candidato->foto_perfil_url);
    }

    /** @test */
    public function it_casts_data_nascimento_to_date()
    {
        $candidato = Candidato::factory()->create([
            'data_nascimento' => '1990-05-15'
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $candidato->data_nascimento);
    }
}
