<?php
/**
 * Test unitaire : Refonte du domaine rural vers les 9 parcelles procédurales uniques
 * Teste la génération procédurale, les potentiels (20 à 100), les formules de progression et l'élévation.
 */
require_once __DIR__ . '/../core/VillageGeneratorService.php';
require_once __DIR__ . '/../core/RuralPlotEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';

echo "=== Test unitaire : 9 Parcelles Rurales Procédurales & Progression 20-100 ===\n";

// 1. Base SQLite en mémoire
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("
    CREATE TABLE planets (
        id INTEGER PRIMARY KEY,
        metal REAL DEFAULT 100000,
        crystal REAL DEFAULT 100000,
        deuterium REAL DEFAULT 100000,
        population INTEGER DEFAULT 100,
        population_max INTEGER DEFAULT 100
    );
");
$pdo->exec("INSERT INTO planets (id, metal, crystal, deuterium, population, population_max) VALUES (1, 50000, 50000, 50000, 100, 100)");

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
echo "4. Test de viabilité mathématique des coûts jusqu'au Niveau 100 :\n";
$costsLvl1 = RuralPlotEngine::calculateUpgradeCost('foret', 1);
$costsLvl20 = RuralPlotEngine::calculateUpgradeCost('foret', 20);
$costsLvl50 = RuralPlotEngine::calculateUpgradeCost('foret', 50);
$costsLvl100 = RuralPlotEngine::calculateUpgradeCost('foret', 100);

echo "   - Foret Niv. 1   : Bois = {$costsLvl1['metal']}, Pierre = {$costsLvl1['crystal']}\n";
echo "   - Foret Niv. 20  : Bois = {$costsLvl20['metal']}, Pierre = {$costsLvl20['crystal']}\n";
echo "   - Foret Niv. 50  : Bois = {$costsLvl50['metal']}, Pierre = {$costsLvl50['crystal']}\n";
echo "   - Foret Niv. 100 : Bois = {$costsLvl100['metal']}, Pierre = {$costsLvl100['crystal']}\n";

if ($costsLvl1['metal'] > 0 && $costsLvl100['metal'] > $costsLvl20['metal'] && $costsLvl100['metal'] < 100000000) {
    echo "   -> [SUCCES] Formule de coût viable, progressive et sans dépassement d'entier !\n";
} else {
    echo "   -> [ECHEC] Anomalie sur les coûts.\n";
    exit(1);
}

// 6. Test d'élévation d'une parcelle via RuralPlotEngine
$engine = new RuralPlotEngine($pdo);
$resUpgrade = $engine->upgradePlot(1, 'foret');
echo "5. Élévation de la forêt : ";
if (!empty($resUpgrade['success']) && $resUpgrade['new_level'] === 2) {
    echo "[SUCCES] Niveau 1 -> Niveau 2 validé ({$resUpgrade['message']})\n";
} else {
    echo "[ECHEC] Erreur : " . ($resUpgrade['error'] ?? 'Inconnue') . "\n";
    exit(1);
}

// 7. Test de la capacité d'habitation du village (75 + L * 25)
$resVillageUp = $engine->upgradePlot(1, 'village');
$housingCap = $engine->getVillageHousingCapacity(1);
echo "6. Capacité d'habitation après élévation du Village au Niveau 2 : {$housingCap['total_capacity']} places (attendu: 75 + 2*25 = 125)\n";
if ($housingCap['total_capacity'] === 125) {
    echo "   -> [SUCCES] Formule d'habitation 75 + (L * 25) validée !\n";
} else {
    echo "   -> [ECHEC] Incohérence capacité : {$housingCap['total_capacity']}\n";
    exit(1);
}

// 8. Test de cohérence avec PlanetEngine
$pe = new PlanetEngine($pdo);
$planetMaxPop = $pe->calculateMaxPopulation([], [], 1);
echo "7. Capacité calculée par PlanetEngine : $planetMaxPop (attendu: 125)\n";
if ($planetMaxPop === 125) {
    echo "   -> [SUCCES] Parfaite synchronisation PlanetEngine <=> RuralPlotEngine !\n";
} else {
    echo "   -> [ECHEC] Désynchronisation : $planetMaxPop\n";
    exit(1);
}

echo "\n[TOUS LES TESTS DU DOMAINE RURAL A 9 PARCELLES SONT VALIDES]\n";
