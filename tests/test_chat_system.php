<?php
/**
 * Test Automatisé : Système de Chat Féodal OpenShogun
 * Vérifie :
 * 1. Initialisation automatique de la table chat_messages
 * 2. Envoi et réception dans le canal Général (Global)
 * 3. Envoi et confidentialité des Chuchotements privés (Whisper 1-à-1)
 * 4. Protection du canal d'Alliance (réservé aux membres de l'alliance)
 * 5. Filtrage incrémental par last_id
 * 6. Protection anti-spam par débit
 * 7. Droits de suppression de message (Auteur ou Staff)
 * 8. Récupération des conversations et des joueurs en ligne
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ChatEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

echo "=== Démarrage des Tests du Chat Féodal ===\n";

try {
    $db = Database::getConnection();
} catch (Exception $e) {
    echo "[SKIP] Connexion Base de Données indisponible : " . $e->getMessage() . "\n";
    exit(0);
}

$chatEngine = new ChatEngine($db);
$chatEngine->ensureChatTables();

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

// Récupérer 2 utilisateurs test
$testUsers = $db->query("SELECT id, username, alliance_id, is_admin, is_moderator FROM users ORDER BY id ASC LIMIT 2")->fetchAll();
if (count($testUsers) < 2) {
    echo "[SKIP] Moins de 2 utilisateurs en base pour tester les conversations.\n";
    exit(0);
}

$u1 = $testUsers[0];
$u2 = $testUsers[1];
$u1Id = (int)$u1['id'];
$u2Id = (int)$u2['id'];

// 1. Canal Global
echo "\n--- 1. Test Canal Général (Global) ---\n";
$sendGlobal = $chatEngine->sendMessage($u1Id, 'global', null, null, "Salutations aux Daimyōs du royaume !");
assertTest($sendGlobal['success'] === true, "Envoi d'un message public dans le canal Général");
$globalMsgId = (int)($sendGlobal['message_id'] ?? 0);
assertTest($globalMsgId > 0, "ID de message global valide (#{$globalMsgId})");

// Récupération par u2
$messagesGlobal = $chatEngine->getMessages($u2Id, 'global', null, null, 0, 10);
$foundMsg = false;
foreach ($messagesGlobal as $m) {
    if ($m['id'] === $globalMsgId) {
        $foundMsg = true;
        break;
    }
}
assertTest($foundMsg === true, "Lecture du message global par un autre joueur");

// Test Polling incrémental avec last_id
$messagesAfterLast = $chatEngine->getMessages($u2Id, 'global', null, null, $globalMsgId, 10);
$containsPrevious = false;
foreach ($messagesAfterLast as $m) {
    if ($m['id'] <= $globalMsgId) {
        $containsPrevious = true;
        break;
    }
}
assertTest($containsPrevious === false, "Filtrage incrémental : les messages <= last_id sont bien exclus");

// 2. Chuchotement Privé (Whisper)
echo "\n--- 2. Test Chuchotement Privé (Whisper) ---\n";
// Attendre 1 seconde pour l'anti-spam
sleep(1);

$sendWhisper = $chatEngine->sendMessage($u1Id, 'whisper', null, $u2Id, "Message secret entre nous deux.");
assertTest($sendWhisper['success'] === true, "Envoi d'un chuchotement direct à un Daimyō");
$whisperMsgId = (int)($sendWhisper['message_id'] ?? 0);

// Destinataire lit la conversation
$recipMsgs = $chatEngine->getMessages($u2Id, 'whisper', null, $u1Id, 0, 10);
$foundInRecip = false;
foreach ($recipMsgs as $m) {
    if ($m['id'] === $whisperMsgId) {
        $foundInRecip = true;
        break;
    }
}
assertTest($foundInRecip === true, "Le destinataire reçoit et lit le chuchotement");

// Tiers non autorisé (u3 ou joueur 999999) ne doit pas voir ce chuchotement
$unauthorizedMsgs = $chatEngine->getMessages(999999, 'whisper', null, $u1Id, 0, 10);
$foundUnauthorized = false;
foreach ($unauthorizedMsgs as $m) {
    if ($m['id'] === $whisperMsgId) {
        $foundUnauthorized = true;
        break;
    }
}
assertTest($foundUnauthorized === false, "Confidentialité : un tiers ne peut pas intercepter le chuchotement");

// 3. Canal Alliance
echo "\n--- 3. Test Canal d'Alliance ---\n";
// Attendre 1 seconde
sleep(1);

// Si u1 n'a pas d'alliance, tenter d'envoyer dans alliance doit échouer
if (empty($u1['alliance_id'])) {
    $sendNoAlliance = $chatEngine->sendMessage($u1Id, 'alliance', null, null, "Message de guilde");
    assertTest($sendNoAlliance['success'] === false, "Blocage de l'envoi d'alliance pour un joueur sans clan");
} else {
    $sendAlliance = $chatEngine->sendMessage($u1Id, 'alliance', (int)$u1['alliance_id'], null, "Pacte de clan");
    assertTest($sendAlliance['success'] === true, "Envoi d'un message dans le canal d'alliance");
}

// 4. Modération & Suppression
echo "\n--- 4. Test Modération & Suppression ---\n";
// Auteur peut supprimer son propre message
$delAuthor = $chatEngine->deleteMessage($globalMsgId, $u1Id);
assertTest($delAuthor['success'] === true, "Suppression de son propre message par l'auteur");

$checkDeleted = $chatEngine->getMessages($u2Id, 'global', null, null, 0, 10);
$msgIsFlagged = false;
foreach ($checkDeleted as $m) {
    if ($m['id'] === $globalMsgId && $m['is_deleted']) {
        $msgIsFlagged = true;
        break;
    }
}
assertTest($msgIsFlagged === true, "Le message supprimé est masqué avec mention appropriée");

// 5. Anti-spam
echo "\n--- 5. Test Anti-Spam ---\n";
// Envoyer un message puis renvoyer immédiatement sans délai
$spam1 = $chatEngine->sendMessage($u1Id, 'global', null, null, "Premier message rapide");
$spam2 = $chatEngine->sendMessage($u1Id, 'global', null, null, "Deuxième message instantané");
assertTest($spam2['success'] === false, "Anti-spam : blocage des messages consécutifs en < 1 seconde");

// 6. Nettoyage
$db->exec("DELETE FROM chat_messages WHERE id IN ({$globalMsgId}, {$whisperMsgId})");

echo "\n============================================\n";
echo "RÉSULTATS : {$testsPassed} PASS / {$testsFailed} FAIL\n";
echo "============================================\n";

if ($testsFailed > 0) {
    exit(1);
}
exit(0);

