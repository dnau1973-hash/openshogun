<?php
/**
 * Test : Service de Diagnostic et Visualisation des Logs d'Erreur Apache
 * Fichier : tests/test_apache_logs.php
 */

require_once __DIR__ . '/../core/ApacheLogService.php';

function assertTest(bool $condition, string $message): void {
    if ($condition) {
        echo "  [PASS] {$message}\n";
    } else {
        echo "  [FAIL] {$message}\n";
        exit(1);
    }
}

echo "\n========================================================================" . PHP_EOL;
echo " TEST : SERVICE DE DIAGNOSTIC DES LOGS APACHE (Game Studio)" . PHP_EOL;
echo "========================================================================" . PHP_EOL . PHP_EOL;

$service = new ApacheLogService();

echo "[TEST 1] Détection du chemin du fichier log Apache..." . PHP_EOL;
$detectedPath = $service->detectApacheErrorLogPath();
assertTest(!empty($detectedPath), "Chemin détecté : {$detectedPath}");
assertTest(file_exists($detectedPath), "Le fichier log existe bien sur le système");

echo PHP_EOL . "[TEST 2] Lecture des logs et parsing..." . PHP_EOL;
$data = $service->getLogViewData(['lines' => 10]);
assertTest($data['logExists'] === true, "Log marqué comme existant dans getLogViewData");
assertTest($data['logSize'] > 0, "Taille du fichier calculée : {$data['logSizeFmt']}");
assertTest(count($data['parsedEntries']) > 0, "Lignes de logs lues et parsées : " . count($data['parsedEntries']));

echo PHP_EOL . "[TEST 3] Vérification de la structure des entrées..." . PHP_EOL;
$sample = $data['parsedEntries'][0];
assertTest(isset($sample['raw']), "Entrée possède le champ 'raw'");
assertTest(isset($sample['level']), "Entrée possède le champ 'level' : {$sample['level']}");
assertTest(isset($sample['badge_class']), "Entrée possède une classe de badge Tabler : {$sample['badge_class']}");
assertTest(isset($sample['message']), "Entrée possède un message : " . substr($sample['message'], 0, 60) . "...");

echo PHP_EOL . "[TEST 4] Détection des logs frères (ex: php_error.log, access.log)..." . PHP_EOL;
$availableLogs = $service->getAvailableLogFiles($detectedPath);
assertTest(count($availableLogs) >= 1, "Nombre de fichiers logs trouvés : " . count($availableLogs));
$logNames = array_column($availableLogs, 'filename');
echo "  [INFO] Fichiers logs détectés : " . implode(', ', $logNames) . PHP_EOL;

echo PHP_EOL . "========================================================================" . PHP_EOL;
echo " TOUS LES TESTS DU SERVICE DE LOG APACHE SONT 100% VALIDES !" . PHP_EOL;
echo "========================================================================" . PHP_EOL;
