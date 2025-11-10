@echo off
REM ################################################################################
REM SCRIPT DE INSTALACAO E EXECUCAO AUTOMATIZADA - PRO APOIO (WINDOWS)
REM
REM Este script automatiza a instalacao, configuracao e execucao do projeto.
REM - Verifica se os passos ja foram executados.
REM - Se tudo estiver pronto, inicia os servidores.
REM
REM Uso: install.bat
REM
REM Autor: Claude Code & Gemini
REM Data: 2025-11-10
REM ################################################################################

setlocal enabledelayedexpansion
color 0B

echo.
echo ===============================================================
echo.
echo      INSTALACAO E EXECUCAO AUTOMATIZADA - PRO APOIO
echo.
echo   Este script ira verificar e configurar o projeto.
echo   Se a configuracao ja estiver completa, os servidores
echo   serao iniciados.
echo.
echo ===============================================================
echo.

REM Variavel de controle. 1 = tudo pronto, 0 = algo foi instalado
set "SETUP_COMPLETE=1"

REM Verificar pre-requisitos
echo [PASSO 1/11] Verificando pre-requisitos...
echo.

REM PHP
where php >nul 2>&1
if %errorlevel% equ 0 (
    for /f "tokens=2 delims= " %%i in ('php -v ^| findstr /C:"PHP"') do (
        set PHP_VERSION=%%i
        echo [OK] PHP %%i encontrado
        REM Verificar versao minima (8.1)
        for /f "tokens=1,2 delims=." %%a in ("%%i") do (
            set PHP_MAJOR=%%a
            set PHP_MINOR=%%b
        )
        if !PHP_MAJOR! LSS 8 (
            echo [ERRO] PHP 8.1+ e necessario. Versao atual: %%i
            echo Baixe em: https://windows.php.net/download/
            pause
            exit /b 1
        )
        if !PHP_MAJOR! EQU 8 if !PHP_MINOR! LSS 1 (
            echo [ERRO] PHP 8.1+ e necessario. Versao atual: %%i
            echo Baixe em: https://windows.php.net/download/
            pause
            exit /b 1
        )
        goto :composer_check
    )
) else (
    echo [ERRO] PHP nao encontrado. Instale PHP 8.1+ antes de continuar.
    echo Baixe em: https://windows.php.net/download/
    pause
    exit /b 1
)

:composer_check
REM Composer
where composer >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Composer encontrado
) else (
    echo [ERRO] Composer nao encontrado. Instale Composer antes de continuar.
    echo Baixe em: https://getcomposer.org/download/
    pause
    exit /b 1
)

REM Node.js
where node >nul 2>&1
if %errorlevel% equ 0 (
    for /f "tokens=*" %%i in ('node --version') do (
        set NODE_VERSION=%%i
        echo [OK] Node.js %%i encontrado
        REM Verificar versao minima (18)
        set NODE_VER=%%i
        set NODE_VER=!NODE_VER:v=!
        for /f "tokens=1 delims=." %%a in ("!NODE_VER!") do (
            set NODE_MAJOR=%%a
        )
        if !NODE_MAJOR! LSS 18 (
            echo [ERRO] Node.js 18+ e necessario. Versao atual: %%i
            echo Baixe em: https://nodejs.org/
            pause
            exit /b 1
        )
    )
) else (
    echo [ERRO] Node.js nao encontrado. Instale Node.js 18+ antes de continuar.
    echo Baixe em: https://nodejs.org/
    pause
    exit /b 1
)

REM npm
where npm >nul 2>&1
if %errorlevel% equ 0 (
    for /f "tokens=*" %%i in ('npm --version') do (
        echo [OK] npm %%i encontrado
    )
) else (
    echo [ERRO] npm nao encontrado. Instale npm antes de continuar.
    pause
    exit /b 1
)

echo.
echo.

REM --- BACKEND ---
cd api_proapoio

echo [PASSO 2/11] Verificando dependencias do backend (Composer)...
if not exist vendor (
    echo [INFO] Diretorio 'vendor' nao encontrado. Instalando dependencias...
    set "SETUP_COMPLETE=0"
    call composer install
    if %errorlevel% neq 0 (
        echo [ERRO] Falha ao instalar dependencias do backend (Composer).
        echo [INFO] Execute 'composer install' manualmente no diretorio api_proapoio.
        pause
        exit /b 1
    )
    echo [OK] Dependencias do backend (Composer) instaladas.
) else (
    echo [OK] Dependencias do backend (Composer) ja instaladas.
)
echo.

