<?php
/**
 * Banc de Tests : Rendu Amélioré & Épuré de la Carte des Provinces
 * OpenShogun - Vérification des 8 tuiles harmonisées, CSS d'allègement et GalaxyEngine
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GalaxyEngine.php';
require_once __DIR__ . '/../core/GameConfig.php';

function assertTest(bool $condition, string $message): void {
    if ($condition) {
        echo "  [PASS] {$message}\n";
    } else {
        echo "  [FAIL] {$message}\n";
        exit(1);
    }
}

echo "========================================================================\n";
echo " TEST : RENDU AMÉLIORÉ ET ÉPURÉ DE LA CARTE DES PROVINCES (MAP V2)\n";
echo "========================================================================\n\n";

// Configuration SQLite en mémoire autonome
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
Database::setConnection($sqlite);

// Schéma minimal pour GalaxyEngine
$sqlite->exec("CREATE TABLE IF NOT EXISTS game_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT,
    setting_type TEXT DEFAULT 'string',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT DEFAULT 'Joueur',
    faction TEXT DEFAULT 'terran',
    points INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    protection_until DATETIME NULL,
    is_bot INTEGER DEFAULT 0,
    alliance_id INTEGER NULL
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS alliances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT DEFAULT 'Alliance',
    tag TEXT DEFAULT 'ALL'
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS planets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL,
    name TEXT DEFAULT 'Fief',
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0,
    planet_type TEXT DEFAULT 'terran',
    metal REAL DEFAULT 1000,
    crystal REAL DEFAULT 1000,
    deuterium REAL DEFAULT 1000
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS authentic_castles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0,
    is_spawned INTEGER DEFAULT 0,
    planet_id INTEGER DEFAULT NULL,
    castle_name TEXT DEFAULT 'Donjon',
    kanji TEXT DEFAULT '城',
    classification TEXT DEFAULT 'Authentique',
    province TEXT DEFAULT 'Yamashiro',
    historical_builder TEXT DEFAULT 'Oda',
    icon TEXT DEFAULT '🏯'
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS imperial_dojo (
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

$sqlite->exec("CREATE TABLE IF NOT EXISTS oases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT DEFAULT 'Oasis',
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0,
    oasis_type TEXT DEFAULT 'forest',
    bonus_wood INTEGER DEFAULT 25,
    bonus_stone INTEGER DEFAULT 0,
    bonus_rice INTEGER DEFAULT 0,
    owner_planet_id INTEGER NULL,
    owner_user_id INTEGER NULL
)");

echo "[TEST 1] Vérification physique des 8 nouvelles tuiles dans public/assets/map/...\n";
$expectedTiles = [
    'tile_plains.jpg'           => 'Plaines',
    'tile_forest.jpg'           => 'Forêt',
    'tile_mountain.jpg'         => 'Montagnes',
    'tile_hills.jpg'            => 'Collines',
    'tile_lake.jpg'             => 'Lac / Eaux',
    'tile_village.jpg'          => 'Fief Minka',
    'tile_authentic_castle.jpg' => 'Donjon Féodal',
    'tile_imperial_dojo.jpg'    => 'Dōjō Impérial'
];

foreach ($expectedTiles as $filename => $label) {
    $path = __DIR__ . '/../public/assets/map/' . $filename;
    assertTest(file_exists($path), "Tuile présente : {$filename} ({$label})");
    $size = filesize($path);
    assertTest($size > 50000, "Taille HD de {$filename} : " . round($size / 1024) . " Ko (haute résolution)");
}

echo "\n[TEST 2] Vérification des URLs et cache busters dans GalaxyEngine::getSectorMap()...\n";
$galaxyEngine = new GalaxyEngine();
$sector = $galaxyEngine->getSectorMap(0, 0, 1);

assertTest(!empty($sector['terrains']), "Secteur contient des terrains générés");
$plainsFound = false;
foreach ($sector['terrains'] as $coord => $t) {
    assertTest(str_contains($t['img'], '?v=3'), "L'image de terrain {$coord} utilise le cache buster v=3");
    if ($t['type'] === 'plains') $plainsFound = true;
}
assertTest($plainsFound === true, "Tuiles de plaines présentes");

echo "\n[TEST 3] Vérification de l'harmonie et des styles épurés dans public/css/style.css...\n";
$css = file_get_contents(__DIR__ . '/../public/css/style.css');

assertTest(str_contains($css, 'background: #b0cb78;'), "Fond du viewport et canevas unifié à #b0cb78");
assertTest(!str_contains($css, 'border: 1px dashed rgba(135, 170, 75, 0.45);'), "Suppression des anciens pointillés dashed disgracieux");
assertTest(str_contains($css, 'border: 0.5px solid rgba(0, 0, 0, 0.04);'), "Micro-séparation subtile appliquée aux tuiles");
assertTest(str_contains($css, '.galaxy-tile:hover .tile-coords'), "Coordonnées discrètes par défaut qui s'illuminent au survol");
assertTest(str_contains($css, 'border-radius: 999px;'), "Badges d'oasis en micro-pastilles douces");

echo "\n[TEST 4] Vérification de la légende et des vues (views/map.php)...\n";
$mapView = file_get_contents(__DIR__ . '/../views/map.php');

assertTest(str_contains($mapView, 'tile_plains.jpg?v=3'), "Légende map.php : Plaines pointe sur v=3");
assertTest(str_contains($mapView, 'tile_forest.jpg?v=3'), "Légende map.php : Forêt pointe sur v=3");
assertTest(str_contains($mapView, 'tile_mountain.jpg?v=3'), "Légende map.php : Montagnes pointe sur v=3");
assertTest(str_contains($mapView, 'tile_lake.jpg?v=3'), "Légende map.php : Lac pointe sur v=3");
assertTest(str_contains($mapView, 'tile_village.jpg?v=3'), "Légende map.php : Fief pointe sur v=3");
assertTest(str_contains($mapView, 'tile_authentic_castle.jpg?v=3'), "Légende map.php : Donjon pointe sur v=3");
assertTest(str_contains($mapView, 'tile_imperial_dojo.jpg?v=3'), "Légende map.php : Dōjō pointe sur v=3");

echo "\n[TEST 5] Vérification du contrôleur JS (public/js/galaxy_map.js)...\n";
$mapJs = file_get_contents(__DIR__ . '/../public/js/galaxy_map.js');
assertTest(str_contains($mapJs, 'tile_plains.jpg?v=3'), "galaxy_map.js : fallback sur v=3");

echo "\n========================================================================\n";
echo " TOUS LES TESTS DU RENDU AMÉLIORÉ DE LA CARTE SONT 100% OK !\n";
echo "========================================================================\n";
