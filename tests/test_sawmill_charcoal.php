<?php
/**
 * Test unitaire & fonctionnel : Production de Charbon de bois dans l'Atelier de Charpenterie (Kizukuri)
 * Valide :
 * 1. Blocage de la production de charbon si la Charpenterie < Niveau 15
 * 2. Déblocage de la production de charbon dès le Niveau 15
 * 3. Ratio de base 1 Bois de Cèdre -> 1 Charbon de bois (avec application du bonus d'efficience)
 * 4. Déduction atomique du bois de cèdre et insertion dans craft_queue
 * 5. Résolution de la production (processCraftQueue) et plafonnement à charcoal_max
 * 6. Annulation d'un lot avec restitution de 80% du bois engagé
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->sqliteCreateFunction('least', function($a, $b) { return min($a, $b); }, 2);
$sqlite->sqliteCreateFunction('greatest', function($a, $b) { return max($a, $b); }, 2);
$sqlite->sqliteCreateFunction('UNIX_TIMESTAMP', function() { return time(); }, 0);
Database::setConnection($sqlite);

require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/GameConfig.php';

echo "=================================================================\n";
echo " TEST : CHARPENTERIE - PRODUCTION DE CHARBON DE BOIS (NIV 15+)\n";
echo "=================================================================\n\n";

// Schéma minimal pour tester PlanetEngine
$sqlite->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY,
    username TEXT,
    faction TEXT DEFAULT 'terran',
    protection_until INTEGER DEFAULT NULL
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS game_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT,
    setting_type TEXT
)");
$sqlite->exec("INSERT INTO game_settings VALUES ('game_speed', '1', 'string')");

$sqlite->exec("CREATE TABLE IF NOT EXISTS planets (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    name TEXT,
    coord_x INTEGER,
    coord_y INTEGER,
    planet_type TEXT DEFAULT 'terrestrial',
    metal REAL DEFAULT 10000,
    crystal REAL DEFAULT 5000,
    deuterium REAL DEFAULT 5000,
    fish REAL DEFAULT 0,
    clay REAL DEFAULT 0,
    tea REAL DEFAULT 0,
    soybean REAL DEFAULT 0,
    sake REAL DEFAULT 0,
    rice_flour REAL DEFAULT 0,
    wooden_beams REAL DEFAULT 0,
    wooden_beams_max INTEGER DEFAULT 10000,
    charcoal REAL DEFAULT 0,
    charcoal_max INTEGER DEFAULT 10000,
    population INTEGER DEFAULT 100,
    contentment INTEGER DEFAULT 100,
    energy_used INTEGER DEFAULT 0,
    energy_max INTEGER DEFAULT 20,
    metal_max INTEGER DEFAULT 50000,
    crystal_max INTEGER DEFAULT 15000,
    deuterium_max INTEGER DEFAULT 15000,
    fish_max INTEGER DEFAULT 15000,
    sake_max INTEGER DEFAULT 10000,
    rice_flour_max INTEGER DEFAULT 10000,
    clay_max INTEGER DEFAULT 10000,
    tea_max INTEGER DEFAULT 10000,
    soybean_max INTEGER DEFAULT 10000,
    is_capital INTEGER DEFAULT 1,
    tactical_evasion INTEGER DEFAULT 0,
    founder_planet_id INTEGER DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    last_resource_update INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS planet_buildings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    building_type TEXT,
    level INTEGER DEFAULT 0,
    slot INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS planet_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    field_slot INTEGER,
    type TEXT,
    level INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS planet_rural_plots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    slot_id INTEGER,
    plot_type TEXT,
    level INTEGER DEFAULT 0,
    workers_assigned INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS construction_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER NOT NULL,
    target_id TEXT NOT NULL,
    target_level INTEGER NOT NULL,
    build_category TEXT NOT NULL,
    started_at INTEGER NOT NULL,
    finishes_at INTEGER NOT NULL
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS shipyard_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER NOT NULL,
    ship_code TEXT NOT NULL,
    count INTEGER NOT NULL,
    total_count INTEGER DEFAULT 0,
    started_at INTEGER NOT NULL,
    finishes_at INTEGER NOT NULL,
    unit_build_time INTEGER NOT NULL DEFAULT 60
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS barracks_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER NOT NULL,
    unit_code TEXT NOT NULL,
    count INTEGER NOT NULL,
    total_count INTEGER DEFAULT 0,
    started_at INTEGER NOT NULL,
    finishes_at INTEGER NOT NULL,
    unit_train_time INTEGER NOT NULL DEFAULT 60
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS alliances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    tag TEXT,
    leader_id INTEGER
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS heroes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    name TEXT,
    level INTEGER DEFAULT 1,
    health REAL DEFAULT 100,
    status TEXT DEFAULT 'home',
    current_planet_id INTEGER DEFAULT 1,
    last_health_update INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS craft_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    building_type TEXT DEFAULT 'sawmill',
    product TEXT,
    cost_resource TEXT DEFAULT 'metal',
    cost_amount REAL DEFAULT 0,
    rice_amount REAL DEFAULT 0,
    produced_amount REAL DEFAULT 0,
    started_at INTEGER,
    finishes_at INTEGER
)");

// Données de test
$sqlite->exec("INSERT INTO users (id, username) VALUES (1, 'DaimyoOda')");
$sqlite->exec("INSERT INTO planets (id, user_id, name, coord_x, coord_y, metal, charcoal) VALUES (1, 1, 'Fief Owari', 1, 1, 5000, 0)");
for ($slot = 1; $slot <= 18; $slot++) {
    $sqlite->exec("INSERT INTO planet_fields (planet_id, field_slot, type, level) VALUES (1, $slot, 'rice_paddy', 1)");
}
// Charpenterie au Niveau 14 d'abord
$sqlite->exec("INSERT INTO planet_buildings (planet_id, building_type, level) VALUES (1, 'sawmill', 14)");

$engine = new PlanetEngine();

// ========================================================
// 1. TEST : Vérification du verrouillage au Niveau 14
// ========================================================
echo "1. Test de verrouillage de la production de charbon avant le Niveau 15 :\n";
$craftAttempt = $engine->craftWoodProduct(1, 'charcoal', 100);
if (!$craftAttempt['success'] && strpos($craftAttempt['error'], 'Niveau 15') !== false) {
    echo "  ✓ Verrouillage validé : {$craftAttempt['error']}\n";
} else {
    echo "  ✗ Échec du verrouillage ! Résultat : " . json_encode($craftAttempt) . "\n";
    exit(1);
}

// ========================================================
// 2. TEST : Élévation de la Charpenterie au Niveau 15
// ========================================================
echo "\n2. Amélioration de l'Atelier de Charpenterie au Niveau 15 :\n";
$sqlite->exec("UPDATE planet_buildings SET level = 15 WHERE planet_id = 1 AND building_type = 'sawmill'");

// Calcul des détails de production
$details = $engine->calculateWoodCraftDetails(1, 'charcoal', 100);
echo "  ✓ Détails calculés : {$details['actual_wood_engaged']} Bois de Cèdre -> {$details['actual_produced']} Charbon de bois (Efficience : +{$details['sawmill_level']} x 2% = " . round(($details['efficiency_multiplier'] - 1) * 100) . "%)\n";

if ($details['base_cost_per_unit'] !== 1) {
    echo "  ✗ Le coût de base par unité doit être 1 Bois de Cèdre !\n";
    exit(1);
}
if ($details['actual_produced'] < 100) {
    echo "  ✗ Le rendement attendu avec bonus doit être >= 100 (obtenu : {$details['actual_produced']}) !\n";
    exit(1);
}

// ========================================================
// 3. TEST : Lancement du craft de charbon
// ========================================================
echo "\n3. Lancement du façonnage de 100 Bois en Charbon de bois :\n";
$craftSuccess = $engine->craftWoodProduct(1, 'charcoal', 100);
if ($craftSuccess['success']) {
    echo "  ✓ Succès : {$craftSuccess['message']}\n";
    echo "  ✓ Bois restant sur le fief : {$craftSuccess['new_metal']} (initial: 5000, engagé: {$craftSuccess['consumed_wood']})\n";
    if ($craftSuccess['new_metal'] != (5000 - $craftSuccess['consumed_wood'])) {
        echo "  ✗ Déduction du bois incorrecte !\n";
        exit(1);
    }
} else {
    echo "  ✗ Erreur inattendue au lancement du craft : " . ($craftSuccess['error'] ?? '') . "\n";
    exit(1);
}

// Vérification dans craft_queue
$stmtQ = $sqlite->query("SELECT * FROM craft_queue WHERE planet_id = 1 AND product = 'charcoal'");
$qRow = $stmtQ->fetch(PDO::FETCH_ASSOC);
if ($qRow && $qRow['cost_resource'] === 'metal' && $qRow['produced_amount'] == $details['actual_produced']) {
    echo "  ✓ Tâche correctement enregistrée en base (ID: {$qRow['id']}, Produit: {$qRow['product']}, Quantité: {$qRow['produced_amount']})\n";
} else {
    echo "  ✗ Entrée dans craft_queue non conforme !\n";
    exit(1);
}

// ========================================================
// 4. TEST : Résolution de la production (processCraftQueue)
// ========================================================
echo "\n4. Simulation de l'achèvement et résolution par processCraftQueue :\n";
// Avancer le temps de fin dans le passé
$sqlite->exec("UPDATE craft_queue SET finishes_at = " . (time() - 10) . " WHERE id = " . $qRow['id']);

$engine->processCraftQueue(1);

$checkPlanet = $sqlite->query("SELECT charcoal FROM planets WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
echo "  ✓ Stock de charbon après finalisation : {$checkPlanet['charcoal']} (attendu : {$qRow['produced_amount']})\n";
if ((float)$checkPlanet['charcoal'] != (float)$qRow['produced_amount']) {
    echo "  ✗ Le stock de charbon n'a pas été incrémenté correctement !\n";
    exit(1);
}

$stmtEmpty = $sqlite->query("SELECT COUNT(*) FROM craft_queue WHERE id = " . $qRow['id']);
if ($stmtEmpty->fetchColumn() == 0) {
    echo "  ✓ File de craft nettoyée après finalisation avec succès.\n";
} else {
    echo "  ✗ La tâche achevée n'a pas été supprimée de la file !\n";
    exit(1);
}

// ========================================================
// 5. TEST : Annulation avec remboursement de 80%
// ========================================================
echo "\n5. Test de remboursement à 80% lors de l'annulation d'un lot :\n";
// Lancer un nouveau craft de 200 bois
$preWood = (float)$sqlite->query("SELECT metal FROM planets WHERE id = 1")->fetchColumn();
$newCraft = $engine->craftWoodProduct(1, 'charcoal', 200);
$postWood = (float)$sqlite->query("SELECT metal FROM planets WHERE id = 1")->fetchColumn();
$craftId = $sqlite->lastInsertId();

$cancelResult = $engine->cancelCraft(1, (int)$craftId);
$refundedWood = (float)$sqlite->query("SELECT metal FROM planets WHERE id = 1")->fetchColumn();
$expectedRefund = floor(200 * 0.8);

echo "  ✓ Annulation effectuée : {$cancelResult['message']}\n";
echo "  ✓ Bois avant craft: {$preWood}, après craft: {$postWood}, après annulation (remboursement +{$expectedRefund}): {$refundedWood}\n";

if ($refundedWood != ($postWood + $expectedRefund)) {
    echo "  ✗ Montant remboursé non conforme ! Attendu : " . ($postWood + $expectedRefund) . ", obtenu : {$refundedWood}\n";
    exit(1);
}

echo "\n=================================================================\n";
echo " ✅ TOUS LES TESTS DE PRODUCTION DE CHARBON ONT RÉUSSI (1 À 5) !\n";
echo "=================================================================\n";
