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
$amount = (float)($_POST['rice_amount'] ?? $_POST['amount'] ?? 0);
$craftId = (int)($_POST['craft_id'] ?? 0);

try {
    $planetEngine = new PlanetEngine();

    if ($action === 'craft_wood' || $action === 'craft_beams' || ($action === 'start_craft' && $product === 'wooden_beams')) {
        $woodAmount = (float)($_POST['wood_amount'] ?? $_POST['amount'] ?? 0);
        $result = $planetEngine->craftWoodProduct((int)$planet['id'], 'wooden_beams', $woodAmount);
        echo json_encode($result);
    } elseif ($action === 'craft_rice' || $action === 'start_craft') {
        $result = $planetEngine->craftRiceProduct((int)$planet['id'], $product, $amount);
        echo json_encode($result);
    } elseif ($action === 'cancel_craft' || $action === 'cancel_wood_craft') {
        $result = $planetEngine->cancelCraft((int)$planet['id'], $craftId);
        echo json_encode($result);
    } elseif ($action === 'get_queue') {
        $bType = $_GET['building_type'] ?? $_POST['building_type'] ?? null;
        $planetEngine->processCraftQueue((int)$planet['id']);
        $queue = $planetEngine->getCraftQueue((int)$planet['id'], $bType);
        echo json_encode(['success' => true, 'queue' => $queue]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Action inconnue.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

