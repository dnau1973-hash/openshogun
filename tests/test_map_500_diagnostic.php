<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
Database::setConnection($sqlite);

// Setup full schema matching OpenShogun
$sqlite->exec("
    CREATE TABLE IF NOT EXISTS game_settings (setting_key VARCHAR(50) PRIMARY KEY, setting_value TEXT, setting_type VARCHAR(20));
    CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, username TEXT, faction TEXT, points INTEGER, bio TEXT, role TEXT, is_admin INTEGER, is_bot INTEGER, protection_until TEXT);
    CREATE TABLE IF NOT EXISTS planets (id INTEGER PRIMARY KEY, user_id INTEGER, name TEXT, coord_x INTEGER, coord_y INTEGER, population INTEGER, population_max INTEGER, metal REAL, crystal REAL, deuterium REAL, fish REAL, rice_flour REAL, sake REAL, wooden_beams REAL, charcoal REAL, contentment INTEGER, energy_used INTEGER, energy_max INTEGER, famine_active INTEGER);
    CREATE TABLE IF NOT EXISTS planet_units (id INTEGER PRIMARY KEY, planet_id INTEGER, unit_code TEXT, count INTEGER);
    CREATE TABLE IF NOT EXISTS planet_buildings (id INTEGER PRIMARY KEY, planet_id INTEGER, building_type TEXT, level INTEGER, slot INTEGER);
    CREATE TABLE IF NOT EXISTS planet_fields (id INTEGER PRIMARY KEY, planet_id INTEGER, field_slot INTEGER, field_type TEXT, level INTEGER);
    CREATE TABLE IF NOT EXISTS planet_rural_plots (id INTEGER PRIMARY KEY, planet_id INTEGER, slot_id INTEGER, structure_type TEXT, level INTEGER, max_level INTEGER DEFAULT 30);
    CREATE TABLE IF NOT EXISTS construction_queue (id INTEGER PRIMARY KEY, planet_id INTEGER, build_category TEXT, target_id TEXT, target_level INTEGER, started_at INTEGER, finishes_at INTEGER);
    CREATE TABLE IF NOT EXISTS barracks_queue (id INTEGER PRIMARY KEY, planet_id INTEGER, unit_code TEXT, count INTEGER, started_at INTEGER, finishes_at INTEGER);
    CREATE TABLE IF NOT EXISTS shipyard_queue (id INTEGER PRIMARY KEY, planet_id INTEGER, ship_code TEXT, count INTEGER, started_at INTEGER, finishes_at INTEGER);
    CREATE TABLE IF NOT EXISTS research_queue (id INTEGER PRIMARY KEY, user_id INTEGER, research_code TEXT, target_level INTEGER, started_at INTEGER, finishes_at INTEGER);
    CREATE TABLE IF NOT EXISTS user_researches (id INTEGER PRIMARY KEY, user_id INTEGER, research_code TEXT, level INTEGER);
    CREATE TABLE IF NOT EXISTS user_quests (id INTEGER PRIMARY KEY, user_id INTEGER, quest_key TEXT, status TEXT, progress INTEGER, completed_at TEXT, claimed_at TEXT);
    CREATE TABLE IF NOT EXISTS quests (id INTEGER PRIMARY KEY, quest_key TEXT, title TEXT, description TEXT, reward_metal INTEGER, reward_crystal INTEGER, reward_deuterium INTEGER);
    CREATE TABLE IF NOT EXISTS oases (id INTEGER PRIMARY KEY, coord_x INTEGER, coord_y INTEGER, type TEXT, bonus_type TEXT, bonus_value INTEGER, owner_planet_id INTEGER);
    CREATE TABLE IF NOT EXISTS annexed_oases (id INTEGER PRIMARY KEY, planet_id INTEGER, oasis_id INTEGER);
    CREATE TABLE IF NOT EXISTS weekly_events (id INTEGER PRIMARY KEY, is_active INTEGER, week_number INTEGER, era_year TEXT, title TEXT, description TEXT, start_date TEXT, end_date TEXT);
    CREATE TABLE IF NOT EXISTS weekly_event_tasks (id INTEGER PRIMARY KEY, event_id INTEGER, task_code TEXT, target_count INTEGER);
    CREATE TABLE IF NOT EXISTS weekly_event_progress (id INTEGER PRIMARY KEY, user_id INTEGER, event_id INTEGER, task_code TEXT, progress INTEGER, is_completed INTEGER, is_claimed INTEGER);
    CREATE TABLE IF NOT EXISTS fleets (id INTEGER PRIMARY KEY, user_id INTEGER, planet_id INTEGER, target_x INTEGER, target_y INTEGER, mission_type TEXT, departure_time INTEGER, arrival_time INTEGER, return_time INTEGER, units TEXT, resources TEXT, is_return INTEGER);
    CREATE TABLE IF NOT EXISTS market_offers (id INTEGER PRIMARY KEY, planet_id INTEGER, user_id INTEGER, offer_type TEXT, amount_offered REAL, amount_demanded REAL, created_at TEXT, status TEXT);
    CREATE TABLE IF NOT EXISTS units (code TEXT PRIMARY KEY, name TEXT, faction TEXT, attack INTEGER, defense INTEGER, speed INTEGER, cargo INTEGER, cost_metal INTEGER, cost_crystal INTEGER, cost_deuterium INTEGER);
    CREATE TABLE IF NOT EXISTS ships (code TEXT PRIMARY KEY, name TEXT, faction TEXT, attack INTEGER, defense INTEGER, speed INTEGER, cargo INTEGER, cost_metal INTEGER, cost_crystal INTEGER, cost_deuterium INTEGER);