echo [PASSO 3/11] Verificando dependencias do backend (NPM)...
if not exist node_modules (
    echo [INFO] Diretorio 'node_modules' nao encontrado. Instalando dependencias...
    set "SETUP_COMPLETE=0"
    call npm install
    if %errorlevel% neq 0 (
        echo [ERRO] Falha ao instalar dependencias do backend (NPM).
        echo [INFO] Execute 'npm install' manualmente no diretorio api_proapoio.
        pause
        exit /b 1
    )
    echo [OK] Dependencias do backend (NPM) instaladas.
) else (
    echo [OK] Dependencias do backend (NPM) ja instaladas.
)
echo.

echo [PASSO 4/11] Verificando arquivo .env do backend...
if not exist .env (
    echo [INFO] Arquivo .env nao encontrado. Criando...
    set "SETUP_COMPLETE=0"
    if exist .env.example (
        copy .env.example .env >nul
        echo [OK] Arquivo .env criado a partir de .env.example.
    ) else (
        echo [AVISO] .env.example nao encontrado. Voce precisara criar o .env manualmente.
    )
) else (
    echo [OK] Arquivo .env do backend ja existe.
)
echo.

echo [PASSO 5/11] Verificando chave de seguranca do backend...
findstr /R /C:"^APP_KEY=$" .env >nul
if %errorlevel% equ 0 (
    echo [INFO] Chave de seguranca (APP_KEY) nao definida. Gerando...
    set "SETUP_COMPLETE=0"
    call php artisan key:generate
    if %errorlevel% neq 0 (
        echo [ERRO] Falha ao gerar chave da aplicacao.
        pause
        exit /b 1
    ) else (
        echo [OK] Chave da aplicacao gerada com sucesso.
    )
) else (
    echo [OK] Chave de seguranca (APP_KEY) ja definida.
)
echo.

echo [PASSO 6/11] Verificando chave JWT...
findstr /R /C:"^JWT_SECRET=" .env >nul
if %errorlevel% neq 0 (
    echo [INFO] Chave JWT nao encontrada. Gerando...
    set "SETUP_COMPLETE=0"
    call php artisan jwt:secret --quiet >nul 2>&1
    if %errorlevel% equ 0 (
        echo [OK] Chave JWT gerada.
    ) else (
        echo [AVISO] Comando jwt:secret nao encontrado ou falhou.
        echo [INFO] Voce pode precisar gerar manualmente com: php artisan jwt:secret
    )
) else (
    echo [OK] Chave JWT ja definida.
)
echo.

echo [PASSO 7/11] Verificando link de storage...
if not exist public\storage (
    echo [INFO] Link de storage nao encontrado. Criando...
    set "SETUP_COMPLETE=0"
    call php artisan storage:link --quiet >nul 2>&1
    if %errorlevel% equ 0 (
        echo [OK] Link de storage configurado.
    ) else (
        echo [AVISO] Falha ao criar link de storage.
    )
) else (
    echo [OK] Link de storage ja existe.
)
echo.

echo [PASSO 8/11] Verificando permissoes de storage (Windows)...
icacls storage /grant Everyone:F /t /q >nul 2>&1
icacls bootstrap\cache /grant Everyone:F /t /q >nul 2>&1
echo [OK] Permissoes de storage verificadas/aplicadas.
echo.

REM --- FRONTEND ---
cd ..\frontend_proapoio

echo [PASSO 9/11] Verificando dependencias do frontend (NPM)...
if not exist node_modules (
    echo [INFO] Diretorio 'node_modules' do frontend nao encontrado. Instalando...
    set "SETUP_COMPLETE=0"
    call npm install
    if %errorlevel% neq 0 (
        echo [ERRO] Falha ao instalar dependencias do frontend.
        echo [INFO] Execute 'npm install' manualmente no diretorio frontend_proapoio.
        pause
        exit /b 1
    )
    echo [OK] Dependencias do frontend instaladas.
) else (
    echo [OK] Dependencias do frontend ja instaladas.
)
echo.

echo [PASSO 10/11] Verificando arquivo .env do frontend...
if not exist .env (
    echo [INFO] Arquivo .env do frontend nao encontrado. Criando...
    set "SETUP_COMPLETE=0"
    (
        echo VITE_API_URL=http://127.0.0.1:8000/api
        echo VITE_APP_NAME="Pro Apoio"
    ) > .env
    echo [OK] Arquivo .env do frontend criado com a URL da API.
) else (
    echo [OK] Arquivo .env do frontend ja existe.
)
echo.

