<?php
/**
 * API Endpoint: Lancement ou annulation de construction
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/BuildingEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$planet = $auth->getCurrentPlanet();
if (!$planet) {
    echo json_encode(['success' => false, 'error' => 'Aucune planète active']);
    exit;
}

$action = $_POST['action'] ?? 'upgrade';
$buildingEngine = new BuildingEngine();

try {
    if ($action === 'upgrade') {
        $category = $_POST['category'] ?? '';
        $targetId = $_POST['target_id'] ?? '';
        if (!in_array($category, ['field', 'building'])) {
            throw new Exception("Catégorie de construction invalide.");
        }
        $result = $buildingEngine->startUpgrade((int)$planet['id'], $category, $targetId);
        echo json_encode($result);
    } elseif ($action === 'cancel') {
        $queueId = (int)($_POST['queue_id'] ?? 0);
        $ok = $buildingEngine->cancelUpgrade((int)$planet['id'], $queueId);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Construction annulée, 80% remboursé.' : 'Impossible d\'annuler.']);
    } else {
        throw new Exception("Action non reconnue.");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

