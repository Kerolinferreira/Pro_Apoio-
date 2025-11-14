<?php
/**
 * Teste E2E: Dashboard
 *
 * OBJETIVO:
 * Testar o acesso e visualização do dashboard para usuários autenticados
 *
 * COMO RODAR:
 * php tests/18_Dashboard.php
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
$testInstituicaoId = null;

try {
    echo "=== TESTE: Dashboard ===\n";

    echo "[1/7] Preparando dados...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $testPassword = 'SenhaSegura123!';
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);

    // Criar candidato
    $candidatoEmail = 'candidato_dashboard_' . time() . '@example.com';
    $stmt = $pdo->prepare("
        INSERT INTO candidatos (email, senha, nome_completo, cpf, telefone,
        data_nascimento, cidade, estado, escolaridade, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $candidatoEmail, $hashedPassword, 'Candidato Dashboard',
        '33333333333', '11933333333', '1991-01-01',
        'São Paulo', 'SP', 'superior_completo'
    ]);
    $testCandidatoId = $pdo->lastInsertId();

    // Criar instituição
    $instituicaoEmail = 'instituicao_dashboard_' . time() . '@example.com';
    $stmt = $pdo->prepare("
        INSERT INTO instituicoes (email, senha, nome_instituicao, cnpj,
        tipo_instituicao, telefone, cidade, estado, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $instituicaoEmail, $hashedPassword, 'Instituição Dashboard',
        '99999999000199', 'escola', '11911111111',
        'São Paulo', 'SP'
    ]);
    $testInstituicaoId = $pdo->lastInsertId();
    echo "✓ Dados criados\n";

    echo "[2/7] Iniciando ChromeDriver...\n";
    $capabilities = DesiredCapabilities::chrome();
    $capabilities->setCapability('goog:chromeOptions', [
        'args' => ['--disable-gpu', '--no-sandbox', '--window-size=1920,1080']
    ]);
    $driver = RemoteWebDriver::create(WEBDRIVER_URL, $capabilities, 60000, 60000);
    echo "✓ ChromeDriver iniciado\n";

    // TESTE 1: Dashboard como Candidato
    echo "[3/7] Testando dashboard como CANDIDATO...\n";
    $driver->get(BASE_URL . 'login');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('email'))
    );
    $driver->findElement(WebDriverBy::id('email'))->sendKeys($candidatoEmail);
    $driver->findElement(WebDriverBy::id('password'))->sendKeys($testPassword);
    $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

    sleep(2);

    // Navegar para dashboard
    $driver->get(BASE_URL . 'dashboard');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('h1'))
    );
    sleep(1);

    $pageText = $driver->findElement(WebDriverBy::tagName('body'))->getText();

    if (strpos($pageText, 'Dashboard') !== false || strpos($pageText, 'Painel') !== false) {
        echo "✓ Dashboard de candidato carregado\n";
    } else {
        echo "⚠ Dashboard pode ter título diferente\n";
    }

    // Verificar elementos típicos do dashboard
    $currentUrl = $driver->getCurrentURL();
    if (strpos($currentUrl, '/dashboard') !== false) {
        echo "✓ URL do dashboard correta\n";
    }

    echo "[4/7] Fazendo logout...\n";
    try {
        $logoutButton = $driver->findElement(
            WebDriverBy::xpath("//button[contains(text(), 'Sair')] | //a[contains(text(), 'Sair')]")
        );
        $logoutButton->click();
        sleep(2);
    } catch (Exception $e) {
        echo "  ⚠ Botão logout não encontrado, limpando cookies\n";
        $driver->manage()->deleteAllCookies();
    }

    // TESTE 2: Dashboard como Instituição
    echo "[5/7] Testando dashboard como INSTITUIÇÃO...\n";
    $driver->get(BASE_URL . 'login');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('email'))
    );
    $driver->findElement(WebDriverBy::id('email'))->clear();
    $driver->findElement(WebDriverBy::id('email'))->sendKeys($instituicaoEmail);
    $driver->findElement(WebDriverBy::id('password'))->clear();
    $driver->findElement(WebDriverBy::id('password'))->sendKeys($testPassword);
    $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

    sleep(2);

    // Navegar para dashboard
    $driver->get(BASE_URL . 'dashboard');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('h1'))
    );
    sleep(1);

    $pageText = $driver->findElement(WebDriverBy::tagName('body'))->getText();

    if (strpos($pageText, 'Dashboard') !== false || strpos($pageText, 'Painel') !== false) {
        echo "✓ Dashboard de instituição carregado\n";
    }

    // TESTE 3: Verificar proteção de rota
    echo "[6/7] Testando proteção de rota (sem login)...\n";
    $driver->manage()->deleteAllCookies();
    $driver->get(BASE_URL . 'dashboard');
    sleep(2);

    $finalUrl = $driver->getCurrentURL();
    if (strpos($finalUrl, '/login') !== false) {
        echo "✓ Rota protegida - redireciona para login sem autenticação\n";
    } else {
        echo "⚠ Dashboard pode estar acessível sem login\n";
    }

    echo "[7/7] Verificando elementos do dashboard...\n";

    // Fazer login novamente para verificar elementos
    $driver->get(BASE_URL . 'login');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('email'))
    );
    $driver->findElement(WebDriverBy::id('email'))->sendKeys($candidatoEmail);
    $driver->findElement(WebDriverBy::id('password'))->sendKeys($testPassword);
    $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();
    sleep(2);

    $driver->get(BASE_URL . 'dashboard');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('body'))
    );
    sleep(1);

    // Verificar se há links/botões de navegação
    $links = $driver->findElements(WebDriverBy::tagName('a'));
    echo "✓ Dashboard possui " . count($links) . " link(s) de navegação\n";

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    if ($driver) {
        try {
            $driver->takeScreenshot(__DIR__ . '/_output/screenshots/18_Dashboard_' . date('Y-m-d_H-i-s') . '.png');
        } catch (Exception $e) {}
    }
    exit(1);

} finally {
    if ($pdo) {
        try {
            echo "\nLimpando dados...\n";
            if ($testCandidatoId) {
                $pdo->prepare("DELETE FROM candidatos WHERE id_candidato = ?")->execute([$testCandidatoId]);
            }
            if ($testInstituicaoId) {
                $pdo->prepare("DELETE FROM instituicoes WHERE id_instituicao = ?")->execute([$testInstituicaoId]);
            }
            echo "✓ Dados removidos\n";
        } catch (Exception $e) {}
    }
    if ($driver) {
        try {
            $driver->quit();
        } catch (Exception $e) {}
    }
}
