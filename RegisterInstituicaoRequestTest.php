<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\RegisterInstituicaoRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RegisterInstituicaoRequestTest extends TestCase
{
    private RegisterInstituicaoRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new RegisterInstituicaoRequest();
    }

    /**
     * @test
     * @description Garante que a autorização sempre retorna true.
     */
    public function it_always_authorizes_the_request(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    /**
     * @test
     * @description Testa a normalização dos dados antes da validação.
     * @dataProvider normalizationProvider
     */
    public function it_normalizes_data_before_validation(array $initialData, string $field, $expectedValue): void
    {
        $this->request->merge($initialData);

        // O método prepareForValidation é protected, então o validamos indiretamente
        // através do objeto Validator, que o invoca.
        $validator = Validator::make($this->request->all(), $this->request->rules());

        // Acessamos os dados após a normalização
        $normalizedData = $this->request->all();

        $this->assertArrayHasKey($field, $normalizedData);
        $this->assertEquals($expectedValue, $normalizedData[$field]);
    }

    public static function normalizationProvider(): array
    {
        return [
            'CNPJ com máscara' => [['cnpj' => '12.345.678/0001-99'], 'cnpj', '12345678000199'],
            'CEP com máscara' => [['cep' => '12345-678'], 'cep', '12345678'],
            'Email com maiúsculas e espaços' => [['email' => '  TESTE@Email.COM '], 'email', 'teste@email.com'],
            'Níveis oferecidos como array' => [
                ['niveis_oferecidos' => ['Infantil', 'Fundamental']],
                'niveis_oferecidos',
                '["Infantil","Fundamental"]'
            ],
            'Níveis oferecidos já como JSON' => [
                ['niveis_oferecidos' => '["Infantil"]'],
                'niveis_oferecidos',
                '["Infantil"]'
            ],
        ];
    }

    /**
     * @test
     * @description Testa as regras de validação com diferentes cenários.
     * @dataProvider validationProvider
     */
    public function it_validates_request_rules(bool $shouldPass, array $data): void
    {
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        $this->assertEquals($shouldPass, $validator->passes());
    }

    public static function validationProvider(): array
    {
        $validData = self::getValidData();

        return [
            'Dados completamente válidos' => [true, $validData],

            // Testes para 'nome'
            'Nome ausente' => [false, array_merge($validData, ['nome' => ''])],
            'Nome muito curto' => [false, array_merge($validData, ['nome' => 'Ab'])],

            // Testes para 'email'
            'Email ausente' => [false, array_merge($validData, ['email' => ''])],
            'Email inválido' => [false, array_merge($validData, ['email' => 'email-invalido'])],

            // Testes para 'password'
            'Senha ausente' => [false, array_merge($validData, ['password' => ''])],
            'Senha muito curta' => [false, array_merge($validData, ['password' => '1234567'])],
            'Senha sem número' => [false, array_merge($validData, ['password' => 'senhafraca'])],
            'Senha sem letra' => [false, array_merge($validData, ['password' => '12345678'])],
            'Confirmação de senha não confere' => [false, array_merge($validData, ['password_confirmation' => 'outraSenha123'])],

            // Testes para 'cnpj'
            'CNPJ ausente' => [false, array_merge($validData, ['cnpj' => ''])],
            'CNPJ inválido' => [false, array_merge($validData, ['cnpj' => '11.111.111/1111-11'])], // Dígito verificador inválido

            // Testes para 'cep'
            'CEP ausente' => [false, array_merge($validData, ['cep' => ''])],
            'CEP com formato inválido' => [false, array_merge($validData, ['cep' => '1234567'])],

            // Testes para 'codigo_inep'
            'Código INEP válido' => [true, array_merge($validData, ['codigo_inep' => '12345678'])],
            'Código INEP nulo' => [true, array_merge($validData, ['codigo_inep' => null])],
            'Código INEP com formato inválido' => [false, array_merge($validData, ['codigo_inep' => '12345'])],

            // Testes para 'niveis_oferecidos'
            'Níveis oferecidos ausente' => [false, array_merge($validData, ['niveis_oferecidos' => ''])],
            'Níveis oferecidos não é JSON' => [false, array_merge($validData, ['niveis_oferecidos' => 'nao-e-json'])],

            // Testes para 'termos_aceite'
            'Termos de aceite não aceito (false)' => [false, array_merge($validData, ['termos_aceite' => false])],
            'Termos de aceite não aceito (0)' => [false, array_merge($validData, ['termos_aceite' => '0'])],
            'Termos de aceite ausente' => [false, array_diff_key($validData, ['termos_aceite' => ''])],
        ];
    }

    /**
     * @test
     * @description Verifica se a mensagem de erro customizada para CNPJ único é retornada.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_returns_custom_message_for_unique_cnpj_rule(): void
    {
        // Mock da facade Validator para forçar a falha da regra 'unique'
        \Illuminate\Support\Facades\Validator::shouldReceive('make')->andReturnUsing(function ($data, $rules, $messages) {
            $validator = \Illuminate\Support\Facades\Validator::getFacadeRoot()->make($data, $rules, $messages);
            $validator->errors()->add('cnpj', $messages['cnpj.unique']);
            return $validator;
        });

        $validator = Validator::make(['cnpj' => '51.363.225/0001-07'], $this->request->rules(), $this->request->messages());
        $this->assertEquals('CNPJ já cadastrado.', $validator->errors()->first('cnpj'));
    }

    /**
     * @test
     * @description Verifica se a mensagem de erro customizada para senha confirmada é retornada.
     */
    public function it_returns_custom_message_for_password_confirmation_rule(): void
    {
        $data = self::getValidData();
        $data['password_confirmation'] = 'senhaDiferente123';

        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        $this->assertTrue($validator->fails());
        $this->assertEquals('A confirmação de senha não confere.', $validator->errors()->first('password'));
    }

    /**
     * @test
     * @description Verifica se a mensagem de erro customizada para termos de aceite é retornada.
     */
    public function it_returns_custom_message_for_accepted_terms_rule(): void
    {
        $data = self::getValidData();
        $data['termos_aceite'] = 'no'; // Valor que não passa na regra 'accepted'

        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        $this->assertTrue($validator->fails());
        $this->assertEquals('Você precisa aceitar os termos de uso para se cadastrar.', $validator->errors()->first('termos_aceite'));
    }

    /**
     * Helper para gerar um conjunto de dados válidos.
     */
    private static function getValidData(): array
    {
        return [
            'nome' => 'Instituição de Teste Válida',
            'email' => 'contato@instituicaoteste.com',
            'password' => 'senhaValida123',
            'password_confirmation' => 'senhaValida123',
            'cnpj' => '51.363.225/0001-07', // CNPJ válido
            'razao_social' => 'Instituição de Teste LTDA',
            'nome_fantasia' => 'Instituição Teste',
            'codigo_inep' => '12345678',
            'cep' => '12345-678',
            'logradouro' => 'Rua dos Testes',
            'bairro' => 'Bairro de Teste',
            'cidade' => 'Cidade Teste',
            'estado' => 'SP',
            'numero' => '123',
            'tipo_instituicao' => 'Privada',
            'niveis_oferecidos' => json_encode(['Ensino Médio', 'Ensino Superior']),
            'nome_responsavel' => 'Responsável Teste',
            'funcao_responsavel' => 'Diretor',
            'termos_aceite' => '1', // Valor que passa na regra 'accepted'
        ];
    }
}