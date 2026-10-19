<?php
/**
 * API REST pour le Dōjō Impérial sur la tuile [0 : 0]
 * (Tenchi no Seichi - 天地の聖地)
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ImperialDojoEngine.php';
require_once __DIR__ . '/../core/AllianceEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Veuillez vous connecter pour accéder au sanctuaire impérial.']);
    exit;
}

$currentUserId = (int)Auth::id();
$currentPlanet = $auth->getCurrentPlanet();
$dojoEngine = new ImperialDojoEngine();
$action = $_GET['action'] ?? $_POST['action'] ?? 'get_state';

try {
    switch ($action) {
        case 'get_state':
            $state = $dojoEngine->getDojoState();
            $garrison = $dojoEngine->getGarrison();
            $topContributors = !empty($state['controlling_alliance_id']) ? $dojoEngine->getTopContributors((int)$state['controlling_alliance_id']) : [];
            echo json_encode([
                'success'           => true,
                'state'             => $state,
                'garrison'          => $garrison,
                'top_contributors'  => $topContributors,
                'planet'            => [
                    'metal'             => (int)$currentPlanet['metal'],
                    'wood'              => (int)$currentPlanet['crystal'],
                    'granite'           => (int)$currentPlanet['deuterium'],
                    'rice'              => (int)($currentPlanet['rice'] ?? 0),
                    'timber_beams'      => (int)($currentPlanet['timber_beams'] ?? 0),
                    'tiles_kawara'      => (int)($currentPlanet['tiles_kawara'] ?? 0),
                    'tiles_onigawara'   => (int)($currentPlanet['tiles_onigawara'] ?? 0),
                    'koban_gold'        => (int)($currentPlanet['koban_gold'] ?? 0)
                ]
            ]);
            break;

        case 'claim':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.");
            }
            $user = $auth->getCurrentUser();
            if (empty($user['alliance_id'])) {
                throw new Exception("Vous devez appartenir à une alliance pour revendiquer le Dōjō Impérial.");
            }
            $res = $dojoEngine->claimDojoForAlliance((int)$user['alliance_id'], $currentUserId);
            echo json_encode($res);
            break;

        case 'contribute':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.");
            }
            $contributions = [
                'wood'      => max(0, (int)($_POST['wood'] ?? 0)),
                'granite'   => max(0, (int)($_POST['granite'] ?? 0)),
                'metal'     => max(0, (int)($_POST['metal'] ?? 0)),
                'rice'      => max(0, (int)($_POST['rice'] ?? 0)),
                'beams'     => max(0, (int)($_POST['beams'] ?? 0)),
                'tiles'     => max(0, (int)($_POST['tiles'] ?? 0)),
                'oni_tiles' => max(0, (int)($_POST['oni_tiles'] ?? 0)),
                'gold'      => max(0, (int)($_POST['gold'] ?? 0))
            ];
            $res = $dojoEngine->contributeResources($currentUserId, (int)$currentPlanet['id'], $contributions);
            echo json_encode($res);
            break;

        default:
            throw new Exception("Action inconnue : " . htmlspecialchars($action));
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}

