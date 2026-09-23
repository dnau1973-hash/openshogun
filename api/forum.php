<?php
/**
 * API REST pour le Forum Féodal & Modération OpenShogun
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ForumEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Veuillez vous connecter pour participer aux débats féodaux.']);
    exit;
}

$currentUserId = (int)Auth::id();
$forumEngine = new ForumEngine();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        // 1. Proclamer un nouveau sujet
        case 'create_topic':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            $catId = (int)($_POST['category_id'] ?? 0);
            $title = (string)($_POST['title'] ?? '');
            $content = (string)($_POST['content'] ?? '');

            $result = $forumEngine->createTopic($catId, $currentUserId, $title, $content);
            echo json_encode($result);
            break;

        // 2. Répondre à un sujet
        case 'reply':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            $topicId = (int)($_POST['topic_id'] ?? 0);
            $content = (string)($_POST['content'] ?? '');

            $result = $forumEngine->createPost($topicId, $currentUserId, $content);
            echo json_encode($result);
            break;

        // 3. Modifier un message
        case 'edit_post':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            $postId = (int)($_POST['post_id'] ?? 0);
            $content = (string)($_POST['content'] ?? '');

            $result = $forumEngine->updatePost($postId, $currentUserId, $content);
            echo json_encode($result);
            break;

        // 4. Supprimer un message
        case 'delete_post':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            $postId = (int)($_POST['post_id'] ?? 0);

            $result = $forumEngine->deletePost($postId, $currentUserId);
            echo json_encode($result);
            break;

        // 5. Supprimer un sujet entier
        case 'delete_topic':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            $topicId = (int)($_POST['topic_id'] ?? 0);

            $result = $forumEngine->deleteTopic($topicId, $currentUserId);
            echo json_encode($result);
            break;

        // 6. Épingler / Désépingler un sujet (Modérateur / Admin)
        case 'toggle_pin':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            $topicId = (int)($_POST['topic_id'] ?? 0);

            $result = $forumEngine->togglePinTopic($topicId, $currentUserId);
            echo json_encode($result);
            break;

        // 7. Verrouiller / Déverrouiller un sujet (Modérateur / Admin)
        case 'toggle_lock':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            $topicId = (int)($_POST['topic_id'] ?? 0);

            $result = $forumEngine->toggleLockTopic($topicId, $currentUserId);
            echo json_encode($result);
            break;

        // 8. Admin : Créer une catégorie de forum
        case 'admin_create_category':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            if (!$auth->isAdmin()) throw new Exception("Action strictement réservée à l'administrateur suprême.");

            $name = (string)($_POST['name'] ?? '');
            $desc = (string)($_POST['description'] ?? '');
            $icon = (string)($_POST['icon'] ?? '💬');
            $order = (int)($_POST['display_order'] ?? 0);
            $isLocked = !empty($_POST['is_locked']);

            if (empty(trim($name))) throw new Exception("Le nom du salon ne peut pas être vide.");
            $catId = $forumEngine->createCategory($name, $desc, $icon, $order, $isLocked);
            echo json_encode(['success' => true, 'category_id' => $catId, 'message' => "Le salon '{$name}' a été créé."]);
            break;

        // 9. Admin : Modifier une catégorie de forum
        case 'admin_edit_category':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            if (!$auth->isAdmin()) throw new Exception("Action strictement réservée à l'administrateur suprême.");

            $catId = (int)($_POST['category_id'] ?? 0);
            $name = (string)($_POST['name'] ?? '');
            $desc = (string)($_POST['description'] ?? '');
            $icon = (string)($_POST['icon'] ?? '💬');
            $order = (int)($_POST['display_order'] ?? 0);
            $isLocked = !empty($_POST['is_locked']);

            if ($catId <= 0 || empty(trim($name))) throw new Exception("Informations de salon incomplètes.");
            $forumEngine->updateCategory($catId, $name, $desc, $icon, $order, $isLocked);
            echo json_encode(['success' => true, 'message' => "Le salon a été mis à jour."]);
            break;

        // 10. Admin : Supprimer une catégorie de forum
        case 'admin_delete_category':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            if (!$auth->isAdmin()) throw new Exception("Action strictement réservée à l'administrateur suprême.");

            $catId = (int)($_POST['category_id'] ?? 0);
            if ($catId <= 0) throw new Exception("Salon invalide.");
            $forumEngine->deleteCategory($catId);
            echo json_encode(['success' => true, 'message' => "Le salon a été supprimé des registres."]);
            break;

        default:
            throw new Exception("Action de forum non reconnue.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

