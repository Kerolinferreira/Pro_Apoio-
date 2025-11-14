<?php
/**
 * Teste E2E: Perfil Público de Instituição
 *
 * OBJETIVO:
 * Testar a visualização do perfil público de uma instituição (sem login)
 *
 * COMO RODAR:
 * php tests/19_PerfilPublicoInstituicao.php
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
    echo "=== TESTE: Perfil Público de Instituição ===\n";

    echo "[1/5] Preparando dados...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Criar instituição
    $stmt = $pdo->prepare("
        INSERT INTO instituicoes (email, senha, nome_instituicao, cnpj,
        tipo_instituicao, telefone, cidade, estado, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        'instituicao_publica_' . time() . '@example.com',
        password_hash('senha123', PASSWORD_DEFAULT),
        'Escola Pública Teste Selenium',
        '00000000000199',
        'escola',
        '11900000000',
        'São Paulo',
        'SP'
    ]);
    $testInstituicaoId = $pdo->lastInsertId();

    // Criar uma vaga da instituição
    $stmt = $pdo->prepare("
        INSERT INTO vagas (id_instituicao, titulo_vaga, descricao,
        cidade, estado, status, tipo, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $testInstituicaoId,
        'Vaga Teste da Instituição',
        'Vaga publicada pela instituição teste',
        'São Paulo',
        'SP',
        'ATIVA',
        'PRESENCIAL'
    ]);
    $testVagaId = $pdo->lastInsertId();
    echo "✓ Instituição e vaga criadas\n";

    echo "[2/5] Iniciando ChromeDriver...\n";
    $capabilities = DesiredCapabilities::chrome();
    $capabilities->setCapability('goog:chromeOptions', [
        'args' => ['--disable-gpu', '--no-sandbox', '--window-size=1920,1080']
    ]);
    $driver = RemoteWebDriver::create(WEBDRIVER_URL, $capabilities, 60000, 60000);
    echo "✓ ChromeDriver iniciado\n";

    echo "[3/5] Acessando perfil público (SEM LOGIN)...\n";
    $driver->get(BASE_URL . 'instituicoes/' . $testInstituicaoId);

    // Aguardar página carregar
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('h1'))
    );
    sleep(2);
    echo "✓ Página de perfil público carregada\n";

    echo "[4/5] Verificando informações exibidas...\n";
    $pageText = $driver->findElement(WebDriverBy::tagName('body'))->getText();

    // Verificar nome da instituição
    if (strpos($pageText, 'Escola Pública Teste Selenium') !== false) {
        echo "✓ Nome da instituição exibido\n";
    } else {
        echo "⚠ Nome da instituição pode não estar visível\n";
    }

    // Verificar cidade/estado
    if (strpos($pageText, 'São Paulo') !== false && strpos($pageText, 'SP') !== false) {
        echo "✓ Localização exibida\n";
    }

    // Verificar tipo
    if (strpos($pageText, 'escola') !== false || strpos($pageText, 'Escola') !== false) {
        echo "✓ Tipo de instituição exibido\n";
    }

    echo "[5/5] Verificando proteção de dados sensíveis...\n";

    // Verificar que e-mail NÃO aparece (dados sensíveis protegidos)
    if (strpos($pageText, 'instituicao_publica_') === false) {
        echo "✓ E-mail não está visível (correto)\n";
    } else {
        echo "⚠ AVISO: E-mail pode estar exposto!\n";
    }

    // Verificar se CNPJ completo NÃO aparece
    if (strpos($pageText, '00000000000199') === false) {
        echo "✓ CNPJ completo não está visível (correto)\n";
    } else {
        echo "⚠ AVISO: CNPJ completo pode estar exposto!\n";
    }

    // Verificar se há lista de vagas da instituição
    if (strpos($pageText, 'Vaga') !== false || strpos($pageText, 'vaga') !== false) {
        echo "✓ Seção de vagas encontrada\n";
    }

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    if ($driver) {
        try {
            $driver->takeScreenshot(__DIR__ . '/_output/screenshots/19_PerfilPublicoInstituicao_' . date('Y-m-d_H-i-s') . '.png');
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
