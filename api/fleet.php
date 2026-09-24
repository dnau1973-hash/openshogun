<?php
/**
 * API Endpoint: Envoi de mission spatiale
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/FleetEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();
if (!$planet) {
    echo json_encode(['success' => false, 'error' => 'Aucune planète active']);
    exit;
}

$fleetEngine = new FleetEngine();

$targetPlanetId = !empty($_POST['target_planet_id']) ? (int)$_POST['target_planet_id'] : null;
$targetOasisId = !empty($_POST['target_oasis_id']) ? (int)$_POST['target_oasis_id'] : null;

// Résolution de destination par coordonnées féodales si targetPlanetId absent
if (!$targetPlanetId && !$targetOasisId && isset($_POST['target_x'], $_POST['target_y']) && $_POST['target_x'] !== '' && $_POST['target_y'] !== '') {
    $db = Database::getConnection();
    $stCoord = $db->prepare("SELECT id FROM planets WHERE coord_x = ? AND coord_y = ? LIMIT 1");
    $stCoord->execute([(int)$_POST['target_x'], (int)$_POST['target_y']]);
    $targetPlanetId = $stCoord->fetchColumn() ?: null;
    if (!$targetPlanetId) {
        // Vérifier si c'est une oasis aux coordonnées indiquées
        $stOasis = $db->prepare("SELECT id FROM oases WHERE coord_x = ? AND coord_y = ? LIMIT 1");
        $stOasis->execute([(int)$_POST['target_x'], (int)$_POST['target_y']]);
        $targetOasisId = $stOasis->fetchColumn() ?: null;
    }
}

$missionType = $_POST['mission_type'] ?? 'raid';
$fleet = $_POST['fleet'] ?? [];
$cargo = [
    'metal' => (int)($_POST['cargo_metal'] ?? 0),
    'crystal' => (int)($_POST['cargo_crystal'] ?? 0),
    'deuterium' => (int)($_POST['cargo_deuterium'] ?? 0)
];

// Pour une mission de transport (Marché ou Convoi), si aucune flotte manuelle n'a été spécifiée,
// allouer automatiquement les transporteurs disponibles nécessaires
if ($missionType === 'transport' && empty($fleet)) {
    $totalCargo = $cargo['metal'] + $cargo['crystal'] + $cargo['deuterium'];
    if ($totalCargo > 0) {
        $db = Database::getConnection();
        $stShips = $db->prepare("SELECT ship_code, count FROM planet_ships WHERE planet_id = ? AND ship_code IN ('transporter_light', 'transporter_heavy')");
        $stShips->execute([(int)$planet['id']]);
        $ships = $stShips->fetchAll(PDO::FETCH_KEY_PAIR);
        $availLight = (int)($ships['transporter_light'] ?? 0);
        $availHeavy = (int)($ships['transporter_heavy'] ?? 0);
        $totalCap = ($availLight * 5000) + ($availHeavy * 25000);

        if ($totalCap < $totalCargo) {
            echo json_encode(['success' => false, 'error' => "Capacité d'emport insuffisante sur le fief (" . number_format($totalCap) . " disponible, " . number_format($totalCargo) . " requis). Entraînez des Chariots de transport."]);
            exit;
        }

        // Calcul optimal
        $needHeavy = min($availHeavy, (int)floor($totalCargo / 25000));
        $rem = $totalCargo - ($needHeavy * 25000);
        if ($rem > 0) {
            if ($availLight * 5000 >= $rem) {
                $needLight = (int)ceil($rem / 5000);
            } elseif ($availHeavy > $needHeavy) {
                $needHeavy++;
                $needLight = 0;
            } else {
                $needLight = min($availLight, (int)ceil($rem / 5000));
            }
        } else {
            $needLight = 0;
        }

        if ($needLight > 0) $fleet['transporter_light'] = $needLight;
        if ($needHeavy > 0) $fleet['transporter_heavy'] = $needHeavy;
    }
}

$hasHero = !empty($_POST['has_hero']);

try {
    $result = $fleetEngine->dispatchMission(
        (int)$user['id'], 
        (int)$planet['id'], 
        $targetPlanetId, 
        $missionType, 
        $fleet, 
        $cargo, 
        $targetOasisId,
        $hasHero
    );
    if (!empty($result['success'])) {
        require_once __DIR__ . '/../core/QuestEngine.php';
        $questEngine = new QuestEngine();
        $questEngine->recordAction((int)$user['id'], 'send_fleet');
    }
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

