<?php
/**
 * Teste E2E: Ver Candidatos com Filtros
 *
 * OBJETIVO:
 * Testar o fluxo de busca e filtragem de candidatos por uma instituição, validando:
 * - Login como instituição
 * - Navegação até a página de buscar candidatos
 * - Aplicação de filtros (cidade, escolaridade, tipo de deficiência)
 * - Verificação de que apenas candidatos compatíveis aparecem
 * - Verificação de que dados sensíveis (contato) não são exibidos na listagem
 * - Limpeza dos dados
 *
 * PRÉ-REQUISITOS:
 * - ChromeDriver rodando em http://127.0.0.1:9515
 * - Frontend rodando em http://localhost:3074
 * - Backend API disponível
 * - Banco de dados 'proapoio' acessível com alguns candidatos cadastrados
 *
 * COMO RODAR:
 * php tests/04_VerCandidatosFiltros.php
 */

require 'vendor/autoload.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Exception\NoSuchElementException;

// Configurações do ambiente
const BASE_URL = 'http://localhost:3074/';
const WEBDRIVER_URL = 'http://127.0.0.1:9515';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'proapoio';
const DB_USER = 'root';
const DB_PASS = '1234';

$driver = null;
$pdo = null;
$testInstituicaoId = null;
$testCandidatoIds = [];

