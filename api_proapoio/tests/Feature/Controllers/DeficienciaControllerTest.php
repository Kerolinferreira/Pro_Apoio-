<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Deficiencia;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeficienciaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'DeficienciaSeeder']);
    }

    /** @test */
    public function lista_todas_deficiencias()
    {
        $response = $this->getJson('/api/deficiencias');

        $response->assertOk()
            ->assertJsonStructure([
                '*' => ['id', 'id_deficiencia', 'nome']
            ]);

        $this->assertGreaterThan(0, count($response->json()));
    }

    /** @test */
    public function deficiencias_retornam_id_e_nome()
    {
        $response = $this->getJson('/api/deficiencias');

        $response->assertOk();
        $deficiencias = $response->json();

        foreach ($deficiencias as $deficiencia) {
            $this->assertArrayHasKey('id', $deficiencia);
            $this->assertArrayHasKey('id_deficiencia', $deficiencia);
            $this->assertArrayHasKey('nome', $deficiencia);
            $this->assertIsInt($deficiencia['id']);
            $this->assertIsInt($deficiencia['id_deficiencia']);
            $this->assertEquals($deficiencia['id'], $deficiencia['id_deficiencia']);
            $this->assertIsString($deficiencia['nome']);
        }
    }

    /** @test */
    public function endpoint_e_publico_nao_requer_autenticacao()
    {
        // Sem autenticação
        $response = $this->getJson('/api/deficiencias');

        $response->assertOk();
    }

    /** @test */
    public function retorna_deficiencias_do_seeder()
    {
        $expectedDeficiencias = ['Visual', 'Auditiva', 'Física', 'Intelectual', 'Psicossocial'];

        $response = $this->getJson('/api/deficiencias');

        $response->assertOk();
        $deficiencias = $response->json();
        $nomes = array_column($deficiencias, 'nome');

        foreach ($expectedDeficiencias as $expected) {
            $this->assertContains($expected, $nomes);
        }
    }

    /** @test */
    public function retorna_json_valido()
    {
        $response = $this->getJson('/api/deficiencias');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json');

        $this->assertIsArray($response->json());
    }
}
