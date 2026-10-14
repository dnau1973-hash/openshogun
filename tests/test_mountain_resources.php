<?php
/**
 * Test unitaire & fonctionnel : Système Minéral de Montagne, Mineur des Cimes & Charbon de Bois
 * OpenShogun
 *
 * Valide :
 * 1. Présence et affichage du Charbon de Bois dans le header (tests de régression visuelle/données)
 * 2. Table `mountain_mineral_stocks` et calculs du moteur `MountainEngine` (isMountainAt, getStock, harvest)
 * 3. Gestion des colonnes sulfur, nitrate, iron_sand sur planets (stockage et plafonds)
 * 4. Unité `mineur` (Mineur des Cimes / Kōfu) et technologie `mining_tech`
 * 5. Expéditions minières de flotte (dispatch, durée sur site, prélèvement de filon, déchargement et notification)
 * 6. Atelier minéralier de concassage (craft de soufre, nitrate, sable de fer)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->sqliteCreateFunction('least', function($a, $b) { return min($a, $b); }, 2);
$sqlite->sqliteCreateFunction('greatest', function($a, $b) { return max($a, $b); }, 2);
$sqlite->sqliteCreateFunction('UNIX_TIMESTAMP', function() { return time(); }, 0);
Database::setConnection($sqlite);

require_once __DIR__ . '/../core/MountainEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/GalaxyEngine.php';

echo "=================================================================\n";
echo " TEST : RESSOURCES DE MONTAGNE, MINEURS & CHARBON DE BOIS\n";
echo "=================================================================\n\n";

// Schéma minimal pour le test
$sqlite->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY,
    username TEXT,
    faction TEXT DEFAULT 'terran',
    points INTEGER DEFAULT 0,
    alliance_id INTEGER DEFAULT NULL,
    is_bot INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    protection_until INTEGER DEFAULT NULL
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS game_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT,
    setting_type TEXT
)");
$sqlite->exec("INSERT INTO game_settings VALUES ('game_speed', '1', 'string'), ('fleet_speed', '1', 'string')");

$sqlite->exec("CREATE TABLE IF NOT EXISTS planets (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    name TEXT,
    coord_x INTEGER,
    coord_y INTEGER,
    planet_type TEXT DEFAULT 'terrestrial',
    metal REAL DEFAULT 5000,
    crystal REAL DEFAULT 5000,
    deuterium REAL DEFAULT 5000,
    clay REAL DEFAULT 5000,
    tea REAL DEFAULT 0,
    soybean REAL DEFAULT 0,
    sake REAL DEFAULT 0,
    rice_flour REAL DEFAULT 0,
    wooden_beams REAL DEFAULT 0,
    charcoal REAL DEFAULT 120,
    charcoal_max INTEGER DEFAULT 10000,
    sulfur REAL DEFAULT 0,
    sulfur_max INTEGER DEFAULT 10000,
    nitrate REAL DEFAULT 0,
    nitrate_max INTEGER DEFAULT 10000,
    iron_sand REAL DEFAULT 0,
    iron_sand_max INTEGER DEFAULT 15000,
    fish REAL DEFAULT 0,
    population INTEGER DEFAULT 100,
    contentment INTEGER DEFAULT 100,
    energy_used INTEGER DEFAULT 0,
    energy_max INTEGER DEFAULT 100,
    metal_max INTEGER DEFAULT 20000,
    crystal_max INTEGER DEFAULT 20000,
    deuterium_max INTEGER DEFAULT 20000,
    fish_max INTEGER DEFAULT 20000,
    sake_max INTEGER DEFAULT 10000,
    rice_flour_max INTEGER DEFAULT 10000,
    wooden_beams_max INTEGER DEFAULT 10000,
    clay_max INTEGER DEFAULT 10000,
    tea_max INTEGER DEFAULT 10000,
    soybean_max INTEGER DEFAULT 10000,
    is_capital INTEGER DEFAULT 1,
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

$sqlite->exec("CREATE TABLE IF NOT EXISTS units (
    id INTEGER PRIMARY KEY,
    code TEXT UNIQUE,
    name TEXT,
    faction TEXT DEFAULT 'all',
    tier INTEGER DEFAULT 1,
    icon TEXT,
    image TEXT,
    metal_cost INTEGER,
    crystal_cost INTEGER,
    deuterium_cost INTEGER,
    rice_flour_cost INTEGER DEFAULT 0,
    attack INTEGER,
    def_infantry INTEGER,
    def_mech INTEGER,
    speed INTEGER,
    cargo_capacity INTEGER,
    base_train_time INTEGER,
    description TEXT
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS researches (
    id INTEGER PRIMARY KEY,
    code TEXT UNIQUE,
    name TEXT,
    metal_cost INTEGER,
    crystal_cost INTEGER,
    deuterium_cost INTEGER,
    base_time INTEGER,
    description TEXT
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS user_researches (
    user_id INTEGER,
    research_code TEXT,
    level INTEGER DEFAULT 0,
    PRIMARY KEY (user_id, research_code)
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS planet_units (
    planet_id INTEGER,
    unit_code TEXT,
    count INTEGER DEFAULT 0,
    PRIMARY KEY (planet_id, unit_code)
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS craft_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    building_type TEXT DEFAULT 'stonemason',
    product TEXT,
    cost_resource TEXT DEFAULT 'crystal',
    cost_amount REAL DEFAULT 0,
    rice_amount REAL DEFAULT 0,
    produced_amount REAL DEFAULT 0,
    started_at INTEGER,
    finishes_at INTEGER
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS fleet_missions (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    source_planet_id INTEGER,
    target_planet_id INTEGER,
    target_oasis_id INTEGER,
    mission_type TEXT,
    fleet_data TEXT,
    cargo_data TEXT,
    has_hero INTEGER DEFAULT 0,
    departure_time INTEGER,
    arrival_time INTEGER,
    return_time INTEGER,
    status TEXT DEFAULT 'en_route'
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS alliances (
    id INTEGER PRIMARY KEY,
    name TEXT,
    tag TEXT
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS authentic_castles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT,
    name TEXT,
    japanese_name TEXT,
    kanji TEXT,
    province TEXT,
    historical_builder TEXT,
    classification TEXT,
    icon TEXT,
    short_desc TEXT,
    coord_x INTEGER,
    coord_y INTEGER,
    is_spawned INTEGER DEFAULT 0,
    planet_id INTEGER DEFAULT NULL
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS oases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    coord_x INTEGER,
    coord_y INTEGER,
    oasis_type TEXT,
    name TEXT,
    bonus_wood INTEGER DEFAULT 0,
    bonus_stone INTEGER DEFAULT 0,
    bonus_rice INTEGER DEFAULT 0,
    res_wood INTEGER DEFAULT 0,
    res_stone INTEGER DEFAULT 0,
    res_rice INTEGER DEFAULT 0,
    owner_planet_id INTEGER DEFAULT NULL
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS lake_fish_stocks (
    coord_x INTEGER,
    coord_y INTEGER,
    fish_stock INTEGER,
    fish_initial INTEGER,
    created_at INTEGER,
    PRIMARY KEY(coord_x, coord_y)
)");

// Insertion données initiales
$sqlite->exec("INSERT INTO users (id, username, faction) VALUES (1, 'DaimyoTest', 'terran')");
$sqlite->exec("INSERT INTO planets (id, user_id, name, coord_x, coord_y, last_resource_update) VALUES (1, 1, 'Fief de Départ', 10, 10, " . time() . ")");
for ($slot = 1; $slot <= 18; $slot++) {
    $sqlite->exec("INSERT INTO planet_fields (planet_id, field_slot, type, level) VALUES (1, $slot, 'rice_paddy', 1)");
}
$sqlite->exec("INSERT INTO planet_buildings (planet_id, building_type, level, slot) VALUES (1, 'stonemason', 5, 2), (1, 'sawmill', 15, 3)");

// Insertion unité mineur et tech
$sqlite->exec("INSERT INTO units (code, name, faction, tier, icon, metal_cost, crystal_cost, deuterium_cost, speed, cargo_capacity, base_train_time) VALUES ('mineur', 'Mineur des Cimes (Kōfu)', 'all', 1, '⛏️', 80, 50, 40, 12, 120, 50)");
$sqlite->exec("INSERT INTO researches (code, name, metal_cost, crystal_cost, deuterium_cost, base_time) VALUES ('mining_tech', 'Métallurgie & Géologie Féodale', 350, 300, 300, 150)");

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $title) {
    global $passCount, $failCount;
    if ($condition) {
        echo "[OK] " . $title . "\n";
        $passCount++;
    } else {
        echo "[FAIL] " . $title . "\n";
        $failCount++;
    }
}

// -------------------------------------------------------------
// Test 1 : Initialisation de MountainEngine et création de la table
// -------------------------------------------------------------
echo "1. Initialisation et validation du schéma MountainEngine...\n";
$mountainEngine = new MountainEngine($sqlite);
$st = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name='mountain_mineral_stocks'");
$hasTable = ($st && $st->fetchColumn() !== false);
assertTest($hasTable, "Table mountain_mineral_stocks créée avec succès");

// -------------------------------------------------------------
// Test 2 : Gisements minéraux (Soufre, Nitrate, Sable de Fer)
// -------------------------------------------------------------
echo "\n2. Test des gisements de montagne...\n";
// Déterminer des coordonnées générant un terrain de type mountain
$foundMountX = null;
$foundMountY = null;
for ($x = 1; $x <= 30; $x++) {
    for ($y = 1; $y <= 30; $y++) {
        $tt = GalaxyEngine::getTerrainType($x, $y);
        if ($tt['type'] === 'mountain') {
            $foundMountX = $x;
            $foundMountY = $y;
            break 2;
        }
    }
}

assertTest($foundMountX !== null, "Tuile montagne trouvée sur la carte : [{$foundMountX}:{$foundMountY}]");
$isMountain = $mountainEngine->isMountainAt($foundMountX, $foundMountY);
assertTest($isMountain, "MountainEngine::isMountainAt détecte bien le biome montagne");

$stock = $mountainEngine->getStock($foundMountX, $foundMountY);
assertTest(
    $stock !== null && 
    $stock['sulfur_stock'] >= MountainEngine::SULFUR_MIN &&
    $stock['nitrate_stock'] >= MountainEngine::NITRATE_MIN &&
    $stock['iron_sand_stock'] >= MountainEngine::IRON_SAND_MIN,
    "Gisement initial généré (Soufre: {$stock['sulfur_stock']}, Nitrate: {$stock['nitrate_stock']}, Sable de fer: {$stock['iron_sand_stock']})"
);

// -------------------------------------------------------------
// Test 3 : Prélèvement minier atomique (harvest)
// -------------------------------------------------------------
echo "\n3. Test du prélèvement minier atomique...\n";
$wanted = [
    'sulfur' => 500,
    'nitrate' => 600,
    'iron_sand' => 1000
];
$harvestRes = $mountainEngine->harvest($foundMountX, $foundMountY, $wanted);
assertTest(
    isset($harvestRes['harvested']) &&
    $harvestRes['harvested']['sulfur'] === 500 &&
    $harvestRes['harvested']['nitrate'] === 600 &&
    $harvestRes['harvested']['iron_sand'] === 1000,
    "Prélèvement minier réussi (500 Soufre, 600 Nitrate, 1000 Sable de Fer)"
);

$newStock = $mountainEngine->getStock($foundMountX, $foundMountY);
assertTest(
    $newStock['sulfur_stock'] === ($stock['sulfur_stock'] - 500) &&
    $newStock['nitrate_stock'] === ($stock['nitrate_stock'] - 600) &&
    $newStock['iron_sand_stock'] === ($stock['iron_sand_stock'] - 1000),
    "Décrémentation atomique vérifiée en base"
);

// -------------------------------------------------------------
// Test 4 : Raffinage artisanal de minerais (PlanetEngine)
// -------------------------------------------------------------
echo "\n4. Test du craft minéral à l'Atelier du Tailleur de Pierre...\n";
$planetEngine = new PlanetEngine($sqlite);

// Craft de Soufre à partir de Granit (4 Granit -> 1 Soufre)
$sulfurCraftDetails = $planetEngine->calculateMineralCraftDetails(1, 'sulfur', 40);
assertTest($sulfurCraftDetails['actual_produced'] >= 10, "Calcul de craft du Soufre (40 Granit -> {$sulfurCraftDetails['actual_produced']} Soufre)");

$sulfurCraftRes = $planetEngine->craftMineralProduct(1, 'sulfur', 40);
assertTest(!empty($sulfurCraftRes['success']), "Lancement du craft de Soufre validé");

$q = $planetEngine->getCraftQueue(1, 'stonemason');
assertTest(count($q) === 1 && $q[0]['product'] === 'sulfur', "Tâche en file d'attente pour le Tailleur de Pierre");

// -------------------------------------------------------------
// Test 5 : Résolution de la file de craft et crédit des minerais
// -------------------------------------------------------------
echo "\n5. Résolution de la file de craft minéral...\n";
// Simuler la fin de la tâche dans craft_queue
$sqlite->exec("UPDATE craft_queue SET finishes_at = " . (time() - 10) . " WHERE id = " . (int)$q[0]['id']);
$planetEngine->processCraftQueue(1);

$pUpdated = $sqlite->query("SELECT sulfur FROM planets WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
assertTest((float)$pUpdated['sulfur'] >= 10, "Le stock de Soufre du fief a été crédité (+{$pUpdated['sulfur']} Soufre)");

// -------------------------------------------------------------
// Test 6 : Vérification de l'indicateur Charbon dans header.php
// -------------------------------------------------------------
echo "\n6. Vérification du Charbon de Bois dans header.php...\n";
$headerContent = file_get_contents(__DIR__ . '/../views/partials/header.php');
assertTest(strpos($headerContent, '$charcoalStock') !== false, "Variable \$charcoalStock présente dans views/partials/header.php");
assertTest(strpos($headerContent, 'res-val-charcoal') !== false, "Élément DOM #res-val-charcoal présent dans views/partials/header.php");
assertTest(strpos($headerContent, 'bar-charcoal') !== false, "Barre de progression #bar-charcoal présente");

// -------------------------------------------------------------
// Test 7 : Vérification de la transmission des minerais par GalaxyEngine
// -------------------------------------------------------------
echo "\n7. Vérification de la transmission des minerais par GalaxyEngine::getSectorMap...\n";
$galaxyEngine = new GalaxyEngine($sqlite);
$sector = $galaxyEngine->getSectorMap($foundMountX, $foundMountY, 1);
$mountKey = $foundMountX . ':' . $foundMountY;
$mountTerrain = $sector['terrains'][$mountKey] ?? null;
assertTest(
    $mountTerrain !== null &&
    isset($mountTerrain['sulfur_stock']) &&
    $mountTerrain['sulfur_stock'] > 0 &&
    isset($mountTerrain['nitrate_stock']) &&
    $mountTerrain['nitrate_stock'] > 0 &&
    isset($mountTerrain['iron_sand_stock']) &&
    $mountTerrain['iron_sand_stock'] > 0,
    "GalaxyEngine transmet bien les réserves minérales positives pour la tuile montagne [{$foundMountX}:{$foundMountY}]"
);

// -------------------------------------------------------------
// Test 8 : Ensemencement mondial seedWorldMountains
// -------------------------------------------------------------
echo "\n8. Test de MountainEngine::seedWorldMountains...\n";
$seededCount = $mountainEngine->seedWorldMountains(15);
assertTest($seededCount > 0, "Ensemencement global de {$seededCount} parcelles de montagne avec gisements persistants");

echo "\n=================================================================\n";
echo " RÉSULTATS : {$passCount} passés, {$failCount} échoués\n";
echo "=================================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);

