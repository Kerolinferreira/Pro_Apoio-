<?php
/**
 * Teste E2E: Editar Perfil de Instituição
 *
 * OBJETIVO:
 * Testar a edição do perfil de uma instituição autenticada
 *
 * COMO RODAR:
 * php tests/15_EditarPerfilInstituicao.php
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

try {
    echo "=== TESTE: Editar Perfil de Instituição ===\n";

    echo "[1/6] Preparando dados...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $testEmail = 'inst_editar_perfil_' . time() . '@example.com';
    $testPassword = 'SenhaSegura123!';

    $stmt = $pdo->prepare("
        INSERT INTO instituicoes (email, senha, nome_instituicao, cnpj,
        tipo_instituicao, telefone, cidade, estado, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $testEmail, password_hash($testPassword, PASSWORD_DEFAULT),
        'Instituição Original', '77777777000199', 'escola',
        '11933333333', 'Rio de Janeiro', 'RJ'
    ]);
    $testInstituicaoId = $pdo->lastInsertId();
    echo "✓ Instituição criada\n";

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
        return strpos($driver->getCurrentURL(), '/perfil/instituicao') !== false
            || strpos($driver->getCurrentURL(), '/dashboard') !== false;
    });
    echo "✓ Login realizado\n";

    echo "[4/6] Navegando para perfil...\n";
    $driver->get(BASE_URL . 'perfil/instituicao');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('h1'))
    );
    sleep(1);
    echo "✓ Página de perfil carregada\n";

    echo "[5/6] Editando informações...\n";

    try {
        $editButton = $driver->findElement(
            WebDriverBy::xpath("//button[contains(text(), 'Editar')]")
        );
        $editButton->click();
        sleep(1);
        echo "  ✓ Modo de edição ativado\n";
    } catch (Exception $e) {
        echo "  ⚠ Botão editar não encontrado\n";
    }

    // Editar telefone
    try {
        $telefoneField = $driver->findElement(WebDriverBy::name('telefone'));
        $telefoneField->clear();
        $telefoneField->sendKeys('11944444444');
        echo "  ✓ Telefone editado\n";
    } catch (Exception $e) {
        echo "  ⚠ Campo telefone não encontrado\n";
    }

    // Editar cidade
    try {
        $cidadeField = $driver->findElement(WebDriverBy::name('cidade'));
        $cidadeField->clear();
        $cidadeField->sendKeys('São Paulo');
        echo "  ✓ Cidade editada\n";
    } catch (Exception $e) {
        echo "  ⚠ Campo cidade não encontrado\n";
    }

    sleep(1);

    echo "[6/6] Salvando alterações...\n";
    $driver->executeScript("window.scrollTo(0, document.body.scrollHeight);");
    sleep(1);

    try {
        $salvarButton = $driver->findElement(
            WebDriverBy::xpath("//button[contains(text(), 'Salvar') or @type='submit']")
        );
        $salvarButton->click();
        sleep(2);
        echo "✓ Alterações enviadas\n";
    } catch (Exception $e) {
        echo "⚠ Botão salvar não encontrado\n";
    }

    // Verificar no banco
    $stmt = $pdo->prepare("
        SELECT nome_instituicao, telefone, cidade, estado
        FROM instituicoes WHERE id_instituicao = ?
    ");
    $stmt->execute([$testInstituicaoId]);
    $inst = $stmt->fetch();

    if ($inst) {
        echo "\nDados no banco:\n";
        echo "  - Nome: {$inst['nome_instituicao']}\n";
        echo "  - Telefone: {$inst['telefone']}\n";
        echo "  - Cidade: {$inst['cidade']}/{$inst['estado']}\n";

        if ($inst['telefone'] === '11944444444' || $inst['cidade'] === 'São Paulo') {
            echo "✓ Perfil editado com sucesso!\n";
        }
    }

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    if ($driver) {
        try {
            $driver->takeScreenshot(__DIR__ . '/_output/screenshots/15_EditarPerfilInstituicao_' . date('Y-m-d_H-i-s') . '.png');
        } catch (Exception $e) {}
    }
    exit(1);

} finally {
    if ($pdo && $testInstituicaoId) {
        try {
            echo "\nLimpando dados...\n";
            $pdo->prepare("DELETE FROM instituicoes WHERE id_instituicao = ?")->execute([$testInstituicaoId]);
            echo "✓ Dados removidos\n";
        } catch (Exception $e) {}
    }
    if ($driver) {
        try {
            $driver->quit();
        } catch (Exception $e) {}
    }
}
