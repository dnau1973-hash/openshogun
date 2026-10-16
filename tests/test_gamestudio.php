<?php
/**
 * Test unitaire et d'intégration pour Game Studio (Architecture MVC)
 * Fichier : tests/test_gamestudio.php
 */

require_once __DIR__ . '/../config/game_constants.php';
require_once __DIR__ . '/../core/Database.php';

echo "=== TEST GAME STUDIO (MVC) ===\n\n";

// 1. Initialisation d'une base SQLite en mémoire
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
Database::setConnection($sqlite);

// Création des tables requises
$sqlite->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT,
    faction TEXT DEFAULT 'terran',
    is_bot INTEGER DEFAULT 0,
    is_admin INTEGER DEFAULT 0,
    points INTEGER DEFAULT 100,
    role TEXT DEFAULT 'user',
    last_active DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$sqlite->exec("CREATE TABLE planets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    name TEXT
)");

$sqlite->exec("CREATE TABLE dev_team_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    metier TEXT DEFAULT 'game_designer',
    assigned_tabs TEXT DEFAULT 'dashboard,general',
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$sqlite->exec("CREATE TABLE user_dev_roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    role_id TEXT NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_by INTEGER NULL
)");

$sqlite->exec("CREATE TABLE dev_roles (
    id TEXT PRIMARY KEY,
    title TEXT NOT NULL,
    honor_title TEXT NOT NULL,
    icon TEXT NOT NULL DEFAULT '🛠️',
    category TEXT NOT NULL DEFAULT 'General',
    description TEXT NULL,
    display_order INTEGER NOT NULL DEFAULT 0
)");

$sqlite->exec("CREATE TABLE dev_permissions (
    id TEXT PRIMARY KEY,
    label TEXT NOT NULL,
    category TEXT NOT NULL,
    description TEXT NULL
)");

$sqlite->exec("CREATE TABLE dev_role_permissions (
    role_id TEXT NOT NULL,
    permission_id TEXT NOT NULL,
    PRIMARY KEY (role_id, permission_id)
)");

$sqlite->exec("CREATE TABLE dev_forge_xp (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    xp_amount INTEGER NOT NULL DEFAULT 0,
    source TEXT NOT NULL,
    reason TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$sqlite->exec("CREATE TABLE user_dev_overrides (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    permission_id TEXT NOT NULL,
    is_granted INTEGER NOT NULL DEFAULT 1,
    assigned_by INTEGER NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$sqlite->exec("INSERT INTO dev_roles (id, title, honor_title, icon, category, description, display_order) VALUES
    ('lead_dev', 'Lead Developer', 'Grand Architecte du Code', '💻', 'Engineering', 'Architecture', 1),
    ('game_designer', 'Game Designer', 'Maître de Guerre', '📜', 'Design', 'Gameplay', 2)");

$sqlite->exec("CREATE TABLE support_tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    type TEXT DEFAULT 'bug',
    category TEXT DEFAULT 'ui',
    severity TEXT DEFAULT 'medium',
    title TEXT DEFAULT 'Test Bug',
    description TEXT DEFAULT 'Description du bug',
    planet_id INTEGER NULL,
    status TEXT DEFAULT 'pending',
    admin_response TEXT NULL,
    admin_id INTEGER NULL,
    responded_at INTEGER NULL,
    created_at INTEGER DEFAULT 1720000000,
    updated_at INTEGER DEFAULT 1720000000
)");

$sqlite->exec("CREATE TABLE castles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    is_spawned INTEGER DEFAULT 1
)");

$sqlite->exec("CREATE TABLE heroes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    status TEXT DEFAULT 'alive'
)");

$sqlite->exec("CREATE TABLE hero_inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hero_id INTEGER
)");

$sqlite->exec("CREATE TABLE user_quests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    status TEXT DEFAULT 'claimed'
)");

$sqlite->exec("CREATE TABLE activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL,
    page_slug TEXT NOT NULL,
    action_type TEXT NULL,
    ip_address TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Insertion de données de test
$sqlite->exec("INSERT INTO users (id, username, faction, is_bot, is_admin, points, role) VALUES 
    (1, 'OdaNobunaga', 'terran', 0, 1, 1500, 'admin'),
    (2, 'TakedaShingen', 'vorash', 0, 0, 1200, 'user'),
    (3, 'BotRaider', 'terran', 1, 0, 500, 'user')");

$sqlite->exec("INSERT INTO dev_team_members (user_id, metier, assigned_tabs, is_active) VALUES 
    (1, 'lead_dev', 'all', 1),
    (2, 'game_designer', 'dashboard,general', 1)");

$sqlite->exec("INSERT INTO user_dev_roles (user_id, role_id) VALUES 
    (1, 'lead_dev'),
    (2, 'game_designer')");

$sqlite->exec("INSERT INTO planets (user_id, name) VALUES (1, 'Fief Owari'), (2, 'Fief Kai')");

$sqlite->exec("INSERT INTO activity_logs (user_id, page_slug, action_type, created_at) VALUES 
    (1, 'resources', 'view', datetime('now')),
    (1, 'gamestudio', 'dashboard', datetime('now')),
    (2, 'city', 'view', datetime('now'))");

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/GameStudioEngine.php';

$engine = new GameStudioEngine($sqlite);

// 2. Test getDashboardData
echo "[1] Test getDashboardData('30d')...\n";
$dashData = $engine->getDashboardData('30d');
assert(is_array($dashData), "getDashboardData doit retourner un array");
assert(isset($dashData['kpiMetrics']), "kpiMetrics doit être présent");
assert(isset($dashData['cleanTimeline']), "cleanTimeline doit être présent");
assert(isset($dashData['cleanTopPages']), "cleanTopPages doit être présent");
assert(isset($dashData['cleanHourly']), "cleanHourly doit être présent");
assert($dashData['totalUsers'] === 2, "totalUsers doit être égal à 2 (hors bots)");
assert($dashData['totalBots'] === 1, "totalBots doit être égal à 1");
echo "OK - getDashboardData keys et statistiques validées.\n";

// 3. Test getGeneralData
echo "[2] Test getGeneralData()...\n";
$generalData = $engine->getGeneralData();
assert(is_array($generalData), "getGeneralData doit retourner un array");
assert(isset($generalData['gitInfo']), "gitInfo doit être présent");
assert(isset($generalData['totalUsers']), "totalUsers doit être présent");
assert(isset($generalData['totalBots']), "totalBots doit être présent");
assert(isset($generalData['totalPlanets']), "totalPlanets doit être présent");
assert(isset($generalData['devMembers']), "devMembers doit être présent");
assert(count($generalData['devMembers']) >= 1, "Il doit y avoir au moins 1 membre dev");
echo "OK - getGeneralData keys et membres dev validés.\n";

// 4. Test isAuthorized
echo "[3] Test isAuthorized()...\n";
assert($engine->isAuthorized(1) === true, "L'utilisateur #1 (admin) doit être autorisé");
assert($engine->isAuthorized(2) === true, "L'utilisateur #2 (dev team) doit être autorisé");
assert($engine->isAuthorized(999) === false, "Un utilisateur non-dev et non-admin ne doit pas être autorisé");
echo "OK - isAuthorized validé (Admins et Dev Team autorisés, tiers bloqués).\n";

// 5. Test rendu MVC views/gamestudio/dashboard.php
echo "[4] Test rendu MVC views/gamestudio/dashboard.php...\n";
extract($dashData);
ob_start();
include __DIR__ . '/../views/gamestudio/dashboard.php';
$dashHtml = ob_get_clean();
assert(strlen($dashHtml) > 500, "Le rendu HTML du dashboard doit être supérieur à 500 octets");
assert(strpos($dashHtml, 'adminApexTrendChart') !== false, "Le conteneur du graphique adminApexTrendChart doit être présent");
assert(strpos($dashHtml, 'adminTrendChartFallback') !== false, "Le conteneur de secours Canvas doit être présent");
assert(strpos($dashHtml, 'renderAnalyticsCharts') !== false, "La fonction JS de rendu graphique doit être incluse");
echo "OK - Rendu MVC Dashboard validé (" . strlen($dashHtml) . " octets).\n";

// 6. Test rendu MVC views/gamestudio/general.php
echo "[5] Test rendu MVC views/gamestudio/general.php...\n";
extract($generalData);
ob_start();
include __DIR__ . '/../views/gamestudio/general.php';
$generalHtml = ob_get_clean();
assert(strlen($generalHtml) > 500, "Le rendu HTML de general doit être supérieur à 500 octets");
assert(strpos($generalHtml, 'Configuration &amp;') !== false, "Le titre de la vue générale doit être présent");
assert(strpos($generalHtml, 'Comptes Humains') !== false, "La carte Comptes Humains doit être présente");
echo "OK - Rendu MVC Général validé (" . strlen($generalHtml) . " octets).\n";

// 6.b Test rendu MVC views/gamestudio/dev_tickets.php
echo "[5.b] Test rendu MVC views/gamestudio/dev_tickets.php...\n";
ob_start();
include __DIR__ . '/../views/gamestudio/dev_tickets.php';
$devTicketsHtml = ob_get_clean();
assert(strlen($devTicketsHtml) > 500, "Le rendu HTML de dev_tickets doit être supérieur à 500 octets");
assert(strpos($devTicketsHtml, 'Traitement des Tickets Support') !== false, "Le titre de dev_tickets doit être présent");
assert(strpos($devTicketsHtml, 'modalDevTicket') !== false, "La modale technique de traitement doit être présente");
echo "OK - Rendu MVC Développeur (Tickets & Bugs) validé (" . strlen($devTicketsHtml) . " octets).\n";

// 7. Test de la présence du raccourci Game Studio dans header.php
echo "[6] Test présence du raccourci Game Studio dans header.php...\n";
$headerContent = file_get_contents(__DIR__ . '/../views/partials/header.php');
assert(strpos($headerContent, 'href="?page=gamestudio"') !== false, "Le raccourci vers ?page=gamestudio doit être dans header.php");
assert(strpos($headerContent, 'target="_blank"') !== false, "Le lien Game Studio doit s'ouvrir dans un nouvel onglet avec target='_blank'");
assert(strpos($headerContent, 'fa-laptop-code') !== false, "L'icône Game Studio doit être présente");
assert(strpos($headerContent, 'href="?page=admin"') !== false, "Le bouton Admin existant doit être conservé");
echo "OK - Raccourci présent avec target='_blank' sans suppression d'icônes existantes.\n";

// 8. Test de la suppression effective de sandbox.php
echo "[7] Test suppression de sandbox.php...\n";
assert(!file_exists(__DIR__ . '/../sandbox.php'), "sandbox.php doit être supprimé de la racine");
echo "OK - sandbox.php n'existe plus.\n";

// 9. Test de la page autonome gamestudio.php et du routage index.php
echo "[8] Test environnement dédié gamestudio.php et scripts graphiques...\n";
$studioContent = file_get_contents(__DIR__ . '/../views/gamestudio.php');
assert(strpos($studioContent, '<!DOCTYPE html>') !== false, "gamestudio.php doit être une page HTML autonome");
assert(strpos($studioContent, 'headerBuildingsCarousel') === false, "gamestudio.php ne doit PAS reprendre le carrousel du jeu");
assert(strpos($studioContent, 'BARRE COMPACTE') === false, "gamestudio.php ne doit PAS reprendre la barre de ressources");

assert(strpos($dashHtml, 'apexcharts') !== false, "ApexCharts CDN doit être chargé dans dashboard.php");
assert(strpos($dashHtml, 'renderCanvasTrend') !== false, "renderCanvasTrend doit être présent");
assert(strpos($dashHtml, 'renderCanvasTopPages') !== false, "renderCanvasTopPages doit être présent");
assert(strpos($dashHtml, 'renderCanvasHourly') !== false, "renderCanvasHourly doit être présent");

$indexContent = file_get_contents(__DIR__ . '/../index.php');
assert(strpos($indexContent, "if (\$page === 'gamestudio')") !== false, "index.php doit router directement gamestudio sans header de jeu");
echo "OK - Environnement Game Studio autonome et visualisations validés.\n";

echo "\n========================================================\n";
echo " TOUS LES TESTS GAME STUDIO (MVC) SONT PASSÉS AVEC SUCCÈS !\n";
echo "========================================================\n";
