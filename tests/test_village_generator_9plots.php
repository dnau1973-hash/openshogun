<?php
/**
 * Test unitaire : Refonte du domaine rural vers les 9 parcelles procédurales uniques
 * Teste la génération procédurale, les potentiels (20 à 100), les durées progressives,
 * la file de construction non instantanée, la résolution PlanetEngine et le remboursement d'annulation.
 */
require_once __DIR__ . '/../core/VillageGeneratorService.php';
require_once __DIR__ . '/../core/RuralPlotEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/BuildingEngine.php';

echo "=== Test unitaire : 9 Parcelles Rurales Procédurales & File de Chantier 20-100 ===\n";

// 1. Base SQLite en mémoire
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
Database::setConnection($pdo);

$pdo->exec("
    CREATE TABLE game_settings (
        setting_key TEXT PRIMARY KEY,
        setting_value TEXT,
        setting_type TEXT
    );
    CREATE TABLE planets (
        id INTEGER PRIMARY KEY,
        user_id INTEGER DEFAULT 1,
        metal REAL DEFAULT 100000,
        crystal REAL DEFAULT 100000,
        deuterium REAL DEFAULT 100000,
        population INTEGER DEFAULT 100,
        last_resource_update INTEGER DEFAULT 0
    );
    CREATE TABLE construction_queue (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        planet_id INTEGER NOT NULL,
        build_category TEXT NOT NULL,
        target_id TEXT NOT NULL,
        target_level INTEGER NOT NULL,
        started_at INTEGER NOT NULL,
        finishes_at INTEGER NOT NULL
    );
    CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        username TEXT DEFAULT 'daimyo_test',
        email TEXT DEFAULT 'test@daimyo.jp',
        gold_coins INTEGER DEFAULT 100,
        faction TEXT DEFAULT 'terran',
        imperial_seal_until TEXT DEFAULT NULL,
        last_daily_gold TEXT DEFAULT NULL
    );
    CREATE TABLE planet_buildings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        planet_id INTEGER NOT NULL,
        building_type TEXT NOT NULL,
        level INTEGER NOT NULL DEFAULT 1,
        slot INTEGER DEFAULT NULL
    );
    CREATE TABLE imperial_seals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        expires_at INTEGER NOT NULL
    );
    CREATE TABLE shipyard_queue (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        planet_id INTEGER NOT NULL,
        ship_code TEXT NOT NULL,
        count INTEGER NOT NULL,
        unit_build_time INTEGER NOT NULL,
        started_at INTEGER NOT NULL,
        finishes_at INTEGER NOT NULL,
        total_count INTEGER DEFAULT NULL
    );
    CREATE TABLE barracks_queue (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        planet_id INTEGER NOT NULL,
        troop_type TEXT NOT NULL,
        count INTEGER NOT NULL,
        unit_build_time INTEGER NOT NULL,
        started_at INTEGER NOT NULL,
        finishes_at INTEGER NOT NULL,
        total_count INTEGER DEFAULT NULL
    );
    CREATE TABLE craft_queue (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        planet_id INTEGER NOT NULL,
        recipe_code TEXT NOT NULL,
        count INTEGER NOT NULL,
        started_at INTEGER NOT NULL,
        finishes_at INTEGER NOT NULL
    );
    CREATE TABLE planet_ships (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        planet_id INTEGER NOT NULL,
        ship_code TEXT NOT NULL,
        count INTEGER NOT NULL
    );
    CREATE TABLE planet_troops (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        planet_id INTEGER NOT NULL,
        troop_type TEXT NOT NULL,
        count INTEGER NOT NULL
    );
    CREATE TABLE planet_fields (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        planet_id INTEGER NOT NULL,
        field_slot INTEGER NOT NULL,
        type TEXT NOT NULL,
        level INTEGER NOT NULL DEFAULT 0
    );
    CREATE TABLE planet_oases (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        planet_id INTEGER NOT NULL,
        oasis_id INTEGER NOT NULL
    );
    CREATE TABLE oases (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        type TEXT NOT NULL
    );
