<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class VagaCest
{
    private $baseUrl = 'http://localhost:3074';

    // Teste de Listagem Pública de Vagas
    public function testListVagas(AcceptanceTester $I)
    {
        $I->wantTo('visualizar lista pública de vagas');

        // Criar vagas de teste
        $this->createTestVagas($I);

        $I->amOnPage($this->baseUrl . '/vagas');

        $I->see('Vagas Disponíveis');
        $I->see('Desenvolvedor Web');
        $I->see('Designer Gráfico');
    }

    // Teste de Visualização Pública de Vaga
    public function testViewVagaPublic(AcceptanceTester $I)
    {
        $I->wantTo('visualizar detalhes de uma vaga');

        $vagaId = $this->createTestVaga($I);

        $I->amOnPage($this->baseUrl . "/vagas/{$vagaId}");

        $I->see('Desenvolvedor Web');
        $I->see('Desenvolvimento de sistemas web');
        $I->see('PHP, Laravel, MySQL');
        $I->see('R$ 5.000,00');
    }

    // Teste de Criação de Vaga (Instituição)
    public function testCreateVaga(AcceptanceTester $I)
    {
        $I->wantTo('criar uma nova vaga como instituição');

        $this->loginAsInstituicao($I);

        $I->amOnPage($this->baseUrl . '/vagas/criar');

        $I->fillField('titulo', 'Analista de Sistemas');
        $I->fillField('descricao', 'Análise e desenvolvimento de sistemas');
        $I->fillField('requisitos', 'Java, Spring Boot, SQL');
        $I->fillField('salario', '6000.00');
        $I->fillField('carga_horaria', '40 horas semanais');
        $I->fillField('tipo_contrato', 'CLT');
        $I->selectOption('status', 'ativa');

        $I->click('Criar Vaga');

        $I->see('Vaga criada com sucesso');
        $I->see('Analista de Sistemas');
    }

    // Teste de Edição de Vaga
    public function testEditVaga(AcceptanceTester $I)
    {
        $I->wantTo('editar uma vaga existente');

        $this->loginAsInstituicao($I);

        $vagaId = $this->createTestVaga($I);

        $I->amOnPage($this->baseUrl . "/vagas/{$vagaId}/editar");

        $I->fillField('titulo', 'Desenvolvedor Web Sênior');
        $I->fillField('salario', '7000.00');

        $I->click('Salvar');

        $I->see('Vaga atualizada com sucesso');
        $I->see('Desenvolvedor Web Sênior');
        $I->see('R$ 7.000,00');
    }

    // Teste de Pausar Vaga
    public function testPauseVaga(AcceptanceTester $I)
    {
        $I->wantTo('pausar uma vaga ativa');

        $this->loginAsInstituicao($I);

        $vagaId = $this->createTestVaga($I);

        $I->amOnPage($this->baseUrl . '/vagas/minhas');

        $I->click("Pausar vaga {$vagaId}");

        $I->see('Vaga pausada com sucesso');
        $I->see('Status: Pausada');
    }

    // Teste de Fechar Vaga
    public function testCloseVaga(AcceptanceTester $I)
    {
        $I->wantTo('fechar uma vaga');

        $this->loginAsInstituicao($I);

        $vagaId = $this->createTestVaga($I);

        $I->amOnPage($this->baseUrl . '/vagas/minhas');

        $I->click("Fechar vaga {$vagaId}");
        $I->acceptPopup(); // Confirmar fechamento

        $I->see('Vaga fechada com sucesso');
        $I->see('Status: Fechada');
    }

    // Teste de Deletar Vaga
    public function testDeleteVaga(AcceptanceTester $I)
    {
        $I->wantTo('deletar uma vaga');

        $this->loginAsInstituicao($I);

        $vagaId = $this->createTestVaga($I);

        $I->amOnPage($this->baseUrl . '/vagas/minhas');

        $I->click("Deletar vaga {$vagaId}");
        $I->acceptPopup(); // Confirmar exclusão

        $I->see('Vaga deletada com sucesso');
        $I->dontSee('Desenvolvedor Web');
    }

    // Teste de Alterar Status da Vaga
    public function testChangeVagaStatus(AcceptanceTester $I)
    {
        $I->wantTo('alterar o status de uma vaga');

        $this->loginAsInstituicao($I);

        $vagaId = $this->createTestVaga($I);

        $I->amOnPage($this->baseUrl . "/vagas/{$vagaId}/status");

        $I->selectOption('status', 'pausada');
        $I->click('Atualizar Status');

        $I->see('Status atualizado com sucesso');
        $I->see('Status: Pausada');
    }

    // Teste de Visualizar Minhas Vagas (Instituição)
    public function testViewMinhasVagas(AcceptanceTester $I)
    {
        $I->wantTo('visualizar minhas vagas como instituição');

        $this->loginAsInstituicao($I);

        $this->createTestVaga($I);

        $I->amOnPage($this->baseUrl . '/vagas/minhas');

        $I->see('Minhas Vagas');
        $I->see('Desenvolvedor Web');
        $I->see('Ativa');
    }

    // Teste de Salvar Vaga (Candidato)
    public function testSaveVaga(AcceptanceTester $I)
    {
        $I->wantTo('salvar uma vaga como candidato');

        $this->loginAsCandidato($I);

        $vagaId = $this->createTestVaga($I);

        $I->amOnPage($this->baseUrl . "/vagas/{$vagaId}");

        $I->click('Salvar Vaga');

        $I->see('Vaga salva com sucesso');
        $I->seeElement('.btn-vaga-salva'); // Botão muda para "Remover"
    }

    // Teste de Remover Vaga Salva (Candidato)
    public function testUnsaveVaga(AcceptanceTester $I)
    {
        $I->wantTo('remover uma vaga salva');

        $this->loginAsCandidato($I);

        $vagaId = $this->createTestVaga($I);

        // Primeiro salvar a vaga
        $this->saveVaga($I, $vagaId);

        $I->amOnPage($this->baseUrl . "/vagas/{$vagaId}");

        $I->click('Remover Vaga');

        $I->see('Vaga removida dos salvos');
        $I->seeElement('.btn-salvar-vaga'); // Botão volta para "Salvar"
    }

    // Teste de Filtrar Vagas por Título
    public function testFilterVagasByTitulo(AcceptanceTester $I)
    {
        $I->wantTo('filtrar vagas por título');

        $this->createTestVagas($I);

        $I->amOnPage($this->baseUrl . '/vagas');

        $I->fillField('search', 'Desenvolvedor');
        $I->click('Buscar');

        $I->wait(1);

        $I->see('Desenvolvedor Web');
        $I->dontSee('Designer Gráfico');
    }

    // Teste de Filtrar Vagas por Salário
    public function testFilterVagasBySalario(AcceptanceTester $I)
    {
        $I->wantTo('filtrar vagas por faixa salarial');

        $this->createTestVagas($I);

        $I->amOnPage($this->baseUrl . '/vagas');

        $I->fillField('salario_min', '5000');
        $I->fillField('salario_max', '6000');
        $I->click('Buscar');

        $I->wait(1);

        $I->see('Desenvolvedor Web'); // Salário 5000
        $I->dontSee('Designer Gráfico'); // Salário fora da faixa
    }

    // Métodos auxiliares
    private function loginAsInstituicao(AcceptanceTester $I)
    {
        $userId = $I->haveInDatabase('usuarios', [
            'email' => 'instituicao@email.com',
            'senha' => password_hash('senha123', PASSWORD_BCRYPT),
            'tipo' => 'instituicao',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $enderecoId = $I->haveInDatabase('enderecos', [
            'cep' => '01310100',
            'logradouro' => 'Av. Paulista',
            'numero' => '1000',
            'bairro' => 'Bela Vista',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $I->haveInDatabase('instituicoes', [
            'usuario_id' => $userId,
            'endereco_id' => $enderecoId,
            'nome_fantasia' => 'Instituição Teste',
            'razao_social' => 'Instituição Teste LTDA',
            'cnpj' => '12345678000190',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $I->amOnPage($this->baseUrl . '/login');
        $I->fillField('email', 'instituicao@email.com');
        $I->fillField('senha', 'senha123');
        $I->click('Entrar');
        $I->wait(1);
    }

    private function loginAsCandidato(AcceptanceTester $I)
    {
        $userId = $I->haveInDatabase('usuarios', [
            'email' => 'candidato@email.com',
            'senha' => password_hash('senha123', PASSWORD_BCRYPT),
            'tipo' => 'candidato',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $I->haveInDatabase('candidatos', [
            'usuario_id' => $userId,
            'nome' => 'Candidato Teste',
            'cpf' => '12345678901',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $I->amOnPage($this->baseUrl . '/login');
        $I->fillField('email', 'candidato@email.com');
        $I->fillField('senha', 'senha123');
        $I->click('Entrar');
        $I->wait(1);
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
            'carga_horaria' => '40 horas semanais',
            'tipo_contrato' => 'CLT',
            'status' => 'ativa',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function createTestVagas(AcceptanceTester $I)
    {
        $instituicaoId = $I->grabFromDatabase('instituicoes', 'id', ['cnpj' => '12345678000190']);

        $I->haveInDatabase('vagas', [
            'instituicao_id' => $instituicaoId,
            'titulo' => 'Desenvolvedor Web',
            'descricao' => 'Desenvolvimento de sistemas web',
            'requisitos' => 'PHP, Laravel',
            'salario' => 5000.00,
            'status' => 'ativa',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $I->haveInDatabase('vagas', [
            'instituicao_id' => $instituicaoId,
            'titulo' => 'Designer Gráfico',
            'descricao' => 'Criação de materiais gráficos',
            'requisitos' => 'Photoshop, Illustrator',
            'salario' => 3500.00,
            'status' => 'ativa',
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
