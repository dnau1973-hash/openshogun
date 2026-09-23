<?php
/**
 * API Endpoint : Gestion Planétaire & Fiefs Féodaux
 * OpenShogun - Renommage, Capitale, Changement de Fief, Recrutement de Colons au Tenshu
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/PlanetEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

$userId = Auth::id();
$currentPlanet = $auth->getCurrentPlanet();
if (!$currentPlanet) {
    echo json_encode(['success' => false, 'error' => 'Aucun fief actif trouvé.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$planetId = (int)($_POST['planet_id'] ?? $_GET['planet_id'] ?? $currentPlanet['id']);

try {
    $planetEngine = new PlanetEngine();

    if ($action === 'rename') {
        $newName = trim($_POST['name'] ?? '');
        $res = $planetEngine->renamePlanet($planetId, $userId, $newName);
        echo json_encode($res);
    } elseif ($action === 'set_capital') {
        $res = $planetEngine->setCapital($planetId, $userId);
        echo json_encode($res);
    } elseif ($action === 'switch') {
        $targetId = (int)($_POST['target_planet_id'] ?? $planetId);
        $auth->setCurrentPlanet($targetId);
        $switched = $auth->getCurrentPlanet();
        echo json_encode([
            'success' => true,
            'message' => "Fief actif changé vers « " . htmlspecialchars($switched['name']) . " ».",
            'planet' => $switched
        ]);
    } elseif ($action === 'train_colonizer') {
        $count = max(1, (int)($_POST['count'] ?? 1));
        $res = $planetEngine->trainColonizer($planetId, $userId, $count);
        echo json_encode($res);
    } elseif ($action === 'get_colonizer_status') {
        $res = $planetEngine->getTenshuColonizerStatus($planetId);
        echo json_encode(['success' => true, 'status' => $res]);
    } elseif ($action === 'list') {
        $planets = $planetEngine->getUserPlanets($userId);
        echo json_encode(['success' => true, 'planets' => $planets]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Action inconnue.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

