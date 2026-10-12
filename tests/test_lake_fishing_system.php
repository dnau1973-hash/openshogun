<?php
/**
 * Test unitaire & fonctionnel : Système Halieutique Féodal (Parcelles de Lac, Pêcheurs du Dojo & Poisson)
 * Valide :
 * 1. La non-annexabilité des parcelles de lac (sanctuaires d'eaux vives communautaires)
 * 2. L'unité Pêcheur Féodal (recrutement, statistiques, tier 1)
 * 3. La technologie académique Halieutique Féodale (fishing_tech)
 * 4. La mission d'expédition de pêche (temps fixe 1h + aller-retour, calcul des prises selon le niveau de recherche)
 * 5. Le déchargement et stockage du poisson dans le Grenier à Riz Fortifié (Kura)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/BarracksEngine.php';
require_once __DIR__ . '/../core/ResearchService.php';
require_once __DIR__ . '/../core/OasisEngine.php';
require_once __DIR__ . '/../core/FleetEngine.php';
require_once __DIR__ . '/../core/GalaxyEngine.php';

echo "=================================================================\n";
echo " TEST : SYSTÈME HALIEUTIQUE, PÊCHEURS & PARCELLES DE LAC\n";
echo "=================================================================\n\n";

$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Schéma minimal pour le test
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
    fish REAL DEFAULT 0,
    clay REAL DEFAULT 0,
    tea REAL DEFAULT 0,
    soybean REAL DEFAULT 0,
    sake REAL DEFAULT 0,
    rice_flour REAL DEFAULT 0,
    population INTEGER DEFAULT 100,
    contentment INTEGER DEFAULT 100,
    energy_used INTEGER DEFAULT 0,
    energy_max INTEGER DEFAULT 20,
    metal_max INTEGER DEFAULT 15000,
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
    wooden_beams REAL DEFAULT 0,
    wooden_beams_max INTEGER DEFAULT 10000,
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
    structure_type TEXT,
    level INTEGER DEFAULT 1,
    max_level INTEGER DEFAULT 30,
    pos_x REAL DEFAULT 50,
    pos_y REAL DEFAULT 50,
    workers_assigned INTEGER DEFAULT 2,
    prod_hourly REAL DEFAULT 0
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS units (
    code TEXT PRIMARY KEY,
    name TEXT,
    faction TEXT DEFAULT 'all',
    tier INTEGER DEFAULT 1,
    icon TEXT DEFAULT '',
    image TEXT DEFAULT '',
    metal_cost INTEGER DEFAULT 60,
    crystal_cost INTEGER DEFAULT 40,
    deuterium_cost INTEGER DEFAULT 50,
    rice_flour_cost INTEGER DEFAULT 0,
    attack INTEGER DEFAULT 5,
    def_infantry INTEGER DEFAULT 10,
    def_mech INTEGER DEFAULT 10,
    speed INTEGER DEFAULT 14,
    cargo_capacity INTEGER DEFAULT 100,
    base_train_time INTEGER DEFAULT 45,
    description TEXT
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS ships (
    code TEXT PRIMARY KEY,
    name TEXT,
    faction TEXT DEFAULT 'all',
    speed INTEGER DEFAULT 10,
    cargo_capacity INTEGER DEFAULT 100
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS planet_units (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    unit_code TEXT,
    count INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS planet_ships (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    ship_code TEXT,
    count INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS researches (
    code TEXT PRIMARY KEY,
    name TEXT,
    metal_cost INTEGER,
    crystal_cost INTEGER,
    deuterium_cost INTEGER,
    base_time INTEGER,
    description TEXT
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS user_researches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    research_code TEXT,
    level INTEGER DEFAULT 0
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
    res_wood INTEGER DEFAULT 1500,
    res_stone INTEGER DEFAULT 1500,
    res_rice INTEGER DEFAULT 1500,
    res_max INTEGER DEFAULT 5000,
    last_loot_time INTEGER DEFAULT 0,
    owner_planet_id INTEGER DEFAULT NULL,
    annexed_at INTEGER DEFAULT NULL
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS oasis_units (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    oasis_id INTEGER,
    unit_code TEXT,
    count INTEGER DEFAULT 0,
    is_wild INTEGER DEFAULT 1
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS fleet_missions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    source_planet_id INTEGER,
    target_planet_id INTEGER DEFAULT NULL,
    target_oasis_id INTEGER DEFAULT NULL,
    adventure_id INTEGER DEFAULT NULL,
    mission_type TEXT,
    fleet_data TEXT,
    cargo_data TEXT,
    has_hero INTEGER DEFAULT 0,
    departure_time INTEGER,
    arrival_time INTEGER,
    return_time INTEGER,
    status TEXT DEFAULT 'en_route'
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER,
    receiver_id INTEGER,
    subject TEXT,
    body TEXT,
    is_read INTEGER DEFAULT 0,
    deleted_by_receiver INTEGER DEFAULT 0,
    deleted_by_sender INTEGER DEFAULT 0,
    created_at INTEGER
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
    unit_build_time INTEGER NOT NULL
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS barracks_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER NOT NULL,
    unit_code TEXT NOT NULL,
    count INTEGER NOT NULL,
    total_count INTEGER DEFAULT 0,
    started_at INTEGER NOT NULL,
    finishes_at INTEGER NOT NULL,
    unit_train_time INTEGER NOT NULL
)");

$sqlite->exec("CREATE TABLE IF NOT EXISTS craft_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER NOT NULL,
    product TEXT NOT NULL,
    rice_amount REAL NOT NULL,
    produced_amount INTEGER NOT NULL,
    started_at INTEGER NOT NULL,
    finishes_at INTEGER NOT NULL
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

$sqlite->sqliteCreateFunction('least', function($a, $b) { return min($a, $b); }, 2);
$sqlite->sqliteCreateFunction('greatest', function($a, $b) { return max($a, $b); }, 2);
$sqlite->sqliteCreateFunction('UNIX_TIMESTAMP', function() { return time(); }, 0);

Database::setConnection($sqlite);

// Insérer données de base
$sqlite->exec("INSERT INTO users (id, username, faction) VALUES (1, 'Nobunaga', 'terran')");
$sqlite->exec("INSERT INTO planets (id, user_id, name, coord_x, coord_y, fish, fish_max, deuterium_max) VALUES (1, 1, 'Château Gifu', 5, 5, 50, 15000, 15000)");

// Pré-remplir les 18 parcelles et bâtiments pour éviter le déclenchement de VillageFieldGenerator sur SQLite
for ($slot = 1; $slot <= 18; $slot++) {
    $sqlite->exec("INSERT INTO planet_fields (planet_id, field_slot, type, level) VALUES (1, $slot, 'rice_paddy', 1)");
}
$sqlite->exec("INSERT INTO planet_buildings (planet_id, building_type, level) VALUES (1, 'tank', 1)");
$sqlite->exec("INSERT INTO planet_buildings (planet_id, building_type, level) VALUES (1, 'storage', 1)");

// 1. Enregistrer l'unité Pêcheur et la Recherche dans la base
$sqlite->exec("INSERT INTO units (code, name, faction, tier, speed, cargo_capacity, base_train_time) VALUES ('pecheur', 'Pêcheur Féodal (Ryōshi)', 'all', 1, 14, 100, 45)");
$sqlite->exec("INSERT INTO researches (code, name, metal_cost, crystal_cost, deuterium_cost, base_time, description) VALUES ('fishing_tech', 'Halieutique Féodale', 300, 250, 400, 120, 'Augmente les prises de poisson.')");
$sqlite->exec("INSERT INTO planet_units (planet_id, unit_code, count) VALUES (1, 'pecheur', 50)");

// =========================================================================
// TEST 1 : Vérification des Métadonnées & Unités au Dojo
// =========================================================================
echo "1. Vérification de l'Unité Pêcheur & Technologie Académique :\n";
$meta = ResearchService::getTechMetadata('fishing_tech');
assert($meta['icon'] === '🐟', "L'icône de fishing_tech doit être 🐟");
assert($meta['category'] === 'logistics', "La catégorie de fishing_tech doit être logistics");
echo "  ✓ Métadonnées Académie validées : Catégorie '{$meta['category_label']}', Icône {$meta['icon']}\n";

$barracksEngine = new BarracksEngine();
$sqlite->exec("INSERT INTO planet_buildings (planet_id, building_type, level) VALUES (1, 'barracks', 2)");
$availableUnits = $barracksEngine->getAvailableUnits(1, 'terran');
$pecheurFound = false;
foreach ($availableUnits as $u) {
    if ($u['code'] === 'pecheur') {
        $pecheurFound = true;
        assert($u['can_train'] === true, "Le pêcheur doit être entraînable avec Caserne Niv 1+");
        assert($u['cargo_capacity'] === 100, "Le pêcheur doit avoir une capacité d'emport de 100");
    }
}
assert($pecheurFound, "L'unité 'pecheur' doit être disponible dans les unités entraînables du Dojo");
echo "  ✓ Unité Pêcheur Féodal présente au Dojo et recrutable (Tier 1)\n";

// =========================================================================
// TEST 2 : Interdiction d'Annexion d'un Lac (Sanctuaire Halieutique)
// =========================================================================
echo "\n2. Test de la non-annexabilité des parcelles de lac :\n";
$sqlite->exec("INSERT INTO oases (id, coord_x, coord_y, oasis_type, name, bonus_rice) VALUES (42, 10, 10, 'lake_rice_50', 'Grand Lac Biwa', 50)");
$oasisEngine = new OasisEngine();
$annexResult = $oasisEngine->annexOasis(42, 1);
assert($annexResult['success'] === false, "L'annexion d'un lac doit échouer");
assert(str_contains($annexResult['message'], 'Les parcelles de lac sont des sanctuaires'), "Le message d'erreur doit expliciter le statut d'eaux libres non annexable");
echo "  ✓ Annexion du lac refusée avec succès : {$annexResult['message']}\n";

// =========================================================================
// TEST 3 : Expédition de Pêche vers un Lac
// =========================================================================
echo "\n3. Test du déploiement d'une expédition de pêche :\n";
$fleetEngine = new FleetEngine();

// Test 3a: Refus si pas de pêcheurs
$fleetSansPecheur = ['pecheur' => 0];
try {
    $fleetEngine->dispatchMission(1, 1, null, 'fishing', $fleetSansPecheur, ['metal'=>0,'crystal'=>0,'deuterium'=>0], 42);
    assert(false, "Une mission sans pêcheurs aurait dû échouer");
} catch (Exception $e) {
    assert(str_contains($e->getMessage(), 'Pêcheur Féodal'), "Doit réclamer des Pêcheurs Féodaux");
    echo "  ✓ Mission refusée sans pêcheur : {$e->getMessage()}\n";
}

// Test 3b: Refus si la destination n'est pas un lac
$sqlite->exec("INSERT INTO oases (id, coord_x, coord_y, oasis_type, name, bonus_wood) VALUES (43, 20, 20, 'forest_wood_50', 'Forêt de Cèdres', 50)");
try {
    $fleetEngine->dispatchMission(1, 1, null, 'fishing', ['pecheur' => 10], ['metal'=>0,'crystal'=>0,'deuterium'=>0], 43);
    assert(false, "Pêcher dans une forêt aurait dû échouer");
} catch (Exception $e) {
    assert(str_contains($e->getMessage(), 'lac'), "Doit refuser une destination non-lac");
    echo "  ✓ Mission refusée sur cible terrestre : {$e->getMessage()}\n";
}

// Test 3c: Succès sur lac avec 25 pêcheurs
$dispatchRes = $fleetEngine->dispatchMission(1, 1, null, 'fishing', ['pecheur' => 25], ['metal'=>0,'crystal'=>0,'deuterium'=>0], 42);
assert($dispatchRes['success'] === true, "La mission de pêche doit être déployée");
$missionId = (int)$dispatchRes['mission_id'];
echo "  ✓ Expédition de pêche #{$missionId} déployée avec succès vers le lac !\n";

// Vérifier que la durée inclut 1 heure fixe de pêche
$missionRow = $sqlite->query("SELECT * FROM fleet_missions WHERE id = {$missionId}")->fetch(PDO::FETCH_ASSOC);
$travelTime = $missionRow['arrival_time'] - $missionRow['departure_time'];
$totalMissionTime = $missionRow['return_time'] - $missionRow['departure_time'];
$fishingTime = $totalMissionTime - (2 * $travelTime);
assert($fishingTime === 3600, "Le temps de pêche fixe sur le lac doit être d'exactement 3600 secondes (1 heure)");
echo "  ✓ Temps de pêche fixe validé : {$fishingTime}s (1h sur le lac) + trajet aller/retour\n";

// =========================================================================
// TEST 4 : Résolution de l'Arrivée & Rendement selon la Recherche Académique
// =========================================================================
echo "\n4. Test du rendement de pêche avec recherche Académique (Niveau 2) :\n";
// Définir la recherche fishing_tech au niveau 2 (+50% de prises)
$sqlite->exec("INSERT INTO user_researches (user_id, research_code, level) VALUES (1, 'fishing_tech', 2)");

// Simuler l'arrivée sur le lac
$missionRow['status'] = 'en_route';
$missionRow['arrival_time'] = time() - 10;
$sqlite->exec("UPDATE fleet_missions SET arrival_time = " . (time() - 10) . " WHERE id = {$missionId}");
$fleetEngine->processFleetMissions();

$missionAfterArrival = $sqlite->query("SELECT * FROM fleet_missions WHERE id = {$missionId}")->fetch(PDO::FETCH_ASSOC);
assert($missionAfterArrival['status'] === 'returning', "La flotte doit passer en statut 'returning' après avoir pêché");

$cargo = json_decode($missionAfterArrival['cargo_data'], true);
$fishCaught = (int)($cargo['fish'] ?? 0);
// 25 pêcheurs × 20 de base × (1 + 0.25 × 2) = 25 × 20 × 1.5 = 750 poissons
$expectedFish = (int)round(25 * 20 * (1 + (0.25 * 2)));
assert($fishCaught === $expectedFish, "Prises attendues : {$expectedFish}, obtenues : {$fishCaught}");
echo "  ✓ Prises calculées et stockées dans la cargaison : {$fishCaught} poissons frais (+50% via Académie Niv. 2)\n";

// =========================================================================
// TEST 5 : Retour au Port & Stockage dans le Grenier à Riz Fortifié (Kura)
// =========================================================================
echo "\n5. Test du déchargement et stockage dans le Grenier à Riz Fortifié :\n";
$sqlite->exec("UPDATE fleet_missions SET return_time = " . (time() - 10) . " WHERE id = {$missionId}");
$fleetEngine->processFleetMissions();

$missionAfterReturn = $sqlite->query("SELECT * FROM fleet_missions WHERE id = {$missionId}")->fetch(PDO::FETCH_ASSOC);
assert($missionAfterReturn['status'] === 'completed', "La mission doit être complétée");

$planetAfter = $sqlite->query("SELECT fish, fish_max FROM planets WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
// Stock initial était de 50 + 750 = 800
assert((int)$planetAfter['fish'] === 800, "Le stock de poisson dans le grenier doit être 800 (initial 50 + 750 rapportés), obtenu : {$planetAfter['fish']}");
echo "  ✓ Poisson débarqué et stocké dans le Grenier à Riz : {$planetAfter['fish']} / {$planetAfter['fish_max']}\n";

// Vérifier notification
$msg = $sqlite->query("SELECT * FROM messages WHERE receiver_id = 1 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assert($msg !== false && str_contains($msg['subject'], 'Expédition de Pêche'), "Le joueur doit avoir reçu un message de rapport de pêche");
echo "  ✓ Rapport féodal de retour de pêche transmis au Daimyō : '{$msg['subject']}'\n";

// =========================================================================
// TEST 6 : Calcul de la Capacité du Grenier à Riz Fortifié (PlanetEngine)
// =========================================================================
echo "\n6. Test de la capacité du Grenier à Riz (b_tank) pour le poisson :\n";
$sqlite->exec("UPDATE planet_buildings SET level = 3 WHERE planet_id = 1 AND building_type = 'tank'");
$planetEngine = new PlanetEngine();
$updatedPlanet = $planetEngine->updatePlanet(1);
$expectedTankMax = (int)(15000 * pow(1.5, 3));
assert((int)$updatedPlanet['fish_max'] === $expectedTankMax, "La capacité maximale de poisson doit être alignée sur le niveau du Grenier à Riz Fortifié (Niv 3 = {$expectedTankMax})");
echo "  ✓ Plafond de stockage poisson indexé sur le Grenier à Riz Fortifié (Niv. 3) : {$updatedPlanet['fish_max']} vivres\n";

echo "\n=================================================================\n";
echo " ✅ TOUS LES TESTS HALIEUTIQUES (1 À 6) ONT RÉUSSI AVEC SUCCÈS !\n";
echo "=================================================================\n";
