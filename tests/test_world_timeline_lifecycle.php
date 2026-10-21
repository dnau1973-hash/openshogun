<?php
/**
 * Test Automatisé : Déroulement du Jeu & Chronologie des Ères Mondiales
 * Fichier : tests/test_world_timeline_lifecycle.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GameConfig.php';
require_once __DIR__ . '/../core/GameStudioEngine.php';
require_once __DIR__ . '/../core/CastleEngine.php';
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
echo "  BANC DE TESTS : DÉROULEMENT DU JEU & CHRONOLOGIE DES ÈRES (GAME STUDIO)\n";
echo "========================================================================\n\n";

// Configuration SQLite en mémoire
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$sqlite->sqliteCreateFunction('UNIX_TIMESTAMP', 'time');
$sqlite->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'));
Database::setConnection($sqlite);

// Schéma de test minimal
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
    is_bot INTEGER DEFAULT 0,
    protection_until DATETIME DEFAULT NULL,
    created_at INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE alliances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    tag TEXT,
    leader_id INTEGER
)");

$sqlite->exec("CREATE TABLE planets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    user_id INTEGER DEFAULT NULL,
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0,
    planet_type TEXT DEFAULT 'colony',
    metal INTEGER DEFAULT 0,
    crystal INTEGER DEFAULT 0,
    deuterium INTEGER DEFAULT 0,
    energy_used INTEGER DEFAULT 0,
    energy_max INTEGER DEFAULT 100,
    metal_max INTEGER DEFAULT 100000,
    crystal_max INTEGER DEFAULT 100000,
    deuterium_max INTEGER DEFAULT 100000,
    is_capital INTEGER DEFAULT 0,
    last_resource_update INTEGER DEFAULT 0,
    created_at INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE authentic_castles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    code TEXT,
    province TEXT,
    description TEXT,
    image TEXT,
    relic_name TEXT,
    relic_type TEXT,
    relic_bonus_percent INTEGER DEFAULT 20,
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0,
    default_x INTEGER DEFAULT 0,
    default_y INTEGER DEFAULT 0,
    is_spawned INTEGER DEFAULT 0,
    planet_id INTEGER DEFAULT NULL,
    created_at INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE imperial_dojo (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0,
    controlling_alliance_id INTEGER DEFAULT NULL,
    current_level INTEGER DEFAULT 0,
    target_level INTEGER DEFAULT 1,
    status TEXT DEFAULT 'unclaimed',
    health_points INTEGER DEFAULT 0,
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

$sqlite->exec("CREATE TABLE oasis_units (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    oasis_id INTEGER,
    unit_code TEXT,
    count INTEGER DEFAULT 0,
    is_wild INTEGER DEFAULT 1
)");

// Insertion de quelques châteaux authentiques
$sqlite->exec("INSERT INTO authentic_castles (id, name, code, province, relic_name, relic_type, relic_bonus_percent, default_x, default_y, is_spawned)
VALUES 
(1, 'Château de Himeji', 'himeji', 'Harima', 'Bannière du Héron Blanc', 'Vitesse de marche', 25, 12, 10, 0),
(2, 'Château de Matsumoto', 'matsumoto', 'Shinano', 'Corbeau d''Ébène', 'Défense des remparts', 30, -15, 8, 0),
(3, 'Château de Hikone', 'hikone', 'Omi', 'Armure du Démon Rouge', 'Attaque d''infanterie', 20, 5, -18, 0)");

echo "[TEST 1] Vérification de l'initialisation et des méthodes de GameConfig pour les Ères...\n";
GameConfig::set('server_start_timestamp', time()); // Serveur tout juste ouvert (J+0)
assertTest(GameConfig::getServerCurrentEra() === 1, "Ère initiale par défaut = 1");
assertTest(GameConfig::isCastlesWarUnlocked() === false, "Guerre des 12 Châteaux verrouillée par défaut");
assertTest(GameConfig::isImperialDojoUnlocked() === false, "Dōjō Impérial verrouillé par défaut");
assertTest(GameConfig::getServerAgeDays() >= 0.0, "Calcul de l'âge du serveur en jours fonctionnel");

echo "\n[TEST 2] Extraction de la télémétrie du cycle de vie via GameStudioEngine::getWorldLifecycleData()...\n";
$gameStudio = new GameStudioEngine();
$data = $gameStudio->getWorldLifecycleData();

assertTest(isset($data['currentEra']) && $data['currentEra'] === 1, "currentEra présent dans la télémétrie");
assertTest(isset($data['serverAgeDays']), "serverAgeDays présent dans la télémétrie");
assertTest(isset($data['eras']) && count($data['eras']) === 5, "Les 5 Ères Féodales sont présentes");
assertTest($data['eras'][1]['status'] === 'active', "Ère 1 active");
assertTest($data['eras'][3]['status'] === 'locked', "Ère 3 verrouillée");
assertTest(isset($data['castlesList']) && count($data['castlesList']) === 3, "Radar des châteaux authentiques alimenté");
assertTest(isset($data['dojoState']), "Moniteur du Dōjō Impérial initialisé");
assertTest($data['dojoCurrLevel'] === 0, "Niveau initial du Dōjō = 0");
assertTest($data['dojoMaxHp'] === 5000000, "HP Max du Dōjō = 5 000 000");

echo "\n[TEST 3] Sauvegarde des paramètres de chronologie et d'automatisation des Ères...\n";
$saveRes = $gameStudio->saveEraSettings([
    'era_auto_progression_enabled' => '1',
    'era_castles_auto_day'         => '10',
    'era_dojo_auto_day'            => '25',
    'era_broadcast_announcements'  => '1',
    'server_current_era'           => '2'
]);
assertTest($saveRes['success'] === true, "saveEraSettings() retourne un succès");
assertTest(GameConfig::getServerCurrentEra() === 2, "L'Ère courante est mise à jour à 2");
assertTest((int)GameConfig::get('era_castles_auto_day') === 10, "era_castles_auto_day sauvegardé à 10");
assertTest((int)GameConfig::get('era_dojo_auto_day') === 25, "era_dojo_auto_day sauvegardé à 25");

echo "\n[TEST 4] Déclenchement 1-clic de la Guerre des 12 Châteaux (Ère III)...\n";
$warRes = $gameStudio->triggerCastlesWar(false);
assertTest($warRes['success'] === true, "triggerCastlesWar() exécuté avec succès");
assertTest(GameConfig::isCastlesWarUnlocked() === true, "era_castles_unlocked activé");
assertTest(GameConfig::getServerCurrentEra() >= 3, "Le monde bascule au minimum en Ère III");

// Vérifier que les châteaux ont été déployés
$refreshedData = $gameStudio->getWorldLifecycleData();
assertTest($refreshedData['castlesUnlocked'] === true, "castlesUnlocked reflété dans getWorldLifecycleData");
assertTest($refreshedData['eras'][3]['status'] === 'active', "L'Ère III est désormais active");

echo "\n[TEST 5] Déclenchement 1-clic du Chantier du Dōjō Impérial [0:0] (Ère IV)...\n";
$dojoRes = $gameStudio->triggerImperialDojo(false);
assertTest($dojoRes['success'] === true, "triggerImperialDojo() exécuté avec succès");
assertTest(GameConfig::isImperialDojoUnlocked() === true, "era_dojo_unlocked activé");
assertTest(GameConfig::getServerCurrentEra() >= 4, "Le monde bascule au minimum en Ère IV");

$refreshedData2 = $gameStudio->getWorldLifecycleData();
assertTest($refreshedData2['dojoUnlocked'] === true, "dojoUnlocked reflété dans getWorldLifecycleData");
assertTest($refreshedData2['eras'][4]['status'] === 'active', "L'Ère IV est active");
assertTest($refreshedData2['eras'][3]['status'] === 'completed', "L'Ère III est marquée achevée");

echo "\n[TEST 6] Forçage manuel de l'Ère (Override GM)...\n";
$setEraRes = $gameStudio->setWorldEra(5, false);
assertTest($setEraRes['success'] === true, "setWorldEra(5) exécuté avec succès");
assertTest(GameConfig::getServerCurrentEra() === 5, "Ère 5 active");
$refreshedData3 = $gameStudio->getWorldLifecycleData();
assertTest($refreshedData3['eras'][5]['status'] === 'active', "Ère V marquée active");

echo "\n[TEST 7] Intégration de getWorldLifecycleData dans getWorldConfigData()...\n";
$fullWorldConfig = $gameStudio->getWorldConfigData();
assertTest(isset($fullWorldConfig['currentEra']), "currentEra présent dans getWorldConfigData");
assertTest(isset($fullWorldConfig['castlesUnlocked']), "castlesUnlocked présent dans getWorldConfigData");
assertTest(isset($fullWorldConfig['dojoUnlocked']), "dojoUnlocked présent dans getWorldConfigData");
assertTest(isset($fullWorldConfig['castlesList']), "castlesList présent dans getWorldConfigData");
assertTest(isset($fullWorldConfig['dojoState']), "dojoState présent dans getWorldConfigData");

echo "\n[TEST 8] Rendu HTML de l'Onglet 7 dans general_world_config.php...\n";
$viewPath = __DIR__ . '/../views/gamestudio/general_world_config.php';
$viewContent = file_get_contents($viewPath);

assertTest(str_contains($viewContent, '7. Déroulement du Jeu &amp; Ères'), "Lien d'onglet 7 présent dans la barre de navigation");
assertTest(str_contains($viewContent, 'id="section-world-timeline-config"'), "Conteneur ancre de l'onglet 7 présent");
assertTest(str_contains($viewContent, 'Chronologie &amp; Paliers Historiques de l\'Archipel'), "Titre de la Frise Chronologique présent");
assertTest(str_contains($viewContent, 'card-body p-4 p-md-5'), "Padding généreux (p-4 p-md-5) présent sur le bloc Chronologie");
assertTest(str_contains($viewContent, 'value="broadcast_era_decree"'), "Bouton de diffusion de la dépêche impériale présent");
assertTest(str_contains($viewContent, 'Guerre des 12 Châteaux Authentiques (Ère III)'), "Console Guerre des 12 Châteaux présente");
assertTest(str_contains($viewContent, 'value="trigger_castles_war"'), "Bouton d'action trigger_castles_war présent");
assertTest(str_contains($viewContent, 'Radar Opérationnel des 12 Châteaux Authentiques'), "Radar des 12 Châteaux présent");
assertTest(str_contains($viewContent, 'Chantier du Dōjō Impérial [0 : 0] (Ère IV - Endgame)'), "Console Dōjō Impérial présente");
assertTest(str_contains($viewContent, 'value="trigger_imperial_dojo"'), "Bouton d'action trigger_imperial_dojo présent");
assertTest(str_contains($viewContent, 'Télémétrie en Direct de la Merveille [0 : 0]'), "Moniteur de la Merveille [0:0] présent");
assertTest(str_contains($viewContent, 'name="era_auto_progression_enabled"'), "Switch de progression automatique présent");
assertTest(str_contains($viewContent, 'name="era_castles_auto_day"'), "Champ de jour auto pour châteaux présent");
assertTest(str_contains($viewContent, 'name="era_dojo_auto_day"'), "Champ de jour auto pour dojo présent");
assertTest(str_contains($viewContent, 'id="selectServerCurrentEra"'), "Sélecteur de forçage de l'Ère active présent");
assertTest(str_contains($viewContent, 'onglets 1 à 8 sont enregistrées'), "Mention des onglets 1 à 8 dans le footer présente");

echo "\n[TEST 9] Vérification du contrôleur POST dans views/gamestudio.php...\n";
$controllerContent = file_get_contents(__DIR__ . '/../views/gamestudio.php');
assertTest(str_contains($controllerContent, "saveEraSettings(\$_POST)"), "saveEraSettings appelé dans save_all_world_settings");
assertTest(str_contains($controllerContent, "'trigger_castles_war'"), "Action trigger_castles_war gérée");
assertTest(str_contains($controllerContent, "'trigger_imperial_dojo'"), "Action trigger_imperial_dojo gérée");
assertTest(str_contains($controllerContent, "'set_world_era'"), "Action set_world_era gérée");
assertTest(str_contains($controllerContent, "'broadcast_era_decree'"), "Action broadcast_era_decree gérée");

echo "\n[TEST 10] Vérification des endpoints d'API dans api/admin.php...\n";
$apiContent = file_get_contents(__DIR__ . '/../api/admin.php');
assertTest(str_contains($apiContent, "case 'trigger_castles_war':"), "Endpoint API trigger_castles_war présent");
assertTest(str_contains($apiContent, "case 'trigger_imperial_dojo':"), "Endpoint API trigger_imperial_dojo présent");
assertTest(str_contains($apiContent, "case 'set_world_era':"), "Endpoint API set_world_era présent");
assertTest(str_contains($apiContent, "case 'get_world_timeline':"), "Endpoint API get_world_timeline présent");

echo "\n[TEST 11] Vérification de la diffusion des Dépêches Impériales à chaque passage d'Ère...\n";
for ($eraTest = 1; $eraTest <= 5; $eraTest++) {
    $bcOk = $gameStudio->broadcastEraTransition($eraTest);
    assertTest($bcOk === true, "Diffusion de la dépêche impériale pour l'Ère {$eraTest} réussie");
}

echo "\n[TEST 12] Vérification de la Modale Proclamation Impériale Illustrée HD Grand Format & Récits Développés...\n";
$headerContent = file_get_contents(__DIR__ . '/../views/partials/header.php');
assertTest(str_contains($headerContent, 'id="modalImperialProclamation"'), "Modale #modalImperialProclamation présente dans header.php");
assertTest(str_contains($headerContent, 'height: 260px;'), "En-tête illustré agrandi 260px présent dans header.php");
assertTest(str_contains($headerContent, 'overflow-y: auto'), "Défilement / scroll intérieur présent dans le corps de la modale");
assertTest(str_contains($headerContent, 'id="imperialModalTitle"'), "Titre dynamique impérial présent dans header.php");
assertTest(str_contains($headerContent, 'id="imperialModalSubtitle"'), "Sous-titre de la chronique présent dans header.php");
assertTest(str_contains($headerContent, 'id="imperialModalImpactList"'), "Liste des impacts stratégiques présente dans header.php");

assertTest(str_contains($viewContent, 'id="modalImperialProclamationStudio"'), "Modale #modalImperialProclamationStudio présente dans general_world_config.php");
assertTest(str_contains($viewContent, 'height: 260px;'), "En-tête illustré agrandi 260px présent dans general_world_config.php");
assertTest(str_contains($viewContent, 'overflow-y: auto'), "Défilement / scroll intérieur présent dans la modale studio");
assertTest(str_contains($viewContent, 'previewEraProclamationModal'), "Fonction de prévisualisation JS par Ère présente");
assertTest(str_contains($viewContent, 'openActiveBroadcastModal'), "Fonction d'ouverture de dépêche active JS présente");
assertTest(str_contains($viewContent, '/public/assets/ere/era_1_aube_des_clans.jpg'), "Illustration Ère 1 intégrée dans la timeline studio");
assertTest(str_contains($viewContent, '/public/assets/ere/era_5_unification_shogunat.jpg'), "Illustration Ère 5 intégrée dans la timeline studio");

require_once __DIR__ . '/../core/LiveOpsEngine.php';
$liveOps = new LiveOpsEngine();
$activeBc = $liveOps->getActiveBroadcast();
assertTest(!empty($activeBc), "Dépêche impériale active récupérée");
assertTest(str_contains($activeBc['image'], '/public/assets/ere/'), "Image de l'ère issue de public/assets/ere/");
assertTest(!empty($activeBc['badge']), "Badge de décret présent dans la dépêche impériale");
assertTest(!empty($activeBc['subtitle']), "Sous-titre littéraire présent dans la dépêche impériale");
assertTest(strlen($activeBc['message']) > 300, "Narration de l'ère richement développée (> 300 caractères)");
assertTest(!empty($activeBc['impact_points']) && count($activeBc['impact_points']) >= 3, "Au moins 3 directives stratégiques féodales détaillées");

echo "\n[TEST 13] Vérification de l'auto-synchronisation de la Proclamation avec l'Ère active (Ère 4)...\n";
GameConfig::set('server_current_era', 4);
assertTest(GameConfig::getServerCurrentEra() === 4, "Monde positionné sur l'Ère 4");

// Simulation d'une dépêche désynchronisée (ex: Ère 1 ou 5 résiduelle)
$liveOps->setBroadcast("Ancienne Proclamation Désynchronisée", "Message obsolète", "info", 60, true, ['era' => 1]);
$desyncBc = $liveOps->getActiveBroadcast();
assertTest((int)$desyncBc['era'] === 1, "Dépêche initialement désynchronisée (Ère 1)");

// Appel de getWorldLifecycleData() : doit détecter l'incohérence et resynchroniser vers l'Ère 4
$lifecycleSync = $gameStudio->getWorldLifecycleData();
assertTest(!empty($lifecycleSync['activeBroadcast']), "Dépêche resynchronisée présente dans le cycle de vie");
assertTest((int)$lifecycleSync['activeBroadcast']['era'] === 4, "La proclamation a été auto-corrigée vers l'Ère 4");
assertTest(str_contains($lifecycleSync['activeBroadcast']['title'], 'Ère IV'), "Le titre de la proclamation reflète fidèlement l'Ère IV");
assertTest(str_contains($lifecycleSync['activeBroadcast']['badge'], 'Ère IV'), "Le badge reflète l'Ère IV • Dōjō Impérial");

echo "\n[TEST 14] Vérification du reset vers l'Ère 1 et suppression du bandeau dans l'en-tête...\n";
// 1. Absence du bandeau de proclamation dans l'en-tête, préservation exclusive de la modale
assertTest(!str_contains($headerContent, 'alert alert-<?= $bcType ?>'), "Le bandeau alert en haut de l'en-tête a bien été supprimé");
assertTest(str_contains($headerContent, 'id="modalImperialProclamation"'), "La modale de proclamation est conservée et autonome");

// 2. Bascule stricte vers l'Ère 1 au reset
GameConfig::set('server_current_era', 1);
GameConfig::set('server_start_timestamp', time());
GameConfig::set('era_castles_unlocked', 0);
GameConfig::set('era_dojo_unlocked', 0);
GameConfig::set('era_auto_progression_enabled', 0); // Désactivé temporairement
$gameStudio->broadcastEraTransition(1);

$finalData = $gameStudio->getWorldLifecycleData();
assertTest((int)$finalData['currentEra'] === 1, "Le monde est strictement en Ère 1 (L'Aube des Clans)");
assertTest($finalData['eras'][1]['status'] === 'active', "L'Ère 1 est active");
assertTest($finalData['eras'][2]['status'] === 'locked', "L'Ère 2 est verrouillée");
assertTest((int)$finalData['activeBroadcast']['era'] === 1, "La proclamation est synchronisée avec l'Ère 1");

echo "\n[TEST 15] RÈGLE 1 : Transition vers l'Ère II conditionnée par la fin d'immunité de chaque joueur...\n";
// Réinitialisation de la table users
$sqlite->exec("DELETE FROM users");
// Ajout de 2 joueurs dont 1 a encore une protection active dans le futur
$sqlite->exec("INSERT INTO users (id, username, is_bot, protection_until) VALUES 
    (1, 'Daimyo_Oda', 0, datetime('now', '+3 days')),
    (2, 'Daimyo_Takeda', 0, datetime('now', '-1 hour'))
");

// Activer l'auto-progression
GameConfig::set('era_auto_progression_enabled', 1);
GameConfig::set('server_current_era', 1);

$cycleDataProtActive = $gameStudio->getWorldLifecycleData();
assertTest((int)$cycleDataProtActive['activeProtectionsCount'] === 1, "Détection correcte : 1 joueur encore sous protection");
assertTest((int)$cycleDataProtActive['currentEra'] === 1, "L'Ère reste en Ère 1 tant qu'au moins 1 joueur est protégé");
assertTest($cycleDataProtActive['eras'][2]['status'] === 'locked', "L'Ère 2 demeure verrouillée");

// Maintenant, fin de protection pour TOUS les joueurs
$sqlite->exec("UPDATE users SET protection_until = datetime('now', '-1 minute')");

$cycleDataProtExpired = $gameStudio->getWorldLifecycleData();
assertTest((int)$cycleDataProtExpired['activeProtectionsCount'] === 0, "Détection : 0 joueur sous protection");
assertTest((int)$cycleDataProtExpired['currentEra'] === 2, "Transition automatique vers l'Ère II déclenchée dès que l'immunité de chaque joueur a expiré");
assertTest($cycleDataProtExpired['eras'][2]['status'] === 'active', "L'Ère 2 est désormais active");

echo "\n[TEST 16] RÈGLE 2 : L'Ère III (Guerre des Châteaux) est STRICTEMENT manuelle (Admin Only)...\n";
// Simuler un âge de serveur très avancé (J+30) et era_castles_auto_day = 10
GameConfig::set('server_start_timestamp', time() - (30 * 86400));
GameConfig::set('server_current_era', 2);
GameConfig::set('era_castles_unlocked', 0);
GameConfig::set('era_castles_auto_day', 10);

$checkNoAutoWar = $gameStudio->getWorldLifecycleData();
assertTest(GameConfig::isCastlesWarUnlocked() === false, "La guerre des châteaux NE se déverrouille PAS automatiquement malgré J+30");
assertTest((int)$checkNoAutoWar['currentEra'] === 2, "Le serveur reste en Ère 2 malgré l'expiration théorique du calendrier");
assertTest($checkNoAutoWar['eras'][3]['status'] === 'locked', "L'Ère 3 reste verrouillée sans déclenchement admin");

// Déclenchement explicite par l'Administrateur
$adminWarTrigger = $gameStudio->triggerCastlesWar(true);
assertTest($adminWarTrigger['success'] === true, "L'administrateur déclenche manuellement la Guerre des 12 Châteaux");
assertTest(GameConfig::isCastlesWarUnlocked() === true, "Guerre des châteaux déverrouillée suite à l'ordre direct");
$afterAdminData = $gameStudio->getWorldLifecycleData();
assertTest((int)$afterAdminData['currentEra'] === 3, "Le monde passe en Ère 3 suite à l'ordre shōgunal manuel");
assertTest($afterAdminData['eras'][3]['status'] === 'active', "L'Ère 3 est active");

echo "\n[TEST 17] RÈGLE 3 : Déclenchement implicite de l'Ère IV par la fin de la Guerre des 12 Châteaux...\n";
// S'assurer que le dojo est verrouillé
GameConfig::set('era_dojo_unlocked', 0);
GameConfig::set('server_current_era', 3);

// Nettoyage et initialisation d'une alliance conquérante
$sqlite->exec("DELETE FROM alliances");
$sqlite->exec("DELETE FROM planets");
$sqlite->exec("INSERT INTO alliances (id, name, tag, leader_id) VALUES (1, 'Clan Oda Shogunate', 'ODA', 1)");
$sqlite->exec("UPDATE users SET alliance_id = 1 WHERE id = 1");

// Compléter authentic_castles pour avoir exactement 12 châteaux
for ($cid = 4; $cid <= 12; $cid++) {
    $sqlite->exec("INSERT OR REPLACE INTO authentic_castles (id, name, code, province, default_x, default_y, is_spawned) 
                   VALUES ($cid, 'Château Forteresse $cid', 'castle_$cid', 'Province_$cid', $cid, -$cid, 1)");
}
// Mettre à jour les 3 premiers châteaux comme spawned
$sqlite->exec("UPDATE authentic_castles SET is_spawned = 1");

// Créer 12 planètes détenues par le joueur 1 (membre de l'alliance ODA) et les assigner aux 12 châteaux
for ($pid = 1; $pid <= 12; $pid++) {
    $sqlite->exec("INSERT INTO planets (id, name, user_id, coord_x, coord_y) VALUES ($pid, 'Fief $pid', 1, $pid, -$pid)");
    $sqlite->exec("UPDATE authentic_castles SET planet_id = $pid WHERE id = $pid");
}

// Vérification de la transition implicite vers l'Ère IV
$lifecycleCastlesEnd = $gameStudio->getWorldLifecycleData();
assertTest($lifecycleCastlesEnd['isCastlesWarConcluded'] === true, "La guerre des châteaux est reconnue comme conclue (12/12 capturés)");
assertTest(GameConfig::isImperialDojoUnlocked() === true, "Le Dōjō Impérial s'est déverrouillé implicitement à la fin de la guerre des 12 châteaux");
assertTest((int)$lifecycleCastlesEnd['currentEra'] === 4, "Le monde a basculé automatiquement en Ère IV");
assertTest($lifecycleCastlesEnd['eras'][4]['status'] === 'active', "L'Ère IV (Dōjō Impérial [0:0]) est active");
assertTest($lifecycleCastlesEnd['eras'][3]['status'] === 'completed', "L'Ère III est marquée terminée");

echo "\n[TEST 18] RÈGLE 4 : Achèvement du Dōjō Niveau 10 -> Ère V et Déclaration Officielle de Fin du Jeu...\n";
// Le Dōjō est au niveau 0 initialement
assertTest(GameConfig::isWorldEndDeclared() === false, "Fin du jeu non déclarée au départ");

// Simuler l'achèvement du Dōjō au Niveau 10
$sqlite->exec("UPDATE imperial_dojo SET current_level = 10 WHERE id = 1");

$lifecycleDojoFinished = $gameStudio->getWorldLifecycleData();
assertTest((int)$lifecycleDojoFinished['currentEra'] === 5, "Le monde bascule en Ère V dès que le Dōjō atteint le Niveau 10");
assertTest($lifecycleDojoFinished['isWorldEndDeclared'] === true, "isWorldEndDeclared est vrai dans la télémétrie");
assertTest(GameConfig::isWorldEndDeclared() === true, "GameConfig::isWorldEndDeclared() confirme la déclaration de fin du jeu");
assertTest(GameConfig::getWorldEndTimestamp() > 0, "Horodatage officiel de fin du monde enregistré");
assertTest($lifecycleDojoFinished['eras'][5]['status'] === 'active', "L'Ère V est active");
assertTest($lifecycleDojoFinished['eras'][4]['status'] === 'completed', "L'Ère IV est marquée terminée");

echo "\n[TEST 19] Rendu UI & Déclencheurs Télémétriques des 4 règles...\n";
$viewPath = __DIR__ . '/../views/gamestudio/general_world_config.php';
$viewContent = file_get_contents($viewPath);

// 1. Déclencheurs télémétriques dans les métadonnées des Ères
assertTest($lifecycleDojoFinished['eras'][2]['trigger_label'] === "Fin de l'immunité de chaque joueur", "Trigger label Ère II = Fin de l'immunité de chaque joueur");
assertTest($lifecycleDojoFinished['eras'][3]['trigger_label'] === "Déclenchement Manuel Administrateur", "Trigger label Ère III = Déclenchement Manuel Administrateur");
assertTest($lifecycleDojoFinished['eras'][4]['trigger_label'] === "Fin de la Guerre des 12 Châteaux", "Trigger label Ère IV = Fin de la Guerre des 12 Châteaux");
assertTest($lifecycleDojoFinished['eras'][5]['trigger_label'] === "Niveau 10 du Dōjō Impérial Érigé", "Trigger label Ère V = Niveau 10 du Dōjō Impérial Érigé");

// 2. Badges et encarts dédiés dans general_world_config.php
assertTest(str_contains($viewContent, 'sous immunité'), "Compteur de joueurs sous immunité présent dans la frise Ère II");
assertTest(str_contains($viewContent, 'Manuel Admin'), "Badge Manuel Admin présent dans la frise Ère III");
assertTest(str_contains($viewContent, 'Ordre Shōgunal Direct'), "Console Ère III configurée en Ordre Shōgunal Direct");
assertTest(str_contains($viewContent, "Déclenchement Manuel Exclusif par l'Administrateur"), "Encart d'exclusivité manuelle présent en Ère III");
assertTest(str_contains($viewContent, "Déclenchement Implicite par la Fin de la Guerre des 12 Châteaux"), "Console Ère IV configurée sur le Déclenchement Implicite");
assertTest(str_contains($viewContent, "Fin du Jeu Déclarée"), "Badge Fin du Jeu Déclarée présent dans la frise Ère V");
assertTest(str_contains($viewContent, "FIN DU JEU DÉCLARÉE"), "Encart Fin du Jeu Déclarée présent dans la console Ère V");

// Rétablissement propre de la dépêche impériale sur l'Ère I pour l'environnement de jeu
$gameStudio->broadcastEraTransition(1);

echo "\n========================================================================\n";
echo " TOUS LES TESTS DU DÉROULEMENT DU JEU (ÈRE & ENDGAME) SONT 100% AU VERT !\n";
echo "========================================================================\n";
