<?php
/**
 * Tests automatisés complets pour le Système de Didacticiel Féodal & Quêtes (OpenShogun)
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/QuestEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/VillageFieldGenerator.php';

echo "========================================================\n";
echo "  BANC DE TESTS : SYSTÈME DE QUÊTES & DIDACTICIEL FÉODAL\n";
echo "========================================================\n";

$db = Database::getConnection();
$questEngine = new QuestEngine();
$planetEngine = new PlanetEngine();

// Nettoyer les anciens résidus de test
$db->exec("DELETE FROM users WHERE username LIKE 'test_daimyo_quest_%'");
$db->exec("DELETE FROM planets WHERE coord_x >= 900");

// Préparation d'un utilisateur de test dédié
$testUsername = 'test_daimyo_quest_' . time();
$testEmail = $testUsername . '@openshogun.local';
$testHash = password_hash('Pass123!', PASSWORD_BCRYPT);
$testFaction = 'terran';

$coordX = rand(900, 999);
$coordY = rand(900, 999);

$db->beginTransaction();
$db->prepare("
    INSERT INTO users (username, email, password_hash, faction, points, bio, created_at, last_active) 
    VALUES (?, ?, ?, ?, 100, NULL, NOW(), NOW())
")->execute([$testUsername, $testEmail, $testHash, $testFaction]);
$testUserId = (int)$db->lastInsertId();

// Domaine castral de test
$db->prepare("
    INSERT INTO planets (user_id, name, coord_x, coord_y, planet_type, metal, crystal, deuterium, energy_used, energy_max, metal_max, crystal_max, deuterium_max, is_capital)
    VALUES (?, 'Fief Test Quest', ?, ?, 'terrestrial', 1000, 1000, 1000, 0, 50, 15000, 15000, 15000, 1)
")->execute([$testUserId, $coordX, $coordY]);
$testPlanetId = (int)$db->lastInsertId();

// Initialiser 18 parcelles au niveau 0
VillageFieldGenerator::populatePlanetFields($db, $testPlanetId, 'balanced', 0, false);

// Initialiser les bâtiments de base
$stmtBuild = $db->prepare("INSERT INTO planet_buildings (planet_id, building_type, level) VALUES (?, ?, ?)");
$stmtBuild->execute([$testPlanetId, 'hq', 1]);
$stmtBuild->execute([$testPlanetId, 'storage', 1]);
$stmtBuild->execute([$testPlanetId, 'tank', 1]);
$stmtBuild->execute([$testPlanetId, 'barracks', 1]);

$db->commit();

echo "Utilisateur de test créé : ID #{$testUserId}, Fief #{$testPlanetId} (coords 998:998)\n\n";

$testsPassed = 0;
$testsTotal = 0;

function assertTest(string $desc, bool $condition, &$passed, &$total) {
    $total++;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$desc}\n";
    } else {
        echo "  [FAIL] {$desc}\n";
    }
}

// 1. Catalogue des quêtes
$catalog = QuestEngine::getCatalog();
assertTest("Le catalogue contient exactement 12 quêtes", count($catalog) === 12, $testsPassed, $testsTotal);
assertTest("La quête 1 est 'wood_field_lvl1'", isset($catalog['wood_field_lvl1']) && $catalog['wood_field_lvl1']['order'] === 1, $testsPassed, $testsTotal);
assertTest("La quête 12 est 'first_expedition'", isset($catalog['first_expedition']) && $catalog['first_expedition']['order'] === 12, $testsPassed, $testsTotal);

// 2. Statut initial : Quête 1 non accomplie car bois niv 0
$status = $questEngine->getPlayerQuestsStatus($testUserId, $testPlanetId);
assertTest("Statut initial : 0 quête réclamée", $status['claimed_count'] === 0, $testsPassed, $testsTotal);
assertTest("Quête active initiale est 'wood_field_lvl1'", $status['active_quest']['key'] === 'wood_field_lvl1', $testsPassed, $testsTotal);
assertTest("Quête 1 n'est pas encore réclamable", $status['active_quest']['is_claimable'] === false, $testsPassed, $testsTotal);

// 3. Amélioration d'une parcelle de bois au niveau 1
$db->prepare("UPDATE planet_fields SET level = 1 WHERE planet_id = ? AND type = 'metal_mine' LIMIT 1")->execute([$testPlanetId]);
$statusAfterWood = $questEngine->getPlayerQuestsStatus($testUserId, $testPlanetId);
assertTest("Après montée camp de bûcherons niveau 1 : Quête 1 devient réclamable", $statusAfterWood['active_quest']['is_claimable'] === true, $testsPassed, $testsTotal);
assertTest("Détection de 1 quête prête à réclamer", $statusAfterWood['claimable_count'] === 1, $testsPassed, $testsTotal);

// 4. Réclamation de la Quête 1
$initialPlanet = $planetEngine->getPlanet($testPlanetId);
$claimRes = $questEngine->claimReward($testUserId, $testPlanetId, 'wood_field_lvl1');
assertTest("Réclamation de la Quête 1 réussie", $claimRes['success'] === true, $testsPassed, $testsTotal);
$afterPlanet = $planetEngine->getPlanet($testPlanetId);

$rewardMetal = $catalog['wood_field_lvl1']['rewards']['metal'];
$rewardCrystal = $catalog['wood_field_lvl1']['rewards']['crystal'];
$rewardDeut = $catalog['wood_field_lvl1']['rewards']['deuterium'];

assertTest("Bois crédité correctement (+{$rewardMetal})", (int)$afterPlanet['metal'] === (int)$initialPlanet['metal'] + $rewardMetal, $testsPassed, $testsTotal);
assertTest("Pierre créditée correctement (+{$rewardCrystal})", (int)$afterPlanet['crystal'] === (int)$initialPlanet['crystal'] + $rewardCrystal, $testsPassed, $testsTotal);
assertTest("Riz crédité correctement (+{$rewardDeut})", (int)$afterPlanet['deuterium'] === (int)$initialPlanet['deuterium'] + $rewardDeut, $testsPassed, $testsTotal);

// 5. Impossible de réclamer deux fois
$claimTwice = $questEngine->claimReward($testUserId, $testPlanetId, 'wood_field_lvl1');
assertTest("Refus de réclamer une quête déjà perçue", $claimTwice['success'] === false, $testsPassed, $testsTotal);

// 6. La quête active passe à l'étape 2 (stone_field_lvl1)
$status2 = $questEngine->getPlayerQuestsStatus($testUserId, $testPlanetId);
assertTest("La quête active devient 'stone_field_lvl1' (Étape 2)", $status2['active_quest']['key'] === 'stone_field_lvl1', $testsPassed, $testsTotal);

// 7. Test de la quête Quête 9 (explore_map) via recordAction
$questEngine->recordAction($testUserId, 'visit_map');
$checkMapQuest = $db->prepare("SELECT status FROM user_quests WHERE user_id = ? AND quest_key = 'explore_map'");
$checkMapQuest->execute([$testUserId]);
assertTest("Visite de la carte enregistrée et passée en 'completed'", $checkMapQuest->fetchColumn() === 'completed', $testsPassed, $testsTotal);

// 8. Test de la quête Quête 10 (Devise de Daimyō)
$db->prepare("UPDATE users SET bio = 'Victoire ou déshonneur, la voie du sabre ne vacille jamais.' WHERE id = ?")->execute([$testUserId]);
$statusBio = $questEngine->getPlayerQuestsStatus($testUserId, $testPlanetId);
$bioQuest = null;
foreach ($statusBio['quests'] as $q) {
    if ($q['key'] === 'daimyo_motto') {
        $bioQuest = $q;
        break;
    }
}
assertTest("La rédaction d'une devise valide la condition de la quête 'daimyo_motto'", $bioQuest['status'] === 'completed', $testsPassed, $testsTotal);

// 9. Test de renforts de troupes dans claimReward (Quête 8)
// On simule que la quête 8 est prête
$db->prepare("
    INSERT INTO user_quests (user_id, quest_key, status, progress, completed_at) 
    VALUES (?, 'train_garrison', 'completed', 100, NOW())
    ON DUPLICATE KEY UPDATE status = 'completed'
")->execute([$testUserId]);

$claimQ8 = $questEngine->claimReward($testUserId, $testPlanetId, 'train_garrison');
assertTest("Réclamation Quête 8 avec troupes d'honneur réussie", $claimQ8['success'] === true, $testsPassed, $testsTotal);
assertTest("Soldats bonus crédités (+5 recrues d'honneur)", $claimQ8['bonus_units'] === 5, $testsPassed, $testsTotal);

$checkUnits = $db->prepare("SELECT count FROM planet_units WHERE planet_id = ? AND unit_code = 'piquier_ashigaru_yari'");
$checkUnits->execute([$testPlanetId]);
assertTest("Garnison du fief renforcée avec les 5 Piquiers Ashigarus", (int)$checkUnits->fetchColumn() >= 5, $testsPassed, $testsTotal);

// 10. Nettoyage de l'utilisateur de test
$db->prepare("DELETE FROM users WHERE id = ?")->execute([$testUserId]);
$checkCleanup = $db->prepare("SELECT COUNT(*) FROM user_quests WHERE user_id = ?");
$checkCleanup->execute([$testUserId]);
assertTest("Cascade de suppression : user_quests nettoyée", (int)$checkCleanup->fetchColumn() === 0, $testsPassed, $testsTotal);

echo "\n========================================================\n";
echo "  RÉSULTATS DU BANC DE TESTS : {$testsPassed} / {$testsTotal} RÉUSSIS\n";
echo "========================================================\n";

if ($testsPassed === $testsTotal) {
    echo "TOUS LES TESTS DU SYSTÈME DE DIDACTICIEL ONT RÉUSSI AVEC SUCCÈS !\n";
    exit(0);
} else {
    echo "CERTAINS TESTS ONT ÉCHOUÉ.\n";
    exit(1);
}
