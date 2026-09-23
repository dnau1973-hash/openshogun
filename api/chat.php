<?php
/**
 * API REST JSON : Chat Féodal & Messagerie Instantanée OpenShogun
 * 
 * Actions disponibles :
 * - fetch : Récupère les messages (avec filtrage par last_id pour polling)
 * - send : Envoie un message dans un canal
 * - conversations : Récupère les conversations privées récentes
 * - delete : Supprime un message (auteur ou staff)
 * - online_users : Récupère les Daimyōs en ligne
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ChatEngine.php';

Auth::initSession();
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

$currentUser = Auth::getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Session expirée.']);
    exit;
}

$userId = (int)$currentUser['id'];
$chatEngine = new ChatEngine();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'fetch');

switch ($action) {
    case 'fetch':
        $channelType = $_GET['channel_type'] ?? ($_POST['channel_type'] ?? 'global');
        $targetId = isset($_GET['target_id']) ? (int)$_GET['target_id'] : null;
        $otherUserId = isset($_GET['other_user_id']) ? (int)$_GET['other_user_id'] : null;
        $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 60;

        $messages = $chatEngine->getMessages($userId, $channelType, $targetId, $otherUserId, $lastId, $limit);
        $maxId = $lastId;
        foreach ($messages as $m) {
            if ($m['id'] > $maxId) {
                $maxId = $m['id'];
            }
        }

        echo json_encode([
            'success' => true,
            'channel_type' => $channelType,
            'messages' => $messages,
            'last_id' => $maxId,
            'count' => count($messages)
        ]);
        break;

    case 'send':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
            exit;
        }

        $channelType = $_POST['channel_type'] ?? 'global';
        $targetId = !empty($_POST['target_id']) ? (int)$_POST['target_id'] : null;
        $recipientId = !empty($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : null;
        $content = $_POST['message'] ?? '';

        $result = $chatEngine->sendMessage($userId, $channelType, $targetId, $recipientId, $content);
        if (!$result['success']) {
            http_response_code(400);
        }
        echo json_encode($result);
        break;

    case 'conversations':
        $conversations = $chatEngine->getRecentConversations($userId);
        echo json_encode([
            'success' => true,
            'conversations' => $conversations
        ]);
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
            exit;
        }

        $messageId = (int)($_POST['message_id'] ?? 0);
        if ($messageId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de message invalide.']);
            exit;
        }

        $res = $chatEngine->deleteMessage($messageId, $userId);
        if (!$res['success']) {
            http_response_code(403);
        }
        echo json_encode($res);
        break;

    case 'online_users':
        $minutes = isset($_GET['minutes']) ? max(1, min(60, (int)$_GET['minutes'])) : 5;
        $onlineUsers = $chatEngine->getOnlineChatters($minutes);
        echo json_encode([
            'success' => true,
            'users' => $onlineUsers,
            'total' => count($onlineUsers)
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Action inconnue.']);
        break;
}