");

// Insert standard user and planet
$sqlite->exec("INSERT INTO users (id, username, faction, points, bio, role, is_admin, is_bot) VALUES (1, 'Nobunaga', 'terran', 1500, 'Seigneur de guerre', 'player', 0, 0)");
$sqlite->exec("INSERT INTO planets (id, user_id, name, coord_x, coord_y, population, population_max, metal, crystal, deuterium, fish, rice_flour, sake, wooden_beams, charcoal, contentment, energy_used, energy_max, famine_active) VALUES (1, 1, 'Fief Azuchi', 15, 25, 45, 120, 2500, 1800, 900, 300, 150, 80, 50, 40, 90, 15, 30, 0)");
$sqlite->exec("INSERT INTO planet_units (planet_id, unit_code, count) VALUES (1, 'pecheur', 5), (1, 'mineur', 3)");

echo "TEST 1: Standard loading with populated variables...\n";
$user = [
    'id' => 1,
    'username' => 'Nobunaga',
    'faction' => 'terran',
    'role' => 'player'
];
$planet = [
    'id' => 1,
    'name' => 'Fief Azuchi',
    'coord_x' => 15,
    'coord_y' => 25,
    'population' => 45,
    'population_max' => 120,
    'metal' => 2500,
    'crystal' => 1800,
    'deuterium' => 900,
    'faction' => 'terran'
];

ob_start();
try {
    require __DIR__ . '/../views/map.php';
    $out = ob_get_clean();
    echo "[PASS] views/map.php rendered successfully (" . strlen($out) . " bytes)\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "[FAIL] TEST 1: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\nTEST 2: Loading when \$planet has missing optional fields...\n";
$planet = [
    'id' => 1,
    'name' => 'Fief Azuchi',
    'coord_x' => 15,
    'coord_y' => 25
];
ob_start();
try {
    require __DIR__ . '/../views/map.php';
    $out = ob_get_clean();
    echo "[PASS] views/map.php rendered successfully with minimal planet (" . strlen($out) . " bytes)\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "[FAIL] TEST 2: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\nTEST 3: Loading when \$planet is null...\n";
$planet = null;
ob_start();
try {
    require __DIR__ . '/../views/map.php';
    $out = ob_get_clean();
    echo "[PASS] views/map.php handled null \$planet cleanly (" . strlen($out) . " bytes)\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "[FAIL] TEST 3: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\nTEST 4: Loading when \$user is null...\n";
$planet = ['id' => 1, 'name' => 'Test', 'coord_x' => 0, 'coord_y' => 0];
$user = null;
ob_start();
try {
    require __DIR__ . '/../views/map.php';
    $out = ob_get_clean();
    echo "[PASS] views/map.php handled null \$user cleanly (" . strlen($out) . " bytes)\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "[FAIL] TEST 4: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\nTEST 5: Loading when Database tables fail...\n";
$failingPdo = new PDO('sqlite::memory:');
$failingPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
Database::setConnection($failingPdo);
$user = ['id' => 1, 'username' => 'Nobunaga', 'faction' => 'terran', 'role' => 'player'];
$planet = ['id' => 1, 'name' => 'Test', 'coord_x' => 0, 'coord_y' => 0];
ob_start();
try {
    require __DIR__ . '/../views/map.php';
    $out = ob_get_clean();
    echo "[PASS] views/map.php handled DB failures without HTTP 500 fatal crash (" . strlen($out) . " bytes)\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "[FAIL] TEST 5: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}
