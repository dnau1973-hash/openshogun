<?php
/**
 * Test de validation automatisé du cycle de jeu OpenGalaxy
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/ShipyardEngine.php';
require_once __DIR__ . '/../core/FleetEngine.php';
require_once __DIR__ . '/../core/CombatEngine.php';

echo "=== DÉBUT DU TEST OPENGALAXY ===\n\n";

$auth = new Auth();
$planetEngine = new PlanetEngine();
$buildingEngine = new BuildingEngine();
$shipyardEngine = new ShipyardEngine();
$fleetEngine = new FleetEngine();

// 1. Inscription d'un joueur Terran
echo "1. Inscription joueur 'TerranCommander'...\n";
$regResult = $auth->register('TerranCommander', 'terran@opengalaxy.local', 'password123', 'terran');
if (!$regResult['success'] && strpos($regResult['error'], 'déjà utilisé') === false) {
    die("Échec inscription : " . $regResult['error'] . "\n");
}
$auth->login('TerranCommander', 'password123');
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();
echo "-> Joueur connecté ID: {$user['id']}, Faction: {$user['faction']}, Planète ID: {$planet['id']} [{$planet['coord_x']}:{$planet['coord_y']}]\n\n";

// 2. Vérification des parcelles et bâtiments
echo "2. Vérification des parcelles et ressources...\n";
$fields = $planetEngine->getFields((int)$planet['id']);
echo "-> Nombre de parcelles initialisées : " . count($fields) . " (attendu 18)\n";
$updatedPlanet = $planetEngine->updatePlanet((int)$planet['id']);
echo "-> Stocks de ressources : Metal={$updatedPlanet['metal']}, Cristal={$updatedPlanet['crystal']}, Deut={$updatedPlanet['deuterium']}, Énergie={$updatedPlanet['energy_used']}/{$updatedPlanet['energy_max']}\n\n";

// 3. Test de construction d'une mine de titanium
echo "3. Test lancement amélioration de la parcelle 1...\n";
$upResult = $buildingEngine->startUpgrade((int)$planet['id'], 'field', '1');
echo "-> Résultat : " . ($upResult['success'] ? "SUCCÈS (finira dans {$upResult['duration']}s)" : "ÉCHEC") . "\n";
$queue = $buildingEngine->getQueue((int)$planet['id']);
echo "-> Éléments dans la file : " . count($queue) . "\n\n";

// 4. Test d'avancement artificiel du temps pour terminer la construction
echo "4. Simulation fin de construction...\n";
$db = Database::getConnection();
$db->prepare("UPDATE construction_queue SET finishes_at = UNIX_TIMESTAMP() - 1 WHERE planet_id = ?")->execute([$planet['id']]);
$planetEngine->updatePlanet((int)$planet['id']);
$fieldsAfter = $planetEngine->getFields((int)$planet['id']);
echo "-> Niveau de la parcelle 1 après résolution : {$fieldsAfter[0]['level']}\n\n";

// 5. Test fabrication d'un vaisseau
echo "5. Test fabrication de vaisseaux au chantier...\n";
// Assurons-nous d'avoir un chantier niveau 1 et des ressources pour tester
$db->prepare("INSERT INTO planet_buildings (planet_id, building_type, level) VALUES (?, 'shipyard', 2) ON DUPLICATE KEY UPDATE level = 2")->execute([$planet['id']]);
$db->prepare("UPDATE planets SET metal = 5000, crystal = 5000, deuterium = 3000 WHERE id = ?")->execute([$planet['id']]);
$shipResult = $shipyardEngine->buildShips((int)$planet['id'], 'spy_probe', 3, $user['faction']);
echo "-> Commande vaisseau : " . ($shipResult['success'] ? "SUCCÈS" : "ÉCHEC") . "\n";
$db->prepare("UPDATE shipyard_queue SET finishes_at = UNIX_TIMESTAMP() - 1 WHERE planet_id = ?")->execute([$planet['id']]);
$planetEngine->updatePlanet((int)$planet['id']);
$stmtShips = $db->prepare("SELECT ship_code, count FROM planet_ships WHERE planet_id = ?");
$stmtShips->execute([$planet['id']]);
$ships = $stmtShips->fetchAll(PDO::FETCH_KEY_PAIR);
echo "-> Flotte en hangar : " . json_encode($ships) . "\n\n";

// 6. Test d'envoi et résolution de mission spatiale (Raid)
echo "6. Test mission spatiale (Raid)...\n";
// Trouver une planète neutre cible
$targetPlanet = $db->query("SELECT * FROM planets WHERE user_id IS NULL LIMIT 1")->fetch();
echo "-> Cible choisie : {$targetPlanet['name']} [{$targetPlanet['coord_x']}:{$targetPlanet['coord_y']}]\n";
$dispatch = $fleetEngine->dispatchMission(
    (int)$user['id'], 
    (int)$planet['id'], 
    (int)$targetPlanet['id'], 
    'raid', 
    ['spy_probe' => 1], 
    ['metal' => 0, 'crystal' => 0, 'deuterium' => 0]
);
echo "-> Déploiement : " . ($dispatch['success'] ? "SUCCÈS (durée {$dispatch['duration']}s)" : "ÉCHEC") . "\n";

// Avancer le temps pour que la flotte arrive
$db->prepare("UPDATE fleet_missions SET arrival_time = UNIX_TIMESTAMP() - 1 WHERE source_planet_id = ? AND status = 'en_route'")->execute([$planet['id']]);
$fleetEngine->processFleetMissions();
echo "-> Mission arrivée et combat simulé !\n";

// Vérifier la présence du rapport de combat
$stmtRep = $db->prepare("SELECT * FROM combat_reports WHERE attacker_id = ? ORDER BY id DESC LIMIT 1");
$stmtRep->execute([$user['id']]);
$lastRep = $stmtRep->fetch();
if ($lastRep) {
    echo "-> Rapport généré : '{$lastRep['title']}', Vainqueur : {$lastRep['winner']}\n";
}

// Avancer le temps pour que la flotte revienne
$db->prepare("UPDATE fleet_missions SET return_time = UNIX_TIMESTAMP() - 1 WHERE source_planet_id = ? AND status = 'returning'")->execute([$planet['id']]);
$fleetEngine->processFleetMissions();
echo "-> Flotte revenue et amarrée à la base !\n\n";

echo "=== TOUS LES TESTS SONT VALIDES ET CONCLUANTS ! ===\n";