");
$pdo->exec("INSERT INTO users (id, username) VALUES (1, 'daimyo_test')");
$pdo->exec("INSERT INTO planets (id, user_id, metal, crystal, deuterium, population) VALUES (1, 1, 50000, 50000, 50000, 100)");

for ($i = 1; $i <= 20; $i++) {
    $pdo->exec("INSERT INTO planet_fields (planet_id, field_slot, type, level) VALUES (1, $i, 'metal_mine', 1)");
}

$generator = new VillageGeneratorService($pdo);

// 2. Génération procédurale d'un village
$plots = $generator->generateVillage(1, false);
echo "1. Nombre de parcelles générées : " . count($plots) . " (attendu: 9)\n";
if (count($plots) !== 9) {
    echo "   -> [ECHEC] Mauvais nombre de parcelles générées.\n";
    exit(1);
}
echo "   -> [SUCCES] 9 parcelles uniques générées avec succès !\n";

// 3. Vérification des 9 types indispensables
$expectedTypes = [
    'tenshu', 'foret', 'carriere', 'fosse_argile', 'riziere', 'champ_soja', 'culture_the', 'sanctuaire_shinto', 'village'
];
$generatedTypes = array_column($plots, 'structure_type');
sort($expectedTypes);
sort($generatedTypes);
if ($expectedTypes === $generatedTypes) {
    echo "2. Types de structures : Tous les 9 types indispensables sont présents !\n";
} else {
    echo "   -> [ECHEC] Incohérence dans les types de structures : " . implode(', ', $generatedTypes) . "\n";
    exit(1);
}

// 4. Vérification des potentiels (max_level entre 20 et 100)
$allWithinRange = true;
foreach ($plots as $p) {
    $maxLvl = (int)$p['max_level'];
    if ($maxLvl < 20 || $maxLvl > 100) {
        $allWithinRange = false;
        echo "   -> [ECHEC] Parcelle {$p['structure_type']} a un max_level hors bornes : $maxLvl\n";
    }
}
if ($allWithinRange) {
    echo "3. Potentiels verticaux : Tous les max_level sont compris entre 20 et 100 (Progression profonde validée) !\n";
} else {
    exit(1);
}

// 5. Vérification des formules de coût et production (Niveau 1 à 100)
echo "4. Test de viabilité mathématique des coûts et durées jusqu'au Niveau 100 :\n";
$costsLvl1 = RuralPlotEngine::calculateUpgradeCost('foret', 1);
$costsLvl20 = RuralPlotEngine::calculateUpgradeCost('foret', 20);
$costsLvl50 = RuralPlotEngine::calculateUpgradeCost('foret', 50);
$costsLvl100 = RuralPlotEngine::calculateUpgradeCost('foret', 100);

$durLvl1 = RuralPlotEngine::calculateUpgradeDuration(1, 1);
$durLvl20 = RuralPlotEngine::calculateUpgradeDuration(20, 1);
$durLvl50 = RuralPlotEngine::calculateUpgradeDuration(50, 1);
$durLvl100 = RuralPlotEngine::calculateUpgradeDuration(100, 1);

echo "   - Foret Niv. 1   : Bois = {$costsLvl1['metal']}, Durée = {$durLvl1}s\n";
echo "   - Foret Niv. 20  : Bois = {$costsLvl20['metal']}, Durée = {$durLvl20}s\n";
echo "   - Foret Niv. 50  : Bois = {$costsLvl50['metal']}, Durée = {$durLvl50}s\n";
echo "   - Foret Niv. 100 : Bois = {$costsLvl100['metal']}, Durée = {$durLvl100}s\n";

if ($durLvl1 >= 30 && $durLvl100 > $durLvl20 && $costsLvl100['metal'] > $costsLvl20['metal']) {
    echo "   -> [SUCCES] Formules de coût et de durée progressives (minutes à plusieurs jours) validées !\n";
} else {
    echo "   -> [ECHEC] Anomalie sur les durées ou coûts.\n";
    exit(1);
}

