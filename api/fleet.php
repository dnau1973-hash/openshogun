<?php
/**
 * API Endpoint: Envoi de mission spatiale
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/FleetEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();
if (!$planet) {
    echo json_encode(['success' => false, 'error' => 'Aucune planète active']);
    exit;
}

$fleetEngine = new FleetEngine();

$targetPlanetId = !empty($_POST['target_planet_id']) ? (int)$_POST['target_planet_id'] : null;
$targetOasisId = !empty($_POST['target_oasis_id']) ? (int)$_POST['target_oasis_id'] : null;
$missionType = $_POST['mission_type'] ?? 'raid';
$fleet = $_POST['fleet'] ?? [];
$cargo = [
    'metal' => (int)($_POST['cargo_metal'] ?? 0),
    'crystal' => (int)($_POST['cargo_crystal'] ?? 0),
    'deuterium' => (int)($_POST['cargo_deuterium'] ?? 0)
];

try {
    $result = $fleetEngine->dispatchMission(
        (int)$user['id'], 
        (int)$planet['id'], 
        $targetPlanetId, 
        $missionType, 
        $fleet, 
        $cargo, 
        $targetOasisId
    );
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

