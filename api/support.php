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
                'ticket'  => $ticket
            ]);
            break;

        // 4. Récupère les données d'un ticket pour pré-remplir le formulaire d'édition
        //    GET /api/support.php?action=get_ticket_for_edit&ticket_id=XX
        case 'get_ticket_for_edit':
            $ticketId = (int)($_GET['ticket_id'] ?? 0);
            if ($ticketId <= 0) {
                throw new InvalidArgumentException("Identifiant de ticket invalide.");
            }
            // getTicketForEdit() lève RuntimeException si accès refusé ou statut non-pending
            $ticket = $supportEngine->getTicketForEdit($ticketId, $userId);
            echo json_encode([
                'success' => true,
                'ticket'  => $ticket,
                'csrf'    => Auth::csrfToken(), // fournit le jeton pour le formulaire
            ]);
            break;

        // 5. Modification d'un ticket par son auteur (statut pending uniquement)
        //    POST /api/support.php  action=update_ticket
        case 'update_ticket':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new RuntimeException("Méthode HTTP invalide.", 405);
            }

            // Validation CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!Auth::verifyCsrf($csrfToken)) {
                http_response_code(403);
                throw new RuntimeException("Jeton de sécurité invalide ou expiré. Rechargez la page et réessayez.", 403);
            }

            $ticketId   = (int)($_POST['ticket_id'] ?? 0);
            $title      = $_POST['title']       ?? '';
            $description = $_POST['description'] ?? '';
            $type       = $_POST['type']        ?? 'bug';
            $category   = $_POST['category']    ?? 'other';
            $severity   = $_POST['severity']    ?? 'medium';

            if ($ticketId <= 0) {
                throw new InvalidArgumentException("Identifiant de ticket invalide.");
            }

            $updated = $supportEngine->updateTicketByAuthor(
                $ticketId,
                $userId,
                $title,
                $description,
                $type,
                $category,
                $severity
            );

            echo json_encode([
                'success' => true,
                'message' => "Votre demande #$ticketId a été modifiée avec succès.",
                'ticket'  => $updated,
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