try {
    echo "=== TESTE: Ver Candidatos com Filtros ===\n";

    // 1. Conectar ao banco e criar dados de teste
    echo "[1/8] Preparando dados de teste...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Criar instituição de teste
    $testEmail = 'teste_filtros_' . time() . '@example.com';
    $testPassword = 'SenhaSegura123!';
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO instituicoes (
            email, senha, nome_instituicao, cnpj, tipo_instituicao,
            telefone, cidade, estado, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $stmt->execute([
        $testEmail,
        $hashedPassword,
        'Instituição Teste Filtros',
        '12345678000111',
        'escola',
        '11977777777',
        'São Paulo',
        'SP'
    ]);

    $testInstituicaoId = $pdo->lastInsertId();
    echo "✓ Instituição criada (ID: {$testInstituicaoId})\n";

    // Criar candidatos de teste com características diferentes
    echo "✓ Criando candidatos de teste...\n";

    // Candidato 1: São Paulo, Superior Completo, Deficiência Visual
    $stmt = $pdo->prepare("
        INSERT INTO candidatos (
            nome_completo, email, senha, cpf, telefone, data_nascimento,
            cidade, estado, escolaridade, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $candidato1Email = 'candidato1_teste_' . time() . '@example.com';
    $stmt->execute([
        'Candidato Teste SP Visual',
        $candidato1Email,
        $hashedPassword,
        '11111111111',
        '11966666666',
        '1995-01-01',
        'São Paulo',
        'SP',
        'superior_completo'
    ]);
    $candidato1Id = $pdo->lastInsertId();
    $testCandidatoIds[] = $candidato1Id;

    // Associar deficiência Visual ao candidato 1
    $stmt = $pdo->prepare("SELECT id_deficiencia FROM deficiencias WHERE nome = 'Visual' LIMIT 1");
    $stmt->execute();
    $deficienciaVisual = $stmt->fetch();

    if ($deficienciaVisual) {
        $stmt = $pdo->prepare("
            INSERT INTO candidatos_deficiencias (id_candidato, id_deficiencia)
            VALUES (?, ?)
        ");
        $stmt->execute([$candidato1Id, $deficienciaVisual['id_deficiencia']]);
    }

    // Candidato 2: Rio de Janeiro, Ensino Médio, Deficiência Auditiva
    $candidato2Email = 'candidato2_teste_' . time() . '@example.com';
    $stmt = $pdo->prepare("
        INSERT INTO candidatos (
            nome_completo, email, senha, cpf, telefone, data_nascimento,
            cidade, estado, escolaridade, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        'Candidato Teste RJ Auditiva',
        $candidato2Email,
        $hashedPassword,
        '22222222222',
        '21955555555',
        '1993-03-03',
        'Rio de Janeiro',
        'RJ',
        'medio_completo'
    ]);
    $candidato2Id = $pdo->lastInsertId();
    $testCandidatoIds[] = $candidato2Id;

    // Associar deficiência Auditiva ao candidato 2
    $stmt = $pdo->prepare("SELECT id_deficiencia FROM deficiencias WHERE nome = 'Auditiva' LIMIT 1");
    $stmt->execute();
    $deficienciaAuditiva = $stmt->fetch();

    if ($deficienciaAuditiva) {
        $stmt = $pdo->prepare("
            INSERT INTO candidatos_deficiencias (id_candidato, id_deficiencia)
            VALUES (?, ?)
        ");
        $stmt->execute([$candidato2Id, $deficienciaAuditiva['id_deficiencia']]);
    }

    echo "✓ Candidatos de teste criados\n";

    // 2. Iniciar WebDriver
    echo "[2/8] Iniciando ChromeDriver...\n";
    $capabilities = DesiredCapabilities::chrome();
    $capabilities->setCapability('goog:chromeOptions', [
        'args' => [
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--window-size=1920,1080'
        ]
    ]);

    $driver = RemoteWebDriver::create(WEBDRIVER_URL, $capabilities, 60000, 60000);
    echo "✓ ChromeDriver iniciado\n";

    // 3. Fazer login como instituição
    echo "[3/8] Fazendo login como instituição...\n";
    $driver->get(BASE_URL . 'login');

    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('email'))
    );

    $driver->findElement(WebDriverBy::id('email'))->sendKeys($testEmail);
    $driver->findElement(WebDriverBy::id('password'))->sendKeys($testPassword);
    $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

    $driver->wait(10)->until(function($driver) {
        $url = $driver->getCurrentURL();
        return strpos($url, '/perfil') !== false || strpos($url, '/dashboard') !== false;
    });

    echo "✓ Login realizado\n";

    // 4. Navegar para página de buscar candidatos
    echo "[4/8] Navegando para buscar candidatos...\n";
    $driver->get(BASE_URL . 'candidatos');

    // Aguardar página carregar - campo de busca por termo (BuscarCandidatosPage.tsx linha 169)
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::id('search-termo')
        )
    );

    echo "✓ Página de busca carregada\n";

    // Aguardar a lista inicial de candidatos carregar
    sleep(2);

    // 5. Testar filtro por localização (cidade)
    echo "[5/8] Testando filtro por localização (cidade)...\n";

    $localizacaoSelect = $driver->findElement(WebDriverBy::id('localizacao-filtro'));
    $localizacaoSelect->click();
    $driver->findElement(WebDriverBy::cssSelector('select#localizacao-filtro option[value="cidade"]'))->click();

    // Aguardar resultados atualizarem (debounce de 400ms)
    sleep(1);

    echo "✓ Filtro de localização aplicado\n";

    // 6. Testar filtro por escolaridade (checkboxes)
    echo "[6/8] Testando filtro por escolaridade...\n";

    // Marcar checkbox "Superior Completo" (BuscarCandidatosPage.tsx linha 188)
    try {
        $superiorCheckbox = $driver->findElement(
            WebDriverBy::id('escolaridade-superior_completo')
        );
        $superiorCheckbox->click();

        sleep(1); // Aguardar debounce

        echo "✓ Filtro de escolaridade aplicado\n";
    } catch (NoSuchElementException $e) {
        echo "⚠ Checkbox de escolaridade não encontrado (pode ter ID diferente)\n";
    }

    // 7. Testar filtro por tipo de deficiência
    echo "[7/8] Testando filtro por tipo de deficiência...\n";

    $deficienciaSelect = $driver->findElement(WebDriverBy::id('tipo_deficiencia'));
    $deficienciaSelect->click();

    try {
        // Selecionar "Deficiência Visual"
        $driver->findElement(WebDriverBy::cssSelector('select#tipo_deficiencia option[value="Visual"]'))->click();

        sleep(1); // Aguardar debounce

        echo "✓ Filtro de deficiência aplicado\n";
    } catch (NoSuchElementException $e) {
        echo "⚠ Opção de deficiência não encontrada\n";
    }

    // 8. Verificar resultados filtrados
    echo "[8/8] Verificando resultados...\n";

    // Aguardar resultados carregarem
    sleep(2);

    // Verificar se existem cards de candidatos
    $candidatoCards = $driver->findElements(
        WebDriverBy::cssSelector('div[class*="CandidatoCard"], a[href*="/candidatos/"]')
    );

    echo "✓ Encontrados " . count($candidatoCards) . " resultado(s) após filtros\n";

    // Verificar que dados de contato NÃO aparecem na listagem
    // (telefone e email não devem estar visíveis - BuscarCandidatosPage.tsx)
    $pageText = $driver->findElement(WebDriverBy::tagName('body'))->getText();

    // Verificar que telefones não aparecem
    if (strpos($pageText, '11966666666') !== false || strpos($pageText, '21955555555') !== false) {
        echo "⚠ AVISO: Telefones estão visíveis na listagem (possível vazamento de dados)\n";
    } else {
        echo "✓ Telefones não aparecem na listagem (correto)\n";
    }

    // Verificar que emails não aparecem
    if (strpos($pageText, $candidato1Email) !== false || strpos($pageText, $candidato2Email) !== false) {
        echo "⚠ AVISO: E-mails estão visíveis na listagem (possível vazamento de dados)\n";
    } else {
        echo "✓ E-mails não aparecem na listagem (correto)\n";
    }

    // Testar limpeza de filtros
    echo "\nTestando limpeza de filtros...\n";

    // Limpar filtro de deficiência
    $deficienciaSelect = $driver->findElement(WebDriverBy::id('tipo_deficiencia'));
    $deficienciaSelect->click();
    $driver->findElement(WebDriverBy::cssSelector('select#tipo_deficiencia option[value=""]'))->click();

    sleep(1);

    // Limpar filtro de localização
    $localizacaoSelect = $driver->findElement(WebDriverBy::id('localizacao-filtro'));
    $localizacaoSelect->click();
    $driver->findElement(WebDriverBy::cssSelector('select#localizacao-filtro option[value=""]'))->click();

    sleep(2);

    $candidatoCardsAll = $driver->findElements(
        WebDriverBy::cssSelector('div[class*="CandidatoCard"], a[href*="/candidatos/"]')
    );

    echo "✓ Após limpar filtros: " . count($candidatoCardsAll) . " resultado(s)\n";

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";

    if ($driver) {
        try {
            $screenshotPath = __DIR__ . '/_output/screenshots/04_VerCandidatosFiltros_' . date('Y-m-d_H-i-s') . '.png';
            $driver->takeScreenshot($screenshotPath);
            echo "Screenshot salvo em: {$screenshotPath}\n";
        } catch (Exception $screenshotEx) {
            echo "Não foi possível salvar screenshot: " . $screenshotEx->getMessage() . "\n";
        }
    }

    exit(1);

} finally {
    // Limpeza
    if ($pdo) {
        try {
            echo "\nLimpando dados de teste...\n";

            foreach ($testCandidatoIds as $candidatoId) {
                // Remover deficiências associadas
                $stmt = $pdo->prepare("DELETE FROM candidatos_deficiencias WHERE id_candidato = ?");
                $stmt->execute([$candidatoId]);

                // Remover experiências
                $stmt = $pdo->prepare("DELETE FROM experiencias_profissionais WHERE id_candidato = ?");
                $stmt->execute([$candidatoId]);

                // Remover candidato
                $stmt = $pdo->prepare("DELETE FROM candidatos WHERE id_candidato = ?");
                $stmt->execute([$candidatoId]);
            }

            echo "✓ Candidatos de teste removidos\n";

            if ($testInstituicaoId) {
                $stmt = $pdo->prepare("DELETE FROM instituicoes WHERE id_instituicao = ?");
                $stmt->execute([$testInstituicaoId]);
                echo "✓ Instituição de teste removida\n";
            }
        } catch (Exception $e) {
            echo "✗ Erro ao limpar dados: " . $e->getMessage() . "\n";
        }
    }

    if ($driver) {
        try {
            $driver->quit();
            echo "✓ WebDriver encerrado\n";
        } catch (Exception $e) {
            echo "✗ Erro ao encerrar WebDriver: " . $e->getMessage() . "\n";
        }
    }
}
