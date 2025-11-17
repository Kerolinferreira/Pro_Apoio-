<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class NotificationCest
{
    private $baseUrl = 'http://localhost:5174';

    // Teste de Listar Notificações
    public function testListNotifications(AcceptanceTester $I)
    {
        $I->wantTo('visualizar minhas notificações');

        $this->loginAsCandidato($I);

        // Criar notificações de teste
        $this->createTestNotifications($I);

        $I->amOnPage($this->baseUrl . '/notificacoes');

        $I->see('Notificações');
        $I->see('Nova proposta recebida');
        $I->see('Proposta aceita pela instituição');
        $I->see('Nova vaga disponível');
    }

    // Teste de Contador de Notificações Não Lidas
    public function testUnreadNotificationsCount(AcceptanceTester $I)
    {
        $I->wantTo('ver o contador de notificações não lidas');

        $this->loginAsCandidato($I);

        $this->createTestNotifications($I);

        $I->amOnPage($this->baseUrl . '/dashboard/candidato');

        // Verificar badge de notificações
        $I->see('3'); // 3 notificações não lidas
        $I->seeElement('.notification-badge');
    }

    // Teste de Marcar Notificação como Lida
    public function testMarkNotificationAsRead(AcceptanceTester $I)
    {
        $I->wantTo('marcar uma notificação como lida');

        $this->loginAsCandidato($I);

        $notificationId = $this->createTestNotification($I);

        $I->amOnPage($this->baseUrl . '/notificacoes');

        $I->click("Marcar como lida {$notificationId}");

        $I->see('Notificação marcada como lida');

        // Verificar que a notificação mudou de aparência
        $I->seeElement(".notification-read-{$notificationId}");
    }

    // Teste de Marcar Todas como Lidas
    public function testMarkAllAsRead(AcceptanceTester $I)
    {
        $I->wantTo('marcar todas as notificações como lidas');

        $this->loginAsCandidato($I);

        $this->createTestNotifications($I);

        $I->amOnPage($this->baseUrl . '/notificacoes');

        $I->click('Marcar todas como lidas');

        $I->see('Todas as notificações foram marcadas como lidas');

        // Verificar que o contador zerou
        $I->amOnPage($this->baseUrl . '/dashboard/candidato');
        $I->dontSeeElement('.notification-badge');
    }

    // Teste de Notificação de Nova Proposta
    public function testNewPropostaNotification(AcceptanceTester $I)
    {
        $I->wantTo('receber notificação ao receber nova proposta');

        // Login como instituição para criar proposta
        $this->loginAsInstituicao($I);

        $candidatoId = $this->createTestCandidato($I);
        $vagaId = $this->createTestVaga($I);

        // Criar proposta
        $I->amOnPage($this->baseUrl . "/candidatos/{$candidatoId}");
        $I->click('Enviar Proposta');
        $I->selectOption('vaga_id', $vagaId);
        $I->fillField('mensagem', 'Proposta de trabalho');
        $I->click('Enviar');

        // Logout e login como candidato
        $I->click('Sair');
        $this->loginAsCandidato($I);

        // Verificar notificação
        $I->amOnPage($this->baseUrl . '/notificacoes');
        $I->see('Nova proposta recebida');
        $I->see('Instituição Teste enviou uma proposta');
    }

    // Teste de Notificação de Proposta Aceita
    public function testPropostaAcceptedNotification(AcceptanceTester $I)
    {
        $I->wantTo('receber notificação quando proposta é aceita');

        // Login como candidato
        $this->loginAsCandidato($I);

        $propostaId = $this->createTestProposta($I);

        // Aceitar proposta
        $I->amOnPage($this->baseUrl . "/propostas/{$propostaId}");
        $I->click('Aceitar Proposta');
        $I->acceptPopup();

        // Logout e login como instituição
        $I->click('Sair');
        $this->loginAsInstituicao($I);

        // Verificar notificação
        $I->amOnPage($this->baseUrl . '/notificacoes');
        $I->see('Proposta aceita');
        $I->see('João Silva aceitou sua proposta');
    }

    // Teste de Notificação de Proposta Recusada
    public function testPropostaRejectedNotification(AcceptanceTester $I)
    {
        $I->wantTo('receber notificação quando proposta é recusada');

        $this->loginAsCandidato($I);

        $propostaId = $this->createTestProposta($I);

        // Recusar proposta
        $I->amOnPage($this->baseUrl . "/propostas/{$propostaId}");
        $I->click('Recusar Proposta');
        $I->fillField('motivo', 'Não tenho interesse');
        $I->click('Confirmar Recusa');

        // Logout e login como instituição
        $I->click('Sair');
        $this->loginAsInstituicao($I);

        // Verificar notificação
        $I->amOnPage($this->baseUrl . '/notificacoes');
        $I->see('Proposta recusada');
        $I->see('João Silva recusou sua proposta');
    }

    // Teste de Notificação de Vaga Excluída
    public function testVagaDeletedNotification(AcceptanceTester $I)
    {
        $I->wantTo('receber notificação quando vaga salva é excluída');

        // Login como candidato e salvar vaga
        $this->loginAsCandidato($I);

        $vagaId = $this->createTestVaga($I);

        $I->amOnPage($this->baseUrl . "/vagas/{$vagaId}");
        $I->click('Salvar Vaga');

        // Logout e login como instituição
        $I->click('Sair');
        $this->loginAsInstituicao($I);

        // Deletar vaga
        $I->amOnPage($this->baseUrl . '/vagas/minhas');
        $I->click("Deletar vaga {$vagaId}");
        $I->acceptPopup();

        // Logout e login como candidato
        $I->click('Sair');
        $this->loginAsCandidato($I);

        // Verificar notificação
        $I->amOnPage($this->baseUrl . '/notificacoes');
        $I->see('Vaga excluída');
        $I->see('A vaga "Desenvolvedor Web" foi excluída');
    }

    // Teste de Clicar em Notificação e Navegar
    public function testClickNotificationNavigate(AcceptanceTester $I)
    {
        $I->wantTo('clicar em notificação e ser redirecionado');

        $this->loginAsCandidato($I);

        $propostaId = $this->createTestProposta($I);

        $this->createTestNotificationWithLink($I, $propostaId);

        $I->amOnPage($this->baseUrl . '/notificacoes');

        $I->click('Nova proposta recebida');

        // Deve redirecionar para a proposta
        $I->seeCurrentUrlEquals($this->baseUrl . "/propostas/{$propostaId}");
    }

    // Teste de Filtrar Notificações por Tipo
    public function testFilterNotificationsByType(AcceptanceTester $I)
    {
        $I->wantTo('filtrar notificações por tipo');

        $this->loginAsCandidato($I);

        $this->createTestNotifications($I);

        $I->amOnPage($this->baseUrl . '/notificacoes');

        $I->selectOption('tipo', 'proposta');
        $I->click('Filtrar');

        $I->wait(1);

        $I->see('Nova proposta recebida');
        $I->dontSee('Nova vaga disponível');
    }

    // Teste de Notificações em Tempo Real
    public function testRealTimeNotifications(AcceptanceTester $I)
    {
        $I->wantTo('receber notificações em tempo real');

        $this->loginAsCandidato($I);

        $I->amOnPage($this->baseUrl . '/dashboard/candidato');

        // Simular chegada de nova notificação (via WebSocket ou polling)
        $this->createTestNotification($I);

        $I->wait(3); // Aguardar atualização

        // Verificar se o badge foi atualizado
        $I->see('1'); // Nova notificação
        $I->seeElement('.notification-badge');
    }

    // Teste de Dropdown de Notificações
    public function testNotificationDropdown(AcceptanceTester $I)
    {
        $I->wantTo('visualizar notificações no dropdown do header');

        $this->loginAsCandidato($I);

        $this->createTestNotifications($I);

        $I->amOnPage($this->baseUrl . '/dashboard/candidato');

        $I->click('.notification-icon');

        // Verificar dropdown abriu
        $I->seeElement('.notification-dropdown');

        $I->see('Nova proposta recebida');
        $I->see('Ver todas');
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

    private function createTestNotifications(AcceptanceTester $I)
    {
        $userId = $I->grabFromDatabase('usuarios', 'id', ['email' => 'candidato@email.com']);

        $notifications = [
            [
                'type' => 'App\\Notifications\\NovaPropostaNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $userId,
                'data' => json_encode([
                    'mensagem' => 'Nova proposta recebida',
                    'proposta_id' => 1,
                    'tipo' => 'proposta'
                ]),
                'read_at' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'type' => 'App\\Notifications\\PropostaAceitaNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $userId,
                'data' => json_encode([
                    'mensagem' => 'Proposta aceita pela instituição',
                    'proposta_id' => 2,
                    'tipo' => 'proposta'
                ]),
                'read_at' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'type' => 'App\\Notifications\\VagaExcluidaNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $userId,
                'data' => json_encode([
                    'mensagem' => 'Nova vaga disponível',
                    'vaga_id' => 1,
                    'tipo' => 'vaga'
                ]),
                'read_at' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        foreach ($notifications as $notification) {
            $I->haveInDatabase('notifications', $notification);
        }
    }

    private function createTestNotification(AcceptanceTester $I)
    {
        $userId = $I->grabFromDatabase('usuarios', 'id', ['email' => 'candidato@email.com']);

        return $I->haveInDatabase('notifications', [
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\NovaPropostaNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $userId,
            'data' => json_encode([
                'mensagem' => 'Nova proposta recebida',
                'proposta_id' => 1
            ]),
            'read_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function createTestNotificationWithLink(AcceptanceTester $I, $propostaId)
    {
        $userId = $I->grabFromDatabase('usuarios', 'id', ['email' => 'candidato@email.com']);

        return $I->haveInDatabase('notifications', [
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\NovaPropostaNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $userId,
            'data' => json_encode([
                'mensagem' => 'Nova proposta recebida',
                'proposta_id' => $propostaId,
                'link' => "/propostas/{$propostaId}"
            ]),
            'read_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function createTestCandidato(AcceptanceTester $I)
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
            'titulo' => 'Desenvolvedor Web',
            'descricao' => 'Desenvolvimento web',
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
            'mensagem' => 'Proposta de teste',
            'status' => 'pendente',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}
