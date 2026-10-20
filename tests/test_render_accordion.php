<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../core/Database.php';

// Set up in-memory sqlite to simulate database
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Reflection to set Database::$instance
$ref = new ReflectionClass('Database');
$prop = $ref->getProperty('instance');
$prop->setAccessible(true);
$prop->setValue(null, $pdo);

// Create minimal schema for tables that might be queried
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, username TEXT, faction TEXT, role TEXT, is_admin INTEGER, points INTEGER);
CREATE TABLE IF NOT EXISTS planets (id INTEGER PRIMARY KEY, user_id INTEGER, name TEXT, faction TEXT, population INTEGER, population_max INTEGER, metal REAL, crystal REAL, deuterium REAL, energy_used REAL, energy_max REAL, last_update INTEGER);
CREATE TABLE IF NOT EXISTS user_quests (id INTEGER PRIMARY KEY, user_id INTEGER, quest_id INTEGER, is_completed INTEGER, is_claimed INTEGER);
CREATE TABLE IF NOT EXISTS construction_queue (id INTEGER PRIMARY KEY, planet_id INTEGER, build_category TEXT, target_id TEXT, target_level INTEGER, is_demolition INTEGER, starts_at INTEGER, finishes_at INTEGER);
CREATE TABLE IF NOT EXISTS barracks_queue (id INTEGER PRIMARY KEY, planet_id INTEGER, unit_type TEXT, quantity INTEGER, completed_units INTEGER, started_at INTEGER, finishes_at INTEGER, duration INTEGER);
CREATE TABLE IF NOT EXISTS shipyard_queue (id INTEGER PRIMARY KEY, planet_id INTEGER, ship_type TEXT, quantity INTEGER, completed_units INTEGER, started_at INTEGER, finishes_at INTEGER, duration INTEGER);
CREATE TABLE IF NOT EXISTS research_queue (id INTEGER PRIMARY KEY, user_id INTEGER, research_code TEXT, level INTEGER, started_at INTEGER, finishes_at INTEGER);
CREATE TABLE IF NOT EXISTS researches (id INTEGER PRIMARY KEY, code TEXT, name TEXT);
CREATE TABLE IF NOT EXISTS planet_feasts (id INTEGER PRIMARY KEY, planet_id INTEGER, feast_type TEXT, started_at INTEGER, finishes_at INTEGER);
CREATE TABLE IF NOT EXISTS craft_queue (id INTEGER PRIMARY KEY, planet_id INTEGER, building_type TEXT, product TEXT, quantity INTEGER, starts_at INTEGER, finishes_at INTEGER, produced_amount REAL);
CREATE TABLE IF NOT EXISTS annexed_oases (id INTEGER PRIMARY KEY, planet_id INTEGER);
CREATE TABLE IF NOT EXISTS oases (id INTEGER PRIMARY KEY, planet_id INTEGER, owner_planet_id INTEGER, name TEXT, type TEXT, level INTEGER, annexed_at INTEGER);
CREATE TABLE IF NOT EXISTS planet_buildings (id INTEGER PRIMARY KEY, planet_id INTEGER, building_type TEXT, level INTEGER);
CREATE TABLE IF NOT EXISTS planet_fields (id INTEGER PRIMARY KEY, planet_id INTEGER, field_slot INTEGER, level INTEGER, type TEXT, UNIQUE(planet_id, field_slot));
CREATE TABLE IF NOT EXISTS units (id INTEGER PRIMARY KEY, code TEXT, name TEXT, icon TEXT, image TEXT, description TEXT, faction TEXT, attack INTEGER, defense INTEGER, speed INTEGER, capacity INTEGER, wood_cost INTEGER, clay_cost INTEGER, iron_cost INTEGER, crop_cost INTEGER, train_time INTEGER);
CREATE TABLE IF NOT EXISTS planet_units (id INTEGER PRIMARY KEY, planet_id INTEGER, unit_type TEXT, unit_code TEXT, count INTEGER);
CREATE TABLE IF NOT EXISTS ships (id INTEGER PRIMARY KEY, code TEXT, name TEXT, icon TEXT);
CREATE TABLE IF NOT EXISTS planet_ships (id INTEGER PRIMARY KEY, planet_id INTEGER, ship_type TEXT, ship_code TEXT, count INTEGER);

");

$user = [
    'id' => 1,
    'username' => 'Nobunaga',
    'faction' => 'terran',
    'role' => 'admin',
    'is_admin' => 1
];

$planet = [
    'id' => 1,
    'user_id' => 1,
    'name' => 'Château de Gifu',
    'faction' => 'terran',
    'population' => 150,
    'population_max' => 500,
    'workforce' => ['is_understaffed' => false, 'understaffed_malus_pct' => 0]
];

$prodRates = [
    'workforce' => ['is_understaffed' => false, 'understaffed_malus_pct' => 0]
];

echo "Testing render of views/partials/resources_east_accordion.php...\n";
try {
    ob_start();
    require __DIR__ . '/../views/partials/resources_east_accordion.php';
    $out = ob_get_clean();
    echo "SUCCESS! Output length: " . strlen($out) . " bytes\n";
    echo "Has accordion: " . (strpos($out, 'id="resourcesEastAccordion"') !== false ? 'YES' : 'NO') . "\n";
    echo "Has urban queue: " . (strpos($out, 'id="urban-construction-queue-card"') !== false ? 'YES' : 'NO') . "\n";
} catch (Throwable $e) {
    echo "EXCEPTION / ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
