<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ExternalApiControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function viacep_retorna_dados_validos()
    {
        Http::fake([
            'viacep.com.br/*' => Http::response([
                'cep' => '01310-100',
                'logradouro' => 'Avenida Paulista',
                'bairro' => 'Bela Vista',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
            ], 200)
        ]);

        $response = $this->getJson('/api/external/viacep/01310-100');

        $response->assertOk()
            ->assertJson([
                'cep' => '01310-100',
                'logradouro' => 'Avenida Paulista',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
            ]);
    }

    /** @test */
    public function viacep_aceita_cep_com_e_sem_mascara()
    {
        Http::fake([
            'viacep.com.br/*' => Http::response(['cep' => '01310100'], 200)
        ]);

        $response1 = $this->getJson('/api/external/viacep/01310-100');
        $response2 = $this->getJson('/api/external/viacep/01310100');

        $response1->assertOk();
        $response2->assertOk();

        // Ambos CEPs são limpos para 01310100, então o segundo usa cache
        // Apenas 1 requisição HTTP é feita
        Http::assertSentCount(1);
    }

    /** @test */
    public function viacep_rejeita_cep_invalido()
    {
        $response = $this->getJson('/api/external/viacep/123'); // muito curto

        $response->assertStatus(422)
            ->assertJson(['message' => 'CEP inválido.']);

        Http::assertNothingSent();
    }

    /** @test */
    public function viacep_retorna_404_para_cep_inexistente()
    {
        Http::fake([
            'viacep.com.br/*' => Http::response(['erro' => true], 200)
        ]);

        $response = $this->getJson('/api/external/viacep/99999999');

        $response->assertNotFound()
            ->assertJson(['message' => 'CEP não encontrado.']);
    }

    /** @test */
    public function viacep_usa_cache()
    {
        Http::fake([
            'viacep.com.br/*' => Http::response(['cep' => '01310100'], 200)
        ]);

        // Primeira chamada - faz requisição
        $this->getJson('/api/external/viacep/01310100');

        // Segunda chamada - usa cache
        $this->getJson('/api/external/viacep/01310100');

        // Deve ter feito apenas 1 requisição HTTP
        Http::assertSentCount(1);
    }

    /** @test */
    public function viacep_pode_bypassar_cache_com_fresh()
    {
        Http::fake([
            'viacep.com.br/*' => Http::response(['cep' => '01310100'], 200)
        ]);

        // Primeira chamada
        $this->getJson('/api/external/viacep/01310100');

        // Segunda chamada com fresh=true
        $this->getJson('/api/external/viacep/01310100?fresh=true');

        // Deve ter feito 2 requisições HTTP
        Http::assertSentCount(2);
    }

    /** @test */
    public function viacep_retorna_502_em_erro_de_rede()
    {
        Http::fake([
            'viacep.com.br/*' => Http::response('', 500)
        ]);

        $response = $this->getJson('/api/external/viacep/01310100');

        $response->assertStatus(502)
            ->assertJson(['message' => 'Erro ao consultar ViaCEP.']);
    }

    /** @test */
    public function receitaws_retorna_dados_validos()
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response(['message' => 'Not found'], 404),
            'receitaws.com.br/*' => Http::response([
                'status' => 'OK',
                'nome' => 'EMPRESA TESTE LTDA',
                'fantasia' => 'Empresa Teste',
                'telefone' => '(11) 1234-5678',
                'municipio' => 'São Paulo',
                'uf' => 'SP',
            ], 200)
        ]);

        $response = $this->getJson('/api/external/receitaws/12345678000190');

        $response->assertOk()
            ->assertJsonStructure([
                'cnpj',
                'razao_social',
                'nome_fantasia',
                'telefone',
                'cidade',
                'estado'
            ]);
    }

    /** @test */
    public function receitaws_prioriza_brasilapi()
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response([
                'razao_social' => 'EMPRESA BRASIL API',
                'estabelecimento' => [
                    'nome_fantasia' => 'Brasil API',
                    'cidade' => ['nome' => 'São Paulo'],
                    'estado' => ['sigla' => 'SP'],
                ],
            ], 200),
            'receitaws.com.br/*' => Http::response([
                'nome' => 'EMPRESA RECEITA WS',
            ], 200)
        ]);

        $response = $this->getJson('/api/external/receitaws/12345678000190');

        $response->assertOk()
            ->assertJsonPath('razao_social', 'EMPRESA BRASIL API');

        // Não deve ter chamado ReceitaWS
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'brasilapi');
        });

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'receitaws');
        });
    }

    /** @test */
    public function receitaws_usa_fallback_quando_brasilapi_falha()
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response('', 500),
            'receitaws.com.br/*' => Http::response([
                'status' => 'OK',
                'nome' => 'EMPRESA FALLBACK',
                'fantasia' => 'Fallback',
            ], 200)
        ]);

        $response = $this->getJson('/api/external/receitaws/12345678000190');

        $response->assertOk()
            ->assertJsonPath('razao_social', 'EMPRESA FALLBACK');

        // Deve ter tentado ambas
        Http::assertSentCount(2);
    }

    /** @test */
    public function receitaws_rejeita_cnpj_invalido()
    {
        $response = $this->getJson('/api/external/receitaws/123'); // muito curto

        $response->assertStatus(422)
            ->assertJson(['message' => 'CNPJ inválido. Deve conter 14 dígitos.']);

        Http::assertNothingSent();
    }

    /** @test */
    public function receitaws_aceita_cnpj_com_e_sem_mascara()
    {
        Cache::flush();

        Http::fake([
            'brasilapi.com.br/*' => Http::response([
                'razao_social' => 'TESTE SA',
                'estabelecimento' => [
                    'nome_fantasia' => 'Teste',
                    'cidade' => ['nome' => 'São Paulo'],
                    'estado' => ['sigla' => 'SP']
                ]
            ], 200),
        ]);

        // CNPJ válido com 14 dígitos
        // Usando apenas pontos e hífens (sem barra para evitar problema de roteamento)
        $response1 = $this->getJson('/api/external/receitaws/12.345.678.0001-90');
        $response2 = $this->getJson('/api/external/receitaws/12345678000190');

        $response1->assertOk();
        $response2->assertOk();
    }

    /** @test */
    public function receitaws_retorna_404_para_cnpj_inexistente()
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response([], 404),
            'receitaws.com.br/*' => Http::response([
                'status' => 'ERROR',
                'message' => 'CNPJ não encontrado',
            ], 200)
        ]);

        $response = $this->getJson('/api/external/receitaws/99999999000199');

        $response->assertNotFound()
            ->assertJson(['message' => 'CNPJ não encontrado']);
    }

    /** @test */
    public function receitaws_usa_cache()
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response([], 404),
            'receitaws.com.br/*' => Http::response([
                'status' => 'OK',
                'nome' => 'TESTE',
            ], 200)
        ]);

        // Primeira chamada
        $this->getJson('/api/external/receitaws/12345678000190');

        // Segunda chamada - usa cache
        $this->getJson('/api/external/receitaws/12345678000190');

        // BrasilAPI tentado 1x, ReceitaWS 1x, total 2
        Http::assertSentCount(2);
    }

    /** @test */
    public function receitaws_pode_bypassar_cache_com_fresh()
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response([], 404),
            'receitaws.com.br/*' => Http::response([
                'status' => 'OK',
                'nome' => 'TESTE',
            ], 200)
        ]);

        // Primeira chamada
        $this->getJson('/api/external/receitaws/12345678000190');

        // Segunda chamada com fresh=true
        $this->getJson('/api/external/receitaws/12345678000190?fresh=true');

        // BrasilAPI 2x, ReceitaWS 2x, total 4
        Http::assertSentCount(4);
    }

    /** @test */
    public function receitaws_retorna_502_quando_ambas_apis_falham()
    {
        Http::fake([
            'brasilapi.com.br/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('API Error');
            },
            'receitaws.com.br/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('API Error');
            }
        ]);

        $response = $this->getJson('/api/external/receitaws/12345678000190');

        $response->assertStatus(502)
            ->assertJson([
                'message' => 'Não foi possível consultar o CNPJ. Tente novamente mais tarde.'
            ]);
    }

    /** @test */
    public function brasilapi_normalizacao_funciona_corretamente()
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response([
                'razao_social' => 'EMPRESA SA',
                'estabelecimento' => [
                    'nome_fantasia' => 'Empresa',
                    'email' => 'contato@empresa.com',
                    'ddd1' => '11',
                    'telefone1' => '12345678',
                    'logradouro' => 'Rua Teste',
                    'numero' => '123',
                    'complemento' => 'Sala 1',
                    'bairro' => 'Centro',
                    'cidade' => ['nome' => 'São Paulo'],
                    'estado' => ['sigla' => 'SP'],
                    'cep' => '01234567',
                    'situacao_cadastral' => 'ATIVA',
                    'data_situacao_cadastral' => '2020-01-01',
                ],
            ], 200)
        ]);

        $response = $this->getJson('/api/external/receitaws/12345678000190');

        $response->assertOk()
            ->assertJson([
                'razao_social' => 'EMPRESA SA',
                'nome_fantasia' => 'Empresa',
                'email' => 'contato@empresa.com',
                'telefone' => '(11) 12345678',
                'logradouro' => 'Rua Teste',
                'numero' => '123',
                'complemento' => 'Sala 1',
                'bairro' => 'Centro',
                'cidade' => 'São Paulo',
                'municipio' => 'São Paulo',
                'estado' => 'SP',
                'uf' => 'SP',
                'cep' => '01234567',
                'situacao' => 'ATIVA',
                'situacao_cadastral' => 'ATIVA',
                'data_situacao' => '2020-01-01',
            ]);
    }

    /** @test */
    public function receitaws_normalizacao_funciona_corretamente()
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response([], 404),
            'receitaws.com.br/*' => Http::response([
                'status' => 'OK',
                'nome' => 'EMPRESA LTDA',
                'fantasia' => 'Empresa',
                'email' => 'contato@empresa.com',
                'telefone' => '(11) 1234-5678',
                'logradouro' => 'Rua Teste',
                'numero' => '123',
                'complemento' => 'Andar 2',
                'bairro' => 'Centro',
                'municipio' => 'São Paulo',
                'uf' => 'SP',
                'cep' => '01234-567',
                'situacao' => 'ATIVA',
                'data_situacao' => '01/01/2020',
            ], 200)
        ]);

        $response = $this->getJson('/api/external/receitaws/12345678000190');

        $response->assertOk()
            ->assertJson([
                'razao_social' => 'EMPRESA LTDA',
                'nome_fantasia' => 'Empresa',
                'email' => 'contato@empresa.com',
                'telefone' => '(11) 1234-5678',
                'telefone_fixo' => '(11) 1234-5678',
                'logradouro' => 'Rua Teste',
                'numero' => '123',
                'complemento' => 'Andar 2',
                'bairro' => 'Centro',
                'cidade' => 'São Paulo',
                'municipio' => 'São Paulo',
                'estado' => 'SP',
                'uf' => 'SP',
                'cep' => '01234-567',
                'situacao' => 'ATIVA',
                'situacao_cadastral' => 'ATIVA',
                'data_situacao' => '01/01/2020',
            ]);
    }

    // ==========================================
    // Testes de Cenários Avançados de Erro
    // ==========================================

    /** @test */
    public function viacep_trata_resposta_malformada()
    {
        Http::fake([
            'viacep.com.br/*' => function () {
                // Simula erro de JSON malformado lançando exceção
                throw new \JsonException('Malformed JSON');
            }
        ]);

        $response = $this->getJson('/api/external/viacep/01310100');

        $response->assertStatus(502)
            ->assertJson(['message' => 'Erro ao consultar ViaCEP.']);
    }

    /** @test */
    public function viacep_trata_timeout()
    {
        Http::fake([
            'viacep.com.br/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            }
        ]);

        $response = $this->getJson('/api/external/viacep/01310100');

        $response->assertStatus(502)
            ->assertJson(['message' => 'Erro ao consultar ViaCEP.']);
    }

    /** @test */
    public function receitaws_trata_resposta_parcial_brasilapi()
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response([
                'razao_social' => 'EMPRESA SA',
                // estabelecimento está ausente ou incompleto
            ], 200)
        ]);

        $response = $this->getJson('/api/external/receitaws/12345678000190');

        $response->assertOk()
            ->assertJsonStructure([
                'cnpj',
                'razao_social',
                'nome_fantasia',
            ]);
    }

    /** @test */
    public function receitaws_trata_timeout_em_ambas_apis()
    {
        Http::fake([
            'brasilapi.com.br/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Timeout');
            },
            'receitaws.com.br/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Timeout');
            }
        ]);

        $response = $this->getJson('/api/external/receitaws/12345678000190');

        $response->assertStatus(502);
    }

    /** @test */
    public function viacep_aceita_cep_com_espacos()
    {
        Http::fake([
            'viacep.com.br/*' => Http::response(['cep' => '01310100'], 200)
        ]);

        $response = $this->getJson('/api/external/viacep/013 101 00');

        $response->assertOk();
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '01310100');
        });
    }

    /** @test */
    public function receitaws_aceita_cnpj_com_caracteres_especiais()
    {
        Cache::flush();

        Http::fake([
            'brasilapi.com.br/*' => Http::response([
                'razao_social' => 'TESTE SA',
                'estabelecimento' => [
                    'nome_fantasia' => 'Teste',
                    'cidade' => ['nome' => 'São Paulo'],
                    'estado' => ['sigla' => 'SP']
                ]
            ], 200)
        ]);

        // Testa com pontos e hífen (sem barra para evitar problema de roteamento)
        $response = $this->getJson('/api/external/receitaws/12.345.678.0001-90');

        $response->assertOk();
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '12345678000190');
        });
    }

    /** @test */
    public function viacep_valida_apenas_numeros_apos_limpeza()
    {
        $response = $this->getJson('/api/external/viacep/ABCD1234');

        $response->assertStatus(422)
            ->assertJson(['message' => 'CEP inválido.']);
    }

    /** @test */
    public function receitaws_valida_apenas_numeros_apos_limpeza()
    {
        $response = $this->getJson('/api/external/receitaws/ABCD123456789');

        $response->assertStatus(422)
            ->assertJson(['message' => 'CNPJ inválido. Deve conter 14 dígitos.']);
    }

    // ==========================================
    // Testes de Rate Limiting
    // ==========================================

    /** @test */
    public function viacep_respeita_rate_limit()
    {
        Http::fake([
            'viacep.com.br/*' => Http::response(['cep' => '01310100'], 200)
        ]);

        // Faz 101 requisições para exceder o limite de 100/min
        for ($i = 0; $i < 101; $i++) {
            $response = $this->getJson('/api/external/viacep/0131010' . ($i % 10));

            if ($i < 100) {
                // Primeiras 100 devem funcionar
                $this->assertTrue(
                    $response->status() === 200 || $response->status() === 429,
                    "Request {$i} should succeed or be rate limited"
                );
            }
        }

        // A 101ª requisição deve ser bloqueada
        $response = $this->getJson('/api/external/viacep/01310100');
        $this->assertEquals(429, $response->status(), 'Should be rate limited after 100 requests');
    }

    /** @test */
    public function receitaws_respeita_rate_limit()
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response([], 404),
            'receitaws.com.br/*' => Http::response([
                'status' => 'OK',
                'nome' => 'TESTE',
            ], 200)
        ]);

        // Faz 101 requisições para exceder o limite de 100/min
        for ($i = 0; $i < 101; $i++) {
            $cnpj = str_pad((string)$i, 14, '0', STR_PAD_LEFT);
            $response = $this->getJson("/api/external/receitaws/{$cnpj}");

            if ($i < 100) {
                // Primeiras 100 devem funcionar
                $this->assertTrue(
                    $response->status() === 200 || $response->status() === 429,
                    "Request {$i} should succeed or be rate limited"
                );
            }
        }

        // A 101ª requisição deve ser bloqueada
        $response = $this->getJson('/api/external/receitaws/12345678000190');
        $this->assertEquals(429, $response->status(), 'Should be rate limited after 100 requests');
    }

    // ==========================================
    // Testes de Estrutura de Resposta (Contrato)
    // ==========================================

    /** @test */
    public function viacep_response_tem_estrutura_esperada()
    {
        Http::fake([
            'viacep.com.br/*' => Http::response([
                'cep' => '01310-100',
                'logradouro' => 'Avenida Paulista',
                'complemento' => 'até 1000',
                'bairro' => 'Bela Vista',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
                'ibge' => '3550308',
                'gia' => '1004',
                'ddd' => '11',
                'siafi' => '7107',
            ], 200)
        ]);

        $response = $this->getJson('/api/external/viacep/01310100');

        $response->assertOk()
            ->assertJsonStructure([
                'cep',
                'logradouro',
                'complemento',
                'bairro',
                'localidade',
                'uf',
            ]);
    }

    /** @test */
    public function receitaws_brasilapi_response_tem_estrutura_esperada()
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response([
                'razao_social' => 'EMPRESA SA',
                'estabelecimento' => [
                    'nome_fantasia' => 'Empresa',
                    'email' => 'contato@empresa.com',
                    'ddd1' => '11',
                    'telefone1' => '12345678',
                    'cidade' => ['nome' => 'São Paulo'],
                    'estado' => ['sigla' => 'SP'],
                ],
            ], 200)
        ]);

        $response = $this->getJson('/api/external/receitaws/12345678000190');

        $response->assertOk()
            ->assertJsonStructure([
                'cnpj',
                'razao_social',
                'nome_fantasia',
                'email',
                'telefone',
                'telefone_fixo',
                'logradouro',
                'numero',
                'complemento',
                'bairro',
                'cidade',
                'municipio',
                'estado',
                'uf',
                'cep',
                'situacao',
                'situacao_cadastral',
                'data_situacao',
            ]);
    }

    /** @test */
    public function receitaws_receitaws_response_tem_estrutura_esperada()
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response([], 404),
            'receitaws.com.br/*' => Http::response([
                'status' => 'OK',
                'nome' => 'EMPRESA LTDA',
                'fantasia' => 'Empresa',
                'telefone' => '(11) 1234-5678',
                'municipio' => 'São Paulo',
                'uf' => 'SP',
            ], 200)
        ]);

        $response = $this->getJson('/api/external/receitaws/12345678000190');

        $response->assertOk()
            ->assertJsonStructure([
                'cnpj',
                'razao_social',
                'nome_fantasia',
                'email',
                'telefone',
                'telefone_fixo',
                'logradouro',
                'numero',
                'complemento',
                'bairro',
                'cidade',
                'municipio',
                'estado',
                'uf',
                'cep',
                'situacao',
                'situacao_cadastral',
                'data_situacao',
            ]);
    }

    /** @test */
    public function viacep_mantem_formato_original_cep()
    {
        Http::fake([
            'viacep.com.br/*' => Http::response([
                'cep' => '01310-100',
                'logradouro' => 'Avenida Paulista',
            ], 200)
        ]);

        $response = $this->getJson('/api/external/viacep/01310-100');

        $response->assertOk()
            ->assertJsonPath('cep', '01310-100');
    }

    /** @test */
    public function receitaws_normaliza_campos_nulos()
    {
        Http::fake([
            'brasilapi.com.br/*' => Http::response([], 404),
            'receitaws.com.br/*' => Http::response([
                'status' => 'OK',
                'nome' => 'EMPRESA SEM DADOS',
                // Campos opcionais ausentes
            ], 200)
        ]);

        $response = $this->getJson('/api/external/receitaws/12345678000190');

        $response->assertOk()
            ->assertJson([
                'cnpj' => '12345678000190',
                'razao_social' => 'EMPRESA SEM DADOS',
                'nome_fantasia' => '',
                'email' => null,
                'telefone' => null,
            ]);
    }
}
