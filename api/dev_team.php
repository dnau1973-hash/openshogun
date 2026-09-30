<?php
/**
 * API REST : Dev Team & Métiers du Jeu Vidéo (OpenShogun)
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/DevTeamEngine.php';
require_once __DIR__ . '/../core/BotEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentification requise.']);
    exit;
}

$currentUserId = (int)Auth::id();
$devEngine = new DevTeamEngine();

// Vérifier que l'utilisateur est bien membre de la Dev Team
if (!$devEngine->isDevTeamMember($currentUserId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès réservé aux membres de la Dev Team.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {

        // 1. Profil du créateur
        case 'get_profile':
            $targetId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $currentUserId;
            $profile = $devEngine->getDevProfile($targetId);
            echo json_encode(['success' => true, 'profile' => $profile]);
            break;

        // 2. Liste de tous les membres de la Dev Team
        case 'get_members':
            $members = $devEngine->getAllDevTeamMembers();
            echo json_encode(['success' => true, 'members' => $members]);
            break;

        // 3. Attribution d'un rôle métier (Direction / Team manage)
        case 'assign_role':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            DevTeamEngine::authorize('team.manage', $currentUserId);

            $targetId = (int)($_POST['user_id'] ?? 0);
            $roleId = (string)($_POST['role_id'] ?? '');

            if ($targetId <= 0 || empty($roleId)) {
                throw new Exception("Paramètres manquants.");
            }

            $success = $devEngine->assignRole($targetId, $roleId);
            echo json_encode([
                'success' => $success,
                'message' => "Le métier a été assigné avec succès !"
            ]);
            break;

        // 4. Révocation d'un rôle métier
        case 'remove_role':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            DevTeamEngine::authorize('team.manage', $currentUserId);

            $targetId = (int)($_POST['user_id'] ?? 0);
            $roleId = (string)($_POST['role_id'] ?? '');

            if ($targetId <= 0 || empty($roleId)) {
                throw new Exception("Paramètres manquants.");
            }

            $success = $devEngine->removeRole($targetId, $roleId);
            echo json_encode([
                'success' => $success,
                'message' => "Le métier a été retiré."
            ]);
            break;

        // 5. Actions Sandbox / Debug (QA & Game Designers)
        case 'sandbox_action':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            DevTeamEngine::authorize('debug.sandbox', $currentUserId);

            $subAction = (string)($_POST['sub_action'] ?? '');
            $db = Database::getConnection();

            if ($subAction === 'give_resources') {
                $planetId = (int)($_SESSION['current_planet_id'] ?? 0);
                if ($planetId <= 0) {
                    $planet = $db->query("SELECT id FROM planets WHERE user_id = $currentUserId ORDER BY is_capital DESC LIMIT 1")->fetch();
                    $planetId = (int)($planet['id'] ?? 0);
                }
                if ($planetId > 0) {
                    $amount = 100000;
                    $db->exec("UPDATE planets SET metal = metal + $amount, crystal = crystal + $amount, deuterium = deuterium + $amount WHERE id = $planetId");
                    $devEngine->addForgeXp($currentUserId, 15, 'sandbox_resources', "+100k ressources injectées (Sandbox QA)");
                    echo json_encode(['success' => true, 'message' => "100 000 Ressources octroyées pour tests de non-régression !"]);
                } else {
                    throw new Exception("Aucun fief rattaché.");
                }
            } elseif ($subAction === 'instant_finish_constructions') {
                $now = time();
                $db->exec("UPDATE construction_queue SET finishes_at = $now WHERE planet_id IN (SELECT id FROM planets WHERE user_id = $currentUserId) AND finishes_at > $now");
                $devEngine->addForgeXp($currentUserId, 20, 'sandbox_instant_build', "Achèvement immédiat des chantiers (Debug)");
                echo json_encode(['success' => true, 'message' => "Tous les chantiers en cours ont été instantanément achevés !"]);
            } else {
                throw new Exception("Action sandbox inconnue.");
            }
            break;

        // 6. Forcer un cycle de cron / bot (Backend / Live Ops)
        case 'trigger_cron':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            DevTeamEngine::authorize('cron.trigger', $currentUserId);

            $botEngine = new BotEngine();
            $result = $botEngine->runCycle();
            $devEngine->addForgeXp($currentUserId, 30, 'trigger_cron', "Déclenchement manuel du cycle de décisions IA");

            echo json_encode([
                'success' => true,
                'message' => "Cycle de décision IA / Cron exécuté avec succès !",
                'details' => $result
            ]);
            break;

        // 7. Accorder de l'XP de Forge (Gamification)
        case 'award_xp':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            DevTeamEngine::authorize('sprints.manage', $currentUserId);

            $targetId = (int)($_POST['user_id'] ?? 0);
            $xpAmount = (int)($_POST['xp_amount'] ?? 0);
            $reason   = (string)($_POST['reason'] ?? 'Contribution de forge');

            if ($targetId <= 0 || $xpAmount <= 0) {
                throw new Exception("Montant d'XP invalide.");
            }

            $devEngine->addForgeXp($targetId, $xpAmount, 'manual_reward', $reason);
            echo json_encode([
                'success' => true,
                'message' => "{$xpAmount} XP de Forge attribués avec mention : '{$reason}' !"
            ]);
            break;

        default:
            throw new Exception("Action Dev Team non reconnue.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
