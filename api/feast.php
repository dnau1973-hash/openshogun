<?php
/**
 * API Endpoint : Célébrations & Banquets Féodaux du Tenshu (Saké)
 * OpenShogun - Tenshu du Daimyō
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/PlanetEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

$planet = $auth->getCurrentPlanet();
if (!$planet) {
    echo json_encode(['success' => false, 'error' => 'Aucun fief actif trouvé.']);
    exit;
}

$action = $_POST['action'] ?? 'start_feast';
$feastType = trim($_POST['feast_type'] ?? '');

if ($action !== 'start_feast') {
    echo json_encode(['success' => false, 'error' => 'Action inconnue.']);
    exit;
}

try {
    $planetEngine = new PlanetEngine();
    $result = $planetEngine->startFeast((int)$planet['id'], $feastType);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
