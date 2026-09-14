<?php
/**
 * API REST d'Administration Système & Gestion des Bots
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GameConfig.php';
require_once __DIR__ . '/../core/BotEngine.php';
require_once __DIR__ . '/../core/WorldGenerator.php';
require_once __DIR__ . '/../core/HonorEngine.php';

$auth = new Auth();

// 1. Vérification stricte des droits Administrateur
if (!Auth::check() || !$auth->isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès restreint au Shogun et aux Administrateurs habilités.']);
    exit;
}

$botEngine = new BotEngine();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        // Sauvegarde des variables de jeu
        case 'save_settings':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode invalide.");
            }

            $gameSpeed = max(1, min(100, (int)($_POST['game_speed'] ?? 5)));
            $resourceSpeed = max(1, min(100, (int)($_POST['resource_speed'] ?? 5)));
            $fleetSpeed = max(1, min(50, (int)($_POST['fleet_speed'] ?? 5)));
            $botsEnabled = !empty($_POST['bots_enabled']) && ($_POST['bots_enabled'] === '1' || $_POST['bots_enabled'] === 'true');
            $botColonize = !empty($_POST['bot_colonize_enabled']) && ($_POST['bot_colonize_enabled'] === '1' || $_POST['bot_colonize_enabled'] === 'true');
            $botMaxPlanets = max(1, min(10, (int)($_POST['bot_max_planets'] ?? 3)));
            $botAggressiveness = in_array($_POST['bot_aggressiveness'] ?? '', ['peaceful', 'moderate', 'aggressive']) 
                ? $_POST['bot_aggressiveness'] 
                : 'moderate';

            GameConfig::set('game_speed', $gameSpeed);
            GameConfig::set('resource_speed', $resourceSpeed);
            GameConfig::set('fleet_speed', $fleetSpeed);
            GameConfig::set('bots_enabled', $botsEnabled);
            GameConfig::set('bot_colonize_enabled', $botColonize);
            GameConfig::set('bot_max_planets', $botMaxPlanets);
            GameConfig::set('bot_aggressiveness', $botAggressiveness);

            echo json_encode([
                'success' => true,
                'message' => "Variables de jeu et équilibrage sauvegardés avec succès !",
                'settings' => GameConfig::load()
            ]);
            break;

        // Déclencher manuellement un cycle d'IA pour tous les bots
        case 'run_bot_cycle':
            $result = $botEngine->executeBotCycle();
            echo json_encode($result);
            break;

        // Générer une escadre de bots prédéfinis
        case 'generate_bots':
            $count = max(1, min(6, (int)($_POST['count'] ?? 3)));
            $result = $botEngine->generatePresetBots($count);
            echo json_encode($result);
            break;

        // Supprimer un bot
        case 'delete_bot':
            $botId = (int)($_POST['bot_id'] ?? 0);
            if ($botId <= 0) {
                throw new Exception("Identifiant de bot invalide.");
            }
            $success = $botEngine->deleteBot($botId);
            if (!$success) {
                throw new Exception("Impossible de supprimer ce compte ou ce n'est pas un bot.");
            }
            echo json_encode(['success' => true, 'message' => "Daimyō Bot et ses fiefs supprimés avec succès."]);
            break;

        // Promouvoir ou révoquer le rôle Admin
        case 'toggle_admin':
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            $currentUserId = (int)Auth::id();

            if ($targetUserId <= 0) {
                throw new Exception("Utilisateur invalide.");
            }
            if ($targetUserId === $currentUserId) {
                throw new Exception("Vous ne pouvez pas modifier votre propre statut Administrateur.");
            }

            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT is_admin, username FROM users WHERE id = ?");
            $stmt->execute([$targetUserId]);
            $targetUser = $stmt->fetch();

            if (!$targetUser) {
                throw new Exception("Utilisateur non trouvé.");
            }

            $newStatus = (int)$targetUser['is_admin'] === 1 ? 0 : 1;
            $db->prepare("UPDATE users SET is_admin = ? WHERE id = ?")->execute([$newStatus, $targetUserId]);

            $statusText = $newStatus === 1 ? "promu Administrateur du Shogunat" : "rétrogradé au rang de Daimyō Joueur";
            echo json_encode([
                'success' => true,
                'message' => "Le Daimyō {$targetUser['username']} a été {$statusText}.",
                'is_admin' => $newStatus
            ]);
            break;

        // Obtenir la liste actualisée des bots
        case 'get_bots':
            $bots = $botEngine->getBots();
            echo json_encode(['success' => true, 'bots' => $bots]);
            break;

        // Décerner les médailles hebdomadaires et réinitialiser les compteurs de la semaine
        case 'award_medals':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode invalide.");
            }
            $weekCode = !empty($_POST['week_code']) ? trim($_POST['week_code']) : null;
            $honorEngine = new HonorEngine();
            $result = $honorEngine->awardWeeklyMedals($weekCode);
            echo json_encode($result);
            break;

        // Générateur de monde procédural
        case 'generate_world':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode invalide.");
            }
            $count = max(1, min(100, (int)($_POST['planet_count'] ?? 12)));
            $radius = max(5, min(50, (int)($_POST['radius'] ?? 10)));
            $clearUninhabited = !empty($_POST['clear_uninhabited']) && ($_POST['clear_uninhabited'] === '1' || $_POST['clear_uninhabited'] === 'true');
            $types = !empty($_POST['types']) && is_array($_POST['types']) ? $_POST['types'] : [];

            $worldGen = new WorldGenerator();
            $res = $worldGen->generatePlanets($count, $radius, $types, $clearUninhabited);
            echo json_encode($res);
            break;

        // Réinitialisation complète de l'Univers (Full Reset)
        case 'reset_universe':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode invalide.");
            }
            $confirmKeyword = trim($_POST['confirm_keyword'] ?? '');
            if ($confirmKeyword !== 'RESET') {
                throw new Exception("Confirmation invalide. Vous devez saisir exactement 'RESET' pour confirmer la réinitialisation.");
            }

            $adminPassword = !empty($_POST['admin_password']) ? trim($_POST['admin_password']) : 'Gabriel125#';
            $neutralCount = max(5, min(50, (int)($_POST['neutral_planets'] ?? 12)));
            $deployBots = !isset($_POST['deploy_bots']) || $_POST['deploy_bots'] === '1' || $_POST['deploy_bots'] === 'true';

            $worldGen = new WorldGenerator();
            $result = $worldGen->resetUniverse($adminPassword, $neutralCount, $deployBots);
            echo json_encode($result);
            break;

        default:
            throw new Exception("Action inconnue.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

