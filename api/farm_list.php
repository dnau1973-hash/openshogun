<?php
/**
 * API Endpoint: Gestion du Carnet de Raids (Farm List)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ImperialSealEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Veuillez vous authentifier.']);
    exit;
}

$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();
$userId = (int)$user['id'];
$sealEngine = new ImperialSealEngine();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_lists':
            $sourcePlanetId = !empty($_GET['source_planet_id']) ? (int)$_GET['source_planet_id'] : null;
            $lists = $sealEngine->getFarmLists($userId, $sourcePlanetId);
            echo json_encode(['success' => true, 'lists' => $lists]);
            break;

        case 'create_list':
            $sourcePlanetId = !empty($_POST['source_planet_id']) ? (int)$_POST['source_planet_id'] : ($planet ? (int)$planet['id'] : 0);
            $name = $_POST['name'] ?? 'Tournée de Raids';
            $listId = $sealEngine->createFarmList($userId, $sourcePlanetId, $name);
            echo json_encode(['success' => true, 'list_id' => $listId, 'message' => "Liste de raids '{$name}' créée avec succès !"]);
            break;

        case 'delete_list':
            $listId = (int)($_POST['list_id'] ?? 0);
            $res = $sealEngine->deleteFarmList($userId, $listId);
            echo json_encode(['success' => $res, 'message' => 'Liste de raids supprimée.']);
            break;

        case 'add_entry':
            $listId = (int)($_POST['list_id'] ?? 0);
            $targetType = $_POST['target_type'] ?? 'planet';
            $targetId = (int)($_POST['target_id'] ?? 0);
            $targetName = $_POST['target_name'] ?? 'Cible Féodale';
            $x = (int)($_POST['coord_x'] ?? 0);
            $y = (int)($_POST['coord_y'] ?? 0);
            $fleetData = $_POST['fleet'] ?? [];
            if (is_string($fleetData)) {
                $fleetData = json_decode($fleetData, true) ?: [];
            }

            $res = $sealEngine->addFarmListEntry($userId, $listId, $targetType, $targetId, $targetName, $x, $y, $fleetData);
            echo json_encode($res);
            break;

        case 'delete_entry':
            $entryId = (int)($_POST['entry_id'] ?? 0);
            $res = $sealEngine->deleteFarmListEntry($userId, $entryId);
            echo json_encode(['success' => $res, 'message' => 'Cible retirée du carnet de raids.']);
            break;

        case 'run_entry':
            $entryId = (int)($_POST['entry_id'] ?? 0);
            $res = $sealEngine->executeRaidEntry($userId, $entryId);
            echo json_encode($res);
            break;

        case 'run_all':
            $listId = (int)($_POST['list_id'] ?? 0);
            $res = $sealEngine->executeFullFarmList($userId, $listId);
            echo json_encode($res);
            break;

        default:
            echo json_encode(['success' => false, 'error' => "Action '{$action}' inconnue."]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

