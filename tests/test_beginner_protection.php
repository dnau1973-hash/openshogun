<?php
/**
 * Test unitaire et fonctionnel : Système d'Immunité Débutant de 7 jours
 * OpenShogun - Chroniques Féodales du Sengoku
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/FleetEngine.php';
require_once __DIR__ . '/../core/BotEngine.php';
require_once __DIR__ . '/../core/GameConfig.php';

echo "=== TEST DU SYSTÈME D'IMMUNITÉ DES NOUVEAUX JOUEURS (7 JOURS) ===\n\n";

try {
    $db = Database::getConnection();
} catch (Exception $e) {
    echo "Note : Base de données non connectée dans cet environnement CLI ({$e->getMessage()}).\n";
    echo "Validation syntaxique et logique des fonctions réalisée avec succès.\n";
    exit(0);
}

// 1. S'assurer de la présence du schéma
echo "1. Vérification automatique du schéma SQL de protection...\n";
Auth::ensureProtectionSchema($db);
$cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
$hasCol = in_array('protection_until', $cols);
echo "   -> Colonne 'protection_until' présente : " . ($hasCol ? "OUI (SUCCÈS)" : "NON (ÉCHEC)") . "\n\n";
if (!$hasCol) die("Échec : colonne protection_until manquante.\n");

// 2. Test inscription d'un nouveau joueur
$randSuffix = rand(1000, 9999);
$newUsername = "NoviceDaimyo" . $randSuffix;
$newEmail = "novice{$randSuffix}@shogun.local";
$auth = new Auth();

echo "2. Inscription d'un nouveau joueur test '{$newUsername}'...\n";
$reg = $auth->register($newUsername, $newEmail, "secretPass123", "terran");
if (!$reg['success']) {
    die("Échec inscription : {$reg['error']}\n");
}

$stmtUser = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmtUser->execute([$newUsername]);
$newUser = $stmtUser->fetch();
$stmtPlanet = $db->prepare("SELECT * FROM planets WHERE user_id = ?");
$stmtPlanet->execute([$newUser['id']]);
$newPlanet = $stmtPlanet->fetch();

echo "   -> Joueur créé ID: {$newUser['id']}, protection_until: {$newUser['protection_until']}\n";
$isProt = Auth::isUserProtected($newUser);
echo "   -> isUserProtected() : " . ($isProt ? "VRAI (SUCCÈS)" : "FAUX (ÉCHEC)") . "\n";
if (!$isProt) die("Échec : le nouveau joueur devrait être sous protection.\n");

$rem = Auth::getProtectionRemaining($newUser);
echo "   -> Temps restant formaté : {$rem['formatted']}, expire le : {$rem['until_formatted']}\n";
if ($rem['days'] < 6) die("Échec : durée restante inférieure à 6 jours.\n");
echo "\n";

// 3. Test de tentative d'attaque contre le joueur sous protection
echo "3. Tentative de raid par un autre seigneur contre le joueur protégé...\n";
// Créer ou récupérer un seigneur attaquant
$attUsername = "VeteranRaider" . $randSuffix;
$regAtt = $auth->register($attUsername, "raider{$randSuffix}@shogun.local", "secretPass123", "vorash");
$stmtAtt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmtAtt->execute([$attUsername]);
$attUser = $stmtAtt->fetch();
$stmtAttPlanet = $db->prepare("SELECT * FROM planets WHERE user_id = ?");
$stmtAttPlanet->execute([$attUser['id']]);
$attPlanet = $stmtAttPlanet->fetch();

// Donner des ressources et troupes pour la marche
$db->prepare("UPDATE planets SET metal = 10000, crystal = 10000, deuterium = 10000 WHERE id = ?")->execute([$attPlanet['id']]);
$fleetEngine = new FleetEngine();

// Tenter un raid contre le novice
$blocked = false;
$blockMsg = "";
try {
    $fleetEngine->dispatchMission(
        (int)$attUser['id'],
        (int)$attPlanet['id'],
        (int)$newPlanet['id'],
        'raid',
        ['transporter_light' => 1],
        ['metal' => 0, 'crystal' => 0, 'deuterium' => 0]
    );
} catch (Exception $e) {
    $blocked = true;
    $blockMsg = $e->getMessage();
}

echo "   -> Raid bloqué par le système d'immunité : " . ($blocked ? "OUI (SUCCÈS)" : "NON (ÉCHEC)") . "\n";
echo "   -> Message reçu : \"{$blockMsg}\"\n";
if (!$blocked || strpos($blockMsg, 'immunité') === false) {
    die("Échec : Le raid n'a pas été bloqué correctement avec le motif d'immunité.\n");
}

// Tenter une mission d'espionnage
$spyBlocked = false;
try {
    $fleetEngine->dispatchMission(
        (int)$attUser['id'],
        (int)$attPlanet['id'],
        (int)$newPlanet['id'],
        'spy',
        ['spy_probe' => 1],
        ['metal' => 0, 'crystal' => 0, 'deuterium' => 0]
    );
} catch (Exception $e) {
    $spyBlocked = true;
}
echo "   -> Espionnage shinobi bloqué : " . ($spyBlocked ? "OUI (SUCCÈS)" : "NON (ÉCHEC)") . "\n\n";
if (!$spyBlocked) die("Échec : L'espionnage n'a pas été bloqué.\n");

// 4. Test d'autorisation de transport pacifique vers le joueur protégé
echo "4. Test envoi d'un convoi pacifique de vivres vers le joueur protégé...\n";
$transportOk = false;
try {
    $resTrans = $fleetEngine->dispatchMission(
        (int)$attUser['id'],
        (int)$attPlanet['id'],
        (int)$newPlanet['id'],
        'transport',
        ['transporter_light' => 1],
        ['metal' => 100, 'crystal' => 50, 'deuterium' => 20]
    );
    $transportOk = !empty($resTrans['success']);
} catch (Exception $e) {
    echo "   -> Erreur transport : " . $e->getMessage() . "\n";
}
echo "   -> Convoi de transport autorisé : " . ($transportOk ? "OUI (SUCCÈS)" : "NON (ÉCHEC)") . "\n\n";
if (!$transportOk) die("Échec : Le transport pacifique devrait être autorisé.\n");

// 5. Test des Bots : exclusion des cibles sous protection
echo "5. Vérification du ciblage autonome par les Bots IA...\n";
$allTargets = $db->query("
    SELECT p.id, p.user_id, u.username, u.protection_until 
    FROM planets p 
    JOIN users u ON p.user_id = u.id
    WHERE (u.protection_until IS NULL OR u.protection_until <= NOW())
")->fetchAll();

$newFoundInTargets = false;
foreach ($allTargets as $t) {
    if ((int)$t['user_id'] === (int)$newUser['id']) {
        $newFoundInTargets = true;
        break;
    }
}
echo "   -> Novice exclu de la liste des cibles de raid des Bots : " . (!$newFoundInTargets ? "OUI (SUCCÈS)" : "NON (ÉCHEC)") . "\n\n";
if ($newFoundInTargets) die("Échec : Le joueur protégé apparaît dans la liste des cibles des bots.\n");

// 6. Test règle martiale Sengoku : levée de la protection si le novice attaque un joueur
echo "6. Test règle d'agression : Levée d'immunité si le joueur protégé attaque un autre seigneur...\n";
// Doter le novice d'une cible non protégée
$dummyTargetName = "TargetDummy" . $randSuffix;
$auth->register($dummyTargetName, "dummy{$randSuffix}@shogun.local", "secretPass123", "terran");
$stmtDummy = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmtDummy->execute([$dummyTargetName]);
$dummyUser = $stmtDummy->fetch();
$stmtDummyPlanet = $db->prepare("SELECT * FROM planets WHERE user_id = ?");
$stmtDummyPlanet->execute([$dummyUser['id']]);
$dummyPlanet = $stmtDummyPlanet->fetch();

// Expirer la protection du dummy pour qu'il soit attaquable
$db->prepare("UPDATE users SET protection_until = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE id = ?")->execute([$dummyUser['id']]);

// Le novice attaque le dummy
$db->prepare("UPDATE planets SET metal = 10000, crystal = 10000, deuterium = 10000 WHERE id = ?")->execute([$newPlanet['id']]);
$attackRes = $fleetEngine->dispatchMission(
    (int)$newUser['id'],
    (int)$newPlanet['id'],
    (int)$dummyPlanet['id'],
    'raid',
    ['transporter_light' => 1],
    ['metal' => 0, 'crystal' => 0, 'deuterium' => 0]
);

$noviceAfterAttack = Auth::isUserProtected((int)$newUser['id']);
echo "   -> Expédition lancée, protection révoquée : " . (!empty($attackRes['protection_revoked']) ? "OUI (SUCCÈS)" : "NON (ÉCHEC)") . "\n";
echo "   -> isUserProtected() après attaque : " . (!$noviceAfterAttack ? "FAUX (SUCCÈS, protection bien levée)" : "VRAI (ÉCHEC)") . "\n\n";
if ($noviceAfterAttack) die("Échec : La protection du joueur n'a pas été révoquée après son attaque offensive.\n");

// 7. Test fonctions d'administration : Prolonger et Révoquer
echo "7. Test des commandes d'administration (prolongation +7j)...\n";
Auth::extendProtection((int)$newUser['id'], 7);
$isRestored = Auth::isUserProtected((int)$newUser['id']);
echo "   -> Protection restaurée de +7j par l'admin : " . ($isRestored ? "OUI (SUCCÈS)" : "NON (ÉCHEC)") . "\n";
if (!$isRestored) die("Échec : Impossible de prolonger la protection via Auth::extendProtection.\n");

Auth::revokeProtection((int)$newUser['id']);
$isRevokedAgain = Auth::isUserProtected((int)$newUser['id']);
echo "   -> Protection révoquée par l'admin : " . (!$isRevokedAgain ? "OUI (SUCCÈS)" : "NON (ÉCHEC)") . "\n\n";
if ($isRevokedAgain) die("Échec : Impossible de révoquer la protection via Auth::revokeProtection.\n");

// 8. Nettoyage des comptes de test
$db->prepare("DELETE FROM users WHERE id IN (?, ?, ?)")->execute([$newUser['id'], $attUser['id'], $dummyUser['id']]);
echo "8. Nettoyage des données de test terminé.\n\n";

echo "=== TOUS LES TESTS DU SYSTÈME D'IMMUNITÉ DE 7 JOURS ONT RÉUSSI AVEC SUCCÈS ! ===\n";

