<?php
/**
 * Script para Executar Todos os Testes E2E
 *
 * Este script executa todos os testes E2E em sequência e agrega os resultados.
 * Gera um relatório final com estatísticas de sucesso/falha.
 *
 * PRÉ-REQUISITOS:
 * - ChromeDriver rodando em http://127.0.0.1:9515
 * - Frontend rodando em http://localhost:3074
 * - Backend API disponível
 * - Banco de dados 'proapoio' acessível
 *
 * COMO RODAR:
 * php tests/run_all.php
 *
 * OPÇÕES:
 * php tests/run_all.php --verbose     # Mostrar output completo de cada teste
 * php tests/run_all.php --stop-on-failure  # Parar na primeira falha
 */

// Configurações
$testsDir = __DIR__;
$verbose = in_array('--verbose', $argv);
$stopOnFailure = in_array('--stop-on-failure', $argv);

// Lista de testes na ordem de execução
$tests = [
    '01_LoginInstituicao.php',
    '02_CadastroVaga.php',
    '03_CadastroCandidato.php',
    '04_VerCandidatosFiltros.php',
    '05_FazerProposta.php',
    '06_Acessibilidade_TabOrder_e_ARIA.php',
    '07_RegisterInstituicao.php',
    '08_LoginCandidato.php',
    '09_BuscarVagasPublico.php',
    '10_SalvarVaga.php',
    '11_CandidatarVaga.php',
    '12_EditarVaga.php',
    '13_ExcluirVaga.php',
    '14_EditarPerfilCandidato.php',
    '15_EditarPerfilInstituicao.php',
    '16_MinhasPropostasCandidato.php',
    '17_Logout.php',
    '18_Dashboard.php',
    '19_PerfilPublicoInstituicao.php',
];

// Estatísticas
$stats = [
    'total' => count($tests),
    'passed' => 0,
    'failed' => 0,
    'duration' => 0,
];

$results = [];

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║      SUITE DE TESTES E2E - ProApoio                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "Iniciando execução de " . count($tests) . " teste(s)...\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

$suiteStartTime = microtime(true);

foreach ($tests as $index => $testFile) {
    $testNumber = $index + 1;
    $testPath = $testsDir . DIRECTORY_SEPARATOR . $testFile;

    if (!file_exists($testPath)) {
        echo "[$testNumber/" . count($tests) . "] ✗ ERRO: Arquivo não encontrado: $testFile\n";
        $stats['failed']++;
        $results[] = [
            'test' => $testFile,
            'status' => 'ERROR',
            'message' => 'Arquivo não encontrado',
            'duration' => 0,
        ];
        continue;
    }

    echo "[$testNumber/" . count($tests) . "] Executando: $testFile\n";
    echo str_repeat('-', 70) . "\n";

    $startTime = microtime(true);

    // Executar teste
    $output = [];
    $returnCode = 0;

    exec("php " . escapeshellarg($testPath) . " 2>&1", $output, $returnCode);

    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);

    // Processar resultado
    if ($returnCode === 0) {
        $status = 'PASSED';
        $symbol = '✓';
        $stats['passed']++;
    } else {
        $status = 'FAILED';
        $symbol = '✗';
        $stats['failed']++;
    }

    $results[] = [
        'test' => $testFile,
        'status' => $status,
        'output' => $output,
        'duration' => $duration,
        'return_code' => $returnCode,
    ];

    // Mostrar resultado
    if ($verbose || $status === 'FAILED') {
        // Mostrar output completo
        foreach ($output as $line) {
            echo "  $line\n";
        }
    } else {
        // Mostrar apenas última linha (geralmente o resumo)
        if (count($output) > 0) {
            $lastLine = end($output);
            echo "  $lastLine\n";
        }
    }

    echo "\n$symbol Teste $status em {$duration}s\n\n";

    // Parar na primeira falha se solicitado
    if ($stopOnFailure && $status === 'FAILED') {
        echo "⚠ Parando execução devido a falha (--stop-on-failure ativado)\n\n";
        break;
    }

    // Pequena pausa entre testes para garantir limpeza
    if ($index < count($tests) - 1) {
        sleep(2);
    }
}

$suiteEndTime = microtime(true);
$stats['duration'] = round($suiteEndTime - $suiteStartTime, 2);

// Relatório Final
echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                      RELATÓRIO FINAL                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "Resumo da Execução:\n";
echo "  Total de testes:     {$stats['total']}\n";
echo "  ✓ Passou:            {$stats['passed']}\n";
echo "  ✗ Falhou:            {$stats['failed']}\n";
echo "  Tempo total:         {$stats['duration']}s\n\n";

// Tabela de resultados
echo "Detalhes por Teste:\n";
echo str_repeat('=', 70) . "\n";
printf("%-40s %-10s %-10s\n", "Teste", "Status", "Tempo");
echo str_repeat('=', 70) . "\n";

foreach ($results as $result) {
    $statusSymbol = $result['status'] === 'PASSED' ? '✓' : '✗';
    printf("%-40s %s %-9s %6.2fs\n",
        substr($result['test'], 0, 39),
        $statusSymbol,
        $result['status'],
        $result['duration']
    );
}

echo str_repeat('=', 70) . "\n\n";

// Análise de Screenshots (se houver falhas)
if ($stats['failed'] > 0) {
    echo "⚠ Testes falharam. Verifique os screenshots em:\n";
    echo "  tests/_output/screenshots/\n\n";

    $screenshotsDir = $testsDir . DIRECTORY_SEPARATOR . '_output' . DIRECTORY_SEPARATOR . 'screenshots';
    if (is_dir($screenshotsDir)) {
        $screenshots = glob($screenshotsDir . DIRECTORY_SEPARATOR . '*.png');
        if (count($screenshots) > 0) {
            echo "  Screenshots encontrados:\n";
            foreach ($screenshots as $screenshot) {
                echo "    - " . basename($screenshot) . "\n";
            }
            echo "\n";
        }
    }
}

// Salvar relatório em arquivo
$reportPath = $testsDir . DIRECTORY_SEPARATOR . '_output' . DIRECTORY_SEPARATOR . 'report_' . date('Y-m-d_H-i-s') . '.txt';
$reportContent = "RELATÓRIO DE TESTES E2E - ProApoio\n";
$reportContent .= "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";
$reportContent .= "Resumo:\n";
$reportContent .= "  Total:   {$stats['total']}\n";
$reportContent .= "  Passou:  {$stats['passed']}\n";
$reportContent .= "  Falhou:  {$stats['failed']}\n";
$reportContent .= "  Duração: {$stats['duration']}s\n\n";
$reportContent .= "Detalhes:\n";

foreach ($results as $result) {
    $reportContent .= "\n" . str_repeat('-', 70) . "\n";
    $reportContent .= "Teste: {$result['test']}\n";
    $reportContent .= "Status: {$result['status']}\n";
    $reportContent .= "Duração: {$result['duration']}s\n";

    if ($result['status'] === 'FAILED' && isset($result['output'])) {
        $reportContent .= "\nOutput:\n";
        $reportContent .= implode("\n", $result['output']) . "\n";
    }
}

file_put_contents($reportPath, $reportContent);
echo "✓ Relatório salvo em: $reportPath\n\n";

// Status de saída
if ($stats['failed'] > 0) {
    echo "❌ Alguns testes falharam. Verifique os detalhes acima.\n";
    exit(1);
} else {
    echo "✅ Todos os testes passaram com sucesso!\n";
    exit(0);
}
