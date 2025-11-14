<?php
/**
 * Teste E2E: Cadastro de Vaga por Instituição
 *
 * OBJETIVO:
 * Testar o fluxo completo de criação de uma vaga, validando:
 * - Login de instituição
 * - Navegação até a página de criar vaga
 * - Preenchimento de todos os campos obrigatórios
 * - Submissão do formulário
 * - Verificação da vaga criada
 * - Limpeza dos dados
 *
 * PRÉ-REQUISITOS:
 * - ChromeDriver rodando em http://127.0.0.1:9515
 * - Frontend rodando em http://localhost:3074
 * - Backend API disponível
 * - Banco de dados 'proapoio' acessível
 *
 * COMO RODAR:
 * php tests/02_CadastroVaga.php
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
$testVagaId = null;

try {
    echo "=== TESTE: Cadastro de Vaga ===\n";

    // 1. Conectar ao banco e criar instituição de teste
    echo "[1/7] Preparando dados de teste...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $testEmail = 'teste_vaga_' . time() . '@example.com';
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
        'Instituição Teste Vaga',
        '12345678000100',
        'escola',
        '11988888888',
        'São Paulo',
        'SP'
    ]);

    $testInstituicaoId = $pdo->lastInsertId();
    echo "✓ Instituição de teste criada (ID: {$testInstituicaoId})\n";

    // 2. Iniciar WebDriver
    echo "[2/7] Iniciando ChromeDriver...\n";
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

    // 3. Fazer login
    echo "[3/7] Fazendo login como instituição...\n";
    $driver->get(BASE_URL . 'login');

    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('email'))
    );

    $driver->findElement(WebDriverBy::id('email'))->sendKeys($testEmail);
    $driver->findElement(WebDriverBy::id('password'))->sendKeys($testPassword);
    $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

    // Aguardar redirecionamento após login
    $driver->wait(10)->until(function($driver) {
        $url = $driver->getCurrentURL();
        return strpos($url, '/perfil/instituicao') !== false || strpos($url, '/dashboard') !== false;
    });

    echo "✓ Login realizado com sucesso\n";

    // 4. Navegar para a página de criar vaga
    echo "[4/7] Navegando para página de criar vaga...\n";
    $driver->get(BASE_URL . 'vagas/criar');

    // Aguardar formulário carregar - campo titulo_vaga (CreateVagaPage.tsx)
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::name('titulo_vaga')
        )
    );

    echo "✓ Página de criação de vaga carregada\n";

    // 5. Preencher formulário
    echo "[5/7] Preenchendo formulário de vaga...\n";

    // Título da vaga
    $tituloVaga = 'Agente de Apoio Educacional - Teste Selenium ' . time();
    $driver->findElement(WebDriverBy::name('titulo_vaga'))->sendKeys($tituloVaga);

    // Descrição
    $descricao = 'Vaga para agente de apoio educacional com experiência em inclusão. ' .
                 'Buscamos profissional dedicado e comprometido com a educação inclusiva.';
    $driver->findElement(WebDriverBy::name('descricao'))->sendKeys($descricao);

    // Necessidades específicas
    $necessidades = 'Experiência prévia com alunos com deficiência visual. Conhecimento em Braille desejável.';
    $driver->findElement(WebDriverBy::name('necessidades_descricao'))->sendKeys($necessidades);

    // Cidade e Estado
    $driver->findElement(WebDriverBy::name('cidade'))->sendKeys('São Paulo');

    $estadoSelect = $driver->findElement(WebDriverBy::name('estado'));
    $estadoSelect->click();
    // Selecionar SP
    $driver->findElement(WebDriverBy::cssSelector('select[name="estado"] option[value="SP"]'))->click();

    // Tipo (select) - já tem valor padrão PRESENCIAL
    // Mas vamos garantir
    $tipoSelect = $driver->findElement(WebDriverBy::name('tipo'));
    $tipoSelect->click();
    $driver->findElement(WebDriverBy::cssSelector('select[name="tipo"] option[value="PRESENCIAL"]'))->click();

    // Modalidade
    $driver->findElement(WebDriverBy::name('modalidade'))->sendKeys('Tempo integral');

    // Carga horária semanal
    $driver->findElement(WebDriverBy::name('carga_horaria_semanal'))->sendKeys('40');

    // Regime de contratação - já tem padrão CLT
    $regimeSelect = $driver->findElement(WebDriverBy::name('regime_contratacao'));
    $regimeSelect->click();
    $driver->findElement(WebDriverBy::cssSelector('select[name="regime_contratacao"] option[value="CLT"]'))->click();

    // Remuneração
    $driver->findElement(WebDriverBy::name('valor_remuneracao'))->sendKeys('2500.00');

    // Tipo de remuneração - já tem padrão MENSAL
    $tipoRemuneracaoSelect = $driver->findElement(WebDriverBy::name('tipo_remuneracao'));
    $tipoRemuneracaoSelect->click();
    $driver->findElement(WebDriverBy::cssSelector('select[name="tipo_remuneracao"] option[value="MENSAL"]'))->click();

    // Dados do aluno (mês e ano de nascimento)
    $driver->findElement(WebDriverBy::name('aluno_nascimento_mes'))->sendKeys('03');
    $driver->findElement(WebDriverBy::name('aluno_nascimento_ano'))->sendKeys('2015');

    // Selecionar deficiências (checkboxes)
    // Aguardar os checkboxes carregarem
    sleep(1); // Necessário para API de deficiências retornar

    try {
        // Tentar selecionar primeira deficiência disponível
        $checkboxes = $driver->findElements(WebDriverBy::cssSelector('input[type="checkbox"][name^="deficiencia"]'));
        if (count($checkboxes) > 0) {
            $checkboxes[0]->click();
            echo "  ✓ Deficiência selecionada\n";
        }
    } catch (Exception $e) {
        echo "  ⚠ Aviso: Não foi possível selecionar deficiência (pode não haver checkboxes na página)\n";
    }

    echo "✓ Formulário preenchido\n";

    // Pequena pausa para garantir que todos os campos foram preenchidos
    sleep(1);

    // 6. Submeter formulário
    echo "[6/7] Submetendo formulário...\n";

    // Procurar botão de submit (geralmente tem texto "Criar Vaga" ou "Salvar")
    $submitButton = $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'));
    $submitButton->click();

    // Aguardar redirecionamento ou mensagem de sucesso
    // Geralmente redireciona para /vagas/minhas ou mostra toast
    try {
        $driver->wait(10)->until(function($driver) {
            $url = $driver->getCurrentURL();
            // Pode redirecionar para lista de vagas ou dashboard
            return strpos($url, '/vagas') !== false
                || strpos($url, '/dashboard') !== false
                || strpos($url, '/perfil') !== false;
        });

        echo "✓ Vaga criada com sucesso!\n";

        // 7. Verificar se a vaga foi criada no banco
        echo "[7/7] Verificando vaga no banco de dados...\n";

        $stmt = $pdo->prepare("
            SELECT id_vaga, titulo_vaga, descricao, cidade, estado, status
            FROM vagas
            WHERE id_instituicao = ?
            AND titulo_vaga = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$testInstituicaoId, $tituloVaga]);
        $vaga = $stmt->fetch();

        if ($vaga) {
            $testVagaId = $vaga['id_vaga'];
            echo "✓ Vaga encontrada no banco:\n";
            echo "  - ID: {$vaga['id_vaga']}\n";
            echo "  - Título: {$vaga['titulo_vaga']}\n";
            echo "  - Cidade: {$vaga['cidade']}/{$vaga['estado']}\n";
            echo "  - Status: {$vaga['status']}\n";
        } else {
            throw new Exception("Vaga não foi encontrada no banco de dados!");
        }

    } catch (TimeoutException $e) {
        throw new Exception("Timeout: Não foi possível confirmar a criação da vaga. URL atual: " . $driver->getCurrentURL());
    }

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";

    if ($driver) {
        try {
            $screenshotPath = __DIR__ . '/_output/screenshots/02_CadastroVaga_' . date('Y-m-d_H-i-s') . '.png';
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

            if ($testVagaId) {
                // Remover deficiências associadas
                $stmt = $pdo->prepare("DELETE FROM vagas_deficiencias WHERE id_vaga = ?");
                $stmt->execute([$testVagaId]);

                // Remover vaga
                $stmt = $pdo->prepare("DELETE FROM vagas WHERE id_vaga = ?");
                $stmt->execute([$testVagaId]);
                echo "✓ Vaga de teste removida\n";
            }

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
