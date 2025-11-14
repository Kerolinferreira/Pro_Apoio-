# Testes de Aceitação - Pro Apoio API

Este diretório contém os testes de aceitação automatizados usando Codeception com WebDriver (ChromeDriver).

## Estrutura dos Testes

Os testes estão organizados por fluxo funcional:

### 1. **AuthCest.php** - Autenticação
- Registro de candidato
- Registro de instituição
- Login
- Logout
- Verificação de email/CPF/CNPJ
- Recuperação de senha
- Reset de senha

### 2. **CandidatoCest.php** - Perfil de Candidatos
- Visualização de perfil
- Atualização de perfil
- Upload de foto
- Alteração de senha
- Gerenciamento de experiências profissionais (criar, editar, deletar)
- Gerenciamento de experiências pessoais (criar, deletar)
- Visualização de vagas salvas
- Exclusão de conta

### 3. **InstituicaoCest.php** - Perfil de Instituições
- Visualização de perfil público
- Visualização de perfil próprio
- Atualização de perfil
- Upload de logo
- Alteração de senha
- Atualização de endereço
- Busca de candidatos
- Visualização de perfil de candidatos
- Visualização de vagas criadas
- Exclusão de conta

### 4. **VagaCest.php** - Gerenciamento de Vagas
- Listagem pública de vagas
- Visualização de vaga
- Criação de vaga (instituição)
- Edição de vaga
- Pausar vaga
- Fechar vaga
- Deletar vaga
- Alterar status da vaga
- Salvar vaga (candidato)
- Remover vaga salva
- Filtros (título, salário)

### 5. **PropostaCest.php** - Sistema de Propostas
- Criar proposta (instituição → candidato)
- Criar proposta (candidato → vaga)
- Listar propostas (instituição)
- Listar propostas (candidato)
- Visualizar detalhes da proposta
- Aceitar proposta
- Recusar proposta
- Cancelar proposta
- Filtrar propostas por status
- Notificações de propostas
- Validação de duplicidade

### 6. **DashboardCest.php** - Dashboard
- Dashboard do candidato com estatísticas
- Dashboard da instituição com estatísticas
- Visualização de propostas recentes
- Visualização de vagas recentes
- Gráficos por status
- Navegação rápida
- Dashboard vazio
- Atualização de estatísticas

### 7. **NotificationCest.php** - Notificações
- Listar notificações
- Contador de não lidas
- Marcar como lida
- Marcar todas como lidas
- Notificação de nova proposta
- Notificação de proposta aceita/recusada
- Notificação de vaga excluída
- Navegação por notificação
- Filtros por tipo
- Dropdown de notificações
- Notificações em tempo real

## Pré-requisitos

1. **ChromeDriver** instalado e rodando na porta 9515
2. **Servidor da aplicação** rodando em `http://localhost:3074`
3. **Banco de dados MySQL** configurado conforme `Acceptance.suite.yml`

## Executando os Testes

### Todos os testes de aceitação:
```bash
php vendor/bin/codecept run acceptance
```

### Teste específico:
```bash
php vendor/bin/codecept run acceptance AuthCest
```

### Teste individual:
```bash
php vendor/bin/codecept run acceptance AuthCest:testLogin
```

### Com saída detalhada:
```bash
php vendor/bin/codecept run acceptance --debug
```

### Com relatório HTML:
```bash
php vendor/bin/codecept run acceptance --html
```

## Configuração

O arquivo de configuração está em `tests/Acceptance.suite.yml`:

```yaml
actor: AcceptanceTester
modules:
    enabled:
        - WebDriver:
            url: 'http://localhost:3074/'
            browser: chrome
            host: 127.0.0.1
            port: 9515
        - Db:
            dsn: 'mysql:host=127.0.0.1;dbname=proapoio'
            user: 'root'
            password: '1234'
            dump: 'tests/_data/dump.sql'
        - \Helper\Acceptance
```

## Iniciando o ChromeDriver

### Windows:
```bash
chromedriver.exe --port=9515
```

### Linux/Mac:
```bash
chromedriver --port=9515
```

## Estrutura dos Testes

Cada teste segue o padrão AAA (Arrange, Act, Assert):

```php
public function testExemplo(AcceptanceTester $I)
{
    // Arrange - Preparar dados
    $I->wantTo('realizar ação X');
    $this->createTestData($I);

    // Act - Executar ação
    $I->amOnPage('/pagina');
    $I->click('Botão');

    // Assert - Verificar resultado
    $I->see('Mensagem de sucesso');
}
```

## Dicas

1. **Aguardar carregamento**: Use `$I->wait(segundos)` quando necessário
2. **Debug**: Use `$I->pauseExecution()` para pausar e inspecionar
3. **Screenshots**: Falhas automaticamente geram screenshots em `tests/_output`
4. **Popups**: Use `$I->acceptPopup()` ou `$I->cancelPopup()`
5. **Elementos**: Use seletores CSS ou XPath para localizar elementos

## Troubleshooting

### Erro de conexão com ChromeDriver
- Verifique se o ChromeDriver está rodando
- Confirme a versão compatível com seu Chrome

### Testes falhando por timeout
- Aumente o tempo de espera: `$I->wait(5)`
- Verifique se o servidor está rodando

### Banco de dados não limpa entre testes
- Verifique configuração do módulo Db
- Certifique-se de ter o dump.sql configurado

## Cobertura de Testes

Total de testes: **80+ casos de teste**

Cobrindo:
- ✅ Autenticação completa
- ✅ Perfis (Candidatos e Instituições)
- ✅ CRUD de Vagas
- ✅ Sistema de Propostas
- ✅ Dashboard com métricas
- ✅ Sistema de Notificações
- ✅ Busca e Filtros
- ✅ Upload de arquivos
- ✅ Validações de formulário

## Manutenção

Ao adicionar novas funcionalidades:
1. Crie novos métodos de teste no Cest apropriado
2. Use métodos auxiliares reutilizáveis
3. Mantenha os testes independentes
4. Documente comportamentos esperados
