<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Codeception\Util\Locator;

class AuthCest
{
    private $baseUrl = 'http://localhost:3074';

    public function _before(AcceptanceTester $I)
    {
        // Limpar banco de dados antes de cada teste
        $I->amOnPage('/');
    }

    // Teste de Registro de Candidato
    public function testRegisterCandidato(AcceptanceTester $I)
    {
        $I->wantTo('registrar um novo candidato');

        // Navegar para a página de registro
        $I->amOnPage($this->baseUrl . '/register/candidato');

        // Preencher formulário de registro
        $I->fillField('nome', 'João Silva');
        $I->fillField('email', 'joao.silva@email.com');
        $I->fillField('cpf', '12345678901');
        $I->fillField('senha', 'senha123');
        $I->fillField('senha_confirmacao', 'senha123');

        // Submeter formulário
        $I->click('Registrar');

        // Verificar se o registro foi bem-sucedido
        $I->see('Cadastro realizado com sucesso');
        $I->seeCurrentUrlEquals($this->baseUrl . '/login');
    }

    // Teste de Registro de Instituição
    public function testRegisterInstituicao(AcceptanceTester $I)
    {
        $I->wantTo('registrar uma nova instituição');

        $I->amOnPage($this->baseUrl . '/register/instituicao');

        $I->fillField('nome_fantasia', 'Faculdade Exemplo');
        $I->fillField('razao_social', 'Faculdade Exemplo LTDA');
        $I->fillField('cnpj', '12345678000190');
        $I->fillField('email', 'contato@faculdade.com');
        $I->fillField('senha', 'senha123');
        $I->fillField('senha_confirmacao', 'senha123');

        $I->click('Registrar');

        $I->see('Cadastro realizado com sucesso');
        $I->seeCurrentUrlEquals($this->baseUrl . '/login');
    }

    // Teste de Login
    public function testLogin(AcceptanceTester $I)
    {
        $I->wantTo('fazer login no sistema');

        // Primeiro criar um usuário
        $this->createTestUser($I);

        // Acessar página de login
        $I->amOnPage($this->baseUrl . '/login');

        $I->fillField('email', 'teste@email.com');
        $I->fillField('senha', 'senha123');

        $I->click('Entrar');

        // Verificar se foi redirecionado para dashboard
        $I->see('Dashboard');
        $I->seeInCurrentUrl('/dashboard');
    }

    // Teste de Verificação de Email
    public function testCheckEmail(AcceptanceTester $I)
    {
        $I->wantTo('verificar se email já está cadastrado');

        $I->amOnPage($this->baseUrl . '/register/candidato');

        $I->fillField('email', 'teste@email.com');
        $I->moveMouseOver('body'); // Trigger blur event

        // Aguardar resposta da verificação
        $I->wait(1);

        // Verificar mensagem de disponibilidade
        $I->see('Email disponível');
    }

    // Teste de Recuperação de Senha
    public function testForgotPassword(AcceptanceTester $I)
    {
        $I->wantTo('recuperar minha senha');

        // Criar usuário de teste
        $this->createTestUser($I);

        $I->amOnPage($this->baseUrl . '/forgot-password');

        $I->fillField('email', 'teste@email.com');
        $I->click('Enviar link de recuperação');

        $I->see('Link de recuperação enviado');
        $I->see('Verifique seu email');
    }

    // Teste de Reset de Senha
    public function testResetPassword(AcceptanceTester $I)
    {
        $I->wantTo('redefinir minha senha');

        // Criar usuário e gerar token
        $token = $this->createTestUserWithResetToken($I);

        $I->amOnPage($this->baseUrl . '/reset-password?token=' . $token);

        $I->fillField('email', 'teste@email.com');
        $I->fillField('senha', 'novaSenha123');
        $I->fillField('senha_confirmacao', 'novaSenha123');

        $I->click('Redefinir senha');

        $I->see('Senha redefinida com sucesso');
        $I->seeCurrentUrlEquals($this->baseUrl . '/login');
    }

    // Teste de Logout
    public function testLogout(AcceptanceTester $I)
    {
        $I->wantTo('fazer logout do sistema');

        // Fazer login primeiro
        $this->loginAsTestUser($I);

        // Clicar no botão de logout
        $I->click('Sair');

        // Verificar se foi redirecionado para página de login
        $I->seeCurrentUrlEquals($this->baseUrl . '/login');
        $I->see('Entrar');
    }

    // Teste de Verificação de CPF
    public function testCheckCpf(AcceptanceTester $I)
    {
        $I->wantTo('verificar se CPF já está cadastrado');

        $I->amOnPage($this->baseUrl . '/register/candidato');

        $I->fillField('cpf', '12345678901');
        $I->moveMouseOver('body'); // Trigger blur event

        $I->wait(1);

        $I->see('CPF disponível');
    }

    // Teste de Verificação de CNPJ
    public function testCheckCnpj(AcceptanceTester $I)
    {
        $I->wantTo('verificar se CNPJ já está cadastrado');

        $I->amOnPage($this->baseUrl . '/register/instituicao');

        $I->fillField('cnpj', '12345678000190');
        $I->moveMouseOver('body'); // Trigger blur event

        $I->wait(1);

        $I->see('CNPJ disponível');
    }

    // Métodos auxiliares
    private function createTestUser(AcceptanceTester $I)
    {
        // Criar usuário via API ou banco de dados
        $I->haveInDatabase('usuarios', [
            'email' => 'teste@email.com',
            'senha' => password_hash('senha123', PASSWORD_BCRYPT),
            'tipo' => 'candidato'
        ]);
    }

    private function createTestUserWithResetToken(AcceptanceTester $I)
    {
        $token = bin2hex(random_bytes(32));

        $I->haveInDatabase('usuarios', [
            'email' => 'teste@email.com',
            'senha' => password_hash('senha123', PASSWORD_BCRYPT),
            'tipo' => 'candidato'
        ]);

        $I->haveInDatabase('password_reset_tokens', [
            'email' => 'teste@email.com',
            'token' => hash('sha256', $token),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $token;
    }

    private function loginAsTestUser(AcceptanceTester $I)
    {
        $this->createTestUser($I);

        $I->amOnPage($this->baseUrl . '/login');
        $I->fillField('email', 'teste@email.com');
        $I->fillField('senha', 'senha123');
        $I->click('Entrar');
        $I->wait(1);
    }
}
