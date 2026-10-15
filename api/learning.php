<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/WisdomLearningEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

$user = Auth::getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable.']);
    exit;
}

$userId = (int)$user['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'get_advice';

try {
    switch ($action) {
        // Obtenir le conseil contextuel du Maître de la Sagesse
        case 'get_advice':
            $planetId = (int)($_SESSION['current_planet_id'] ?? 0);
            $planetEngine = new PlanetEngine();
            $planet = null;
            if ($planetId > 0) {
                $planet = $planetEngine->getPlanet($planetId);
            }
            if (!$planet) {
                $planets = $planetEngine->getUserPlanets($userId);
                $planet = $planets[0] ?? null;
            }

            if (!$planet) {
                echo json_encode(['success' => true, 'has_advice' => false]);
                exit;
            }

            // Permettre la réouverture manuelle via ?manual=1
            $isManual = !empty($_GET['manual']) || !empty($_POST['manual']);
            $advice = WisdomLearningEngine::evaluateWisdom($planet, $user, $isManual);

            echo json_encode([
                'success' => true,
                'learning_mode_enabled' => ((int)($user['learning_mode'] ?? 1) === 1),
                'has_advice' => ($advice !== null),
                'advice' => $advice
            ]);
            break;

        // Acquitter un jalon pour qu'il ne s'affiche plus automatiquement
        case 'dismiss_advice':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode de requête invalide.");
            }
            $milestoneKey = trim((string)($_POST['milestone_key'] ?? ''));
            if (empty($milestoneKey)) {
                throw new Exception("Identifiant de jalon manquant.");
            }

            $success = WisdomLearningEngine::markMilestoneSeen($userId, $milestoneKey);
            echo json_encode([
                'success' => $success,
                'milestone_key' => $milestoneKey,
                'message' => "Enseignement assimilé avec sagesse."
            ]);
            break;

        // Désactiver définitivement le mode apprentissage depuis la modale
        case 'disable_learning_mode':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode de requête invalide.");
            }
            require_once __DIR__ . '/../core/HonorEngine.php';
            $honorEngine = new HonorEngine();
            $success = $honorEngine->updateLearningMode($userId, false);
            echo json_encode([
                'success' => $success,
                'learning_mode' => 0,
                'message' => "Mode apprentissage désactivé. Vous pouvez le réactiver à tout moment sur votre page Poster."
            ]);
            break;

        default:
            throw new Exception("Action inconnue : {$action}");
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

