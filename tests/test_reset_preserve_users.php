<?php
/**
 * Test Automatisé : Conservation des Comptes Joueurs lors du Reset de l'Univers
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/WorldGenerator.php';
require_once __DIR__ . '/../core/GameConfig.php';

echo "=================================================================\n";
echo " TEST : CONSERVATION DES COMPTES JOUEURS LORS DU RESET MONDE\n";
echo "=================================================================\n\n";

// 1. Initialiser une base SQLite en mémoire pour tester de façon isolée et autonome
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fonctions SQLite de compatibilité MySQL
$sqlite->sqliteCreateFunction('UNIX_TIMESTAMP', fn() => time(), 0);
$sqlite->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'), 0);
$sqlite->sqliteCreateFunction('LEAST', fn(...$args) => min($args));
$sqlite->sqliteCreateFunction('GREATEST', fn(...$args) => max($args));

Database::setConnection($sqlite);

// 2. Schéma minimal pour l'univers
$sqlite->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    email TEXT UNIQUE,
    password_hash TEXT,
    faction TEXT DEFAULT 'terran',
    is_admin INTEGER DEFAULT 0,
    is_moderator INTEGER DEFAULT 0,
    is_bot INTEGER DEFAULT 0,
    points INTEGER DEFAULT 0,
    alliance_id INTEGER DEFAULT NULL,
    protection_until INTEGER DEFAULT NULL,
    gold_coins INTEGER DEFAULT 100,
    imperial_seal_until TEXT DEFAULT NULL,
    last_daily_gold TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    last_active TEXT DEFAULT CURRENT_TIMESTAMP
)");

$sqlite->exec("CREATE TABLE planets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    name TEXT,
    coord_x INTEGER,
    coord_y INTEGER,
    planet_type TEXT DEFAULT 'terrestrial',
    metal REAL DEFAULT 1000,
    crystal REAL DEFAULT 1000,
    deuterium REAL DEFAULT 1000,
    fish REAL DEFAULT 0,
    energy_used REAL DEFAULT 0,
    energy_max REAL DEFAULT 120,
    metal_max REAL DEFAULT 30000,
    crystal_max REAL DEFAULT 30000,
    deuterium_max REAL DEFAULT 30000,
    fish_max REAL DEFAULT 30000,
    is_capital INTEGER DEFAULT 1,
    last_resource_update INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE planet_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    field_slot INTEGER,
    type TEXT,
    level INTEGER DEFAULT 0,
    UNIQUE(planet_id, field_slot)
)");

$sqlite->exec("CREATE TABLE planet_buildings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    building_type TEXT,
    level INTEGER DEFAULT 0,
    slot INTEGER DEFAULT NULL
)");

$sqlite->exec("CREATE TABLE planet_rural_plots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    planet_id INTEGER,
    plot_index INTEGER,
    building_type TEXT,
    level INTEGER DEFAULT 0
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

$sqlite->exec("CREATE TABLE heroes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNIQUE,
    current_planet_id INTEGER,
    name TEXT,
    level INTEGER DEFAULT 1,
    experience INTEGER DEFAULT 0,
    health REAL DEFAULT 100.0,
    status TEXT DEFAULT 'home',
    last_health_update INTEGER DEFAULT 0,
    unassigned_points INTEGER DEFAULT 4,
    stat_strength INTEGER DEFAULT 0,
    stat_offense_bonus INTEGER DEFAULT 0,
    stat_defense_bonus INTEGER DEFAULT 0,
    stat_production INTEGER DEFAULT 0,
    production_type TEXT DEFAULT 'all'
)");

$sqlite->exec("CREATE TABLE hero_adventures (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    coord_x INTEGER,
    coord_y INTEGER,
    name TEXT,
    difficulty TEXT,
    status TEXT
)");

$sqlite->exec("CREATE TABLE game_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT,
    setting_type TEXT,
    updated_at TEXT
)");

$sqlite->exec("CREATE TABLE lake_fish_stocks (
    coord_x INTEGER,
    coord_y INTEGER,
    fish_stock INTEGER,
    fish_initial INTEGER,
    created_at INTEGER,
    PRIMARY KEY(coord_x, coord_y)
)");

$sqlite->exec("CREATE TABLE mountain_mineral_stocks (
    coord_x INTEGER,
    coord_y INTEGER,
    sulfur_stock INTEGER,
    sulfur_initial INTEGER,
    nitrate_stock INTEGER,
    nitrate_initial INTEGER,
    iron_sand_stock INTEGER,
    iron_sand_initial INTEGER,
    created_at INTEGER,
    PRIMARY KEY(coord_x, coord_y)
)");

$sqlite->exec("CREATE TABLE authentic_castles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    coord_x INTEGER,
    coord_y INTEGER,
    is_spawned INTEGER DEFAULT 0,
    planet_id INTEGER DEFAULT NULL
)");

$sqlite->exec("CREATE TABLE oases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    coord_x INTEGER,
    coord_y INTEGER,
    oasis_type TEXT
)");

$sqlite->exec("CREATE TABLE user_weekly_stats (
    user_id INTEGER PRIMARY KEY,
    attack_points INTEGER DEFAULT 0,
    defense_points INTEGER DEFAULT 0,
    raid_resources INTEGER DEFAULT 0,
    start_week_points INTEGER DEFAULT 0
)");

// Créer les tables dynamiques vides pour TRUNCATE / DELETE
$tablesToCreate = [
    'combat_reports', 'messages', 'fleet_missions', 'research_queue',
    'user_researches', 'shipyard_queue', 'barracks_queue', 'construction_queue',
    'craft_queue', 'user_quests', 'hero_inventory', 'oasis_units',
    'support_tickets', 'alliances'
];
foreach ($tablesToCreate as $t) {
    $sqlite->exec("CREATE TABLE IF NOT EXISTS {$t} (id INTEGER PRIMARY KEY AUTOINCREMENT)");
}

// 3. Préparer des comptes joueurs existants avant reset
$passwordHashTakeda = password_hash('ShingenSecret123#', PASSWORD_BCRYPT);
$passwordHashOda = password_hash('NobunagaPass456#', PASSWORD_BCRYPT);

$sqlite->prepare("
    INSERT INTO users (username, email, password_hash, faction, is_admin, is_bot, points, alliance_id, gold_coins, imperial_seal_until)
    VALUES ('shingen_takeda', 'shingen@kai.jp', ?, 'vorash', 0, 0, 15000, 2, 2500, '2026-12-31 23:59:59')
")->execute([$passwordHashTakeda]);
$shingenId = (int)$sqlite->lastInsertId();

$sqlite->prepare("
    INSERT INTO users (username, email, password_hash, faction, is_admin, is_bot, points, alliance_id, gold_coins)
    VALUES ('nobunaga_oda', 'nobunaga@owari.jp', ?, 'terran', 0, 0, 32000, 2, 1200)
")->execute([$passwordHashOda]);
$nobunagaId = (int)$sqlite->lastInsertId();

// Insérer aussi un faux bot
$sqlite->prepare("
    INSERT INTO users (username, email, password_hash, faction, is_admin, is_bot, points, gold_coins)
    VALUES ('Ancien_Bot_Oda', 'bot@bot.jp', 'xxx', 'terran', 0, 1, 5000, 0)
")->execute();

echo "1. Utilisateurs créés avant reset :\n";
echo "  - Compte 'shingen_takeda' (ID {$shingenId}, Vorash, 15 000 pts, 2 500 Kobars d'or, Sceau actif)\n";
echo "  - Compte 'nobunaga_oda' (ID {$nobunagaId}, Terran, 32 000 pts, 1 200 Kobars d'or)\n";
echo "  - Bot IA 'Ancien_Bot_Oda' (is_bot = 1)\n\n";

// 4. Exécuter le Reset du Monde
echo "2. Exécution du reset de l'univers via WorldGenerator::resetUniverse...\n";
$worldGen = new WorldGenerator($sqlite);
$res = $worldGen->resetUniverse('NouveauAdmin125#', 5, false);

if (!$res['success']) {
    echo "[FAIL] Erreur lors du reset: " . ($res['message'] ?? 'Erreur inconnue') . "\n";
    exit(1);
}
echo "[OK] Reset exécuté avec succès !\n";

if (GameConfig::getServerCurrentEra() !== 1) {
    echo "[FAIL] L'univers devrait être à l'Ère 1 après le reset (trouvé: " . GameConfig::getServerCurrentEra() . ")\n";
    exit(1);
}
echo "[OK] L'univers a bien basculé à l'Ère 1 (Jour 0) après le reset\n\n";

// 5. Vérifier que les comptes joueurs humains sont TOUJOURS présents
echo "3. Vérification de la persistance des comptes joueurs humains :\n";

$usersCount = (int)$sqlite->query("SELECT COUNT(*) FROM users WHERE is_bot = 0")->fetchColumn();
if ($usersCount < 3) { // nezzar + shingen + nobunaga
    echo "[FAIL] Nombre de comptes attendu >= 3, trouvé {$usersCount}\n";
    exit(1);
}
echo "[OK] 3 comptes humains présents en base (nezzar + 2 joueurs conservés)\n";

// Vérifier shingen_takeda
$shingen = $sqlite->query("SELECT * FROM users WHERE username = 'shingen_takeda'")->fetch(PDO::FETCH_ASSOC);
if (!$shingen) {
    echo "[FAIL] Compte 'shingen_takeda' introuvable !\n";
    exit(1);
}
if (!password_verify('ShingenSecret123#', $shingen['password_hash'])) {
    echo "[FAIL] Mot de passe de 'shingen_takeda' corrompu !\n";
    exit(1);
}
if ((int)$shingen['points'] !== 0) {
    echo "[FAIL] Points de 'shingen_takeda' non réinitialisés (trouvé {$shingen['points']})\n";
    exit(1);
}
if ((int)$shingen['gold_coins'] !== 0) {
    echo "[FAIL] Réserve de Kobars de 'shingen_takeda' non remise à zéro (trouvé {$shingen['gold_coins']})\n";
    exit(1);
}
if ($shingen['imperial_seal_until'] !== null) {
    echo "[FAIL] Sceau Impérial de 'shingen_takeda' non réinitialisé\n";
    exit(1);
}
if ($shingen['alliance_id'] !== null) {
    echo "[FAIL] Alliance de 'shingen_takeda' non réinitialisée\n";
    exit(1);
}
echo "[OK] Compte 'shingen_takeda' intact, identifiants préservés, points et Kobars remis à zéro\n";

// Vérifier nobunaga_oda
$nobunaga = $sqlite->query("SELECT * FROM users WHERE username = 'nobunaga_oda'")->fetch(PDO::FETCH_ASSOC);
if (!$nobunaga || !password_verify('NobunagaPass456#', $nobunaga['password_hash'])) {
    echo "[FAIL] Compte ou mot de passe de 'nobunaga_oda' invalide !\n";
    exit(1);
}
echo "[OK] Compte 'nobunaga_oda' intact et mot de passe vérifié\n";

// Vérifier que l'ancien bot a bien été supprimé
$oldBot = $sqlite->query("SELECT * FROM users WHERE username = 'Ancien_Bot_Oda'")->fetch(PDO::FETCH_ASSOC);
if ($oldBot) {
    echo "[FAIL] L'ancien bot aurait dû être supprimé lors du reset !\n";
    exit(1);
}
echo "[OK] Ancien bot purgé avec succès\n";

// 6. Vérifier que chaque joueur a reçu un nouveau fief de départ
echo "\n4. Vérification des nouveaux fiefs capitaux attribués aux joueurs :\n";
$shingenPlanet = $sqlite->query("SELECT * FROM planets WHERE user_id = {$shingenId} AND is_capital = 1")->fetch(PDO::FETCH_ASSOC);
if (!$shingenPlanet) {
    echo "[FAIL] Aucun fief de départ créé pour 'shingen_takeda' !\n";
    exit(1);
}
echo "[OK] Fief '{$shingenPlanet['name']}' aux coordonnées [{$shingenPlanet['coord_x']}:{$shingenPlanet['coord_y']}] pour shingen_takeda\n";

$nobunagaPlanet = $sqlite->query("SELECT * FROM planets WHERE user_id = {$nobunagaId} AND is_capital = 1")->fetch(PDO::FETCH_ASSOC);
if (!$nobunagaPlanet) {
    echo "[FAIL] Aucun fief de départ créé pour 'nobunaga_oda' !\n";
    exit(1);
}
echo "[OK] Fief '{$nobunagaPlanet['name']}' aux coordonnées [{$nobunagaPlanet['coord_x']}:{$nobunagaPlanet['coord_y']}] pour nobunaga_oda\n";

// 7. Vérifier que chaque joueur a un Héros Samouraï réinitialisé
echo "\n5. Vérification des Héros Samouraïs réinitialisés :\n";
$shingenHero = $sqlite->query("SELECT * FROM heroes WHERE user_id = {$shingenId}")->fetch(PDO::FETCH_ASSOC);
if (!$shingenHero || (int)$shingenHero['level'] !== 1) {
    echo "[FAIL] Héros de 'shingen_takeda' invalide ou niveau != 1\n";
    exit(1);
}
echo "[OK] Héros '{$shingenHero['name']}' niveau 1 présent pour shingen_takeda\n";

$adminHero = $sqlite->query("SELECT * FROM heroes WHERE user_id = " . (int)$res['admin']['id'])->fetch(PDO::FETCH_ASSOC);
if (!$adminHero) {
    echo "[FAIL] Héros de l'administrateur introuvable !\n";
    exit(1);
}
echo "[OK] Héros '{$adminHero['name']}' présent pour l'Administrateur nezzar\n";

// 8. Test de l'option DÉSACTIVÉE (purge totale quand preserveHumanUsers = false)
echo "\n6. Test de l'option DÉSACTIVÉE : resetUniverse avec preserveHumanUsers = false...\n";
$resPurge = $worldGen->resetUniverse('NouveauAdmin125#', 5, false, false);
if (!$resPurge['success']) {
    echo "[FAIL] Erreur lors du reset purge totale: " . ($resPurge['message'] ?? '') . "\n";
    exit(1);
}
$remainingUsers = (int)$sqlite->query("SELECT COUNT(*) FROM users")->fetchColumn();
$nonAdminUsers = (int)$sqlite->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn();
if ($nonAdminUsers !== 0) {
    echo "[FAIL] Des utilisateurs non-admins subsistent après purge totale ({$nonAdminUsers} trouvés) !\n";
    exit(1);
}
echo "[OK] Purge totale validée : 0 joueur non-admin restant, seul l'admin subsiste ({$remainingUsers} utilisateur au total)\n";

echo "\n=================================================================\n";
echo " TOUS LES TESTS DE PRÉSERVATION & PURGE DES COMPTES ONT RÉUSSI !\n";
echo "=================================================================\n";

