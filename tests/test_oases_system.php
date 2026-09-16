<?php
/**
 * Test Automatisé : Système d'Oasis Inoccupées, Faune Sauvage, Pillage et Annexions Féodales
 * OpenShogun
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/OasisEngine.php';
require_once __DIR__ . '/../core/FleetEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/CombatEngine.php';

echo "=== TEST DU SYSTÈME D'OASIS & ANIMAUX SAUVAGES ===\n\n";

$db = Database::getConnection();
$oasisEngine = new OasisEngine();
$fleetEngine = new FleetEngine();
$planetEngine = new PlanetEngine();
$combatEngine = new CombatEngine();

// 1. Vérifier la présence des 3 animaux sauvages dans la table units
echo "1. Vérification des animaux sauvages dans la table units...\n";
$stmtAnimals = $db->query("SELECT code, name, attack, def_infantry, def_mech, icon FROM units WHERE code IN ('sanglier_sauvage', 'loup_honshu', 'ours_hokkaido')");
$animals = $stmtAnimals->fetchAll(PDO::FETCH_ASSOC);
assert(count($animals) === 3, "Il doit y avoir exactement 3 types d'animaux sauvages.");
foreach ($animals as $a) {
    echo "   - {$a['icon']} {$a['name']} ({$a['code']}) : Att {$a['attack']}, Def {$a['def_infantry']}/{$a['def_mech']}\n";
}
echo "   [OK] Animaux sauvages configurés.\n\n";

// 2. Vérifier les oasis existantes dans la base
echo "2. Vérification des oasis déployées...\n";
$stmtCount = $db->query("SELECT COUNT(*) FROM oases");
$oasisCount = (int)$stmtCount->fetchColumn();
assert($oasisCount >= 20, "Au moins 20 oasis doivent être présentes sur la carte.");
echo "   - $oasisCount oasis recensées sur la carte du Shogunat.\n";

// Trouver ou créer une oasis de test
$stmtTestOasis = $db->query("SELECT * FROM oases WHERE coord_x = 99 AND coord_y = 99");
$testOasis = $stmtTestOasis->fetch(PDO::FETCH_ASSOC);
if (!$testOasis) {
    $db->exec("
        INSERT INTO oases (coord_x, coord_y, oasis_type, name, bonus_wood, bonus_stone, bonus_rice, res_wood, res_stone, res_rice)
        VALUES (99, 99, 'lake_50_rice', 'Oasis du Lac Shinano Test', 0, 0, 50, 1500, 1500, 1500)
    ");
    $testOasisId = (int)$db->lastInsertId();
} else {
    $testOasisId = (int)$testOasis['id'];
    $db->exec("
        UPDATE oases 
        SET owner_planet_id = NULL, annexed_at = NULL, res_wood = 1500, res_stone = 1500, res_rice = 1500, bonus_rice = 50, bonus_wood = 0, bonus_stone = 0
        WHERE id = $testOasisId
    ");
}

// Réinitialiser la faune de l'oasis de test
$db->exec("DELETE FROM oasis_units WHERE oasis_id = $testOasisId");
$db->exec("
    INSERT INTO oasis_units (oasis_id, unit_code, count, is_wild) VALUES
    ($testOasisId, 'sanglier_sauvage', 5, 1),
    ($testOasisId, 'loup_honshu', 3, 1)
");

$garrison = $oasisEngine->getOasisGarrison($testOasisId);
assert(count($garrison) === 2, "La garnison sauvage doit contenir 2 espèces d'animaux.");
assert(!$oasisEngine->isOasisPacified($testOasisId), "L'oasis ne doit pas être pacifiée avec des bêtes vivantes.");
echo "   [OK] Oasis de test initialisée avec faune sauvage hostile.\n\n";

// 3. Récupérer un joueur et sa planète pour le test
$stmtPlayer = $db->query("SELECT u.id as user_id, u.username, p.id as planet_id, p.coord_x, p.coord_y FROM users u JOIN planets p ON p.user_id = u.id LIMIT 1");
$player = $stmtPlayer->fetch(PDO::FETCH_ASSOC);
$userId = (int)$player['user_id'];
$planetId = (int)$player['planet_id'];

echo "3. Test des troupes disponibles et ravitaillement du joueur #$userId...\n";
// Donner des troupes et des ressources au joueur pour le test
$db->exec("UPDATE planets SET metal = 20000, crystal = 20000, deuterium = 20000 WHERE id = $planetId");
$db->exec("
    INSERT INTO planet_units (planet_id, unit_code, count) 
    VALUES ($planetId, 'piquier_ashigaru_yari', 50) 
    ON DUPLICATE KEY UPDATE count = count + 50
");

// 4. Test d'envoi d'une expédition vers l'oasis (Raid)
echo "4. Déploiement d'un raid vers l'oasis...\n";
$dispatchRes = $fleetEngine->dispatchMission(
    $userId,
    $planetId,
    null, // target_planet_id
    'raid',
    ['piquier_ashigaru_yari' => 20],
    ['metal' => 0, 'crystal' => 0, 'deuterium' => 0],
    $testOasisId // target_oasis_id
);

assert($dispatchRes['success'] === true, "L'expédition doit être envoyée avec succès.");
echo "   - Expédition envoyée avec succès vers l'oasis.\n";

// Récupérer la mission en cours
$stmtMission = $db->prepare("SELECT * FROM fleet_missions WHERE user_id = ? AND target_oasis_id = ? ORDER BY id DESC LIMIT 1");
$stmtMission->execute([$userId, $testOasisId]);
$mission = $stmtMission->fetch(PDO::FETCH_ASSOC);
assert($mission && $mission['status'] === 'en_route', "La mission doit être en statut 'en_route'.");

// Simuler l'arrivée de la mission à destination
$db->exec("UPDATE fleet_missions SET arrival_time = UNIX_TIMESTAMP() - 10 WHERE id = {$mission['id']}");
$fleetEngine->processFleetMissions();

// Vérifier le statut de la mission après combat (doit être 'returning' avec du butin)
$stmtMission->execute([$userId, $testOasisId]);
$missionAfter = $stmtMission->fetch(PDO::FETCH_ASSOC);
assert($missionAfter['status'] === 'returning', "La flotte doit être en statut 'returning' après le raid victorieux.");
$cargoLoot = json_decode($missionAfter['cargo_data'], true);
$totalLoot = ($cargoLoot['metal'] ?? 0) + ($cargoLoot['crystal'] ?? 0) + ($cargoLoot['deuterium'] ?? 0);
echo "   - Butin rapporté par les pillards : {$cargoLoot['metal']} Bois, {$cargoLoot['crystal']} Pierre, {$cargoLoot['deuterium']} Riz (Total: $totalLoot).\n";
assert($totalLoot > 0, "Les pillards doivent avoir rapporté du butin.");

// 5. Simuler le retour de la flotte au fief
echo "5. Résolution du retour de la flotte au fief...\n";
$db->exec("UPDATE fleet_missions SET return_time = UNIX_TIMESTAMP() - 10 WHERE id = {$mission['id']}");
$fleetEngine->processFleetMissions();

$stmtMission->execute([$userId, $testOasisId]);
$missionDone = $stmtMission->fetch(PDO::FETCH_ASSOC);
assert($missionDone['status'] === 'completed', "La mission doit être marquée 'completed' après son retour.");
echo "   [OK] Raid, combat contre les bêtes, pillage et retour résolus avec succès.\n\n";

// 6. Test de pacification et annexion
echo "6. Test de pacification et annexion...\n";
// Supprimer les bêtes restantes de l'oasis de test pour la pacifier
$db->exec("DELETE FROM oasis_units WHERE oasis_id = $testOasisId AND is_wild = 1");
assert($oasisEngine->isOasisPacified($testOasisId), "L'oasis doit maintenant être considérée comme pacifiée.");

// Annexion de l'oasis par le joueur
$annexRes = $oasisEngine->annexOasis($testOasisId, $planetId);
assert($annexRes['success'] === true, "L'annexion doit réussir : " . ($annexRes['message'] ?? ''));
echo "   - {$annexRes['message']}\n";

// Vérifier les bonus conférés au fief
$bonuses = $oasisEngine->getTotalOasisBonusesForPlanet($planetId);
assert($bonuses['rice'] >= 50, "Le bonus de riz de l'oasis doit être d'au moins +50%.");
echo "   - Bonus cumulés du fief : +{$bonuses['rice']}% Riz, +{$bonuses['wood']}% Bois, +{$bonuses['stone']}% Pierre.\n";

// Vérifier l'impact direct sur PlanetEngine::calculateProduction
$fields = $planetEngine->getFields($planetId);
$prodWithOasis = $planetEngine->calculateProduction($fields, $planetId);
$prodWithoutOasis = $planetEngine->calculateProduction($fields, null);
assert($prodWithOasis['deuterium'] > $prodWithoutOasis['deuterium'], "La production de riz doit être supérieure grâce à l'oasis.");
echo "   - Production de Riz sans oasis : {$prodWithoutOasis['deuterium']}/h | Avec oasis : {$prodWithOasis['deuterium']}/h (+50% !)\n";
echo "   [OK] Calcul du bonus de production validé.\n\n";

// 7. Test de la limite de 3 oasis par fief
echo "7. Test de la limite de 3 oasis maximum par fief...\n";
// Créer temporairement 2 autres oasis et les annexer
$db->exec("INSERT INTO oases (coord_x, coord_y, oasis_type, name, bonus_wood, owner_planet_id) VALUES (991, 991, 'forest_50', 'Oasis Forêt 1', 50, $planetId)");
$extraOasis1 = (int)$db->lastInsertId();
$db->exec("INSERT INTO oases (coord_x, coord_y, oasis_type, name, bonus_stone, owner_planet_id) VALUES (992, 992, 'mountain_50', 'Oasis Mont 2', 50, $planetId)");
$extraOasis2 = (int)$db->lastInsertId();

// Créer une 4ème oasis pacifiée et tenter de l'annexer
$db->exec("INSERT INTO oases (coord_x, coord_y, oasis_type, name, bonus_wood) VALUES (993, 993, 'forest_25', 'Oasis Forêt 3', 25)");
$extraOasis3 = (int)$db->lastInsertId();

$refuseAnnex = $oasisEngine->annexOasis($extraOasis3, $planetId);
assert($refuseAnnex['success'] === false, "L'annexion d'une 4ème oasis doit être refusée.");
echo "   - Tentative de 4ème oasis refusée : {$refuseAnnex['message']}\n";

// 8. Test d'abandon d'oasis
echo "8. Test d'abandon d'oasis...\n";
$abandonRes = $oasisEngine->abandonOasis($testOasisId, $planetId);
assert($abandonRes['success'] === true, "L'abandon de l'oasis doit réussir.");
$bonusesAfterAbandon = $oasisEngine->getTotalOasisBonusesForPlanet($planetId);
assert($bonusesAfterAbandon['rice'] === 0, "Le bonus de riz doit retomber à 0 après l'abandon de l'oasis.");
echo "   - Oasis abandonnée avec succès, bonus de riz réinitialisé.\n";

// Nettoyage des données de test
$db->exec("DELETE FROM oases WHERE id IN ($testOasisId, $extraOasis1, $extraOasis2, $extraOasis3)");
$db->exec("DELETE FROM fleet_missions WHERE target_oasis_id IN ($testOasisId, $extraOasis1, $extraOasis2, $extraOasis3)");
echo "   [OK] Nettoyage des données de test effectué.\n\n";

echo "=== TOUS LES TESTS DU SYSTÈME D'OASIS ONT RÉUSSI AVEC SUCCÈS ! ===\n";
