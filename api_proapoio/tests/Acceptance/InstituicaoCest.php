<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class InstituicaoCest
{
    private $baseUrl = 'http://localhost:5174';
    private $authToken;

    public function _before(AcceptanceTester $I)
    {
        // Criar e autenticar uma instituição de teste
        $this->authToken = $this->createAndAuthenticateInstituicao($I);
    }

    // Teste de Visualização de Perfil Público
    public function testViewPublicProfile(AcceptanceTester $I)
    {
        $I->wantTo('visualizar o perfil público de uma instituição');

        $instituicaoId = $I->grabFromDatabase('instituicoes', 'id', ['cnpj' => '12345678000190']);

        $I->amOnPage($this->baseUrl . "/instituicoes/{$instituicaoId}");

        $I->see('Faculdade Exemplo');
        $I->see('contato@faculdade.com');
        $I->see('Sobre a instituição');
    }

    // Teste de Visualização de Perfil Próprio
    public function testViewOwnProfile(AcceptanceTester $I)
    {
        $I->wantTo('visualizar meu perfil de instituição');

        $I->amOnPage($this->baseUrl . '/instituicao/profile');

        $I->see('Meu Perfil');
        $I->see('Faculdade Exemplo');
        $I->see('12345678000190'); // CNPJ
    }

    // Teste de Atualização de Perfil
    public function testUpdateProfile(AcceptanceTester $I)
    {
        $I->wantTo('atualizar meu perfil de instituição');

        $I->amOnPage($this->baseUrl . '/instituicao/profile/editar');

        $I->fillField('nome_fantasia', 'Faculdade Exemplo Atualizado');
        $I->fillField('telefone', '1133334444');
        $I->fillField('descricao', 'Instituição de ensino superior de excelência');
        $I->fillField('site', 'https://faculdade.com.br');

        $I->click('Salvar');

        $I->see('Perfil atualizado com sucesso');
        $I->see('Faculdade Exemplo Atualizado');
    }

    // Teste de Upload de Logo
    public function testUploadLogo(AcceptanceTester $I)
    {
        $I->wantTo('fazer upload do logo da instituição');

        $I->amOnPage($this->baseUrl . '/instituicao/profile/editar');

        // Simular upload de arquivo
        $I->attachFile('logo', 'test-logo.png');
        $I->click('Enviar Logo');

        $I->see('Logo atualizado com sucesso');
        $I->seeElement('img.institution-logo');
    }

    // Teste de Alteração de Senha
    public function testChangePassword(AcceptanceTester $I)
    {
        $I->wantTo('alterar minha senha');

        $I->amOnPage($this->baseUrl . '/instituicao/profile/senha');

        $I->fillField('senha_atual', 'senha123');
        $I->fillField('nova_senha', 'novaSenha456');
        $I->fillField('confirmar_senha', 'novaSenha456');

        $I->click('Alterar Senha');

        $I->see('Senha alterada com sucesso');
    }

    // Teste de Atualização de Endereço
    public function testUpdateEndereco(AcceptanceTester $I)
    {
        $I->wantTo('atualizar o endereço da instituição');

        $I->amOnPage($this->baseUrl . '/instituicao/profile/endereco');

        $I->fillField('cep', '01310-100');
        $I->wait(1); // Aguardar busca automática do CEP

        $I->see('Av. Paulista'); // Rua preenchida automaticamente
        $I->fillField('numero', '1000');
        $I->fillField('complemento', 'Sala 101');

        $I->click('Salvar Endereço');

        $I->see('Endereço atualizado com sucesso');
    }

    // Teste de Busca de Candidatos
    public function testSearchCandidatos(AcceptanceTester $I)
    {
        $I->wantTo('buscar candidatos disponíveis');

        $I->amOnPage($this->baseUrl . '/candidatos');

        $I->see('Buscar Candidatos');

        // Filtrar por habilidades
        $I->fillField('habilidades', 'PHP, Laravel');
        $I->click('Buscar');

        $I->wait(1);

        $I->see('Resultados da busca');
        $I->see('candidato'); // Ver pelo menos um resultado
    }

    // Teste de Visualizar Perfil de Candidato
    public function testViewCandidatoProfile(AcceptanceTester $I)
    {
        $I->wantTo('visualizar o perfil de um candidato');

        // Criar um candidato de teste
        $candidatoId = $this->createTestCandidato($I);

        $I->amOnPage($this->baseUrl . "/candidatos/{$candidatoId}");

        $I->see('João Silva');
        $I->see('Experiências Profissionais');
        $I->see('Experiências Pessoais');
    }

    // Teste de Deletar Conta
    public function testDeleteAccount(AcceptanceTester $I)
    {
        $I->wantTo('deletar minha conta de instituição');

        $I->amOnPage($this->baseUrl . '/instituicao/profile/configuracoes');

        $I->click('Deletar Conta');
        $I->acceptPopup(); // Confirmar exclusão

        $I->fillField('senha', 'senha123'); // Confirmar com senha
        $I->click('Confirmar Exclusão');

        $I->see('Conta deletada com sucesso');
        $I->seeCurrentUrlEquals($this->baseUrl . '/');
    }

    // Teste de Visualizar Minhas Vagas
    public function testViewMinhasVagas(AcceptanceTester $I)
    {
        $I->wantTo('visualizar minhas vagas criadas');

        // Criar uma vaga de teste
        $this->createTestVaga($I);

        $I->amOnPage($this->baseUrl . '/vagas/minhas');

        $I->see('Minhas Vagas');
        $I->see('Desenvolvedor Web'); // Título da vaga
    }

    // Teste de Editar Perfil com Campos Opcionais
    public function testUpdateProfileWithOptionalFields(AcceptanceTester $I)
    {
        $I->wantTo('atualizar perfil com campos opcionais');

        $I->amOnPage($this->baseUrl . '/instituicao/profile/editar');

        $I->fillField('linkedin', 'https://linkedin.com/company/faculdade');
        $I->fillField('facebook', 'https://facebook.com/faculdade');
        $I->fillField('instagram', '@faculdade');

        $I->click('Salvar');

        $I->see('Perfil atualizado com sucesso');
    }

    // Métodos auxiliares
    private function createAndAuthenticateInstituicao(AcceptanceTester $I)
    {
        $userId = $I->haveInDatabase('usuarios', [
            'email' => 'contato@faculdade.com',
            'senha' => password_hash('senha123', PASSWORD_BCRYPT),
            'tipo' => 'instituicao',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $I->haveInDatabase('enderecos', [
            'cep' => '01310100',
            'logradouro' => 'Av. Paulista',
            'numero' => '1000',
            'bairro' => 'Bela Vista',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $enderecoId = $I->grabFromDatabase('enderecos', 'id', ['cep' => '01310100']);

        $I->haveInDatabase('instituicoes', [
            'usuario_id' => $userId,
            'endereco_id' => $enderecoId,
            'nome_fantasia' => 'Faculdade Exemplo',
            'razao_social' => 'Faculdade Exemplo LTDA',
            'cnpj' => '12345678000190',
            'telefone' => '1133334444',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Fazer login
        $I->amOnPage($this->baseUrl . '/login');
        $I->fillField('email', 'contato@faculdade.com');
        $I->fillField('senha', 'senha123');
        $I->click('Entrar');
        $I->wait(1);

        return 'fake_jwt_token';
    }

    private function createTestCandidato(AcceptanceTester $I)
    {
        $userId = $I->haveInDatabase('usuarios', [
            'email' => 'joao@email.com',
            'senha' => password_hash('senha123', PASSWORD_BCRYPT),
            'tipo' => 'candidato',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $candidatoId = $I->haveInDatabase('candidatos', [
            'usuario_id' => $userId,
            'nome' => 'João Silva',
            'cpf' => '12345678901',
            'telefone' => '11987654321',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $candidatoId;
    }

    private function createTestVaga(AcceptanceTester $I)
    {
        $instituicaoId = $I->grabFromDatabase('instituicoes', 'id', ['cnpj' => '12345678000190']);

        return $I->haveInDatabase('vagas', [
            'instituicao_id' => $instituicaoId,
            'titulo' => 'Desenvolvedor Web',
            'descricao' => 'Desenvolvimento de sistemas web',
            'requisitos' => 'PHP, Laravel, MySQL',
            'salario' => 5000.00,
            'status' => 'ativa',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}
