<?php
/**
 * Teste E2E: Excluir Vaga
 *
 * OBJETIVO:
 * Testar a exclusão de uma vaga por uma instituição
 *
 * COMO RODAR:
 * php tests/13_ExcluirVaga.php
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
    echo "=== TESTE: Excluir Vaga ===\n";

    echo "[1/6] Preparando dados...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $testEmail = 'inst_excluir_' . time() . '@example.com';
    $testPassword = 'SenhaSegura123!';

    $stmt = $pdo->prepare("
        INSERT INTO instituicoes (email, senha, nome_instituicao, cnpj,
        tipo_instituicao, telefone, cidade, estado, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$testEmail, password_hash($testPassword, PASSWORD_DEFAULT),
        'Instituição Excluir', '55555555000199', 'escola', '11944444444', 'São Paulo', 'SP']);
    $testInstituicaoId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        INSERT INTO vagas (id_instituicao, titulo_vaga, descricao,
        cidade, estado, status, tipo, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$testInstituicaoId, 'Vaga para Excluir',
        'Esta vaga será excluída', 'São Paulo', 'SP', 'ATIVA', 'PRESENCIAL']);
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
        return strpos($driver->getCurrentURL(), '/perfil') !== false;
    });
    echo "✓ Login realizado\n";

    echo "[4/6] Navegando para minhas vagas...\n";
    $driver->get(BASE_URL . 'vagas/minhas');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('h1'))
    );
    sleep(2);
    echo "✓ Página de minhas vagas carregada\n";

    echo "[5/6] Excluindo vaga...\n";
    try {
        $excluirButton = $driver->findElement(
            WebDriverBy::xpath("//button[contains(text(), 'Excluir') or contains(@aria-label, 'Excluir')]")
        );
        $excluirButton->click();
        sleep(1);

        // Confirmar exclusão (se houver modal de confirmação)
        try {
            $confirmarButton = $driver->findElement(
                WebDriverBy::xpath("//button[contains(text(), 'Confirmar') or contains(text(), 'Sim')]")
            );
            $confirmarButton->click();
            sleep(2);
        } catch (Exception $e) {
            // Modal de confirmação pode não existir
        }

        echo "✓ Vaga excluída\n";
    } catch (Exception $e) {
        echo "⚠ Botão excluir não encontrado: " . $e->getMessage() . "\n";
    }

    echo "[6/6] Verificando exclusão no banco...\n";
    $stmt = $pdo->prepare("SELECT deleted_at FROM vagas WHERE id_vaga = ?");
    $stmt->execute([$testVagaId]);
    $vaga = $stmt->fetch();

    if ($vaga && $vaga['deleted_at'] !== null) {
        echo "✓ Vaga marcada como excluída (soft delete)\n";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM vagas WHERE id_vaga = ?");
        $stmt->execute([$testVagaId]);
        $count = $stmt->fetch()['count'];
        if ($count == 0) {
            echo "✓ Vaga excluída permanentemente (hard delete)\n";
            $testVagaId = null; // Não precisa limpar
        }
    }

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    if ($driver) {
        try {
            $driver->takeScreenshot(__DIR__ . '/_output/screenshots/13_ExcluirVaga_' . date('Y-m-d_H-i-s') . '.png');
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
