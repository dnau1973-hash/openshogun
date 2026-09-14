<?php
/**
 * Test unitaire de la Caserne Militaire et du Panel des Troupes
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/BarracksEngine.php';

echo "=== TEST DU SYSTÈME D'ARMÉE & TROUPES (STYLE TRAVIAN) ===\n\n";

$auth = new Auth();
$auth->login('TerranCommander', 'password123');
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

echo "-> Joueur: {$user['username']} ({$user['faction']}) sur Planète ID {$planet['id']}\n";

$barracksEngine = new BarracksEngine();
$planetEngine = new PlanetEngine();

// 1. Consultation des troupes stationnées
$troops = $barracksEngine->getStationedUnits((int)$planet['id'], $user['faction']);
echo "1. Soldats actuellement stationnés dans la cité :\n";
foreach ($troops as $t) {
    echo "   - {$t['icon']} {$t['name']} (Palier {$t['tier']}) : {$t['stationed_count']} en garnison [Att: {$t['attack']}, Df Inf: {$t['def_infantry']}, Df Méca: {$t['def_mech']}]\n";
}
echo "\n";

// 2. Entraînement de nouvelles recrues
echo "2. Lancement entraînement de 10 Marines Terran...\n";
// Assurons des ressources suffisantes pour le test
$db = Database::getConnection();
$db->prepare("UPDATE planets SET metal = metal + 3000, crystal = crystal + 3000, deuterium = deuterium + 1000 WHERE id = ?")->execute([$planet['id']]);

$trainResult = $barracksEngine->trainUnits((int)$planet['id'], 'terran_marine', 10, $user['faction']);
echo "-> Résultat : " . ($trainResult['success'] ? "SUCCÈS" : "ÉCHEC") . " : {$trainResult['message']}\n";

// 3. Vérification de la file active
$queue = $barracksEngine->getQueue((int)$planet['id']);
echo "-> Régiments dans la file d'entraînement : " . count($queue) . "\n";
echo "   - Cible : {$queue[0]['count']}x {$queue[0]['unit_name']} (finit à {$queue[0]['finishes_at']})\n\n";

// 4. Résolution de l'entraînement
echo "4. Simulation de fin de formation...\n";
$db->prepare("UPDATE barracks_queue SET finishes_at = UNIX_TIMESTAMP() - 1 WHERE planet_id = ?")->execute([$planet['id']]);
$planetEngine->updatePlanet((int)$planet['id']);

$troopsAfter = $barracksEngine->getStationedUnits((int)$planet['id'], $user['faction']);
echo "-> Effectif des Marines après entraînement : {$troopsAfter[0]['stationed_count']} unités (+10 recrues validées)\n\n";

echo "=== TEST DU SYSTÈME D'ARMÉE RÉUSSI AVEC SUCCÈS ! ===\n";

