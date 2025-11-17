@echo off
setlocal enabledelayedexpansion

REM ================================================================================
REM Script de Execução Completa de Testes de Aceitação (Codeception + ChromeDriver)
REM Projeto: Pro Apoio
REM ================================================================================

echo.
echo ================================================================================
echo     TESTES DE ACEITACAO - PRO APOIO
echo     Execucao Completa com ChromeDriver
echo ================================================================================
echo.

REM Cores para output (Windows 10+)
REM Verificar se está no diretório correto
if not exist "api_proapoio" (
    echo [ERRO] Este script deve ser executado na raiz do projeto Pro_Apoio-
    echo Diretorio atual: %CD%
    pause
    exit /b 1
)

cd api_proapoio

echo [INFO] Iniciando verificacoes de pre-requisitos...
echo.

REM ================================================================================
REM 1. VERIFICAR PRE-REQUISITOS
REM ================================================================================

echo [1/6] Verificando ChromeDriver...
where chromedriver >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERRO] ChromeDriver nao encontrado no PATH
    echo.
    echo Solucoes:
    echo 1. Baixe de: https://chromedriver.chromium.org/
    echo 2. Adicione ao PATH ou coloque na pasta do projeto
    echo.
    pause
    exit /b 1
)
echo [OK] ChromeDriver encontrado

echo.
echo [2/6] Verificando PHP...
where php >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERRO] PHP nao encontrado no PATH
    pause
    exit /b 1
)
echo [OK] PHP encontrado

echo.
echo [3/6] Verificando Composer...
where composer >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERRO] Composer nao encontrado no PATH
    pause
    exit /b 1
)
echo [OK] Composer encontrado

echo.
echo [4/6] Verificando dependencias do Codeception...
if not exist "vendor\bin\codecept" (
    echo [AVISO] Codeception nao encontrado
    echo [INFO] Instalando dependencias...
    composer install --no-interaction
    if %ERRORLEVEL% NEQ 0 (
        echo [ERRO] Falha ao instalar dependencias
        pause
        exit /b 1
    )
)
echo [OK] Codeception disponivel

echo.
echo [5/6] Verificando estrutura de testes...
if not exist "tests\Acceptance" (
    echo [ERRO] Pasta tests\Acceptance nao encontrada
    pause
    exit /b 1
)
echo [OK] Estrutura de testes OK

echo.
echo [6/6] Construindo helpers do Codeception...
vendor\bin\codecept build 2>&1 | findstr /V "[32m" | findstr /V "[39m"
set BUILD_RESULT=%ERRORLEVEL%
if %BUILD_RESULT% NEQ 0 (
    echo [AVISO] Erro ao construir helpers (codigo: %BUILD_RESULT%, continuando...)
) else (
    echo [OK] Helpers construidos com sucesso
)

echo.
echo ================================================================================
echo PRE-REQUISITOS VERIFICADOS
echo ================================================================================
echo.

REM ================================================================================
REM 2. VERIFICAR SERVIÇOS NECESSÁRIOS
REM ================================================================================

echo [INFO] Verificando servicos necessarios...
echo.

echo Verificando se ChromeDriver esta rodando na porta 9515...
netstat -an | findstr "9515" | findstr "LISTENING" >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [AVISO] ChromeDriver NAO esta rodando na porta 9515
    echo.
    echo Para iniciar o ChromeDriver, abra um NOVO terminal e execute:
    echo     chromedriver --port=9515
    echo.
    echo Pressione qualquer tecla apos iniciar o ChromeDriver...
    pause >nul

    REM Verificar novamente
    netstat -an | findstr "9515" | findstr "LISTENING" >nul 2>&1
    if %ERRORLEVEL% NEQ 0 (
        echo [ERRO] ChromeDriver ainda nao detectado. Certifique-se de inicia-lo.
        pause
        exit /b 1
    )
)
echo [OK] ChromeDriver detectado na porta 9515

echo.
echo Verificando se Backend (Laravel) esta rodando...
curl -s http://localhost:8000 >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [AVISO] Backend NAO esta rodando em http://localhost:8000
    echo.
    echo Para iniciar o backend, abra um NOVO terminal e execute:
    echo     cd api_proapoio
    echo     php artisan serve --port=8000
    echo.
    echo Pressione qualquer tecla apos iniciar o backend...
    pause >nul

    REM Verificar novamente
    curl -s http://localhost:8000 >nul 2>&1
    if %ERRORLEVEL% NEQ 0 (
        echo [ERRO] Backend ainda nao detectado.
        pause
        exit /b 1
    )
)
echo [OK] Backend detectado em http://localhost:8000

