#!/bin/bash

################################################################################
# Script de Execução Completa de Testes de Aceitação (Codeception + ChromeDriver)
# Projeto: Pro Apoio
# Compatível com: Linux e macOS
################################################################################

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para imprimir com cor
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[AVISO]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERRO]${NC} $1"
}

print_header() {
    echo ""
    echo "================================================================================"
    echo "    $1"
    echo "================================================================================"
    echo ""
}

################################################################################
# INÍCIO DO SCRIPT
################################################################################

print_header "TESTES DE ACEITAÇÃO - PRO APOIO"
echo "Execução Completa com ChromeDriver"
echo ""

# Verificar se está no diretório correto
if [ ! -d "api_proapoio" ]; then
    print_error "Este script deve ser executado na raiz do projeto Pro_Apoio-"
    print_info "Diretório atual: $(pwd)"
    exit 1
fi

cd api_proapoio

print_info "Iniciando verificações de pré-requisitos..."
echo ""

################################################################################
# 1. VERIFICAR PRÉ-REQUISITOS
################################################################################

print_info "[1/6] Verificando ChromeDriver..."
if ! command -v chromedriver &> /dev/null; then
    print_error "ChromeDriver não encontrado no PATH"
    echo ""
    echo "Soluções:"
    echo "1. Instale via brew (macOS): brew install chromedriver"
    echo "2. Instale via apt (Ubuntu): apt-get install chromium-chromedriver"
    echo "3. Baixe de: https://chromedriver.chromium.org/"
    echo ""
    exit 1
fi
print_success "ChromeDriver encontrado"

echo ""
print_info "[2/6] Verificando PHP..."
if ! command -v php &> /dev/null; then
    print_error "PHP não encontrado no PATH"
    exit 1
fi
print_success "PHP encontrado ($(php -r 'echo PHP_VERSION;'))"

echo ""
print_info "[3/6] Verificando Composer..."
if ! command -v composer &> /dev/null; then
    print_error "Composer não encontrado no PATH"
    exit 1
fi
print_success "Composer encontrado"

echo ""
print_info "[4/6] Verificando dependências do Codeception..."
if [ ! -f "vendor/bin/codecept" ]; then
    print_warning "Codeception não encontrado"
    print_info "Instalando dependências..."
    composer install --no-interaction
    if [ $? -ne 0 ]; then
        print_error "Falha ao instalar dependências"
        exit 1
    fi
fi
print_success "Codeception disponível"

echo ""
print_info "[5/6] Verificando estrutura de testes..."
if [ ! -d "tests/Acceptance" ]; then
    print_error "Pasta tests/Acceptance não encontrada"
    exit 1
fi
print_success "Estrutura de testes OK"

echo ""
print_info "[6/6] Construindo helpers do Codeception..."
vendor/bin/codecept build > /dev/null 2>&1
if [ $? -ne 0 ]; then
    print_warning "Erro ao construir helpers (continuando...)"
else
    print_success "Helpers construídos"
fi

print_header "PRÉ-REQUISITOS VERIFICADOS"

################################################################################
# 2. VERIFICAR SERVIÇOS NECESSÁRIOS
################################################################################

print_info "Verificando serviços necessários..."
echo ""

print_info "Verificando se ChromeDriver está rodando na porta 9515..."
if lsof -Pi :9515 -sTCP:LISTEN -t >/dev/null 2>&1; then
    print_success "ChromeDriver detectado na porta 9515"
else
    echo ""
    print_warning "ChromeDriver NÃO está rodando na porta 9515"
    echo ""
    echo "Para iniciar o ChromeDriver, abra um NOVO terminal e execute:"
    echo "    chromedriver --port=9515"
    echo ""
    read -p "Pressione ENTER após iniciar o ChromeDriver..."

    # Verificar novamente
    if ! lsof -Pi :9515 -sTCP:LISTEN -t >/dev/null 2>&1; then
        print_error "ChromeDriver ainda não detectado. Certifique-se de iniciá-lo."
        exit 1
    fi
    print_success "ChromeDriver agora detectado"
fi

echo ""
print_info "Verificando se Backend (Laravel) está rodando..."
if curl -s http://localhost:8000 > /dev/null 2>&1; then
    print_success "Backend detectado em http://localhost:8000"
else
    echo ""
    print_warning "Backend NÃO está rodando em http://localhost:8000"
    echo ""
    echo "Para iniciar o backend, abra um NOVO terminal e execute:"
    echo "    cd api_proapoio"
    echo "    php artisan serve --port=8000"
    echo ""
    read -p "Pressione ENTER após iniciar o backend..."

    # Verificar novamente
    if ! curl -s http://localhost:8000 > /dev/null 2>&1; then
        print_error "Backend ainda não detectado."
        exit 1
    fi
    print_success "Backend agora detectado"
fi

echo ""
print_info "Verificando se Frontend (React) está rodando..."
if curl -s http://localhost:5174 > /dev/null 2>&1; then
    print_success "Frontend detectado em http://localhost:5174"
else
    echo ""
    print_warning "Frontend NÃO está rodando em http://localhost:5174"
    echo ""
    echo "Para iniciar o frontend, abra um NOVO terminal e execute:"
    echo "    cd frontend_proapoio"
    echo "    npm run dev"
    echo ""
    read -p "Pressione ENTER após iniciar o frontend..."

    # Verificar novamente
    if ! curl -s http://localhost:5174 > /dev/null 2>&1; then
        print_error "Frontend ainda não detectado."
        exit 1
    fi
    print_success "Frontend agora detectado"
