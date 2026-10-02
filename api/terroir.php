<?php
/**
 * API Endpoint: Amélioration des slots de terroir (TerroirEngine)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/TerroirEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

$planet = $auth->getCurrentPlanet();
if (!$planet) {
    echo json_encode(['success' => false, 'error' => 'Aucun domaine actif trouvé.']);
    exit;
}

$action = $_POST['action'] ?? 'upgrade';
$terroirEngine = new TerroirEngine();

try {
    if ($action === 'upgrade') {
        $resourceType = trim((string)($_POST['resource_type'] ?? ''));
        $slotIndex = (int)($_POST['slot_index'] ?? 0);

        if (!$resourceType || $slotIndex <= 0) {
            throw new Exception("Paramètres de parcelle manquants.");
        }

        $res = $terroirEngine->upgradeSlot((int)$planet['id'], $resourceType, $slotIndex);
        echo json_encode($res);
    } else {
        throw new Exception("Action non reconnue.");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
