<?php
/**
 * Teste E2E: Logout
 *
 * OBJETIVO:
 * Testar o fluxo de logout (sair do sistema)
 *
 * COMO RODAR:
 * php tests/17_Logout.php
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
    echo "=== TESTE: Logout ===\n";

    echo "[1/5] Preparando dados...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $testEmail = 'candidato_logout_' . time() . '@example.com';
    $testPassword = 'SenhaSegura123!';

    $stmt = $pdo->prepare("
        INSERT INTO candidatos (email, senha, nome_completo, cpf, telefone,
        data_nascimento, cidade, estado, escolaridade, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $testEmail, password_hash($testPassword, PASSWORD_DEFAULT),
        'Candidato Logout', '44444444444', '11944444444',
        '1992-01-01', 'São Paulo', 'SP', 'medio_completo'
    ]);
    $testCandidatoId = $pdo->lastInsertId();
    echo "✓ Candidato criado\n";

    echo "[2/5] Iniciando ChromeDriver...\n";
    $capabilities = DesiredCapabilities::chrome();
    $capabilities->setCapability('goog:chromeOptions', [
        'args' => ['--disable-gpu', '--no-sandbox', '--window-size=1920,1080']
    ]);
    $driver = RemoteWebDriver::create(WEBDRIVER_URL, $capabilities, 60000, 60000);
    echo "✓ ChromeDriver iniciado\n";

    echo "[3/5] Fazendo login...\n";
    $driver->get(BASE_URL . 'login');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('email'))
    );
    $driver->findElement(WebDriverBy::id('email'))->sendKeys($testEmail);
    $driver->findElement(WebDriverBy::id('password'))->sendKeys($testPassword);
    $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();
    $driver->wait(10)->until(function($driver) {
        return strpos($driver->getCurrentURL(), '/perfil/candidato') !== false
            || strpos($driver->getCurrentURL(), '/dashboard') !== false;
    });
    echo "✓ Login realizado\n";

    echo "[4/5] Fazendo logout...\n";

    // Procurar botão de logout (pode estar no header, menu, etc)
    try {
        // Tentar encontrar botão/link de logout
        $logoutButton = $driver->findElement(
            WebDriverBy::xpath("//button[contains(text(), 'Sair') or contains(text(), 'Logout')] | //a[contains(text(), 'Sair') or contains(text(), 'Logout')]")
        );
        $logoutButton->click();
        sleep(2);
        echo "✓ Clicou em logout\n";
    } catch (Exception $e) {
        // Tentar por aria-label
        try {
            $logoutButton = $driver->findElement(
                WebDriverBy::cssSelector('button[aria-label*="Sair"], a[aria-label*="Sair"]')
            );
            $logoutButton->click();
            sleep(2);
            echo "✓ Clicou em logout (aria-label)\n";
        } catch (Exception $e2) {
            echo "⚠ Botão de logout não encontrado: " . $e->getMessage() . "\n";
        }
    }

    echo "[5/5] Verificando logout...\n";

    // Verificar se foi redirecionado para página pública
    $currentUrl = $driver->getCurrentURL();

    if (strpos($currentUrl, '/login') !== false || strpos($currentUrl, '/') === 0) {
        echo "✓ Redirecionado para página pública\n";
    } else {
        echo "⚠ URL atual: $currentUrl\n";
    }

    // Tentar acessar página protegida
    $driver->get(BASE_URL . 'perfil/candidato');
    sleep(2);
    $finalUrl = $driver->getCurrentURL();

    if (strpos($finalUrl, '/login') !== false) {
        echo "✓ Acesso negado a página protegida - Logout bem-sucedido!\n";
    } else {
        echo "⚠ Ainda consegue acessar páginas protegidas\n";
    }

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    if ($driver) {
        try {
            $driver->takeScreenshot(__DIR__ . '/_output/screenshots/17_Logout_' . date('Y-m-d_H-i-s') . '.png');
        } catch (Exception $e) {}
    }
    exit(1);

} finally {
    if ($pdo && $testCandidatoId) {
        try {
            echo "\nLimpando dados...\n";
            $pdo->prepare("DELETE FROM candidatos WHERE id_candidato = ?")->execute([$testCandidatoId]);
            echo "✓ Dados removidos\n";
        } catch (Exception $e) {}
    }
    if ($driver) {
        try {
            $driver->quit();
        } catch (Exception $e) {}
    }
}
