<?php
/**
 * API Endpoint: Récupération des secteurs de la carte galactique
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/GalaxyEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$centerX = (int)($_GET['x'] ?? 0);
$centerY = (int)($_GET['y'] ?? 0);
$radius = min(10, max(2, (int)($_GET['radius'] ?? 4)));

$galaxyEngine = new GalaxyEngine();
$data = $galaxyEngine->getSectorMap($centerX, $centerY, $radius);

echo json_encode([
    'success' => true,
    'data' => $data
]);

