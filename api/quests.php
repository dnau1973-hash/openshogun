<?php
/**
 * API REST pour le Système de Didacticiel Féodal & Quêtes (OpenShogun)
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/QuestEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Veuillez vous connecter pour consulter vos quêtes de Daimyō.']);
    exit;
}

$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

if (!$user || !$planet) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Domaine ou profil introuvable.']);
    exit;
}

$userId = (int)$user['id'];
$planetId = (int)$planet['id'];
$questEngine = new QuestEngine();

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_status';

try {
    switch ($action) {
        // Obtenir le statut complet de toutes les quêtes et la quête active
        case 'get_status':
            $status = $questEngine->getPlayerQuestsStatus($userId, $planetId);
            echo json_encode(['success' => true, 'data' => $status]);
            break;

        // Réclamer la récompense de la quête achevée
        case 'claim':
            $questKey = trim($_POST['quest_key'] ?? $_GET['quest_key'] ?? '');
            if (empty($questKey)) {
                throw new Exception("Identifiant de quête manquant.");
            }
            $result = $questEngine->claimReward($userId, $planetId, $questKey);
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Impossible de réclamer la récompense.');
            }
            echo json_encode($result);
            break;

        // Enregistrer une action joueur (ex: visite de la carte)
        case 'record_action':
            $actionKey = trim($_POST['action_key'] ?? $_GET['action_key'] ?? '');
            if (!empty($actionKey)) {
                $questEngine->recordAction($userId, $actionKey);
            }
            $status = $questEngine->getPlayerQuestsStatus($userId, $planetId);
            echo json_encode(['success' => true, 'data' => $status]);
            break;

        default:
            throw new Exception("Action inconnue.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
