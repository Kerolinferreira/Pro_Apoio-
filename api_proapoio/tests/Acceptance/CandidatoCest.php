<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class CandidatoCest
{
    private $baseUrl = 'http://localhost:3074';
    private $authToken;

    public function _before(AcceptanceTester $I)
    {
        // Criar e autenticar um candidato de teste
        $this->authToken = $this->createAndAuthenticateCandidato($I);
    }

    // Teste de Visualização de Perfil
    public function testViewProfile(AcceptanceTester $I)
    {
        $I->wantTo('visualizar meu perfil de candidato');

        $I->amOnPage($this->baseUrl . '/candidato/profile');

        $I->see('Meu Perfil');
        $I->see('João Silva');
        $I->see('joao@email.com');
    }

    // Teste de Atualização de Perfil
    public function testUpdateProfile(AcceptanceTester $I)
    {
        $I->wantTo('atualizar meu perfil');

        $I->amOnPage($this->baseUrl . '/candidato/profile/editar');

        $I->fillField('nome', 'João Silva Atualizado');
        $I->fillField('telefone', '11987654321');
        $I->fillField('data_nascimento', '1990-01-01');

        $I->click('Salvar');

        $I->see('Perfil atualizado com sucesso');
        $I->see('João Silva Atualizado');
    }

    // Teste de Upload de Foto
    public function testUploadFoto(AcceptanceTester $I)
    {
        $I->wantTo('fazer upload de uma foto de perfil');

        $I->amOnPage($this->baseUrl . '/candidato/profile/editar');

        // Simular upload de arquivo
        $I->attachFile('foto', 'test-photo.jpg');
        $I->click('Enviar Foto');

        $I->see('Foto atualizada com sucesso');
        $I->seeElement('img.profile-photo');
    }

    // Teste de Alteração de Senha
    public function testChangePassword(AcceptanceTester $I)
    {
        $I->wantTo('alterar minha senha');

        $I->amOnPage($this->baseUrl . '/candidato/profile/senha');

        $I->fillField('senha_atual', 'senha123');
        $I->fillField('nova_senha', 'novaSenha456');
        $I->fillField('confirmar_senha', 'novaSenha456');

        $I->click('Alterar Senha');

        $I->see('Senha alterada com sucesso');
    }

    // Teste de Adicionar Experiência Profissional
    public function testAddExperienciaProfissional(AcceptanceTester $I)
    {
        $I->wantTo('adicionar experiência profissional');

        $I->amOnPage($this->baseUrl . '/candidato/profile/experiencias');

        $I->click('Adicionar Experiência Profissional');

        $I->fillField('empresa', 'Empresa XYZ');
        $I->fillField('cargo', 'Desenvolvedor');
        $I->fillField('data_inicio', '2020-01-01');
        $I->fillField('data_fim', '2023-12-31');
        $I->fillField('descricao', 'Desenvolvimento de sistemas web');

        $I->click('Salvar');

        $I->see('Experiência adicionada com sucesso');
        $I->see('Empresa XYZ');
        $I->see('Desenvolvedor');
    }

    // Teste de Editar Experiência Profissional
    public function testEditExperienciaProfissional(AcceptanceTester $I)
    {
        $I->wantTo('editar uma experiência profissional');

        // Primeiro adicionar uma experiência
        $expId = $this->addExperienciaProfissional($I);

        $I->amOnPage($this->baseUrl . '/candidato/profile/experiencias');

        $I->click("Editar experiência {$expId}");

        $I->fillField('cargo', 'Desenvolvedor Sênior');
        $I->click('Salvar');

        $I->see('Experiência atualizada com sucesso');
        $I->see('Desenvolvedor Sênior');
    }

    // Teste de Deletar Experiência Profissional
    public function testDeleteExperienciaProfissional(AcceptanceTester $I)
    {
        $I->wantTo('deletar uma experiência profissional');

        $expId = $this->addExperienciaProfissional($I);

        $I->amOnPage($this->baseUrl . '/candidato/profile/experiencias');

        $I->click("Deletar experiência {$expId}");
        $I->acceptPopup(); // Confirmar exclusão

        $I->see('Experiência removida com sucesso');
        $I->dontSee('Empresa XYZ');
    }

    // Teste de Adicionar Experiência Pessoal
    public function testAddExperienciaPessoal(AcceptanceTester $I)
    {
        $I->wantTo('adicionar experiência pessoal (deficiência)');

        $I->amOnPage($this->baseUrl . '/candidato/profile/experiencias-pessoais');

        $I->click('Adicionar Experiência Pessoal');

        $I->selectOption('deficiencia_id', '1'); // Visual
        $I->fillField('descricao', 'Deficiência visual parcial');

        $I->click('Salvar');

        $I->see('Experiência pessoal adicionada com sucesso');
        $I->see('Deficiência visual');
    }

    // Teste de Deletar Experiência Pessoal
    public function testDeleteExperienciaPessoal(AcceptanceTester $I)
    {
        $I->wantTo('deletar uma experiência pessoal');

        $expId = $this->addExperienciaPessoal($I);

        $I->amOnPage($this->baseUrl . '/candidato/profile/experiencias-pessoais');

        $I->click("Deletar experiência pessoal {$expId}");
        $I->acceptPopup();

        $I->see('Experiência pessoal removida com sucesso');
    }

    // Teste de Visualizar Vagas Salvas
    public function testViewVagasSalvas(AcceptanceTester $I)
    {
        $I->wantTo('visualizar minhas vagas salvas');

        // Salvar uma vaga primeiro
        $this->saveVaga($I, 1);

        $I->amOnPage($this->baseUrl . '/candidatos/me/vagas-salvas');

        $I->see('Vagas Salvas');
        $I->see('Desenvolvedor Web'); // Nome da vaga salva
    }

    // Teste de Deletar Conta
    public function testDeleteAccount(AcceptanceTester $I)
    {
        $I->wantTo('deletar minha conta');

        $I->amOnPage($this->baseUrl . '/candidato/profile/configuracoes');

        $I->click('Deletar Conta');
        $I->acceptPopup(); // Confirmar exclusão

        $I->fillField('senha', 'senha123'); // Confirmar com senha
        $I->click('Confirmar Exclusão');

        $I->see('Conta deletada com sucesso');
        $I->seeCurrentUrlEquals($this->baseUrl . '/');
    }

    // Métodos auxiliares
    private function createAndAuthenticateCandidato(AcceptanceTester $I)
    {
        $userId = $I->haveInDatabase('usuarios', [
            'email' => 'joao@email.com',
            'senha' => password_hash('senha123', PASSWORD_BCRYPT),
            'tipo' => 'candidato',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $I->haveInDatabase('candidatos', [
            'usuario_id' => $userId,
            'nome' => 'João Silva',
            'cpf' => '12345678901',
            'telefone' => '11987654321',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Fazer login e obter token
        $I->amOnPage($this->baseUrl . '/login');
        $I->fillField('email', 'joao@email.com');
        $I->fillField('senha', 'senha123');
        $I->click('Entrar');
        $I->wait(1);

        return 'fake_jwt_token';
    }

    private function addExperienciaProfissional(AcceptanceTester $I)
    {
        $candidatoId = $I->grabFromDatabase('candidatos', 'id', ['cpf' => '12345678901']);

        return $I->haveInDatabase('experiencias_profissionais', [
            'candidato_id' => $candidatoId,
            'empresa' => 'Empresa XYZ',
            'cargo' => 'Desenvolvedor',
            'data_inicio' => '2020-01-01',
            'data_fim' => '2023-12-31',
            'descricao' => 'Desenvolvimento de sistemas web',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function addExperienciaPessoal(AcceptanceTester $I)
    {
        $candidatoId = $I->grabFromDatabase('candidatos', 'id', ['cpf' => '12345678901']);

        return $I->haveInDatabase('experiencias_pessoais', [
            'candidato_id' => $candidatoId,
            'deficiencia_id' => 1,
            'descricao' => 'Deficiência visual parcial',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function saveVaga(AcceptanceTester $I, $vagaId)
    {
        $candidatoId = $I->grabFromDatabase('candidatos', 'id', ['cpf' => '12345678901']);

        $I->haveInDatabase('vagas_salvas', [
            'candidato_id' => $candidatoId,
            'vaga_id' => $vagaId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}
