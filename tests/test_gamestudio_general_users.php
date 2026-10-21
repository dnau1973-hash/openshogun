<?php
/**
 * Test Automatisé : Module de Gestion des Joueurs & Fiefs (Game Studio Général)
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/GameConfig.php';
require_once __DIR__ . '/../core/GameStudioEngine.php';

echo "=================================================================\n";
echo " TEST : MODULE GESTION DES JOUEURS DANS GAME STUDIO GENERAL\n";
echo "=================================================================\n\n";

$testsPassed = 0;
$totalTests = 0;

function assertTest(string $description, bool $condition): void {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        echo "  [PASS] {$description}\n";
        $testsPassed++;
    } else {
        echo "  [FAIL] {$description}\n";
    }
}

// 1. Initialiser une base SQLite en mémoire pour tester de façon isolée et autonome
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fonctions SQLite de compatibilité MySQL
$sqlite->sqliteCreateFunction('UNIX_TIMESTAMP', fn() => time(), 0);
$sqlite->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'), 0);
$sqlite->sqliteCreateFunction('LEAST', fn(...$args) => min($args));
$sqlite->sqliteCreateFunction('GREATEST', fn(...$args) => max($args));

Database::setConnection($sqlite);

// 2. Schéma minimal
$sqlite->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    email TEXT UNIQUE,
    password_hash TEXT,
    faction TEXT DEFAULT 'terran',
    is_admin INTEGER DEFAULT 0,
    is_moderator INTEGER DEFAULT 0,
    is_bot INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    points INTEGER DEFAULT 0,
    alliance_id INTEGER DEFAULT NULL,
    protection_until TEXT DEFAULT NULL,
    gold_coins INTEGER DEFAULT 100,
    imperial_seal_until TEXT DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    avatar TEXT DEFAULT NULL,
    role TEXT DEFAULT 'player',
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
    crystal REAL DEFAULT 800,
    deuterium REAL DEFAULT 400,
    sake REAL DEFAULT 0,
    fish REAL DEFAULT 0,
    rice_flour REAL DEFAULT 0,
    wooden_beams REAL DEFAULT 0,
    charcoal REAL DEFAULT 0,
    roof_tiles REAL DEFAULT 0,
    metal_max INTEGER DEFAULT 15000,
    crystal_max INTEGER DEFAULT 15000,
    deuterium_max INTEGER DEFAULT 15000,
    population INTEGER DEFAULT 100,
    is_capital INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");

$sqlite->exec("CREATE TABLE heroes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNIQUE,
    current_planet_id INTEGER DEFAULT 1,
    name TEXT DEFAULT 'Samouraï Champion',
    level INTEGER DEFAULT 1,
    experience INTEGER DEFAULT 100,
    health REAL DEFAULT 100,
    status TEXT DEFAULT 'home',
    stat_strength INTEGER DEFAULT 5,
    stat_offense_bonus INTEGER DEFAULT 10,
    stat_defense_bonus INTEGER DEFAULT 10
)");

$sqlite->exec("CREATE TABLE alliances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    tag TEXT,
    leader_id INTEGER
)");

$sqlite->exec("CREATE TABLE authentic_castles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    coord_x INTEGER,
    coord_y INTEGER,
    is_spawned INTEGER DEFAULT 0
)");

// 3. Insérer des données de test
$now = date('Y-m-d H:i:s');
$recentActive = date('Y-m-d H:i:s', time() - 300); // 5 min ago
$oldActive = date('Y-m-d H:i:s', time() - 200000); // 2 days ago
$futureProtection = date('Y-m-d H:i:s', time() + 86400 * 5); // in 5 days
$futureSeal = date('Y-m-d H:i:s', time() + 86400 * 3); // in 3 days

// User 1: Admin Oda
$sqlite->exec("INSERT INTO users (id, username, email, password_hash, faction, is_admin, points, gold_coins, last_active, is_active)
VALUES (1, 'Nobunaga', 'nobunaga@oda.jp', 'hash1', 'terran', 1, 5000, 1500, '{$recentActive}', 1)");

// User 2: Takeda (protégé, seal)
$sqlite->exec("INSERT INTO users (id, username, email, password_hash, faction, points, gold_coins, last_active, protection_until, imperial_seal_until, is_active)
VALUES (2, 'Shingen', 'shingen@takeda.jp', 'hash2', 'vorash', 3200, 450, '{$recentActive}', '{$futureProtection}', '{$futureSeal}', 1)");

// User 3: Tokugawa (suspendu/banni)
$sqlite->exec("INSERT INTO users (id, username, email, password_hash, faction, is_moderator, points, gold_coins, last_active, is_active)
VALUES (3, 'Ieyasu', 'ieyasu@tokugawa.jp', 'hash3', 'aethelis', 1, 4100, 800, '{$oldActive}', 0)");

// User 4: Bot (ne doit pas apparaître dans la gestion des joueurs humains)
$sqlite->exec("INSERT INTO users (id, username, email, password_hash, is_bot, points, last_active, is_active)
VALUES (4, 'Bot_Raider', 'bot@ai.jp', 'hash4', 1, 500, '{$recentActive}', 1)");

// Planètes
$sqlite->exec("INSERT INTO planets (id, user_id, name, coord_x, coord_y, metal, crystal, deuterium, is_capital)
VALUES (1, 1, 'Château d Azuchi', 10, 15, 25000, 18000, 9000, 1)");

$sqlite->exec("INSERT INTO planets (id, user_id, name, coord_x, coord_y, metal, crystal, deuterium, is_capital)
VALUES (2, 1, 'Gifu', 12, 16, 5000, 4000, 2000, 0)");

$sqlite->exec("INSERT INTO planets (id, user_id, name, coord_x, coord_y, metal, crystal, deuterium, is_capital)
VALUES (3, 2, 'Kofu', -5, 20, 12000, 8000, 4000, 1)");

// Héros
$sqlite->exec("INSERT INTO heroes (id, user_id, name, level, health, status)
VALUES (1, 1, 'Nobunaga Samouraï', 12, 100, 'home')");

// Instancier le moteur
$engine = new GameStudioEngine($sqlite);

// TEST 1 : getGeneralUsersData - KPIs globaux
echo "Test 1 : KPIs globaux de getGeneralUsersData()\n";
$data = $engine->getGeneralUsersData();
$kpis = $data['usersKpis'];

assertTest("Total des joueurs humains = 3 (les bots sont exclus)", $kpis['total_users'] === 3);
assertTest("Actifs en direct (<15m) = 2 (Nobunaga, Shingen)", $kpis['active_15m'] === 2);
assertTest("Joueurs protégés = 1 (Shingen)", $kpis['protected_users'] === 1);
assertTest("Joueurs avec Sceau Impérial = 1 (Shingen)", $kpis['imperial_seal_users'] === 1);
assertTest("Joueurs bannis / inactifs = 1 (Ieyasu)", $kpis['banned_users'] === 1);
assertTest("Comptage de la faction Terran = 1", ($kpis['faction_counts']['terran'] ?? 0) === 1);
assertTest("Comptage de la faction Vorash = 1", ($kpis['faction_counts']['vorash'] ?? 0) === 1);
assertTest("Comptage de la faction Aethelis = 1", ($kpis['faction_counts']['aethelis'] ?? 0) === 1);

// TEST 2 : Filtres et Recherche
echo "\nTest 2 : Filtres et Recherche\n";
// Recherche par nom
$resSearch = $engine->getGeneralUsersData(['search' => 'Shingen']);
assertTest("Recherche par nom 'Shingen' renvoie 1 résultat", $resSearch['filteredTotal'] === 1 && $resSearch['usersList'][0]['username'] === 'Shingen');

// Recherche par coordonnées [10:15]
$resCoord = $engine->getGeneralUsersData(['search' => '[10:15]']);
assertTest("Recherche par coordonnées '[10:15]' renvoie Nobunaga", $resCoord['filteredTotal'] === 1 && $resCoord['usersList'][0]['username'] === 'Nobunaga');

// Filtre faction 'vorash'
$resVorash = $engine->getGeneralUsersData(['faction' => 'vorash']);
assertTest("Filtre faction 'vorash' renvoie uniquement Shingen", $resVorash['filteredTotal'] === 1 && $resVorash['usersList'][0]['faction'] === 'vorash');

// Filtre statut 'banned'
$resBanned = $engine->getGeneralUsersData(['status' => 'banned']);
assertTest("Filtre statut 'banned' renvoie Ieyasu", $resBanned['filteredTotal'] === 1 && $resBanned['usersList'][0]['username'] === 'Ieyasu');

// TEST 3 : getUserProfile (Inspection 360°)
echo "\nTest 3 : getUserProfile() Inspection 360°\n";
$profile = $engine->getUserProfile(1);
assertTest("Profil Nobunaga récupéré", $profile !== null && $profile['user']['username'] === 'Nobunaga');
assertTest("Nobunaga possède 2 fiefs", count($profile['planets']) === 2);
assertTest("Capitale de Nobunaga est 'Château d Azuchi'", $profile['planets'][0]['name'] === 'Château d Azuchi');
assertTest("Héros de Nobunaga est présent (Niveau 12)", $profile['hero'] !== null && (int)$profile['hero']['level'] === 12);

// TEST 4 : giveResourcesToPlanet
echo "\nTest 4 : giveResourcesToPlanet()\n";
$resGift = $engine->giveResourcesToPlanet(1, 10000, 5000, 2000);
assertTest("Subsides accordés avec succès", $resGift['success'] === true);
$checkPlanet = $sqlite->query("SELECT metal, crystal, deuterium FROM planets WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
assertTest("Nouveau stock de riz = 35000", (float)$checkPlanet['metal'] == 35000.0);
assertTest("Nouveau stock de bois = 23000", (float)$checkPlanet['crystal'] == 23000.0);
assertTest("Nouveau stock de fer = 11000", (float)$checkPlanet['deuterium'] == 11000.0);

// TEST 5 : teleportColony
echo "\nTest 5 : teleportColony()\n";
// Téléportation vers un emplacement libre
$resTeleport = $engine->teleportColony(2, 25, 25);
assertTest("Téléportation vers [25:25] réussie", $resTeleport['success'] === true);
$checkCoord = $sqlite->query("SELECT coord_x, coord_y FROM planets WHERE id = 2")->fetch(PDO::FETCH_ASSOC);
assertTest("Nouvelles coordonnées enregistrées [25:25]", (int)$checkCoord['coord_x'] === 25 && (int)$checkCoord['coord_y'] === 25);

// Collision avec un autre fief
$resCollision = $engine->teleportColony(2, 10, 15);
assertTest("Téléportation refusée si coordonnées déjà occupées", $resCollision['success'] === false);

// Coordonnées hors limites
$resOOB = $engine->teleportColony(2, 9999, 9999);
assertTest("Téléportation refusée si en dehors des frontières du monde", $resOOB['success'] === false);

// TEST 6 : resetUserPassword
echo "\nTest 6 : resetUserPassword()\n";
$resPwd = $engine->resetUserPassword(2, 'TestSecret2026!');
assertTest("Réinitialisation mot de passe réussie", $resPwd['success'] === true);
$hashInDb = $sqlite->query("SELECT password_hash FROM users WHERE id = 2")->fetchColumn();
assertTest("Nouveau hash valide et vérifiable", password_verify('TestSecret2026!', $hashInDb));

// TEST 7 : toggleUserBan
echo "\nTest 7 : toggleUserBan()\n";
// Débannir Ieyasu (id 3)
$resUnban = $engine->toggleUserBan(3, "Pardon Shogunal accordé");
assertTest("Débannissement d'Ieyasu réussi", $resUnban['success'] === true && $resUnban['is_active'] === 1);
$activeInDb = (int)$sqlite->query("SELECT is_active FROM users WHERE id = 3")->fetchColumn();
assertTest("is_active en base est passé à 1", $activeInDb === 1);

// Re-bannir Ieyasu
$resReban = $engine->toggleUserBan(3, "Comportement séditieux");
assertTest("Re-bannissement réussi", $resReban['success'] === true && $resReban['is_active'] === 0);
$activeInDb2 = (int)$sqlite->query("SELECT is_active FROM users WHERE id = 3")->fetchColumn();
assertTest("is_active en base est repassé à 0", $activeInDb2 === 0);

// TEST 8 : Rendu MVC de la vue general.php avec subtab=users
echo "\nTest 8 : Rendu MVC views/gamestudio/general.php avec subtab=users\n";
$_GET['page'] = 'gamestudio';
$_GET['tab'] = 'general';
$_GET['subtab'] = 'users';

$usersManagementData = $engine->getGeneralUsersData();
extract($usersManagementData);

ob_start();
include __DIR__ . '/../views/gamestudio/general.php';
$html = ob_get_clean();

assertTest("Le sous-onglet Gestion des Joueurs est généré", strpos($html, 'Registre Impérial des Daimyōs Joueurs') !== false);
assertTest("Nobunaga est présent dans le rendu HTML", strpos($html, 'Nobunaga') !== false);
assertTest("Shingen est présent dans le rendu HTML", strpos($html, 'Shingen') !== false);
assertTest("Ieyasu est présent dans le rendu HTML", strpos($html, 'Ieyasu') !== false);
assertTest("Le bot AI Raider est exclu du tableau", strpos($html, 'Bot_Raider') === false);

echo "\n=================================================================\n";
echo " RÉSULTATS : {$testsPassed} / {$totalTests} tests réussis.\n";
echo "=================================================================\n";

if ($testsPassed === $totalTests) {
    echo " TOUS LES TESTS SONT AU VERT !\n";
    exit(0);
} else {
    echo " CERTAINS TESTS ONT ÉCHOUÉ.\n";
    exit(1);
}

