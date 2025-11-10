<?php

namespace Tests\Integration;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * Testes de Integração Reais com APIs Externas.
 *
 * ATENÇÃO: Estes testes fazem chamadas REAIS às APIs externas.
 * Execute apenas quando necessário para validar integrações.
 *
 * Para executar:
 *   php artisan test --group=integration
 *
 * Para excluir (comportamento padrão):
 *   php artisan test --exclude-group=integration
 */
class ExternalApiIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Limpa cache para garantir chamadas reais
        Cache::flush();

        // Pula testes se não estiver em ambiente adequado
        if (!env('RUN_INTEGRATION_TESTS', false)) {
            $this->markTestSkipped(
                'Testes de integração desabilitados. ' .
                'Configure RUN_INTEGRATION_TESTS=true no .env para executar.'
            );
        }
    }

    /**
     * @test
     * @group integration
     * @group external-api
     */
    public function viacep_integracao_real_com_cep_valido()
    {
        // CEP da Avenida Paulista - deve existir
        $response = $this->getJson('/api/external/viacep/01310100');

        $response->assertOk()
            ->assertJsonStructure([
                'cep',
                'logradouro',
                'bairro',
                'localidade',
                'uf',
            ])
            ->assertJsonPath('uf', 'SP')
            ->assertJsonPath('localidade', 'São Paulo');
    }

    /**
     * @test
     * @group integration
     * @group external-api
     */
    public function viacep_integracao_real_com_cep_inexistente()
    {
        // CEP que provavelmente não existe
        $response = $this->getJson('/api/external/viacep/99999999');

        $response->assertNotFound()
            ->assertJson(['message' => 'CEP não encontrado.']);
    }

    /**
     * @test
     * @group integration
     * @group external-api
     */
    public function viacep_integracao_real_testa_cache()
    {
        $cep = '01310100';

        // Primeira chamada - faz requisição real
        $start1 = microtime(true);
        $response1 = $this->getJson("/api/external/viacep/{$cep}");
        $time1 = microtime(true) - $start1;

        $response1->assertOk();

        // Segunda chamada - deve usar cache (muito mais rápida)
        $start2 = microtime(true);
        $response2 = $this->getJson("/api/external/viacep/{$cep}");
        $time2 = microtime(true) - $start2;

        $response2->assertOk();

        // Cache deve ser significativamente mais rápido
        $this->assertLessThan($time1 / 2, $time2,
            'Segunda requisição deveria ser muito mais rápida (cache)');

        // Dados devem ser idênticos
        $this->assertEquals(
            $response1->json(),
            $response2->json(),
            'Dados do cache devem ser idênticos aos dados originais'
        );
    }

    /**
     * @test
     * @group integration
     * @group external-api
     * @group cnpj
     */
    public function cnpj_integracao_real_com_cnpj_valido()
    {
        // CNPJ do Google Brasil
        $cnpj = '06990590000123';

        $response = $this->getJson("/api/external/receitaws/{$cnpj}");

        $response->assertOk()
            ->assertJsonStructure([
                'cnpj',
                'razao_social',
                'nome_fantasia',
                'cidade',
                'estado',
            ])
            ->assertJsonPath('cnpj', $cnpj);

        // Valida que retornou dados reais
        $this->assertNotEmpty($response->json('razao_social'),
            'Razão social deve estar preenchida');
    }

    /**
     * @test
     * @group integration
     * @group external-api
     * @group cnpj
     */
    public function cnpj_integracao_real_com_cnpj_inexistente()
    {
        // CNPJ com formato válido mas que não existe
        $response = $this->getJson('/api/external/receitaws/00000000000000');

        $response->assertNotFound();
    }

    /**
     * @test
     * @group integration
     * @group external-api
     * @group cnpj
     */
    public function cnpj_integracao_real_testa_fallback()
    {
        // CNPJ do Google Brasil - testa se pelo menos uma API responde
        $cnpj = '06990590000123';

        $response = $this->getJson("/api/external/receitaws/{$cnpj}");

        $response->assertOk()
            ->assertJsonStructure([
                'cnpj',
                'razao_social',
            ]);

        // Verifica que os dados foram normalizados corretamente
        $data = $response->json();

        $this->assertArrayHasKey('telefone', $data,
            'Resposta normalizada deve ter campo telefone');
        $this->assertArrayHasKey('telefone_fixo', $data,
            'Resposta normalizada deve ter campo telefone_fixo');
        $this->assertArrayHasKey('cidade', $data,
            'Resposta normalizada deve ter campo cidade');
        $this->assertArrayHasKey('municipio', $data,
            'Resposta normalizada deve ter campo municipio (alias)');
        $this->assertArrayHasKey('estado', $data,
            'Resposta normalizada deve ter campo estado');
        $this->assertArrayHasKey('uf', $data,
            'Resposta normalizada deve ter campo uf (alias)');
    }

    /**
     * @test
     * @group integration
     * @group external-api
     * @group cnpj
     */
    public function cnpj_integracao_real_testa_cache()
    {
        $cnpj = '06990590000123';

        // Primeira chamada - faz requisição real
        $start1 = microtime(true);
        $response1 = $this->getJson("/api/external/receitaws/{$cnpj}");
        $time1 = microtime(true) - $start1;

        $response1->assertOk();

        // Segunda chamada - deve usar cache
        $start2 = microtime(true);
        $response2 = $this->getJson("/api/external/receitaws/{$cnpj}");
        $time2 = microtime(true) - $start2;

        $response2->assertOk();

        // Cache deve ser significativamente mais rápido
        $this->assertLessThan($time1 / 2, $time2,
            'Segunda requisição deveria ser muito mais rápida (cache)');

        // Dados devem ser idênticos
        $this->assertEquals(
            $response1->json(),
            $response2->json(),
            'Dados do cache devem ser idênticos aos dados originais'
        );
    }

    /**
     * @test
     * @group integration
     * @group external-api
     */
    public function viacep_integracao_real_testa_bypass_cache()
    {
        $cep = '01310100';

        // Primeira chamada - popula cache
        $response1 = $this->getJson("/api/external/viacep/{$cep}");
        $response1->assertOk();

        // Segunda chamada com fresh=true - deve fazer nova requisição
        $start = microtime(true);
        $response2 = $this->getJson("/api/external/viacep/{$cep}?fresh=true");
        $time = microtime(true) - $start;

        $response2->assertOk();

        // Com fresh=true, deve fazer requisição real (mais lenta que cache)
        $this->assertGreaterThan(0.01, $time,
            'Requisição com fresh=true deve fazer chamada real (não instantânea)');
    }

    /**
     * @test
     * @group integration
     * @group external-api
     * @group cnpj
     */
    public function cnpj_integracao_real_testa_bypass_cache()
    {
        $cnpj = '06990590000123';

        // Primeira chamada - popula cache
        $response1 = $this->getJson("/api/external/receitaws/{$cnpj}");
        $response1->assertOk();

        // Segunda chamada com fresh=true - deve fazer nova requisição
        $start = microtime(true);
        $response2 = $this->getJson("/api/external/receitaws/{$cnpj}?fresh=true");
        $time = microtime(true) - $start;

        $response2->assertOk();

        // Com fresh=true, deve fazer requisição real (mais lenta que cache)
        $this->assertGreaterThan(0.01, $time,
            'Requisição com fresh=true deve fazer chamada real (não instantânea)');
    }

    /**
     * @test
     * @group integration
     * @group external-api
     */
    public function viacep_integracao_real_aceita_diferentes_formatos()
    {
        // Testa com hífen
        $response1 = $this->getJson('/api/external/viacep/01310-100');
        $response1->assertOk();

        // Testa sem hífen
        $response2 = $this->getJson('/api/external/viacep/01310100');
        $response2->assertOk();

        // Ambos devem retornar os mesmos dados
        $this->assertEquals(
            $response1->json('logradouro'),
            $response2->json('logradouro'),
            'Diferentes formatos devem retornar mesmos dados'
        );
    }

    /**
     * @test
     * @group integration
     * @group external-api
     * @group cnpj
     */
    public function cnpj_integracao_real_aceita_diferentes_formatos()
    {
        $cnpjFormatado = '06.990.590/0001-23';
        $cnpjLimpo = '06990590000123';

        // Testa com máscara
        $response1 = $this->getJson("/api/external/receitaws/{$cnpjFormatado}");
        $response1->assertOk();

        // Testa sem máscara
        $response2 = $this->getJson("/api/external/receitaws/{$cnpjLimpo}");
        $response2->assertOk();

        // Ambos devem retornar os mesmos dados
        $this->assertEquals(
            $response1->json('razao_social'),
            $response2->json('razao_social'),
            'Diferentes formatos devem retornar mesmos dados'
        );
    }

    /**
     * @test
     * @group integration
     * @group external-api
     * @group performance
     */
    public function viacep_integracao_real_testa_timeout()
    {
        // Testa que a requisição respeita timeout de 5 segundos
        $start = microtime(true);
        $response = $this->getJson('/api/external/viacep/01310100');
        $duration = microtime(true) - $start;

        $response->assertOk();

        // Deve responder em menos de 10 segundos (timeout + margem)
        $this->assertLessThan(10, $duration,
            'Requisição deve respeitar timeout e não travar indefinidamente');
    }

    /**
     * @test
     * @group integration
     * @group external-api
     * @group cnpj
     * @group performance
     */
    public function cnpj_integracao_real_testa_timeout()
    {
        // Testa que a requisição respeita timeout de 10 segundos por API
        $start = microtime(true);
        $response = $this->getJson('/api/external/receitaws/06990590000123');
        $duration = microtime(true) - $start;

        $response->assertOk();

        // Deve responder em menos de 25 segundos (timeout de ambas APIs + margem)
        $this->assertLessThan(25, $duration,
            'Requisição deve respeitar timeout e não travar indefinidamente');
    }
}
