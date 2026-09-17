<?php
/**
 * Banc de Tests : Système de Support, Bugs & Suggestions (Joueur & Admin)
 */
if (!defined('SPEED_FACTOR')) {
    define('SPEED_FACTOR', 5);
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/SupportEngine.php';

echo "========================================================\n";
echo "  BANC DE TESTS : SYSTÈME DE SUPPORT & BUGS/SUGGESTIONS\n";
echo "========================================================\n\n";

// 1. Vérification du routage dans index.php
$indexContent = file_get_contents(__DIR__ . '/../index.php');
if (strpos($indexContent, "'support'") !== false) {
    echo "[PASS] Route 'support' autorisée dans index.php\n";
} else {
    echo "[FAIL] Route 'support' absente de index.php\n";
    exit(1);
}

// 2. Vérification du bouton dans header.php
$headerContent = file_get_contents(__DIR__ . '/../views/partials/header.php');
if (strpos($headerContent, '?page=support') !== false) {
    echo "[PASS] Bouton d'assistance 📮 intégré dans header.php\n";
} else {
    echo "[FAIL] Bouton 📮 absent du header\n";
    exit(1);
}

// 3. Test de création de tickets via SupportEngine
$db = Database::getConnection();
$supportEngine = new SupportEngine();

// Récupérer un utilisateur test (ex: ID 1 ou admin)
$user = $db->query("SELECT id, username FROM users WHERE is_bot = 0 ORDER BY id ASC LIMIT 1")->fetch();
if (!$user) {
    echo "[FAIL] Aucun utilisateur trouvé pour le test.\n";
    exit(1);
}
$userId = (int)$user['id'];
echo "[INFO] Utilisateur test : {$user['username']} (#{$userId})\n";

// TEST 3.1 : Création d'un ticket Bug
$bugTitle = "Test Auto Bug : Problème d'affichage sur parcelle #12";
$bugDesc = "Lors de l'amélioration de la rizière au niveau 3, un décalage graphique apparaît sur le compteur.";
$ticketBug = $supportEngine->createTicket(
    $userId,
    'bug',
    'resources',
    $bugTitle,
    $bugDesc,
    'medium'
);

if ($ticketBug && $ticketBug['id'] > 0) {
    echo "[PASS] Ticket Bug créé avec succès (ID #{$ticketBug['id']})\n";
} else {
    echo "[FAIL] Échec de création du ticket Bug\n";
    exit(1);
}
$bugId = $ticketBug['id'];

// TEST 3.2 : Création d'un ticket Suggestion
$suggTitle = "Test Auto Suggestion : Ajout d'un raccourci clavier pour la carte";
$suggDesc = "Il serait très pratique de pouvoir utiliser les touches fléchées pour naviguer sur la carte des provinces.";
$ticketSugg = $supportEngine->createTicket(
    $userId,
    'suggestion',
    'map',
    $suggTitle,
    $suggDesc,
    'low'
);

if ($ticketSugg && $ticketSugg['id'] > 0) {
    echo "[PASS] Ticket Suggestion créé avec succès (ID #{$ticketSugg['id']})\n";
} else {
    echo "[FAIL] Échec de création du ticket Suggestion\n";
    exit(1);
}
$suggId = $ticketSugg['id'];

// 4. Récupération des tickets du joueur
$userTickets = $supportEngine->getUserTickets($userId);
$foundBug = false;
$foundSugg = false;
foreach ($userTickets as $t) {
    if ((int)$t['id'] === $bugId) $foundBug = true;
    if ((int)$t['id'] === $suggId) $foundSugg = true;
}

if ($foundBug && $foundSugg) {
    echo "[PASS] getUserTickets renvoie bien tous les tickets du joueur\n";
} else {
    echo "[FAIL] Tickets manquants dans getUserTickets\n";
    exit(1);
}

// 5. Récupération de tous les tickets et statistiques pour l'admin
$stats = $supportEngine->getStatistics();
if ($stats['total'] >= 2 && $stats['count_pending'] >= 2) {
    echo "[PASS] getStatistics() calcule correctement les compteurs (Total: {$stats['total']}, En attente: {$stats['count_pending']})\n";
} else {
    echo "[FAIL] Statistiques incorrectes\n";
    exit(1);
}

$allBugs = $supportEngine->getAllTickets('bug');
$hasBugInList = array_filter($allBugs, fn($b) => (int)$b['id'] === $bugId);
if (!empty($hasBugInList)) {
    echo "[PASS] getAllTickets('bug') filtre correctement les tickets de bugs\n";
} else {
    echo "[FAIL] Filtre par type défaillant\n";
    exit(1);
}

// 6. Traitement par l'administrateur
$adminResponse = "Merci pour votre signalement. L'anomalie a été isolée et résolue par nos maîtres charpentiers.";
$updateRes = $supportEngine->updateTicketStatus(
    $bugId,
    'resolved',
    $adminResponse,
    $userId,
    true // Notifier le joueur par missive
);

if ($updateRes['status'] === 'resolved') {
    echo "[PASS] updateTicketStatus() a mis à jour le statut vers 'resolved'\n";
} else {
    echo "[FAIL] Échec de mise à jour du statut\n";
    exit(1);
}

// 7. Vérifier que le ticket a bien la réponse en base
$ticketChecked = $supportEngine->getTicketById($bugId);
if ($ticketChecked && $ticketChecked['status'] === 'resolved' && strpos($ticketChecked['admin_response'], 'charpentiers') !== false) {
    echo "[PASS] getTicketById() contient bien la réponse officielle et le nouveau statut\n";
} else {
    echo "[FAIL] Données de ticket non conformes après mise à jour\n";
    exit(1);
}

// 8. Vérifier que la missive de notification a bien été créée dans la table messages
$stmtMsg = $db->prepare("
    SELECT * FROM messages 
    WHERE receiver_id = ? AND subject LIKE ? 
    ORDER BY id DESC LIMIT 1
");
$stmtMsg->execute([$userId, "%#{$bugId}%"]);
$notifMsg = $stmtMsg->fetch();

if ($notifMsg && strpos($notifMsg['body'], 'charpentiers') !== false) {
    echo "[PASS] Missive impériale reçue dans la messagerie du joueur avec la réponse des développeurs\n";
} else {
    echo "[FAIL] Aucune missive générée pour le joueur\n";
    exit(1);
}

// 9. Test de rendu de la vue support.php
$_SESSION['user_id'] = $userId;
$_SESSION['planet_id'] = 1;
$_GET['page'] = 'support';
$_GET['tab'] = 'history';

ob_start();
try {
    require __DIR__ . '/../views/support.php';
    $supportHtml = ob_get_clean();
    if (strlen($supportHtml) > 1000 && (strpos($supportHtml, htmlspecialchars($bugTitle)) !== false || strpos($supportHtml, $bugTitle) !== false)) {
        echo "[PASS] Rendu complet de views/support.php validé avec affichage des tickets\n";
    } else {
        echo "[FAIL] Contenu de views/support.php inattendu\n";
        exit(1);
    }
} catch (Throwable $e) {
    ob_end_clean();
    echo "[FAIL] Erreur de rendu de views/support.php : " . $e->getMessage() . "\n";
    exit(1);
}

// 10. Nettoyage des tickets de test
$supportEngine->deleteTicket($bugId);
$supportEngine->deleteTicket($suggId);
if ($notifMsg) {
    $db->prepare("DELETE FROM messages WHERE id = ?")->execute([$notifMsg['id']]);
}

$checkDeleted = $supportEngine->getTicketById($bugId);
if (!$checkDeleted) {
    echo "[PASS] Suppression et nettoyage des tickets de test validés\n";
} else {
    echo "[FAIL] Échec du nettoyage du ticket\n";
    exit(1);
}

echo "\n========================================================\n";
echo "  TOUS LES TESTS DU SYSTÈME DE SUPPORT SONT VALIDÉS !   \n";
echo "========================================================\n";
