<?php
/**
 * API REST pour la gestion des Événements Hebdomadaires Narratifs (OpenShogun)
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/WeeklyEventEngine.php';
require_once __DIR__ . '/../core/GameStudioEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Veuillez vous connecter pour participer aux événements.']);
    exit;
}

$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

if (!$user || !$planet) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Utilisateur ou fief introuvable.']);
    exit;
}

$userId = (int)$user['id'];
$planetId = (int)$planet['id'];
$eventEngine = new WeeklyEventEngine();
$studioEngine = new GameStudioEngine();
$isStudioAuthorized = $auth->isAdmin() || $studioEngine->isAuthorized($userId);

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_active';

try {
    switch ($action) {
        // Récupérer l'événement actif avec la progression en temps réel du joueur
        case 'get_active':
            $event = $eventEngine->getActiveEvent($userId, $planetId);
            if (!$event) {
                echo json_encode(['success' => true, 'has_event' => false, 'event' => null]);
                exit;
            }
            echo json_encode([
                'success'   => true,
                'has_event' => true,
                'event'     => $event
            ]);
            break;

        // Réclamer la récompense d'un acte narratif achevé
        case 'claim_task':
            $taskId = (int)($_POST['task_id'] ?? $_GET['task_id'] ?? 0);
            if ($taskId <= 0) {
                throw new Exception("Identifiant d'acte manquant.");
            }
            $res = $eventEngine->claimTaskReward($userId, $planetId, $taskId);
            if (!$res['success']) {
                throw new Exception($res['error'] ?? 'Échec de réclamation.');
            }
            // Récupérer l'état rafraîchi de l'événement
            $event = $eventEngine->getActiveEvent($userId, $planetId);
            echo json_encode([
                'success' => true,
                'message' => $res['message'],
                'reward'  => $res['reward'],
                'event'   => $event
            ]);
            break;

        // Réclamer la récompense finale de clôture
        case 'claim_final':
            $eventId = (int)($_POST['event_id'] ?? $_GET['event_id'] ?? 0);
            if ($eventId <= 0) {
                throw new Exception("Identifiant d'événement manquant.");
            }
            $res = $eventEngine->claimFinalReward($userId, $planetId, $eventId);
            if (!$res['success']) {
                throw new Exception($res['error'] ?? 'Échec de réclamation finale.');
            }
            $event = $eventEngine->getActiveEvent($userId, $planetId);
            echo json_encode([
                'success' => true,
                'message' => $res['message'],
                'rewards' => $res['rewards'],
                'event'   => $event
            ]);
            break;

        // =====================================================================
        // ACTIONS D'ADMINISTRATION / GAME STUDIO
        // =====================================================================
        case 'admin_save_event':
            if (!$isStudioAuthorized) {
                http_response_code(403);
                throw new Exception("Accès refusé. Privilèges de Game Studio requis.");
            }
            $savedId = $eventEngine->saveEvent($_POST);
            echo json_encode([
                'success'  => true,
                'message'  => "Chronique enregistrée avec succès !",
                'event_id' => $savedId
            ]);
            break;

        case 'admin_sync_current_week':
            if (!$isStudioAuthorized) {
                http_response_code(403);
                throw new Exception("Accès refusé. Privilèges de Game Studio requis.");
            }
            $synced = $eventEngine->syncCurrentWeekEvent();
            if (!$synced) {
                throw new Exception("Aucune chronique trouvée pour la semaine en cours.");
            }
            echo json_encode([
                'success' => true,
                'message' => "Chronique synchronisée avec succès pour " . ($synced['week_label'] ?? 'la semaine') . " : " . $synced['title'],
                'event'   => $synced
            ]);
            break;

        case 'admin_resync_all_dates':
            if (!$isStudioAuthorized) {
                http_response_code(403);
                throw new Exception("Accès refusé. Privilèges de Game Studio requis.");
            }
            $year = !empty($_POST['year']) ? (int)$_POST['year'] : null;
            $count = $eventEngine->resyncAllWeekDates($year);
            echo json_encode([
                'success' => true,
                'message' => "$count chroniques recalibrées du lundi au dimanche.",
                'count'   => $count
            ]);
            break;

        case 'admin_set_active':
            if (!$isStudioAuthorized) {
                http_response_code(403);
                throw new Exception("Accès refusé. Privilèges de Game Studio requis.");
            }
            $eventId = (int)($_POST['event_id'] ?? 0);
            if ($eventId <= 0) throw new Exception("ID d'événement manquant.");
            $ok = $eventEngine->setActiveEvent($eventId);
            echo json_encode([
                'success' => $ok,
                'message' => $ok ? "Événement activé avec succès !" : "Erreur d'activation."
            ]);
            break;

        case 'admin_delete_event':
            if (!$isStudioAuthorized) {
                http_response_code(403);
                throw new Exception("Accès refusé. Privilèges de Game Studio requis.");
            }
            $eventId = (int)($_POST['event_id'] ?? 0);
            if ($eventId <= 0) throw new Exception("ID d'événement manquant.");
            $ok = $eventEngine->deleteEvent($eventId);
            echo json_encode([
                'success' => $ok,
                'message' => $ok ? "Chronique supprimée." : "Erreur de suppression."
            ]);
            break;

        case 'admin_save_task':
            if (!$isStudioAuthorized) {
                http_response_code(403);
                throw new Exception("Accès refusé. Privilèges de Game Studio requis.");
            }
            $savedTaskId = $eventEngine->saveTask($_POST);
            echo json_encode([
                'success' => true,
                'message' => "Acte narratif enregistré avec succès !",
                'task_id' => $savedTaskId
            ]);
            break;

        case 'admin_delete_task':
            if (!$isStudioAuthorized) {
                http_response_code(403);
                throw new Exception("Accès refusé. Privilèges de Game Studio requis.");
            }
            $taskId = (int)($_POST['task_id'] ?? 0);
            if ($taskId <= 0) throw new Exception("ID de tâche manquant.");
            $ok = $eventEngine->deleteTask($taskId);
            echo json_encode([
                'success' => $ok,
                'message' => $ok ? "Acte narratif supprimé." : "Erreur de suppression."
            ]);
            break;

        case 'admin_get_event':
            if (!$isStudioAuthorized) {
                http_response_code(403);
                throw new Exception("Accès refusé. Privilèges de Game Studio requis.");
            }
            $eventId = (int)($_GET['event_id'] ?? 0);
            $eventDetails = $eventEngine->getEventDetails($eventId);
            if (!$eventDetails) throw new Exception("Événement introuvable.");
            echo json_encode([
                'success' => true,
                'event'   => $eventDetails
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Action non reconnue : '$action'"]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}

