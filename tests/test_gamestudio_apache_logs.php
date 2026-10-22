<?php
/**
 * Test : Intégration de la visualisation des logs Apache dans le Game Studio
 * Fichier : tests/test_gamestudio_apache_logs.php
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
echo " TEST : VISUALISATION DU LOG D'ERREUR APACHE DANS GAME STUDIO GÉNÉRAL" . PHP_EOL;
echo "========================================================================" . PHP_EOL . PHP_EOL;

// 1. Service ApacheLogService
echo "[TEST 1] Service ApacheLogService..." . PHP_EOL;
$service = new ApacheLogService();
$logPath = $service->detectApacheErrorLogPath();
assertTest(!empty($logPath), "Chemin détecté automatiquement : {$logPath}");
assertTest(file_exists($logPath), "Le fichier log Apache existe bien");

$data = $service->getLogViewData(['lines' => 20]);
assertTest($data['logExists'] === true, "logExists est à true");
assertTest(!empty($data['logSizeFmt']), "Taille formatée présente : {$data['logSizeFmt']}");
assertTest(is_array($data['parsedEntries']), "parsedEntries est un tableau");
assertTest(isset($data['stats']['total_parsed']), "Statistiques de log calculées");
assertTest(!empty($data['availableLogs']), "Logs frères détectés : " . count($data['availableLogs']));

// 2. Parsing de logs
echo PHP_EOL . "[TEST 2] Parsing des formats de log Apache 2.4 & PHP..." . PHP_EOL;
$apacheSample = "[Fri Feb 27 19:04:32.069840 2026] [mpm_winnt:notice] [pid 31032:tid 464] AH00455: Apache/2.4.65 configured";
$parsed = $service->parseLogLine($apacheSample);
assertTest($parsed['level'] === 'notice', "Niveau extrait : {$parsed['level']}");
assertTest($parsed['module'] === 'mpm_winnt', "Module extrait : {$parsed['module']}");
assertTest($parsed['code'] === 'AH00455', "Code AH extrait : {$parsed['code']}");
assertTest(str_contains($parsed['message'], 'configured'), "Message extrait correctement");

$phpSample = "[22-Oct-2026 21:04:12 UTC] PHP Fatal error: Uncaught Exception in index.php";
$parsedPhp = $service->parseLogLine($phpSample);
assertTest($parsedPhp['level'] === 'crit', "PHP Fatal converti en niveau critique");
assertTest($parsedPhp['module'] === 'php', "Module PHP détecté");

// 3. Présence des éléments dans views/gamestudio.php
echo PHP_EOL . "[TEST 3] Déclaration du sous-onglet dans views/gamestudio.php..." . PHP_EOL;
$gamestudioMain = file_get_contents(__DIR__ . '/../views/gamestudio.php');
assertTest(str_contains($gamestudioMain, "'apache_logs'"), "apache_logs autorisé dans allowedSubTabs");
assertTest(str_contains($gamestudioMain, "subtab=apache_logs"), "Lien subtab=apache_logs présent dans le dropdown Général");
assertTest(str_contains($gamestudioMain, "core/ApacheLogService.php"), "Chargement de ApacheLogService dans le contrôleur");

// 4. Présence des éléments dans views/gamestudio/general.php
echo PHP_EOL . "[TEST 4] Déclaration de l'onglet dans views/gamestudio/general.php..." . PHP_EOL;
$generalView = file_get_contents(__DIR__ . '/../views/gamestudio/general.php');
assertTest(str_contains($generalView, "'apache_logs'"), "apache_logs autorisé dans general.php");
assertTest(str_contains($generalView, "Logs d'Erreur Apache"), "Onglet 'Logs d'Erreur Apache' présent dans la barre de navigation");
assertTest(str_contains($generalView, "general_apache_logs.php"), "Inclusion de general_apache_logs.php présente");

// 5. Rendu de la vue partielle general_apache_logs.php
echo PHP_EOL . "[TEST 5] Test de rendu de la vue partielle general_apache_logs.php..." . PHP_EOL;
extract($data);
ob_start();
include __DIR__ . '/../views/gamestudio/general_apache_logs.php';
$output = ob_get_clean();
assertTest(!empty($output), "Vue general_apache_logs.php rendue avec succès (" . strlen($output) . " octets)");
assertTest(str_contains($output, "Journal des Erreurs Apache"), "Titre présent dans le HTML rendu");
assertTest(str_contains($output, "apacheLogsTable"), "Tableau des logs présent dans le HTML rendu");

echo PHP_EOL . "========================================================================" . PHP_EOL;
echo " TOUS LES TESTS D'INTÉGRATION DU LOG APACHE SONT 100% VALIDES !" . PHP_EOL;
echo "========================================================================" . PHP_EOL;
