<?php

namespace Tests\Unit\Helpers;

use App\Helpers\JwtHelper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtHelperTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'tipo_usuario' => 'Candidato'
        ]);
    }

    /** @test */
    public function it_can_generate_token()
    {
        $token = JwtHelper::generateToken($this->user);

        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        // Token deve ter 3 partes separadas por ponto
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /** @test */
    public function generated_token_has_valid_structure()
    {
        $token = JwtHelper::generateToken($this->user);

        $parts = explode('.', $token);
        [$header, $payload, $signature] = $parts;

        // Cada parte deve ser uma string não vazia
        $this->assertNotEmpty($header);
        $this->assertNotEmpty($payload);
        $this->assertNotEmpty($signature);
    }

    /** @test */
    public function it_can_decode_valid_token()
    {
        $token = JwtHelper::generateToken($this->user);
        $decoded = JwtHelper::decodeToken($token);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('sub', $decoded);
        $this->assertArrayHasKey('tipo_usuario', $decoded);
        $this->assertArrayHasKey('exp', $decoded);
        $this->assertArrayHasKey('iat', $decoded);
        $this->assertEquals($this->user->id, $decoded['sub']);
        $this->assertEquals('Candidato', $decoded['tipo_usuario']);
    }

    /** @test */
    public function decoded_token_contains_all_required_claims()
    {
        $token = JwtHelper::generateToken($this->user);
        $decoded = JwtHelper::decodeToken($token);

        $this->assertArrayHasKey('jti', $decoded); // JWT ID
        $this->assertArrayHasKey('iat', $decoded); // Issued At
        $this->assertArrayHasKey('nbf', $decoded); // Not Before
        $this->assertArrayHasKey('exp', $decoded); // Expiration
    }

    /** @test */
    public function it_includes_extra_claims()
    {
        $token = JwtHelper::generateToken($this->user, 86400, [
            'custom_claim' => 'custom_value',
            'another' => 123
        ]);

        $decoded = JwtHelper::decodeToken($token);

        $this->assertEquals('custom_value', $decoded['custom_claim']);
        $this->assertEquals(123, $decoded['another']);
    }

    /** @test */
    public function it_respects_custom_ttl()
    {
        $ttl = 3600; // 1 hora
        $token = JwtHelper::generateToken($this->user, $ttl);
        $decoded = JwtHelper::decodeToken($token);

        $expectedExp = $decoded['iat'] + $ttl;
        // Permitir margem de 5 segundos para timing
        $this->assertEqualsWithDelta($expectedExp, $decoded['exp'], 5);
    }

    /** @test */
    public function it_rejects_token_with_invalid_format()
    {
        $invalidToken = 'invalid.token';
        $decoded = JwtHelper::decodeToken($invalidToken);

        $this->assertNull($decoded);
    }

    /** @test */
    public function it_rejects_token_with_only_two_parts()
    {
        $invalidToken = 'header.payload';
        $decoded = JwtHelper::decodeToken($invalidToken);

        $this->assertNull($decoded);
    }

    /** @test */
    public function it_rejects_token_with_invalid_signature()
    {
        $token = JwtHelper::generateToken($this->user);

        // Modificar a assinatura
        $parts = explode('.', $token);
        $parts[2] = 'invalidsignature';
        $tamperedToken = implode('.', $parts);

        $decoded = JwtHelper::decodeToken($tamperedToken);

        $this->assertNull($decoded);
    }

    /** @test */
    public function it_rejects_token_with_tampered_payload()
    {
        $token = JwtHelper::generateToken($this->user);

        // Modificar o payload
        $parts = explode('.', $token);
        $parts[1] = base64_encode(json_encode(['sub' => 999, 'tipo_usuario' => 'admin']));
        $tamperedToken = implode('.', $parts);

        $decoded = JwtHelper::decodeToken($tamperedToken);

        $this->assertNull($decoded);
    }

    /** @test */
    public function it_rejects_expired_token()
    {
        // Gerar token com TTL negativo (já expirado)
        $token = JwtHelper::generateToken($this->user, -100);

        // Aguardar um momento para garantir expiração
        sleep(1);

        $decoded = JwtHelper::decodeToken($token);

        $this->assertNull($decoded);
    }

    /** @test */
    public function it_accepts_token_within_clock_skew()
    {
        // Gerar token com TTL muito curto mas dentro da tolerância de 60s
        $token = JwtHelper::generateToken($this->user, 30);

        $decoded = JwtHelper::decodeToken($token);

        // Deve aceitar devido ao clock skew de 60s
        $this->assertIsArray($decoded);
    }

    /** @test */
    public function it_rejects_token_with_invalid_base64()
    {
        $invalidToken = 'invalid!!!.base64!!!.data!!!';
        $decoded = JwtHelper::decodeToken($invalidToken);

        $this->assertNull($decoded);
    }

    /** @test */
    public function it_rejects_token_with_wrong_algorithm()
    {
        // Criar um token manualmente com algoritmo diferente
        $header = base64_encode(json_encode(['alg' => 'HS512', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode(['sub' => $this->user->id]));
        $signature = base64_encode('fake_signature');

        $token = "$header.$payload.$signature";

        $decoded = JwtHelper::decodeToken($token);

        $this->assertNull($decoded);
    }

    /** @test */
    public function it_rejects_token_with_wrong_type()
    {
        // Criar um token com typ diferente
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'WRONG']));
        $payload = base64_encode(json_encode(['sub' => $this->user->id, 'exp' => time() + 3600]));
        $signature = base64_encode('fake_signature');

        $token = "$header.$payload.$signature";

        $decoded = JwtHelper::decodeToken($token);

        $this->assertNull($decoded);
    }

    /** @test */
    public function it_rejects_none_algorithm()
    {
        // Tentar usar algoritmo "none" (vulnerabilidade conhecida)
        $header = base64_encode(json_encode(['alg' => 'none', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode(['sub' => $this->user->id]));

        $token = "$header.$payload.";

        $decoded = JwtHelper::decodeToken($token);

        $this->assertNull($decoded);
    }

    /** @test */
    public function jti_is_unique_for_each_token()
    {
        $token1 = JwtHelper::generateToken($this->user);
        $token2 = JwtHelper::generateToken($this->user);

        $decoded1 = JwtHelper::decodeToken($token1);
        $decoded2 = JwtHelper::decodeToken($token2);

        $this->assertNotEquals($decoded1['jti'], $decoded2['jti']);
    }

    /** @test */
    public function iat_and_nbf_are_set_to_current_time()
    {
        $beforeTime = time();
        $token = JwtHelper::generateToken($this->user);
        $afterTime = time();

        $decoded = JwtHelper::decodeToken($token);

        $this->assertGreaterThanOrEqual($beforeTime, $decoded['iat']);
        $this->assertLessThanOrEqual($afterTime, $decoded['iat']);
        $this->assertEquals($decoded['iat'], $decoded['nbf']);
    }

    /** @test */
    public function it_stores_user_type_in_token()
    {
        $candidatoUser = User::factory()->create(['tipo_usuario' => 'Candidato']);
        $instituicaoUser = User::factory()->create(['tipo_usuario' => 'Instituicao']);

        $token1 = JwtHelper::generateToken($candidatoUser);
        $token2 = JwtHelper::generateToken($instituicaoUser);

        $decoded1 = JwtHelper::decodeToken($token1);
        $decoded2 = JwtHelper::decodeToken($token2);

        $this->assertEquals('Candidato', $decoded1['tipo_usuario']);
        $this->assertEquals('Instituicao', $decoded2['tipo_usuario']);
    }

    /** @test */
    public function token_remains_valid_for_its_ttl()
    {
        $expectedExp = time() + 3600;
        $token = JwtHelper::generateToken($this->user, 3600); // 1 hora

        // Decodificar imediatamente
        $decoded = JwtHelper::decodeToken($token);
        $this->assertIsArray($decoded);

        // Verificar exp com margem de 5 segundos para timing
        $this->assertEqualsWithDelta($expectedExp, $decoded['exp'], 5);
    }

    /** @test */
    public function it_handles_malformed_json_in_header()
    {
        $header = base64_encode('{invalid json');
        $payload = base64_encode(json_encode(['sub' => 1]));
        $signature = base64_encode('sig');

        $token = "$header.$payload.$signature";

        $decoded = JwtHelper::decodeToken($token);

        $this->assertNull($decoded);
    }

    /** @test */
    public function it_handles_malformed_json_in_payload()
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode('{invalid json');
        $signature = base64_encode('sig');

        $token = "$header.$payload.$signature";

        $decoded = JwtHelper::decodeToken($token);

        $this->assertNull($decoded);
    }
}
