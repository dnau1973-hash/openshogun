<?php
require_once __DIR__ . '/../core/Database.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE game_settings (setting_key TEXT PRIMARY KEY, setting_value TEXT, setting_type TEXT);');
$pdo->exec('CREATE TABLE planets (id INTEGER PRIMARY KEY, metal REAL DEFAULT 0, crystal REAL DEFAULT 0, deuterium REAL DEFAULT 0, clay REAL DEFAULT 0, tea REAL DEFAULT 0, soybean REAL DEFAULT 0, energy_max INTEGER DEFAULT 20, energy_used INTEGER DEFAULT 0, last_resource_update INTEGER DEFAULT 0, population INTEGER DEFAULT 100, contentment INTEGER DEFAULT 100);');
$pdo->exec('CREATE TABLE planet_rural_plots (id INTEGER PRIMARY KEY AUTOINCREMENT, planet_id INTEGER, slot_id INTEGER, structure_type TEXT, level INTEGER, max_level INTEGER, pos_x REAL, pos_y REAL, workers_assigned INTEGER, prod_hourly REAL);');
$pdo->exec('CREATE TABLE planet_buildings (id INTEGER PRIMARY KEY AUTOINCREMENT, planet_id INTEGER, building_type TEXT, level INTEGER);');
$pdo->exec('CREATE TABLE planet_fields (id INTEGER PRIMARY KEY AUTOINCREMENT, planet_id INTEGER, field_slot INTEGER, type TEXT, level INTEGER);');
$pdo->exec('CREATE TABLE planet_feasts (id INTEGER PRIMARY KEY AUTOINCREMENT, planet_id INTEGER, feast_type TEXT, tenshu_level INTEGER, started_at INTEGER, finishes_at INTEGER);');
Database::setConnection($pdo);

require_once __DIR__ . '/../core/GameConfig.php';
require_once __DIR__ . '/../core/VillageGeneratorService.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/RuralPlotEngine.php';

echo "=== 1. Test des vitesses de production unitaires ===\n";
GameConfig::set('resource_speed', 1);
$prodClay1 = VillageGeneratorService::calculateHourlyProduction('fosse_argile', 1);
$prodTea1  = VillageGeneratorService::calculateHourlyProduction('culture_the', 1);
$prodSoy1  = VillageGeneratorService::calculateHourlyProduction('champ_soja', 1);
echo "Vitesse 1x -> Argile: {$prodClay1}/h | Thé: {$prodTea1}/h | Soja: {$prodSoy1}/h\n";

GameConfig::set('resource_speed', 5);
$prodClay5 = VillageGeneratorService::calculateHourlyProduction('fosse_argile', 1);
$prodTea5  = VillageGeneratorService::calculateHourlyProduction('culture_the', 1);
$prodSoy5  = VillageGeneratorService::calculateHourlyProduction('champ_soja', 1);
echo "Vitesse 5x -> Argile: {$prodClay5}/h | Thé: {$prodTea5}/h | Soja: {$prodSoy5}/h\n";

assert(abs($prodClay5 - ($prodClay1 * 5)) < 0.2, "Erreur: Argile 5x doit valoir 5 fois Argile 1x");
assert(abs($prodTea5 - ($prodTea1 * 5)) < 0.2, "Erreur: Thé 5x doit valoir 5 fois Thé 1x");
assert(abs($prodSoy5 - ($prodSoy1 * 5)) < 0.2, "Erreur: Soja 5x doit valoir 5 fois Soja 1x");
echo "[OK] Multiplicateur resource_speed validé pour les parcelles unitaires !\n";

echo "\n=== 2. Test d'intégration avec PlanetEngine::calculateProduction ===\n";
$pdo->exec("INSERT INTO planets (id, metal, crystal, deuterium, clay, tea, soybean) VALUES (1, 1000, 1000, 1000, 0, 0, 0)");
$plots = [
    ['foret', 1],
    ['carriere', 1],
    ['riziere', 1],
    ['fosse_argile', 1],
    ['culture_the', 1],
    ['champ_soja', 1],
    ['sanctuaire_shinto', 1],
    ['village', 1],
    ['tenshu', 1],
];
$slot = 1;
foreach ($plots as [$type, $lvl]) {
    $pVal = VillageGeneratorService::calculateHourlyProduction($type, $lvl);
    $pdo->prepare("INSERT INTO planet_rural_plots (planet_id, slot_id, structure_type, level, max_level, pos_x, pos_y, workers_assigned, prod_hourly) VALUES (1, ?, ?, ?, 30, 50, 50, 2, ?)")
        ->execute([$slot++, $type, $lvl, $pVal]);
}

$pe = new PlanetEngine($pdo);
$rates5 = $pe->calculateProduction([], 1);

echo "Production avec resource_speed = 5 :\n";
echo "- Métal / Bois : {$rates5['metal']}/h\n";
echo "- Cristal / Pierre : {$rates5['crystal']}/h\n";
echo "- Deutérium / Riz : {$rates5['deuterium']}/h\n";
echo "- Argile : {$rates5['clay']}/h\n";
echo "- Thé : {$rates5['tea']}/h\n";
echo "- Soja : {$rates5['soybean']}/h\n";

assert($rates5['clay'] == (int)$prodClay5, "Erreur prod clay");
assert($rates5['tea'] == (int)$prodTea5, "Erreur prod tea");
assert($rates5['soybean'] == (int)$prodSoy5, "Erreur prod soybean");
echo "[OK] Intégration calculateProduction validée à 5x !\n";

echo "\n=== 3. Changement dynamique à 10x ===\n";
GameConfig::set('resource_speed', 10);
$rates10 = $pe->calculateProduction([], 1);
echo "Production avec resource_speed = 10 :\n";
echo "- Argile : {$rates10['clay']}/h\n";
echo "- Thé : {$rates10['tea']}/h\n";
echo "- Soja : {$rates10['soybean']}/h\n";

assert($rates10['clay'] > $rates5['clay'], "Erreur dynamique speed up");
assert($rates10['clay'] == (int)($prodClay1 * 10), "Erreur prod clay 10x");
echo "[OK] Prise en compte dynamique en temps réel validée !\n";

echo "\n=============================================\n";
echo " TOUS LES TESTS D'ACCÉLÉRATION SONT VALIDÉS ! \n";
echo "=============================================\n";

