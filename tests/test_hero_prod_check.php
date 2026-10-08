<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/HeroEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/GameConfig.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
Database::setConnection($pdo);

$pdo->exec("
    CREATE TABLE game_settings (
        setting_key TEXT PRIMARY KEY,
        setting_value TEXT,
        setting_type TEXT
    );
    CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        username TEXT,
        faction TEXT,
        points INTEGER
    );
    CREATE TABLE planets (
        id INTEGER PRIMARY KEY,
        user_id INTEGER,
        name TEXT,
        coord_x INTEGER,
        coord_y INTEGER,
        planet_type TEXT,
        metal REAL,
        crystal REAL,
        deuterium REAL,
        energy_used INTEGER,
        energy_max INTEGER,
        metal_max INTEGER,
        crystal_max INTEGER,
        deuterium_max INTEGER,
        last_resource_update INTEGER,
        is_capital INTEGER
    );
    CREATE TABLE heroes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER UNIQUE,
        current_planet_id INTEGER,
        name TEXT,
        level INTEGER DEFAULT 1,
        experience INTEGER DEFAULT 0,
        health REAL DEFAULT 100.0,
        status TEXT DEFAULT 'home',
        last_health_update INTEGER DEFAULT 0,
        stat_strength INTEGER DEFAULT 0,
        stat_offense_bonus INTEGER DEFAULT 0,
        stat_defense_bonus INTEGER DEFAULT 0,
        stat_production INTEGER DEFAULT 0,
        production_type TEXT DEFAULT 'balanced',
        unassigned_points INTEGER DEFAULT 4,
        revive_finish_time INTEGER NULL,
        equipped_weapon TEXT NULL,
        equipped_helmet TEXT NULL,
        equipped_armor TEXT NULL,
        equipped_horse TEXT NULL,
        equipped_talisman TEXT NULL
    );
    CREATE TABLE hero_adventures (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        coord_x INTEGER,
        coord_y INTEGER,
        name TEXT,
        difficulty TEXT,
        status TEXT
    );
    CREATE TABLE hero_inventory (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        item_code TEXT,
        item_type TEXT,
        name TEXT,
        description TEXT,
        rarity TEXT,
        bonus_data TEXT,
        is_equipped INTEGER DEFAULT 0,
        equipped_at INTEGER NULL
    );
    CREATE TABLE planet_buildings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        planet_id INTEGER,
        building_type TEXT,
        level INTEGER,
        slot INTEGER
    );
    CREATE TABLE planet_fields (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        planet_id INTEGER,
        field_slot INTEGER,
        field_type TEXT,
        level INTEGER
    );
    CREATE TABLE planet_rural_plots (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        planet_id INTEGER,
        slot_id INTEGER,
        structure_type TEXT,
        level INTEGER DEFAULT 1,
        max_level INTEGER DEFAULT 30,
        pos_x REAL DEFAULT 50.0,
        pos_y REAL DEFAULT 50.0,
        workers_assigned INTEGER DEFAULT 2,
        prod_hourly REAL DEFAULT 0.0
    );
    CREATE TABLE fleet_missions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        mission_type TEXT,
        departure_time INTEGER
    );
");

$pdo->exec("INSERT INTO users (id, username, faction, points) VALUES (1, 'DaimyoTest', 'terran', 100)");
$pdo->exec("INSERT INTO planets (id, user_id, name, coord_x, coord_y, planet_type, metal, crystal, deuterium, energy_used, energy_max, metal_max, crystal_max, deuterium_max, last_resource_update, is_capital)
            VALUES (1, 1, 'Château Test', 500, 500, 'terrestrial', 1000, 1000, 1000, 0, 50, 15000, 15000, 15000, " . time() . ", 1)");

$heroEngine = new HeroEngine($pdo);
$hero = $heroEngine->createHeroForUser(1, 'Héros Test', 1, 500, 500);

echo "Initial Hero:\n";
print_r($hero['effective']);

// Allocating 4 points to production
$alloc = $heroEngine->allocatePoints(1, 0, 0, 0, 4);
echo "Alloc result: " . json_encode($alloc) . "\n";

$heroUpdated = $heroEngine->getHeroByUserId(1);
echo "Updated Hero effective:\n";
print_r($heroUpdated['effective']);

$bonus = $heroEngine->getHeroProductionBonus(1);
echo "Bonus for planet 1:\n";
print_r($bonus);

$planetEngine = new PlanetEngine($pdo);
$prod = $planetEngine->calculateProduction([], 1);
echo "calculateProduction for planet 1:\n";
print_r($prod);

// Test 1: Hero on adventure
$pdo->exec("UPDATE heroes SET status = 'adventure' WHERE id = 1");
$bonusAdv = $heroEngine->getHeroProductionBonus(1);
echo "Bonus when status is 'adventure':\n";
print_r($bonusAdv);

// Test 2: Hero current_planet_id is 0
$pdo->exec("UPDATE heroes SET status = 'home', current_planet_id = 0 WHERE id = 1");
$bonusZero = $heroEngine->getHeroProductionBonus(1);
echo "Bonus when current_planet_id is 0:\n";
print_r($bonusZero);
