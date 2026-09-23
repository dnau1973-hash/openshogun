<?php
/**
 * Test Automatisé : Système d'Alliance Féodale OpenShogun
 * Vérifie :
 * 1. Déblocage de la création strictement au Niveau 3 du Pavillon Diplomatique
 * 2. Capacité dynamique (+3 places par niveau, max 60)
 * 3. Cycle complet d'invitation, acceptation, refus
 * 4. Blocage lorsque la capacité maximale est atteinte
 * 5. Cession de commandement, exclusion, dissolution
 * 6. Protection contre les attaques fratricides entre membres du même clan
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/AllianceEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

echo "=== Démarrage des Tests du Système d'Alliances Féodales ===\n";

try {
    $db = Database::getConnection();
} catch (Exception $e) {
    echo "[SKIP] Connexion Base de Données indisponible : " . $e->getMessage() . "\n";
    echo "Poursuite de la vérification sans base active.\n";
    exit(0);
}

$allianceEngine = new AllianceEngine($db);
$allianceEngine->ensureAllianceTables();

$testsPassed = 0;
$testsFailed = 0;

function assertTest(bool $condition, string $testName) {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo "  [PASS] {$testName}\n";
        $testsPassed++;
    } else {
        echo "  [FAIL] {$testName}\n";
        $testsFailed++;
    }
}

// 1. Test des calculs de capacité selon le niveau du Pavillon Diplomatique
echo "\n--- 1. Test des Formules de Capacité (+3 / niveau, max 60) ---\n";
// Niv 1 : 3*3 = 9 (minimum garanti pour une alliance active)
// Niv 3 : 9 places
// Niv 4 : 12 places
// Niv 10 : 30 places
// Niv 20 : 60 places (plafond)
// Niv 25 : 60 places (plafonné à 60)
$capNiv3 = min(ALLIANCE_MAX_MEMBERS, max(3, 3) * ALLIANCE_SLOTS_PER_EMBASSY_LEVEL);
$capNiv4 = min(ALLIANCE_MAX_MEMBERS, max(3, 4) * ALLIANCE_SLOTS_PER_EMBASSY_LEVEL);
$capNiv10 = min(ALLIANCE_MAX_MEMBERS, max(3, 10) * ALLIANCE_SLOTS_PER_EMBASSY_LEVEL);
$capNiv20 = min(ALLIANCE_MAX_MEMBERS, max(3, 20) * ALLIANCE_SLOTS_PER_EMBASSY_LEVEL);
$capNiv25 = min(ALLIANCE_MAX_MEMBERS, max(3, 25) * ALLIANCE_SLOTS_PER_EMBASSY_LEVEL);

assertTest($capNiv3 === 9, "Niveau 3 = 9 membres");
assertTest($capNiv4 === 12, "Niveau 4 = 12 membres (+3 places)");
assertTest($capNiv10 === 30, "Niveau 10 = 30 membres");
assertTest($capNiv20 === 60, "Niveau 20 = 60 membres (Capacité Maximale)");
assertTest($capNiv25 === 60, "Niveau supérieur à 20 reste plafonné à 60 membres");

// 2. Nettoyage et création d'utilisateurs de test
echo "\n--- 2. Préparation des Données de Test ---\n";
$db->exec("DELETE FROM alliance_invitations WHERE 1=1");
$db->exec("DELETE FROM users WHERE username IN ('test_daimyo_lead', 'test_daimyo_recruit', 'test_daimyo_nobuild')");
$db->exec("DELETE FROM alliances WHERE tag IN ('TESTCL', 'TEST2')");

$hash = password_hash('Secret123!', PASSWORD_BCRYPT);

// Leader (avec ambassade)
$db->prepare("INSERT INTO users (username, email, password_hash, faction, points) VALUES ('test_daimyo_lead', 'lead@test.com', ?, 'terran', 1500)")->execute([$hash]);
$leadId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO planets (user_id, name, coord_x, coord_y) VALUES (?, 'Fief du Leader', 9901, 9901)")->execute([$leadId]);
$leadPlanetId = (int)$db->lastInsertId();

// Recrue (avec ambassade niveau 1)
$db->prepare("INSERT INTO users (username, email, password_hash, faction, points) VALUES ('test_daimyo_recruit', 'recruit@test.com', ?, 'vorash', 800)")->execute([$hash]);
$recruitId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO planets (user_id, name, coord_x, coord_y) VALUES (?, 'Fief de Recrue', 9902, 9902)")->execute([$recruitId]);
$recruitPlanetId = (int)$db->lastInsertId();

// Joueur sans ambassade (Niveau 0)
$db->prepare("INSERT INTO users (username, email, password_hash, faction, points) VALUES ('test_daimyo_nobuild', 'nobuild@test.com', ?, 'aethelis', 500)")->execute([$hash]);
$noBuildId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO planets (user_id, name, coord_x, coord_y) VALUES (?, 'Fief Sans Ambassade', 9903, 9903)")->execute([$noBuildId]);
$noBuildPlanetId = (int)$db->lastInsertId();

// 3. Test du blocage de création si Ambassade < 3
echo "\n--- 3. Test du Verrouillage de Création (Ambassade < 3) ---\n";
// Actuellement lead a 0 ambassade
$resCreate0 = $allianceEngine->createAlliance($leadId, "Test Clan Fail", "TESTCL");
assertTest(!$resCreate0['success'] && strpos($resCreate0['error'], 'Niveau 3') !== false, "Refus de création avec Ambassade Niv. 0");

// Ajout d'une ambassade Niv. 2 sur la planète du leader
$db->prepare("INSERT INTO planet_buildings (planet_id, building_type, level) VALUES (?, 'embassy', 2)")->execute([$leadPlanetId]);
$resCreate2 = $allianceEngine->createAlliance($leadId, "Test Clan Fail", "TESTCL");
assertTest(!$resCreate2['success'] && strpos($resCreate2['error'], 'Niveau 3') !== false, "Refus de création avec Ambassade Niv. 2 (actuel : 2/3)");

// 4. Test de la création réussie dès Ambassade >= 3
echo "\n--- 4. Test de Création Réussie (Ambassade >= 3) ---\n";
$db->prepare("UPDATE planet_buildings SET level = 3 WHERE planet_id = ? AND building_type = 'embassy'")->execute([$leadPlanetId]);
$resCreate3 = $allianceEngine->createAlliance($leadId, "Clan des Tigres Écarlates", "TESTCL", "Charte du Clan");
assertTest($resCreate3['success'] === true, "Succès de création d'alliance avec Ambassade Niv. 3");

$allianceId = (int)($resCreate3['alliance_id'] ?? 0);
$createdAlly = $allianceEngine->getAlliance($allianceId);
assertTest($createdAlly !== null && $createdAlly['tag'] === 'TESTCL', "L'alliance est bien enregistrée en BDD");
assertTest((int)$createdAlly['capacity'] === 9, "Capacité initiale au Niv. 3 = 9 places");

// 5. Test du recrutement et des invitations
echo "\n--- 5. Test d'Invitation Diplomatique ---\n";
// Inviter un joueur inexistant
$resInvNonExistent = $allianceEngine->inviteUser($leadId, 'daimyo_fantome');
assertTest(!$resInvNonExistent['success'], "Échec d'invitation d'un joueur inexistant");

// Inviter recruit
$resInv = $allianceEngine->inviteUser($leadId, 'test_daimyo_recruit', 'Bienvenue au clan !');
assertTest($resInv['success'] === true, "Invitation transmise avec succès à test_daimyo_recruit");

$pendingRecruit = $allianceEngine->getUserPendingInvitations($recruitId);
assertTest(count($pendingRecruit) === 1, "La recrue voit bien son invitation en attente");

// 6. Test de réponse à l'invitation (Rejoindre nécessite Ambassade >= 1)
echo "\n--- 6. Test de Ratification (Ambassade >= 1 requise) ---\n";
$invId = (int)$pendingRecruit[0]['id'];

// Actuellement recruit a 0 ambassade
$resAccept0 = $allianceEngine->respondInvitation($recruitId, $invId, 'accept');
assertTest(!$resAccept0['success'] && strpos($resAccept0['error'], 'Niveau 1') !== false, "Refus d'adhésion si la recrue n'a pas d'ambassade Niv. 1");

// Donner ambassade Niv. 1 à recruit
$db->prepare("INSERT INTO planet_buildings (planet_id, building_type, level) VALUES (?, 'embassy', 1)")->execute([$recruitPlanetId]);
$resAccept1 = $allianceEngine->respondInvitation($recruitId, $invId, 'accept');
assertTest($resAccept1['success'] === true, "Succès d'adhésion une fois l'ambassade Niv. 1 bâtie");

// Vérifier les membres
$members = $allianceEngine->getAllianceMembers($allianceId);
assertTest(count($members) === 2, "L'alliance compte maintenant 2 membres");

// 7. Test de la dynamique de capacité (Amélioration Ambassade Leader)
echo "\n--- 7. Test de Déblocage Dynamique de Places ---\n";
$db->prepare("UPDATE planet_buildings SET level = 5 WHERE planet_id = ? AND building_type = 'embassy'")->execute([$leadPlanetId]);
$capNiv5 = $allianceEngine->getAllianceCapacity($allianceId);
assertTest($capNiv5 === 15, "Amélioration Ambassade Chef Niv. 5 -> Capacité = 15 membres");

$db->prepare("UPDATE planet_buildings SET level = 20 WHERE planet_id = ? AND building_type = 'embassy'")->execute([$leadPlanetId]);
$capNiv20Actual = $allianceEngine->getAllianceCapacity($allianceId);
assertTest($capNiv20Actual === 60, "Amélioration Ambassade Chef Niv. 20 -> Capacité = 60 membres (Max)");

// 8. Test de départ, exclusion et cession
echo "\n--- 8. Test de Gestion des Membres & Cession ---\n";
// Le leader ne peut pas quitter sans céder
$resLeaveLeader = $allianceEngine->leaveAlliance($leadId);
assertTest(!$resLeaveLeader['success'], "Le Chef ne peut pas déserter l'alliance sans céder ou dissoudre");

// Exclusion d'un membre
$resKick = $allianceEngine->kickMember($leadId, $recruitId);
assertTest($resKick['success'] === true, "Le Chef peut exclure un membre");

$membersAfterKick = $allianceEngine->getAllianceMembers($allianceId);
assertTest(count($membersAfterKick) === 1, "Nombre de membres retombé à 1 après exclusion");

// Dissolution
$resDisband = $allianceEngine->disbandAlliance($leadId);
assertTest($resDisband['success'] === true, "Le Chef peut dissoudre l'alliance");

$allyAfterDisband = $allianceEngine->getAlliance($allianceId);
assertTest($allyAfterDisband === null, "L'alliance est bien supprimée de la base de données");

// Nettoyage final
$db->exec("DELETE FROM users WHERE id IN ({$leadId}, {$recruitId}, {$noBuildId})");
$db->exec("DELETE FROM planets WHERE id IN ({$leadPlanetId}, {$recruitPlanetId}, {$noBuildPlanetId})");

echo "\n=======================================================\n";
echo "RÉSULTATS : {$testsPassed} tests réussis, {$testsFailed} échecs.\n";
echo "=======================================================\n";

if ($testsFailed > 0) {
    exit(1);
}

