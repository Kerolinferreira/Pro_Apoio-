# Scripts de Execução de Testes - Pro Apoio

## 📜 Visão Geral

Este diretório contém scripts automatizados para executar todos os testes de aceitação (E2E) do projeto Pro Apoio em ordem lógica, com verificações automáticas de pré-requisitos e relatórios detalhados.

---

## 📋 Scripts Disponíveis

### 1. `run_all_acceptance_tests.bat` (Windows)
**Plataforma:** Windows 7, 8, 10, 11
**Shell:** CMD (Command Prompt)

### 2. `run_all_acceptance_tests.sh` (Linux/macOS)
**Plataforma:** Linux, macOS, WSL
**Shell:** Bash

---

## 🚀 Como Usar

### Windows

```cmd
# Opção 1: Clicar duas vezes no arquivo
run_all_acceptance_tests.bat

# Opção 2: Executar via CMD
cd C:\caminho\para\Pro_Apoio-
run_all_acceptance_tests.bat
```

### Linux/macOS

```bash
# Dar permissão de execução (primeira vez)
chmod +x run_all_acceptance_tests.sh

# Executar
./run_all_acceptance_tests.sh
```

---

## ✅ O Que os Scripts Fazem

### 1️⃣ **Verificações de Pré-requisitos**

Os scripts automaticamente verificam:
- ✅ ChromeDriver instalado e no PATH
- ✅ PHP instalado e acessível
- ✅ Composer instalado
- ✅ Dependências do Codeception instaladas
- ✅ Estrutura de testes presente

**Se algo estiver faltando**, o script para e exibe instruções claras de como resolver.

---

### 2️⃣ **Verificações de Serviços**

Os scripts verificam se os serviços necessários estão rodando:
- ✅ **ChromeDriver** na porta `9515`
- ✅ **Backend (Laravel)** em `http://localhost:8000`
- ✅ **Frontend (React)** em `http://localhost:5174`

**Se algo não estiver rodando**, o script:
1. Exibe instruções de como iniciar o serviço
2. Pausa e aguarda você iniciar
3. Verifica novamente antes de continuar

---

### 3️⃣ **Execução dos Testes em Ordem Lógica**

Os testes são executados na seguinte ordem:

| # | Suite | Descrição | Tempo Médio |
|---|-------|-----------|-------------|
| 1 | **AuthCest** | Autenticação completa | ~60s |
| 2 | **CandidatoCest** | Perfil do candidato | ~90s |
| 3 | **InstituicaoCest** | Perfil da instituição | ~70s |
| 4 | **VagaCest** | CRUD de vagas | ~120s |
| 5 | **PropostaCest** | Sistema de propostas | ~150s |
| 6 | **DashboardCest** | Dashboards | ~50s |
| 7 | **NotificationCest** | Notificações | ~40s |
| 8 | **FluxosCompletosECompletosCest** | Fluxos E2E completos (NOVO) | ~240s |

**Tempo Total Estimado:** ~13-15 minutos

---

### 4️⃣ **Relatório Final**

Ao final, o script exibe:
- ✅ Total de suites executadas
- ✅ Quantas passaram
- ❌ Quantas falharam
- 📊 Taxa de sucesso (%)
- 📂 Localização dos logs e screenshots
- 💡 Próximos passos sugeridos

---

## 📦 Pré-requisitos (Setup Inicial)

Antes de executar os scripts pela primeira vez, certifique-se de que os seguintes itens estão instalados:

### Windows

```cmd
# 1. ChromeDriver
# Baixar de: https://chromedriver.chromium.org/
# Adicionar ao PATH ou colocar na pasta do projeto

# 2. PHP 8.2+
# Baixar de: https://windows.php.net/download/

# 3. Composer
# Baixar de: https://getcomposer.org/download/

# 4. Node.js + npm
# Baixar de: https://nodejs.org/

# 5. Instalar dependências
cd api_proapoio
composer install

cd ..\frontend_proapoio
npm install
```

### Linux (Ubuntu/Debian)

```bash
# 1. ChromeDriver
sudo apt-get update
sudo apt-get install chromium-chromedriver

# 2. PHP 8.2+
sudo apt-get install php8.2 php8.2-cli php8.2-mysql php8.2-curl

# 3. Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 4. Node.js + npm
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# 5. Instalar dependências
cd api_proapoio
composer install

cd ../frontend_proapoio
npm install
```

