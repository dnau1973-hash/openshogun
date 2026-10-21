<?php
/**
 * Test Automatisé : Visibilité Conditionnelle du Dōjō Impérial & Interdiction d'Annexion de [0 : 0]
 * Fichier : tests/test_imperial_dojo_visibility_and_annexation.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GameConfig.php';
require_once __DIR__ . '/../core/GalaxyEngine.php';
require_once __DIR__ . '/../core/FleetEngine.php';
require_once __DIR__ . '/../core/ImperialDojoEngine.php';

function assertTest(bool $condition, string $title): void {
    if ($condition) {
        echo "  [PASS] " . $title . "\n";
    } else {
        echo "  [FAIL] " . $title . "\n";
        exit(1);
    }
}

echo "========================================================================\n";
echo " TEST : VISIBILITÉ CONDITIONNELLE & NON-ANNEXABILITÉ DU DŌJŌ IMPÉRIAL [0:0]\n";
echo "========================================================================\n\n";

// 1. Initialisation SQLite en mémoire
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$sqlite->sqliteCreateFunction('UNIX_TIMESTAMP', 'time');
$sqlite->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'));
Database::setConnection($sqlite);

// Schéma minimal
$sqlite->exec("CREATE TABLE game_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT,
    setting_type TEXT DEFAULT 'string',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$sqlite->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT,
    alliance_id INTEGER DEFAULT NULL,
    faction TEXT DEFAULT 'oda',
    points INTEGER DEFAULT 100,
    created_at INTEGER DEFAULT 0,
    is_bot INTEGER DEFAULT 0,
    protection_until DATETIME DEFAULT NULL
)");

$sqlite->exec("CREATE TABLE planets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER DEFAULT NULL,
    name TEXT,
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0,
    planet_type TEXT DEFAULT 'colony',
    metal INTEGER DEFAULT 50000,
    crystal INTEGER DEFAULT 50000,
    deuterium INTEGER DEFAULT 50000,
    energy_used INTEGER DEFAULT 0,
    energy_max INTEGER DEFAULT 100,
    metal_max INTEGER DEFAULT 100000,
    crystal_max INTEGER DEFAULT 100000,
    deuterium_max INTEGER DEFAULT 100000,
    is_capital INTEGER DEFAULT 0,
    last_resource_update INTEGER DEFAULT 0,
    created_at INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE planet_units (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    unit_code TEXT,
    count INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE planet_ships (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    ship_code TEXT,
    count INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE fleets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    source_planet_id INTEGER,
    target_planet_id INTEGER DEFAULT NULL,
    target_oasis_id INTEGER DEFAULT NULL,
    mission_type TEXT,
    fleet_composition TEXT,
    cargo_metal INTEGER DEFAULT 0,
    cargo_crystal INTEGER DEFAULT 0,
    cargo_deuterium INTEGER DEFAULT 0,
    departure_time INTEGER,
    arrival_time INTEGER,
    return_time INTEGER,
    status TEXT DEFAULT 'active'
)");

$sqlite->exec("CREATE TABLE authentic_castles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0,
    is_spawned INTEGER DEFAULT 0,
    planet_id INTEGER DEFAULT NULL
)");

$sqlite->exec("CREATE TABLE imperial_dojo (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0,
    controlling_alliance_id INTEGER DEFAULT NULL,
    current_level INTEGER DEFAULT 0,
    target_level INTEGER DEFAULT 1,
    status TEXT DEFAULT 'unclaimed',
    health_points INTEGER DEFAULT 5000000,
    max_health_points INTEGER DEFAULT 5000000,
    stock_wood INTEGER DEFAULT 0,
    stock_granite INTEGER DEFAULT 0,
    stock_metal INTEGER DEFAULT 0,
    stock_rice INTEGER DEFAULT 0,
    stock_beams INTEGER DEFAULT 0,
    stock_tiles INTEGER DEFAULT 0,
    stock_oni_tiles INTEGER DEFAULT 0,
    stock_gold INTEGER DEFAULT 0,
    construction_started_at INTEGER DEFAULT NULL,
    construction_finishes_at INTEGER DEFAULT NULL,
    created_at INTEGER DEFAULT 0,
    updated_at INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE oases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0,
    owner_planet_id INTEGER DEFAULT NULL
)");

$sqlite->exec("CREATE TABLE alliances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    tag TEXT,
    leader_id INTEGER DEFAULT 1
)");

$sqlite->exec("CREATE TABLE construction_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    building_id INTEGER DEFAULT 1,
    target_level INTEGER DEFAULT 1,
    finishes_at INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE planet_buildings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    building_type TEXT,
    level INTEGER DEFAULT 0,
    UNIQUE(planet_id, building_type)
)");

$sqlite->exec("CREATE TABLE planet_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    field_slot INTEGER DEFAULT 1,
    type TEXT DEFAULT 'metal_mine',
    level INTEGER DEFAULT 0,
    UNIQUE(planet_id, field_slot)
)");

$sqlite->exec("CREATE TABLE shipyard_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    unit_code TEXT,
    count INTEGER DEFAULT 0,
    started_at INTEGER DEFAULT 0,
    finishes_at INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE barracks_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    unit_code TEXT,
    count INTEGER DEFAULT 0,
    started_at INTEGER DEFAULT 0,
    finishes_at INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE units (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT,
    speed INTEGER DEFAULT 1000
)");

$sqlite->exec("CREATE TABLE ships (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT,
    speed INTEGER DEFAULT 1000
)");

// Données de test
$sqlite->exec("INSERT INTO users (id, username, alliance_id, faction) VALUES (1, 'Daimyo_Oda', 1, 'oda')");
$sqlite->exec("INSERT INTO alliances (id, name, tag) VALUES (1, 'Clan Oda', 'ODA')");
$sqlite->exec("INSERT INTO planets (id, user_id, name, coord_x, coord_y, metal, crystal, deuterium) VALUES (1, 1, 'Fief Principal', 2, 2, 50000, 50000, 50000)");
$sqlite->exec("INSERT INTO planet_units (planet_id, unit_code, count) VALUES (1, 'colonizer', 5), (1, 'samurai_yari', 100)");

echo "[TEST 1] Raccourci dans le menu 'Mon Empire' (views/partials/header.php)...\n";
$headerContent = file_get_contents(__DIR__ . '/../views/partials/header.php');

assertTest(str_contains($headerContent, 'GameConfig::isImperialDojoUnlocked()'), "Condition GameConfig::isImperialDojoUnlocked() présente dans header.php");
assertTest(str_contains($headerContent, '<?php if ($isDojoUnlocked): ?>'), "Lien Dōjō Impérial conditionné par isDojoUnlocked dans header.php");

echo "\n[TEST 2] Bannière Dōjō Impérial dans l'Alliance (views/alliance.php)...\n";
$allianceContent = file_get_contents(__DIR__ . '/../views/alliance.php');
assertTest(str_contains($allianceContent, 'GameConfig::isImperialDojoUnlocked()'), "Bannière Dōjō Impérial conditionnée dans alliance.php");

echo "\n[TEST 3] Carte (GalaxyEngine) : Tuile [0 : 0] quand le Dōjō est VERROUILLÉ...\n";
GameConfig::set('era_dojo_unlocked', 0);
assertTest(GameConfig::isImperialDojoUnlocked() === false, "Dōjō verrouillé par défaut");

$galaxyEngine = new GalaxyEngine();
$sectorLocked = $galaxyEngine->getSectorMap(0, 0, 1);
assertTest(isset($sectorLocked['planets']['0:0']), "Tuile [0:0] présente dans le secteur");
$tileLocked = $sectorLocked['planets']['0:0'];

assertTest((int)$tileLocked['is_imperial_dojo'] === 0, "is_imperial_dojo = 0 quand le Dōjō est verrouillé");
assertTest((int)$tileLocked['dojo_unlocked'] === 0, "dojo_unlocked = 0 quand le Dōjō est verrouillé");
assertTest(!empty($tileLocked['is_sealed_dojo']), "is_sealed_dojo = 1 pour marquer le sanctuaire scellé");
assertTest(str_contains($tileLocked['planet_name'], 'Terre Sacrée'), "Nom de la tuile = Terre Sacrée Inviolable");
assertTest((int)$tileLocked['is_annexable'] === 0, "is_annexable = 0 (inviolable)");

echo "\n[TEST 4] Carte (GalaxyEngine) : Tuile [0 : 0] quand le Dōjō est ACTIVÉ...\n";
GameConfig::set('era_dojo_unlocked', 1);
assertTest(GameConfig::isImperialDojoUnlocked() === true, "Dōjō déverrouillé");

$sectorUnlocked = $galaxyEngine->getSectorMap(0, 0, 1);
$tileUnlocked = $sectorUnlocked['planets']['0:0'];

assertTest((int)$tileUnlocked['is_imperial_dojo'] === 1, "is_imperial_dojo = 1 quand le Dōjō est activé");
assertTest((int)$tileUnlocked['dojo_unlocked'] === 1, "dojo_unlocked = 1 quand le Dōjō est activé");
assertTest(str_contains($tileUnlocked['planet_name'], 'Dōjō Impérial'), "Nom de la tuile = Dōjō Impérial");
assertTest((int)$tileUnlocked['is_annexable'] === 0, "is_annexable = 0 (non annexable individuellement)");

echo "\n[TEST 5] Contrôles JS dans public/js/galaxy_map.js & views/map.php...\n";
$globalMapJs = file_get_contents(__DIR__ . '/../public/js/galaxy_map.js');
assertTest(str_contains($globalMapJs, 'isDojoActive = (planet && planet.is_imperial_dojo && planet.dojo_unlocked)'), "Classe imperial-dojo-tile strictement conditionnée par dojo_unlocked");
assertTest(str_contains($globalMapJs, 'isDojoSealed'), "Traitement visuel de sanctuaire scellé pour 0:0 quand inactif");

$mapView = file_get_contents(__DIR__ . '/../views/map.php');
assertTest(str_contains($mapView, 'isDojoUnlocked'), "Modale carte vérifie isDojoUnlocked pour 0:0");
assertTest(str_contains($mapView, 'Non Annexable'), "Mention explicite Non Annexable dans la modale");
assertTest(str_contains($mapView, 'Sanctuaire Non Annexable'), "Bouton désactivé Sanctuaire Non Annexable si tentative sur 0:0");

echo "\n[TEST 6] Interdiction formelle de colonisation & annexion via FleetEngine::dispatchMission()...\n";
// Insertion de la planète 2 en [0 : 0]
$sqlite->exec("DELETE FROM planets WHERE id = 2 OR (coord_x = 0 AND coord_y = 0)");
$sqlite->exec("INSERT INTO planets (id, user_id, name, coord_x, coord_y, metal, crystal, deuterium) VALUES (2, NULL, 'Terre Sacrée [0:0]', 0, 0, 0, 0, 0)");

$fleetEngine = new FleetEngine();

// 1. Tentative de colonisation de [0:0] quand le dojo est verrouillé
GameConfig::set('era_dojo_unlocked', 0);
$threwColonizeLocked = false;
try {
    $fleetEngine->dispatchMission(1, 1, 2, 'colonize', ['colonizer' => 1], []);
} catch (Exception $e) {
    $threwColonizeLocked = true;
    assertTest(str_contains($e->getMessage(), 'sanctuaire impérial inviolable') || str_contains($e->getMessage(), 'annexer'), "Rejet colonisation [0:0] : " . $e->getMessage());
}
assertTest($threwColonizeLocked === true, "La mission colonize sur [0:0] est formellement bloquée");

// 2. Tentative de colonisation de [0:0] quand le dojo est débloqué
GameConfig::set('era_dojo_unlocked', 1);
$threwColonizeUnlocked = false;
try {
    $fleetEngine->dispatchMission(1, 1, 2, 'colonize', ['colonizer' => 1], []);
} catch (Exception $e) {
    $threwColonizeUnlocked = true;
    assertTest(str_contains($e->getMessage(), 'sanctuaire impérial inviolable') || str_contains($e->getMessage(), 'annexer'), "Rejet colonisation Dōjō actif : " . $e->getMessage());
}
assertTest($threwColonizeUnlocked === true, "Aucun joueur ne peut annexer [0:0] même quand le Dōjō est activé");

// 3. Tentative de mission ordinaire (attack) sur [0:0]
$threwRaid = false;
try {
    $fleetEngine->dispatchMission(1, 1, 2, 'attack', ['samurai_yari' => 10], []);
} catch (Exception $e) {
    $threwRaid = true;
    assertTest(str_contains($e->getMessage(), 'Garnison Fédérale') || str_contains($e->getMessage(), 'Siège Destructeur'), "Rejet attaque normale sur [0:0] : " . $e->getMessage());
}
assertTest($threwRaid === true, "Les attaques ordinaires sur [0:0] sont interdites");

// 4. Tentative de mission quand le dojo est verrouillé
GameConfig::set('era_dojo_unlocked', 0);
$threwSiegeLocked = false;
try {
    $fleetEngine->dispatchMission(1, 1, 2, 'dojo_siege', ['samurai_yari' => 10], []);
} catch (Exception $e) {
    $threwSiegeLocked = true;
    assertTest(str_contains($e->getMessage(), 'brumes impériales'), "Rejet mouvement quand Dōjō non éveillé : " . $e->getMessage());
}
assertTest($threwSiegeLocked === true, "Aucun mouvement militaire ne peut pénétrer [0:0] tant que le Dōjō n'est pas activé");

echo "\n========================================================================\n";
echo " TOUS LES TESTS DE VISIBILITÉ DU DŌJŌ ET D'INTERDICTION D'ANNEXION SONT OK !\n";
echo "========================================================================\n";
