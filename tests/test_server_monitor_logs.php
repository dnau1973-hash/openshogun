<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/ServerMonitorEngine.php';

echo "=== TEST DU MOTEUR DE SUPERVISION & LOGS MATÉRIELS ===\n\n";

$engine = new ServerMonitorEngine();

// 1. Diagnostic complet
$diag = $engine->getSystemDiagnostics();
assert(isset($diag['network']), "Clé network présente");
assert(isset($diag['network']['rx_speed_kbps']), "rx_speed_kbps présent");
assert(isset($diag['network']['tx_speed_kbps']), "tx_speed_kbps présent");
assert(isset($diag['network']['primary_iface']), "primary_iface présent");
assert(isset($diag['hardware_summary']), "hardware_summary présent");
echo "✅ Test 1 : Diagnostic complet validé (Réseau: " . $diag['network']['primary_iface'] . " | Débit: " . $diag['network']['rx_speed_kbps'] . " Ko/s)\n";

// 2. Enregistrement d'instantanés
$log1 = $engine->recordHardwareLog(true, 0);
assert(isset($log1['cpu_load_1m']), "cpu_load_1m présent dans log");
assert(isset($log1['ram_percent']), "ram_percent présent dans log");
assert(isset($log1['net_rx_mb']), "net_rx_mb présent dans log");
echo "✅ Test 2 : Instantané enregistré avec succès (ID: {$log1['id']}, Temp: {$log1['cpu_temp']}°C, RAM: {$log1['ram_percent']}%)\n";

// 3. Récupération des logs
$logs = $engine->getHardwareLogs(10);
assert(is_array($logs) && count($logs) >= 1, "Logs récupérés sous forme de liste");
echo "✅ Test 3 : Récupération des logs validée (" . count($logs) . " relevés)\n";

// 4. Synthèse statistique des pics
$summary = $engine->getHardwareLogSummary();
assert(isset($summary['peak_temp']), "peak_temp calculé");
assert(isset($summary['peak_load']), "peak_load calculé");
assert(isset($summary['peak_ram']), "peak_ram calculé");
echo "✅ Test 4 : Synthèse des pics validée (Pic Temp: {$summary['peak_temp']}°C, Pic Load: {$summary['peak_load']}, Pic RAM: {$summary['peak_ram']}%)\n";

// 5. Export CSV
$csv = $engine->exportLogsCsv();
assert(str_contains($csv, 'Température CPU'), "En-tête CSV valide");
assert(str_contains($csv, ';'), "Délimiteur point-virgule présent");
echo "✅ Test 5 : Export CSV validé (" . strlen($csv) . " octets)\n";

echo "\n🎉 TOUS LES TESTS DE SUPERVISION ET DE LOGS SONT AU VERT !\n";