### macOS

```bash
# 1. ChromeDriver
brew install chromedriver

# 2. PHP 8.2+
brew install php@8.2

# 3. Composer
brew install composer

# 4. Node.js + npm
brew install node

# 5. Instalar dependências
cd api_proapoio
composer install

cd ../frontend_proapoio
npm install
```

---

## 🖥️ Iniciar Serviços Antes de Executar os Testes

Você precisa de **3 terminais abertos** executando os seguintes comandos:

### Terminal 1: ChromeDriver

```bash
chromedriver --port=9515
```

**Saída esperada:**
```
ChromeDriver was started successfully.
```

### Terminal 2: Backend (Laravel)

```bash
cd api_proapoio
php artisan serve --port=8000
```

**Saída esperada:**
```
Laravel development server started: http://127.0.0.1:8000
```

### Terminal 3: Frontend (React)

```bash
cd frontend_proapoio
npm run dev
```

**Saída esperada:**
```
  VITE v5.x.x  ready in xxx ms

  ➜  Local:   http://localhost:5174/
```

### Terminal 4: Executar os Testes

```bash
# Windows
run_all_acceptance_tests.bat

# Linux/macOS
./run_all_acceptance_tests.sh
```

---

## 📊 Interpretando os Resultados

### ✅ Todos os Testes Passaram

```
========================================
   TODOS OS TESTES PASSARAM! ✓
========================================

Total de Suites Executadas: 8
Suites Aprovadas: 8
Suites Falhadas: 0
Taxa de Sucesso: 100%
```

**Próximos passos:**
- Revisar logs em `tests/_output/` (opcional)
- Executar testes unitários: `php artisan test`
- Commit das mudanças

---

### ❌ Alguns Testes Falharam

```
========================================
   ALGUNS TESTES FALHARAM ✗
========================================

Suites que falharam:
  - VagaCest
  - PropostaCest

Total de Suites Executadas: 8
Suites Aprovadas: 6
Suites Falhadas: 2
Taxa de Sucesso: 75%
```

**O que fazer:**

1. **Verificar Screenshots**
   ```bash
   cd api_proapoio/tests/_output
   # Abrir arquivos .png para ver onde falhou
   ```

2. **Verificar Logs**
   ```bash
   cd api_proapoio/tests/_output
   # Abrir arquivos .html ou .txt para detalhes
   ```

3. **Executar Teste Falhado com Debug**
   ```bash
   cd api_proapoio
   vendor/bin/codecept run acceptance VagaCest --debug
   ```

4. **Executar Teste Específico**
   ```bash
   # Executar apenas um método de teste
   vendor/bin/codecept run acceptance VagaCest:testCriarVaga --debug
   ```

---

## 🐛 Troubleshooting Comum

### Problema 1: "ChromeDriver não encontrado no PATH"

**Solução Windows:**
```cmd
# Baixar ChromeDriver de: https://chromedriver.chromium.org/
# Colocar chromedriver.exe em: C:\Windows\System32
# OU adicionar pasta ao PATH
```

**Solução Linux/macOS:**
```bash
# Ubuntu/Debian
sudo apt-get install chromium-chromedriver

# macOS
brew install chromedriver
```

---

### Problema 2: "Backend NÃO está rodando"

**Verificar se a porta 8000 está em uso:**

Windows:
```cmd
netstat -ano | findstr :8000
```

Linux/macOS:
```bash
lsof -i :8000
```

**Iniciar backend:**
```bash
cd api_proapoio
php artisan serve --port=8000
```

---

### Problema 3: "Frontend NÃO está rodando"

**Verificar se a porta 5174 está em uso:**

Windows:
```cmd
netstat -ano | findstr :5174
```

Linux/macOS:
```bash
lsof -i :5174
```

**Iniciar frontend:**
```bash
cd frontend_proapoio
npm run dev
```

---

### Problema 4: "Teste falhou com erro de timeout"

**Causas comuns:**
- Frontend/Backend lento demão
- ChromeDriver perdeu conexão
- Elemento na página não foi encontrado

