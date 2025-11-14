# Testes E2E - ProApoio

Suite completa de testes automatizados End-to-End (E2E) usando Selenium WebDriver em PHP para o sistema ProApoio.

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Pré-requisitos](#pré-requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Executando os Testes](#executando-os-testes)
- [Estrutura dos Testes](#estrutura-dos-testes)
- [Troubleshooting](#troubleshooting)

## 🎯 Visão Geral

Esta suite de testes cobre os principais fluxos do sistema ProApoio:

1. **Login de Instituição** - Autenticação e redirecionamento
2. **Cadastro de Vaga** - Criação de oportunidades por instituições
3. **Cadastro de Candidato** - Registro de agentes de apoio
4. **Busca com Filtros** - Filtragem de candidatos por localização, escolaridade e deficiência
5. **Fazer Proposta** - Envio de propostas para candidatos
6. **Acessibilidade** - Validação de tab order, ARIA labels e navegação por teclado

## 📦 Pré-requisitos

### Software Necessário

1. **PHP 7.4+** com extensões:
   - pdo_mysql
   - curl
   - mbstring

2. **Composer** (gerenciador de dependências PHP)

3. **ChromeDriver**
   - Download: https://chromedriver.chromium.org/
   - Versão deve corresponder à versão do Google Chrome instalado

4. **MySQL/MariaDB**
   - Banco de dados `proapoio` configurado

5. **Servidor Web**
   - Frontend React rodando em `http://localhost:3074`
   - Backend Laravel API disponível

## 🔧 Instalação

### 1. Instalar Dependências PHP

No diretório raiz do projeto, execute:

```bash
composer require facebook/webdriver
```

Ou se já tiver um `composer.json`, execute:

```bash
composer install
```

### 2. Baixar e Configurar ChromeDriver

#### Windows:
```powershell
# Baixar ChromeDriver
# Visite: https://chromedriver.chromium.org/downloads
# Extrair para C:\chromedriver\chromedriver.exe

# Adicionar ao PATH (opcional)
$env:Path += ";C:\chromedriver"
```

#### Linux/Mac:
```bash
# Baixar ChromeDriver
wget https://chromedriver.storage.googleapis.com/LATEST_RELEASE
VERSION=$(cat LATEST_RELEASE)
wget https://chromedriver.storage.googleapis.com/$VERSION/chromedriver_linux64.zip

# Extrair e mover
unzip chromedriver_linux64.zip
sudo mv chromedriver /usr/local/bin/
sudo chmod +x /usr/local/bin/chromedriver
```

### 3. Verificar Instalação

```bash
# Verificar PHP
php --version

# Verificar Composer
composer --version

# Verificar ChromeDriver
chromedriver --version
```

## ⚙️ Configuração

### 1. Configurar Variáveis de Ambiente

Edite as constantes no início de cada arquivo de teste (ou centralize em um arquivo de config):

```php
const BASE_URL = 'http://localhost:3074/';      // URL do frontend
const WEBDRIVER_URL = 'http://127.0.0.1:9515';  // Endpoint do ChromeDriver
const DB_HOST = '127.0.0.1';
const DB_NAME = 'proapoio';
const DB_USER = 'root';
const DB_PASS = '1234';
```

### 2. Estrutura de Diretórios

Os testes esperam a seguinte estrutura:

```
tests/
├── 01_LoginInstituicao.php
├── 02_CadastroVaga.php
├── 03_CadastroCandidato.php
├── 04_VerCandidatosFiltros.php
├── 05_FazerProposta.php
├── 06_Acessibilidade_TabOrder_e_ARIA.php
├── run_all.php
├── README.md
├── _output/
│   ├── screenshots/    # Screenshots de falhas
│   └── reports/        # Relatórios de execução
└── _data/
    └── dump.sql        # (Opcional) Dump do banco para restauração
```

### 3. Preparar Banco de Dados

Certifique-se de que o banco de dados está limpo ou em estado conhecido:

```bash
# Opcional: Restaurar dump
mysql -u root -p proapoio < tests/_data/dump.sql
```

## 🚀 Executando os Testes

### Iniciar Serviços Necessários

#### 1. Iniciar ChromeDriver

```bash
# Abrir um terminal separado
chromedriver --port=9515

# Ou em background (Linux/Mac)
chromedriver --port=9515 &
```

#### 2. Iniciar Frontend

```bash
cd frontend_proapoio
npm run dev
# Aguardar iniciar em http://localhost:3074
```

#### 3. Iniciar Backend

```bash
cd api_proapoio
php artisan serve
# Ou configurar servidor Apache/Nginx
```

### Executar Testes

#### Executar Todos os Testes

```bash
php tests/run_all.php
```

#### Executar Todos com Output Detalhado

```bash
php tests/run_all.php --verbose
```

#### Parar na Primeira Falha

```bash
php tests/run_all.php --stop-on-failure
```

#### Executar Teste Individual

```bash
php tests/01_LoginInstituicao.php
php tests/02_CadastroVaga.php
php tests/03_CadastroCandidato.php
php tests/04_VerCandidatosFiltros.php
php tests/05_FazerProposta.php
php tests/06_Acessibilidade_TabOrder_e_ARIA.php
```

## 📊 Estrutura dos Testes

### Padrão de Cada Teste

Todos os testes seguem o mesmo padrão:

```php
<?php
require 'vendor/autoload.php';

// Imports do WebDriver
use Facebook\WebDriver\Remote\RemoteWebDriver;
// ...

// Configurações
const BASE_URL = '...';
// ...

// Variáveis de controle
$driver = null;
$pdo = null;
// IDs dos dados de teste criados

try {
    // 1. Conectar ao banco e criar fixtures
    // 2. Iniciar WebDriver
    // 3. Executar ações do usuário
    // 4. Validar resultados
    // 5. Verificar no banco de dados

    exit(0); // Sucesso

} catch (Exception $e) {
    // Tratamento de erros
    // Screenshot automático
    exit(1); // Falha

} finally {
    // Limpeza: remover dados de teste
    // Fechar WebDriver
}
```

### Características dos Testes

- ✅ **Esperas Explícitas**: Uso de `WebDriverExpectedCondition` em vez de `sleep`
- ✅ **Fixtures**: Criação de dados necessários antes de cada teste
- ✅ **Limpeza**: Remoção de dados de teste no bloco `finally`
- ✅ **Screenshots**: Captura automática em caso de falha
- ✅ **Seletores Robustos**: Preferência para ID > name > CSS estável
- ✅ **Validação Dupla**: UI + Banco de Dados

## 🐛 Troubleshooting

### Problema: "Connection refused" ao ChromeDriver

**Solução:**
```bash
# Verificar se ChromeDriver está rodando
curl http://127.0.0.1:9515/status

# Se não estiver, iniciar:
chromedriver --port=9515
```

### Problema: "Element not found"

**Possíveis Causas:**
1. Página não carregou completamente
2. Seletor CSS/ID mudou
3. Elemento é dinâmico (carregado via AJAX)

**Soluções:**
- Aumentar tempo de espera: `$driver->wait(15)`
- Verificar seletores no código-fonte do frontend
- Adicionar espera para elemento específico

### Problema: Timeout em operações

**Solução:**
```php
// Aumentar timeouts na criação do driver
$driver = RemoteWebDriver::create(
    WEBDRIVER_URL,
    $capabilities,
    120000,  // connection timeout (ms)
    120000   // request timeout (ms)
);
```

### Problema: Banco de dados com dados antigos

**Solução:**
```bash
# Limpar dados de teste manualmente
mysql -u root -p proapoio

DELETE FROM propostas WHERE id_instituicao IN (SELECT id_instituicao FROM instituicoes WHERE email LIKE 'teste_%');
DELETE FROM vagas WHERE id_instituicao IN (SELECT id_instituicao FROM instituicoes WHERE email LIKE 'teste_%');
DELETE FROM candidatos WHERE email LIKE 'candidato_teste_%';
DELETE FROM instituicoes WHERE email LIKE 'teste_%';
```

### Problema: Frontend não está na porta 3074

**Solução:**
1. Alterar `BASE_URL` em cada teste
2. Ou criar arquivo de configuração centralizado:

```php
// tests/config.php
<?php
return [
    'base_url' => getenv('BASE_URL') ?: 'http://localhost:3074/',
    'webdriver_url' => getenv('WEBDRIVER_URL') ?: 'http://127.0.0.1:9515',
    // ...
];
```

### Problema: ChromeDriver incompatível com Chrome

**Solução:**
```bash
# Verificar versão do Chrome
google-chrome --version
# ou
chrome --version

# Baixar ChromeDriver correspondente
# https://chromedriver.chromium.org/downloads
```

## 📝 Boas Práticas

1. **Rodar testes em ambiente isolado** (não em produção)
2. **Verificar estado do banco antes de rodar testes**
3. **Usar headless mode para CI/CD**:
   ```php
   $capabilities->setCapability('goog:chromeOptions', [
       'args' => ['--headless', '--disable-gpu']
   ]);
   ```
4. **Manter seletores atualizados** quando a UI mudar
5. **Adicionar sleep apenas quando inevitável** (APIs externas, animações)

## 📄 Relatórios

Após executar `run_all.php`, relatórios são salvos em:

- **Screenshots**: `tests/_output/screenshots/`
- **Relatórios**: `tests/_output/report_YYYY-MM-DD_HH-MM-SS.txt`

## 🤝 Contribuindo

Para adicionar novos testes:

1. Criar arquivo `tests/07_NovoTeste.php`
2. Seguir o padrão dos testes existentes
3. Adicionar ao array `$tests` em `run_all.php`
4. Documentar no README

## 📞 Suporte

Para problemas ou dúvidas:

- Verificar logs do ChromeDriver
- Analisar screenshots em `tests/_output/screenshots/`
- Executar teste individual com `--verbose`

---

**Última atualização**: 2025-01-14
**Versão**: 1.0.0
