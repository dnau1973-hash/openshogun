<?php
/**
 * API REST / AJAX : Gestionnaire de Storyboards & Narrative Designer
 * Endpoints pour le Game Studio (OpenShogun)
 * Fichier : api/storyboard.php
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/GameStudioEngine.php';
require_once __DIR__ . '/../core/StoryboardEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentification requise.']);
    exit;
}

$currentUser = $auth->getCurrentUser();
$userId = (int)($currentUser['id'] ?? 0);

$studioEngine = new GameStudioEngine();
if (!$studioEngine->isAuthorized($userId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès réservé aux membres du Game Studio.']);
    exit;
}

$engine = new StoryboardEngine();

// Récupération des données POST JSON ou classiques
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput ?: '[]', true) ?: [];

$action = $_GET['action'] ?? $_POST['action'] ?? $jsonInput['action'] ?? 'list';

try {
    switch ($action) {
        // Liste de tous les storyboards
        case 'list':
            $storyboards = $engine->getStoryboards();
            echo json_encode([
                'success' => true,
                'storyboards' => $storyboards,
            ]);
            break;

        // Récupération détaillée d'un storyboard avec ses plans et timecodes
        case 'get':
            $id = (int)($_GET['id'] ?? $_POST['id'] ?? $jsonInput['id'] ?? 0);
            $sb = $engine->getStoryboard($id);
            if (!$sb) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Storyboard introuvable.']);
                exit;
            }
            echo json_encode([
                'success' => true,
                'storyboard' => $sb,
            ]);
            break;

        // Création d'un nouveau projet de storyboard
        case 'create':
            $data = !empty($jsonInput['data']) ? $jsonInput['data'] : $_POST;
            $created = $engine->createStoryboard($data, $userId);
            if (!$created) {
                echo json_encode(['success' => false, 'error' => 'Impossible de créer le storyboard.']);
                exit;
            }
            echo json_encode([
                'success' => true,
                'message' => 'Nouveau storyboard initialisé avec succès !',
                'storyboard' => $created,
            ]);
            break;

        // Mise à jour des métadonnées du projet (titre, synopsis, durée cible, etc.)
        case 'update':
            $id = (int)($jsonInput['id'] ?? $_POST['id'] ?? 0);
            $data = $jsonInput['data'] ?? $_POST;
            $ok = $engine->updateStoryboard($id, $data);
            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour des paramètres.']);
                exit;
            }
            $updated = $engine->getStoryboard($id);
            echo json_encode([
                'success' => true,
                'message' => 'Paramètres du storyboard enregistrés.',
                'storyboard' => $updated,
            ]);
            break;

        // Duplication d'un storyboard
        case 'duplicate':
            $id = (int)($jsonInput['id'] ?? $_POST['id'] ?? 0);
            $duplicated = $engine->duplicateStoryboard($id, $userId);
            if (!$duplicated) {
                echo json_encode(['success' => false, 'error' => 'Impossible de dupliquer ce projet.']);
                exit;
            }
            echo json_encode([
                'success' => true,
                'message' => 'Projet dupliqué avec succès.',
                'storyboard' => $duplicated,
            ]);
            break;

        // Suppression d'un storyboard
        case 'delete':
            $id = (int)($jsonInput['id'] ?? $_POST['id'] ?? 0);
            $ok = $engine->deleteStoryboard($id);
            echo json_encode([
                'success' => $ok,
                'message' => $ok ? 'Storyboard supprimé.' : 'Échec de la suppression.',
            ]);
            break;

        // Sauvegarde de l'ensemble des plans (Drag & Drop, ajouts, modifications)
        case 'save_shots':
            $id = (int)($jsonInput['storyboard_id'] ?? $_POST['storyboard_id'] ?? 0);
            $shots = $jsonInput['shots'] ?? $_POST['shots'] ?? [];
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Identifiant de storyboard invalide.']);
                exit;
            }
            $ok = $engine->saveStoryboardShots($id, $shots);
            $updated = $engine->getStoryboard($id);
            echo json_encode([
                'success' => $ok,
                'message' => 'Timeline synchronisée avec succès.',
                'storyboard' => $updated,
            ]);
            break;

        // Génération automatique des prompts IA vidéo (Runway, Sora, Kling, Luma)
        case 'ai_prompts':
            $id = (int)($_GET['id'] ?? $_POST['id'] ?? $jsonInput['id'] ?? 0);
            $prompts = $engine->generateAiPrompts($id);
            echo json_encode([
                'success' => true,
                'data' => $prompts,
            ]);
            break;

        // Export du script de tournage en Markdown
        case 'export_markdown':
            $id = (int)($_GET['id'] ?? $_POST['id'] ?? $jsonInput['id'] ?? 0);
            $markdown = $engine->exportMarkdown($id);
            echo json_encode([
                'success' => true,
                'markdown' => $markdown,
            ]);
            break;

        // Catalogue complet des illustrations du jeu pour le sélecteur modal
        case 'gallery_assets':
            $assets = $engine->getGalleryAssets();
            echo json_encode([
                'success' => true,
                'total' => count($assets),
                'assets' => $assets,
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action non reconnue : ' . htmlspecialchars((string)$action)]);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur interne du serveur : ' . $e->getMessage()
    ]);
}
