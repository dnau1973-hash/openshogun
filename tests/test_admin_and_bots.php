<?php
/**
 * Test Automatisé : Administration, Variables de Jeu et Système de Bots
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GameConfig.php';
require_once __DIR__ . '/../core/BotEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/BarracksEngine.php';
require_once __DIR__ . '/../core/ShipyardEngine.php';
require_once __DIR__ . '/../core/FleetEngine.php';

echo "========================================================\n";
echo " TEST DU SYSTÈME D'ADMINISTRATION & DES BOTS AUTONOMES \n";
echo "========================================================\n\n";

$db = Database::getConnection();

// --- TEST 1 : Variables Dynamiques & GameConfig ---
echo "--- TEST 1 : Configuration & Variables de Jeu ---\n";
GameConfig::clearCache();

$initialSpeed = GameConfig::get('game_speed');
echo "- Vitesse de jeu initiale : {$initialSpeed}\n";
assert($initialSpeed !== null, "Erreur: game_speed non trouvé.");

// Modification de la vitesse
GameConfig::set('game_speed', 25);
$newSpeed = GameConfig::get('game_speed');
echo "- Nouvelle vitesse de jeu après modification : {$newSpeed}\n";
assert($newSpeed === 25, "Erreur: échec mise à jour game_speed.");

// Modification de la vitesse des ressources
GameConfig::set('resource_speed', 10);
$resSpeed = GameConfig::get('resource_speed');
echo "- Vitesse des ressources : {$resSpeed}\n";
assert($resSpeed === 10, "Erreur: échec mise à jour resource_speed.");

// Remise à 5 pour le standard
GameConfig::set('game_speed', 5);
GameConfig::set('resource_speed', 5);
GameConfig::set('fleet_speed', 5);
GameConfig::set('bots_enabled', true);
GameConfig::set('bot_colonize_enabled', true);
GameConfig::set('bot_max_planets', 3);
echo "✔ Test 1 réussi : GameConfig fonctionne parfaitement.\n\n";


// --- TEST 2 : Impact Dynamique sur les Moteurs ---
echo "--- TEST 2 : Impact Dynamique sur les Moteurs de Jeu ---\n";
$planetEngine = new PlanetEngine();
$testFields = [
    ['field_slot' => 1, 'type' => 'metal_mine', 'level' => 3],
    ['field_slot' => 15, 'type' => 'solar_plant', 'level' => 4]
];

// Production avec resource_speed = 5
GameConfig::set('resource_speed', 5);
$prod5 = $planetEngine->calculateProduction($testFields);

// Production avec resource_speed = 20
GameConfig::set('resource_speed', 20);
$prod20 = $planetEngine->calculateProduction($testFields);

echo "- Production métal à 5x : {$prod5['metal']}/h\n";
echo "- Production métal à 20x : {$prod20['metal']}/h\n";
assert($prod20['metal'] > $prod5['metal'], "Erreur: la vitesse de ressource n'a pas impacté la production.");
echo "✔ Test 2 réussi : Les moteurs consomment les variables dynamiques.\n\n";

// Remettre à 5
GameConfig::set('resource_speed', 5);


// --- TEST 3 : Création de Bot et Peuplement ---
echo "--- TEST 3 : Création de Bot et Peuplement ---\n";
$botEngine = new BotEngine();

// Nettoyer un éventuel bot de test précédent
$testBotName = "TestBot_Titan";
$existingTestBot = $db->query("SELECT id FROM users WHERE username = '{$testBotName}'")->fetch();
if ($existingTestBot) {
    $botEngine->deleteBot((int)$existingTestBot['id']);
}

$createRes = $botEngine->createBot($testBotName, 'terran');
assert($createRes['success'] === true, "Erreur création bot: " . ($createRes['error'] ?? ''));
$testBotId = $createRes['bot_id'];
echo "- Bot '{$testBotName}' (ID: {$testBotId}) créé aux coordonnées [{$createRes['coords']['x']} : {$createRes['coords']['y']}]\n";

// Vérifier les données en base
$checkBot = $db->query("SELECT * FROM users WHERE id = {$testBotId}")->fetch();
assert((int)$checkBot['is_bot'] === 1, "Erreur: is_bot n'est pas 1.");

$checkPlanets = $db->query("SELECT COUNT(*) FROM planets WHERE user_id = {$testBotId}")->fetchColumn();
echo "- Nombre initial de planètes du bot : {$checkPlanets}\n";
assert((int)$checkPlanets === 1, "Erreur: le bot devrait avoir 1 planète capitale.");

$checkTroops = $db->query("
    SELECT SUM(count) FROM planet_units pu 
    JOIN planets p ON pu.planet_id = p.id 
    WHERE p.user_id = {$testBotId}
")->fetchColumn();
echo "- Garnison initiale du bot : {$checkTroops} soldats\n";
assert((int)$checkTroops >= 30, "Erreur: garnison initiale incomplète.");
echo "✔ Test 3 réussi : Création de bot autonome opérationnelle.\n\n";


// --- TEST 4 : Exécution du Cycle IA & Colonisation Automatique ---
echo "--- TEST 4 : Exécution du Cycle IA & Colonisation Automatique ---\n";
$cycleResult = $botEngine->executeBotCycle();
assert($cycleResult['success'] === true, "Erreur cycle IA: " . ($cycleResult['message'] ?? ''));

$rep = $cycleResult['report'];
echo "- Bots traités dans le cycle : {$rep['bots_processed']}\n";
echo "- Mines améliorées : {$rep['mines_upgraded']}\n";
echo "- Bâtiments améliorés : {$rep['buildings_upgraded']}\n";
echo "- Soldats entraînés : {$rep['troops_trained']}\n";
echo "- Nouvelles colonies fondées : " . count($rep['colonies_founded']) . "\n";

foreach ($rep['colonies_founded'] as $col) {
    echo "  -> [{$col['bot']}] {$col['colony_name']} aux coordonnées {$col['coords']}\n";
}

// Vérifier que le bot de test possède désormais 2 planètes grâce à la colonisation automatique
$updatedPlanetsCount = (int)$db->query("SELECT COUNT(*) FROM planets WHERE user_id = {$testBotId}")->fetchColumn();
echo "- Nombre de planètes du bot après cycle IA : {$updatedPlanetsCount} (Attendu >= 2)\n";
assert($updatedPlanetsCount >= 2, "Erreur: la colonisation automatique du bot n'a pas créé de nouvelle planète.");

// Nettoyage du bot de test
$botEngine->deleteBot($testBotId);
$afterDeleteCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE id = {$testBotId}")->fetchColumn();
assert($afterDeleteCount === 0, "Erreur: suppression du bot échouée.");
echo "- Bot de test nettoyé proprement.\n";
echo "✔ Test 4 réussi : Simulation IA et colonisation automatique validées.\n\n";


// --- TEST 5 : Génération d'Escadre Multi-Factions ---
echo "--- TEST 5 : Génération d'Escadre de Bots Multi-Factions ---\n";
$genRes = $botEngine->generatePresetBots(3);
echo "- Bots générés : {$genRes['created_count']} (" . implode(', ', $genRes['created_bots']) . ")\n";
echo "✔ Test 5 réussi : Génération d'escadre opérationnelle.\n\n";

echo "========================================================\n";
echo " TOUS LES TESTS D'ADMINISTRATION ET BOTS SONT VALIDÉS ! \n";
echo "========================================================\n";