echo.
echo Verificando se Frontend (React) esta rodando...
curl -s http://localhost:5174 >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [AVISO] Frontend NAO esta rodando em http://localhost:5174
    echo.
    echo Para iniciar o frontend, abra um NOVO terminal e execute:
    echo     cd frontend_proapoio
    echo     npm run dev
    echo.
    echo Pressione qualquer tecla apos iniciar o frontend...
    pause >nul

    REM Verificar novamente
    curl -s http://localhost:5174 >nul 2>&1
    if %ERRORLEVEL% NEQ 0 (
        echo [ERRO] Frontend ainda nao detectado.
        pause
        exit /b 1
    )
)
echo [OK] Frontend detectado em http://localhost:5174

echo.
echo ================================================================================
echo TODOS OS SERVICOS ESTAO RODANDO
echo ================================================================================
echo.

REM ================================================================================
REM 3. LIMPAR OUTPUTS ANTERIORES
REM ================================================================================

echo [INFO] Limpando outputs de testes anteriores...
if exist "tests\_output\*" (
    del /Q "tests\_output\*" >nul 2>&1
)
echo [OK] Output limpo

echo.
echo ================================================================================
echo INICIANDO EXECUCAO DOS TESTES
echo ================================================================================
echo.

REM Variáveis de controle
set TOTAL_TESTS=0
set PASSED_TESTS=0
set FAILED_TESTS=0
set SKIPPED_TESTS=0

REM ================================================================================
REM 4. EXECUTAR TESTES EM ORDEM LÓGICA
REM ================================================================================

echo.
echo ========================================
echo FASE 1: TESTES DE AUTENTICACAO
echo ========================================
echo.

echo [1/7] Executando AuthCest (Autenticacao)...
echo ----------------------------------------
vendor\bin\codecept run Acceptance AuthCest --no-interaction --no-colors
set /a TOTAL_TESTS+=1
if %ERRORLEVEL% EQU 0 (
    echo [OK] AuthCest - PASSOU
    set /a PASSED_TESTS+=1
) else (
    echo [FALHA] AuthCest - FALHOU
    set /a FAILED_TESTS+=1
)
echo.

echo.
echo ========================================
echo FASE 2: TESTES DE PERFIS
echo ========================================
echo.

echo [2/7] Executando CandidatoCest (Perfil Candidato)...
echo ----------------------------------------
vendor\bin\codecept run Acceptance CandidatoCest --no-interaction --no-colors
set /a TOTAL_TESTS+=1
if %ERRORLEVEL% EQU 0 (
    echo [OK] CandidatoCest - PASSOU
    set /a PASSED_TESTS+=1
) else (
    echo [FALHA] CandidatoCest - FALHOU
    set /a FAILED_TESTS+=1
)
echo.

echo [3/7] Executando InstituicaoCest (Perfil Instituicao)...
echo ----------------------------------------
vendor\bin\codecept run Acceptance InstituicaoCest --no-interaction --no-colors
set /a TOTAL_TESTS+=1
if %ERRORLEVEL% EQU 0 (
    echo [OK] InstituicaoCest - PASSOU
    set /a PASSED_TESTS+=1
) else (
    echo [FALHA] InstituicaoCest - FALHOU
    set /a FAILED_TESTS+=1
)
echo.

echo.
echo ========================================
echo FASE 3: TESTES DE VAGAS
echo ========================================
echo.

echo [4/7] Executando VagaCest (CRUD de Vagas)...
echo ----------------------------------------
vendor\bin\codecept run Acceptance VagaCest --no-interaction --no-colors
set /a TOTAL_TESTS+=1
if %ERRORLEVEL% EQU 0 (
    echo [OK] VagaCest - PASSOU
    set /a PASSED_TESTS+=1
) else (
    echo [FALHA] VagaCest - FALHOU
    set /a FAILED_TESTS+=1
)
echo.

echo.
echo ========================================
echo FASE 4: TESTES DE PROPOSTAS
echo ========================================
echo.

echo [5/7] Executando PropostaCest (Sistema de Propostas)...
echo ----------------------------------------
vendor\bin\codecept run Acceptance PropostaCest --no-interaction --no-colors
set /a TOTAL_TESTS+=1
if %ERRORLEVEL% EQU 0 (
    echo [OK] PropostaCest - PASSOU
    set /a PASSED_TESTS+=1
) else (
    echo [FALHA] PropostaCest - FALHOU
    set /a FAILED_TESTS+=1
)
echo.

