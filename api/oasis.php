<?php
/**
 * API Endpoint: Gestion des Oasis Naturelles (Annexion, Abandon, Détails)
 * OpenShogun
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/OasisEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();
if (!$planet) {
    echo json_encode(['success' => false, 'error' => 'Aucun fief actif']);
    exit;
}

$oasisEngine = new OasisEngine();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$oasisId = (int)($_POST['oasis_id'] ?? $_GET['oasis_id'] ?? 0);

if ($oasisId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Identifiant d\'oasis invalide']);
    exit;
}

try {
    switch ($action) {
        case 'annex':
            $result = $oasisEngine->annexOasis($oasisId, (int)$planet['id']);
            echo json_encode($result);
            break;

        case 'abandon':
            $result = $oasisEngine->abandonOasis($oasisId, (int)$planet['id']);
            echo json_encode($result);
            break;

        case 'details':
            $oasis = $oasisEngine->getOasisById($oasisId);
            if (!$oasis) {
                echo json_encode(['success' => false, 'error' => 'Oasis introuvable']);
                exit;
            }
            $isPacified = $oasisEngine->isOasisPacified($oasisId);
            echo json_encode([
                'success' => true,
                'oasis' => $oasis,
                'is_pacified' => $isPacified,
                'is_owner' => ($oasis['owner_planet_id'] == $planet['id'])
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
