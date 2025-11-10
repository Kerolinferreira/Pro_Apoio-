<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidato;
use App\Models\Instituicao;
use App\Models\Endereco;
use App\Models\Deficiencia;
use App\Models\ExperienciaProfissional;
use App\Models\ExperienciaPessoal;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CandidatoFinderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'DeficienciaSeeder']);
    }

    /** @test */
    public function instituicao_pode_buscar_candidatos()
    {
        $instituicao = $this->createInstituicao();

        // Criar candidatos
        $candidato1 = $this->createCandidato(['nome_completo' => 'João Silva']);
        $candidato2 = $this->createCandidato(['nome_completo' => 'Maria Santos']);

        $response = $this->actingAs($instituicao->user)
            ->getJson('/api/candidatos');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'nome_completo', 'nivel_escolaridade']
                ],
                'links',
                'current_page',
                'total'
            ]);
    }

    /** @test */
    public function candidato_nao_pode_buscar_candidatos()
    {
        $candidato = $this->createCandidato();

        $response = $this->actingAs($candidato->user)
            ->getJson('/api/candidatos');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Acesso negado. Apenas instituições podem buscar candidatos.']);
    }

    /** @test */
    public function busca_por_termo_funciona()
    {
        $instituicao = $this->createInstituicao();

        $candidato1 = $this->createCandidato(['nome_completo' => 'João Silva Programador']);
        $candidato2 = $this->createCandidato(['nome_completo' => 'Maria Santos Designer']);

        $response = $this->actingAs($instituicao->user)
            ->getJson('/api/candidatos?termo=Programador');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('João Silva Programador', $data[0]['nome_completo']);
    }

    /** @test */
    public function busca_previne_termo_muito_longo()
    {
        $instituicao = $this->createInstituicao();

        $termoLongo = str_repeat('a', 101);

        $response = $this->actingAs($instituicao->user)
            ->getJson('/api/candidatos?termo=' . $termoLongo);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Termo de busca muito longo.']);
    }

    /** @test */
    public function busca_escapa_caracteres_especiais_like()
    {
        $instituicao = $this->createInstituicao();

        $candidato = $this->createCandidato(['nome_completo' => 'João_Silva']);

        // Buscar por % não deve retornar todos
        $response = $this->actingAs($instituicao->user)
            ->getJson('/api/candidatos?termo=%');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    /** @test */
    public function filtro_por_escolaridade_funciona()
    {
        $instituicao = $this->createInstituicao();

        $candidato1 = $this->createCandidato(['nivel_escolaridade' => 'Superior Completo']);
        $candidato2 = $this->createCandidato(['nivel_escolaridade' => 'Médio Completo']);

        $response = $this->actingAs($instituicao->user)
            ->getJson('/api/candidatos?escolaridade=Superior Completo');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('Superior Completo', $data[0]['nivel_escolaridade']);
    }

    /** @test */
    public function filtro_por_tipo_deficiencia_funciona()
    {
        $instituicao = $this->createInstituicao();
        $deficiencia = Deficiencia::first();

        $candidato1 = $this->createCandidato();
        $experiencia = ExperienciaProfissional::create([
            'id_candidato' => $candidato1->id,
            'titulo' => 'Experiência com Visual',
            'descricao' => 'Teste',
            'data_inicio' => '2020-01-01',
            'data_fim' => '2021-01-01',
            'tempo_experiencia' => '1-2 anos',
            'candidatar_mesma_deficiencia' => true,
            'comentario' => 'Teste'
        ]);
        $experiencia->deficiencias()->attach($deficiencia->id_deficiencia);

        $candidato2 = $this->createCandidato();

        $response = $this->actingAs($instituicao->user)
            ->getJson('/api/candidatos?tipo_deficiencia=' . $deficiencia->nome);

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data);
    }

    /** @test */
    public function paginacao_funciona_corretamente()
    {
        $instituicao = $this->createInstituicao();

        // Criar 25 candidatos
        for ($i = 0; $i < 25; $i++) {
            $this->createCandidato(['nome_completo' => "Candidato $i"]);
        }

        $response = $this->actingAs($instituicao->user)
            ->getJson('/api/candidatos?per_page=10');

        $response->assertOk()
            ->assertJsonStructure(['data', 'links', 'current_page', 'per_page', 'total'])
            ->assertJsonPath('per_page', 10)
            ->assertJsonPath('total', 25);
    }

    /** @test */
    public function detalhe_publico_candidato_retorna_estrutura_correta()
    {
        $candidato = $this->createCandidato();

        // Adicionar experiências
        ExperienciaProfissional::create([
            'id_candidato' => $candidato->id,
            'titulo' => 'Agente de Apoio',
            'descricao' => 'Experiência profissional',
            'data_inicio' => '2020-01-01',
            'data_fim' => null,
            'tempo_experiencia' => '2-3 anos',
            'candidatar_mesma_deficiencia' => false,
            'comentario' => 'Teste'
        ]);

        ExperienciaPessoal::create([
            'id_candidato' => $candidato->id,
            'titulo' => 'Familiar PCD',
            'descricao' => 'Experiência pessoal',
            'data_inicio' => '2015-01-01',
            'data_fim' => null,
            'interesse_atuar' => true
        ]);

        $response = $this->getJson("/api/candidatos/{$candidato->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'nome_completo',
                'escolaridade',
                'endereco' => ['cidade', 'estado'],
                'deficiencias_atuadas',
                'experiencias' => [
                    '*' => ['id', 'tipo', 'titulo', 'descricao', 'data_inicio', 'data_fim']
                ]
            ]);
    }

    /** @test */
    public function detalhe_candidato_retorna_404_se_nao_existe()
    {
        $response = $this->getJson('/api/candidatos/99999');

        $response->assertNotFound()
            ->assertJson(['message' => 'Recurso não encontrado.']);
    }

    /** @test */
    public function experiencias_sao_unificadas_e_ordenadas()
    {
        $candidato = $this->createCandidato();

        // Experiência profissional mais recente
        ExperienciaProfissional::create([
            'id_candidato' => $candidato->id,
            'titulo' => 'Experiência Recente',
            'descricao' => 'Teste',
            'data_inicio' => '2023-01-01',
            'data_fim' => null,
            'tempo_experiencia' => '1-2 anos',
            'candidatar_mesma_deficiencia' => false,
            'comentario' => 'Teste'
        ]);

        // Experiência pessoal mais antiga
        ExperienciaPessoal::create([
            'id_candidato' => $candidato->id,
            'titulo' => 'Experiência Antiga',
            'descricao' => 'Teste',
            'data_inicio' => '2020-01-01',
            'data_fim' => '2021-01-01',
            'interesse_atuar' => true
        ]);

        $response = $this->getJson("/api/candidatos/{$candidato->id}");

        $response->assertOk();
        $experiencias = $response->json('experiencias');

        // Verifica que a mais recente vem primeiro
        $this->assertEquals('Experiência Recente', $experiencias[0]['titulo']);
        $this->assertEquals('profissional', $experiencias[0]['tipo']);
        $this->assertEquals('Experiência Antiga', $experiencias[1]['titulo']);
        $this->assertEquals('pessoal', $experiencias[1]['tipo']);
    }

    // Helpers
    protected function createCandidato(array $overrides = []): Candidato
    {
        $user = User::create([
            'nome' => $overrides['nome_completo'] ?? 'Candidato Teste',
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
            'cpf' => '12345678901',
            'data_nascimento' => '1995-01-01',
            'genero' => 'Masculino',
            'telefone' => '11987654321',
            'nivel_escolaridade' => 'Superior Completo',
            'nome_completo' => $overrides['nome_completo'] ?? 'Candidato Teste',
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
            'cnpj' => '12345678000190',
            'razao_social' => 'Instituição Teste LTDA',
            'nome_fantasia' => 'Instituição Teste',
            'id_endereco' => $endereco->id_endereco,
        ]);
    }
}
