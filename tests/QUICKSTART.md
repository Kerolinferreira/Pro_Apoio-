# 🚀 Guia Rápido - Testes E2E ProApoio

## Início Rápido (5 minutos)

### 1️⃣ Instalar Dependências

```bash
# No diretório raiz do projeto
composer require facebook/webdriver
```

### 2️⃣ Baixar ChromeDriver

**Windows:**
- Acesse: https://chromedriver.chromium.org/downloads
- Baixe a versão compatível com seu Chrome
- Extraia para `C:\chromedriver\` ou diretório de sua preferência

**Linux/Mac:**
```bash
# Exemplo para Linux
wget https://chromedriver.storage.googleapis.com/114.0.5735.90/chromedriver_linux64.zip
unzip chromedriver_linux64.zip
sudo mv chromedriver /usr/local/bin/
sudo chmod +x /usr/local/bin/chromedriver
```

### 3️⃣ Iniciar Serviços

**Terminal 1 - ChromeDriver:**
```bash
chromedriver --port=9515
```

**Terminal 2 - Frontend:**
```bash
cd frontend_proapoio
npm run dev
# Aguardar iniciar em http://localhost:3074
```

**Terminal 3 - Backend:**
```bash
cd api_proapoio
php artisan serve
```

### 4️⃣ Configurar Banco de Dados

Edite as configurações em cada arquivo de teste se necessário:

```php
const DB_HOST = '127.0.0.1';
const DB_NAME = 'proapoio';
const DB_USER = 'root';
const DB_PASS = '1234';  // ⚠️ Altere se necessário
```

### 5️⃣ Executar Testes

**Terminal 4 - Testes:**
```bash
# Executar todos os testes
php tests/run_all.php

# Ou executar teste individual
php tests/01_LoginInstituicao.php
```

## 📊 Resultados

Após a execução:

✅ **Sucesso**: Exit code 0, todos os testes passaram
❌ **Falha**: Exit code 1, verifique screenshots em `tests/_output/screenshots/`

## 🔍 Verificação Rápida

Antes de rodar os testes, verifique:

```bash
# ✓ ChromeDriver está rodando?
curl http://127.0.0.1:9515/status

# ✓ Frontend está acessível?
curl http://localhost:3074

# ✓ Backend está respondendo?
curl http://localhost:8000/api/deficiencias

# ✓ Banco de dados está acessível?
mysql -u root -p1234 -e "USE proapoio; SELECT COUNT(*) FROM instituicoes;"
```

## 🐛 Problemas Comuns

### ChromeDriver não inicia
```bash
# Windows: Adicionar ao PATH
set PATH=%PATH%;C:\chromedriver

# Ou executar diretamente
C:\chromedriver\chromedriver.exe --port=9515
```

### Teste falha com "Connection refused"
```bash
# Verificar se ChromeDriver está rodando
netstat -an | grep 9515  # Linux/Mac
netstat -an | findstr 9515  # Windows
```

### Frontend não está na porta 3074
Edite `BASE_URL` nos arquivos de teste:
```php
const BASE_URL = 'http://localhost:PORTA_CORRETA/';
```

## 📝 Opções Avançadas

```bash
# Mostrar output detalhado de cada teste
php tests/run_all.php --verbose

# Parar na primeira falha
php tests/run_all.php --stop-on-failure

# Executar teste específico com output completo
php tests/03_CadastroCandidato.php 2>&1 | tee output.log
```

## 🎯 O Que os Testes Cobrem

| Teste | Descrição | Duração Aprox. |
|-------|-----------|----------------|
| 01_LoginInstituicao | Login e redirecionamento | ~15s |
| 02_CadastroVaga | Criar vaga completa | ~30s |
| 03_CadastroCandidato | Cadastro de candidato | ~35s |
| 04_VerCandidatosFiltros | Busca com filtros | ~25s |
| 05_FazerProposta | Enviar proposta | ~25s |
| 06_Acessibilidade | ARIA e tab order | ~20s |

**Total:** ~2-3 minutos

## 📞 Precisa de Ajuda?

1. ✅ Verificar logs do ChromeDriver
2. ✅ Analisar screenshots em `tests/_output/screenshots/`
3. ✅ Executar teste individual com output detalhado
4. ✅ Consultar [README.md](README.md) para troubleshooting completo

---

**Dica**: Execute os testes regularmente para garantir que novas mudanças não quebrem funcionalidades existentes!
