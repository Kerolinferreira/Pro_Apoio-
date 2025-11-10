<?php

namespace Tests\Unit\Models;

use App\Models\Instituicao;
use App\Models\User;
use App\Models\Endereco;
use App\Models\Vaga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituicaoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_table_name()
    {
        $instituicao = new Instituicao();
        $this->assertEquals('instituicoes', $instituicao->getTable());
    }

    /** @test */
    public function it_has_correct_primary_key()
    {
        $instituicao = new Instituicao();
        $this->assertEquals('id_instituicao', $instituicao->getKeyName());
    }

    /** @test */
    public function it_belongs_to_user()
    {
        $user = User::factory()->create(['tipo_usuario' => 'Instituicao']);
        $instituicao = Instituicao::factory()->create(['id_usuario' => $user->id_usuario]);

        $this->assertInstanceOf(User::class, $instituicao->user);
        $this->assertEquals($user->id_usuario, $instituicao->user->id_usuario);
    }

    /** @test */
    public function it_belongs_to_endereco()
    {
        $endereco = Endereco::factory()->create();
        $instituicao = Instituicao::factory()->create(['id_endereco' => $endereco->id_endereco]);

        $this->assertInstanceOf(Endereco::class, $instituicao->endereco);
        $this->assertEquals($endereco->id_endereco, $instituicao->endereco->id_endereco);
    }

    /** @test */
    public function it_has_many_vagas()
    {
        $instituicao = Instituicao::factory()->create();
        Vaga::factory()->count(3)->create(['id_instituicao' => $instituicao->id_instituicao]);

        $this->assertCount(3, $instituicao->vagas);
        $this->assertInstanceOf(Vaga::class, $instituicao->vagas->first());
    }

    /** @test */
    public function it_can_be_created_with_complete_data()
    {
        $instituicao = Instituicao::factory()->create([
            'cnpj' => '11222333000181',
            'razao_social' => 'Escola ABC LTDA',
            'nome_fantasia' => 'Escola ABC',
            'tipo_instituicao' => 'Pública',
            'codigo_inep' => '12345678'
        ]);

        $this->assertDatabaseHas('instituicoes', [
            'cnpj' => '11222333000181',
            'razao_social' => 'Escola ABC LTDA',
            'nome_fantasia' => 'Escola ABC',
            'tipo_instituicao' => 'Pública'
        ]);
    }

    /** @test */
    public function id_accessor_returns_id_instituicao()
    {
        $instituicao = Instituicao::factory()->create();

        $this->assertEquals($instituicao->id_instituicao, $instituicao->id);
    }

    /** @test */
    public function it_stores_contact_information()
    {
        $instituicao = Instituicao::factory()->create([
            'email_corporativo' => 'contato@escola.edu.br',
            'telefone_fixo' => '1133334444',
            'celular_corporativo' => '11987654321'
        ]);

        $this->assertEquals('contato@escola.edu.br', $instituicao->email_corporativo);
        $this->assertEquals('1133334444', $instituicao->telefone_fixo);
        $this->assertEquals('11987654321', $instituicao->celular_corporativo);
    }

    /** @test */
    public function it_stores_responsible_person_data()
    {
        $instituicao = Instituicao::factory()->create([
            'nome_responsavel' => 'Maria Silva',
            'funcao_responsavel' => 'Diretora'
        ]);

        $this->assertEquals('Maria Silva', $instituicao->nome_responsavel);
        $this->assertEquals('Diretora', $instituicao->funcao_responsavel);
    }

    /** @test */
    public function it_stores_education_levels()
    {
        $instituicao = Instituicao::factory()->create([
            'niveis_oferecidos' => 'Ensino Fundamental, Ensino Médio'
        ]);

        $this->assertEquals('Ensino Fundamental, Ensino Médio', $instituicao->niveis_oferecidos);
    }

    /** @test */
    public function cnpj_is_required_and_unique()
    {
        Instituicao::factory()->create(['cnpj' => '11222333000181']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Instituicao::factory()->create(['cnpj' => '11222333000181']);
    }
}
