<?php
/**
 * Test unitaire : Consommation et Production de Ferveur Divine (Sérénité Shinto)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/RuralPlotEngine.php';
require_once __DIR__ . '/../core/VillageGeneratorService.php';

// Création d'une base SQLite en mémoire
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sqlite->exec("CREATE TABLE IF NOT EXISTS game_settings (setting_key VARCHAR(50), setting_value VARCHAR(100), setting_type VARCHAR(20))");
$sqlite->exec("CREATE TABLE IF NOT EXISTS planets (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    name TEXT,
    coord_x INTEGER,
    coord_y INTEGER,
    planet_type TEXT,
    metal REAL DEFAULT 1000,
    crystal REAL DEFAULT 1000,
    deuterium REAL DEFAULT 1000,
    clay REAL DEFAULT 0,
    tea REAL DEFAULT 0,
    soybean REAL DEFAULT 0,
    population INTEGER DEFAULT 100,
    contentment INTEGER DEFAULT 100,
    energy_used INTEGER DEFAULT 0,
    energy_max INTEGER DEFAULT 20,
    last_resource_update INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS planet_rural_plots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER NOT NULL,
    slot_id INTEGER NOT NULL,
    structure_type TEXT NOT NULL,
    level INTEGER NOT NULL DEFAULT 1,
    max_level INTEGER NOT NULL DEFAULT 30,
    pos_x REAL NOT NULL DEFAULT 50.00,
    pos_y REAL NOT NULL DEFAULT 50.00,
    workers_assigned INTEGER NOT NULL DEFAULT 2,
    prod_hourly REAL NOT NULL DEFAULT 0.00
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS planet_buildings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER NOT NULL,
    building_type TEXT NOT NULL,
    level INTEGER NOT NULL DEFAULT 0,
    slot INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS planet_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER NOT NULL,
    field_slot INTEGER NOT NULL,
    field_type TEXT NOT NULL,
    level INTEGER NOT NULL DEFAULT 0
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS construction_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER NOT NULL,
    target_id INTEGER NOT NULL,
    target_level INTEGER NOT NULL,
    build_category TEXT NOT NULL,
    started_at INTEGER NOT NULL,
    finishes_at INTEGER NOT NULL
)");

Database::setConnection($sqlite);

echo "=== VÉRIFICATION DU MOTEUR DE FERVEUR DIVINE (SHINTO) ===\n\n";

// 1. Test des taux de consommation unitaire
assert(VillageGeneratorService::calculateHourlyEnergyConsumption('riziere', 1) === 6, "Rizière niv 1 doit consommer 6");
assert(VillageGeneratorService::calculateHourlyEnergyConsumption('carriere', 1) === 5, "Carrière niv 1 doit consommer 5");
assert(VillageGeneratorService::calculateHourlyEnergyConsumption('foret', 1) === 4, "Forêt niv 1 doit consommer 4");
assert(VillageGeneratorService::calculateHourlyEnergyConsumption('fosse_argile', 1) === 4, "Argile niv 1 doit consommer 4");
assert(VillageGeneratorService::calculateHourlyEnergyConsumption('champ_soja', 1) === 4, "Soja niv 1 doit consommer 4");
assert(VillageGeneratorService::calculateHourlyEnergyConsumption('culture_the', 1) === 3, "Thé niv 1 doit consommer 3");
assert(VillageGeneratorService::calculateHourlyEnergyConsumption('tenshu', 5) === 0, "Tenshu ne doit pas consommer");
assert(VillageGeneratorService::calculateHourlyEnergyConsumption('village', 5) === 0, "Village ne doit pas consommer");
assert(VillageGeneratorService::calculateHourlyEnergyConsumption('sanctuaire_shinto', 5) === 0, "Sanctuaire ne doit pas consommer");
echo "[OK] Taux unitaires de consommation par structure validés.\n";

// 2. Test du village équilibré au Niveau 1
// Insertion des 9 parcelles au niveau 1
$plots = [
    1 => ['structure_type' => 'tenshu', 'level' => 1],
    2 => ['structure_type' => 'foret', 'level' => 1],
    3 => ['structure_type' => 'carriere', 'level' => 1],
    4 => ['structure_type' => 'culture_the', 'level' => 1],
    5 => ['structure_type' => 'village', 'level' => 1],
    6 => ['structure_type' => 'fosse_argile', 'level' => 1],
    7 => ['structure_type' => 'riziere', 'level' => 1],
    8 => ['structure_type' => 'champ_soja', 'level' => 1],
    9 => ['structure_type' => 'sanctuaire_shinto', 'level' => 1]
];
$stmtPlot = $sqlite->prepare("INSERT INTO planet_rural_plots (planet_id, slot_id, structure_type, level, prod_hourly) VALUES (?, ?, ?, ?, ?)");
foreach ($plots as $slotId => $p) {
    $prod = VillageGeneratorService::calculateHourlyProduction($p['structure_type'], $p['level']);
    $stmtPlot->execute([1, $slotId, $p['structure_type'], $p['level'], $prod]);
}

$sqlite->exec("INSERT INTO planets (id, user_id, name, coord_x, coord_y, planet_type) VALUES (1, 1, 'Fief Équilibré', 1, 1, 'normal')");

$pe = new PlanetEngine();
$prod = $pe->calculateProduction([], 1);

// Sanctuaire niv 1 = 30 + 20 base = 50 max
// Conso = 4 (foret) + 5 (carriere) + 6 (riziere) + 4 (argile) + 4 (soja) + 3 (the) = 26
echo "Cas Niveau 1 :\n";
echo "- Ferveur Max : {$prod['energy_max']} (Attendu: 50)\n";
echo "- Ferveur Conso : {$prod['energy_used']} (Attendu: 26)\n";
echo "- Ratio Énergie : {$prod['energy_ratio']} (Attendu: 1.0)\n";
assert($prod['energy_max'] === 50, "Energy max incorrect au niv 1");
assert($prod['energy_used'] === 26, "Energy used incorrect au niv 1");
assert($prod['energy_ratio'] == 1.0, "Energy ratio doit être 1.0");
echo "[OK] Cas équilibré niveau 1 validé avec succès (Solde net : +" . ($prod['energy_max'] - $prod['energy_used']) . " ferveur).\n\n";

// 3. Test du déficit critique : Mines niveau 5, Sanctuaire laissé à 1
// Conso = 26 * 5 = 130 ferveur
// Prod = 50 ferveur
$sqlite->exec("UPDATE planet_rural_plots SET level = 5 WHERE planet_id = 1 AND structure_type != 'sanctuaire_shinto' AND structure_type != 'tenshu' AND structure_type != 'village'");
$prodDeficit = $pe->calculateProduction([], 1);

echo "Cas Déficit Critique (Mines niv 5, Sanctuaire niv 1) :\n";
echo "- Ferveur Max : {$prodDeficit['energy_max']} (Attendu: 50)\n";
echo "- Ferveur Conso : {$prodDeficit['energy_used']} (Attendu: 130)\n";
echo "- Ratio Énergie : {$prodDeficit['energy_ratio']} (Attendu: 0.1)\n";
assert($prodDeficit['energy_max'] === 50, "Energy max doit être 50");
assert($prodDeficit['energy_used'] === 130, "Energy used doit être 130");
assert($prodDeficit['energy_ratio'] == 0.10, "Energy ratio doit chuter à 0.10 en déficit !");
echo "[OK] Déficit critique validé (Pénalité stricte de 10% appliquée).\n\n";

// 4. Test de rétablissement : Élévation du Sanctuaire au niveau 5
// Sanctuaire niv 5 = 20 + 5 * 30 = 170
$sqlite->exec("UPDATE planet_rural_plots SET level = 5 WHERE planet_id = 1 AND structure_type = 'sanctuaire_shinto'");
$prodRestored = $pe->calculateProduction([], 1);

echo "Cas Rétablissement (Sanctuaire niv 5) :\n";
echo "- Ferveur Max : {$prodRestored['energy_max']} (Attendu: 170)\n";
echo "- Ferveur Conso : {$prodRestored['energy_used']} (Attendu: 130)\n";
echo "- Ratio Énergie : {$prodRestored['energy_ratio']} (Attendu: 1.0)\n";
assert($prodRestored['energy_max'] === 170, "Energy max doit être 170");
assert($prodRestored['energy_used'] === 130, "Energy used doit être 130");
assert($prodRestored['energy_ratio'] == 1.0, "Energy ratio doit être rétabli à 1.0");
echo "[OK] Rétablissement validé (Solde net : +" . ($prodRestored['energy_max'] - $prodRestored['energy_used']) . " ferveur).\n\n";

// 5. Test du Pavillon de Thé (Teahouse) : Niv 2 (+10% ferveur max)
$sqlite->exec("INSERT INTO planet_buildings (planet_id, building_type, level, slot) VALUES (1, 'teahouse', 2, 33)");
$prodTeahouse = $pe->calculateProduction([], 1);
echo "Cas Pavillon de Thé Niv. 2 (+10% ferveur max) :\n";
echo "- Ferveur Max avec Teahouse : {$prodTeahouse['energy_max']} (170 * 1.10 = 187)\n";
assert($prodTeahouse['energy_max'] === 187, "Teahouse doit booster ferveur max");
echo "[OK] Bonus du Pavillon de Thé validé.\n\n";

// 6. Test de RuralPlotEngine::getEnrichedPlots()
$rpe = new RuralPlotEngine();
$enriched = $rpe->getEnrichedPlots(1, ['metal' => 1000, 'crystal' => 1000, 'deuterium' => 1000]);
assert($enriched['riziere']['energy_cons'] === 30, "Rizière niv 5 doit consommer 30 ferveur");
assert($enriched['foret']['energy_cons'] === 20, "Forêt niv 5 doit consommer 20 ferveur");
assert($enriched['sanctuaire_shinto']['energy_cons'] === 0, "Sanctuaire doit avoir conso 0");
assert($enriched['riziere']['next_energy_cons'] === 36, "Rizière prochain niveau doit consommer 36");
echo "[OK] RuralPlotEngine enrichit correctement energy_cons et next_energy_cons.\n\n";

echo ">>> TOUS LES TESTS DE FERVEUR DIVINE ONT RÉUSSI AVEC SUCCÈS ! <<<\n";

