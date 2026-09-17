<?php
/**
 * API REST pour la gestion et la diffusion des annonces de fonctionnalités (OpenShogun)
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/AnnouncementEngine.php';

$auth = new Auth();

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

$user = $auth->getCurrentUser();
$userId = (int)$user['id'];
$isAdmin = $auth->isAdmin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        // --- ACTIONS JOUEURS ---

        // Récupérer la première annonce non lue pour le joueur
        case 'get_unread':
            $firstUnread = AnnouncementEngine::getFirstUnreadForUser($userId);
            $unreadList = AnnouncementEngine::getUnreadForUser($userId);
            echo json_encode([
                'success' => true,
                'has_unread' => ($firstUnread !== null),
                'announcement' => $firstUnread,
                'unread_count' => count($unreadList)
            ]);
            break;

        // Marquer une annonce comme lue et validée par le joueur
        case 'mark_read':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode HTTP invalide.");
            }

            $announcementId = trim($_POST['announcement_id'] ?? '');
            if (empty($announcementId)) {
                throw new Exception("Identifiant d'annonce manquant.");
            }

            $ok = AnnouncementEngine::markAsRead($userId, $announcementId);
            if (!$ok) {
                throw new Exception("Échec de l'enregistrement de lecture.");
            }

            // Récupérer s'il en reste d'autres
            $remaining = AnnouncementEngine::getUnreadForUser($userId);

            echo json_encode([
                'success' => true,
                'message' => "Lecture enregistrée avec succès.",
                'remaining_unread_count' => count($remaining),
                'next_announcement' => !empty($remaining) ? $remaining[0] : null
            ]);
            break;

        // --- ACTIONS ADMINISTRATEUR ---

        // Liste de toutes les annonces (brouillons et publiées) + stats
        case 'admin_list':
            if (!$isAdmin) {
                http_response_code(403);
                throw new Exception("Accès réservé aux administrateurs.");
            }

            $announcements = AnnouncementEngine::getAllAnnouncements(false);
            $stats = AnnouncementEngine::getReadStats();

            // Enrichir avec le nombre de lectures
            foreach ($announcements as &$ann) {
                $id = $ann['id'] ?? '';
                $ann['read_count'] = $stats[$id] ?? 0;
            }

            echo json_encode([
                'success' => true,
                'announcements' => $announcements,
                'total_count' => count($announcements)
            ]);
            break;

        // Sauvegarder (créer ou modifier) une annonce
        case 'admin_save':
            if (!$isAdmin) {
                http_response_code(403);
                throw new Exception("Accès réservé aux administrateurs.");
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode HTTP invalide.");
            }

            $id = !empty($_POST['id']) ? trim($_POST['id']) : null;
            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                throw new Exception("Le titre de l'annonce est obligatoire.");
            }

            $featuresRaw = $_POST['features'] ?? [];
            if (is_string($featuresRaw)) {
                $features = json_decode($featuresRaw, true) ?: [];
            } elseif (is_array($featuresRaw)) {
                $features = $featuresRaw;
            } else {
                $features = [];
            }

            $isPublished = !empty($_POST['is_published']) && ($_POST['is_published'] === '1' || $_POST['is_published'] === 'true' || $_POST['is_published'] === true);

            $data = [
                'version' => trim($_POST['version'] ?? 'v1.0'),
                'title' => $title,
                'badge' => trim($_POST['badge'] ?? '✨ NOUVEAUTÉ'),
                'icon' => trim($_POST['icon'] ?? '📜'),
                'summary' => trim($_POST['summary'] ?? ''),
                'date' => !empty($_POST['date']) ? trim($_POST['date']) : date('Y-m-d'),
                'is_published' => $isPublished,
                'author' => $user['username'] ?? 'Admin',
                'features' => $features
            ];

            $savedId = AnnouncementEngine::saveAnnouncement($data, $id);

            echo json_encode([
                'success' => true,
                'message' => $id ? "Annonce mise à jour avec succès." : "Nouvelle annonce créée avec succès.",
                'announcement_id' => $savedId
            ]);
            break;

        // Valider / Dévalider la publication d'une annonce
        case 'admin_toggle_publish':
            if (!$isAdmin) {
                http_response_code(403);
                throw new Exception("Accès réservé aux administrateurs.");
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode HTTP invalide.");
            }

            $id = trim($_POST['id'] ?? '');
            if (empty($id)) {
                throw new Exception("Identifiant d'annonce manquant.");
            }

            $ann = AnnouncementEngine::getAnnouncement($id);
            if (!$ann) {
                throw new Exception("Annonce introuvable.");
            }

            $newStatus = empty($ann['is_published']);
            AnnouncementEngine::setPublishedStatus($id, $newStatus);

            $statusText = $newStatus ? "publiée et visible des joueurs" : "retirée (mise en brouillon)";

            echo json_encode([
                'success' => true,
                'message' => "L'annonce est désormais {$statusText}.",
                'is_published' => $newStatus
            ]);
            break;

        // Supprimer une annonce
        case 'admin_delete':
            if (!$isAdmin) {
                http_response_code(403);
                throw new Exception("Accès réservé aux administrateurs.");
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode HTTP invalide.");
            }

            $id = trim($_POST['id'] ?? '');
            if (empty($id)) {
                throw new Exception("Identifiant d'annonce manquant.");
            }

            $ok = AnnouncementEngine::deleteAnnouncement($id);
            if (!$ok) {
                throw new Exception("Impossible de supprimer l'annonce.");
            }

            echo json_encode([
                'success' => true,
                'message' => "L'annonce a été supprimée avec succès."
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Action '{$action}' inconnue."]);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
