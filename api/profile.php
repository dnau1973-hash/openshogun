<?php
/**
 * API REST pour la Fiche Joueur, Profil et Tableau d'Honneur
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/HonorEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Veuillez vous connecter pour accéder aux registres du Shogunat.']);
    exit;
}

$honorEngine = new HonorEngine();
$action = $_GET['action'] ?? $_POST['action'] ?? 'get_profile';

try {
    switch ($action) {
        // Consulter la fiche joueur d'un daimyō
        case 'get_profile':
            $userId = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? Auth::id());
            if ($userId <= 0) {
                throw new Exception("Identifiant de Daimyō invalide.");
            }

            $profile = $honorEngine->getUserProfile($userId);
            if (!$profile) {
                throw new Exception("Daimyō introuvable dans les registres impériaux.");
            }

            $profile['is_self'] = ($userId === (int)Auth::id());
            echo json_encode(['success' => true, 'profile' => $profile]);
            break;

        // Mettre à jour la devise / bio personnelle
        case 'update_bio':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode invalide.");
            }
            $currentUserId = (int)Auth::id();
            $bio = (string)($_POST['bio'] ?? '');
            
            $success = $honorEngine->updateBio($currentUserId, $bio);
            echo json_encode([
                'success' => $success,
                'message' => "Votre devise de Daimyō a été actualisée avec succès !",
                'bio' => trim(strip_tags($bio))
            ]);
            break;

        // Récupérer le Tableau d'Honneur de la semaine
        case 'get_honor_roll':
            $honorRoll = $honorEngine->getFullHonorRoll(10);
            echo json_encode(['success' => true, 'honor_roll' => $honorRoll]);
            break;

        default:
            throw new Exception("Action inconnue.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

