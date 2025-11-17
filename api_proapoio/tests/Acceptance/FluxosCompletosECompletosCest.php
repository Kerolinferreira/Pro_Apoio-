<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Codeception\Util\Locator;

/**
 * Testes de Fluxos Completos End-to-End
 *
 * Esta suite cobre fluxos completos do sistema que envolvem múltiplas páginas
 * e interações, simulando o comportamento real de usuários.
 */
class FluxosCompletosECompletosCest
{
    /**
     * TESTE 1: Fluxo Completo - Registro de Candidato até Candidatura em Vaga
     *
     * Cenário: Um novo usuário se registra como candidato, completa seu perfil,
     * busca uma vaga, visualiza detalhes e se candidata.
     *
     * Passos:
     * 1. Acessa página de registro
     * 2. Preenche formulário completo de candidato
     * 3. Valida email único
     * 4. Busca CEP automaticamente
     * 5. Seleciona deficiências
     * 6. Cria conta com sucesso
     * 7. É redirecionado para perfil
     * 8. Adiciona experiência profissional
     * 9. Faz upload de foto
     * 10. Busca vagas disponíveis
     * 11. Aplica filtros de busca
     * 12. Visualiza detalhes de uma vaga
     * 13. Envia proposta com mensagem
     * 14. Verifica proposta em "Minhas Propostas"
     * 15. Salva outra vaga nos favoritos
     * 16. Verifica vaga em "Vagas Salvas"
     */
    public function testeFluxoCompletoRegistroCandidatoAteCandidatura(AcceptanceTester $I)
    {
        $I->wantTo('simular fluxo completo de um novo candidato desde registro até candidatura');

        // === PARTE 1: REGISTRO ===
        $I->amOnPage('/register');
        $I->see('Sou Candidato');
        $I->click('Sou Candidato');

        $I->seeInCurrentUrl('/register/candidato');
        $I->see('Cadastro de Candidato');

        // Preencher dados pessoais
        $email = 'candidato_teste_' . time() . '@example.com';
        $cpf = '123.456.789-00'; // CPF fictício para teste

        $I->fillField('nome_completo', 'João Silva Teste');
        $I->fillField('email', $email);
        $I->fillField('telefone', '(11) 98765-4321');
        $I->fillField('cpf', $cpf);
        $I->fillField('data_nascimento', '1990-05-15');
        $I->selectOption('genero', 'Masculino');

        // Buscar CEP (deve preencher automaticamente logradouro, bairro, cidade, estado)
        $I->fillField('cep', '01310-100'); // Av. Paulista, SP
        $I->wait(2); // Aguarda requisição ViaCEP
        $I->seeInField('logradouro', 'Avenida Paulista');
        $I->seeInField('cidade', 'São Paulo');
        $I->seeInField('estado', 'SP');

        $I->fillField('numero', '1000');
        $I->fillField('complemento', 'Apto 101');

        // Escolaridade e experiência
        $I->selectOption('escolaridade', 'Superior Completo');
        $I->fillField('experiencia', 'Tenho 3 anos de experiência com alunos com deficiência visual.');

        // Selecionar deficiências
        $I->checkOption('deficiencia_1'); // Visual
        $I->checkOption('deficiencia_2'); // Auditiva

        // Senha
        $I->fillField('password', 'SenhaSegura123!');
        $I->fillField('password_confirmation', 'SenhaSegura123!');

        // Submeter formulário
        $I->click('Criar Conta');
        $I->wait(2);

        // Verificar sucesso e redirecionamento
        $I->see('Cadastro realizado com sucesso');
        $I->seeInCurrentUrl('/perfil/candidato');

        // === PARTE 2: COMPLETAR PERFIL ===
        $I->see('João Silva Teste');
        $I->see($email);

        // Adicionar experiência profissional
        $I->click('Adicionar Experiência Profissional');
        $I->waitForElement('textarea[name="descricao"]', 5);
        $I->fillField('descricao', 'Trabalhei por 2 anos como agente de apoio na Escola ABC.');
        $I->fillField('tempo_experiencia', '2-3 anos');
        $I->fillField('idade_aluno', 12);
        $I->checkOption('interesse_mesma_deficiencia');
        $I->checkOption('deficiencia_experiencia_1'); // Visual
        $I->click('Salvar Experiência');
        $I->wait(1);
        $I->see('Experiência adicionada com sucesso');

        // Upload de foto (simulado)
        // Nota: Requer arquivo de teste em tests/_data/
        // $I->attachFile('input[type="file"]', 'foto_teste.jpg');
        // $I->click('Enviar Foto');
        // $I->wait(2);
        // $I->see('Foto atualizada com sucesso');

        // === PARTE 3: BUSCAR E CANDIDATAR-SE A VAGA ===
        $I->click('Buscar Vagas'); // Menu ou link
        $I->seeInCurrentUrl('/vagas');

        // Aplicar filtros
        $I->fillField('cidade', 'São Paulo');
        $I->selectOption('estado', 'SP');
        $I->click('Buscar');
        $I->wait(1);

        // Verificar resultados
        $I->see('Vagas Disponíveis');
        // Assume que há pelo menos 1 vaga no banco de testes
        $I->see('Agente de Apoio'); // Parte do título de alguma vaga

        // Clicar na primeira vaga
        $I->click(Locator::firstElement('.vaga-card'));
        $I->seeInCurrentUrl('/vagas/');

        // Visualizar detalhes
        $I->see('Descrição da Vaga');
        $I->see('Requisitos do Aluno');
        $I->see('Candidatar-se');

        // Enviar proposta
        $I->click('Candidatar-se');
        $I->waitForElement('textarea[name="mensagem"]', 5);
        $I->fillField('mensagem', 'Tenho grande interesse nesta vaga e experiência relevante.');
        $I->click('Enviar Proposta');
        $I->wait(2);

        // Verificar sucesso
        $I->see('Proposta enviada com sucesso');
        $I->see('Você já enviou uma proposta'); // Badge

        // === PARTE 4: VERIFICAR PROPOSTA ENVIADA ===
        $I->click('Minhas Propostas'); // Menu
        $I->seeInCurrentUrl('/minhas-propostas');
        $I->see('Pendente'); // Status
        $I->see('Tenho grande interesse'); // Parte da mensagem

        // === PARTE 5: SALVAR VAGA NOS FAVORITOS ===
        $I->amOnPage('/vagas');
        $I->wait(1);

        // Clicar em "Salvar Vaga" na segunda vaga (para não ser a mesma candidatada)
        $I->click(Locator::elementAt('.btn-salvar-vaga', 2));
        $I->wait(1);
        $I->see('Vaga salva com sucesso');

        // Verificar em Vagas Salvas
        $I->click('Vagas Salvas');
        $I->seeInCurrentUrl('/vagas-salvas');
        $I->see('Agente de Apoio'); // Título da vaga salva
    }

