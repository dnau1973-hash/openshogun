<?php
/**
 * api/trade_route.php
 * API Endpoint : Gestion des Routes Commerciales Automatisées Féodales
 * Privilège exclusif du Sceau Impérial (Privilège du Shōgun)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ImperialSealEngine.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

$userId = (int)$user['id'];
$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
$sealEngine = new ImperialSealEngine();

try {
    switch ($action) {
        case 'get_routes':
            $routes = $sealEngine->getTradeRoutes($userId);
            $isSealActive = $sealEngine->isSealActive($userId);
            echo json_encode([
                'success' => true,
                'is_seal_active' => $isSealActive,
                'routes' => $routes
            ]);
            break;

        case 'create_route':
            $sourcePlanetId = (int)($_POST['source_planet_id'] ?? 0);
            $targetPlanetId = (int)($_POST['target_planet_id'] ?? 0);
            $wood = (int)($_POST['wood'] ?? 0);
            $stone = (int)($_POST['stone'] ?? 0);
            $rice = (int)($_POST['rice'] ?? 0);
            $intervalHours = (int)($_POST['interval_hours'] ?? 4);
            $transporterPref = trim($_POST['transporter_pref'] ?? 'auto');

            $res = $sealEngine->createTradeRoute(
                $userId,
                $sourcePlanetId,
                $targetPlanetId,
                $wood,
                $stone,
                $rice,
                $intervalHours,
                $transporterPref
            );
            echo json_encode($res);
            break;

        case 'toggle_route':
            $routeId = (int)($_POST['route_id'] ?? 0);
            $res = $sealEngine->toggleTradeRoute($userId, $routeId);
            echo json_encode($res);
            break;

        case 'delete_route':
            $routeId = (int)($_POST['route_id'] ?? 0);
            $res = $sealEngine->deleteTradeRoute($userId, $routeId);
            echo json_encode($res);
            break;

        case 'execute_route':
            $routeId = (int)($_POST['route_id'] ?? 0);
            // Vérifier que la route appartient bien au joueur
            $routes = $sealEngine->getTradeRoutes($userId);
            $found = false;
            foreach ($routes as $r) {
                if ((int)$r['id'] === $routeId) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                echo json_encode(['success' => false, 'error' => 'Route commerciale introuvable ou non autorisée.']);
                exit;
            }

            $res = $sealEngine->executeTradeRoute($routeId, true);
            echo json_encode($res);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action non reconnue.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
