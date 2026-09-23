<?php
/**
 * API REST pour le Système d'Alliances Féodales OpenShogun
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/AllianceEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Veuillez vous connecter pour accéder aux traités féodaux.']);
    exit;
}

$currentUserId = (int)Auth::id();
$allianceEngine = new AllianceEngine();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        // 1. Fonder une nouvelle alliance
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.");
            }
            $name = (string)($_POST['name'] ?? '');
            $tag = (string)($_POST['tag'] ?? '');
            $desc = (string)($_POST['description'] ?? '');

            $result = $allianceEngine->createAlliance($currentUserId, $name, $tag, $desc);
            echo json_encode($result);
            break;

        // 2. Inviter un Daimyō
        case 'invite':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.");
            }
            $targetUsername = (string)($_POST['username'] ?? '');
            $message = (string)($_POST['message'] ?? '');

            $result = $allianceEngine->inviteUser($currentUserId, $targetUsername, $message);
            echo json_encode($result);
            break;

        // 3. Annuler une invitation envoyée
        case 'cancel_invite':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.");
            }
            $invId = (int)($_POST['invitation_id'] ?? 0);
            $result = $allianceEngine->cancelInvitation($currentUserId, $invId);
            echo json_encode($result);
            break;

        // 4. Répondre à une invitation reçue (Accepter ou Décliner)
        case 'respond_invite':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.");
            }
            $invId = (int)($_POST['invitation_id'] ?? 0);
            $decision = (string)($_POST['decision'] ?? 'accept');

            $result = $allianceEngine->respondInvitation($currentUserId, $invId, $decision);
            echo json_encode($result);
            break;

        // 5. Exclure un membre (réservé au Chef)
        case 'kick':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.");
            }
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            $result = $allianceEngine->kickMember($currentUserId, $targetUserId);
            echo json_encode($result);
            break;

        // 6. Quitter son alliance
        case 'leave':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.");
            }
            $result = $allianceEngine->leaveAlliance($currentUserId);
            echo json_encode($result);
            break;

        // 7. Céder le titre de Chef Suprême
        case 'transfer':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.");
            }
            $newLeaderId = (int)($_POST['new_leader_id'] ?? 0);
            $result = $allianceEngine->transferLeadership($currentUserId, $newLeaderId);
            echo json_encode($result);
            break;

        // 8. Dissoudre l'alliance féodale
        case 'disband':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.");
            }
            $result = $allianceEngine->disbandAlliance($currentUserId);
            echo json_encode($result);
            break;

        // 9. Mettre à jour la charte de l'alliance
        case 'update_desc':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.");
            }
            $desc = (string)($_POST['description'] ?? '');
            $result = $allianceEngine->updateAllianceDescription($currentUserId, $desc);
            echo json_encode($result);
            break;

        // 10. Rechercher des alliances
        case 'search':
            $q = (string)($_GET['q'] ?? '');
            $results = $allianceEngine->searchAlliances($q);
            echo json_encode(['success' => true, 'alliances' => $results]);
            break;

        // 11. Récupérer les détails d'une alliance
        case 'get_details':
            $allianceId = (int)($_GET['alliance_id'] ?? 0);
            $alliance = $allianceEngine->getAlliance($allianceId);
            if (!$alliance) {
                throw new Exception("Alliance introuvable.");
            }
            $members = $allianceEngine->getAllianceMembers($allianceId);
            echo json_encode(['success' => true, 'alliance' => $alliance, 'members' => $members]);
            break;

        default:
            throw new Exception("Action d'alliance non reconnue.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

