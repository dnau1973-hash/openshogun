<?php
/**
 * Test unitaire et d'intégration : Finition instantanée d'une construction pour 2 Kobans
 */
require_once __DIR__ . '/../config/game_constants.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';

echo "=== TEST : FINITION INSTANTANÉE D'UNE CONSTRUCTION (2 KOBANS) ===\n\n";

// Configuration d'une base SQLite en mémoire pour isolation et reproductibilité
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
Database::setConnection($sqlite);

// Initialisation des schémas requis
$sqlite->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT,
    faction TEXT DEFAULT 'terran',
    gold_coins INTEGER NOT NULL DEFAULT 100,
    imperial_seal_until TEXT NULL DEFAULT NULL
)");

$sqlite->exec("CREATE TABLE planets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    name TEXT,
    population INTEGER DEFAULT 100,
    metal REAL DEFAULT 10000,
    crystal REAL DEFAULT 10000,
    deuterium REAL DEFAULT 10000,
    last_update INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE construction_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    build_category TEXT,
    target_id TEXT,
    target_level INTEGER,
    started_at INTEGER,
    finishes_at INTEGER
)");

$sqlite->exec("CREATE TABLE planet_buildings (
    planet_id INTEGER,
    building_type TEXT,
    level INTEGER DEFAULT 0,
    slot INTEGER DEFAULT 0,
    PRIMARY KEY(planet_id, building_type)
)");

$sqlite->exec("CREATE TABLE planet_fields (
    planet_id INTEGER,
    field_slot INTEGER,
    type TEXT,
    level INTEGER DEFAULT 0,
    PRIMARY KEY(planet_id, field_slot)
)");

$sqlite->exec("CREATE TABLE planet_rural_plots (
    planet_id INTEGER,
    structure_type TEXT,
    level INTEGER DEFAULT 0,
    workers_assigned INTEGER DEFAULT 0,
    prod_hourly REAL DEFAULT 0,
    PRIMARY KEY(planet_id, structure_type)
)");

