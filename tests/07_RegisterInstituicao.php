<?php
/**
 * Teste E2E: Registro de Instituição
 *
 * OBJETIVO:
 * Testar o fluxo completo de cadastro de uma instituição, validando:
 * - Navegação até a página de registro de instituição
 * - Preenchimento de todos os campos obrigatórios
 * - Submissão do formulário
 * - Verificação da instituição criada no banco
 * - Limpeza dos dados
 *
 * PRÉ-REQUISITOS:
 * - ChromeDriver rodando em http://127.0.0.1:9515
 * - Frontend rodando em http://localhost:3074
 * - Backend API disponível
 * - Banco de dados 'proapoio' acessível
 *
 * COMO RODAR:
 * php tests/07_RegisterInstituicao.php
 */

require 'vendor/autoload.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\TimeoutException;

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
    echo "=== TESTE: Registro de Instituição ===\n";

    // 1. Conectar ao banco
    echo "[1/6] Conectando ao banco de dados...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✓ Conectado ao banco de dados\n";

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
    echo "✓ ChromeDriver iniciado\n";

    // 3. Navegar para página de registro de instituição
    echo "[3/6] Navegando para página de registro...\n";
    $driver->get(BASE_URL . 'register/instituicao');

    // Aguardar formulário carregar
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::name('nome_fantasia')
        )
    );
    echo "✓ Página de registro carregada\n";

    // 4. Preencher formulário
    echo "[4/6] Preenchendo formulário de registro...\n";

    $testEmail = 'instituicao_register_' . time() . '@example.com';
    $testPassword = 'SenhaSegura123!';

    // Dados básicos
    $driver->findElement(WebDriverBy::name('nome_fantasia'))->sendKeys('Escola Teste Selenium');
    $driver->findElement(WebDriverBy::name('razao_social'))->sendKeys('Escola Teste Selenium LTDA');
    $driver->findElement(WebDriverBy::name('cnpj'))->sendKeys('12345678000144');
    $driver->findElement(WebDriverBy::name('email'))->sendKeys($testEmail);
    $driver->findElement(WebDriverBy::name('password'))->sendKeys($testPassword);
    $driver->findElement(WebDriverBy::name('password_confirmation'))->sendKeys($testPassword);

    // Código INEP (opcional mas vamos preencher)
    try {
        $driver->findElement(WebDriverBy::name('codigo_inep'))->sendKeys('12345678');
    } catch (Exception $e) {
        echo "  ⚠ Campo codigo_inep não encontrado\n";
    }

    // Tipo de instituição
    $tipoSelect = $driver->findElement(WebDriverBy::name('tipo_instituicao'));
    $tipoSelect->click();
    $driver->findElement(WebDriverBy::cssSelector('select[name="tipo_instituicao"] option[value="Pública Municipal"]'))->click();

    // Níveis oferecidos (checkboxes)
    try {
        $checkboxes = $driver->findElements(WebDriverBy::cssSelector('input[type="checkbox"][name^="niveis"]'));
        if (count($checkboxes) > 0) {
            $checkboxes[0]->click(); // Marcar primeiro nível
            echo "  ✓ Nível oferecido selecionado\n";
        }
    } catch (Exception $e) {
        echo "  ⚠ Checkboxes de níveis não encontrados\n";
    }

    // Contato
    try {
        $driver->findElement(WebDriverBy::name('email_corporativo'))->sendKeys('contato@escolateste.com.br');
    } catch (Exception $e) {
        echo "  ⚠ Campo email_corporativo não encontrado\n";
    }

    try {
        $driver->findElement(WebDriverBy::name('celular_corporativo'))->sendKeys('11987654321');
    } catch (Exception $e) {
        echo "  ⚠ Campo celular_corporativo não encontrado\n";
    }

    try {
        $driver->findElement(WebDriverBy::name('telefone_fixo'))->sendKeys('1133334444');
    } catch (Exception $e) {
        echo "  ⚠ Campo telefone_fixo não encontrado\n";
    }

    // Responsável
    try {
        $driver->findElement(WebDriverBy::name('nome_responsavel'))->sendKeys('Maria Silva');
    } catch (Exception $e) {
        echo "  ⚠ Campo nome_responsavel não encontrado\n";
    }

    try {
        $driver->findElement(WebDriverBy::name('funcao_responsavel'))->sendKeys('Diretora');
    } catch (Exception $e) {
        echo "  ⚠ Campo funcao_responsavel não encontrado\n";
    }

    // Endereço
    $driver->findElement(WebDriverBy::name('cep'))->sendKeys('01310100');

    // Aguardar API ViaCEP
    sleep(2);

    // Complementar campos de endereço se não preenchidos automaticamente
    $logradouroField = $driver->findElement(WebDriverBy::name('logradouro'));
    if (empty($logradouroField->getAttribute('value'))) {
        $logradouroField->sendKeys('Avenida Paulista');
    }

    try {
        $numeroField = $driver->findElement(WebDriverBy::name('numero'));
        $numeroField->sendKeys('1000');
    } catch (Exception $e) {
        echo "  ⚠ Campo numero não encontrado\n";
    }

    $bairroField = $driver->findElement(WebDriverBy::name('bairro'));
    if (empty($bairroField->getAttribute('value'))) {
        $bairroField->sendKeys('Bela Vista');
    }

    $cidadeField = $driver->findElement(WebDriverBy::name('cidade'));
    if (empty($cidadeField->getAttribute('value'))) {
        $cidadeField->sendKeys('São Paulo');
    }

    // Estado
    $estadoSelect = $driver->findElement(WebDriverBy::name('estado'));
    $estadoSelect->click();
    $driver->findElement(WebDriverBy::cssSelector('select[name="estado"] option[value="SP"]'))->click();

    echo "✓ Formulário preenchido\n";

    // Rolar para o botão de submit
    $driver->executeScript("window.scrollTo(0, document.body.scrollHeight);");
    sleep(1);

    // 5. Submeter formulário
    echo "[5/6] Submetendo formulário...\n";

    $submitButton = $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'));
    $submitButton->click();

    // Aguardar resposta e redirecionamento
    try {
        $driver->wait(15)->until(function($driver) {
            $url = $driver->getCurrentURL();
            return strpos($url, '/login') !== false
                || strpos($url, '/dashboard') !== false
                || strpos($url, '/perfil/instituicao') !== false;
        });

        echo "✓ Cadastro enviado com sucesso!\n";

    } catch (TimeoutException $e) {
        // Verificar se há mensagem de erro
        $bodyText = $driver->findElement(WebDriverBy::tagName('body'))->getText();

        if (strpos($bodyText, 'já está em uso') !== false || strpos($bodyText, 'já cadastrado') !== false) {
            echo "⚠ Aviso: E-mail ou CNPJ pode já estar cadastrado\n";
        }

        throw new Exception("Timeout: Não foi possível confirmar o cadastro. URL atual: " . $driver->getCurrentURL());
    }

    // 6. Verificar instituição no banco
    echo "[6/6] Verificando instituição no banco de dados...\n";

    $stmt = $pdo->prepare("
        SELECT id_instituicao, nome_instituicao, email, cnpj, cidade, estado, tipo_instituicao
        FROM instituicoes
        WHERE email = ? OR cnpj = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$testEmail, '12345678000144']);
    $instituicao = $stmt->fetch();

    if ($instituicao) {
        $testInstituicaoId = $instituicao['id_instituicao'];
        echo "✓ Instituição encontrada no banco:\n";
        echo "  - ID: {$instituicao['id_instituicao']}\n";
        echo "  - Nome: {$instituicao['nome_instituicao']}\n";
        echo "  - Email: {$instituicao['email']}\n";
        echo "  - CNPJ: {$instituicao['cnpj']}\n";
        echo "  - Localização: {$instituicao['cidade']}/{$instituicao['estado']}\n";
        echo "  - Tipo: {$instituicao['tipo_instituicao']}\n";
    } else {
        throw new Exception("Instituição não foi encontrada no banco de dados!");
    }

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";

    if ($driver) {
        try {
            $screenshotPath = __DIR__ . '/_output/screenshots/07_RegisterInstituicao_' . date('Y-m-d_H-i-s') . '.png';
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

            // Remover vagas associadas
            $stmt = $pdo->prepare("DELETE FROM vagas WHERE id_instituicao = ?");
            $stmt->execute([$testInstituicaoId]);

            // Remover instituição
            $stmt = $pdo->prepare("DELETE FROM instituicoes WHERE id_instituicao = ?");
            $stmt->execute([$testInstituicaoId]);

            echo "✓ Instituição de teste removida\n";
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
