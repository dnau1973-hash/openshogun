<?php
/**
 * Tests automatisés complets pour le Système de Héros "Samouraï" (OpenShogun)
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/HeroEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/FleetEngine.php';
require_once __DIR__ . '/../core/CombatEngine.php';
require_once __DIR__ . '/../core/WorldGenerator.php';

echo "========================================================\n";
echo "  BANC DE TESTS : SYSTÈME DE HÉROS SAMOURAÏ & AVENTURES\n";
echo "========================================================\n";

$db = Database::getConnection();
$heroEngine = new HeroEngine();
$planetEngine = new PlanetEngine();
$fleetEngine = new FleetEngine();

// Nettoyage préalable des tests précédents
$db->exec("DELETE FROM users WHERE username LIKE 'test_daimyo_hero_%'");
$db->exec("DELETE FROM planets WHERE coord_x >= 850 AND coord_x <= 899");

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

// 1. Création du compte test et de son fief
$testUsername = 'test_daimyo_hero_' . time();
$testEmail = $testUsername . '@openshogun.local';
$testHash = password_hash('HeroPass123!', PASSWORD_BCRYPT);
$testFaction = 'terran';

$db->beginTransaction();
$db->prepare("
    INSERT INTO users (username, email, password_hash, faction, points, bio, created_at, last_active) 
    VALUES (?, ?, ?, ?, 150, NULL, NOW(), NOW())
")->execute([$testUsername, $testEmail, $testHash, $testFaction]);
$testUserId = (int)$db->lastInsertId();

$coordX = rand(850, 899);
$coordY = rand(850, 899);
$db->prepare("
    INSERT INTO planets (user_id, name, coord_x, coord_y, planet_type, metal, crystal, deuterium, energy_used, energy_max, metal_max, crystal_max, deuterium_max, is_capital)
    VALUES (?, 'Château du Héros', ?, ?, 'terrestrial', 20000, 20000, 20000, 0, 100, 50000, 50000, 50000, 1)
")->execute([$testUserId, $coordX, $coordY]);
$testPlanetId = (int)$db->lastInsertId();

// Cible ennemie pour tester le déploiement d'expédition
$enemyCoordX = $coordX + 1;
$enemyCoordY = $coordY + 1;
$db->prepare("
    INSERT INTO planets (user_id, name, coord_x, coord_y, planet_type, metal, crystal, deuterium, energy_used, energy_max, metal_max, crystal_max, deuterium_max, is_capital)
    VALUES (1, 'Fief Ennemi Test', ?, ?, 'terrestrial', 5000, 5000, 5000, 0, 50, 20000, 20000, 20000, 0)
")->execute([$enemyCoordX, $enemyCoordY]);
$enemyPlanetId = (int)$db->lastInsertId();

$db->commit();

echo "Utilisateur créé : ID #{$testUserId}, Fief #{$testPlanetId} (Coords: {$coordX}:{$coordY})\n\n";

// TEST 1 : Création automatique et initialisation du héros
echo "--- TEST 1 : INITIALISATION DU HÉROS SAMOURAÏ ---\n";
$hero = $heroEngine->createHeroForUser($testUserId, 'Miyamoto Test', $testPlanetId, $coordX, $coordY);
assertTest("Le héros est initialisé avec succès", !empty($hero['id']), $testsPassed, $testsTotal);
assertTest("Le niveau initial est 1", (int)$hero['level'] === 1, $testsPassed, $testsTotal);
assertTest("La santé initiale est 100%", (float)$hero['health'] === 100.0, $testsPassed, $testsTotal);
assertTest("Le statut initial est 'home'", $hero['status'] === 'home', $testsPassed, $testsTotal);
assertTest("4 points d'attributs non attribués au départ", (int)$hero['unassigned_points'] === 4, $testsPassed, $testsTotal);

// TEST 2 : Vérification des aventures initiales générées
echo "\n--- TEST 2 : AVENTURES INITIALES DU HÉROS ---\n";
$adventures = $heroEngine->getAdventures($testUserId);
assertTest("3 aventures de départ sont générées", count($adventures) === 3, $testsPassed, $testsTotal);
assertTest("Les coordonnées et temps de trajet sont calculés", isset($adventures[0]['duration_seconds']) && $adventures[0]['duration_seconds'] > 0, $testsPassed, $testsTotal);

// TEST 3 : Calcul des statistiques de base
echo "\n--- TEST 3 : CALCUL DES STATISTIQUES EFFECTIVES ---\n";
$stats = $heroEngine->calculateEffectiveStats($hero);
assertTest("Force de base = 150 (sans points)", $stats['combat_strength'] === 150, $testsPassed, $testsTotal);
assertTest("Bonus offensif de base = 0%", (float)$stats['offense_bonus_pct'] === 0.0, $testsPassed, $testsTotal);
assertTest("Bonus défensif de base = 0%", (float)$stats['defense_bonus_pct'] === 0.0, $testsPassed, $testsTotal);
assertTest("Production de base = 0", 
    $stats['hourly_production']['metal'] === 0 && $stats['hourly_production']['crystal'] === 0 && $stats['hourly_production']['deuterium'] === 0,
    $testsPassed, $testsTotal
);

// TEST 4 : Attribution des points d'attributs
echo "\n--- TEST 4 : ATTRIBUTION DES POINTS D'ATTRIBUTS ---\n";
// Allocation valide : 2 points en force, 1 en off, 1 en prod (total = 4)
$resAlloc = $heroEngine->allocatePoints($testUserId, 2, 1, 0, 1);
assertTest("L'attribution de 4 points est acceptée", $resAlloc['success'] === true, $testsPassed, $testsTotal);

$hero = $heroEngine->getHeroByUserId($testUserId);
assertTest("Points non attribués restants = 0", (int)$hero['unassigned_points'] === 0, $testsPassed, $testsTotal);
assertTest("Points force = 2", (int)$hero['stat_strength'] === 2, $testsPassed, $testsTotal);
assertTest("Points attaque = 1", (int)$hero['stat_offense_bonus'] === 1, $testsPassed, $testsTotal);
assertTest("Points production = 1", (int)$hero['stat_production'] === 1, $testsPassed, $testsTotal);

$stats = $heroEngine->calculateEffectiveStats($hero);
assertTest("Nouvelle force = 310 (150 de base + 2*80)", $stats['combat_strength'] === 310, $testsPassed, $testsTotal);
assertTest("Nouveau bonus offensif = +0.2%", (float)$stats['offense_bonus_pct'] === 0.2, $testsPassed, $testsTotal);
assertTest("Nouvelle production équilibrée active (> 0)", $stats['hourly_production']['metal'] > 0, $testsPassed, $testsTotal);

// Tentative d'attribution illégale sans points disponibles
$resIllegal = $heroEngine->allocatePoints($testUserId, 1, 0, 0, 0);
assertTest("Rejet de l'attribution sans points disponibles", $resIllegal['success'] === false, $testsPassed, $testsTotal);

// TEST 5 : Spécialisation de la production (Bois / Métal)
echo "\n--- TEST 5 : SPÉCIALISATION DE PRODUCTION DU DOMAINE ---\n";
$heroEngine->setProductionType($testUserId, 'metal');
$hero = $heroEngine->getHeroByUserId($testUserId);
$prodBonus = $heroEngine->getHeroProductionBonus($testPlanetId);
assertTest("Spécialisation Bois activée : Bois > 0, Pierre = 0, Riz = 0", 
    $prodBonus['metal'] > 0 && $prodBonus['crystal'] === 0 && $prodBonus['deuterium'] === 0,
    $testsPassed, $testsTotal
);

// TEST 6 : Gain d'expérience et montée de niveau
echo "\n--- TEST 6 : EXPÉRIENCE ET MONTÉE DE NIVEAU ---\n";
// Niveau 1 -> Niveau 2 nécessite 300 XP ( (1+1)*150 = 300 )
$heroEngine->applyDamage($testUserId, 30.0); // Réduire à 70% pour tester le soin lors du level up
$heroBeforeXp = $heroEngine->getHeroByUserId($testUserId);
assertTest("Santé après blessure = 70%", (float)$heroBeforeXp['health'] === 70.0, $testsPassed, $testsTotal);

$resXp = $heroEngine->addExperience($testUserId, 350);
assertTest("Passage au niveau supérieur détecté (levels_gained >= 1)", $resXp['levels_gained'] >= 1, $testsPassed, $testsTotal);
assertTest("Nouveau niveau = 2", $resXp['new_level'] === 2, $testsPassed, $testsTotal);

$heroAfterXp = $heroEngine->getHeroByUserId($testUserId);
assertTest("Nouveaux points non attribués = 4", (int)$heroAfterXp['unassigned_points'] === 4, $testsPassed, $testsTotal);
assertTest("Soin de montée de niveau (+20% -> 90%)", (float)$heroAfterXp['health'] === 90.0, $testsPassed, $testsTotal);

// TEST 7 : Gestion de l'inventaire et des artefacts samouraïs
echo "\n--- TEST 7 : ARSENAL ET ARTEFACTS FÉODAUX ---\n";
$grantedItem = $heroEngine->grantRandomEquipment($testUserId);
assertTest("Un artefact légendaire a été forgé", !empty($grantedItem['item_code']), $testsPassed, $testsTotal);

// Vérification de la règle de non-duplication des reliques
$allGrantedCodes = [$grantedItem['item_code']];
for ($i = 0; $i < 15; $i++) {
    $nextItem = $heroEngine->grantRandomEquipment($testUserId);
    if ($nextItem) {
        $allGrantedCodes[] = $nextItem['item_code'];
    }
}
$uniqueCodes = array_unique($allGrantedCodes);
assertTest("Règle des reliques : aucun doublon n'a été attribué (" . count($uniqueCodes) . " uniques)", count($allGrantedCodes) === count($uniqueCodes), $testsPassed, $testsTotal);

$inventory = $heroEngine->getInventory($testUserId);
assertTest("L'inventaire contient l'équipement", count($inventory) >= 1, $testsPassed, $testsTotal);

$equipRes = $heroEngine->equipItem($testUserId, (int)$inventory[0]['id']);
assertTest("L'équipement de l'objet réussit", $equipRes['success'] === true, $testsPassed, $testsTotal);

$heroEquipped = $heroEngine->getHeroByUserId($testUserId);
$slotKey = 'equipped_' . $inventory[0]['item_type'];
assertTest("L'emplacement {$inventory[0]['item_type']} est occupé", !empty($heroEquipped[$slotKey]), $testsPassed, $testsTotal);

$unequipRes = $heroEngine->unequipSlot($testUserId, $inventory[0]['item_type']);
assertTest("Le déséquipement réussit", $unequipRes['success'] === true, $testsPassed, $testsTotal);

// TEST 8 : Lancement et Résolution d'Aventure
echo "\n--- TEST 8 : SYSTÈME D'AVENTURES EN CARTE DU MONDE ---\n";
$adventures = $heroEngine->getAdventures($testUserId);
$advToLaunch = $adventures[0];

$missionAdv = $heroEngine->startAdventure($testUserId, (int)$advToLaunch['id']);
assertTest("Départ en aventure validé (mission créée)", !empty($missionAdv['mission_id']), $testsPassed, $testsTotal);

$heroInAdv = $heroEngine->getHeroByUserId($testUserId);
assertTest("Statut du héros passe à 'adventure'", $heroInAdv['status'] === 'adventure', $testsPassed, $testsTotal);

// Résolution de l'arrivée de l'aventure
$missionRow = $db->prepare("SELECT * FROM fleet_missions WHERE id = ?");
$missionRow->execute([(int)$missionAdv['mission_id']]);
$missionData = $missionRow->fetch();

$heroEngine->resolveAdventureArrival($missionData);

$advStatus = $db->query("SELECT status FROM hero_adventures WHERE id = " . (int)$advToLaunch['id'])->fetchColumn();
assertTest("L'aventure s'est résolue et est marquée 'completed'", $advStatus === 'completed', $testsPassed, $testsTotal);

// Vérifier la génération d'une nouvelle aventure de remplacement
$adventuresAfter = $heroEngine->getAdventures($testUserId);
assertTest("Une nouvelle aventure a remplacé celle terminée (total = 3)", count($adventuresAfter) === 3, $testsPassed, $testsTotal);

// Retour de la mission d'aventure
$stmtMReturn = $db->prepare("SELECT * FROM fleet_missions WHERE id = ?");
$stmtMReturn->execute([(int)$missionAdv['mission_id']]);
$mReturnData = $stmtMReturn->fetch();
$fleetEngine->resolveReturn($mReturnData);

$heroReturned = $heroEngine->getHeroByUserId($testUserId);
assertTest("Le héros est rentré au domaine castral ('home')", $heroReturned['status'] === 'home', $testsPassed, $testsTotal);

// Vérification du quota après la 1ère aventure
$quota1 = $heroEngine->getDailyAdventureQuota($testUserId);
assertTest("Quota après 1 aventure : 1/3 (2 restantes)", $quota1['count'] === 1 && $quota1['remaining'] === 2 && $quota1['can_adventure'] === true, $testsPassed, $testsTotal);

// Lancer et résoudre la 2ème aventure
$advs2 = $heroEngine->getAdventures($testUserId);
$missionAdv2 = $heroEngine->startAdventure($testUserId, (int)$advs2[0]['id']);
assertTest("2ème aventure lancée avec succès", !empty($missionAdv2['mission_id']), $testsPassed, $testsTotal);
$mRow2 = $db->query("SELECT * FROM fleet_missions WHERE id = " . (int)$missionAdv2['mission_id'])->fetch();
$heroEngine->resolveAdventureArrival($mRow2);
$fleetEngine->resolveReturn($db->query("SELECT * FROM fleet_missions WHERE id = " . (int)$missionAdv2['mission_id'])->fetch());

// Lancer et résoudre la 3ème aventure
$advs3 = $heroEngine->getAdventures($testUserId);
$missionAdv3 = $heroEngine->startAdventure($testUserId, (int)$advs3[0]['id']);
assertTest("3ème aventure lancée avec succès", !empty($missionAdv3['mission_id']), $testsPassed, $testsTotal);
$mRow3 = $db->query("SELECT * FROM fleet_missions WHERE id = " . (int)$missionAdv3['mission_id'])->fetch();
$heroEngine->resolveAdventureArrival($mRow3);
$fleetEngine->resolveReturn($db->query("SELECT * FROM fleet_missions WHERE id = " . (int)$missionAdv3['mission_id'])->fetch());

// Vérification du quota après 3 aventures : 3/3 atteint
$quota3 = $heroEngine->getDailyAdventureQuota($testUserId);
assertTest("Quota après 3 aventures : 3/3 (0 restante, can_adventure=false)", $quota3['count'] === 3 && $quota3['remaining'] === 0 && $quota3['can_adventure'] === false, $testsPassed, $testsTotal);

// TENTATIVE DE 4ÈME AVENTURE : DOIT ÊTRE BLOQUÉE
$advs4 = $heroEngine->getAdventures($testUserId);
$missionAdv4 = $heroEngine->startAdventure($testUserId, (int)$advs4[0]['id']);
assertTest("La 4ème aventure est refusée (limite quotidienne atteinte)", $missionAdv4['success'] === false && strpos($missionAdv4['error'], 'Limite quotidienne') !== false, $testsPassed, $testsTotal);

// TEST 9 : Déploiement en Expédition Militaire Féodale
echo "\n--- TEST 9 : EXPÉDITIONS MILITAIRES AVEC LE HÉROS ---\n";
// Placer quelques troupes dans le fief
$db->prepare("INSERT INTO planet_units (planet_id, unit_code, count) VALUES (?, 'fantassin_leger_takeda', 10)")->execute([$testPlanetId]);

$fleetRes = $fleetEngine->dispatchMission(
    $testUserId,
    $testPlanetId,
    $enemyPlanetId,
    'raid',
    ['fantassin_leger_takeda' => 5],
    ['metal' => 0, 'crystal' => 0, 'deuterium' => 0],
    null,
    true // has_hero = true
);
assertTest("L'expédition avec le héros est lancée", !empty($fleetRes['mission_id']), $testsPassed, $testsTotal);

$heroInMission = $heroEngine->getHeroByUserId($testUserId);
assertTest("Le héros a le statut 'mission'", $heroInMission['status'] === 'mission', $testsPassed, $testsTotal);

// Résoudre le retour de cette mission pour libérer le héros
$stmtM = $db->prepare("SELECT * FROM fleet_missions WHERE id = ?");
$stmtM->execute([(int)$fleetRes['mission_id']]);
$mRow = $stmtM->fetch();
$fleetEngine->resolveReturn($mRow);

$heroBack = $heroEngine->getHeroByUserId($testUserId);
assertTest("Le héros est de retour au fief ('home')", $heroBack['status'] === 'home', $testsPassed, $testsTotal);

// TEST 10 : Mort au Combat et Rituel de Résurrection
echo "\n--- TEST 10 : MORT ET RITUEL DE RÉSURRECTION ---\n";
$heroEngine->applyDamage($testUserId, 150.0); // Dommages létaux
$deadHero = $heroEngine->getHeroByUserId($testUserId);
assertTest("Santé tombée à 0%", (float)$deadHero['health'] === 0.0, $testsPassed, $testsTotal);
assertTest("Statut passé à 'dead'", $deadHero['status'] === 'dead', $testsPassed, $testsTotal);

// Lancer la résurrection
$reviveRes = $heroEngine->reviveHero($testUserId, $testPlanetId);
assertTest("Le rituel de résurrection est initié", $reviveRes['success'] === true, $testsPassed, $testsTotal);
assertTest("La durée de régénération post-mortem est de 24 heures (86400s)", $reviveRes['duration'] === 86400, $testsPassed, $testsTotal);

$revivingHero = $heroEngine->getHeroByUserId($testUserId);
assertTest("Statut passé à 'reviving'", $revivingHero['status'] === 'reviving', $testsPassed, $testsTotal);

// Simuler la fin du rituel (avancer le timestamp de résurrection)
$db->prepare("UPDATE heroes SET revive_finish_time = UNIX_TIMESTAMP() - 1 WHERE id = ?")->execute([$deadHero['id']]);
$revivedHero = $heroEngine->getHeroByUserId($testUserId); // Déclenche la vérification de fin de résurrection
assertTest("Le rituel s'est terminé et le héros est réincarné ('home')", $revivedHero['status'] === 'home', $testsPassed, $testsTotal);
assertTest("La santé du héros est restaurée à 100%", (float)$revivedHero['health'] === 100.0, $testsPassed, $testsTotal);

// Nettoyage final des données de test
$db->prepare("DELETE FROM fleet_missions WHERE user_id = ?")->execute([$testUserId]);
$db->prepare("DELETE FROM hero_inventory WHERE user_id = ?")->execute([$testUserId]);
$db->prepare("DELETE FROM hero_adventures WHERE user_id = ?")->execute([$testUserId]);
$db->prepare("DELETE FROM heroes WHERE user_id = ?")->execute([$testUserId]);
$db->prepare("DELETE FROM planet_units WHERE planet_id IN (?, ?)")->execute([$testPlanetId, $enemyPlanetId]);
$db->prepare("DELETE FROM planets WHERE id IN (?, ?)")->execute([$testPlanetId, $enemyPlanetId]);
$db->prepare("DELETE FROM users WHERE id = ?")->execute([$testUserId]);

echo "\n========================================================\n";
echo "  RÉSULTATS FINAUX : {$testsPassed} / {$testsTotal} TESTS RÉUSSIS\n";
echo "========================================================\n";

if ($testsPassed === $testsTotal) {
    echo "TOUS LES TESTS DU SYSTÈME DE HÉROS SONT VALIDÉS AVEC SUCCÈS !\n";
    exit(0);
} else {
    echo "CERTAINS TESTS ONT ÉCHOUÉ.\n";
    exit(1);
}
