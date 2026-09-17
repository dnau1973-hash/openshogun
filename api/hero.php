<?php
/**
 * API REST pour la gestion du Samouraï Héros Champion (OpenShogun)
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/HeroEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Veuillez vous connecter pour accéder à votre Samouraï.']);
    exit;
}

$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

if (!$user || !$planet) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Utilisateur ou domaine actif introuvable.']);
    exit;
}

$userId = (int)$user['id'];
$planetId = (int)$planet['id'];
$heroEngine = new HeroEngine();

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_hero';

try {
    switch ($action) {
        // Obtenir les informations complètes du héros
        case 'get_hero':
            $hero = $heroEngine->getHeroByUserId($userId);
            if (!$hero) {
                throw new Exception("Héros introuvable.");
            }
            $adventures = $heroEngine->getAdventures($userId);
            $inventory = $heroEngine->getInventory($userId);

            echo json_encode([
                'success' => true,
                'hero' => $hero,
                'adventures' => $adventures,
                'inventory' => $inventory
            ]);
            break;

        // Répartir les points d'attributs
        case 'allocate_points':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode POST requise.");
            }
            $addStrength = max(0, (int)($_POST['strength'] ?? 0));
            $addOffense = max(0, (int)($_POST['offense'] ?? 0));
            $addDefense = max(0, (int)($_POST['defense'] ?? 0));
            $addProd = max(0, (int)($_POST['production'] ?? 0));

            $result = $heroEngine->allocatePoints($userId, $addStrength, $addOffense, $addDefense, $addProd);
            echo json_encode($result);
            break;

        // Modifier le mode de production du domaine
        case 'set_production_type':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode POST requise.");
            }
            $type = trim($_POST['production_type'] ?? 'balanced');
            $result = $heroEngine->setProductionType($userId, $type);
            echo json_encode($result);
            break;

        // Partir en aventure féodale
        case 'start_adventure':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode POST requise.");
            }
            $adventureId = (int)($_POST['adventure_id'] ?? 0);
            if ($adventureId <= 0) {
                throw new Exception("Identifiant d'aventure invalide.");
            }
            $result = $heroEngine->startAdventure($userId, $adventureId);
            echo json_encode($result);
            break;

        // Lancer le rituel de résurrection
        case 'revive_hero':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode POST requise.");
            }
            $targetPlanetId = (int)($_POST['planet_id'] ?? $planetId);
            $result = $heroEngine->reviveHero($userId, $targetPlanetId);
            echo json_encode($result);
            break;

        // Équiper un objet
        case 'equip_item':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode POST requise.");
            }
            $itemId = (int)($_POST['item_id'] ?? 0);
            if ($itemId <= 0) {
                throw new Exception("Identifiant d'objet invalide.");
            }
            $result = $heroEngine->equipItem($userId, $itemId);
            echo json_encode($result);
            break;

        // Déséquiper un emplacement
        case 'unequip_item':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode POST requise.");
            }
            $slot = trim($_POST['slot'] ?? '');
            $result = $heroEngine->unequipSlot($userId, $slot);
            echo json_encode($result);
            break;

        default:
            throw new Exception("Action inconnue.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
