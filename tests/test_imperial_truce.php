<?php
/**
 * Test Automatisé : Système des Trêves Impériales (Noël, Nouvel An & Décret d'Exception)
 * Fichier : tests/test_imperial_truce.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GameConfig.php';
require_once __DIR__ . '/../core/GameStudioEngine.php';
require_once __DIR__ . '/../core/FleetEngine.php';
require_once __DIR__ . '/../core/LiveOpsEngine.php';

function assertTest(bool $condition, string $title): void {
    if ($condition) {
        echo "  [PASS] " . $title . "\n";
    } else {
        echo "  [FAIL] " . $title . "\n";
        exit(1);
    }
}

echo "========================================================================\n";
echo "  BANC DE TESTS : TRÊVES IMPÉRIALES SACRÉES (NOËL & NOUVEL AN)\n";
echo "========================================================================\n\n";

// Configuration SQLite en mémoire
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$sqlite->sqliteCreateFunction('UNIX_TIMESTAMP', 'time');
$sqlite->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'));
Database::setConnection($sqlite);

// Schéma de test
$sqlite->exec("CREATE TABLE game_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT,
    setting_type TEXT DEFAULT 'string',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$sqlite->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT,
    faction TEXT DEFAULT 'Terran',
    alliance_id INTEGER DEFAULT NULL,
    is_bot INTEGER DEFAULT 0,
    protection_until INTEGER DEFAULT 0,
    created_at INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE planets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    user_id INTEGER DEFAULT NULL,
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0,
    planet_type TEXT DEFAULT 'colony',
    metal INTEGER DEFAULT 100000,
    crystal INTEGER DEFAULT 100000,
    deuterium INTEGER DEFAULT 100000,
    energy_used INTEGER DEFAULT 0,
    energy_max INTEGER DEFAULT 100,
    metal_max INTEGER DEFAULT 100000,
    crystal_max INTEGER DEFAULT 100000,
    deuterium_max INTEGER DEFAULT 100000,
    is_capital INTEGER DEFAULT 0,
    last_resource_update INTEGER DEFAULT 0,
    created_at INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE fleet_missions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    source_planet_id INTEGER,
    target_planet_id INTEGER DEFAULT NULL,
    target_oasis_id INTEGER DEFAULT NULL,
    mission_type TEXT,
    fleet_data TEXT,
    cargo_data TEXT,
    departure_time INTEGER,
    arrival_time INTEGER,
    return_time INTEGER,
    status TEXT DEFAULT 'en_route',
    has_hero INTEGER DEFAULT 0,
    created_at INTEGER DEFAULT 0
)");

echo "[TEST 1] Validation des dates précises de la Trêve de Noël (3 jours : 24, 25, 26 Décembre)...\n";
GameConfig::set('truce_christmas_enabled', 1);
GameConfig::set('truce_newyear_enabled', 1);
GameConfig::set('truce_custom_enabled', 0);

// 23 Décembre 23h59 -> Inactif
$ts23Dec = strtotime("2026-12-23 23:59:50");
assertTest(GameConfig::isImperialTruceActive($ts23Dec) === false, "23 Décembre 23h59 : Trêve inactive");

// 24 Décembre 00h00 -> Actif (1 jour avant Noël)
$ts24Dec = strtotime("2026-12-24 00:00:05");
$status24 = GameConfig::getImperialTruceStatus($ts24Dec);
assertTest($status24['active'] === true, "24 Décembre 00h00 : Trêve de Noël active (Veille)");
assertTest($status24['type'] === 'christmas', "Type de trêve = 'christmas'");
assertTest(str_contains($status24['name'], 'Noël'), "Nom = Trêve Sacrée de Noël");

// 25 Décembre 12h00 -> Actif (Jour de Noël)
$ts25Dec = strtotime("2026-12-25 12:00:00");
assertTest(GameConfig::isImperialTruceActive($ts25Dec) === true, "25 Décembre 12h00 : Trêve de Noël active (Jour J)");

// 26 Décembre 23h59 -> Actif (1 jour après Noël)
$ts26Dec = strtotime("2026-12-26 23:59:50");
assertTest(GameConfig::isImperialTruceActive($ts26Dec) === true, "26 Décembre 23h59 : Trêve de Noël active (Lendemain)");

// 27 Décembre 00h01 -> Inactif
$ts27Dec = strtotime("2026-12-27 00:01:00");
assertTest(GameConfig::isImperialTruceActive($ts27Dec) === false, "27 Décembre 00h01 : Trêve de Noël terminée (reprise des combats)");

echo "\n[TEST 2] Validation des dates précises de la Trêve du Nouvel An (3 jours : 31 Décembre, 1er, 2 Janvier)...\n";
// 30 Décembre 23h59 -> Inactif
$ts30Dec = strtotime("2026-12-30 23:59:50");
assertTest(GameConfig::isImperialTruceActive($ts30Dec) === false, "30 Décembre 23h59 : Trêve inactive");

// 31 Décembre 00h00 -> Actif (1 jour avant Nouvel An)
$ts31Dec = strtotime("2026-12-31 00:00:05");
$status31 = GameConfig::getImperialTruceStatus($ts31Dec);
assertTest($status31['active'] === true, "31 Décembre 00h00 : Trêve du Nouvel An active (Réveillon)");
assertTest($status31['type'] === 'newyear', "Type de trêve = 'newyear'");
assertTest(str_contains($status31['name'], 'Nouvel An'), "Nom = Trêve Sacrée du Nouvel An");

// 1er Janvier 12h00 -> Actif (Jour de l'An)
$ts01Jan = strtotime("2027-01-01 12:00:00");
assertTest(GameConfig::isImperialTruceActive($ts01Jan) === true, "1er Janvier 12h00 : Trêve active (Jour de l'An)");

// 2 Janvier 23h59 -> Actif (1 jour après)
$ts02Jan = strtotime("2027-01-02 23:59:50");
assertTest(GameConfig::isImperialTruceActive($ts02Jan) === true, "2 Janvier 23h59 : Trêve active (Lendemain du Nouvel An)");

// 3 Janvier 00h01 -> Inactif
$ts03Jan = strtotime("2027-01-03 00:01:00");
assertTest(GameConfig::isImperialTruceActive($ts03Jan) === false, "3 Janvier 00h01 : Trêve du Nouvel An terminée");

echo "\n[TEST 3] Validation des Boutons à Bascule (Désactivation & Trêve d'Exception en Direct)...\n";
// Désactivation du switch Noël : même le 25 décembre, pas de trêve
GameConfig::set('truce_christmas_enabled', 0);
assertTest(GameConfig::isImperialTruceActive($ts25Dec) === false, "Switch Noël désactivé : Pas de trêve le 25 Décembre");
GameConfig::set('truce_christmas_enabled', 1);

// Activation du switch Décret d'Exception en direct (temps réel)
$tsToday = time();
assertTest(GameConfig::isImperialTruceActive($tsToday) === false, "Actuellement hors période de fête, trêve inactive");
GameConfig::set('truce_custom_enabled', 1);
$statusCustom = GameConfig::getImperialTruceStatus($tsToday);
assertTest($statusCustom['active'] === true, "Switch Décret Direct activé : Trêve d'Exception active IMMÉDIATEMENT");
assertTest($statusCustom['type'] === 'custom', "Type = 'custom'");
assertTest(str_contains($statusCustom['name'], 'Exception'), "Nom = Trêve Impériale d'Exception");
GameConfig::set('truce_custom_enabled', 0);
assertTest(GameConfig::isImperialTruceActive($tsToday) === false, "Switch Décret Direct désactivé : Retour à la normale");

echo "\n[TEST 4] Test de la méthode saveTruceSettings de GameStudioEngine...\n";
$studioEngine = new GameStudioEngine($sqlite);
$saveRes = $studioEngine->saveTruceSettings([
    'truce_christmas_enabled' => '1',
    'truce_newyear_enabled'   => '1',
    'truce_custom_enabled'    => '0'
]);
assertTest($saveRes['success'] === true, "saveTruceSettings exécuté avec succès");
assertTest((bool)GameConfig::get('truce_christmas_enabled') === true, "truce_christmas_enabled sauvegardé à true");
assertTest((bool)GameConfig::get('truce_newyear_enabled') === true, "truce_newyear_enabled sauvegardé à true");
assertTest((bool)GameConfig::get('truce_custom_enabled') === false, "truce_custom_enabled sauvegardé à false");

echo "\n[TEST 5] Test de la diffusion de décret broadcastTruceDecree...\n";
$bcTruce = $studioEngine->broadcastTruceDecree('christmas');
assertTest($bcTruce === true, "Proclamation solennelle de la Trêve de Noël diffusée");

$liveOps = new LiveOpsEngine($sqlite);
$activeBc = $liveOps->getActiveBroadcast();
assertTest(!empty($activeBc), "Dépêche active récupérée");
assertTest(str_contains($activeBc['title'], 'Noël'), "Titre mentionne la Trêve Sacrée de Noël");
assertTest(str_contains($activeBc['badge'], 'Noël'), "Badge mentionne Fêtes de Noël (3 Jours)");
assertTest(!str_contains($activeBc['action_label'], '&rarr;'), "action_label propre sans résidu '&rarr;'");

echo "\n[TEST 6] Test de blocage des attaques et raids dans FleetEngine pendant la trêve...\n";
// Création de deux joueurs et deux fiefs
$sqlite->exec("INSERT INTO users (id, username) VALUES (1, 'Daimyo_Attaquant'), (2, 'Daimyo_Defenseur')");
$sqlite->exec("INSERT INTO planets (id, name, user_id, coord_x, coord_y) VALUES (1, 'Fief Attaquant', 1, 10, 10), (2, 'Fief Défenseur', 2, 12, 12)");

$fleetEngine = new FleetEngine();

// Activer la trêve en direct pour le test
GameConfig::set('truce_custom_enabled', 1);

// Tenter une mission d'attaque hostile
$attackBlocked = false;
try {
    $fleetEngine->dispatchMission(1, 1, 2, 'attack', ['soldier' => 10], ['metal' => 0, 'crystal' => 0, 'deuterium' => 0]);
} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'Trêve Impériale') || str_contains($e->getMessage(), 'interdit') || str_contains($e->getMessage(), 'suspendu')) {
        $attackBlocked = true;
    }
}
assertTest($attackBlocked === true, "Lancement d'une mission d'attaque bloqué par la Trêve Impériale");

// Tenter une mission de raid hostile
$raidBlocked = false;
try {
    $fleetEngine->dispatchMission(1, 1, 2, 'raid', ['soldier' => 5], ['metal' => 0, 'crystal' => 0, 'deuterium' => 0]);
} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'Trêve Impériale') || str_contains($e->getMessage(), 'interdit') || str_contains($e->getMessage(), 'suspendu')) {
        $raidBlocked = true;
    }
}
assertTest($raidBlocked === true, "Lancement d'une mission de raid bloqué par la Trêve Impériale");

// Tenter une mission d'espionnage
$spyBlocked = false;
try {
    $fleetEngine->dispatchMission(1, 1, 2, 'spy', ['spy_probe' => 1], ['metal' => 0, 'crystal' => 0, 'deuterium' => 0]);
} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'Trêve Impériale') || str_contains($e->getMessage(), 'interdit') || str_contains($e->getMessage(), 'suspendu')) {
        $spyBlocked = true;
    }
}
assertTest($spyBlocked === true, "Lancement d'une mission d'espionnage bloqué par la Trêve Impériale");

// Désactiver la trêve
GameConfig::set('truce_custom_enabled', 0);

echo "\n[TEST 7] Test du repli pacifique automatique à l'arrivée (resolveArrival)...\n";
// Simuler une flotte hostile déjà en route
$sqlite->exec("INSERT INTO fleet_missions (id, user_id, source_planet_id, target_planet_id, mission_type, fleet_data, cargo_data, departure_time, arrival_time, return_time, status) 
VALUES (999, 1, 1, 2, 'attack', '{\"soldier\":10}', '{\"metal\":0,\"crystal\":0,\"deuterium\":0}', " . (time() - 100) . ", " . (time() - 10) . ", " . (time() + 100) . ", 'en_route')");

// La trêve est désormais active
GameConfig::set('truce_custom_enabled', 1);

// Traiter les missions arrivées
$fleetEngine->processFleetMissions();

$stmtCheck = $sqlite->query("SELECT status FROM fleet_missions WHERE id = 999");
$missionStatus = $stmtCheck->fetchColumn();
assertTest($missionStatus === 'returning', "Flotte hostile arrivée pendant la trêve : repli pacifique automatique (status = 'returning')");

GameConfig::set('truce_custom_enabled', 0);

echo "\n[TEST 8] Vérification de l'absence de résidus HTML sur le bouton d'action de la modale...\n";
$viewContent = file_get_contents(__DIR__ . '/../views/gamestudio/general_world_config.php');
$headerContent = file_get_contents(__DIR__ . '/../views/partials/header.php');

assertTest(str_contains($viewContent, 'id="section-world-truce-config"'), "Onglet 8 #section-world-truce-config présent dans general_world_config.php");
assertTest(str_contains($viewContent, 'switchTruceChristmas'), "Switch de la Trêve de Noël présent");
assertTest(str_contains($viewContent, 'switchTruceNewYear'), "Switch de la Trêve du Nouvel An présent");
assertTest(str_contains($viewContent, 'switchTruceCustom'), "Switch de la Trêve d'Exception en direct présent");
assertTest(str_contains($viewContent, 'action_label: \'Bâtir mon Fief Rural\''), "Bouton 'Bâtir mon Fief Rural' sans entité &rarr; dans le JS");
assertTest(!str_contains($viewContent, 'action_label: \'Bâtir mon Fief Rural &rarr;\''), "Aucun résidu HTML &rarr; sur le label du bouton");

echo "\n========================================================================\n";
echo " TOUS LES TESTS DES TRÊVES IMPÉRIALES SONT 100% AU VERT !\n";
echo "========================================================================\n";
