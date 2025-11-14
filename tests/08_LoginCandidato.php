<?php
/**
 * Teste E2E: Login de Candidato
 *
 * OBJETIVO:
 * Testar o fluxo completo de login de um candidato, validando:
 * - Criação de candidato de teste no banco
 * - Preenchimento do formulário de login
 * - Autenticação bem-sucedida
 * - Redirecionamento correto para perfil do candidato
 * - Limpeza dos dados de teste
 *
 * COMO RODAR:
 * php tests/08_LoginCandidato.php
 */

require 'vendor/autoload.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

const BASE_URL = 'http://localhost:3074/';
const WEBDRIVER_URL = 'http://127.0.0.1:9515';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'proapoio';
const DB_USER = 'root';
const DB_PASS = '1234';

$driver = null;
$pdo = null;
$testCandidatoId = null;

try {
    echo "=== TESTE: Login de Candidato ===\n";

    // 1. Conectar ao banco e criar candidato de teste
    echo "[1/5] Preparando dados de teste...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $testEmail = 'teste_candidato_login_' . time() . '@example.com';
    $testPassword = 'SenhaSegura123!';
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO candidatos (
            email, senha, nome_completo, cpf, telefone, data_nascimento,
            cidade, estado, escolaridade, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $stmt->execute([
        $testEmail,
        $hashedPassword,
        'Candidato Teste Login',
        '99999999999',
        '11999999999',
        '1995-01-01',
        'São Paulo',
        'SP',
        'superior_completo'
    ]);

    $testCandidatoId = $pdo->lastInsertId();
    echo "✓ Candidato criado com ID: {$testCandidatoId}\n";

    // 2. Iniciar WebDriver
    echo "[2/5] Iniciando ChromeDriver...\n";
    $capabilities = DesiredCapabilities::chrome();
    $capabilities->setCapability('goog:chromeOptions', [
        'args' => ['--disable-gpu', '--no-sandbox', '--window-size=1920,1080']
    ]);

    $driver = RemoteWebDriver::create(WEBDRIVER_URL, $capabilities, 60000, 60000);
    echo "✓ ChromeDriver iniciado\n";

    // 3. Navegar até página de login
    echo "[3/5] Navegando até a página de login...\n";
    $driver->get(BASE_URL . 'login');

    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('email'))
    );
    echo "✓ Página de login carregada\n";

    // 4. Preencher formulário de login
    echo "[4/5] Preenchendo formulário de login...\n";

    $driver->findElement(WebDriverBy::id('email'))->sendKeys($testEmail);
    $driver->findElement(WebDriverBy::id('password'))->sendKeys($testPassword);

    echo "✓ Formulário preenchido\n";
    sleep(1);

    // 5. Submeter formulário
    echo "[5/5] Submetendo formulário...\n";

    $submitButton = $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'));
    $submitButton->click();

    // Aguardar redirecionamento para /perfil/candidato
    $driver->wait(10)->until(function($driver) {
        $currentUrl = $driver->getCurrentURL();
        return strpos($currentUrl, '/perfil/candidato') !== false
            || strpos($currentUrl, '/dashboard') !== false;
    });

    $finalUrl = $driver->getCurrentURL();
    echo "✓ Login bem-sucedido! Redirecionado para: {$finalUrl}\n";

    // Verificar se há elementos típicos da página de perfil do candidato
    $driver->wait(5)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::tagName('header')
        )
    );

    echo "✓ Página de perfil carregada corretamente\n";

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";

    if ($driver) {
        try {
            $screenshotPath = __DIR__ . '/_output/screenshots/08_LoginCandidato_' . date('Y-m-d_H-i-s') . '.png';
            $driver->takeScreenshot($screenshotPath);
            echo "Screenshot salvo em: {$screenshotPath}\n";
        } catch (Exception $e) {}
    }

    exit(1);

} finally {
    if ($pdo && $testCandidatoId) {
        try {
            echo "\nLimpando dados de teste...\n";
            $stmt = $pdo->prepare("DELETE FROM candidatos WHERE id_candidato = ?");
            $stmt->execute([$testCandidatoId]);
            echo "✓ Candidato de teste removido\n";
        } catch (Exception $e) {
            echo "✗ Erro ao limpar dados: " . $e->getMessage() . "\n";
        }
    }

    if ($driver) {
        try {
            $driver->quit();
            echo "✓ WebDriver encerrado\n";
        } catch (Exception $e) {}
    }
}
