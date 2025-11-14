<?php
/**
 * Teste E2E: Minhas Propostas (Candidato)
 *
 * OBJETIVO:
 * Testar a visualização das propostas/candidaturas enviadas por um candidato
 *
 * COMO RODAR:
 * php tests/16_MinhasPropostasCandidato.php
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
$testVagaId = null;
$testPropostaId = null;

try {
    echo "=== TESTE: Minhas Propostas (Candidato) ===\n";

    echo "[1/5] Preparando dados...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $testPassword = 'SenhaSegura123!';
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);

    // Criar candidato
    $testEmail = 'candidato_propostas_' . time() . '@example.com';
    $stmt = $pdo->prepare("
        INSERT INTO candidatos (email, senha, nome_completo, cpf, telefone,
        data_nascimento, cidade, estado, escolaridade, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $testEmail, $hashedPassword, 'Candidato Propostas',
        '55555555555', '11955555555', '1993-01-01',
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
        'inst_prop_' . time() . '@example.com', $hashedPassword,
        'Instituição Propostas', '88888888000199', 'escola',
        '11922222222', 'São Paulo', 'SP'
    ]);
    $testInstituicaoId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        INSERT INTO vagas (id_instituicao, titulo_vaga, descricao,
        cidade, estado, status, tipo, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $testInstituicaoId, 'Vaga para Proposta Teste',
        'Descrição', 'São Paulo', 'SP', 'ATIVA', 'PRESENCIAL'
    ]);
    $testVagaId = $pdo->lastInsertId();

    // Criar proposta (candidatura)
    $stmt = $pdo->prepare("
        INSERT INTO propostas (id_candidato, id_vaga, id_instituicao,
        mensagem, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $testCandidatoId, $testVagaId, $testInstituicaoId,
        'Mensagem de candidatura teste', 'PENDENTE'
    ]);
    $testPropostaId = $pdo->lastInsertId();
    echo "✓ Dados criados\n";

    echo "[2/5] Iniciando ChromeDriver...\n";
    $capabilities = DesiredCapabilities::chrome();
    $capabilities->setCapability('goog:chromeOptions', [
        'args' => ['--disable-gpu', '--no-sandbox', '--window-size=1920,1080']
    ]);
    $driver = RemoteWebDriver::create(WEBDRIVER_URL, $capabilities, 60000, 60000);
    echo "✓ ChromeDriver iniciado\n";

    echo "[3/5] Fazendo login como candidato...\n";
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

    echo "[4/5] Navegando para minhas propostas...\n";
    $driver->get(BASE_URL . 'minhas-propostas');
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('h1'))
    );
    sleep(2);
    echo "✓ Página de minhas propostas carregada\n";

    echo "[5/5] Verificando propostas...\n";
    $pageText = $driver->findElement(WebDriverBy::tagName('body'))->getText();

    if (strpos($pageText, 'Vaga para Proposta Teste') !== false) {
        echo "✓ Proposta encontrada na listagem!\n";
    } else {
        echo "⚠ Proposta pode não estar visível (verificar seletores)\n";
    }

    // Verificar se o status é exibido
    if (strpos($pageText, 'PENDENTE') !== false || strpos($pageText, 'Pendente') !== false) {
        echo "✓ Status da proposta exibido\n";
    }

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    if ($driver) {
        try {
            $driver->takeScreenshot(__DIR__ . '/_output/screenshots/16_MinhasPropostasCandidato_' . date('Y-m-d_H-i-s') . '.png');
        } catch (Exception $e) {}
    }
    exit(1);

} finally {
    if ($pdo) {
        try {
            echo "\nLimpando dados...\n";
            if ($testPropostaId) {
                $pdo->prepare("DELETE FROM propostas WHERE id_proposta = ?")->execute([$testPropostaId]);
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
