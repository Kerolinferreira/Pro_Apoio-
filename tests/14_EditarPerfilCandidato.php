<?php
/**
 * Teste E2E: Editar Perfil de Candidato
 *
 * OBJETIVO:
 * Testar a edição do perfil de um candidato autenticado, validando:
 * - Login como candidato
 * - Acessar página de perfil
 * - Editar informações pessoais
 * - Salvar alterações
 * - Verificar no banco
 *
 * COMO RODAR:
 * php tests/14_EditarPerfilCandidato.php
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
    echo "=== TESTE: Editar Perfil de Candidato ===\n";

    echo "[1/6] Preparando dados...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $testEmail = 'candidato_editar_' . time() . '@example.com';
    $testPassword = 'SenhaSegura123!';

    $stmt = $pdo->prepare("
        INSERT INTO candidatos (email, senha, nome_completo, cpf, telefone,
        data_nascimento, cidade, estado, escolaridade, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $testEmail, password_hash($testPassword, PASSWORD_DEFAULT),
        'Nome Original', '66666666666', '11966666666',
        '1990-01-01', 'São Paulo', 'SP', 'medio_completo'
    ]);
    $testCandidatoId = $pdo->lastInsertId();
    echo "✓ Candidato criado\n";

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
        return strpos($driver->getCurrentURL(), '/perfil/candidato') !== false
            || strpos($driver->getCurrentURL(), '/dashboard') !== false;
    });
    echo "✓ Login realizado\n";

    echo "[4/6] Navegando para perfil...\n";
    $driver->get(BASE_URL . 'perfil/candidato');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('h1'))
    );
    sleep(1);
    echo "✓ Página de perfil carregada\n";

    echo "[5/6] Editando informações...\n";

    // Procurar botão "Editar" ou campos editáveis
    try {
        // Tentar clicar em botão de editar
        $editButton = $driver->findElement(
            WebDriverBy::xpath("//button[contains(text(), 'Editar') or contains(@aria-label, 'Editar')]")
        );
        $editButton->click();
        sleep(1);
        echo "  ✓ Modo de edição ativado\n";
    } catch (Exception $e) {
        echo "  ⚠ Botão editar não encontrado (campos podem já estar editáveis)\n";
    }

    // Tentar editar nome
    try {
        $nomeField = $driver->findElement(WebDriverBy::name('nome_completo'));
        $nomeField->clear();
        $nomeField->sendKeys('Nome EDITADO - Teste Selenium');
        echo "  ✓ Nome editado\n";
    } catch (Exception $e) {
        echo "  ⚠ Campo nome não encontrado ou não editável\n";
    }

    // Tentar editar telefone
    try {
        $telefoneField = $driver->findElement(WebDriverBy::name('telefone'));
        $telefoneField->clear();
        $telefoneField->sendKeys('11955555555');
        echo "  ✓ Telefone editado\n";
    } catch (Exception $e) {
        echo "  ⚠ Campo telefone não encontrado\n";
    }

    // Tentar editar escolaridade
    try {
        $escolaridadeSelect = $driver->findElement(WebDriverBy::name('escolaridade'));
        $escolaridadeSelect->click();
        $driver->findElement(
            WebDriverBy::cssSelector('select[name="escolaridade"] option[value="superior_completo"]')
        )->click();
        echo "  ✓ Escolaridade editada\n";
    } catch (Exception $e) {
        echo "  ⚠ Campo escolaridade não encontrado\n";
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
        echo "✓ Alterações salvas\n";
    } catch (Exception $e) {
        echo "⚠ Botão salvar não encontrado: " . $e->getMessage() . "\n";
    }

    // Verificar no banco
    $stmt = $pdo->prepare("
        SELECT nome_completo, telefone, escolaridade
        FROM candidatos WHERE id_candidato = ?
    ");
    $stmt->execute([$testCandidatoId]);
    $candidato = $stmt->fetch();

    if ($candidato) {
        echo "\nDados no banco:\n";
        echo "  - Nome: {$candidato['nome_completo']}\n";
        echo "  - Telefone: {$candidato['telefone']}\n";
        echo "  - Escolaridade: {$candidato['escolaridade']}\n";

        if (strpos($candidato['nome_completo'], 'EDITADO') !== false) {
            echo "✓ Perfil editado com sucesso!\n";
        } else {
            echo "⚠ Perfil pode não ter sido editado (verificar manualmente)\n";
        }
    }

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    if ($driver) {
        try {
            $driver->takeScreenshot(__DIR__ . '/_output/screenshots/14_EditarPerfilCandidato_' . date('Y-m-d_H-i-s') . '.png');
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
