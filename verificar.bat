@echo off
REM Script de Verificacao Rapida - Pro Apoio (WINDOWS)
REM Verifica se o projeto esta pronto para execucao

setlocal enabledelayedexpansion
color 0B

echo.
echo Verificando o projeto Pro Apoio...
echo.

set PROBLEMS=0

REM 1. Verificar PHP
echo Verificando PHP...
where php >nul 2>&1
if %errorlevel% equ 0 (
    for /f "tokens=2" %%i in ('php -v ^| findstr /C:"PHP"') do (
        echo [OK] PHP %%i instalado
    )
) else (
    echo [ERRO] PHP nao encontrado
    set /a PROBLEMS+=1
)

REM 2. Verificar Composer
echo Verificando Composer...
where composer >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Composer instalado
) else (
    echo [ERRO] Composer nao encontrado
    set /a PROBLEMS+=1
)

REM 3. Verificar Node.js
echo Verificando Node.js...
where node >nul 2>&1
if %errorlevel% equ 0 (
    for /f "tokens=*" %%i in ('node --version') do (
        echo [OK] Node.js %%i instalado
    )
) else (
    echo [ERRO] Node.js nao encontrado
    set /a PROBLEMS+=1
)

REM 4. Verificar npm
echo Verificando npm...
where npm >nul 2>&1
if %errorlevel% equ 0 (
    for /f "tokens=*" %%i in ('npm --version') do (
        echo [OK] npm %%i instalado
    )
) else (
    echo [ERRO] npm nao encontrado
    set /a PROBLEMS+=1
)

REM 5. Verificar MySQL/MariaDB
echo Verificando MySQL/MariaDB...
where mysql >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] MySQL/MariaDB instalado
) else (
    echo [AVISO] MySQL/MariaDB nao encontrado no PATH
    echo          (pode estar instalado mas nao configurado)
)

echo.

REM 6. Verificar arquivos do backend
echo Verificando arquivos do backend...
if exist "api_proapoio\.env" (
    echo [OK] .env do backend existe
) else (
    echo [ERRO] .env do backend nao encontrado
    set /a PROBLEMS+=1
)

if exist "api_proapoio\vendor" (
    echo [OK] Dependencias do backend instaladas
) else (
    echo [ERRO] Dependencias do backend nao instaladas
    echo        Execute: cd api_proapoio ^&^& composer install
    set /a PROBLEMS+=1
)

echo.

REM 7. Verificar arquivos do frontend
echo Verificando arquivos do frontend...
if exist "frontend_proapoio\.env" (
    echo [OK] .env do frontend existe
) else (
    echo [AVISO] .env do frontend nao encontrado
    echo          Crie o arquivo com: VITE_API_URL=http://localhost:8000/api
)

if exist "frontend_proapoio\node_modules" (
    echo [OK] Dependencias do frontend instaladas
) else (
    echo [ERRO] Dependencias do frontend nao instaladas
    echo        Execute: cd frontend_proapoio ^&^& npm install
    set /a PROBLEMS+=1
)

echo.
echo ===============================================================

if %PROBLEMS% equ 0 (
    echo.
    echo [SUCESSO] Projeto pronto para execucao!
    echo.
    echo Para iniciar o projeto:
    echo   1. Terminal 1: cd api_proapoio ^&^& php artisan serve
    echo   2. Terminal 2: cd frontend_proapoio ^&^& npm run dev
    echo   3. Acesse: http://localhost:5174
    echo.
) else (
    echo.
    echo [ATENCAO] Foram encontrados %PROBLEMS% problema(s)
    echo.
    echo Execute o script de instalacao: install.bat
    echo Ou siga o guia: Documentos\GUIA_INSTALACAO_COMPLETO.md
    echo.
)

echo ===============================================================
echo.
pause
