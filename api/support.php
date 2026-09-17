<?php
/**
 * API REST de Support, Signalement de Bugs & Suggestions (OpenShogun)
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/SupportEngine.php';

$auth = new Auth();

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();
$userId = (int)$user['id'];
$planetId = $planet ? (int)$planet['id'] : null;

$supportEngine = new SupportEngine();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        // 1. Création d'un ticket par un joueur
        case 'create_ticket':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode HTTP invalide.");
            }

            $type = $_POST['type'] ?? 'bug';
            $category = $_POST['category'] ?? 'other';
            $severity = $_POST['severity'] ?? 'medium';
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';

            $ticket = $supportEngine->createTicket(
                $userId,
                $type,
                $category,
                $title,
                $description,
                $severity,
                $planetId
            );

            $typeLabel = ($type === 'bug') ? 'Le dysfonctionnement' : 'La suggestion';
            echo json_encode([
                'success' => true,
                'message' => "{$typeLabel} a été transmis avec succès aux équipes de développement !",
                'ticket' => $ticket
            ]);
            break;

        // 2. Liste des tickets du joueur connecté
        case 'get_my_tickets':
            $tickets = $supportEngine->getUserTickets($userId);
            echo json_encode([
                'success' => true,
                'tickets' => $tickets
            ]);
            break;

        // 3. Consultation d'un ticket individuel (admin ou propriétaire)
        case 'get_ticket':
            $ticketId = (int)($_GET['ticket_id'] ?? $_POST['ticket_id'] ?? 0);
            $ticket = $supportEngine->getTicketById($ticketId);

            if (!$ticket) {
                throw new Exception("Ticket introuvable.");
            }

            // Seul l'administrateur ou le joueur auteur du ticket peut le consulter
            if (!$auth->isAdmin() && (int)$ticket['user_id'] !== $userId) {
                http_response_code(403);
                throw new Exception("Accès interdit à ce ticket.");
            }

            echo json_encode([
                'success' => true,
                'ticket' => $ticket
            ]);
            break;

        // --- ACTIONS RÉSERVÉES AUX ADMINISTRATEURS ---
        case 'admin_get_tickets':
            if (!$auth->isAdmin()) {
                http_response_code(403);
                throw new Exception("Accès restreint à l'administration.");
            }

            $type = !empty($_GET['type']) ? $_GET['type'] : null;
            $status = !empty($_GET['status']) ? $_GET['status'] : null;
            $search = !empty($_GET['search']) ? trim($_GET['search']) : null;

            $tickets = $supportEngine->getAllTickets($type, $status, $search);
            $stats = $supportEngine->getStatistics();

            echo json_encode([
                'success' => true,
                'tickets' => $tickets,
                'stats' => $stats
            ]);
            break;

        case 'admin_update_ticket':
            if (!$auth->isAdmin()) {
                http_response_code(403);
                throw new Exception("Accès restreint à l'administration.");
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode HTTP invalide.");
            }

            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            $status = $_POST['status'] ?? 'pending';
            $adminResponse = $_POST['admin_response'] ?? null;
            $notifyUser = !empty($_POST['notify_user']) && ($_POST['notify_user'] === '1' || $_POST['notify_user'] === 'true');

            $res = $supportEngine->updateTicketStatus(
                $ticketId,
                $status,
                $adminResponse,
                $userId,
                $notifyUser
            );

            echo json_encode([
                'success' => true,
                'message' => "Le ticket #{$ticketId} a été mis à jour avec succès !",
                'ticket' => $res
            ]);
            break;

        case 'admin_delete_ticket':
            if (!$auth->isAdmin()) {
                http_response_code(403);
                throw new Exception("Accès restreint à l'administration.");
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode HTTP invalide.");
            }

            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            $success = $supportEngine->deleteTicket($ticketId);

            echo json_encode([
                'success' => $success,
                'message' => "Le ticket #{$ticketId} a été supprimé."
            ]);
            break;

        default:
            throw new Exception("Action non reconnue.");
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

