<?php
/**
 * API Endpoint: Amélioration et gestion des 9 parcelles rurales (RuralPlotEngine)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/RuralPlotEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

$planet = $auth->getCurrentPlanet();
if (!$planet) {
    echo json_encode(['success' => false, 'error' => 'Aucun domaine féodal actif trouvé.']);
    exit;
}

$action = $_POST['action'] ?? 'upgrade';
$ruralPlotEngine = new RuralPlotEngine();

try {
    if ($action === 'upgrade') {
        $structureType = trim((string)($_POST['structure_type'] ?? ''));

        // Tolérance sur les anciens noms éventuels
        if ($structureType === 'wood') $structureType = 'foret';
        if ($structureType === 'stone') $structureType = 'carriere';
        if ($structureType === 'clay') $structureType = 'fosse_argile';
        if ($structureType === 'rice') $structureType = 'riziere';
        if ($structureType === 'tea') $structureType = 'culture_the';
        if ($structureType === 'soybean') $structureType = 'champ_soja';
        if ($structureType === 'shrine') $structureType = 'sanctuaire_shinto';
        if ($structureType === 'housing') $structureType = 'village';

        if (!$structureType) {
            throw new Exception("Type de structure non spécifié.");
        }

        $res = $ruralPlotEngine->upgradePlot((int)$planet['id'], $structureType);
        echo json_encode($res);
    } elseif ($action === 'cancel') {
        require_once __DIR__ . '/../core/BuildingEngine.php';
        $queueId = (int)($_POST['queue_id'] ?? 0);
        if ($queueId <= 0) {
            throw new Exception("Identifiant de chantier manquant.");
        }
        $buildingEngine = new BuildingEngine();
        $ok = $buildingEngine->cancelUpgrade((int)$planet['id'], $queueId);
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Chantier annulé avec succès. 80% des ressources restituées.']);
        } else {
            throw new Exception("Impossible d'annuler ce chantier.");
        }
    } else {
        throw new Exception("Action non reconnue.");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

