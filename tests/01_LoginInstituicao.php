<?php
/**
 * Teste E2E: Login de Instituição
 *
 * OBJETIVO:
 * Testar o fluxo completo de login de uma instituição, validando:
 * - Criação de instituição de teste no banco
 * - Preenchimento do formulário de login
 * - Autenticação bem-sucedida
 * - Redirecionamento correto para perfil da instituição
 * - Limpeza dos dados de teste
 *
 * PRÉ-REQUISITOS:
 * - ChromeDriver rodando em http://127.0.0.1:9515
 * - Frontend rodando em http://localhost:3074
 * - Backend API disponível
 * - Banco de dados 'proapoio' acessível
 *
 * COMO RODAR:
 * php tests/01_LoginInstituicao.php
 */

require 'vendor/autoload.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Exception\NoSuchElementException;

// Configurações do ambiente
const BASE_URL = 'http://localhost:3074/';
const WEBDRIVER_URL = 'http://127.0.0.1:9515';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'proapoio';
const DB_USER = 'root';
const DB_PASS = '1234';

$driver = null;
$pdo = null;
$testInstituicaoId = null;

try {
    echo "=== TESTE: Login de Instituição ===\n";

    // 1. Conectar ao banco de dados
    echo "[1/6] Conectando ao banco de dados...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✓ Conectado ao banco de dados\n";

    // 2. Criar instituição de teste
    echo "[2/6] Criando instituição de teste...\n";
    $testEmail = 'teste_instituicao_' . time() . '@example.com';
    $testPassword = 'SenhaSegura123!';
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO instituicoes (
            email,
            senha,
            nome_instituicao,
            cnpj,
            tipo_instituicao,
            telefone,
            cidade,
            estado,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $stmt->execute([
        $testEmail,
        $hashedPassword,
        'Instituição Teste Selenium',
        '12345678000199',
        'escola',
        '11999999999',
        'São Paulo',
        'SP'
    ]);

    $testInstituicaoId = $pdo->lastInsertId();
    echo "✓ Instituição criada com ID: {$testInstituicaoId}\n";

    // 3. Iniciar WebDriver
    echo "[3/6] Iniciando ChromeDriver...\n";
    $capabilities = DesiredCapabilities::chrome();
    $capabilities->setCapability('goog:chromeOptions', [
        'args' => [
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--window-size=1920,1080'
        ]
    ]);

    $driver = RemoteWebDriver::create(WEBDRIVER_URL, $capabilities, 60000, 60000);
    echo "✓ ChromeDriver iniciado\n";

    // 4. Navegar até página de login
    echo "[4/6] Navegando até a página de login...\n";
    $driver->get(BASE_URL . 'login');

    // Aguardar o formulário carregar
    // Seletor: input#email (campo de email na LoginPage.tsx linha 154)
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('email'))
    );
    echo "✓ Página de login carregada\n";

    // 5. Preencher formulário de login
    echo "[5/6] Preenchendo formulário de login...\n";

    // Campo Email (id="email" - LoginPage.tsx linha 154)
    $emailField = $driver->findElement(WebDriverBy::id('email'));
    $emailField->clear();
    $emailField->sendKeys($testEmail);

    // Campo Senha (id="password" - LoginPage.tsx linha 182)
    $passwordField = $driver->findElement(WebDriverBy::id('password'));
    $passwordField->clear();
    $passwordField->sendKeys($testPassword);

    echo "✓ Formulário preenchido\n";

    // Pequena pausa para garantir que os campos foram preenchidos
    sleep(1);

    // 6. Submeter formulário
    echo "[6/6] Submetendo formulário...\n";

    // Seletor: button[type="submit"] (LoginPage.tsx linha 229)
    $submitButton = $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'));
    $submitButton->click();

    // Aguardar redirecionamento para /perfil/instituicao (LoginPage.tsx linha 52)
    // ou /dashboard caso o comportamento mude
    try {
        $driver->wait(10)->until(function($driver) {
            $currentUrl = $driver->getCurrentURL();
            return strpos($currentUrl, '/perfil/instituicao') !== false
                || strpos($currentUrl, '/dashboard') !== false;
        });

        $finalUrl = $driver->getCurrentURL();
        echo "✓ Login bem-sucedido! Redirecionado para: {$finalUrl}\n";

        // Verificar se há elementos típicos da página de perfil da instituição
        // Header deve estar presente (componente Header.tsx)
        $driver->wait(5)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::tagName('header')
            )
        );

        echo "✓ Página de perfil carregada corretamente\n";

    } catch (TimeoutException $e) {
        throw new Exception("Timeout: Não foi possível confirmar o redirecionamento após login. URL atual: " . $driver->getCurrentURL());
    }

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";

    // Salvar screenshot em caso de erro
    if ($driver) {
        try {
            $screenshotPath = __DIR__ . '/_output/screenshots/01_LoginInstituicao_' . date('Y-m-d_H-i-s') . '.png';
            $driver->takeScreenshot($screenshotPath);
            echo "Screenshot salvo em: {$screenshotPath}\n";
        } catch (Exception $screenshotEx) {
            echo "Não foi possível salvar screenshot: " . $screenshotEx->getMessage() . "\n";
        }
    }

    exit(1);

} finally {
    // Limpeza: remover dados de teste
    if ($pdo && $testInstituicaoId) {
        try {
            echo "\nLimpando dados de teste...\n";
            $stmt = $pdo->prepare("DELETE FROM instituicoes WHERE id_instituicao = ?");
            $stmt->execute([$testInstituicaoId]);
            echo "✓ Instituição de teste removida\n";
        } catch (Exception $e) {
            echo "✗ Erro ao limpar dados: " . $e->getMessage() . "\n";
        }
    }

    // Fechar WebDriver
    if ($driver) {
        try {
            $driver->quit();
            echo "✓ WebDriver encerrado\n";
        } catch (Exception $e) {
            echo "✗ Erro ao encerrar WebDriver: " . $e->getMessage() . "\n";
        }
    }
}
