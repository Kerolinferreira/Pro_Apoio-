@echo off
REM ################################################################################
REM SCRIPT DE INSTALACAO AUTOMATIZADA - PRO APOIO (WINDOWS)
REM
REM Este script automatiza a instalacao e configuracao do projeto Pro Apoio
REM
REM Uso: install.bat
REM
REM Autor: Claude Code
REM Data: 2025-11-08
REM ################################################################################

setlocal enabledelayedexpansion
color 0B

echo.
echo ===============================================================
echo.
echo            INSTALACAO AUTOMATIZADA - PRO APOIO
echo.
echo   Este script ira configurar o projeto do zero
echo.
echo ===============================================================
echo.

REM Verificar pre-requisitos
echo [PASSO 1/10] Verificando pre-requisitos...
echo.

REM PHP
where php >nul 2>&1
if %errorlevel% equ 0 (
    for /f "tokens=2" %%i in ('php -v ^| findstr /C:"PHP"') do (
        echo [OK] PHP %%i encontrado
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
        echo [OK] Node.js %%i encontrado
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

REM Configurar backend
echo [PASSO 2/10] Instalando dependencias do backend (Composer)...
cd api_proapoio
call composer install --quiet
if %errorlevel% neq 0 (
    echo [ERRO] Falha ao instalar dependencias do backend
    pause
    exit /b 1
)
echo [OK] Dependencias do backend instaladas
echo.

REM Configurar .env do backend
echo [PASSO 3/10] Configurando arquivo .env do backend...
if not exist .env (
    if exist .env.example (
        copy .env.example .env >nul
        echo [OK] Arquivo .env criado a partir de .env.example
    ) else (
        echo [AVISO] Arquivo .env.example nao encontrado
        echo Voce precisara criar o arquivo .env manualmente
    )
) else (
    echo [AVISO] Arquivo .env ja existe, pulando...
)
echo.

REM Gerar chaves
echo [PASSO 4/10] Gerando chaves de seguranca...
call php artisan key:generate --quiet
if %errorlevel% neq 0 (
    echo [ERRO] Falha ao gerar chave da aplicacao
) else (
    echo [OK] Chave da aplicacao gerada
)

REM Tentar gerar chave JWT
call php artisan jwt:secret --quiet >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Chave JWT gerada
) else (
    echo [AVISO] Comando jwt:secret nao encontrado (pode nao estar instalado)
)
echo.

REM Storage link
echo [PASSO 5/10] Criando link simbolico para storage...
call php artisan storage:link --quiet >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Link de storage configurado
) else (
    echo [AVISO] Link ja existe ou falhou
)
echo.

REM Permissoes (Windows)
echo [PASSO 6/10] Configurando permissoes de storage...
icacls storage /grant Everyone:F /t /q >nul 2>&1
icacls bootstrap\cache /grant Everyone:F /t /q >nul 2>&1
echo [OK] Permissoes configuradas
echo.

REM Migrations
echo [PASSO 7/10] Executando migrations do banco de dados...
echo.
echo ATENCAO: Certifique-se de que o banco de dados esta criado e configurado no .env
echo.
set /p run_migrations="Deseja executar as migrations agora? (s/n): "
if /i "%run_migrations%"=="s" (
    call php artisan migrate --force
    if %errorlevel% neq 0 (
        echo [ERRO] Falha ao executar migrations
        echo Verifique a configuracao do banco de dados no .env
    ) else (
        echo [OK] Migrations executadas
        echo.
        set /p run_seeders="Deseja executar os seeders para dados de teste? (s/n): "
        if /i "!run_seeders!"=="s" (
            call php artisan db:seed --force
            if %errorlevel% neq 0 (
                echo [ERRO] Falha ao executar seeders
            ) else (
                echo [OK] Seeders executados
            )
        ) else (
            echo [AVISO] Seeders nao executados
        )
    )
) else (
    echo [AVISO] Migrations nao executadas - voce precisara executar manualmente:
    echo   cd api_proapoio
    echo   php artisan migrate
)
echo.

REM Limpar cache
echo [PASSO 8/10] Limpando cache...
call php artisan config:clear --quiet >nul 2>&1
call php artisan cache:clear --quiet >nul 2>&1
call php artisan route:clear --quiet >nul 2>&1
call php artisan view:clear --quiet >nul 2>&1
echo [OK] Cache limpo
echo.

REM Voltar para raiz e configurar frontend
cd ..

echo [PASSO 9/10] Instalando dependencias do frontend (npm)...
cd frontend_proapoio
call npm install --silent
if %errorlevel% neq 0 (
    echo [ERRO] Falha ao instalar dependencias do frontend
    pause
    exit /b 1
)
echo [OK] Dependencias do frontend instaladas
echo.

REM Configurar .env do frontend
echo [PASSO 10/10] Configurando arquivo .env do frontend...
if not exist .env (
    (
        echo VITE_API_URL=http://localhost:8000/api
        echo VITE_APP_NAME="Pro Apoio"
    ) > .env
    echo [OK] Arquivo .env do frontend criado
) else (
    echo [AVISO] Arquivo .env do frontend ja existe, pulando...
)

cd ..

echo.
echo ===============================================================
echo.
echo              INSTALACAO CONCLUIDA COM SUCESSO!
echo.
echo ===============================================================
echo.
echo.
echo PROXIMOS PASSOS:
echo.
echo 1. Configure o banco de dados no arquivo: api_proapoio\.env
echo    - DB_CONNECTION=mysql
echo    - DB_HOST=127.0.0.1
echo    - DB_PORT=3306
echo    - DB_DATABASE=proapoio
echo    - DB_USERNAME=seu_usuario
echo    - DB_PASSWORD=sua_senha
echo.
echo 2. Execute as migrations (se ainda nao executou):
echo    cd api_proapoio
echo    php artisan migrate
echo.
echo 3. Inicie o backend em um terminal:
echo    cd api_proapoio
echo    php artisan serve
echo.
echo 4. Inicie o frontend em outro terminal:
echo    cd frontend_proapoio
echo    npm run dev
echo.
echo 5. Acesse no navegador:
echo    http://localhost:5174
echo.
echo Boa sorte com o projeto Pro Apoio!
echo.
pause