fi

print_header "TODOS OS SERVIÇOS ESTÃO RODANDO"

################################################################################
# 3. LIMPAR OUTPUTS ANTERIORES
################################################################################

print_info "Limpando outputs de testes anteriores..."
rm -f tests/_output/* 2>/dev/null
print_success "Output limpo"

print_header "INICIANDO EXECUÇÃO DOS TESTES"

# Variáveis de controle
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Array para armazenar suites falhadas
declare -a FAILED_SUITES=()

################################################################################
# 4. EXECUTAR TESTES EM ORDEM LÓGICA
################################################################################

# Função para executar teste e contabilizar resultado
run_test() {
    local TEST_NAME=$1
    local TEST_DESCRIPTION=$2
    local TEST_NUMBER=$3

    echo ""
    echo "[$TEST_NUMBER/8] Executando $TEST_NAME ($TEST_DESCRIPTION)..."
    echo "----------------------------------------"

    vendor/bin/codecept run Acceptance "$TEST_NAME" --no-interaction
    local EXIT_CODE=$?

    ((TOTAL_TESTS++))

    if [ $EXIT_CODE -eq 0 ]; then
        print_success "$TEST_NAME - PASSOU"
        ((PASSED_TESTS++))
    else
        print_error "$TEST_NAME - FALHOU"
        ((FAILED_TESTS++))
        FAILED_SUITES+=("$TEST_NAME")
    fi
    echo ""
}

echo ""
echo "========================================"
echo "FASE 1: TESTES DE AUTENTICAÇÃO"
echo "========================================"
run_test "AuthCest" "Autenticação" 1

echo ""
echo "========================================"
echo "FASE 2: TESTES DE PERFIS"
echo "========================================"
run_test "CandidatoCest" "Perfil Candidato" 2
run_test "InstituicaoCest" "Perfil Instituição" 3

echo ""
echo "========================================"
echo "FASE 3: TESTES DE VAGAS"
echo "========================================"
run_test "VagaCest" "CRUD de Vagas" 4

echo ""
echo "========================================"
echo "FASE 4: TESTES DE PROPOSTAS"
echo "========================================"
run_test "PropostaCest" "Sistema de Propostas" 5

echo ""
echo "========================================"
echo "FASE 5: TESTES DE DASHBOARD E NOTIFICAÇÕES"
echo "========================================"
run_test "DashboardCest" "Dashboards" 6
run_test "NotificationCest" "Notificações" 7

echo ""
echo "========================================"
echo "FASE 6: TESTES DE FLUXOS COMPLETOS"
echo "========================================"
run_test "FluxosCompletosECompletosCest" "Fluxos End-to-End" 8

################################################################################
# 5. RELATÓRIO FINAL
################################################################################

print_header "RELATÓRIO FINAL - TESTES DE ACEITAÇÃO"

# Calcular porcentagem de sucesso
SUCCESS_RATE=$((PASSED_TESTS * 100 / TOTAL_TESTS))

echo "Total de Suites Executadas: $TOTAL_TESTS"
echo "Suites Aprovadas: $PASSED_TESTS"
echo "Suites Falhadas: $FAILED_TESTS"
echo "Taxa de Sucesso: $SUCCESS_RATE%"
echo ""

if [ $FAILED_TESTS -eq 0 ]; then
    echo "========================================"
    echo "   TODOS OS TESTES PASSARAM! ✓"
    echo "========================================"
    echo ""
    echo "Próximos passos:"
    echo "1. Verifique o relatório HTML (se gerado)"
    echo "2. Revise screenshots em tests/_output/ (se houver)"
    echo "3. Execute testes unitários e feature: php artisan test"
    echo ""
else
    echo "========================================"
    echo "   ALGUNS TESTES FALHARAM ✗"
    echo "========================================"
    echo ""
    echo "Suites que falharam:"
    for suite in "${FAILED_SUITES[@]}"; do
        echo "  - $suite"
    done
    echo ""
    echo "Por favor:"
    echo "1. Verifique logs em tests/_output/"
    echo "2. Verifique screenshots em tests/_output/"
    echo "3. Execute testes falhados individualmente com --debug:"
    echo "   vendor/bin/codecept run Acceptance NomeDaSuite --debug"
    echo ""
fi

print_header "ORDEM DE EXECUÇÃO DOS TESTES"

echo "1. AuthCest - Autenticação (registro, login, logout, recuperação de senha)"
echo "2. CandidatoCest - Perfil e experiências do candidato"
echo "3. InstituicaoCest - Perfil e configurações da instituição"
echo "4. VagaCest - CRUD de vagas, busca e filtros"
echo "5. PropostaCest - Envio, aceitação e recusa de propostas"
echo "6. DashboardCest - Dashboards e métricas"
echo "7. NotificationCest - Sistema de notificações"
echo "8. FluxosCompletosECompletosCest - Fluxos end-to-end completos (NOVO)"
echo ""

print_header "ARQUIVOS DE OUTPUT"

echo "- Logs: tests/_output/"
echo "- Screenshots (se falhas): tests/_output/"
echo "- Relatório HTML: tests/_output/report.html (se --html foi usado)"
echo ""

print_header "Execução concluída em: $(date)"

# Retornar código de saída apropriado
if [ $FAILED_TESTS -gt 0 ]; then
    exit 1
else
    exit 0
fi
