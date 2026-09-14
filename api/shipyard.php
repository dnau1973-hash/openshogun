<?php
/**
 * API Endpoint: Construction de vaisseaux spatiaux
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ShipyardEngine.php';

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

$shipyardEngine = new ShipyardEngine();
$shipCode = $_POST['ship_code'] ?? '';
$count = (int)($_POST['count'] ?? 0);

try {
    $result = $shipyardEngine->buildShips((int)$planet['id'], $shipCode, $count, $user['faction']);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

