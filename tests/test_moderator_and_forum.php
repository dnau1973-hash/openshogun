<?php
/**
 * Test Automatisé : Rôle Modérateur et Forum Féodal OpenShogun
 * Vérifie :
 * 1. Promotion et rétrogradation du statut de Modérateur (Auth::setModerator, Auth::isUserModerator)
 * 2. Initialisation automatique du schéma forum et catégories par défaut
 * 3. Création, édition et suppression de catégories (Admin)
 * 4. Création de sujets (topics) et réponses (posts)
 * 5. Privilèges de Modération (Épingler, Verrouiller, Édition, Suppression)
 * 6. Protection des sujets verrouillés contre les réponses des joueurs ordinaires
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ForumEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

echo "=== Démarrage des Tests du Rôle Modérateur & Forum Féodal ===\n";

try {
    $db = Database::getConnection();
} catch (Exception $e) {
    echo "[SKIP] Connexion Base de Données indisponible : " . $e->getMessage() . "\n";
    echo "Poursuite de la vérification sans base active.\n";
    exit(0);
}

$auth = new Auth();
$auth->ensureProtectionSchema();

$forumEngine = new ForumEngine($db);
$forumEngine->ensureForumTables();

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

// Trouver un utilisateur test ou en créer un
$testUserStmt = $db->query("SELECT id, username, is_moderator, is_admin FROM users ORDER BY id ASC LIMIT 2");
$testUsers = $testUserStmt->fetchAll();

if (empty($testUsers)) {
    echo "[SKIP] Aucun utilisateur trouvé en base pour les tests.\n";
    exit(0);
}

$u1 = $testUsers[0];
$u1Id = (int)$u1['id'];

// 1. Test Statut Modérateur
echo "\n--- 1. Test Gestion Statut Modérateur ---\n";
$initialModStatus = Auth::isUserModerator($u1Id);

// Activer le statut modérateur
$setOk = Auth::setModerator($u1Id, true);
assertTest($setOk === true, "Promotion du joueur #{$u1Id} au rang de Modérateur");
assertTest(Auth::isUserModerator($u1Id) === true, "Vérification isUserModerator() = true après promotion");

// Désactiver le statut modérateur
$unsetOk = Auth::setModerator($u1Id, false);
assertTest($unsetOk === true, "Rétrogradation du joueur #{$u1Id} au rang de Daimyō ordinaire");
assertTest(Auth::isUserModerator($u1Id) === false, "Vérification isUserModerator() = false après rétrogradation");

// Rétablir le statut initial ou définir un modérateur pour la suite des tests
Auth::setModerator($u1Id, true);

// 2. Test Catégories du Forum
echo "\n--- 2. Test Catégories du Forum ---\n";
$categories = $forumEngine->getCategories();
assertTest(!empty($categories), "Récupération des catégories (au moins les catégories par défaut)");
assertTest(count($categories) >= 4, "Présence d'au moins 4 catégories dans le forum");

// Créer une catégorie d'administration de test
$catResult = $forumEngine->createCategory(
    "Zone d'Exercice Test",
    "Catégorie temporaire pour validation des suites de tests automatisées",
    "⚔️",
    99,
    false
);
assertTest($catResult['success'] === true, "Création d'une nouvelle catégorie par l'administration");
$testCatId = (int)($catResult['category_id'] ?? 0);
assertTest($testCatId > 0, "ID de la catégorie de test valide (#{$testCatId})");

$catInfo = $forumEngine->getCategory($testCatId);
assertTest($catInfo !== null && $catInfo['title'] === "Zone d'Exercice Test", "Lecture des détails de la catégorie créée");

// 3. Test Création de Sujet & Réponse
echo "\n--- 3. Test Création de Sujet et Réponses ---\n";
$topicRes = $forumEngine->createTopic(
    $testCatId,
    $u1Id,
    "Édit Impérial : Répétition Générale",
    "Ceci est le message inaugural du sujet d'entraînement pour tester les fonctionnalités du forum féodal."
);
assertTest($topicRes['success'] === true, "Création d'un sujet (Topic) avec message initial");
$topicId = (int)($topicRes['topic_id'] ?? 0);
assertTest($topicId > 0, "ID du sujet créé valide (#{$topicId})");

$topic = $forumEngine->getTopic($topicId);
assertTest($topic !== null && $topic['title'] === "Édit Impérial : Répétition Générale", "Lecture du sujet créé");
assertTest((int)$topic['posts_count'] === 1, "Compteur de messages du sujet initialisé à 1");

// Ajouter une réponse
$replyRes = $forumEngine->replyToTopic(
    $topicId,
    $u1Id,
    "Voici une réponse officielle envoyée par le Daimyō en personne."
);
assertTest($replyRes['success'] === true, "Ajout d'une réponse au sujet");
$replyPostId = (int)($replyRes['post_id'] ?? 0);
assertTest($replyPostId > 0, "ID du message de réponse valide (#{$replyPostId})");

$topicUpdated = $forumEngine->getTopic($topicId);
assertTest((int)$topicUpdated['posts_count'] === 2, "Compteur de messages incrémenté à 2");

// 4. Test Modération (Épingler, Verrouiller, Éditer)
echo "\n--- 4. Test Privilèges de Modération ---\n";
$pinRes = $forumEngine->setTopicPinned($topicId, true);
assertTest($pinRes['success'] === true, "Épinglage du sujet par la modération");
$topicPinned = $forumEngine->getTopic($topicId);
assertTest((int)$topicPinned['is_pinned'] === 1, "Vérification is_pinned = 1");

$lockRes = $forumEngine->setTopicLocked($topicId, true);
assertTest($lockRes['success'] === true, "Verrouillage du sujet par la modération");
$topicLocked = $forumEngine->getTopic($topicId);
assertTest((int)$topicLocked['is_locked'] === 1, "Vérification is_locked = 1");

// Test permission de réponse sur sujet verrouillé :
// Un utilisateur ordinaire ne doit pas pouvoir répondre, un membre du staff oui
$normalUserRes = $forumEngine->replyToTopic($topicId, 999999, "Tentative de réponse non autorisée", false);
assertTest($normalUserRes['success'] === false, "Refus de réponse pour un joueur ordinaire sur sujet verrouillé");

$staffUserRes = $forumEngine->replyToTopic($topicId, $u1Id, "Intervention du Modérateur sur sujet clos", true);
assertTest($staffUserRes['success'] === true, "Autorisation de réponse pour le Staff sur sujet verrouillé");

// Édition de message
$editRes = $forumEngine->editPost($replyPostId, $u1Id, "Message révisé avec sagesse et équité.", true);
assertTest($editRes['success'] === true, "Édition d'un message par le modérateur");

// 5. Test Nettoyage et Suppression
echo "\n--- 5. Test Suppression et Nettoyage ---\n";
$delPostRes = $forumEngine->deletePost($replyPostId, $u1Id, true);
assertTest($delPostRes['success'] === true, "Suppression d'un message de réponse");

$delTopicRes = $forumEngine->deleteTopic($topicId, $u1Id, true);
assertTest($delTopicRes['success'] === true, "Suppression du sujet de test");

$delCatRes = $forumEngine->deleteCategory($testCatId);
assertTest($delCatRes['success'] === true, "Suppression de la catégorie de test");

// Rétablir le statut modérateur initial
Auth::setModerator($u1Id, $initialModStatus);

// Résumé
echo "\n============================================\n";
echo "RÉSULTATS : {$testsPassed} PASS / {$testsFailed} FAIL\n";
echo "============================================\n";

if ($testsFailed > 0) {
    exit(1);
}
exit(0);

