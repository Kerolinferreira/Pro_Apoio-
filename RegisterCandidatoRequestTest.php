<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\RegisterCandidatoRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RegisterCandidatoRequestTest extends TestCase
{
    private RegisterCandidatoRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new RegisterCandidatoRequest();
    }

    /** @test */
    public function it_always_authorizes_the_request(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    /**
     * @test
     * @dataProvider normalizationProvider
     */
    public function it_normalizes_data_before_validation(array $initialData, string $field, $expectedValue): void
    {
        $this->request->merge($initialData);

        // O método prepareForValidation é chamado internamente pelo Validator
        Validator::make($this->request->all(), $this->request->rules());

        $this->assertEquals($expectedValue, $this->request->input($field));
    }

    public static function normalizationProvider(): array
    {
        return [
            'normalizes email to lowercase' => [['email' => '  TESTE@Email.COM '], 'email', 'teste@email.com'],
            'normalizes cpf by removing mask' => [['cpf' => '123.456.789-00'], 'cpf', '12345678900'],
            'normalizes cep by removing mask' => [['cep' => '12345-678'], 'cep', '12345678'],
            'normalizes telefone by removing mask' => [['telefone' => '(11) 98765-4321'], 'telefone', '11987654321'],
            'maps escolaridade to nivel_escolaridade' => [['escolaridade' => 'Superior Completo'], 'nivel_escolaridade', 'Superior Completo'],
        ];
    }

    /**
     * @test
     * @dataProvider validationProvider
     */
    public function it_validates_request_rules(bool $shouldPass, array $data): void
    {
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        $this->assertEquals($shouldPass, $validator->passes(), $validator->errors()->first());
    }

    public static function validationProvider(): array
    {
        $validData = self::getValidData();

        return [
            'dados completamente válidos' => [true, $validData],

            // Testes de falha
            'nome ausente' => [false, array_merge($validData, ['nome' => ''])],
            'email inválido' => [false, array_merge($validData, ['email' => 'email-invalido'])],
            'senha muito curta' => [false, array_merge($validData, ['password' => '12345'])],
            'senha sem número' => [false, array_merge($validData, ['password' => 'senhafraca'])],
            'senha sem letra' => [false, array_merge($validData, ['password' => '12345678'])],
            'confirmação de senha não confere' => [false, array_merge($validData, ['password_confirmation' => 'outraSenha123'])],
            'cpf inválido' => [false, array_merge($validData, ['cpf' => '12345678900'])], // Dígito verificador inválido
            'cep ausente' => [false, array_merge($validData, ['cep' => ''])],
            'telefone ausente' => [false, array_merge($validData, ['telefone' => ''])],
            'escolaridade ausente' => [false, array_merge($validData, ['nivel_escolaridade' => ''])],
            'curso superior obrigatório para nível superior' => [
                false,
                array_merge($validData, ['nivel_escolaridade' => 'Superior Completo', 'curso_superior' => ''])
            ],
            'instituição de ensino obrigatória com curso superior' => [
                false,
                array_merge($validData, ['nivel_escolaridade' => 'Superior Completo', 'curso_superior' => 'Análise de Sistemas', 'instituicao_ensino' => ''])
            ],
        ];
    }

    /** @test */
    public function it_returns_custom_error_messages(): void
    {
        $messages = $this->request->messages();

        $this->assertEquals('E-mail já cadastrado.', $messages['email.unique']);
        $this->assertEquals('CPF já cadastrado.', $messages['cpf.unique']);
        $this->assertEquals('A confirmação de senha não confere.', $messages['password.confirmed']);
        $this->assertEquals('O campo curso superior é obrigatório para o nível selecionado.', $messages['curso_superior.required_if']);
    }

    /**
     * Helper para gerar um conjunto de dados válidos.
     */
    private static function getValidData(): array
    {
        return [
            'nome' => 'Candidato de Teste Válido',
            'email' => 'candidato@teste.com',
            'password' => 'senhaValida123',
            'password_confirmation' => 'senhaValida123',
            'cpf' => '15823635002', // CPF Válido
            'cep' => '12345-678',
            'telefone' => '(11) 99999-9999',
            'data_nascimento' => '1990-01-01',
            'cidade' => 'Cidade Teste',
            'estado' => 'SP',
            'nivel_escolaridade' => 'Médio Completo',
            'curso_superior' => null,
            'instituicao_ensino' => null,
            'experiencia' => 'Tenho experiência com testes.',
            'termos_aceite' => true,
        ];
    }
}
