<?php
/**
 * Test Automatisé : Configuration du Monde & Dimensionnement des Tuiles
 * Vérifie le calcul des tuiles (2R+1)², la persistance dans GameConfig,
 * les contrôles de sécurité et le rendu de la vue générale du Game Studio.
 * 
 * Fichier : tests/test_world_configuration.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GameConfig.php';
require_once __DIR__ . '/../core/GameStudioEngine.php';
require_once __DIR__ . '/../core/VillageFieldGenerator.php';

function assertTest(bool $condition, string $title): void {
    if ($condition) {
        echo "  [PASS] " . $title . "\n";
    } else {
        echo "  [FAIL] " . $title . "\n";
        exit(1);
    }
}

echo "========================================================================\n";
echo "  BANC DE TESTS : CONFIGURATION DU MONDE & TUILES GÉNÉRÉES\n";
echo "========================================================================\n\n";

// Initialisation d'une base SQLite en mémoire pour le banc de test
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
Database::setConnection($sqlite);

$sqlite->exec("CREATE TABLE game_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT,
    setting_type TEXT DEFAULT 'string',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$sqlite->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT,
    is_bot INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE planets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL,
    name TEXT,
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE authentic_castles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0,
    is_spawned INTEGER DEFAULT 1
)");

$sqlite->exec("CREATE TABLE oases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    owner_planet_id INTEGER NULL,
    name TEXT,
    oasis_type TEXT DEFAULT 'lake_rice_50',
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0,
    bonus_wood INTEGER DEFAULT 0,
    bonus_stone INTEGER DEFAULT 0,
    bonus_rice INTEGER DEFAULT 50,
    res_wood INTEGER DEFAULT 1000,
    res_stone INTEGER DEFAULT 1000,
    res_rice INTEGER DEFAULT 1000,
    res_max INTEGER DEFAULT 5000,
    last_loot_time INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE oasis_units (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    oasis_id INTEGER,
    unit_code TEXT,
    count INTEGER DEFAULT 0,
    is_wild INTEGER DEFAULT 1
)");

// Insertion de données de test
$sqlite->exec("INSERT INTO game_settings (setting_key, setting_value, setting_type) VALUES ('world_radius', '35', 'int')");
$sqlite->exec("INSERT INTO game_settings (setting_key, setting_value, setting_type) VALUES ('oasis_density_percent', '2.0', 'float')");
$sqlite->exec("INSERT INTO game_settings (setting_key, setting_value, setting_type) VALUES ('oasis_respawn_on_capture', '1', 'boolean')");
$sqlite->exec("INSERT INTO users (id, username, is_bot) VALUES (1, 'Nezzar', 0), (2, 'TakedaBot', 1)");
$sqlite->exec("INSERT INTO planets (user_id, name, coord_x, coord_y) VALUES (1, 'Château Nezzar', 1, 1), (2, 'Fort Takeda', -12, 18), (NULL, 'Terre Libre', 15, -20)");
$sqlite->exec("INSERT INTO authentic_castles (name, coord_x, coord_y, is_spawned) VALUES ('Château de Himeji', -25, -20, 1)");
$sqlite->exec("INSERT INTO oases (id, name, oasis_type, coord_x, coord_y, bonus_wood, bonus_rice) VALUES (1, 'Source Chaude de Kusatsu', 'lake_rice_50', 10, 15, 0, 50)");
$sqlite->exec("INSERT INTO oasis_units (oasis_id, unit_code, count, is_wild) VALUES (1, 'sanglier_sauvage', 25, 1), (1, 'loup_honshu', 15, 1)");

GameConfig::clearCache();

// [TEST 1] Formule mathématique cartésienne du nombre total de tuiles
echo "[TEST 1] Validation de la formule géométrique (2R + 1)²...\n";
assertTest(GameConfig::getTotalTilesCount(15) === 961, "Rayon R=15 => Grille 31x31 => 961 tuiles");
assertTest(GameConfig::getTotalTilesCount(35) === 5041, "Rayon R=35 => Grille 71x71 => 5 041 tuiles (Standard Shogun)");
assertTest(GameConfig::getTotalTilesCount(50) === 10201, "Rayon R=50 => Grille 101x101 => 10 201 tuiles (Grand Monde)");
assertTest(GameConfig::getTotalTilesCount(75) === 22801, "Rayon R=75 => Grille 151x151 => 22 801 tuiles (Archipel Épique)");
assertTest(GameConfig::getTotalTilesCount(100) === 40401, "Rayon R=100 => Grille 201x201 => 40 401 tuiles (Mégamonde)");

// [TEST 2] Valeur par défaut et lecture du rayon
echo "\n[TEST 2] Lecture de la configuration du rayon via GameConfig...\n";
$initialRadius = GameConfig::getWorldRadius();
assertTest($initialRadius >= 10, "Le rayon initial est valide ($initialRadius >= 10)");
$expectedTotal = ($initialRadius * 2 + 1) * ($initialRadius * 2 + 1);
assertTest(GameConfig::getTotalTilesCount() === $expectedTotal, "getTotalTilesCount() sans paramètre utilise le rayon actif ($expectedTotal tuiles)");

// [TEST 3] Méthode GameStudioEngine::getWorldConfigData()
echo "\n[TEST 3] Extraction de la télémétrie spatiale via GameStudioEngine...\n";
$gameStudioEngine = new GameStudioEngine();
$worldData = $gameStudioEngine->getWorldConfigData();

assertTest(isset($worldData['worldRadius']), "worldRadius présent dans la télémétrie");
assertTest(isset($worldData['totalTiles']), "totalTiles présent dans la télémétrie");
assertTest(isset($worldData['gridDimension']), "gridDimension présent dans la télémétrie");
assertTest(isset($worldData['boundaryPerimeter']), "boundaryPerimeter présent dans la télémétrie");
assertTest(isset($worldData['presets']) && count($worldData['presets']) >= 5, "Au moins 5 préréglages recommandés disponibles");
assertTest(isset($worldData['humanVillages']), "humanVillages présent dans la télémétrie");
assertTest(isset($worldData['authenticCastles']), "authenticCastles présent dans la télémétrie");
assertTest(isset($worldData['biomesEstimate']), "biomesEstimate calculé");
assertTest(isset($worldData['quadrants']) && isset($worldData['quadrants']['NO']), "Quadrants tactiques calculés");

// [TEST 4] Règles de validation et sauvegarde du rayon
echo "\n[TEST 4] Contrôles de limites et sauvegarde...\n";
$resTooSmall = $gameStudioEngine->saveWorldRadius(5);
assertTest($resTooSmall['success'] === false, "Rejet d'un rayon trop petit (< 10)");

$resTooLarge = $gameStudioEngine->saveWorldRadius(250);
assertTest($resTooLarge['success'] === false, "Rejet d'un rayon trop grand (> 200)");

$testRadius = 45;
$resValid = $gameStudioEngine->saveWorldRadius($testRadius);
assertTest($resValid['success'] === true, "Sauvegarde réussie d'un nouveau rayon R=$testRadius");
assertTest(GameConfig::getWorldRadius() === $testRadius, "GameConfig::getWorldRadius() renvoie immédiatement la nouvelle valeur ($testRadius)");
assertTest(GameConfig::getTotalTilesCount() === 8281, "8 281 tuiles calculées pour R=45 (91x91)");

// Restaurer le rayon initial
$gameStudioEngine->saveWorldRadius($initialRadius);
assertTest(GameConfig::getWorldRadius() === $initialRadius, "Restauration du rayon d'origine ($initialRadius)");

// [TEST 5] Détection de sécurité anti-rétrécissement
echo "\n[TEST 5] Test de l'avertissement de sécurité si R < coordonnées maximales...\n";
$minRequired = $worldData['minRequiredRadius'];
if ($minRequired > 10) {
    $resWarn = $gameStudioEngine->saveWorldRadius(10);
    assertTest($resWarn['success'] === true && !empty($resWarn['warning']), "Avertissement généré si R=10 < coordonnée maximale ($minRequired)");
    $gameStudioEngine->saveWorldRadius($initialRadius);
} else {
    echo "  [INFO] Coordonnées maximales <= 10, avertissement non applicable directement.\n";
}

// [TEST 6] Rendu HTML de la vue partielle general_world_config.php
echo "\n[TEST 6] Test du rendu MVC de la vue Configuration du Monde...\n";
ob_start();
// Injecter les variables nécessaires
extract($gameStudioEngine->getWorldConfigData());
require __DIR__ . '/../views/gamestudio/general_world_config.php';
$html = ob_get_clean();

assertTest(strpos($html, '1. Dimension du Monde &amp; Nombre de Tuiles Générées') !== false, "Le 1er item 'Dimension du Monde & Nombre de Tuiles Générées' est bien présent");
assertTest(strpos($html, 'id="worldRadiusSlider"') !== false, "Slider interactif présent dans le DOM");
assertTest(strpos($html, 'id="worldRadiusInput"') !== false, "Input numérique de rayon présent");
assertTest(strpos($html, 'id="kpi-total-tiles"') !== false, "Élément KPI du total de tuiles présent");
assertTest(strpos($html, 'applyWorldPreset') !== false, "Fonction JS de préréglages 1-clic présente");
assertTest(strpos($html, 'Biomes Naturels Déterministes') !== false, "Section des biomes procéduraux présente");
assertTest(strpos($html, 'Découpage Tactique des 4 Quadrants') !== false, "Section des 4 quadrants tactiques présente");

// [TEST 7] Item 2 : Configuration & Écosystème des Oasis
echo "\n[TEST 7] Test de l'Item 2 : Configuration des Oasis et Calcul Dynamique...\n";
$saveOasisRes = $gameStudioEngine->saveOasisSettings(2.5, true);
assertTest($saveOasisRes['success'] === true, "saveOasisSettings(2.5, true) exécuté avec succès");
assertTest((float)GameConfig::get('oasis_density_percent') === 2.5, "oasis_density_percent mis à jour à 2.5%");
assertTest((bool)GameConfig::get('oasis_respawn_on_capture') === true, "oasis_respawn_on_capture mis à jour à true");

// Vérifier les nouvelles données extraites
$refreshedWorldData = $gameStudioEngine->getWorldConfigData();
assertTest(isset($refreshedWorldData['oasisDensityPercent']) && $refreshedWorldData['oasisDensityPercent'] === 2.5, "oasisDensityPercent reflété dans getWorldConfigData");
assertTest(isset($refreshedWorldData['targetOasesCount']) && $refreshedWorldData['targetOasesCount'] === 126, "targetOasesCount calculé pour 5041 tuiles à 2.5% (= 126 oasis)");
assertTest(isset($refreshedWorldData['allOasesList']) && count($refreshedWorldData['allOasesList']) >= 1, "allOasesList contient les oasis du monde");
assertTest(isset($refreshedWorldData['oasisFaunaCount']) && $refreshedWorldData['oasisFaunaCount'] === 40, "oasisFaunaCount compte exactement les bêtes sauvages (25 + 15 = 40)");

// Rendu HTML Item 2
assertTest(strpos($html, '2. Configuration &amp; Écosystème des Oasis Naturelles') !== false, "Le 2e item 'Configuration & Écosystème des Oasis Naturelles' est présent");
assertTest(strpos($html, 'id="oasisDensitySlider"') !== false, "Slider de densité des oasis présent dans le DOM");
assertTest(strpos($html, 'id="oasisRespawnToggle"') !== false, "Switch de réapparition automatique présent");
assertTest(strpos($html, 'id="formRepopulateOases"') !== false, "Formulaire de génération & rééquilibrage présent");
assertTest(strpos($html, 'id="formRespawnFauna"') !== false, "Formulaire de régénération de faune présent");
assertTest(strpos($html, 'id="tableOasesList"') !== false, "Registre interactif des oasis présent dans la vue");

// [TEST 8] Item 3 : Vitesses & Équilibrage du Monde Féodal
echo "\n[TEST 8] Test de l'Item 3 : Vitesses, Équilibrage & Simulateur d'Impact...\n";
$speedParams = [
    'game_speed'               => 10,
    'resource_speed'           => 8,
    'fleet_speed'              => 5,
    'hero_xp_rate_percent'     => 150,
    'hero_cage_drop_rate'      => 30,
    'hero_bandage_drop_rate'   => 25,
    'famine_enabled'           => '1',
    'famine_rate'              => 4.5,
    'famine_flour_consumption' => 1.5
];
$saveSpeedRes = $gameStudioEngine->saveSpeedBalancing($speedParams);
assertTest($saveSpeedRes['success'] === true, "saveSpeedBalancing() exécuté avec succès");
assertTest((int)GameConfig::get('game_speed') === 10, "game_speed mis à jour à 10");
assertTest((int)GameConfig::get('resource_speed') === 8, "resource_speed mis à jour à 8");
assertTest((int)GameConfig::get('fleet_speed') === 5, "fleet_speed mis à jour à 5");
assertTest((int)GameConfig::get('hero_xp_rate_percent') === 150, "hero_xp_rate_percent mis à jour à 150%");
assertTest((int)GameConfig::get('hero_cage_drop_rate') === 30, "hero_cage_drop_rate mis à jour à 30%");
assertTest((int)GameConfig::get('hero_bandage_drop_rate') === 25, "hero_bandage_drop_rate mis à jour à 25%");
assertTest((bool)GameConfig::get('famine_enabled') === true, "famine_enabled mis à jour à true");
assertTest((float)GameConfig::get('famine_rate') === 4.5, "famine_rate mis à jour à 4.5%");
assertTest((float)GameConfig::get('famine_flour_consumption') === 1.5, "famine_flour_consumption mis à jour à 1.5");

// Vérifier les nouvelles données extraites dans getWorldConfigData
$speedWorldData = $gameStudioEngine->getWorldConfigData();
assertTest($speedWorldData['gameSpeed'] === 10, "gameSpeed présent dans getWorldConfigData");
assertTest($speedWorldData['resourceSpeed'] === 8, "resourceSpeed présent dans getWorldConfigData");
assertTest($speedWorldData['fleetSpeed'] === 5, "fleetSpeed présent dans getWorldConfigData");
assertTest($speedWorldData['heroXpRatePercent'] === 150, "heroXpRatePercent présent dans getWorldConfigData");
assertTest($speedWorldData['heroCageDropRate'] === 30, "heroCageDropRate présent dans getWorldConfigData");
assertTest($speedWorldData['heroBandageDropRate'] === 25, "heroBandageDropRate présent dans getWorldConfigData");
assertTest($speedWorldData['famineEnabled'] === true, "famineEnabled présent dans getWorldConfigData");
assertTest($speedWorldData['famineRate'] === 4.5, "famineRate présent dans getWorldConfigData");
assertTest($speedWorldData['famineFlourConsumption'] === 1.5, "famineFlourConsumption présent dans getWorldConfigData");

// Rendu HTML complet avec l'Item 3 rafraîchi
ob_start();
extract($speedWorldData);
require __DIR__ . '/../views/gamestudio/general_world_config.php';
$htmlSpeed = ob_get_clean();

assertTest(strpos($htmlSpeed, '3. Vitesses &amp; Équilibrage du Monde Féodal') !== false, "Le 3e item 'Vitesses & Équilibrage du Monde Féodal' est présent dans la vue");
assertTest(strpos($htmlSpeed, 'id="section-world-speed-config"') !== false, "Conteneur ancre de l'Item 3 présent");
assertTest(strpos($htmlSpeed, 'id="sliderGameSpeed"') !== false, "Slider Vitesse Jeu présent");
assertTest(strpos($htmlSpeed, 'id="sliderResourceSpeed"') !== false, "Slider Vitesse Production présent");
assertTest(strpos($htmlSpeed, 'id="sliderFleetSpeed"') !== false, "Slider Vitesse Marche présent");
assertTest(strpos($htmlSpeed, 'id="sim-build-time"') !== false, "Simulateur temps de chantier présent");
assertTest(strpos($htmlSpeed, 'id="sim-march-time"') !== false, "Simulateur temps de marche présent");
assertTest(strpos($htmlSpeed, 'id="sim-resource-prod"') !== false, "Simulateur production horaire présent");
assertTest(strpos($htmlSpeed, 'applyDevSpeedPreset') !== false, "Fonction JS de préréglages de vitesse présente");
assertTest(strpos($htmlSpeed, 'id="checkFamineEnabled"') !== false, "Switch de péril de famine présent");
assertTest(strpos($htmlSpeed, 'id="sliderBandageDrop"') !== false, "Slider Taux de Bandages présent dans Item 3");

// [TEST 9] Item 4 : Dotation Initiale du Nouveau Seigneur & Immunité Débutant
echo "\n[TEST 9] Test de l'Item 4 : Dotation Initiale (Bois, Pierre, Riz, Poisson, Or) & Immunité...\n";
$starterParams = [
    'starter_wood'             => 2500,
    'starter_stone'            => 2200,
    'starter_rice'             => 1800,
    'starter_fish'             => 450,
    'starter_gold_coins'       => 200,
    'beginner_protection_days' => 14
];
$saveStarterRes = $gameStudioEngine->saveStartingEndowment($starterParams);
assertTest($saveStarterRes['success'] === true, "saveStartingEndowment() exécuté avec succès");
assertTest((int)GameConfig::get('starter_wood') === 2500, "starter_wood mis à jour à 2 500");
assertTest((int)GameConfig::get('starter_stone') === 2200, "starter_stone mis à jour à 2 200");
assertTest((int)GameConfig::get('starter_rice') === 1800, "starter_rice mis à jour à 1 800");
assertTest((int)GameConfig::get('starter_fish') === 450, "starter_fish mis à jour à 450");
assertTest((int)GameConfig::get('starter_gold_coins') === 200, "starter_gold_coins mis à jour à 200 Ryō");
assertTest((int)GameConfig::get('beginner_protection_days') === 14, "beginner_protection_days mis à jour à 14 jours");

// Données enrichies dans getWorldConfigData
$starterWorldData = $gameStudioEngine->getWorldConfigData();
assertTest($starterWorldData['starterWood'] === 2500, "starterWood présent dans getWorldConfigData");
assertTest($starterWorldData['starterStone'] === 2200, "starterStone présent dans getWorldConfigData");
assertTest($starterWorldData['starterRice'] === 1800, "starterRice présent dans getWorldConfigData");
assertTest($starterWorldData['starterFish'] === 450, "starterFish présent dans getWorldConfigData");
assertTest($starterWorldData['starterGoldCoins'] === 200, "starterGoldCoins présent dans getWorldConfigData");
assertTest($starterWorldData['beginnerProtectionDays'] === 14, "beginnerProtectionDays reflété dans getWorldConfigData");

// Rendu HTML complet avec l'Item 4
ob_start();
extract($starterWorldData);
require __DIR__ . '/../views/gamestudio/general_world_config.php';
$htmlStarter = ob_get_clean();

assertTest(strpos($htmlStarter, '4. Dotation Initiale du Nouveau Seigneur &amp; Durée de l\'Immunité') !== false, "Le 4e item 'Dotation Initiale & Durée de l\'Immunité' est présent dans la vue");
assertTest(strpos($htmlStarter, 'id="section-world-starting-endowment"') !== false, "Conteneur ancre de l'Item 4 présent");
assertTest(strpos($htmlStarter, 'id="sliderStarterWood"') !== false, "Slider Bois initial présent");
assertTest(strpos($htmlStarter, 'id="sliderStarterStone"') !== false, "Slider Pierre initiale présent");
assertTest(strpos($htmlStarter, 'id="sliderStarterRice"') !== false, "Slider Riz initial présent");
assertTest(strpos($htmlStarter, 'id="sliderStarterFish"') !== false, "Slider Poisson initial présent");
assertTest(strpos($htmlStarter, 'id="sliderStarterGold"') !== false, "Slider Or initial présent");
assertTest(strpos($htmlStarter, 'id="sliderStarterProtection"') !== false, "Slider Immunité débutant présent");
assertTest(strpos($htmlStarter, 'id="sim-tenshu-level"') !== false, "Simulateur Tenshu initial présent");
assertTest(strpos($htmlStarter, 'id="sim-plots-count"') !== false, "Simulateur parcelles initiales présent");
assertTest(strpos($htmlStarter, 'applyStarterPreset') !== false, "Fonction JS applyStarterPreset présente");

// [TEST 10] Test de l'Item 5 : Gestion des Clans IA & Daimyōs Autonomes
echo "\n[TEST 10] Test de l'Item 5 : Gestion des Clans IA & Daimyōs Autonomes...\n";

$botParams = [
    'bots_enabled'           => '1',
    'bot_colonize_enabled'   => '1',
    'bot_spawn_enabled'      => '1',
    'bot_aggressiveness'     => 'aggressive',
    'bot_max_planets'        => 5,
    'bot_spawn_interval_min' => 20,
    'bot_spawn_max_villages' => 75
];
$saveBotRes = $gameStudioEngine->saveBotsSettings($botParams);
assertTest($saveBotRes['success'] === true, "saveBotsSettings() exécuté avec succès");
assertTest((bool)GameConfig::get('bots_enabled') === true, "bots_enabled mis à jour à true");
assertTest((bool)GameConfig::get('bot_colonize_enabled') === true, "bot_colonize_enabled mis à jour à true");
assertTest((bool)GameConfig::get('bot_spawn_enabled') === true, "bot_spawn_enabled mis à jour à true");
assertTest(GameConfig::get('bot_aggressiveness') === 'aggressive', "bot_aggressiveness mis à jour à aggressive");
assertTest((int)GameConfig::get('bot_max_planets') === 5, "bot_max_planets mis à jour à 5");
assertTest((int)GameConfig::get('bot_spawn_interval_min') === 20, "bot_spawn_interval_min mis à jour à 20");
assertTest((int)GameConfig::get('bot_spawn_max_villages') === 75, "bot_spawn_max_villages mis à jour à 75");

$botWorldData = $gameStudioEngine->getWorldConfigData();
assertTest($botWorldData['botsEnabled'] === true, "botsEnabled présent dans getWorldConfigData");
assertTest($botWorldData['botColonizeEnabled'] === true, "botColonizeEnabled présent dans getWorldConfigData");
assertTest($botWorldData['botSpawnEnabled'] === true, "botSpawnEnabled présent dans getWorldConfigData");
assertTest($botWorldData['botAggressiveness'] === 'aggressive', "botAggressiveness présent dans getWorldConfigData");
assertTest($botWorldData['botMaxPlanets'] === 5, "botMaxPlanets présent dans getWorldConfigData");
assertTest($botWorldData['botSpawnIntervalMin'] === 20, "botSpawnIntervalMin présent dans getWorldConfigData");
assertTest($botWorldData['botSpawnMaxVillages'] === 75, "botSpawnMaxVillages présent dans getWorldConfigData");
assertTest(isset($botWorldData['botsList']), "botsList présent dans getWorldConfigData");

// Rendu HTML complet avec l'Item 5 et l'Item 6
ob_start();
extract($botWorldData);
require __DIR__ . '/../views/gamestudio/general_world_config.php';
$htmlBots = ob_get_clean();

assertTest(strpos($htmlBots, '5. Gestion des Clans IA &amp; Daimyōs Autonomes') !== false, "Le 5e item 'Gestion des Clans IA & Daimyōs Autonomes' est présent dans la vue");
assertTest(strpos($htmlBots, 'id="section-world-bots-config"') !== false, "Conteneur ancre de l'Item 5 présent");
assertTest(strpos($htmlBots, 'id="switchBotsEnabled"') !== false, "Switch Moteur IA présent");
assertTest(strpos($htmlBots, 'id="switchBotColonize"') !== false, "Switch Colonisation IA présent");
assertTest(strpos($htmlBots, 'id="switchBotSpawn"') !== false, "Switch Éclosion Spontanée présent");
assertTest(strpos($htmlBots, 'id="sliderBotMaxPlanets"') !== false, "Slider Fiefs max par clan présent");
assertTest(strpos($htmlBots, 'id="sliderBotSpawnInterval"') !== false, "Slider Intervalle d'éclosion présent");
assertTest(strpos($htmlBots, 'id="sliderBotSpawnMax"') !== false, "Slider Plafond mondial fiefs PNJ présent");
assertTest(strpos($htmlBots, 'spawn_preset_bots') !== false, "Action Déployer 3 Daimyōs IA présente");
assertTest(strpos($htmlBots, 'trigger_spontaneous_spawn') !== false, "Action Éclosion Spontanée 360° présente");
assertTest(strpos($htmlBots, 'run_bot_cycle') !== false, "Action Cycle de Décision IA présente");
assertTest(strpos($htmlBots, 'updateBotsEnabledUI') !== false, "Fonction JS updateBotsEnabledUI présente");
assertTest(strpos($htmlBots, 'updateAggressivenessBadge') !== false, "Fonction JS updateAggressivenessBadge présente");
assertTest(strpos($htmlBots, 'applyWorldBotsPreset') !== false, "Fonction JS applyWorldBotsPreset présente");
assertTest(strpos($htmlBots, 'Directives Stratégiques &amp; Règles de Décision des PNJ') !== false, "Bloc Directives Stratégiques & Règles de Décision présent");
assertTest(strpos($htmlBots, '1. Décision Économique &amp; Parcelles Rurales') !== false, "Règle 1 Économie & 18 parcelles présente");
assertTest(strpos($htmlBots, '2. Décision d\'Urbanisme &amp; Fortifications') !== false, "Règle 2 Urbanisme & Tenshu présente");
assertTest(strpos($htmlBots, '3. Conscription Martiale selon la Faction') !== false, "Règle 3 Conscription militaire présente");
assertTest(strpos($htmlBots, '4. Règles d\'Engagement &amp; Raids Féodaux') !== false, "Règle 4 Raids & Exclusion débutants présente");
assertTest(strpos($htmlBots, '5. Reconnaissance Shinobi &amp; Infiltration') !== false, "Règle 5 Infiltration Shinobi présente");
assertTest(strpos($htmlBots, '6. Colonisation &amp; Expansion Provinciale') !== false, "Règle 6 Colonisation & Fiefs présente");
assertTest(strpos($htmlBots, 'executeBotCycle() &bull; JSON Output') !== false, "Console simulée BotEngine avec JSON output présente");

// [TEST 11] Test de l'Item 6 : Régénération du Monde & Conservation des Joueurs Humains
echo "\n[TEST 11] Test de l'Item 6 : Régénération & Conservation des Joueurs Humains...\n";

// Sauvegarde de l'option à true
$saveRegenRes = $gameStudioEngine->saveRegenerationSettings(true);
assertTest($saveRegenRes['success'] === true, "saveRegenerationSettings(true) exécuté avec succès");
assertTest((bool)GameConfig::get('preserve_human_users_on_reset') === true, "preserve_human_users_on_reset mis à jour à true");

// Sauvegarde de l'option à false
$saveRegenResFalse = $gameStudioEngine->saveRegenerationSettings(false);
assertTest($saveRegenResFalse['success'] === true, "saveRegenerationSettings(false) exécuté avec succès");
assertTest((bool)GameConfig::get('preserve_human_users_on_reset') === false, "preserve_human_users_on_reset mis à jour à false");

// Restauration à true pour les joueurs
$gameStudioEngine->saveRegenerationSettings(true);
$regenWorldData = $gameStudioEngine->getWorldConfigData();
assertTest($regenWorldData['preserveHumanUsers'] === true, "preserveHumanUsers présent et égal à true dans getWorldConfigData");

// Rendu HTML complet avec l'Item 6
ob_start();
extract($regenWorldData);
require __DIR__ . '/../views/gamestudio/general_world_config.php';
$htmlRegen = ob_get_clean();

assertTest(strpos($htmlRegen, '6. Régénération &amp; Réinitialisation du Monde Féodal') !== false, "Le 6e item 'Régénération & Réinitialisation du Monde Féodal' est présent dans la vue");
assertTest(strpos($htmlRegen, 'id="section-world-regeneration-config"') !== false, "Conteneur ancre de l'Item 6 présent");
assertTest(strpos($htmlRegen, 'id="switchPreserveHumanUsers"') !== false, "Switch 'Conserver les Joueurs Humains' présent dans le DOM");
assertTest(strpos($htmlRegen, 'Conserver les Joueurs Humains') !== false, "Libellé 'Conserver les Joueurs Humains' présent");
assertTest(strpos($htmlRegen, 'Conserver les comptes des joueurs déjà présents dans le monde après la régénération du monde') !== false, "Description explicative 'Conserver les comptes des joueurs déjà présents...' présente");
assertTest(strpos($htmlRegen, 'id="modalStudioRegenerateWorld"') !== false, "Modale de confirmation sécurisée présente");
assertTest(strpos($htmlRegen, 'id="inputRegenConfirmKeyword"') !== false, "Champ mot-clé de sécurité RESET présent");
assertTest(strpos($htmlRegen, 'id="btnConfirmRegenerateWorld"') !== false, "Bouton d'exécution de la régénération présent");
assertTest(strpos($htmlRegen, 'btnAutoFillRegenKeyword') !== false, "Bouton de remplissage 'RESET' en 1-clic présent");
assertTest(strpos($htmlRegen, 'checkAcknowledgeRegen') !== false, "Case d'attestation de réinitialisation présente");
assertTest(strpos($htmlRegen, 'labelRegenValidationStatus') !== false, "Indicateur d'état de déverrouillage présent");
assertTest(strpos($htmlRegen, 'checkRegenValidation') !== false, "Fonction JS checkRegenValidation présente");
assertTest(strpos($htmlRegen, 'autoFillRegenKeyword') !== false, "Fonction JS autoFillRegenKeyword présente");
assertTest(strpos($htmlRegen, 'updatePreserveHumanUsersUI') !== false, "Fonction JS updatePreserveHumanUsersUI présente");
assertTest(strpos($htmlRegen, 'openStudioRegenerateModal') !== false, "Fonction JS openStudioRegenerateModal présente");

// [TEST 12] Test de Game Elevate Designer : Clans IA & Daimyōs Autonomes (elevate_bots.php)
echo "\n[TEST 12] Test de Game Elevate Designer : Directives Stratégiques & Règles PNJ...\n";

$elevateBotsScope = [
    'botsList' => $botWorldData['botsList'],
    'settings' => [
        'bots_enabled' => 1,
        'bot_colonize_enabled' => 1,
        'bot_max_planets' => 3,
        'bot_aggressiveness' => 'moderate',
        'bot_spawn_enabled' => 1,
        'bot_spawn_interval_min' => 15,
        'bot_spawn_max_villages' => 50
    ],
    'botSpawnStatus' => [
        'enabled' => true,
        'current_villages' => 3,
        'next_spawn_in_formatted' => '12m 30s',
        'last_spawn_formatted' => 'Il y a 5 min',
        'quadrants' => ['NO' => 1, 'NE' => 1, 'SO' => 1, 'SE' => 0]
    ]
];

ob_start();
extract($elevateBotsScope);
require __DIR__ . '/../views/gamestudio/elevate_bots.php';
$htmlElevateBots = ob_get_clean();

assertTest(strpos($htmlElevateBots, 'Directives Stratégiques &amp; Règles de Décision des PNJ') !== false, "Titre Directives Stratégiques présent dans elevate_bots.php");
assertTest(strpos($htmlElevateBots, 'applyElevateBotPreset') !== false, "Fonction JS applyElevateBotPreset présente");
assertTest(strpos($htmlElevateBots, 'Bonsaï Défensif') !== false, "Preset Bonsaï Défensif présent dans Elevate Designer");
assertTest(strpos($htmlElevateBots, 'Sengoku Équilibré') !== false, "Preset Sengoku Équilibré présent dans Elevate Designer");
assertTest(strpos($htmlElevateBots, 'Tempête Martiale Oda') !== false, "Preset Tempête Martiale Oda présent dans Elevate Designer");
assertTest(strpos($htmlElevateBots, '1. Décision Économique &amp; Parcelles Rurales') !== false, "Arbre 1 Économie présent dans Elevate Designer");
assertTest(strpos($htmlElevateBots, '2. Décision d\'Urbanisme &amp; Fortifications') !== false, "Arbre 2 Urbanisme présent dans Elevate Designer");
assertTest(strpos($htmlElevateBots, '3. Conscription Martiale selon la Faction') !== false, "Arbre 3 Conscription présent dans Elevate Designer");
assertTest(strpos($htmlElevateBots, '4. Règles d\'Engagement &amp; Raids Féodaux') !== false, "Arbre 4 Raids & Exclusion débutants présent dans Elevate Designer");
assertTest(strpos($htmlElevateBots, '5. Reconnaissance Shinobi &amp; Infiltration') !== false, "Arbre 5 Shinobi présent dans Elevate Designer");
assertTest(strpos($htmlElevateBots, '6. Colonisation &amp; Expansion Provinciale') !== false, "Arbre 6 Colonisation présent dans Elevate Designer");
assertTest(strpos($htmlElevateBots, 'executeBotCycle() &bull; JSON Output') !== false, "Console simulée d'exécution de cycle présente dans Elevate Designer");

echo "\n========================================================================\n";
echo " TOUS LES TESTS MONDE & ELEVATE DESIGNER SONT 100% AU VERT ! \n";
echo "========================================================================\n";