// 6. Test du lancement de chantier non-instantané (File de construction)
$engine = new RuralPlotEngine($pdo);
$initForetLvl = (int)$pdo->query("SELECT level FROM planet_rural_plots WHERE planet_id = 1 AND structure_type = 'foret'")->fetchColumn();
$resUpgrade = $engine->upgradePlot(1, 'foret');

echo "5. Lancement de chantier Forêt (non instantané) : ";
if (!empty($resUpgrade['success']) && !empty($resUpgrade['queue_id'])) {
    echo "[SUCCES] Chantier inséré dans construction_queue (Queue ID #{$resUpgrade['queue_id']}, fin dans {$resUpgrade['duration']}s)\n";
} else {
    echo "[ECHEC] Erreur : " . ($resUpgrade['error'] ?? 'Inconnue') . "\n";
    exit(1);
}

// Vérification que le niveau en base n'a PAS été incrémenté immédiatement
$foretLvlDuringWork = (int)$pdo->query("SELECT level FROM planet_rural_plots WHERE planet_id = 1 AND structure_type = 'foret'")->fetchColumn();
if ($foretLvlDuringWork === $initForetLvl) {
    echo "   -> [SUCCES] Verrouillage strict : Le niveau en base reste à $initForetLvl pendant les travaux (non instantané validé) !\n";
} else {
    echo "   -> [ECHEC] Le niveau s'est incrémenté instantanément : $foretLvlDuringWork !\n";
    exit(1);
}

// 7. Test de résolution via PlanetEngine::processConstructionQueue
// Simuler la fin du chantier dans le passé
$pdo->exec("UPDATE construction_queue SET finishes_at = " . (time() - 10) . " WHERE id = " . (int)$resUpgrade['queue_id']);
$pe = new PlanetEngine($pdo);
$pe->processConstructionQueue(1);

$foretLvlAfter = (int)$pdo->query("SELECT level FROM planet_rural_plots WHERE planet_id = 1 AND structure_type = 'foret'")->fetchColumn();
$remainingInQueue = (int)$pdo->query("SELECT COUNT(*) FROM construction_queue WHERE planet_id = 1")->fetchColumn();
echo "6. Résolution du chantier après échéance : ";
if ($foretLvlAfter === $initForetLvl + 1 && $remainingInQueue === 0) {
    echo "[SUCCES] Niveau Forêt passé à $foretLvlAfter, file purgée avec succès !\n";
} else {
    echo "[ECHEC] Niveau=$foretLvlAfter, items en file=$remainingInQueue\n";
    exit(1);
}

// 8. Test d'annulation d'un chantier rural et remboursement 80%
$initMetal = (float)$pdo->query("SELECT metal FROM planets WHERE id = 1")->fetchColumn();
$resVillageUp = $engine->upgradePlot(1, 'village');
$villageQueueId = (int)$resVillageUp['queue_id'];
$metalAfterLaunch = (float)$pdo->query("SELECT metal FROM planets WHERE id = 1")->fetchColumn();
$villageCost = RuralPlotEngine::calculateUpgradeCost('village', 1);

$be = new BuildingEngine($pdo);
$cancelSuccess = $be->cancelUpgrade(1, $villageQueueId);
$metalAfterCancel = (float)$pdo->query("SELECT metal FROM planets WHERE id = 1")->fetchColumn();
$expectedRefund = (int)($villageCost['metal'] * 0.8);

echo "7. Annulation de chantier et remboursement : ";
if ($cancelSuccess && ($metalAfterCancel - $metalAfterLaunch) === (float)$expectedRefund) {
    echo "[SUCCES] Chantier annulé et 80% des ressources ({$expectedRefund} métal) restituées !\n";
} else {
    echo "[ECHEC] Restitution incorrecte : diff=" . ($metalAfterCancel - $metalAfterLaunch) . " vs attendu=$expectedRefund\n";
    exit(1);
}

// 9. Test de capacité d'habitation synchronisée
// Relancer le village et le faire aboutir
$resVillageFinal = $engine->upgradePlot(1, 'village');
$pdo->exec("UPDATE construction_queue SET finishes_at = " . (time() - 10) . " WHERE id = " . (int)$resVillageFinal['queue_id']);
$pe->processConstructionQueue(1);

