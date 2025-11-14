<?php
/**
 * Teste E2E: Acessibilidade - Tab Order e ARIA
 *
 * OBJETIVO:
 * Testar aspectos de acessibilidade do sistema, validando:
 * - Ordem de tabulação (tab order) em páginas críticas
 * - Presença de atributos ARIA apropriados
 * - Labels e roles em elementos interativos
 * - Foco após modais e ações
 * - Navegação por teclado
 *
 * PRÉ-REQUISITOS:
 * - ChromeDriver rodando em http://127.0.0.1:9515
 * - Frontend rodando em http://localhost:3074
 * - Backend API disponível
 * - Banco de dados 'proapoio' acessível
 *
 * COMO RODAR:
 * php tests/06_Acessibilidade_TabOrder_e_ARIA.php
 */

require 'vendor/autoload.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\Interactions\WebDriverActions;

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

try {
    echo "=== TESTE: Acessibilidade - Tab Order e ARIA ===\n";

    // 1. Conectar ao banco e criar instituição de teste
    echo "[1/6] Preparando dados de teste...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $testEmail = 'teste_acessibilidade_' . time() . '@example.com';
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
        'Instituição Teste Acessibilidade',
        '12345678000133',
        'escola',
        '11944444444',
        'São Paulo',
        'SP'
    ]);

    $testInstituicaoId = $pdo->lastInsertId();
    echo "✓ Dados de teste preparados\n";

    // 2. Iniciar WebDriver
    echo "[2/6] Iniciando ChromeDriver...\n";
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
    $actions = new WebDriverActions($driver);
    echo "✓ ChromeDriver iniciado\n";

    // 3. Testar Tab Order na página de Login
    echo "[3/6] Testando Tab Order na página de Login...\n";
    $driver->get(BASE_URL . 'login');

    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('email'))
    );

    // Testar sequência de tabulação esperada: email -> password -> checkbox remember -> link esqueci senha -> botão submit
    $tabSequence = [];

    // Primeiro elemento (email deve receber foco inicial ou após primeiro TAB)
    $driver->findElement(WebDriverBy::tagName('body'))->click(); // Reset focus
    $actions->sendKeys(WebDriverKeys::TAB)->perform();
    sleep(0.5);

    $activeElement = $driver->switchTo()->activeElement();
    $activeId = $activeElement->getAttribute('id');
    $activeTag = $activeElement->getTagName();
    $tabSequence[] = "{$activeTag}#{$activeId}";

    // Próximos TABs
    for ($i = 0; $i < 5; $i++) {
        $actions->sendKeys(WebDriverKeys::TAB)->perform();
        sleep(0.5);

        $activeElement = $driver->switchTo()->activeElement();
        $activeId = $activeElement->getAttribute('id') ?: 'no-id';
        $activeTag = $activeElement->getTagName();
        $activeType = $activeElement->getAttribute('type') ?: '';
        $tabSequence[] = "{$activeTag}#{$activeId}" . ($activeType ? "[$activeType]" : "");
    }

    echo "  Tab order encontrada:\n";
    foreach ($tabSequence as $index => $element) {
        echo "    [$index] $element\n";
    }

    // Verificar se campo email e password estão na sequência
    $tabSequenceStr = implode(' -> ', $tabSequence);
    if (strpos($tabSequenceStr, 'email') !== false && strpos($tabSequenceStr, 'password') !== false) {
        echo "✓ Tab order inclui campos essenciais (email e password)\n";
    } else {
        echo "⚠ AVISO: Tab order pode não incluir campos essenciais\n";
    }

    // 4. Testar atributos ARIA na página de Login
    echo "[4/6] Testando atributos ARIA na página de Login...\n";

    // Verificar aria-labels e roles importantes
    $ariaChecks = [
        'input[aria-invalid]' => 'aria-invalid em campos de entrada',
        'button[aria-busy]' => 'aria-busy em botões',
        '[role="alert"]' => 'role="alert" para mensagens de erro',
        '[aria-live]' => 'aria-live para anúncios dinâmicos',
        'label[for]' => 'labels associados a campos'
    ];

    foreach ($ariaChecks as $selector => $description) {
        try {
            $elements = $driver->findElements(WebDriverBy::cssSelector($selector));
            if (count($elements) > 0) {
                echo "  ✓ Encontrado: $description (" . count($elements) . " elemento(s))\n";
            } else {
                echo "  ⚠ Não encontrado: $description\n";
            }
        } catch (Exception $e) {
            echo "  ⚠ Erro ao verificar: $description\n";
        }
    }

    // 5. Fazer login e testar acessibilidade em página de buscar candidatos
    echo "[5/6] Testando acessibilidade na página de busca de candidatos...\n";

    $driver->findElement(WebDriverBy::id('email'))->sendKeys($testEmail);
    $driver->findElement(WebDriverBy::id('password'))->sendKeys($testPassword);
    $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

    $driver->wait(10)->until(function($driver) {
        $url = $driver->getCurrentURL();
        return strpos($url, '/perfil') !== false || strpos($url, '/dashboard') !== false;
    });

    // Navegar para buscar candidatos
    $driver->get(BASE_URL . 'candidatos');

    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::id('search-termo')
        )
    );

    // Verificar labels nos campos de filtro
    $filterFields = [
        'search-termo' => 'Campo de busca por termo',
        'localizacao-filtro' => 'Filtro de localização',
        'tipo_deficiencia' => 'Filtro de tipo de deficiência'
    ];

    foreach ($filterFields as $fieldId => $description) {
        try {
            $field = $driver->findElement(WebDriverBy::id($fieldId));
            $label = $driver->findElement(WebDriverBy::cssSelector("label[for='$fieldId']"));

            if ($label) {
                echo "  ✓ $description tem label associado: " . $label->getText() . "\n";
            } else {
                echo "  ⚠ $description não tem label associado\n";
            }
        } catch (Exception $e) {
            echo "  ⚠ Campo '$fieldId' ou seu label não encontrado\n";
        }
    }

    // Testar navegação por teclado nos filtros
    echo "\n  Testando navegação por teclado nos filtros...\n";

    $searchField = $driver->findElement(WebDriverBy::id('search-termo'));
    $searchField->click();

    // Verificar se o campo tem foco
    $activeElement = $driver->switchTo()->activeElement();
    $activeSame = ($activeElement->getID() === $searchField->getID());

    if ($activeSame) {
        echo "  ✓ Campo de busca recebe foco ao clicar\n";
    } else {
        echo "  ⚠ Campo de busca pode não estar recebendo foco corretamente\n";
    }

    // 6. Verificar ARIA em componentes principais
    echo "[6/6] Verificando ARIA em componentes principais...\n";

    // Header (deve ter role="banner" ou tag <header>)
    try {
        $headers = $driver->findElements(WebDriverBy::tagName('header'));
        if (count($headers) > 0) {
            echo "  ✓ Header presente (tag semântica <header>)\n";
        } else {
            $banners = $driver->findElements(WebDriverBy::cssSelector('[role="banner"]'));
            if (count($banners) > 0) {
                echo "  ✓ Banner presente (role=\"banner\")\n";
            } else {
                echo "  ⚠ Header/Banner não encontrado\n";
            }
        }
    } catch (Exception $e) {
        echo "  ⚠ Erro ao verificar header\n";
    }

    // Links e botões devem ter textos descritivos
    $links = $driver->findElements(WebDriverBy::tagName('a'));
    $linksWithoutText = 0;

    foreach ($links as $link) {
        $text = trim($link->getText());
        $ariaLabel = $link->getAttribute('aria-label');

        if (empty($text) && empty($ariaLabel)) {
            $linksWithoutText++;
        }
    }

    if ($linksWithoutText > 0) {
        echo "  ⚠ Encontrados $linksWithoutText link(s) sem texto ou aria-label\n";
    } else {
        echo "  ✓ Todos os links têm texto ou aria-label\n";
    }

    // Verificar botões
    $buttons = $driver->findElements(WebDriverBy::tagName('button'));
    $buttonsWithoutLabel = 0;

    foreach ($buttons as $button) {
        $text = trim($button->getText());
        $ariaLabel = $button->getAttribute('aria-label');

        if (empty($text) && empty($ariaLabel)) {
            $buttonsWithoutLabel++;
        }
    }

    if ($buttonsWithoutLabel > 0) {
        echo "  ⚠ Encontrados $buttonsWithoutLabel botão(ões) sem texto ou aria-label\n";
    } else {
        echo "  ✓ Todos os botões têm texto ou aria-label\n";
    }

    // Verificar imagens com alt
    $images = $driver->findElements(WebDriverBy::tagName('img'));
    $imagesWithoutAlt = 0;

    foreach ($images as $img) {
        $alt = $img->getAttribute('alt');
        if ($alt === null || $alt === '') {
            $imagesWithoutAlt++;
        }
    }

    if ($imagesWithoutAlt > 0) {
        echo "  ⚠ Encontradas $imagesWithoutAlt imagem(ns) sem atributo alt\n";
    } else if (count($images) > 0) {
        echo "  ✓ Todas as imagens têm atributo alt\n";
    } else {
        echo "  ℹ Nenhuma imagem encontrada na página\n";
    }

    echo "\n=== TESTE DE ACESSIBILIDADE CONCLUÍDO ===\n";
    echo "\nRESUMO:\n";
    echo "- Tab order testada na página de login\n";
    echo "- Atributos ARIA verificados\n";
    echo "- Labels de formulários verificados\n";
    echo "- Navegação por teclado testada\n";
    echo "- Elementos semânticos verificados\n";
    echo "\nConsulte os avisos (⚠) acima para possíveis melhorias de acessibilidade.\n";

    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";

    if ($driver) {
        try {
            $screenshotPath = __DIR__ . '/_output/screenshots/06_Acessibilidade_' . date('Y-m-d_H-i-s') . '.png';
            $driver->takeScreenshot($screenshotPath);
            echo "Screenshot salvo em: {$screenshotPath}\n";
        } catch (Exception $screenshotEx) {
            echo "Não foi possível salvar screenshot: " . $screenshotEx->getMessage() . "\n";
        }
    }

    exit(1);

} finally {
    // Limpeza
    if ($pdo && $testInstituicaoId) {
        try {
            echo "\nLimpando dados de teste...\n";
            $stmt = $pdo->prepare("DELETE FROM instituicoes WHERE id_instituicao = ?");
            $stmt->execute([$testInstituicaoId]);
            echo "✓ Dados de teste removidos\n";
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