cd ..

REM --- FINALIZACAO ---
echo [PASSO 11/11] Verificando configuracao do banco de dados...
cd api_proapoio
set "DB_CONFIGURED=0"
findstr /R /C:"^DB_DATABASE=.*[^[:space:]]" .env >nul
if %errorlevel% equ 0 (
    findstr /R /C:"^DB_DATABASE=your_database" .env >nul
    if %errorlevel% neq 0 (
        set "DB_CONFIGURED=1"
    )
)

if "%DB_CONFIGURED%"=="1" (
    echo [OK] Banco de dados parece estar configurado.
    echo.
    echo [INFO] Verificando se migrations foram executadas...
    php artisan migrate:status >nul 2>&1
    if %errorlevel% neq 0 (
        echo [INFO] Migrations ainda nao foram executadas.
        echo.
        set /p RUN_MIGRATIONS="Deseja executar as migrations agora? (S/N): "
        if /i "!RUN_MIGRATIONS!"=="S" (
            echo [INFO] Executando migrations...
            call php artisan migrate --force
            if %errorlevel% neq 0 (
                echo [ERRO] Falha ao executar migrations.
                echo [INFO] Verifique a configuracao do banco de dados no arquivo .env
                cd ..
                pause
                exit /b 1
            )
            echo [OK] Migrations executadas com sucesso.
        ) else (
            echo [AVISO] Migrations nao executadas. Execute manualmente: php artisan migrate
        )
    ) else (
        echo [OK] Migrations ja foram executadas.
    )
) else (
    echo [AVISO] Banco de dados nao esta configurado.
)
echo.

cd ..

REM Verificar se portas estao em uso
echo [INFO] Verificando disponibilidade das portas...
netstat -ano | findstr ":8000" | findstr "LISTENING" >nul
if %errorlevel% equ 0 (
    echo [AVISO] Porta 8000 ja esta em uso. O backend pode nao iniciar corretamente.
)

netstat -ano | findstr ":5174" | findstr "LISTENING" >nul
if %errorlevel% equ 0 (
    echo [AVISO] Porta 5174 ja esta em uso. O frontend pode nao iniciar corretamente.
)
echo.

if "%SETUP_COMPLETE%"=="1" (
    if "%DB_CONFIGURED%"=="1" (
        echo ===============================================================
        echo.
        echo      SETUP COMPLETO. INICIANDO SERVIDORES...
        echo.
        echo ===============================================================
        echo.
        echo [INFO] Iniciando servidor do Backend (API) em uma nova janela...
        start "ProApoio - Backend" cmd /c "cd api_proapoio && php artisan serve"

        echo [INFO] Aguardando 3 segundos para o backend iniciar...
        timeout /t 3 /nobreak >nul

        echo [INFO] Iniciando servidor do Frontend em uma nova janela...
        start "ProApoio - Frontend" cmd /c "cd frontend_proapoio && npm run dev -- --port 5174"

        echo.
        echo [OK] Servidores iniciados. Verifique as novas janelas do terminal.
        echo    - Backend (API) em: http://127.0.0.1:8000
        echo    - Frontend em: http://localhost:5174
        echo.
) else (
    echo ===============================================================
    echo.
    echo      INSTALACAO CONCLUIDA! AGORA FALTAM PASSOS MANUAIS.
    echo.
    echo ===============================================================
    echo.
    echo.
    echo PROXIMOS PASSOS:
    echo.
    echo 1. CRIE UM BANCO DE DADOS (ex: 'proapoio').
    echo.
    echo 2. CONFIGURE O BANCO DE DADOS no arquivo: api_proapoio\.env
    echo    - DB_DATABASE=proapoio
    echo    - DB_USERNAME=seu_usuario
    echo    - DB_PASSWORD=sua_senha
    echo.
    echo 3. EXECUTE AS MIGRATIONS (apos configurar o .env):
    echo    cd api_proapoio
    echo    php artisan migrate
    echo.
    echo 4. INICIE OS SERVIDORES (execute em terminais separados):
    echo    - Backend: cd api_proapoio && php artisan serve
    echo    - Frontend: cd frontend_proapoio && npm run dev -- --port 5174
    echo.
    echo    OU execute este script novamente apos configurar o banco de dados.
    echo.
)

pause
