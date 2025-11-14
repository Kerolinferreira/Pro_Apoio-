<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class DashboardCest
{
    private $baseUrl = 'http://localhost:3074';

    // Teste de Dashboard do Candidato
    public function testCandidatoDashboard(AcceptanceTester $I)
    {
        $I->wantTo('visualizar o dashboard como candidato');

        $this->loginAsCandidato($I);

        // Criar dados para o dashboard
        $this->createCandidatoDashboardData($I);

        $I->amOnPage($this->baseUrl . '/dashboard/candidato');

        $I->see('Dashboard do Candidato');

        // Verificar estatísticas
        $I->see('Propostas Recebidas');
        $I->see('3'); // Total de propostas

        $I->see('Propostas Aceitas');
        $I->see('1');

        $I->see('Propostas Pendentes');
        $I->see('2');

        $I->see('Vagas Salvas');
        $I->see('2');

        // Verificar gráficos/cards
        $I->seeElement('.card-propostas');
        $I->seeElement('.card-vagas-salvas');
    }

    // Teste de Dashboard da Instituição
    public function testInstituicaoDashboard(AcceptanceTester $I)
    {
        $I->wantTo('visualizar o dashboard como instituição');

        $this->loginAsInstituicao($I);

        // Criar dados para o dashboard
        $this->createInstituicaoDashboardData($I);

        $I->amOnPage($this->baseUrl . '/dashboard/instituicao');

        $I->see('Dashboard da Instituição');

        // Verificar estatísticas
        $I->see('Vagas Ativas');
        $I->see('2');

        $I->see('Total de Vagas');
        $I->see('4');

        $I->see('Propostas Enviadas');
        $I->see('5');

        $I->see('Candidatos Aceitos');
        $I->see('2');

        // Verificar cards
        $I->seeElement('.card-vagas');
        $I->seeElement('.card-propostas');
    }

    // Teste de Visualização de Propostas Recentes no Dashboard (Candidato)
    public function testRecentPropostasCandidato(AcceptanceTester $I)
    {
        $I->wantTo('visualizar propostas recentes no dashboard');

        $this->loginAsCandidato($I);

        $this->createCandidatoDashboardData($I);

        $I->amOnPage($this->baseUrl . '/dashboard/candidato');

        $I->see('Propostas Recentes');
        $I->see('Desenvolvedor Web');
        $I->see('Designer Gráfico');

        // Verificar botões de ação
        $I->seeElement('.btn-ver-proposta');
    }

    // Teste de Visualização de Vagas Recentes no Dashboard (Instituição)
    public function testRecentVagasInstituicao(AcceptanceTester $I)
    {
        $I->wantTo('visualizar vagas recentes no dashboard');

        $this->loginAsInstituicao($I);

        $this->createInstituicaoDashboardData($I);

        $I->amOnPage($this->baseUrl . '/dashboard/instituicao');

        $I->see('Vagas Recentes');
        $I->see('Desenvolvedor Web');
        $I->see('Ativa');

        // Verificar ações
        $I->seeElement('.btn-editar-vaga');
        $I->seeElement('.btn-ver-candidatos');
    }

    // Teste de Gráfico de Propostas por Status (Candidato)
    public function testPropostasChartCandidato(AcceptanceTester $I)
    {
        $I->wantTo('visualizar gráfico de propostas por status');

        $this->loginAsCandidato($I);

        $this->createCandidatoDashboardData($I);

        $I->amOnPage($this->baseUrl . '/dashboard/candidato');

        $I->see('Propostas por Status');

        // Verificar se há elemento canvas/chart
        $I->seeElement('#propostas-chart');

        // Verificar legendas
        $I->see('Pendentes: 2');
        $I->see('Aceitas: 1');
    }

    // Teste de Gráfico de Vagas por Status (Instituição)
    public function testVagasChartInstituicao(AcceptanceTester $I)
    {
        $I->wantTo('visualizar gráfico de vagas por status');

        $this->loginAsInstituicao($I);

        $this->createInstituicaoDashboardData($I);

        $I->amOnPage($this->baseUrl . '/dashboard/instituicao');

        $I->see('Vagas por Status');

        // Verificar elemento do gráfico
        $I->seeElement('#vagas-chart');

        // Verificar legendas
        $I->see('Ativas: 2');
        $I->see('Pausadas: 1');
        $I->see('Fechadas: 1');
    }

    // Teste de Navegação Rápida do Dashboard (Candidato)
    public function testQuickNavigationCandidato(AcceptanceTester $I)
    {
        $I->wantTo('usar links de navegação rápida no dashboard');

        $this->loginAsCandidato($I);

        $I->amOnPage($this->baseUrl . '/dashboard/candidato');

        // Clicar em "Ver todas as propostas"
        $I->click('Ver todas as propostas');
        $I->seeCurrentUrlEquals($this->baseUrl . '/propostas');

        // Voltar ao dashboard
        $I->amOnPage($this->baseUrl . '/dashboard/candidato');

        // Clicar em "Buscar vagas"
        $I->click('Buscar vagas');
        $I->seeCurrentUrlEquals($this->baseUrl . '/vagas');
    }

    // Teste de Navegação Rápida do Dashboard (Instituição)
    public function testQuickNavigationInstituicao(AcceptanceTester $I)
    {
        $I->wantTo('usar links de navegação rápida no dashboard');

        $this->loginAsInstituicao($I);

        $I->amOnPage($this->baseUrl . '/dashboard/instituicao');

        // Clicar em "Criar nova vaga"
        $I->click('Criar nova vaga');
        $I->seeCurrentUrlEquals($this->baseUrl . '/vagas/criar');

        // Voltar ao dashboard
        $I->amOnPage($this->baseUrl . '/dashboard/instituicao');

        // Clicar em "Ver minhas vagas"
        $I->click('Ver minhas vagas');
        $I->seeCurrentUrlEquals($this->baseUrl . '/vagas/minhas');
    }

    // Teste de Dashboard Vazio (Candidato)
    public function testEmptyDashboardCandidato(AcceptanceTester $I)
    {
        $I->wantTo('verificar dashboard vazio de candidato');

        $this->loginAsCandidato($I);

        $I->amOnPage($this->baseUrl . '/dashboard/candidato');

        $I->see('Você ainda não recebeu propostas');
        $I->see('Comece buscando vagas do seu interesse');
    }

    // Teste de Dashboard Vazio (Instituição)
    public function testEmptyDashboardInstituicao(AcceptanceTester $I)
    {
        $I->wantTo('verificar dashboard vazio de instituição');

        $this->loginAsInstituicao($I);

        $I->amOnPage($this->baseUrl . '/dashboard/instituicao');

        $I->see('Você ainda não criou vagas');
        $I->see('Comece criando sua primeira vaga');
    }

    // Teste de Atualização Automática de Estatísticas
    public function testDashboardStatsUpdate(AcceptanceTester $I)
    {
        $I->wantTo('verificar atualização de estatísticas após criar vaga');

        $this->loginAsInstituicao($I);

        $I->amOnPage($this->baseUrl . '/dashboard/instituicao');

        $I->see('Vagas Ativas');
        $I->see('0');

        // Criar uma vaga
        $this->createTestVaga($I);

        // Recarregar dashboard
        $I->amOnPage($this->baseUrl . '/dashboard/instituicao');

        $I->see('Vagas Ativas');
        $I->see('1');
    }

    // Métodos auxiliares
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

    private function createCandidatoDashboardData(AcceptanceTester $I)
    {
        $candidatoId = $I->grabFromDatabase('candidatos', 'id', ['cpf' => '12345678901']);
        $instituicaoId = $this->createTestInstituicao($I);

        // Criar vagas
        $vaga1 = $I->haveInDatabase('vagas', [
            'instituicao_id' => $instituicaoId,
            'titulo' => 'Desenvolvedor Web',
            'descricao' => 'Desenvolvimento web',
            'status' => 'ativa',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $vaga2 = $I->haveInDatabase('vagas', [
            'instituicao_id' => $instituicaoId,
            'titulo' => 'Designer Gráfico',
            'descricao' => 'Design gráfico',
            'status' => 'ativa',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Criar propostas
        $I->haveInDatabase('propostas', [
            'candidato_id' => $candidatoId,
            'vaga_id' => $vaga1,
            'status' => 'pendente',
            'mensagem' => 'Proposta 1',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $I->haveInDatabase('propostas', [
            'candidato_id' => $candidatoId,
            'vaga_id' => $vaga2,
            'status' => 'pendente',
            'mensagem' => 'Proposta 2',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $I->haveInDatabase('propostas', [
            'candidato_id' => $candidatoId,
            'vaga_id' => $vaga1,
            'status' => 'aceita',
            'mensagem' => 'Proposta aceita',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Criar vagas salvas
        $I->haveInDatabase('vagas_salvas', [
            'candidato_id' => $candidatoId,
            'vaga_id' => $vaga1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $I->haveInDatabase('vagas_salvas', [
            'candidato_id' => $candidatoId,
            'vaga_id' => $vaga2,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function createInstituicaoDashboardData(AcceptanceTester $I)
    {
        $instituicaoId = $I->grabFromDatabase('instituicoes', 'id', ['cnpj' => '12345678000190']);
        $candidatoId = $this->createTestCandidato($I);

        // Criar vagas
        $I->haveInDatabase('vagas', [
            'instituicao_id' => $instituicaoId,
            'titulo' => 'Desenvolvedor Web',
            'descricao' => 'Desenvolvimento web',
            'status' => 'ativa',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $I->haveInDatabase('vagas', [
            'instituicao_id' => $instituicaoId,
            'titulo' => 'Designer',
            'descricao' => 'Design',
            'status' => 'ativa',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $I->haveInDatabase('vagas', [
            'instituicao_id' => $instituicaoId,
            'titulo' => 'Analista',
            'descricao' => 'Análise',
            'status' => 'pausada',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $vaga4 = $I->haveInDatabase('vagas', [
            'instituicao_id' => $instituicaoId,
            'titulo' => 'Gerente',
            'descricao' => 'Gerenciamento',
            'status' => 'fechada',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Criar propostas
        for ($i = 0; $i < 5; $i++) {
            $I->haveInDatabase('propostas', [
                'candidato_id' => $candidatoId,
                'vaga_id' => $vaga4,
                'status' => $i < 2 ? 'aceita' : 'pendente',
                'mensagem' => "Proposta {$i}",
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    private function createTestInstituicao(AcceptanceTester $I)
    {
        $userId = $I->haveInDatabase('usuarios', [
            'email' => 'outra@instituicao.com',
            'senha' => password_hash('senha123', PASSWORD_BCRYPT),
            'tipo' => 'instituicao',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $enderecoId = $I->haveInDatabase('enderecos', [
            'cep' => '01310100',
            'logradouro' => 'Rua Teste',
            'numero' => '100',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $I->haveInDatabase('instituicoes', [
            'usuario_id' => $userId,
            'endereco_id' => $enderecoId,
            'nome_fantasia' => 'Outra Instituição',
            'razao_social' => 'Outra Instituição LTDA',
            'cnpj' => '98765432000100',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function createTestCandidato($I)
    {
        $userId = $I->haveInDatabase('usuarios', [
            'email' => 'outro@candidato.com',
            'senha' => password_hash('senha123', PASSWORD_BCRYPT),
            'tipo' => 'candidato',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $I->haveInDatabase('candidatos', [
            'usuario_id' => $userId,
            'nome' => 'Maria Santos',
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
            'titulo' => 'Nova Vaga',
            'descricao' => 'Descrição da vaga',
            'status' => 'ativa',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}
