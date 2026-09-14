<?php
/**
 * API Endpoint : Messagerie Interstellaire
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/MessageEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

$user = $auth->getCurrentUser();
$messageEngine = new MessageEngine();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'send') {
        $receiver = $_POST['receiver'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $body = $_POST['body'] ?? '';

        $res = $messageEngine->sendMessage((int)$user['id'], $receiver, $subject, $body);
        echo json_encode($res);
    } elseif ($action === 'delete') {
        $messageId = (int)($_POST['message_id'] ?? 0);
        $ok = $messageEngine->deleteMessage($messageId, (int)$user['id']);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Message supprimé.' : 'Impossible de supprimer le message.']);
    } elseif ($action === 'get_unread') {
        $count = $messageEngine->getUnreadCount((int)$user['id']);
        echo json_encode(['success' => true, 'unread_count' => $count]);
    } else {
        throw new Exception("Action non reconnue.");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

