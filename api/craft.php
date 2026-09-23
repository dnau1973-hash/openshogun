<?php
/**
 * API Endpoint : Transformation & Raffinage de Riz en Saké et Farine de Riz
 * OpenShogun - Meunerie & Brasserie Sakagura
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

$action = $_POST['action'] ?? 'craft_rice';
$product = trim($_POST['product'] ?? '');
$amount = (float)($_POST['amount'] ?? 0);

if ($action !== 'craft_rice') {
    echo json_encode(['success' => false, 'error' => 'Action inconnue.']);
    exit;
}

try {
    $planetEngine = new PlanetEngine();
    $result = $planetEngine->craftRiceProduct((int)$planet['id'], $product, $amount);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

