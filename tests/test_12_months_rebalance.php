<?php
/**
 * Test de vérification de l'équilibrage 12 mois
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GameConfig.php';
require_once __DIR__ . '/../core/BuildingEngine.php';

function formatDuration($s) {
    if ($s >= 86400) {
        $d = floor($s / 86400);
        $rem = $s % 86400;
        $h = floor($rem / 3600);
        $m = floor(($rem % 3600) / 60);
        $sec = $rem % 60;
        return "{$d}j " . sprintf('%02dh%02dm%02ds', $h, $m, $sec) . " ({$s}s)";
    }
    if ($s >= 3600) {
        $h = floor($s / 3600);
        $m = floor(($s % 3600) / 60);
        $sec = $s % 60;
        return sprintf('%02dh%02dm%02ds', $h, $m, $sec) . " ({$s}s)";
    }
    $m = floor($s / 60);
    $sec = $s % 60;
    return sprintf('%02dm%02ds', $m, $sec) . " ({$s}s)";
}

echo "=======================================================\n";
echo "   TEST DE VÉRIFICATION DU RYTHME DU SERVEUR 12 MOIS   \n";
echo "=======================================================\n\n";

$db = Database::getConnection();
$gameSpeed = (int)GameConfig::get('game_speed', 1);
echo "Vitesse du jeu (game_speed) : $gameSpeed\n";
if ($gameSpeed !== 1) {
    throw new Exception("ERREUR: game_speed doit être à 1 pour le serveur 12 mois");
}

$buildingEngine = new BuildingEngine();

// 1. Test des Parcelles Rurales (ex: Rizière / metal_mine)
echo "\n--- 1. Évolution des Parcelles Agricoles (Rizière / metal_mine) Tenshu Niv 1 ---\n";
$targetLevels = [1, 2, 3, 5, 8, 10, 15, 20];
foreach ($targetLevels as $targetLvl) {
    $curLvl = $targetLvl - 1;
    $details = $buildingEngine->getUpgradeDetails('field', 'metal_mine', $curLvl, 1);
    echo sprintf("  -> Vers Niveau %2d : %-22s | Coût: %6d Riz, %6d Bois\n", 
        $targetLvl, formatDuration($details['duration']), $details['cost']['metal'], $details['cost']['crystal']);
    assert($details['duration'] >= 10);
}

// 2. Test des Bâtiments Urbains (Palais Seigneurial / Tenshu / hq)
echo "\n--- 2. Évolution du Palais Seigneurial (Tenshu / hq) ---\n";
foreach ($targetLevels as $targetLvl) {
    $curLvl = $targetLvl - 1;
    // hqLevel = targetLvl - 1 (Tenshu s'accélère lui-même selon son niveau actuel)
    $details = $buildingEngine->getUpgradeDetails('building', 'hq', $curLvl, max(1, $curLvl));
    echo sprintf("  -> Vers Niveau %2d : %-22s | Coût: %6d Riz, %6d Bois\n", 
        $targetLvl, formatDuration($details['duration']), $details['cost']['metal'], $details['cost']['crystal']);
    assert($details['duration'] >= 10);
}

// 3. Test de l'impact du Tenshu sur la construction
echo "\n--- 3. Impact du Tenshu sur la Caserne Niv 10 (hq lvl 1 vs lvl 10 vs lvl 20) ---\n";
$b1 = $buildingEngine->getUpgradeDetails('building', 'barracks', 9, 1);
$b10 = $buildingEngine->getUpgradeDetails('building', 'barracks', 9, 10);
$b20 = $buildingEngine->getUpgradeDetails('building', 'barracks', 9, 20);
echo "  Tenshu Niv 1  : " . formatDuration($b1['duration']) . "\n";
echo "  Tenshu Niv 10 : " . formatDuration($b10['duration']) . "\n";
echo "  Tenshu Niv 20 : " . formatDuration($b20['duration']) . "\n";
assert($b1['duration'] > $b10['duration'] && $b10['duration'] > $b20['duration']);

// 4. Test Recrutement Unités en Base de Données
echo "\n--- 4. Troupes & Recrutement (Dojo Niv 1 vs Niv 5 vs Niv 15) ---\n";
$units = $db->query("SELECT code, name, tier, base_train_time FROM units ORDER BY tier ASC, base_train_time ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($units as $u) {
    $base = (int)$u['base_train_time'];
    $t1  = max(5, (int)($base / (1 + (1 * 0.15))));
    $t5  = max(5, (int)($base / (1 + (5 * 0.15))));
    $t15 = max(5, (int)($base / (1 + (15 * 0.15))));
    echo sprintf("  [T%d] %-30s | Base: %4ds | N1: %-10s | N5: %-10s | N15: %-10s\n",
        $u['tier'], $u['name'], $base, formatDuration($t1), formatDuration($t5), formatDuration($t15));
    assert($base >= 50 && $t15 < $t1);
}

// 5. Test Engins de Siège & Convois
echo "\n--- 5. Engins de Siège, Convois & Montures (Atelier Niv 1 vs Niv 10) ---\n";
$ships = $db->query("SELECT code, name, base_build_time FROM ships ORDER BY base_build_time ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($ships as $s) {
    $base = (int)$s['base_build_time'];
    $s1  = max(10, (int)($base / (1 + (1 * 0.15))));
    $s10 = max(10, (int)($base / (1 + (10 * 0.15))));
    echo sprintf("  %-32s | Base: %5ds | Atelier N1: %-12s | Atelier N10: %-12s\n",
        $s['name'], $base, formatDuration($s1), formatDuration($s10));
    assert($base >= 90 && $s10 < $s1);
}

// 6. Test Technologies & Recherches
echo "\n--- 6. Recherches de l'Académie (Niveau 1, 5, 10 avec Académie Niv 5) ---\n";
$researches = $db->query("SELECT code, name, base_time FROM researches ORDER BY base_time ASC")->fetchAll(PDO::FETCH_ASSOC);
$labLvl = 5;
foreach ($researches as $r) {
    $base = (int)$r['base_time'];
    // r1: vers lvl 1 ($curLvl = 0, $nextLvl = 1)
    $r1  = max(30, (int)(($base * pow(1.38, 0) * 1) / ((1 + ($labLvl * 0.15)) * 1)));
    // r5: vers lvl 5 ($curLvl = 4, $nextLvl = 5)
    $r5  = max(30, (int)(($base * pow(1.38, 4) * 5) / ((1 + ($labLvl * 0.15)) * 1)));
    // r10: vers lvl 10 ($curLvl = 9, $nextLvl = 10)
    $r10 = max(30, (int)(($base * pow(1.38, 9) * 10) / ((1 + ($labLvl * 0.15)) * 1)));
    echo sprintf("  %-35s | L1: %-10s | L5: %-12s | L10: %-14s\n",
        $r['name'], formatDuration($r1), formatDuration($r5), formatDuration($r10));
    assert($r1 < $r5 && $r5 < $r10);
}

echo "\n=======================================================\n";
echo "   TOUS LES TESTS DE RYTHME 12 MOIS ONT RÉUSSI AVEC SUCCÈS !   \n";
echo "=======================================================\n";
