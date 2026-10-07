<?php
/**
 * Test unitaire : Dissociation des bâtiments urbains/champs ruraux de la capacité d'habitation
 */
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/TerroirEngine.php';

echo "=== Test de dissociation de la capacité d'habitation ===\n";

$buildings = [
    'hq' => 20,
    'storage' => 15,
    'barracks' => 10,
    'shipyard' => 8,
    'wall' => 12
];
$fields = [
    ['level' => 10],
    ['level' => 15],
    ['level' => 8]
];

// Base SQLite en mémoire
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("
    CREATE TABLE planet_terroir_slots (
        planet_id INTEGER,
        resource_type TEXT,
        slot_index INTEGER,
        level INTEGER,
        workers_assigned INTEGER,
        PRIMARY KEY (planet_id, resource_type, slot_index)
    )
");

$pe = new PlanetEngine($pdo);

// 1. Test sans planetId (doit renvoyer le défaut 100 et ignorer les bâtiments/champs)
$capWithoutPlanet = $pe->calculateMaxPopulation($buildings, $fields);
echo "1. Capacité par défaut (bâtiments Niv.20+ mais sans planetId) : $capWithoutPlanet (attendu: 100)\n";
if ($capWithoutPlanet === 100) {
    echo "   -> [SUCCES] Les bâtiments et champs ne gonflent plus la capacité !\n";
} else {
    echo "   -> [ECHEC] Valeur inattendue : $capWithoutPlanet\n";
    exit(1);
}

// 2. Cas A : Aucune parcelle enregistrée en base (doit valoir 5 parcelles niveau 1 -> 75 + 25 = 100)
$capEmptyDb = $pe->calculateMaxPopulation($buildings, $fields, 42);
echo "2. Capacité planète sans parcelles enregistrées : $capEmptyDb (attendu: 100)\n";
if ($capEmptyDb === 100) {
    echo "   -> [SUCCES] 5 parcelles implicites niveau 1 => 100\n";
} else {
    echo "   -> [ECHEC] Valeur inattendue : $capEmptyDb\n";
    exit(1);
}

// 3. Cas B : 2 parcelles améliorées (Slot 1 -> Niv. 3, Slot 2 -> Niv. 2, Slots 3..5 restent Niv. 1)
// Total niveaux = 3 + 2 + 1 + 1 + 1 = 8 => Capacité = 75 + 8 * 5 = 115
$pdo->exec("INSERT INTO planet_terroir_slots VALUES (42, 'housing', 1, 3, 6)");
$pdo->exec("INSERT INTO planet_terroir_slots VALUES (42, 'housing', 2, 2, 4)");

$capUpgraded = $pe->calculateMaxPopulation($buildings, $fields, 42);
echo "3. Capacité après amélioration parcelles d'habitation : $capUpgraded (attendu: 115)\n";
if ($capUpgraded === 115) {
    echo "   -> [SUCCES] Formule 75 + (8 × 5) = 115 validée !\n";
} else {
    echo "   -> [ECHEC] Valeur inattendue : $capUpgraded\n";
    exit(1);
}

// 4. Vérification de cohérence exacte avec TerroirEngine
$te = new TerroirEngine($pdo, $pe);
$teCap = $te->getHousingCapacity(42);
echo "4. Capacité calculée par TerroirEngine : {$teCap['total_capacity']} (attendu: 115)\n";
if ($teCap['total_capacity'] === $capUpgraded) {
    echo "   -> [SUCCES] Parfaite synchronisation PlanetEngine <=> TerroirEngine !\n";
} else {
    echo "   -> [ECHEC] Désynchronisation entre moteurs\n";
    exit(1);
}

echo "\n[TOUS LES TESTS SONT VALIDES]\n";
