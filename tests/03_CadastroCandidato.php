<?php
/**
 * Teste E2E: Cadastro de Candidato
 *
 * OBJETIVO:
 * Testar o fluxo completo de cadastro de um candidato (agente de apoio), validando:
 * - Navegação até a página de cadastro de candidato
 * - Preenchimento de todos os campos obrigatórios (dados pessoais, endereço, escolaridade)
 * - Adição de experiência profissional
 * - Submissão do formulário
 * - Verificação do candidato criado no banco
 * - Limpeza dos dados
 *
 * PRÉ-REQUISITOS:
 * - ChromeDriver rodando em http://127.0.0.1:9515
 * - Frontend rodando em http://localhost:3074
 * - Backend API disponível
 * - Banco de dados 'proapoio' acessível
 *
 * COMO RODAR:
 * php tests/03_CadastroCandidato.php
 */

require 'vendor/autoload.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;
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
$testCandidatoId = null;

try {
    echo "=== TESTE: Cadastro de Candidato ===\n";

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

    // 3. Navegar para página de cadastro de candidato
    echo "[3/6] Navegando para página de cadastro...\n";
    $driver->get(BASE_URL . 'register/candidato');

    // Aguardar formulário carregar - campo nome_completo (RegisterCandidatoPage.tsx)
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::name('nome_completo')
        )
    );
    echo "✓ Página de cadastro carregada\n";

    // 4. Preencher formulário
    echo "[4/6] Preenchendo formulário de cadastro...\n";

    // Dados pessoais
    $testEmail = 'candidato_teste_' . time() . '@example.com';
    $testPassword = 'SenhaSegura123!';

    $driver->findElement(WebDriverBy::name('nome_completo'))->sendKeys('João da Silva Teste Selenium');
    $driver->findElement(WebDriverBy::name('email'))->sendKeys($testEmail);
    $driver->findElement(WebDriverBy::name('telefone'))->sendKeys('11987654321');
    $driver->findElement(WebDriverBy::name('cpf'))->sendKeys('12345678901');

    // Data de nascimento (formato yyyy-mm-dd)
    $driver->findElement(WebDriverBy::name('data_nascimento'))->sendKeys('1990-05-15');

    // Senha
    $driver->findElement(WebDriverBy::name('password'))->sendKeys($testPassword);
    $driver->findElement(WebDriverBy::name('password_confirmation'))->sendKeys($testPassword);

    // Endereço
    $driver->findElement(WebDriverBy::name('cep'))->sendKeys('01310100');

    // Aguardar CEP ser preenchido automaticamente (via API)
    sleep(2); // Necessário para API ViaCEP retornar

    // Completar campos de endereço caso não sejam preenchidos automaticamente
    $logradouroField = $driver->findElement(WebDriverBy::name('logradouro'));
    if (empty($logradouroField->getAttribute('value'))) {
        $logradouroField->sendKeys('Avenida Paulista');
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

    // Escolaridade
    $escolaridadeSelect = $driver->findElement(WebDriverBy::name('escolaridade'));
    $escolaridadeSelect->click();
    // Selecionar "Ensino Superior Completo"
    $driver->findElement(WebDriverBy::cssSelector('select[name="escolaridade"] option[value="superior_completo"]'))->click();

    // Curso superior (aparece condicionalmente)
    try {
        $cursoField = $driver->findElement(WebDriverBy::name('curso_superior'));
        $cursoField->sendKeys('Pedagogia');
    } catch (Exception $e) {
        echo "  ⚠ Campo 'curso_superior' não encontrado (pode ser condicional)\n";
    }

    // Instituição de ensino
    try {
        $instituicaoField = $driver->findElement(WebDriverBy::name('instituicao_ensino'));
        $instituicaoField->sendKeys('Universidade de São Paulo');
    } catch (Exception $e) {
        echo "  ⚠ Campo 'instituicao_ensino' não encontrado\n";
    }

    // Experiência profissional (primeira experiência é adicionada por padrão)
    // Procurar campos de experiência (RegisterCandidatoPage.tsx linha 92-98)

    // Idade do aluno
    try {
        $idadeAlunoField = $driver->findElement(WebDriverBy::name('experiencias_profissionais[0][idade_aluno]'));
        $idadeAlunoField->sendKeys('10');
    } catch (Exception $e) {
        echo "  ⚠ Campo idade_aluno não encontrado (formato pode variar)\n";
    }

    // Tempo de experiência
    try {
        $tempoExpSelect = $driver->findElement(WebDriverBy::name('experiencias_profissionais[0][tempo_experiencia]'));
        $tempoExpSelect->click();
        // Selecionar "1-2 anos"
        $driver->findElement(WebDriverBy::cssSelector('select[name="experiencias_profissionais[0][tempo_experiencia]"] option[value="1-2"]'))->click();
    } catch (Exception $e) {
        echo "  ⚠ Campo tempo_experiencia não encontrado\n";
    }

    // Comentário sobre experiência
    try {
        $comentarioField = $driver->findElement(WebDriverBy::name('experiencias_profissionais[0][comentario]'));
        $comentarioField->sendKeys('Trabalhei como auxiliar de sala com alunos de inclusão. Tenho experiência com adaptação de materiais e comunicação com famílias.');
    } catch (Exception $e) {
        echo "  ⚠ Campo comentário não encontrado\n";
    }

    // Selecionar deficiências (checkboxes)
    sleep(1); // Aguardar API de deficiências

    try {
        // Tentar selecionar primeira deficiência disponível em deficiencia_ids
        $checkboxes = $driver->findElements(WebDriverBy::cssSelector('input[type="checkbox"][name^="deficiencia"]'));
        if (count($checkboxes) > 0) {
            $checkboxes[0]->click();
            echo "  ✓ Deficiência selecionada\n";
        }
    } catch (Exception $e) {
        echo "  ⚠ Checkboxes de deficiência não encontrados\n";
    }

    echo "✓ Formulário preenchido\n";

    // Rolar para o botão de submit (pode estar fora da viewport)
    $driver->executeScript("window.scrollTo(0, document.body.scrollHeight);");
    sleep(1);

    // 5. Submeter formulário
    echo "[5/6] Submetendo formulário...\n";

    $submitButton = $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'));
    $submitButton->click();

    // Aguardar resposta e redirecionamento
    // Após sucesso, deve redirecionar para /login ou /dashboard
    try {
        $driver->wait(15)->until(function($driver) {
            $url = $driver->getCurrentURL();
            return strpos($url, '/login') !== false
                || strpos($url, '/dashboard') !== false
                || strpos($url, '/perfil/candidato') !== false;
        });

        echo "✓ Cadastro enviado com sucesso!\n";

    } catch (TimeoutException $e) {
        // Verificar se há mensagem de erro na página
        $bodyText = $driver->findElement(WebDriverBy::tagName('body'))->getText();

        if (strpos($bodyText, 'já está em uso') !== false || strpos($bodyText, 'já cadastrado') !== false) {
            echo "⚠ Aviso: E-mail pode já estar cadastrado (teste anterior não limpou?)\n";
        }

        throw new Exception("Timeout: Não foi possível confirmar o cadastro. URL atual: " . $driver->getCurrentURL());
    }

    // 6. Verificar candidato no banco
    echo "[6/6] Verificando candidato no banco de dados...\n";

    $stmt = $pdo->prepare("
        SELECT id_candidato, nome_completo, email, cidade, estado
        FROM candidatos
        WHERE email = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$testEmail]);
    $candidato = $stmt->fetch();

    if ($candidato) {
        $testCandidatoId = $candidato['id_candidato'];
        echo "✓ Candidato encontrado no banco:\n";
        echo "  - ID: {$candidato['id_candidato']}\n";
        echo "  - Nome: {$candidato['nome_completo']}\n";
        echo "  - Email: {$candidato['email']}\n";
        echo "  - Localização: {$candidato['cidade']}/{$candidato['estado']}\n";
    } else {
        throw new Exception("Candidato não foi encontrado no banco de dados!");
    }

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";

    if ($driver) {
        try {
            $screenshotPath = __DIR__ . '/_output/screenshots/03_CadastroCandidato_' . date('Y-m-d_H-i-s') . '.png';
            $driver->takeScreenshot($screenshotPath);
            echo "Screenshot salvo em: {$screenshotPath}\n";
        } catch (Exception $screenshotEx) {
            echo "Não foi possível salvar screenshot: " . $screenshotEx->getMessage() . "\n";
        }
    }

    exit(1);

} finally {
    // Limpeza
    if ($pdo && $testCandidatoId) {
        try {
            echo "\nLimpando dados de teste...\n";

            // Remover experiências profissionais
            $stmt = $pdo->prepare("DELETE FROM experiencias_profissionais WHERE id_candidato = ?");
            $stmt->execute([$testCandidatoId]);

            // Remover deficiências associadas
            $stmt = $pdo->prepare("DELETE FROM candidatos_deficiencias WHERE id_candidato = ?");
            $stmt->execute([$testCandidatoId]);

            // Remover candidato
            $stmt = $pdo->prepare("DELETE FROM candidatos WHERE id_candidato = ?");
            $stmt->execute([$testCandidatoId]);

            echo "✓ Candidato de teste removido\n";
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
