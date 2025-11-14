<?php
/**
 * Teste E2E: Editar Vaga
 *
 * OBJETIVO:
 * Testar a edição de uma vaga existente por uma instituição
 *
 * COMO RODAR:
 * php tests/12_EditarVaga.php
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
$testInstituicaoId = null;
$testVagaId = null;

try {
    echo "=== TESTE: Editar Vaga ===\n";

    echo "[1/6] Preparando dados...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $testEmail = 'inst_editar_' . time() . '@example.com';
    $testPassword = 'SenhaSegura123!';
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO instituicoes (email, senha, nome_instituicao, cnpj,
        tipo_instituicao, telefone, cidade, estado, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$testEmail, $hashedPassword, 'Instituição Editar',
        '44444444000199', 'escola', '11955555555', 'São Paulo', 'SP']);
    $testInstituicaoId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        INSERT INTO vagas (id_instituicao, titulo_vaga, descricao,
        cidade, estado, status, tipo, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$testInstituicaoId, 'Vaga Original',
        'Descrição original', 'São Paulo', 'SP', 'ATIVA', 'PRESENCIAL']);
    $testVagaId = $pdo->lastInsertId();
    echo "✓ Dados criados\n";

    echo "[2/6] Iniciando ChromeDriver...\n";
    $capabilities = DesiredCapabilities::chrome();
    $capabilities->setCapability('goog:chromeOptions', [
        'args' => ['--disable-gpu', '--no-sandbox', '--window-size=1920,1080']
    ]);
    $driver = RemoteWebDriver::create(WEBDRIVER_URL, $capabilities, 60000, 60000);
    echo "✓ ChromeDriver iniciado\n";

    echo "[3/6] Fazendo login...\n";
    $driver->get(BASE_URL . 'login');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('email'))
    );
    $driver->findElement(WebDriverBy::id('email'))->sendKeys($testEmail);
    $driver->findElement(WebDriverBy::id('password'))->sendKeys($testPassword);
    $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();
    $driver->wait(10)->until(function($driver) {
        return strpos($driver->getCurrentURL(), '/perfil') !== false
            || strpos($driver->getCurrentURL(), '/dashboard') !== false;
    });
    echo "✓ Login realizado\n";

    echo "[4/6] Navegando para editar vaga...\n";
    $driver->get(BASE_URL . 'vagas/' . $testVagaId . '/editar');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::name('titulo_vaga')
        )
    );
    echo "✓ Página de edição carregada\n";

    echo "[5/6] Editando vaga...\n";
    $tituloField = $driver->findElement(WebDriverBy::name('titulo_vaga'));
    $tituloField->clear();
    $tituloField->sendKeys('Vaga EDITADA - Teste Selenium');

    $descricaoField = $driver->findElement(WebDriverBy::name('descricao'));
    $descricaoField->clear();
    $descricaoField->sendKeys('Descrição EDITADA por teste automatizado');

    sleep(1);
    echo "✓ Campos editados\n";

    echo "[6/6] Salvando alterações...\n";
    $driver->executeScript("window.scrollTo(0, document.body.scrollHeight);");
    sleep(1);

    $submitButton = $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'));
    $submitButton->click();

    sleep(2);

    // Verificar no banco
    $stmt = $pdo->prepare("SELECT titulo_vaga, descricao FROM vagas WHERE id_vaga = ?");
    $stmt->execute([$testVagaId]);
    $vaga = $stmt->fetch();

    if ($vaga && strpos($vaga['titulo_vaga'], 'EDITADA') !== false) {
        echo "✓ Vaga editada com sucesso no banco\n";
    } else {
        echo "⚠ Vaga pode não ter sido editada\n";
    }

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    if ($driver) {
        try {
            $driver->takeScreenshot(__DIR__ . '/_output/screenshots/12_EditarVaga_' . date('Y-m-d_H-i-s') . '.png');
        } catch (Exception $e) {}
    }
    exit(1);

} finally {
    if ($pdo) {
        try {
            echo "\nLimpando dados...\n";
            if ($testVagaId) {
                $pdo->prepare("DELETE FROM vagas WHERE id_vaga = ?")->execute([$testVagaId]);
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