echo.
echo ========================================
echo FASE 5: TESTES DE DASHBOARD E NOTIFICACOES
echo ========================================
echo.

echo [6/7] Executando DashboardCest (Dashboards)...
echo ----------------------------------------
vendor\bin\codecept run Acceptance DashboardCest --no-interaction --no-colors
set /a TOTAL_TESTS+=1
if %ERRORLEVEL% EQU 0 (
    echo [OK] DashboardCest - PASSOU
    set /a PASSED_TESTS+=1
) else (
    echo [FALHA] DashboardCest - FALHOU
    set /a FAILED_TESTS+=1
)
echo.

echo [INFO] Executando NotificationCest (Notificacoes)...
echo ----------------------------------------
vendor\bin\codecept run Acceptance NotificationCest --no-interaction --no-colors
set /a TOTAL_TESTS+=1
if %ERRORLEVEL% EQU 0 (
    echo [OK] NotificationCest - PASSOU
    set /a PASSED_TESTS+=1
) else (
    echo [FALHA] NotificationCest - FALHOU
    set /a FAILED_TESTS+=1
)
echo.

echo.
echo ========================================
echo FASE 6: TESTES DE FLUXOS COMPLETOS
echo ========================================
echo.

echo [7/7] Executando FluxosCompletosECompletosCest (Fluxos End-to-End)...
echo ----------------------------------------
vendor\bin\codecept run Acceptance FluxosCompletosECompletosCest --no-interaction --no-colors
set /a TOTAL_TESTS+=1
if %ERRORLEVEL% EQU 0 (
    echo [OK] FluxosCompletosECompletosCest - PASSOU
    set /a PASSED_TESTS+=1
) else (
    echo [FALHA] FluxosCompletosECompletosCest - FALHOU
    set /a FAILED_TESTS+=1
)
echo.

REM ================================================================================
REM 5. RELATÓRIO FINAL
REM ================================================================================

echo.
echo ================================================================================
echo RELATORIO FINAL - TESTES DE ACEITACAO
echo ================================================================================
echo.

REM Calcular porcentagem de sucesso
set /a SUCCESS_RATE=(%PASSED_TESTS% * 100) / %TOTAL_TESTS%

echo Total de Suites Executadas: %TOTAL_TESTS%
echo Suites Aprovadas: %PASSED_TESTS%
echo Suites Falhadas: %FAILED_TESTS%
echo Taxa de Sucesso: %SUCCESS_RATE%%%
echo.

if %FAILED_TESTS% EQU 0 (
    echo ========================================
    echo    TODOS OS TESTES PASSARAM!
    echo ========================================
    echo.
    echo Proximos passos:
    echo 1. Verifique o relatorio HTML (se gerado^)
    echo 2. Revise screenshots em tests\_output\ (se houver^)
    echo 3. Execute testes unitarios e feature: php artisan test
    echo.
) else (
    echo ========================================
    echo    ALGUNS TESTES FALHARAM
    echo ========================================
    echo.
    echo Por favor:
    echo 1. Verifique logs em tests\_output\
    echo 2. Verifique screenshots em tests\_output\
    echo 3. Execute testes falhados individualmente com --debug:
    echo    vendor\bin\codecept run Acceptance NomeDaSuite --debug
    echo.
)

echo ================================================================================
echo ORDEM DE EXECUCAO DOS TESTES
echo ================================================================================
echo.
echo 1. AuthCest - Autenticacao (registro, login, logout, recuperacao de senha^)
echo 2. CandidatoCest - Perfil e experiencias do candidato
echo 3. InstituicaoCest - Perfil e configuracoes da instituicao
echo 4. VagaCest - CRUD de vagas, busca e filtros
echo 5. PropostaCest - Envio, aceitacao e recusa de propostas
echo 6. DashboardCest - Dashboards e metricas
echo 7. NotificationCest - Sistema de notificacoes
echo 8. FluxosCompletosECompletosCest - Fluxos end-to-end completos (NOVO^)
echo.

echo ================================================================================
echo ARQUIVOS DE OUTPUT
echo ================================================================================
echo.
echo - Logs: tests\_output\
echo - Screenshots (se falhas^): tests\_output\
echo - Relatorio HTML: tests\_output\report.html (se --html foi usado^)
echo.

echo ================================================================================
echo Execucao concluida em: %DATE% %TIME%
echo ================================================================================
echo.

REM Pausar para visualizar resultados
pause

REM Retornar código de saída apropriado
if %FAILED_TESTS% GTR 0 (
    exit /b 1
) else (
    exit /b 0
)