**Soluções:**
```bash
# 1. Aumentar timeout no teste (editar arquivo de teste)
$I->wait(5); // Aumentar de 1 para 5 segundos

# 2. Executar com debug para ver exatamente onde parou
vendor/bin/codecept run acceptance NomeDaSuite --debug

# 3. Pausar execução para inspeção manual
# Adicionar no teste: $I->pauseExecution();
```

---

### Problema 5: "Banco de dados não está configurado"

**Solução:**
```bash
# 1. Copiar .env.example para .env
cd api_proapoio
cp .env.example .env

# 2. Configurar banco de dados no .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proapoio
DB_USERNAME=root
DB_PASSWORD=sua_senha

# 3. Executar migrations
php artisan migrate

# 4. Seed (opcional, para dados de teste)
php artisan db:seed
```

---

## 🔧 Opções Avançadas

### Executar com Relatório HTML

```bash
# Windows
cd api_proapoio
vendor\bin\codecept run acceptance --html

# Linux/macOS
cd api_proapoio
vendor/bin/codecept run acceptance --html

# Abrir relatório
# Arquivo gerado em: tests/_output/report.html
```

---

### Executar Apenas Testes Rápidos

Se você quiser executar apenas alguns testes para verificação rápida:

```bash
# Apenas autenticação
vendor/bin/codecept run acceptance AuthCest

# Apenas fluxos completos (novos)
vendor/bin/codecept run acceptance FluxosCompletosECompletosCest

# Múltiplas suites
vendor/bin/codecept run acceptance AuthCest,VagaCest
```

---

### Executar com Modo Verbose

```bash
# Mais detalhes durante execução
vendor/bin/codecept run acceptance --verbose

# Ou usar --debug para detalhes completos
vendor/bin/codecept run acceptance --debug
```

---

## 📁 Estrutura de Output

Após executar os testes, você encontrará:

```
api_proapoio/tests/_output/
├── *.png                    # Screenshots de falhas
├── *.html                   # Páginas HTML salvas
├── *.fail.html             # Páginas onde teste falhou
├── report.html             # Relatório HTML (se --html)
└── *.log                   # Logs de execução
```

---

## 🎯 Próximos Passos Após Execução

### Se Todos os Testes Passaram ✅

1. **Executar Testes Unitários e Feature**
   ```bash
   cd api_proapoio
   php artisan test
   ```

2. **Gerar Relatório de Cobertura (opcional)**
   ```bash
   php artisan test --coverage
   ```

3. **Commit das Mudanças**
   ```bash
   git add .
   git commit -m "feat: testes de aceitação passando"
   git push
   ```

---

### Se Alguns Testes Falharam ❌

1. **Analisar Falhas**
   - Verificar screenshots em `tests/_output/`
   - Ler logs de erro

2. **Executar Teste Isolado com Debug**
   ```bash
   vendor/bin/codecept run acceptance NomeDaSuite:nomeDoTeste --debug
   ```

3. **Corrigir Problemas**
   - Atualizar seletores CSS se necessário
   - Ajustar timeouts
   - Corrigir bugs no código

4. **Re-executar Testes**
   ```bash
   ./run_all_acceptance_tests.sh
   ```

---

## 📞 Suporte

**Problemas com os scripts?**
- Verifique os pré-requisitos acima
- Consulte a seção de Troubleshooting
- Leia logs em `tests/_output/`

**Problemas com os testes?**
- Consulte `README_TESTES_COMPLETOS.md`
- Consulte `ANALISE_COMPLETA_FLUXOS_E_TESTES.md`

---

## 📝 Changelog dos Scripts

### Versão 1.0 (2025-01-16)
- ✅ Script inicial para Windows (.bat)
- ✅ Script inicial para Linux/macOS (.sh)
- ✅ Verificação automática de pré-requisitos
- ✅ Verificação de serviços (ChromeDriver, Backend, Frontend)
- ✅ Execução em ordem lógica (8 suites)
- ✅ Relatório detalhado de resultados
- ✅ Tratamento de erros
- ✅ Instruções claras em caso de falha

---

**Última Atualização:** 2025-01-16
**Versão dos Scripts:** 1.0
**Compatibilidade:** Windows 7+, Linux (Ubuntu 18.04+), macOS 10.14+
