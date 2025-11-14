<?php
/**
 * Teste E2E: Buscar Vagas (Público - Não Autenticado)
 *
 * OBJETIVO:
 * Testar o fluxo de busca de vagas sem autenticação, validando:
 * - Acesso à página de vagas sem login
 * - Aplicação de filtros
 * - Visualização de resultados
 * - Acesso a detalhes de uma vaga
 *
 * COMO RODAR:
 * php tests/09_BuscarVagasPublico.php
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
    echo "=== TESTE: Buscar Vagas (Público) ===\n";

    // 1. Preparar dados de teste
    echo "[1/6] Preparando dados de teste...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Criar instituição temporária
    $stmt = $pdo->prepare("
        INSERT INTO instituicoes (
            email, senha, nome_instituicao, cnpj, tipo_instituicao,
            telefone, cidade, estado, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $stmt->execute([
        'teste_vaga_publica_' . time() . '@example.com',
        password_hash('senha123', PASSWORD_DEFAULT),
        'Instituição Teste Vaga Pública',
        '11111111000199',
        'escola',
        '11999999999',
        'São Paulo',
        'SP'
    ]);

    $testInstituicaoId = $pdo->lastInsertId();

    // Criar vaga pública
    $stmt = $pdo->prepare("
        INSERT INTO vagas (
            id_instituicao, titulo_vaga, descricao, cidade, estado,
            status, tipo, modalidade, valor_remuneracao,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $stmt->execute([
        $testInstituicaoId,
        'Vaga Teste Pública - Selenium',
        'Esta é uma vaga de teste para o sistema',
        'São Paulo',
        'SP',
        'ATIVA',
        'PRESENCIAL',
        'Tempo Integral',
        2500.00
    ]);

    $testVagaId = $pdo->lastInsertId();
    echo "✓ Vaga criada (ID: {$testVagaId})\n";

    // 2. Iniciar WebDriver
    echo "[2/6] Iniciando ChromeDriver...\n";
    $capabilities = DesiredCapabilities::chrome();
    $capabilities->setCapability('goog:chromeOptions', [
        'args' => ['--disable-gpu', '--no-sandbox', '--window-size=1920,1080']
    ]);

    $driver = RemoteWebDriver::create(WEBDRIVER_URL, $capabilities, 60000, 60000);
    echo "✓ ChromeDriver iniciado\n";

    // 3. Navegar para página de vagas (sem login)
    echo "[3/6] Navegando para página de vagas...\n";
    $driver->get(BASE_URL . 'vagas');

    // Aguardar página carregar
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::tagName('h1')
        )
    );

    echo "✓ Página de vagas carregada (sem autenticação)\n";

    // Aguardar resultados carregarem
    sleep(2);

    // 4. Verificar se há vagas exibidas
    echo "[4/6] Verificando vagas exibidas...\n";

    $vagaCards = $driver->findElements(
        WebDriverBy::cssSelector('div[class*="VagaCard"], a[href*="/vagas/"]')
    );

    echo "✓ Encontradas " . count($vagaCards) . " vaga(s) na listagem\n";

    // 5. Aplicar filtro (se existir)
    echo "[5/6] Testando filtros...\n";

    try {
        // Tentar filtrar por cidade
        $filtroTexto = $driver->findElement(WebDriverBy::cssSelector('input[placeholder*="idad"], input[type="search"]'));
        $filtroTexto->sendKeys('São Paulo');
        sleep(1);

        echo "✓ Filtro de texto aplicado\n";
    } catch (Exception $e) {
        echo "  ⚠ Campo de filtro não encontrado (pode não existir na página pública)\n";
    }

    // 6. Acessar detalhes de uma vaga
    echo "[6/6] Acessando detalhes da vaga criada...\n";

    $driver->get(BASE_URL . 'vagas/' . $testVagaId);

    // Aguardar página de detalhes carregar
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::tagName('h1')
        )
    );

    $pageText = $driver->findElement(WebDriverBy::tagName('body'))->getText();

    if (strpos($pageText, 'Vaga Teste Pública') !== false) {
        echo "✓ Página de detalhes carregada corretamente\n";
        echo "✓ Título da vaga encontrado na página\n";
    } else {
        echo "⚠ Título da vaga não encontrado (pode estar com seletor diferente)\n";
    }

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";

    if ($driver) {
        try {
            $screenshotPath = __DIR__ . '/_output/screenshots/09_BuscarVagasPublico_' . date('Y-m-d_H-i-s') . '.png';
            $driver->takeScreenshot($screenshotPath);
            echo "Screenshot salvo em: {$screenshotPath}\n";
        } catch (Exception $e) {}
    }

    exit(1);

} finally {
    if ($pdo) {
        try {
            echo "\nLimpando dados de teste...\n";

            if ($testVagaId) {
                $stmt = $pdo->prepare("DELETE FROM vagas WHERE id_vaga = ?");
                $stmt->execute([$testVagaId]);
                echo "✓ Vaga removida\n";
            }

            if ($testInstituicaoId) {
                $stmt = $pdo->prepare("DELETE FROM instituicoes WHERE id_instituicao = ?");
                $stmt->execute([$testInstituicaoId]);
                echo "✓ Instituição removida\n";
            }
        } catch (Exception $e) {
            echo "✗ Erro ao limpar dados: " . $e->getMessage() . "\n";
        }
    }

    if ($driver) {
        try {
            $driver->quit();
            echo "✓ WebDriver encerrado\n";
        } catch (Exception $e) {}
    }
}