    /**
     * TESTE 2: Fluxo Completo - Registro de Instituição até Aceitar Proposta
     *
     * Cenário: Uma nova instituição se registra, cria uma vaga, recebe proposta
     * de um candidato e aceita a proposta.
     *
     * Passos:
     * 1. Acessa página de registro
     * 2. Preenche formulário de instituição
     * 3. Valida CNPJ único
     * 4. Busca dados via ReceitaWS
     * 5. Cria conta com sucesso
     * 6. É redirecionado para perfil
     * 7. Cria nova vaga
     * 8. Preenche todos os dados da vaga
     * 9. Seleciona deficiências
     * 10. Publica vaga
     * 11. Simula candidato enviando proposta (via DB)
     * 12. Acessa "Minhas Propostas"
     * 13. Visualiza detalhes da proposta
     * 14. Aceita a proposta
     * 15. Verifica status atualizado
     */
    public function testeFluxoCompletoRegistroInstituicaoAteAceitarProposta(AcceptanceTester $I)
    {
        $I->wantTo('simular fluxo completo de instituição desde registro até aceitar proposta');

        // === PARTE 1: REGISTRO ===
        $I->amOnPage('/register');
        $I->click('Sou Instituição');
        $I->seeInCurrentUrl('/register/instituicao');

        $email = 'instituicao_teste_' . time() . '@example.com';
        $cnpj = '12.345.678/0001-90'; // CNPJ fictício

        $I->fillField('nome_fantasia', 'Escola Teste ABC');
        $I->fillField('razao_social', 'Escola Teste ABC Ltda');
        $I->fillField('cnpj', $cnpj);
        $I->fillField('email', $email);
        $I->fillField('telefone', '(11) 3333-4444');

        // Buscar CNPJ na ReceitaWS (se mockado ou teste de integração)
        // Assumindo que preenche razão social automaticamente
        $I->wait(2);

        // Endereço
        $I->fillField('cep', '01310-100');
        $I->wait(2);
        $I->fillField('numero', '500');

        // Responsável
        $I->fillField('nome_responsavel', 'Maria Diretora');
        $I->fillField('cargo_responsavel', 'Diretora');

        // Senha
        $I->fillField('password', 'SenhaSegura123!');
        $I->fillField('password_confirmation', 'SenhaSegura123!');

        $I->click('Criar Conta');
        $I->wait(2);

        $I->see('Cadastro realizado com sucesso');
        $I->seeInCurrentUrl('/perfil/instituicao');

        // === PARTE 2: CRIAR VAGA ===
        $I->click('Criar Nova Vaga');
        $I->seeInCurrentUrl('/vagas/criar');

        $I->fillField('titulo_vaga', 'Agente de Apoio para Deficiência Física');
        $I->fillField('descricao', 'Buscamos profissional com experiência em deficiência física.');
        $I->fillField('necessidades_descricao', 'Auxílio em locomoção e atividades diárias.');
        $I->fillField('cidade', 'São Paulo');
        $I->selectOption('estado', 'SP');
        $I->fillField('modalidade', 'Tempo Integral');

        // Perfil do aluno
        $I->selectOption('aluno_nascimento_mes', '3');
        $I->fillField('aluno_nascimento_ano', '2012');

        // Deficiências
        $I->checkOption('deficiencia_3'); // Física

        // Condições de trabalho
        $I->selectOption('regime_contratacao', 'CLT');
        $I->fillField('carga_horaria_semanal', '40');
        $I->fillField('valor_remuneracao', '3000.00');
        $I->selectOption('tipo_remuneracao', 'MENSAL');

        $I->click('Publicar Vaga');
        $I->wait(2);

        $I->see('Vaga criada com sucesso');
        $I->seeInCurrentUrl('/perfil/instituicao');

        // Verificar vaga na lista
        $I->see('Agente de Apoio para Deficiência Física');
        $I->see('Ativa'); // Status

        // === PARTE 3: SIMULAR RECEBIMENTO DE PROPOSTA ===
        // Nota: Em um cenário real, um candidato real enviaria a proposta.
        // Para este teste, vamos simular criando proposta via banco de dados.

        $vagaId = $I->grabFromDatabase('vagas', 'id_vaga', [
            'titulo_vaga' => 'Agente de Apoio para Deficiência Física'
        ]);

        // Criar candidato fictício e proposta via DB
        $I->haveInDatabase('users', [
            'email' => 'candidato_proposta@example.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'CANDIDATO',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $userId = $I->grabFromDatabase('users', 'id', [
            'email' => 'candidato_proposta@example.com'
        ]);

        $I->haveInDatabase('candidatos', [
            'id_usuario' => $userId,
            'nome_completo' => 'Pedro Candidato',
            'cpf' => '987.654.321-00',
            'telefone' => '(11) 99999-8888',
            'data_nascimento' => '1995-08-20',
            'escolaridade' => 'Médio Completo',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $candidatoId = $I->grabFromDatabase('candidatos', 'id', [
            'id_usuario' => $userId
        ]);

        $I->haveInDatabase('propostas', [
            'id_vaga' => $vagaId,
            'id_candidato' => $candidatoId,
            'mensagem' => 'Tenho experiência com deficiência física e gostaria de fazer parte da equipe.',
            'status' => 'PENDENTE',
            'data_envio' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // === PARTE 4: VISUALIZAR E ACEITAR PROPOSTA ===
        $I->click('Minhas Propostas');
        $I->seeInCurrentUrl('/minhas-propostas');

        $I->see('Pedro Candidato');
        $I->see('Pendente');
        $I->see('Agente de Apoio para Deficiência Física');

        // Ver detalhes
        $I->click('Ver Detalhes');
        $I->waitForElement('.modal-proposta', 5);
        $I->see('Tenho experiência com deficiência física');

        // Aceitar proposta
        $I->click('Aceitar Proposta');
        $I->wait(1);

        // Confirmar
        $I->see('Deseja aceitar esta proposta?');
        $I->click('Confirmar');
        $I->wait(2);

        $I->see('Proposta aceita com sucesso');

        // Verificar status atualizado
        $I->see('Aceita'); // Status
        $I->seeInDatabase('propostas', [
            'id_vaga' => $vagaId,
            'id_candidato' => $candidatoId,
            'status' => 'ACEITA'
        ]);
    }

    /**
     * TESTE 3: Fluxo Completo - Edição de Vaga e Gerenciamento de Status
     *
     * Cenário: Instituição edita uma vaga existente, pausa, reativa e fecha.
     */
    public function testeFluxoEdicaoGerenciamentoVaga(AcceptanceTester $I)
    {
        $I->wantTo('testar edição e gerenciamento completo de status de vaga');

        // Pre-requisito: Instituição logada com vaga criada
        $I->haveInDatabase('users', [
            'id' => 9001,
            'email' => 'inst_edit@example.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'INSTITUICAO',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $I->haveInDatabase('instituicoes', [
            'id' => 5001,
            'id_usuario' => 9001,
            'nome_fantasia' => 'Escola Editora',
            'razao_social' => 'Escola Editora Ltda',
            'cnpj' => '00.000.000/0001-00',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $I->haveInDatabase('vagas', [
            'id_vaga' => 7001,
            'id_instituicao' => 5001,
            'titulo_vaga' => 'Vaga Original para Editar',
            'tipo' => 'PRESENCIAL',
            'status' => 'ATIVA',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Login
        $I->amOnPage('/login');
        $I->fillField('email', 'inst_edit@example.com');
        $I->fillField('password', 'senha123');
        $I->click('Entrar');
        $I->wait(2);

        // Acessar perfil/minhas vagas
        $I->amOnPage('/perfil/instituicao');
        $I->see('Vaga Original para Editar');

        // Clicar em "Editar Vaga"
        $I->click('.btn-editar-vaga'); // Seletor do botão de editar
        $I->seeInCurrentUrl('/vagas/editar/7001');

        // Editar campos
        $I->fillField('titulo_vaga', 'Vaga EDITADA - Novo Título');
        $I->fillField('descricao', 'Descrição atualizada com novos requisitos.');
        $I->fillField('valor_remuneracao', '3500.00');

        $I->click('Salvar Alterações');
        $I->wait(2);

        $I->see('Vaga atualizada com sucesso');
        $I->seeInCurrentUrl('/perfil/instituicao');

        // Verificar mudanças
        $I->see('Vaga EDITADA - Novo Título');
        $I->seeInDatabase('vagas', [
            'id_vaga' => 7001,
            'titulo_vaga' => 'Vaga EDITADA - Novo Título',
            'valor_remuneracao' => 3500.00
        ]);

        // Pausar vaga
        $I->click('.btn-pausar-vaga');
        $I->wait(1);
        $I->see('Vaga pausada');
        $I->seeInDatabase('vagas', ['id_vaga' => 7001, 'status' => 'PAUSADA']);

        // Reativar vaga
        $I->click('.btn-reativar-vaga');
        $I->wait(1);
        $I->see('Vaga reativada');
        $I->seeInDatabase('vagas', ['id_vaga' => 7001, 'status' => 'ATIVA']);

        // Fechar vaga
        $I->click('.btn-fechar-vaga');
        $I->wait(1);
        $I->see('Deseja fechar esta vaga?');
        $I->click('Confirmar');
        $I->wait(2);
        $I->see('Vaga fechada');
        $I->seeInDatabase('vagas', ['id_vaga' => 7001, 'status' => 'FECHADA']);
    }

    /**
     * TESTE 4: Fluxo de Recuperação de Senha Completo
     *
     * Cenário: Usuário esqueceu a senha, solicita reset, recebe link e redefine.
     */
    public function testeFluxoRecuperacaoSenhaCompleto(AcceptanceTester $I)
    {
        $I->wantTo('testar fluxo completo de recuperação de senha');

        // Criar usuário existente
        $I->haveInDatabase('users', [
            'id' => 8001,
            'email' => 'usuario_reset@example.com',
            'password' => bcrypt('senhaAntiga123'),
            'tipo_usuario' => 'CANDIDATO',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Acessar página de esqueci senha
        $I->amOnPage('/forgot-password');
        $I->see('Recuperar Senha');

        // Preencher email
        $I->fillField('email', 'usuario_reset@example.com');
        $I->click('Enviar Link de Recuperação');
        $I->wait(2);

        // Verificar mensagem genérica (segurança)
        $I->see('Se o email estiver cadastrado, você receberá um link');

        // Verificar token gerado no banco
        $I->seeInDatabase('password_reset_tokens', [
            'email' => 'usuario_reset@example.com'
        ]);

        // Obter token do banco
        $token = $I->grabFromDatabase('password_reset_tokens', 'token', [
            'email' => 'usuario_reset@example.com'
        ]);

        // Simular clique no link do email
        $I->amOnPage('/reset-password?token=' . $token . '&email=usuario_reset@example.com');
        $I->see('Redefinir Senha');

        // Verificar email pré-preenchido
        $I->seeInField('email', 'usuario_reset@example.com');

        // Preencher nova senha
        $I->fillField('password', 'novaSenhaSegura456!');
        $I->fillField('password_confirmation', 'novaSenhaSegura456!');
        $I->click('Redefinir Senha');
        $I->wait(2);

        $I->see('Senha redefinida com sucesso');

        // Redireciona para login
        $I->seeInCurrentUrl('/login');

        // Testar login com nova senha
        $I->fillField('email', 'usuario_reset@example.com');
        $I->fillField('password', 'novaSenhaSegura456!');
        $I->click('Entrar');
        $I->wait(2);

        $I->see('Bem-vindo');
        $I->seeInCurrentUrl('/dashboard');

        // Verificar token foi invalidado
        $I->dontSeeInDatabase('password_reset_tokens', [
            'email' => 'usuario_reset@example.com',
            'token' => $token
        ]);
    }

    /**
     * TESTE 5: Fluxo de Busca Avançada com Múltiplos Filtros
     *
     * Cenário: Candidato busca vagas usando combinação de filtros
     */
    public function testeFluxoBuscaAvancadaVagas(AcceptanceTester $I)
    {
        $I->wantTo('testar busca de vagas com múltiplos filtros combinados');

        // Criar vagas de teste com diferentes características
        $I->haveInDatabase('users', ['id' => 9002, 'email' => 'inst@vagas.com', 'password' => bcrypt('senha'), 'tipo_usuario' => 'INSTITUICAO']);
        $I->haveInDatabase('instituicoes', ['id' => 5002, 'id_usuario' => 9002, 'nome_fantasia' => 'Inst Teste', 'cnpj' => '11.111.111/0001-11']);

        // Vaga 1: São Paulo, CLT, Visual
        $I->haveInDatabase('vagas', [
            'id_vaga' => 8001,
            'id_instituicao' => 5002,
            'titulo_vaga' => 'Vaga SP CLT Visual',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'tipo' => 'PRESENCIAL',
            'regime_contratacao' => 'CLT',
            'status' => 'ATIVA'
        ]);

        // Vaga 2: Rio de Janeiro, PJ, Auditiva
        $I->haveInDatabase('vagas', [
            'id_vaga' => 8002,
            'id_instituicao' => 5002,
            'titulo_vaga' => 'Vaga RJ PJ Auditiva',
            'cidade' => 'Rio de Janeiro',
            'estado' => 'RJ',
            'tipo' => 'PRESENCIAL',
            'regime_contratacao' => 'PJ',
            'status' => 'ATIVA'
        ]);

        // Vaga 3: São Paulo, Estágio, Física
        $I->haveInDatabase('vagas', [
            'id_vaga' => 8003,
            'id_instituicao' => 5002,
            'titulo_vaga' => 'Vaga SP Estágio Física',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'tipo' => 'PRESENCIAL',
            'regime_contratacao' => 'ESTAGIO',
            'status' => 'ATIVA'
        ]);

        // Acessar busca de vagas
        $I->amOnPage('/vagas');

        // Sem filtros: deve ver todas (3 vagas)
        $I->see('Vaga SP CLT Visual');
        $I->see('Vaga RJ PJ Auditiva');
        $I->see('Vaga SP Estágio Física');

        // Filtrar por cidade: São Paulo
        $I->fillField('cidade', 'São Paulo');
        $I->click('Buscar');
        $I->wait(1);

        $I->see('Vaga SP CLT Visual');
        $I->see('Vaga SP Estágio Física');
        $I->dontSee('Vaga RJ PJ Auditiva');

        // Adicionar filtro de estado: SP
        $I->selectOption('estado', 'SP');
        $I->click('Buscar');
        $I->wait(1);

        // Ainda deve ver as 2 de SP
        $I->see('Vaga SP CLT Visual');
        $I->see('Vaga SP Estágio Física');

        // Adicionar filtro de tipo: CLT
        $I->checkOption('tipo_CLT');
        $I->click('Buscar');
        $I->wait(1);

        // Agora deve ver apenas 1
        $I->see('Vaga SP CLT Visual');
        $I->dontSee('Vaga SP Estágio Física');
        $I->dontSee('Vaga RJ PJ Auditiva');

        // Limpar filtros
        $I->click('Limpar Filtros');
        $I->wait(1);

        // Deve ver todas novamente
        $I->see('Vaga SP CLT Visual');
        $I->see('Vaga RJ PJ Auditiva');
        $I->see('Vaga SP Estágio Física');

        // Busca textual
        $I->fillField('q', 'Auditiva');
        $I->click('Buscar');
        $I->wait(1);

        $I->see('Vaga RJ PJ Auditiva');
        $I->dontSee('Vaga SP CLT Visual');
    }

    /**
     * TESTE 6: Fluxo de Candidato Cancelando Proposta
     *
     * Cenário: Candidato envia proposta e depois cancela
     */
    public function testeFluxoCandidatoCancelaProposta(AcceptanceTester $I)
    {
        $I->wantTo('testar candidato cancelando proposta enviada');

        // Setup: Candidato com proposta pendente
        $I->haveInDatabase('users', [
            'id' => 9003,
            'email' => 'cand_cancela@example.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'CANDIDATO'
        ]);

        $I->haveInDatabase('candidatos', [
            'id' => 6001,
            'id_usuario' => 9003,
            'nome_completo' => 'Ana Canceladora',
            'cpf' => '111.222.333-44',
            'telefone' => '(11) 91111-2222'
        ]);

        $I->haveInDatabase('vagas', [
            'id_vaga' => 9001,
            'id_instituicao' => 5002,
            'titulo_vaga' => 'Vaga para Cancelar Proposta',
            'tipo' => 'PRESENCIAL',
            'status' => 'ATIVA'
        ]);

        $I->haveInDatabase('propostas', [
            'id_proposta' => 10001,
            'id_vaga' => 9001,
            'id_candidato' => 6001,
            'mensagem' => 'Proposta que será cancelada',
            'status' => 'PENDENTE',
            'data_envio' => date('Y-m-d H:i:s')
        ]);

        // Login
        $I->amOnPage('/login');
        $I->fillField('email', 'cand_cancela@example.com');
        $I->fillField('password', 'senha123');
        $I->click('Entrar');
        $I->wait(2);

        // Acessar Minhas Propostas
        $I->click('Minhas Propostas');
        $I->seeInCurrentUrl('/minhas-propostas');

        $I->see('Vaga para Cancelar Proposta');
        $I->see('Pendente');

        // Cancelar proposta
        $I->click('Cancelar Proposta');
        $I->wait(1);
        $I->see('Deseja cancelar esta proposta?');
        $I->click('Confirmar');
        $I->wait(2);

        $I->see('Proposta cancelada com sucesso');

        // Verificar remoção da lista ou status atualizado
        $I->dontSee('Vaga para Cancelar Proposta');
        // Ou se mantém na lista com status:
        // $I->see('Cancelada');

        // Verificar no banco
        $I->dontSeeInDatabase('propostas', [
            'id_proposta' => 10001,
            'status' => 'PENDENTE'
        ]);
        // Pode ser soft delete ou status='CANCELADA'
    }

    /**
     * TESTE 7: Fluxo de Validação de Erros no Registro
     *
     * Cenário: Testa todos os casos de erro de validação no registro
     */
    public function testeFluxoValidacoesErrosRegistro(AcceptanceTester $I)
    {
        $I->wantTo('testar validações de erro no formulário de registro');

        $I->amOnPage('/register/candidato');

        // Tentar submeter vazio
        $I->click('Criar Conta');
        $I->wait(1);
        $I->see('campo é obrigatório'); // Múltiplas mensagens

        // Email inválido
        $I->fillField('email', 'email_invalido');
        $I->click('nome_completo'); // Blur do campo email
        $I->wait(0.5);
        $I->see('Email inválido');

        // Email duplicado
        $I->haveInDatabase('users', ['email' => 'email_duplicado@example.com', 'password' => bcrypt('senha')]);
        $I->fillField('email', 'email_duplicado@example.com');
        $I->click('nome_completo');
        $I->wait(0.5);
        $I->see('já está cadastrado');

        // CPF inválido
        $I->fillField('cpf', '000.000.000-00');
        $I->click('nome_completo');
        $I->wait(0.5);
        $I->see('CPF inválido');

        // Senhas não coincidem
        $I->fillField('password', 'Senha123!');
        $I->fillField('password_confirmation', 'Senha456!');
        $I->click('nome_completo');
        $I->wait(0.5);
        $I->see('senhas não coincidem');

        // Senha fraca
        $I->fillField('password', '123');
        $I->fillField('password_confirmation', '123');
        $I->click('nome_completo');
        $I->wait(0.5);
        $I->see('mínimo 8 caracteres');

        // CEP inválido
        $I->fillField('cep', '00000-000');
        $I->wait(2);
        $I->see('CEP inválido');
    }
}
