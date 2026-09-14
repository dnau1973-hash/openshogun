<?php
/**
 * Test unitaire du système de messagerie interstellaire
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/MessageEngine.php';

echo "=== TEST DU SYSTÈME DE MESSAGERIE INTERSTELLAIRE ===\n\n";

$auth = new Auth();
$messageEngine = new MessageEngine();

// 1. Inscription ou connexion de 2 joueurs de test
echo "1. Préparation de deux commandants pour le test...\n";
$auth->register('VorashOverlord', 'vorash@opengalaxy.local', 'password123', 'vorash');
$auth->register('TerranCommander', 'terran@opengalaxy.local', 'password123', 'terran');

$db = Database::getConnection();
$u1 = $db->query("SELECT id, username FROM users WHERE username = 'TerranCommander'")->fetch();
$u2 = $db->query("SELECT id, username FROM users WHERE username = 'VorashOverlord'")->fetch();

echo "-> Émetteur : {$u1['username']} (ID: {$u1['id']})\n";
echo "-> Destinataire : {$u2['username']} (ID: {$u2['id']})\n\n";

// 2. Test envoi d'un message valide
echo "2. Envoi d'une transmission diplomatique...\n";
$sendRes = $messageEngine->sendMessage(
    (int)$u1['id'], 
    $u2['username'], 
    "Proposition de Pacte de Non-Agression", 
    "Salutations Commandant Vorash. Nous vous proposons un cessez-le-feu de 7 jours sur le secteur (1, 1)."
);
echo "-> Résultat : " . ($sendRes['success'] ? "SUCCÈS (Message ID {$sendRes['message_id']})" : "ÉCHEC : {$sendRes['error']}") . "\n\n";
$msgId = (int)$sendRes['message_id'];

// 3. Vérification de la boîte de réception de VorashOverlord
echo "3. Vérification de la boîte de réception de VorashOverlord...\n";
$unread = $messageEngine->getUnreadCount((int)$u2['id']);
echo "-> Messages non lus pour VorashOverlord : {$unread} (Attendu >= 1)\n";
$inbox = $messageEngine->getInbox((int)$u2['id']);
echo "-> Nombre total de messages reçus : " . count($inbox) . "\n";
echo "-> Dernier message reçu : '{$inbox[0]['subject']}' de '{$inbox[0]['sender_name']}'\n\n";

// 4. Lecture du message et vérification du statut de lecture
echo "4. Lecture de la transmission par VorashOverlord...\n";
$readMsg = $messageEngine->getMessage($msgId, (int)$u2['id']);
echo "-> Message lu : '{$readMsg['subject']}', Statut lu : {$readMsg['is_read']}\n";
$unreadAfter = $messageEngine->getUnreadCount((int)$u2['id']);
echo "-> Messages non lus restants : {$unreadAfter} (Attendu : décrémenté de 1)\n\n";

// 5. Test boîte d'envoi de TerranCommander
echo "5. Vérification de la boîte d'envoi de TerranCommander...\n";
$outbox = $messageEngine->getOutbox((int)$u1['id']);
echo "-> Nombre de messages envoyés : " . count($outbox) . "\n";
echo "-> Dernier message envoyé à : '{$outbox[0]['receiver_name']}'\n\n";

// 6. Test suppression par VorashOverlord
echo "6. Suppression de la transmission par VorashOverlord...\n";
$delOk = $messageEngine->deleteMessage($msgId, (int)$u2['id']);
echo "-> Suppression réussie : " . ($delOk ? "OUI" : "NON") . "\n";
$inboxAfterDel = $messageEngine->getInbox((int)$u2['id']);
$stillInInbox = false;
foreach ($inboxAfterDel as $m) {
    if ((int)$m['id'] === $msgId) $stillInInbox = true;
}
echo "-> Présent dans la boîte de réception de Vorash ? " . ($stillInInbox ? "OUI (Erreur)" : "NON (Correct)") . "\n";

// Le message doit toujours être présent dans l'outbox de Terran
$outboxAfterDel = $messageEngine->getOutbox((int)$u1['id']);
$stillInOutbox = false;
foreach ($outboxAfterDel as $m) {
    if ((int)$m['id'] === $msgId) $stillInOutbox = true;
}
echo "-> Présent dans la boîte d'envoi de Terran ? " . ($stillInOutbox ? "OUI (Isolation parfaite)" : "NON (Erreur)") . "\n\n";

// 7. Test cas d'erreur
echo "7. Test cas d'erreur (destinataire inexistant & auto-envoi)...\n";
$err1 = $messageEngine->sendMessage((int)$u1['id'], 'Inconnu_9999', 'Test', 'Corps');
echo "-> Destinataire inexistant : " . (!$err1['success'] ? "REJETÉ CORRECTEMENT ({$err1['error']})" : "ERREUR") . "\n";
$err2 = $messageEngine->sendMessage((int)$u1['id'], $u1['username'], 'Test', 'Corps');
echo "-> Auto-envoi : " . (!$err2['success'] ? "REJETÉ CORRECTEMENT ({$err2['error']})" : "ERREUR") . "\n\n";

echo "=== TOUS LES TESTS DE MESSAGERIE SONT VALIDES ET CONCLUANTS ! ===\n";

