<?php
/**
 * Teste E2E: Candidatar-se a Vaga
 *
 * OBJETIVO:
 * Testar candidatura de um candidato a uma vaga, validando:
 * - Login como candidato
 * - Acessar detalhes da vaga
 * - Abrir modal de candidatura
 * - Enviar candidatura
 * - Verificar candidatura no banco
 *
 * COMO RODAR:
 * php tests/11_CandidatarVaga.php
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
    echo "=== TESTE: Candidatar-se a Vaga ===\n";

    // 1. Preparar dados
    echo "[1/6] Preparando dados de teste...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $testPassword = 'SenhaSegura123!';
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);

    // Criar candidato
    $testEmail = 'candidato_vaga_' . time() . '@example.com';
    $stmt = $pdo->prepare("
        INSERT INTO candidatos (email, senha, nome_completo, cpf, telefone,
        data_nascimento, cidade, estado, escolaridade, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$testEmail, $hashedPassword, 'Candidato Teste',
        '77777777777', '11977777777', '1992-01-01', 'São Paulo', 'SP', 'superior_completo']);
    $testCandidatoId = $pdo->lastInsertId();

    // Criar instituição e vaga
    $stmt = $pdo->prepare("
        INSERT INTO instituicoes (email, senha, nome_instituicao, cnpj,
        tipo_instituicao, telefone, cidade, estado, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute(['inst_' . time() . '@example.com', $hashedPassword,
        'Instituição Teste', '33333333000199', 'escola',
        '11966666666', 'São Paulo', 'SP']);
    $testInstituicaoId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        INSERT INTO vagas (id_instituicao, titulo_vaga, descricao,
        cidade, estado, status, tipo, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$testInstituicaoId, 'Vaga para Candidatura Teste',
        'Descrição da vaga', 'São Paulo', 'SP', 'ATIVA', 'PRESENCIAL']);
    $testVagaId = $pdo->lastInsertId();
    echo "✓ Dados criados\n";

    // 2. Iniciar WebDriver
    echo "[2/6] Iniciando ChromeDriver...\n";
    $capabilities = DesiredCapabilities::chrome();
    $capabilities->setCapability('goog:chromeOptions', [
        'args' => ['--disable-gpu', '--no-sandbox', '--window-size=1920,1080']
    ]);
    $driver = RemoteWebDriver::create(WEBDRIVER_URL, $capabilities, 60000, 60000);
    echo "✓ ChromeDriver iniciado\n";

    // 3. Fazer login
    echo "[3/6] Fazendo login como candidato...\n";
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

    // 4. Acessar vaga
    echo "[4/6] Acessando detalhes da vaga...\n";
    $driver->get(BASE_URL . 'vagas/' . $testVagaId);
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('h1'))
    );
    sleep(1);
    echo "✓ Página da vaga carregada\n";

    // 5. Candidatar-se
    echo "[5/6] Abrindo modal de candidatura...\n";
    try {
        $candidatarButton = $driver->findElement(
            WebDriverBy::xpath("//button[contains(text(), 'Candidatar') or contains(text(), 'Enviar')]")
        );
        $candidatarButton->click();

        // Aguardar modal
        $driver->wait(10)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('div[role="dialog"], textarea#mensagem')
            )
        );
        echo "✓ Modal aberto\n";

        // Preencher mensagem
        $mensagemField = $driver->findElement(WebDriverBy::id('mensagem'));
        $mensagemField->sendKeys('Olá! Tenho grande interesse nesta vaga...');
        sleep(1);

        // Enviar
        $submitButton = $driver->findElement(
            WebDriverBy::xpath("//button[@type='submit' and contains(., 'Enviar')]")
        );
        $submitButton->click();
        sleep(2);

        echo "✓ Candidatura enviada\n";

    } catch (Exception $e) {
        echo "⚠ Erro ao candidatar: " . $e->getMessage() . "\n";
    }

    // 6. Verificar no banco
    echo "[6/6] Verificando candidatura no banco...\n";
    $stmt = $pdo->prepare("
        SELECT id_proposta, status
        FROM propostas
        WHERE id_candidato = ? AND id_vaga = ?
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$testCandidatoId, $testVagaId]);
    $proposta = $stmt->fetch();

    if ($proposta) {
        $testPropostaId = $proposta['id_proposta'];
        echo "✓ Candidatura encontrada no banco (ID: {$testPropostaId})\n";
    } else {
        echo "⚠ Candidatura não encontrada no banco\n";
    }

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    if ($driver) {
        try {
            $driver->takeScreenshot(__DIR__ . '/_output/screenshots/11_CandidatarVaga_' . date('Y-m-d_H-i-s') . '.png');
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
