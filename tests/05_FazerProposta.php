<?php
/**
 * Teste E2E: Fazer Proposta para Candidato
 *
 * OBJETIVO:
 * Testar o fluxo completo de envio de proposta de uma instituição para um candidato, validando:
 * - Login como instituição
 * - Busca e localização de candidato
 * - Abertura do modal de proposta
 * - Preenchimento da mensagem de proposta
 * - Envio da proposta
 * - Verificação da proposta no banco de dados
 * - Limpeza dos dados
 *
 * PRÉ-REQUISITOS:
 * - ChromeDriver rodando em http://127.0.0.1:9515
 * - Frontend rodando em http://localhost:3074
 * - Backend API disponível
 * - Banco de dados 'proapoio' acessível
 *
 * COMO RODAR:
 * php tests/05_FazerProposta.php
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
$testCandidatoId = null;
$testVagaId = null;
$testPropostaId = null;

try {
    echo "=== TESTE: Fazer Proposta para Candidato ===\n";

    // 1. Preparar dados de teste
    echo "[1/8] Preparando dados de teste...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $testPassword = 'SenhaSegura123!';
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);

    // Criar instituição
    $testEmail = 'teste_proposta_inst_' . time() . '@example.com';
    $stmt = $pdo->prepare("
        INSERT INTO instituicoes (
            email, senha, nome_instituicao, cnpj, tipo_instituicao,
            telefone, cidade, estado, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $stmt->execute([
        $testEmail,
        $hashedPassword,
        'Instituição Teste Proposta',
        '12345678000122',
        'escola',
        '11966666666',
        'São Paulo',
        'SP'
    ]);

    $testInstituicaoId = $pdo->lastInsertId();
    echo "✓ Instituição criada (ID: {$testInstituicaoId})\n";

    // Criar candidato
    $candidatoEmail = 'candidato_proposta_' . time() . '@example.com';
    $stmt = $pdo->prepare("
        INSERT INTO candidatos (
            nome_completo, email, senha, cpf, telefone, data_nascimento,
            cidade, estado, escolaridade, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $stmt->execute([
        'Candidato Teste Proposta',
        $candidatoEmail,
        $hashedPassword,
        '33333333333',
        '11955555555',
        '1992-06-15',
        'São Paulo',
        'SP',
        'superior_completo'
    ]);

    $testCandidatoId = $pdo->lastInsertId();
    echo "✓ Candidato criado (ID: {$testCandidatoId})\n";

    // Criar vaga da instituição
    $stmt = $pdo->prepare("
        INSERT INTO vagas (
            id_instituicao, titulo_vaga, descricao, cidade, estado,
            status, tipo, modalidade, carga_horaria_semanal,
            regime_contratacao, valor_remuneracao, tipo_remuneracao,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $stmt->execute([
        $testInstituicaoId,
        'Vaga Teste Proposta',
        'Vaga para teste de proposta',
        'São Paulo',
        'SP',
        'ATIVA',
        'PRESENCIAL',
        'Tempo integral',
        40,
        'CLT',
        2500.00,
        'MENSAL'
    ]);

    $testVagaId = $pdo->lastInsertId();
    echo "✓ Vaga criada (ID: {$testVagaId})\n";

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

    // 4. Navegar para perfil público do candidato
    echo "[4/8] Navegando para perfil do candidato...\n";
    $driver->get(BASE_URL . 'candidatos/' . $testCandidatoId);

    // Aguardar página carregar
    $driver->wait(10)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::tagName('h1')
        )
    );

    sleep(1);
    echo "✓ Perfil do candidato carregado\n";

    // 5. Localizar botão "Fazer Proposta" ou "Enviar Proposta"
    echo "[5/8] Localizando botão de proposta...\n";

    try {
        // Tentar vários seletores possíveis
        $propostaButton = null;

        try {
            // Seletor por texto do botão
            $propostaButton = $driver->findElement(
                WebDriverBy::xpath("//button[contains(text(), 'Fazer Proposta') or contains(text(), 'Enviar Proposta')]")
            );
        } catch (Exception $e) {
            // Tentar por classe CSS
            $propostaButton = $driver->findElement(
                WebDriverBy::cssSelector('button[class*="proposta"], button[aria-label*="proposta"]')
            );
        }

        echo "✓ Botão de proposta encontrado\n";

        // Rolar para o botão se necessário
        $driver->executeScript("arguments[0].scrollIntoView(true);", [$propostaButton]);
        sleep(1);

        // 6. Clicar no botão para abrir modal
        echo "[6/8] Abrindo modal de proposta...\n";
        $propostaButton->click();

        // Aguardar modal abrir (PropostaModal.tsx linha 104)
        $driver->wait(10)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('div[role="dialog"], div[aria-modal="true"], textarea#mensagem')
            )
        );

        echo "✓ Modal de proposta aberto\n";

    } catch (Exception $e) {
        throw new Exception("Não foi possível localizar ou clicar no botão de proposta: " . $e->getMessage());
    }

    // 7. Preencher formulário de proposta
    echo "[7/8] Preenchendo mensagem de proposta...\n";

    // Campo de mensagem (PropostaModal.tsx linha 139)
    $mensagemField = $driver->findElement(WebDriverBy::id('mensagem'));

    $mensagemProposta = "Olá! Ficamos muito interessados no seu perfil e gostaríamos de convidá-lo(a) " .
                        "para uma oportunidade em nossa instituição. Você tem experiência que se alinha " .
                        "perfeitamente com nossas necessidades. Aguardamos seu contato!";

    $mensagemField->clear();
    $mensagemField->sendKeys($mensagemProposta);

    echo "✓ Mensagem preenchida\n";

    sleep(1);

    // 8. Submeter proposta
    echo "[8/8] Enviando proposta...\n";

    // Botão submit no modal (PropostaModal.tsx linha 176)
    $submitButton = $driver->findElement(
        WebDriverBy::xpath("//button[@type='submit' and (contains(text(), 'Enviar') or contains(., 'Enviar'))]")
    );

    $submitButton->click();

    // Aguardar confirmação (modal fechar ou toast aparecer)
    try {
        // Aguardar modal desaparecer
        $driver->wait(10)->until(
            WebDriverExpectedCondition::invisibilityOfElementLocated(
                WebDriverBy::cssSelector('div[role="dialog"]')
            )
        );

        echo "✓ Proposta enviada (modal fechado)\n";

    } catch (TimeoutException $e) {
        // Modal pode não fechar imediatamente, verificar se houve sucesso de outra forma
        echo "⚠ Modal ainda visível, verificando banco de dados...\n";
    }

    // Verificar no banco de dados
    sleep(2);

    $stmt = $pdo->prepare("
        SELECT id_proposta, id_vaga, id_candidato, id_instituicao, mensagem, status
        FROM propostas
        WHERE id_candidato = ?
        AND id_instituicao = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$testCandidatoId, $testInstituicaoId]);
    $proposta = $stmt->fetch();

    if ($proposta) {
        $testPropostaId = $proposta['id_proposta'];
        echo "✓ Proposta encontrada no banco:\n";
        echo "  - ID: {$proposta['id_proposta']}\n";
        echo "  - Vaga: {$proposta['id_vaga']}\n";
        echo "  - Status: {$proposta['status']}\n";
        echo "  - Mensagem: " . substr($proposta['mensagem'], 0, 50) . "...\n";
    } else {
        throw new Exception("Proposta não foi encontrada no banco de dados!");
    }

    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";

    if ($driver) {
        try {
            $screenshotPath = __DIR__ . '/_output/screenshots/05_FazerProposta_' . date('Y-m-d_H-i-s') . '.png';
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

            if ($testPropostaId) {
                $stmt = $pdo->prepare("DELETE FROM propostas WHERE id_proposta = ?");
                $stmt->execute([$testPropostaId]);
                echo "✓ Proposta removida\n";
            }

            if ($testVagaId) {
                $stmt = $pdo->prepare("DELETE FROM vagas_deficiencias WHERE id_vaga = ?");
                $stmt->execute([$testVagaId]);

                $stmt = $pdo->prepare("DELETE FROM vagas WHERE id_vaga = ?");
                $stmt->execute([$testVagaId]);
                echo "✓ Vaga removida\n";
            }

            if ($testCandidatoId) {
                $stmt = $pdo->prepare("DELETE FROM candidatos_deficiencias WHERE id_candidato = ?");
                $stmt->execute([$testCandidatoId]);

                $stmt = $pdo->prepare("DELETE FROM experiencias_profissionais WHERE id_candidato = ?");
                $stmt->execute([$testCandidatoId]);

                $stmt = $pdo->prepare("DELETE FROM candidatos WHERE id_candidato = ?");
                $stmt->execute([$testCandidatoId]);
                echo "✓ Candidato removido\n";
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
        } catch (Exception $e) {
            echo "✗ Erro ao encerrar WebDriver: " . $e->getMessage() . "\n";
        }
    }
}
