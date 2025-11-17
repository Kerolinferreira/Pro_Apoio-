<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class PropostaCest
{
    private $baseUrl = 'http://localhost:5174';

    // Teste de Criar Proposta (Instituição para Candidato)
    public function testCreatePropostaByInstituicao(AcceptanceTester $I)
    {
        $I->wantTo('criar uma proposta para um candidato como instituição');

        $this->loginAsInstituicao($I);

        $candidatoId = $this->createTestCandidato($I);
        $vagaId = $this->createTestVaga($I);

        $I->amOnPage($this->baseUrl . "/candidatos/{$candidatoId}");

        $I->click('Enviar Proposta');

        $I->selectOption('vaga_id', $vagaId);
        $I->fillField('mensagem', 'Gostaria de convidá-lo para esta vaga.');

        $I->click('Enviar');

        $I->see('Proposta enviada com sucesso');
        $I->see('Proposta pendente');
    }

    // Teste de Criar Proposta (Candidato para Vaga)
    public function testCreatePropostaByCandidato(AcceptanceTester $I)
    {
        $I->wantTo('candidatar-me a uma vaga');

        $this->loginAsCandidato($I);

        $vagaId = $this->createTestVaga($I);

        $I->amOnPage($this->baseUrl . "/vagas/{$vagaId}");

        $I->click('Candidatar-se');

        $I->fillField('mensagem', 'Tenho interesse nesta vaga.');

        $I->click('Enviar Candidatura');

        $I->see('Candidatura enviada com sucesso');
        $I->see('Aguardando resposta');
    }

    // Teste de Listar Propostas (Instituição)
    public function testListPropostasInstituicao(AcceptanceTester $I)
    {
        $I->wantTo('visualizar propostas enviadas como instituição');

        $this->loginAsInstituicao($I);

        // Criar proposta de teste
        $this->createTestProposta($I);

        $I->amOnPage($this->baseUrl . '/propostas');

        $I->see('Minhas Propostas');
        $I->see('João Silva'); // Nome do candidato
        $I->see('Pendente');
    }

    // Teste de Listar Propostas (Candidato)
    public function testListPropostasCandidato(AcceptanceTester $I)
    {
        $I->wantTo('visualizar propostas recebidas como candidato');

        $this->loginAsCandidato($I);

        // Criar proposta de teste
        $this->createTestProposta($I);

        $I->amOnPage($this->baseUrl . '/propostas');

        $I->see('Propostas Recebidas');
        $I->see('Desenvolvedor Web'); // Título da vaga
        $I->see('Pendente');
    }

    // Teste de Visualizar Detalhes da Proposta
    public function testViewPropostaDetails(AcceptanceTester $I)
    {
        $I->wantTo('visualizar detalhes de uma proposta');

        $this->loginAsCandidato($I);

        $propostaId = $this->createTestProposta($I);

        $I->amOnPage($this->baseUrl . "/propostas/{$propostaId}");

        $I->see('Detalhes da Proposta');
        $I->see('Desenvolvedor Web');
        $I->see('Instituição Teste');
        $I->see('Gostaria de convidá-lo');
        $I->see('Status: Pendente');
    }

    // Teste de Aceitar Proposta (Candidato)
    public function testAcceptProposta(AcceptanceTester $I)
    {
        $I->wantTo('aceitar uma proposta como candidato');

        $this->loginAsCandidato($I);

        $propostaId = $this->createTestProposta($I);

        $I->amOnPage($this->baseUrl . "/propostas/{$propostaId}");

        $I->click('Aceitar Proposta');
        $I->acceptPopup(); // Confirmar aceitação

        $I->see('Proposta aceita com sucesso');
        $I->see('Status: Aceita');
    }

    // Teste de Recusar Proposta (Candidato)
    public function testRejectProposta(AcceptanceTester $I)
    {
        $I->wantTo('recusar uma proposta como candidato');

        $this->loginAsCandidato($I);

        $propostaId = $this->createTestProposta($I);

        $I->amOnPage($this->baseUrl . "/propostas/{$propostaId}");

        $I->click('Recusar Proposta');

        $I->fillField('motivo', 'Não tenho interesse no momento.');
        $I->click('Confirmar Recusa');

        $I->see('Proposta recusada');
        $I->see('Status: Recusada');
    }

    // Teste de Cancelar Proposta (Instituição)
    public function testCancelProposta(AcceptanceTester $I)
    {
        $I->wantTo('cancelar uma proposta como instituição');

        $this->loginAsInstituicao($I);

        $propostaId = $this->createTestProposta($I);

        $I->amOnPage($this->baseUrl . "/propostas/{$propostaId}");

        $I->click('Cancelar Proposta');
        $I->acceptPopup(); // Confirmar cancelamento

        $I->see('Proposta cancelada com sucesso');
    }

    // Teste de Filtrar Propostas por Status
    public function testFilterPropostasByStatus(AcceptanceTester $I)
    {
        $I->wantTo('filtrar propostas por status');

        $this->loginAsCandidato($I);

        // Criar propostas com diferentes status
        $this->createTestPropostaWithStatus($I, 'pendente');
        $this->createTestPropostaWithStatus($I, 'aceita');

        $I->amOnPage($this->baseUrl . '/propostas');

        $I->selectOption('status', 'aceita');
        $I->click('Filtrar');

        $I->wait(1);

        $I->see('Aceita');
        $I->dontSee('Pendente');
    }

    // Teste de Notificação ao Criar Proposta
    public function testNotificationOnCreateProposta(AcceptanceTester $I)
    {
        $I->wantTo('verificar se candidato recebe notificação ao receber proposta');

        $this->loginAsInstituicao($I);

        $candidatoId = $this->createTestCandidato($I);
        $vagaId = $this->createTestVaga($I);

        // Criar proposta
        $I->amOnPage($this->baseUrl . "/candidatos/{$candidatoId}");
        $I->click('Enviar Proposta');
        $I->selectOption('vaga_id', $vagaId);
        $I->fillField('mensagem', 'Nova proposta de trabalho');
        $I->click('Enviar');

        // Fazer logout e login como candidato
        $I->click('Sair');
        $this->loginAsCandidato($I);

        // Verificar notificação
        $I->amOnPage($this->baseUrl . '/notificacoes');
        $I->see('Nova proposta recebida');
    }

    // Teste de Notificação ao Aceitar Proposta
    public function testNotificationOnAcceptProposta(AcceptanceTester $I)
    {
        $I->wantTo('verificar se instituição recebe notificação quando proposta é aceita');

        $this->loginAsCandidato($I);

        $propostaId = $this->createTestProposta($I);

        // Aceitar proposta
        $I->amOnPage($this->baseUrl . "/propostas/{$propostaId}");
        $I->click('Aceitar Proposta');
        $I->acceptPopup();

        // Fazer logout e login como instituição
        $I->click('Sair');
        $this->loginAsInstituicao($I);

        // Verificar notificação
        $I->amOnPage($this->baseUrl . '/notificacoes');
        $I->see('Proposta aceita');
    }

    // Teste de Limite de Propostas por Vaga
    public function testPropostaLimitPerVaga(AcceptanceTester $I)
    {
        $I->wantTo('verificar que não posso enviar proposta duplicada para mesma vaga');

        $this->loginAsCandidato($I);

        $vagaId = $this->createTestVaga($I);

        // Primeira proposta
        $I->amOnPage($this->baseUrl . "/vagas/{$vagaId}");
        $I->click('Candidatar-se');
        $I->fillField('mensagem', 'Primeira tentativa');
        $I->click('Enviar Candidatura');
        $I->see('Candidatura enviada com sucesso');

        // Tentar enviar segunda proposta para mesma vaga
        $I->amOnPage($this->baseUrl . "/vagas/{$vagaId}");
        $I->see('Você já se candidatou a esta vaga');
        $I->dontSeeElement('.btn-candidatar');
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
            'nome' => 'João Silva',
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

    private function createTestCandidato(AcceptanceTester $I)
    {
        $userId = $I->haveInDatabase('usuarios', [
            'email' => 'joao@email.com',
            'senha' => password_hash('senha123', PASSWORD_BCRYPT),
            'tipo' => 'candidato',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $I->haveInDatabase('candidatos', [
            'usuario_id' => $userId,
            'nome' => 'João Silva',
            'cpf' => '98765432100',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function createTestVaga(AcceptanceTester $I)
    {
        $instituicaoId = $I->grabFromDatabase('instituicoes', 'id', ['cnpj' => '12345678000190']);

        return $I->haveInDatabase('vagas', [
            'instituicao_id' => $instituicaoId,
            'titulo' => 'Desenvolvedor Web',
            'descricao' => 'Desenvolvimento de sistemas web',
            'requisitos' => 'PHP, Laravel',
            'salario' => 5000.00,
            'status' => 'ativa',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function createTestProposta(AcceptanceTester $I)
    {
        $candidatoId = $I->grabFromDatabase('candidatos', 'id', ['cpf' => '12345678901']);
        $vagaId = $this->createTestVaga($I);

        return $I->haveInDatabase('propostas', [
            'candidato_id' => $candidatoId,
            'vaga_id' => $vagaId,
            'mensagem' => 'Gostaria de convidá-lo para esta vaga.',
            'status' => 'pendente',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function createTestPropostaWithStatus(AcceptanceTester $I, $status)
    {
        $candidatoId = $I->grabFromDatabase('candidatos', 'id', ['cpf' => '12345678901']);
        $vagaId = $this->createTestVaga($I);

        return $I->haveInDatabase('propostas', [
            'candidato_id' => $candidatoId,
            'vaga_id' => $vagaId,
            'mensagem' => 'Proposta de teste',
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}