$sqlite->exec("CREATE TABLE game_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT,
    setting_type TEXT
)");
$sqlite->exec("INSERT INTO game_settings VALUES 
    ('game_speed', '1', 'string'), 
    ('fleet_speed', '1', 'string'),
    ('building_time_coeff', '1.0', 'string'),
    ('building_time_growth', '1.28', 'string'),
    ('building_cost_coeff', '1.0', 'string'),
    ('building_cost_growth', '1.0', 'string')
");

// Données de test
$sqlite->exec("INSERT INTO users (id, username, faction, gold_coins) VALUES (1, 'Nobunaga', 'terran', 10)");
$sqlite->exec("INSERT INTO planets (id, user_id, name, population, last_update) VALUES (1, 1, 'Gifu', 100, " . time() . ")");
$sqlite->exec("INSERT INTO planet_buildings (planet_id, building_type, level) VALUES (1, 'hq', 1)");

$planetId = 1;
$userId = 1;

$buildingEngine = new BuildingEngine($sqlite);

// -------------------------------------------------------------
// Test 1 : Rejet si solde de Kobans insuffisant (< 2)
// -------------------------------------------------------------
echo "[1] Test de rejet si solde < 2 Kobans...\n";
$sqlite->exec("UPDATE users SET gold_coins = 1 WHERE id = $userId");

$now = time();
$sqlite->prepare("
    INSERT INTO construction_queue (planet_id, build_category, target_id, target_level, started_at, finishes_at)
    VALUES (?, 'building', 'market', 2, ?, ?)
")->execute([$planetId, $now, $now + 3600]);
$fakeQueueId = (int)$sqlite->lastInsertId();

$errorTriggered = false;
try {
    $buildingEngine->instantFinish($userId, $planetId, $fakeQueueId);
} catch (Exception $e) {
    $errorTriggered = true;
    echo "✓ Erreur capturée avec succès : " . $e->getMessage() . "\n";
    assert(strpos($e->getMessage(), 'insuffisant') !== false, "Message d'erreur attendu pour solde insuffisant.");
}
assert($errorTriggered, "L'opération aurait dû échouer pour solde insuffisant.");
$sqlite->exec("DELETE FROM construction_queue WHERE id = $fakeQueueId");

// -------------------------------------------------------------
// Test 2 : Finition instantanée d'un bâtiment urbain (building)
// -------------------------------------------------------------
echo "\n[2] Test de finition instantanée d'un bâtiment urbain (2 Kobans)...\n";
$sqlite->exec("UPDATE users SET gold_coins = 10 WHERE id = $userId");
$sqlite->exec("INSERT INTO planet_buildings (planet_id, building_type, level) VALUES ($planetId, 'wall', 1) ON CONFLICT(planet_id, building_type) DO UPDATE SET level = 1");

$now = time();
$sqlite->prepare("
    INSERT INTO construction_queue (planet_id, build_category, target_id, target_level, started_at, finishes_at)
    VALUES (?, 'building', 'wall', 2, ?, ?)
")->execute([$planetId, $now, $now + 3600]);
$qWall = (int)$sqlite->lastInsertId();

$res = $buildingEngine->instantFinish($userId, $planetId, $qWall);
assert($res['success'] === true, "L'achèvement instantané du mur doit réussir.");
assert($res['gold_spent'] === 2, "Le coût doit être de 2 Kobans.");
assert($res['remaining_gold'] === 8, "Le solde restant doit être 8.");

$stLvl = $sqlite->prepare("SELECT level FROM planet_buildings WHERE planet_id = ? AND building_type = 'wall'");
$stLvl->execute([$planetId]);
$wallLevel = (int)$stLvl->fetchColumn();
assert($wallLevel === 2, "Le niveau du mur doit être passé à 2. Trouvé : $wallLevel");

$stQ = $sqlite->prepare("SELECT COUNT(*) FROM construction_queue WHERE id = ?");
$stQ->execute([$qWall]);
assert((int)$stQ->fetchColumn() === 0, "Le chantier de mur doit être supprimé de la file.");
echo "✓ Bâtiment urbain complété avec succès ! Nouveau niveau : $wallLevel, Kobans restants : 8\n";

// -------------------------------------------------------------
// Test 3 : Finition instantanée d'une unité de ressource (field)
// -------------------------------------------------------------
echo "\n[3] Test de finition instantanée d'une parcelle de ressources (field)...\n";
$slot = 1;
$sqlite->prepare("INSERT INTO planet_fields (planet_id, field_slot, type, level) VALUES (?, ?, 'metal_mine', 3) ON CONFLICT(planet_id, field_slot) DO UPDATE SET level = 3")->execute([$planetId, $slot]);

$now = time();
$sqlite->prepare("
    INSERT INTO construction_queue (planet_id, build_category, target_id, target_level, started_at, finishes_at)
    VALUES (?, 'field', ?, 4, ?, ?)
")->execute([$planetId, $slot, $now, $now + 7200]);
$qField = (int)$sqlite->lastInsertId();

$res = $buildingEngine->instantFinish($userId, $planetId, $qField);
assert($res['success'] === true, "L'achèvement instantané de la parcelle doit réussir.");
assert($res['remaining_gold'] === 6, "Le solde restant doit être 6.");

$stField = $sqlite->prepare("SELECT level FROM planet_fields WHERE planet_id = ? AND field_slot = ?");
$stField->execute([$planetId, $slot]);
$fieldLvl = (int)$stField->fetchColumn();
assert($fieldLvl === 4, "Le niveau de la parcelle doit être 4. Trouvé : $fieldLvl");

$stQ = $sqlite->prepare("SELECT COUNT(*) FROM construction_queue WHERE id = ?");
$stQ->execute([$qField]);
assert((int)$stQ->fetchColumn() === 0, "Le chantier de la parcelle doit être supprimé de la file.");
echo "✓ Parcelle de ressource complétée avec succès ! Nouveau niveau : $fieldLvl, Kobans restants : 6\n";

// -------------------------------------------------------------
// Test 4 : Finition instantanée d'une parcelle rurale (rural_plot)
// -------------------------------------------------------------
echo "\n[4] Test de finition instantanée d'une parcelle rurale (rural_plot : riziere)...\n";
$sqlite->prepare("INSERT INTO planet_rural_plots (planet_id, structure_type, level) VALUES (?, 'riziere', 1) ON CONFLICT(planet_id, structure_type) DO UPDATE SET level = 1")->execute([$planetId]);

$now = time();
$sqlite->prepare("
    INSERT INTO construction_queue (planet_id, build_category, target_id, target_level, started_at, finishes_at)
    VALUES (?, 'rural_plot', 'riziere', 2, ?, ?)
")->execute([$planetId, $now, $now + 5000]);
$qRural = (int)$sqlite->lastInsertId();

$res = $buildingEngine->instantFinish($userId, $planetId, $qRural);
assert($res['success'] === true, "L'achèvement instantané de la rizière doit réussir.");
assert($res['remaining_gold'] === 4, "Le solde restant doit être 4.");

$stRural = $sqlite->prepare("SELECT level FROM planet_rural_plots WHERE planet_id = ? AND structure_type = 'riziere'");
$stRural->execute([$planetId]);
$ruralLvl = (int)$stRural->fetchColumn();
assert($ruralLvl === 2, "Le niveau de la rizière rurale doit être 2. Trouvé : $ruralLvl");
echo "✓ Parcelle rurale complétée avec succès ! Nouveau niveau : $ruralLvl, Kobans restants : 4\n";

// -------------------------------------------------------------
// Test 5 : Décalage séquentiel du chantier suivant
// -------------------------------------------------------------
echo "\n[5] Test d'avancement des chantiers suivants dans la file...\n";
$now = time();
$t1Finish = $now + 3600;
// Job 1
$sqlite->prepare("
    INSERT INTO construction_queue (planet_id, build_category, target_id, target_level, started_at, finishes_at)
    VALUES (?, 'building', 'market', 3, ?, ?)
")->execute([$planetId, $now, $t1Finish]);
$job1 = (int)$sqlite->lastInsertId();

// Job 2 séquentiel qui attendait la fin de Job 1
$sqlite->prepare("
    INSERT INTO construction_queue (planet_id, build_category, target_id, target_level, started_at, finishes_at)
    VALUES (?, 'building', 'wall', 3, ?, ?)
")->execute([$planetId, $t1Finish, $t1Finish + 1800]);
$job2 = (int)$sqlite->lastInsertId();

// Finir Job 1
$res = $buildingEngine->instantFinish($userId, $planetId, $job1);
assert($res['success'] === true);

// Vérifier que Job 2 a été avancé pour commencer immédiatement
$stJ2 = $sqlite->prepare("SELECT started_at, finishes_at FROM construction_queue WHERE id = ?");
$stJ2->execute([$job2]);
$j2Data = $stJ2->fetch(PDO::FETCH_ASSOC);
assert($j2Data !== false, "Job 2 doit toujours être dans la file.");
assert((int)$j2Data['started_at'] <= time(), "Job 2 doit maintenant avoir démarré.");
assert((int)$j2Data['finishes_at'] <= time() + 1805, "Job 2 doit finir dans ~1800s à partir de maintenant.");
echo "✓ Chantier séquentiel suivant avancé avec succès ! started_at = {$j2Data['started_at']}, finishes_at = {$j2Data['finishes_at']}\n";

echo "\n>>> TOUS LES TESTS SONT PASSÉS AVEC SUCCÈS (5/5) ! <<<\n";

