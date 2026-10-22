<?php
/**
 * Test de validation : Déplacement de l'Éclaireur Shinobi Furtif (spy_probe) dans le Dojo Militaire
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BarracksEngine.php';
require_once __DIR__ . '/../core/ShipyardEngine.php';
require_once __DIR__ . '/../core/FleetEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';

$db = Database::getConnection();
$barracksEngine = new BarracksEngine();
$shipyardEngine = new ShipyardEngine();
$fleetEngine = new FleetEngine();
$planetEngine = new PlanetEngine();

echo "========================================================\n";
echo "🥷 TEST : ÉCLAIREUR SHINOBI DANS LE DOJO MILITAIRE\n";
echo "========================================================\n";

// 1. Trouver un joueur test ou admin
$user = $db->query("SELECT * FROM users WHERE is_bot = 0 ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assert(!empty($user), "Un utilisateur doit exister.");
$planet = $db->query("SELECT * FROM planets WHERE user_id = {$user['id']} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assert(!empty($planet), "La planète de l'utilisateur doit exister.");

$planetId = (int)$planet['id'];
$faction = $user['faction'] ?? 'terran';

echo "Joueur : {$user['username']} [ID: {$user['id']}, Faction: $faction]\n";
echo "Fief : {$planet['name']} [ID: $planetId]\n\n";

// 2. Vérifier que spy_probe est dans units et plus dans ships
$stUnits = $db->query("SELECT * FROM units WHERE code = 'spy_probe'")->fetch(PDO::FETCH_ASSOC);
assert(!empty($stUnits), "spy_probe doit être dans la table units.");
assert((int)$stUnits['speed'] === 20000, "La vitesse de l'éclaireur doit être 20000.");
assert($stUnits['icon'] === '🥷', "L'icône doit être 🥷.");

$stShips = $db->query("SELECT * FROM ships WHERE code = 'spy_probe'")->fetch(PDO::FETCH_ASSOC);
assert(empty($stShips), "spy_probe NE DOIT PLUS être dans la table ships.");
echo "✓ Règle 1 validée : spy_probe est bien dans 'units' et absent de 'ships'.\n";

// 3. Vérifier la liste des unités du Dojo
$availableUnits = $barracksEngine->getAvailableUnits($planetId, $faction);
$foundInBarracks = false;
foreach ($availableUnits as $u) {
    if ($u['code'] === 'spy_probe') {
        $foundInBarracks = true;
        assert($u['tier'] == 1, "Le tier doit être 1.");
        break;
    }
}
assert($foundInBarracks, "spy_probe doit être disponible dans getAvailableUnits du Dojo.");
echo "✓ Règle 2 validée : spy_probe figure parmi les troupes entraînables au Dojo Militaire.\n";

// 4. Vérifier l'Atelier de Siège (Shipyard)
$availableShips = $shipyardEngine->getAvailableShips($planetId, $faction);
$foundInShipyard = false;
foreach ($availableShips as $s) {
    if ($s['code'] === 'spy_probe') {
        $foundInShipyard = true;
        break;
    }
}
assert(!$foundInShipyard, "spy_probe NE DOIT PAS être dans getAvailableShips de l'Atelier de Siège.");
echo "✓ Règle 3 validée : spy_probe est absent de l'Atelier de Siège & Écuries.\n";

// 5. Test d'entraînement direct de l'Éclaireur Shinobi au Dojo
$db->prepare("INSERT INTO planet_buildings (planet_id, building_type, level) VALUES (?, 'barracks', 3) ON DUPLICATE KEY UPDATE level = GREATEST(level, 3)")->execute([$planetId]);
$db->prepare("UPDATE planets SET crystal = crystal + 5000, deuterium = deuterium + 2000 WHERE id = ?")->execute([$planetId]);

$trainResult = $barracksEngine->trainUnits($planetId, 'spy_probe', 3, $faction);
assert(!empty($trainResult['success']), "L'entraînement de spy_probe doit réussir.");
echo "✓ Règle 4 validée : Recrutement de 3 Éclaireurs Shinobis au Dojo réussi : {$trainResult['message']}\n";

// Résoudre la file si nécessaire
$db->prepare("UPDATE barracks_queue SET finishes_at = UNIX_TIMESTAMP() - 1 WHERE planet_id = ? AND unit_code = 'spy_probe'")->execute([$planetId]);
$planetEngine->updatePlanet($planetId);

$stGarrison = $db->prepare("SELECT count FROM planet_units WHERE planet_id = ? AND unit_code = 'spy_probe'");
$stGarrison->execute([$planetId]);
$shinobiCount = (int)$stGarrison->fetchColumn();
assert($shinobiCount >= 3, "Il doit y avoir au moins 3 Éclaireurs Shinobis dans la garnison du Dojo.");
echo "✓ Règle 5 validée : Effectif actuel en garnison au Dojo : $shinobiCount Éclaireur(s) Shinobi(s).\n";

// 6. Test d'envoi d'une mission d'espionnage avec la sonde depuis planet_units
$targetPlanet = $db->query("SELECT * FROM planets WHERE id != $planetId LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assert(!empty($targetPlanet), "Une planète cible doit exister.");

$initialProbeCount = $shinobiCount;
$dispatch = $fleetEngine->dispatchMission(
    (int)$user['id'],
    $planetId,
    (int)$targetPlanet['id'],
    'spy',
    ['spy_probe' => 1],
    ['metal' => 0, 'crystal' => 0, 'deuterium' => 0]
);
assert(!empty($dispatch['success']), "Le lancement de la mission d'espionnage doit réussir.");

$stGarrison->execute([$planetId]);
$afterDispatchCount = (int)$stGarrison->fetchColumn();
assert($afterDispatchCount === $initialProbeCount - 1, "1 Éclaireur Shinobi a bien été débité de la garnison du Dojo.");
echo "✓ Règle 6 validée : Déploiement d'espionnage avec 1 Éclaireur Shinobi déduit de planet_units.\n";

// 7. Résolution du vol aller et retour de l'Éclaireur
$missionId = (int)$dispatch['mission_id'];
$db->prepare("UPDATE fleet_missions SET arrival_time = UNIX_TIMESTAMP() - 1 WHERE id = ?")->execute([$missionId]);
$fleetEngine->processFleetMissions();

$db->prepare("UPDATE fleet_missions SET return_time = UNIX_TIMESTAMP() - 1 WHERE id = ?")->execute([$missionId]);
$fleetEngine->processFleetMissions();

$stGarrison->execute([$planetId]);
$afterReturnCount = (int)$stGarrison->fetchColumn();
assert($afterReturnCount === $initialProbeCount, "L'Éclaireur Shinobi est bien retourné et réintégré dans la garnison du Dojo (planet_units).");
echo "✓ Règle 7 validée : L'Éclaireur Shinobi a achevé sa mission et est retourné sain et sauf dans planet_units !\n";

echo "\n========================================================\n";
echo "🎉 TOUS LES TESTS DE DÉPLACEMENT DU SHINOBI AU DOJO SONT VALIDÉS !\n";
echo "========================================================\n";

