<?php
/**
 * API Endpoint: Gestion du Sceau Impérial (Privilège du Shōgun / Travian Plus)
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
        case 'status':
            $status = $sealEngine->getSealStatus($userId);
            echo json_encode(['success' => true, 'status' => $status]);
            break;

        case 'activate':
            $days = (int)($_POST['days'] ?? 7);
            $res = $sealEngine->activateSeal($userId, $days);
            echo json_encode($res);
            break;

        case 'claim_daily':
            $res = $sealEngine->claimDailyBonus($userId);
            echo json_encode($res);
            break;

        case 'npc_exchange':
            $targetPlanetId = !empty($_POST['planet_id']) ? (int)$_POST['planet_id'] : ($planet ? (int)$planet['id'] : 0);
            $wood = (int)($_POST['wood'] ?? 0);
            $stone = (int)($_POST['stone'] ?? 0);
            $rice = (int)($_POST['rice'] ?? 0);

            $res = $sealEngine->npcExchange($userId, $targetPlanetId, $wood, $stone, $rice);
            echo json_encode($res);
            break;

        case 'toggle_evasion':
            $targetPlanetId = !empty($_POST['planet_id']) ? (int)$_POST['planet_id'] : ($planet ? (int)$planet['id'] : 0);
            $newVal = $sealEngine->toggleTacticalEvasion($userId, $targetPlanetId);
            echo json_encode([
                'success' => true,
                'tactical_evasion' => $newVal,
                'message' => $newVal 
                    ? "⛩️ Ordre de Repli Tactique activé : votre garnison évitera l'affrontement direct en cas d'assaut ennemi." 
                    : "🛡️ Ordre de Garnison rétabli : vos guerriers combattront pied à pied sur les remparts."
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => "Action '{$action}' inconnue."]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
