# Guia Completo de Testes - Pro Apoio

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Documentação Completa](#documentação-completa)
3. [Execução de Testes](#execução-de-testes)
4. [Testes Criados](#testes-criados)
5. [Próximos Passos](#próximos-passos)

---

## 🎯 Visão Geral

Este documento fornece um guia rápido para executar e entender os testes do sistema Pro Apoio. A análise completa está disponível em **ANALISE_COMPLETA_FLUXOS_E_TESTES.md**.

### Tipos de Testes Disponíveis

| Tipo | Framework | Quantidade | Propósito |
|------|-----------|------------|-----------|
| **Unit** | PHPUnit | 40+ | Testar modelos e helpers isoladamente |
| **Feature** | Laravel Testing | 200+ | Testar endpoints da API |
| **Acceptance** | Codeception + WebDriver | 80+ | Testar interface web (E2E) |
| **Integration** | PHPUnit | 6+ | Testar APIs externas (opcional) |

**Total: ~413+ casos de teste**

---

## 📚 Documentação Completa

### Arquivos de Documentação

1. **ANALISE_COMPLETA_FLUXOS_E_TESTES.md**
   - Mapeamento completo de rotas (50+ endpoints)
   - Mapeamento de páginas do frontend (23 páginas)
   - 9 fluxos completos documentados:
     - Registro de Candidato
     - Registro de Instituição
     - Login
     - Recuperação de Senha
     - Criar Vaga
     - Buscar Vagas
     - Visualizar Detalhes de Vaga
     - Enviar Proposta
     - Gerenciar Propostas
   - Matriz de cobertura de testes
   - Lacunas identificadas
   - Plano de testes sugeridos

2. **tests/Acceptance/FluxosCompletosECompletosCest.php**
   - 7 novos testes E2E criados:
     - Fluxo completo: Registro → Candidatura
     - Fluxo completo: Registro Instituição → Aceitar Proposta
     - Edição e gerenciamento de vaga
     - Recuperação de senha completa
     - Busca avançada com filtros
     - Cancelamento de proposta
     - Validações de erros no registro

---

## 🚀 Execução de Testes

### Pré-requisitos

#### Para Testes Unit + Feature (PHPUnit/Laravel)
```bash
# Configurar .env.testing
cp .env .env.testing
# Editar .env.testing com banco de dados de teste

# Instalar dependências
composer install

# Executar migrations no banco de teste
php artisan migrate --env=testing --database=testing
```

#### Para Testes Acceptance (Codeception/WebDriver)
```bash
# 1. Instalar ChromeDriver
# Baixar de: https://chromedriver.chromium.org/
# Adicionar ao PATH ou executar localmente

# 2. Iniciar ChromeDriver
chromedriver --port=9515

# 3. Iniciar servidor da aplicação
# Terminal 1: Backend
cd api_proapoio
php artisan serve --port=8000

# Terminal 2: Frontend
cd frontend_proapoio
npm run dev

# 4. Configurar database dump (opcional)
mysqldump -u root -p proapoio > tests/_data/dump.sql

# 5. Configurar Codeception
cd api_proapoio
vendor/bin/codecept build
```

### Comandos de Execução

#### Testes Unit

```bash
# Todos os testes unitários
cd api_proapoio
php artisan test tests/Unit

# Teste específico
php artisan test tests/Unit/Models/UserTest.php

# Com cobertura
php artisan test tests/Unit --coverage
```

#### Testes Feature

```bash
# Todos os testes de feature
php artisan test tests/Feature

# Controller específico
php artisan test tests/Feature/Controllers/VagaControllerTest.php

# Com saída detalhada
php artisan test tests/Feature --verbose

# Paralelo (mais rápido)
php artisan test tests/Feature --parallel
```

#### Testes Acceptance (Codeception)

```bash
# IMPORTANTE: Certifique-se que ChromeDriver e servidores estão rodando!

# Todos os testes de aceitação
vendor/bin/codecept run acceptance

# Suite específica
vendor/bin/codecept run acceptance AuthCest

# Teste específico
vendor/bin/codecept run acceptance AuthCest:testLogin

# Com debug (útil para desenvolvimento)
vendor/bin/codecept run acceptance --debug

# Com HTML report
vendor/bin/codecept run acceptance --html

# Pausar execução para inspeção manual
# Adicione no teste: $I->pauseExecution();
```

#### Novo Teste de Fluxos Completos

```bash
# Executar todos os 7 novos testes E2E
vendor/bin/codecept run acceptance FluxosCompletosECompletosCest

# Teste específico
vendor/bin/codecept run acceptance FluxosCompletosECompletosCest:testeFluxoCompletoRegistroCandidatoAteCandidatura

# Com debug
vendor/bin/codecept run acceptance FluxosCompletosECompletosCest --debug
```

#### Todos os Testes

```bash
# Executar TUDO (Unit + Feature)
php artisan test

# OU usando composer
composer test

# Acceptance separadamente (requer setup manual)
vendor/bin/codecept run acceptance
```

---

## 🧪 Testes Criados

### Arquivo: `tests/Acceptance/FluxosCompletosECompletosCest.php`

#### TESTE 1: Fluxo Completo - Registro Candidato até Candidatura
**Complexidade:** Alta
**Duração Estimada:** 30-45 segundos
**Cobertura:**
- Registro de candidato (formulário completo)
- Validação de email único
- Busca automática de CEP
- Seleção de deficiências
- Criação de experiência profissional
- Upload de foto (comentado, requer arquivo)
- Busca de vagas com filtros
- Visualização de detalhes de vaga
- Envio de proposta
- Salvar vaga nos favoritos
- Verificação em "Minhas Propostas"
- Verificação em "Vagas Salvas"

**Validações:**
- ✅ Redirecionamento correto após registro
- ✅ Dados persistidos no banco
- ✅ Token JWT gerado e armazenado
- ✅ Proposta criada com status PENDENTE
- ✅ Vaga salva em favoritos

---

#### TESTE 2: Fluxo Completo - Registro Instituição até Aceitar Proposta
**Complexidade:** Alta
**Duração Estimada:** 35-50 segundos
**Cobertura:**
- Registro de instituição (formulário completo)
- Validação de CNPJ único
- Busca via ReceitaWS (mockado)
- Criação de vaga completa
- Seleção de deficiências associadas
- Recebimento de proposta (via DB)
- Visualização de propostas recebidas
- Aceitar proposta
- Notificação para candidato (verificada no DB)

**Validações:**
- ✅ Vaga criada com status ATIVA
- ✅ Tipo padrão PRESENCIAL
- ✅ Proposta aceita corretamente
- ✅ Status atualizado para ACEITA
- ✅ Data de resposta registrada

---

#### TESTE 3: Edição e Gerenciamento de Vaga
**Complexidade:** Média
**Duração Estimada:** 20-30 segundos
**Cobertura:**
- Edição de campos da vaga
- Alteração de remuneração
- Pausar vaga (status → PAUSADA)
- Reativar vaga (status → ATIVA)
- Fechar vaga (status → FECHADA)

**Validações:**
- ✅ Alterações persistidas corretamente
- ✅ Transições de status funcionais
- ✅ Confirmações exibidas ao usuário

---

#### TESTE 4: Recuperação de Senha Completo
**Complexidade:** Média
**Duração Estimada:** 25-35 segundos
**Cobertura:**
- Solicitação de reset de senha
- Geração de token de reset
- Persistência em `password_reset_tokens`
- Acesso à página de reset via link
- Redefinição de senha
- Login com nova senha
- Invalidação de token após uso

**Validações:**
- ✅ Token gerado e armazenado
- ✅ Senha atualizada no banco
- ✅ Token removido após reset
- ✅ Login funcional com nova senha

---

#### TESTE 5: Busca Avançada com Múltiplos Filtros
**Complexidade:** Média
**Duração Estimada:** 20-30 segundos
**Cobertura:**
- Busca sem filtros (todas as vagas)
- Filtro por cidade
- Filtro por estado
- Filtro por tipo (checkboxes múltiplos)
- Combinação de filtros
- Limpar filtros
- Busca textual (termo)

**Validações:**
- ✅ Filtros aplicados corretamente
- ✅ Resultados corretos para cada filtro
- ✅ Combinação de filtros funcional
- ✅ Busca textual precisa

---

#### TESTE 6: Candidato Cancelando Proposta
**Complexidade:** Baixa
**Duração Estimada:** 15-20 segundos
**Cobertura:**
- Listagem de propostas do candidato
- Cancelamento de proposta pendente
- Confirmação de cancelamento
- Remoção da lista (ou status CANCELADA)

**Validações:**
- ✅ Proposta removida ou status atualizado
- ✅ Persistência no banco
- ✅ Feedback ao usuário

---

#### TESTE 7: Validações de Erros no Registro
**Complexidade:** Média
**Duração Estimada:** 20-30 segundos
**Cobertura:**
- Submissão de formulário vazio
- Email inválido
- Email duplicado (já cadastrado)
- CPF inválido
- CPF duplicado
- Senhas não coincidem
- Senha fraca (< 8 caracteres)
- CEP inválido

**Validações:**
- ✅ Mensagens de erro exibidas corretamente
- ✅ Campos destacados em vermelho
- ✅ Validações em tempo real (blur/change)
- ✅ Botão "Criar Conta" desabilitado quando há erros

---

## 📊 Cobertura de Testes

### Fluxos Críticos Cobertos

| Fluxo | Unit | Feature | Acceptance | Status |
|-------|------|---------|------------|--------|
| Registro de Candidato | ✅ | ✅ | ✅ | Completo |
| Registro de Instituição | ✅ | ✅ | ✅ | Completo |
| Login/Logout | ✅ | ✅ | ✅ | Completo |
| Recuperação de Senha | ✅ | ✅ | ✅ | Completo |
| CRUD de Vagas | ✅ | ✅ | ✅ | Completo |
| Busca de Vagas | - | ✅ | ✅ | Completo |
| Envio de Proposta | ✅ | ✅ | ✅ | Completo |
| Aceitar/Recusar Proposta | ✅ | ✅ | ✅ | Completo |
| Cancelar Proposta | - | ✅ | ✅ | Completo |
| Salvar/Remover Vaga | ✅ | ✅ | ✅ | Completo |
| Perfil Candidato (Edição) | ✅ | ✅ | ✅ | Completo |
| Perfil Instituição (Edição) | ✅ | ✅ | ✅ | Completo |
| Experiências (CRUD) | ✅ | ✅ | ✅ | Completo |
| Upload de Foto/Logo | ✅ | ✅ | ⚠️ | Parcial (mock) |
| Busca de Candidatos | - | ✅ | ⚠️ | Parcial |
| Dashboard Candidato | - | - | ✅ | Básico |
| Dashboard Instituição | - | - | ✅ | Básico |
| Notificações | - | ✅ | ✅ | Completo |
| APIs Externas (CEP/CNPJ) | - | ✅ | ⚠️ | Mockado |

**Legenda:**
- ✅ Completo: Cobertura abrangente
- ⚠️ Parcial: Cobertura básica ou mockada
- - Não aplicável

---

## 🔍 Lacunas Identificadas e Próximos Passos

### Lacunas de Alta Prioridade

1. **Upload de Arquivos Real**
   - Atualmente mockado ou comentado
   - Requer arquivos de teste em `tests/_data/`
   - Validar formatos, tamanhos e processamento

2. **Segurança (XSS, SQL Injection, CSRF)**
   - Adicionar testes específicos de segurança
   - Testar inputs maliciosos
   - Validar escape de caracteres especiais

3. **Concorrência**
   - Testes com múltiplos usuários simultâneos
   - Conflitos de atualização (race conditions)
   - Transações concorrentes

### Lacunas de Média Prioridade

4. **Performance e Carga**
   - Testes com muitas vagas (100+)
   - Muitas propostas (1000+)
   - Paginação com datasets grandes

5. **Mobile/Responsividade**
   - Testes em viewports mobile
   - Navegação em telas pequenas
   - Gestos touch (se aplicável)

6. **Acessibilidade**
   - Navegação via teclado
   - Screen readers
   - ARIA attributes
   - Contraste de cores

### Lacunas de Baixa Prioridade

7. **SEO e Meta Tags**
   - Validação de títulos de página
   - Meta descriptions
   - Open Graph tags

8. **Emails**
   - Validar conteúdo de emails enviados
   - Templates corretos
   - Links funcionais
   - (Requer MailHog ou similar)

9. **Rate Limiting Real**
   - Testes de throttle exaustivos
   - Validar bloqueio após limite
   - Tempo de desbloqueio

---

## 🛠️ Sugestões de Melhorias

### 1. Adicionar Arquivo de Teste para Upload

Criar arquivo de imagem em `tests/_data/`:

```bash
# Criar pasta se não existir
mkdir -p tests/_data

# Copiar imagem de teste (ou criar uma dummy)
# Exemplo: Gerar imagem PNG 100x100 preta
convert -size 100x100 xc:black tests/_data/foto_teste.jpg
```

Descomentar linhas de upload nos testes:
```php
$I->attachFile('input[type="file"]', 'foto_teste.jpg');
$I->click('Enviar Foto');
$I->wait(2);
$I->see('Foto atualizada com sucesso');
```

### 2. Configurar MailHog para Testes de Email

```bash
# Instalar MailHog
brew install mailhog  # macOS
# ou
apt-get install mailhog  # Ubuntu

# Executar
mailhog

# Configurar .env.testing
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

Adicionar verificações nos testes:
```php
// Após solicitar reset de senha
$I->wait(2);
// Verificar email foi enviado (via API do MailHog)
$emails = file_get_contents('http://127.0.0.1:8025/api/v2/messages');
$I->assertStringContainsString('Recuperar Senha', $emails);
```

### 3. Testes de Segurança

Criar novo arquivo `tests/Acceptance/SecurityCest.php`:

```php
public function testXSSPrevention(AcceptanceTester $I)
{
    $I->amOnPage('/register/candidato');
    $I->fillField('nome_completo', '<script>alert("XSS")</script>');
    $I->click('Criar Conta');

    // Verificar que script NÃO é executado
    $I->dontSeeInPageSource('<script>alert("XSS")</script>');
    $I->see('&lt;script&gt;'); // HTML entities
}

public function testSQLInjectionPrevention(AcceptanceTester $I)
{
    $I->amOnPage('/login');
    $I->fillField('email', "admin' OR '1'='1");
    $I->fillField('password', "anything");
    $I->click('Entrar');

    // Deve falhar (não deve logar)
    $I->see('Email ou senha inválidos');
    $I->seeInCurrentUrl('/login');
}
```

### 4. Testes de Performance

Criar `tests/Performance/LoadTest.php`:

```php
use Codeception\Test\Unit;

class LoadTest extends Unit
{
    public function testBuscaVagasComMuitasVagas()
    {
        // Criar 1000 vagas
        Vaga::factory()->count(1000)->create();

        $start = microtime(true);
        $response = $this->getJson('/api/vagas?per_page=20');
        $duration = microtime(true) - $start;

        $response->assertStatus(200);
        $this->assertLessThan(2.0, $duration); // Menos de 2 segundos
    }
}
```

### 5. CI/CD Integration

Criar `.github/workflows/tests.yml`:

```yaml
name: Run Tests

on: [push, pull_request]

jobs:
  phpunit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install
      - run: php artisan test

  codeception:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: nanasess/setup-chromedriver@v2
      - run: chromedriver --port=9515 &
      - run: php artisan serve &
      - run: npm ci && npm run dev &
      - run: vendor/bin/codecept run acceptance
```

---

## 📝 Como Adicionar Novos Testes

### Testes Feature (API)

```php
// tests/Feature/Controllers/NovoControllerTest.php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NovoControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_do_something()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/endpoint', ['data' => 'value']);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('table', ['field' => 'value']);
    }
}
```

### Testes Acceptance (E2E)

```php
// tests/Acceptance/NovoFluxoCest.php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class NovoFluxoCest
{
    public function testNovoFluxo(AcceptanceTester $I)
    {
        $I->wantTo('descrever o que quero testar');

        // Arrange
        $I->haveInDatabase('users', [...]);

        // Act
        $I->amOnPage('/pagina');
        $I->fillField('campo', 'valor');
        $I->click('Botão');

        // Assert
        $I->see('Mensagem Esperada');
        $I->seeInCurrentUrl('/sucesso');
        $I->seeInDatabase('table', ['field' => 'value']);
    }
}
```

---

## 🎓 Recursos e Referências

### Documentação Oficial

- **PHPUnit**: https://phpunit.de/documentation.html
- **Laravel Testing**: https://laravel.com/docs/11.x/testing
- **Codeception**: https://codeception.com/docs/01-Introduction
- **WebDriver**: https://www.selenium.dev/documentation/webdriver/

### Padrões e Boas Práticas

- **AAA Pattern** (Arrange-Act-Assert)
- **Given-When-Then** (BDD)
- **Page Object Pattern** (para E2E complexos)
- **Factory Pattern** (para criação de dados)

### Ferramentas Úteis

- **Laravel Telescope**: Debugging em desenvolvimento
- **MailHog**: Captura de emails em testes
- **Xdebug**: Cobertura de código
- **Mockery**: Mocking de dependências

---

## ✅ Checklist de Execução

Antes de executar os testes, verifique:

### Testes Unit + Feature
- [ ] `.env.testing` configurado
- [ ] Banco de dados de teste criado e limpo
- [ ] Migrations executadas (`php artisan migrate --env=testing`)
- [ ] Dependências instaladas (`composer install`)

### Testes Acceptance
- [ ] ChromeDriver baixado e no PATH
- [ ] ChromeDriver rodando (`chromedriver --port=9515`)
- [ ] Backend rodando (`php artisan serve --port=8000`)
- [ ] Frontend rodando (`npm run dev`)
- [ ] URL correta em `Acceptance.suite.yml` (http://localhost:5174/)
- [ ] Banco de dados populado ou dump disponível
- [ ] Codeception construído (`vendor/bin/codecept build`)

---

## 📧 Contato e Suporte

Para dúvidas ou problemas com os testes:

1. **Issues**: Abra issue no repositório
2. **Documentação**: Consulte `ANALISE_COMPLETA_FLUXOS_E_TESTES.md`
3. **Logs**: Verifique `tests/_output/` após falhas
4. **Screenshots**: Capturas automáticas em `tests/_output/` quando teste falha

---

**Última Atualização:** 2025-01-16
**Versão:** 1.0
**Autor:** Análise Automatizada do Sistema Pro Apoio
