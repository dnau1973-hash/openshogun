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

        // Téléverser un avatar personnalisé pour le Daimyō
        case 'upload_avatar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode de requête non autorisée.");
            }
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                $errorCode = $_FILES['avatar']['error'] ?? 'AUCUN_FICHIER';
                throw new Exception("Aucun fichier d'avatar valide n'a été transmis (code: {$errorCode}).");
            }

            $file = $_FILES['avatar'];
            $maxSize = 2 * 1024 * 1024; // 2 Mo
            if ($file['size'] > $maxSize) {
                throw new Exception("L'image de l'avatar est trop volumineuse (maximum 2 Mo).");
            }

            // Vérification stricte du type MIME
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            $allowedMimes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($allowedMimes[$mimeType])) {
                throw new Exception("Format d'image non supporté ({$mimeType}). Seuls les formats JPEG, PNG et WEBP sont acceptés.");
            }

            $currentUserId = (int)Auth::id();
            $ext = $allowedMimes[$mimeType];
            $uniqueName = sprintf('avatar_%d_%d_%s.%s', $currentUserId, time(), bin2hex(random_bytes(6)), $ext);

            // Répertoire de destination
            $uploadDir = __DIR__ . '/../public/assets/uploads/avatars/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception("Impossible de créer le répertoire d'enregistrement des avatars.");
                }
            }

            $destPath = $uploadDir . $uniqueName;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                throw new Exception("Échec de l'enregistrement de l'avatar sur le serveur.");
            }

            $webUrl = '/public/assets/uploads/avatars/' . $uniqueName;
            $success = $honorEngine->updateAvatar($currentUserId, $webUrl);

            // Mettre à jour immédiatement la variable de session pour éviter toute perte au rechargement
            Auth::initSession();
            if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
                $_SESSION['user'] = [];
            }
            $_SESSION['user']['avatar'] = $webUrl;
            $_SESSION['avatar'] = $webUrl;

            // Commit explicite de transaction si existante
            $db = Database::getConnection();
            if ($db->inTransaction()) {
                $db->commit();
            }

            echo json_encode([
                'success' => $success,
                'message' => "Votre avatar personnalisé a été établi avec honneur !",
                'avatar_url' => $webUrl
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