$newVillageLvl = (int)$pdo->query("SELECT level FROM planet_rural_plots WHERE planet_id = 1 AND structure_type = 'village'")->fetchColumn();
$expectedCap = 75 + ($newVillageLvl * 25);
$housingCap = $engine->getVillageHousingCapacity(1);
$planetMaxPop = $pe->calculateMaxPopulation([], [], 1);

echo "8. Capacité d'habitation après Village Niv. $newVillageLvl : {$housingCap['total_capacity']} places (attendu: $expectedCap)\n";
if ($housingCap['total_capacity'] === $expectedCap && $planetMaxPop === $expectedCap) {
    echo "   -> [SUCCES] Parfaite synchronisation PlanetEngine <=> RuralPlotEngine !\n";
} else {
    echo "   -> [ECHEC] Désynchronisation : Rural={$housingCap['total_capacity']} vs Planet=$planetMaxPop\n";
    exit(1);
}

// 10. Test de concurrence : Clan Oda (terran) - Double développement simultané (1 rural + 1 urbain)
$pdo->exec("UPDATE planets SET metal = 500000, crystal = 500000, deuterium = 500000 WHERE id = 1");
$pdo->exec("UPDATE users SET faction = 'terran' WHERE id = 1");
$pdo->exec("DELETE FROM construction_queue WHERE planet_id = 1");

$resBuildUrbain = $be->startUpgrade(1, 'building', 'hq');
$resBuildRural = $engine->upgradePlot(1, 'carriere');

echo "9. Double développement simultané Clan Oda (Urbain + Rural) : ";
if (!empty($resBuildUrbain['success']) && !empty($resBuildRural['success'])) {
    $qCount = (int)$pdo->query("SELECT COUNT(*) FROM construction_queue WHERE planet_id = 1")->fetchColumn();
    if ($qCount === 2) {
        echo "[SUCCES] 1 bâtiment urbain et 1 parcelle rurale en chantier simultané validés (Queue count = 2) !\n";
    } else {
        echo "[ECHEC] Queue count = $qCount\n";
        exit(1);
    }
} else {
    echo "[ECHEC] Erreur : Urbain=" . json_encode($resBuildUrbain) . " | Rural=" . json_encode($resBuildRural) . "\n";
    exit(1);
}

// Tenter un 2e rural pour Oda sans Sceau -> doit être refusé
$resSecondRural = $engine->upgradePlot(1, 'riziere');
if (!$resSecondRural['success']) {
    echo "   -> [SUCCES] Blocage du 2e chantier rural sans Sceau respecté : {$resSecondRural['error']}\n";
} else {
    echo "   -> [ECHEC] Le 2e chantier rural a été autorisé sans Sceau !\n";
    exit(1);
}

// 11. Test de concurrence : Autre clan (vorash / Takeda) sans Sceau Impérial
// Doit refuser tout chantier simultané (1 seul au total)
$pdo->exec("DELETE FROM construction_queue WHERE planet_id = 1");
$pdo->exec("UPDATE users SET faction = 'vorash' WHERE id = 1");

$resVorashUrbain = $be->startUpgrade(1, 'building', 'storage');
$resVorashRural = $engine->upgradePlot(1, 'foret');

echo "10. Monopole de chantier pour Clan non-Oda (Takeda/Tokugawa sans Sceau) : ";
if (!empty($resVorashUrbain['success']) && empty($resVorashRural['success'])) {
    echo "[SUCCES] 1er chantier autorisé, 2e chantier simultané bloqué avec succès : {$resVorashRural['error']}\n";
} else {
    echo "[ECHEC] Résultat inattendu : Urbain=" . json_encode($resVorashUrbain) . " | Rural=" . json_encode($resVorashRural) . "\n";
    exit(1);
}

echo "\n[TOUS LES TESTS DU DOMAINE RURAL & FILE DE CHANTIER SONT VALIDES]\n";
