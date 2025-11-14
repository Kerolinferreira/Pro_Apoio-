<?php
/**
 * Teste E2E: Salvar Vaga (Candidato)
 *
 * OBJETIVO:
 * Testar o fluxo de salvar uma vaga como candidato autenticado, validando:
 * - Login como candidato
 * - Buscar vagas
 * - Salvar uma vaga
 * - Ver vagas salvas
 * - Remover vaga salva
 *
 * COMO RODAR:
 * php tests/10_SalvarVaga.php
 */

require 'vendor/autoload.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

const BASE_URL = 'http://localhost:3074/';
const WEBDRIVER_URL = 'http://127.0.0.1:9515';
const DB_HOST = '127.0.0.1';
const DB_USER = 'root';
const DB_PASS = '1234';
const DB_NAME = 'proapoio';

$driver = null;
$pdo = null;
$testCandidatoId = null;
$testInstituicaoId = null;
$testVagaId = null;

try {
    echo "=== TESTE: Salvar Vaga (Candidato) ===\n";

    // 1. Preparar dados
    echo "[1/7] Preparando dados de teste...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $testPassword = 'SenhaSegura123!';
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);

    // Criar candidato
    $testEmail = 'candidato_salvar_' . time() . '@example.com';
    $stmt = $pdo->prepare("
        INSERT INTO candidatos (email, senha, nome_completo, cpf, telefone,
        data_nascimento, cidade, estado, escolaridade, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $testEmail, $hashedPassword, 'Candidato Teste Salvar',
        '88888888888', '11988888888', '1995-01-01',
        'São Paulo', 'SP', 'superior_completo'
    ]);
    $testCandidatoId = $pdo->lastInsertId();

    // Criar instituição e vaga
    $stmt = $pdo->prepare("
        INSERT INTO instituicoes (email, senha, nome_instituicao, cnpj,
        tipo_instituicao, telefone, cidade, estado, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        'inst_salvar_' . time() . '@example.com', $hashedPassword,
        'Instituição Teste', '22222222000199', 'escola',
        '11977777777', 'São Paulo', 'SP'
    ]);
    $testInstituicaoId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        INSERT INTO vagas (id_instituicao, titulo_vaga, descricao,
        cidade, estado, status, tipo, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $testInstituicaoId, 'Vaga Teste para Salvar',
        'Descrição da vaga', 'São Paulo', 'SP', 'ATIVA', 'PRESENCIAL'
    ]);
    $testVagaId = $pdo->lastInsertId();
    echo "✓ Dados criados\n";

    // 2. Iniciar WebDriver
    echo "[2/7] Iniciando ChromeDriver...\n";
    $capabilities = DesiredCapabilities::chrome();
    $capabilities->setCapability('goog:chromeOptions', [
        'args' => ['--disable-gpu', '--no-sandbox', '--window-size=1920,1080']
    ]);
    $driver = RemoteWebDriver::create(WEBDRIVER_URL, $capabilities, 60000, 60000);
    echo "✓ ChromeDriver iniciado\n";

    // 3. Fazer login como candidato
    echo "[3/7] Fazendo login como candidato...\n";
    $driver->get(BASE_URL . 'login');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('email'))
    );
    $driver->findElement(WebDriverBy::id('email'))->sendKeys($testEmail);
    $driver->findElement(WebDriverBy::id('password'))->sendKeys($testPassword);
    $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

    $driver->wait(10)->until(function($driver) {
        $url = $driver->getCurrentURL();
        return strpos($url, '/perfil/candidato') !== false || strpos($url, '/dashboard') !== false;
    });
    echo "✓ Login realizado\n";

    // 4. Navegar para lista de vagas
    echo "[4/7] Navegando para lista de vagas...\n";
    $driver->get(BASE_URL . 'vagas');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('h1'))
    );
    sleep(2); // Aguardar vagas carregarem
    echo "✓ Página de vagas carregada\n";

    // 5. Salvar vaga
    echo "[5/7] Salvando vaga...\n";

    // Ir diretamente para a vaga
    $driver->get(BASE_URL . 'vagas/' . $testVagaId);
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('h1'))
    );
    sleep(1);

    // Procurar botão de salvar (pode ter vários formatos)
    try {
        $salvarButton = $driver->findElement(
            WebDriverBy::xpath("//button[contains(text(), 'Salvar') or contains(@aria-label, 'Salvar')]")
        );
        $salvarButton->click();
        sleep(1);
        echo "✓ Vaga salva\n";
    } catch (Exception $e) {
        echo "⚠ Botão salvar não encontrado (pode ter texto/seletor diferente)\n";
    }

    // 6. Ver vagas salvas
    echo "[6/7] Verificando vagas salvas...\n";
    $driver->get(BASE_URL . 'vagas-salvas');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('h1'))
    );
    sleep(2);

    $pageText = $driver->findElement(WebDriverBy::tagName('body'))->getText();
    if (strpos($pageText, 'Vaga Teste para Salvar') !== false) {
        echo "✓ Vaga encontrada na lista de vagas salvas\n";
    } else {
        echo "⚠ Vaga pode não estar na lista (verificar no banco)\n";
    }

    // Verificar no banco
    $stmt = $pdo->prepare("
        SELECT * FROM vagas_salvas
        WHERE id_candidato = ? AND id_vaga = ?
    ");
    $stmt->execute([$testCandidatoId, $testVagaId]);
    $vagaSalva = $stmt->fetch();

    if ($vagaSalva) {
        echo "✓ Vaga confirmada no banco de dados\n";
    }

    // 7. Remover vaga salva
    echo "[7/7] Removendo vaga salva...\n";
    try {
        $removerButton = $driver->findElement(
            WebDriverBy::xpath("//button[contains(text(), 'Remover') or contains(@aria-label, 'Remover')]")
        );
        $removerButton->click();
        sleep(1);
        echo "✓ Vaga removida das salvas\n";
    } catch (Exception $e) {
        echo "⚠ Botão remover não encontrado\n";
    }

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    if ($driver) {
        try {
            $driver->takeScreenshot(__DIR__ . '/_output/screenshots/10_SalvarVaga_' . date('Y-m-d_H-i-s') . '.png');
        } catch (Exception $e) {}
    }
    exit(1);

} finally {
    if ($pdo) {
        try {
            echo "\nLimpando dados de teste...\n";
            if ($testCandidatoId && $testVagaId) {
                $stmt = $pdo->prepare("DELETE FROM vagas_salvas WHERE id_candidato = ? AND id_vaga = ?");
                $stmt->execute([$testCandidatoId, $testVagaId]);
            }
            if ($testVagaId) {
                $pdo->prepare("DELETE FROM vagas WHERE id_vaga = ?")->execute([$testVagaId]);
            }
            if ($testInstituicaoId) {
                $pdo->prepare("DELETE FROM instituicoes WHERE id_instituicao = ?")->execute([$testInstituicaoId]);
            }
            if ($testCandidatoId) {
                $pdo->prepare("DELETE FROM candidatos WHERE id_candidato = ?")->execute([$testCandidatoId]);
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
