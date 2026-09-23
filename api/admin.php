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
require_once __DIR__ . '/../core/CastleEngine.php';

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

            $oasisDensity = max(0.5, min(20.0, (float)($_POST['oasis_density_percent'] ?? 2.0)));
            $oasisRespawn = !empty($_POST['oasis_respawn_on_capture']) && ($_POST['oasis_respawn_on_capture'] === '1' || $_POST['oasis_respawn_on_capture'] === 'true');
            $beginnerProtectionDays = max(0, min(365, (int)($_POST['beginner_protection_days'] ?? 7)));

            $famineEnabled = !empty($_POST['famine_enabled']) && ($_POST['famine_enabled'] === '1' || $_POST['famine_enabled'] === 'true');
            $famineRate = max(0.5, min(50.0, (float)($_POST['famine_rate'] ?? 3.0)));
            $famineConsumption = max(0.1, min(20.0, (float)($_POST['famine_flour_consumption'] ?? 1.0)));

            GameConfig::set('game_speed', $gameSpeed);
            GameConfig::set('resource_speed', $resourceSpeed);
            GameConfig::set('fleet_speed', $fleetSpeed);
            GameConfig::set('bots_enabled', $botsEnabled);
            GameConfig::set('bot_colonize_enabled', $botColonize);
            GameConfig::set('bot_max_planets', $botMaxPlanets);
            GameConfig::set('bot_aggressiveness', $botAggressiveness);
            GameConfig::set('oasis_density_percent', $oasisDensity);
            GameConfig::set('oasis_respawn_on_capture', $oasisRespawn);
            GameConfig::set('beginner_protection_days', $beginnerProtectionDays);
            GameConfig::set('famine_enabled', $famineEnabled);
            GameConfig::set('famine_rate', $famineRate);
            GameConfig::set('famine_flour_consumption', $famineConsumption);

            echo json_encode([
                'success' => true,
                'message' => "Variables de jeu, équilibrage, oasis, immunité et mécanisme de famine sauvegardés avec succès !",
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

        // Promouvoir ou révoquer le rôle Modérateur
        case 'toggle_moderator':
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            $currentUserId = (int)Auth::id();

            if ($targetUserId <= 0) {
                throw new Exception("Utilisateur invalide.");
            }

            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT is_moderator, username FROM users WHERE id = ?");
            $stmt->execute([$targetUserId]);
            $targetUser = $stmt->fetch();

            if (!$targetUser) {
                throw new Exception("Utilisateur non trouvé.");
            }

            $newStatus = (int)($targetUser['is_moderator'] ?? 0) === 1 ? 0 : 1;
            $db->prepare("UPDATE users SET is_moderator = ? WHERE id = ?")->execute([$newStatus, $targetUserId]);

            $statusText = $newStatus === 1 ? "investi du rôle de Modérateur Féodal 🛡️" : "retiré du corps de Modération";
            echo json_encode([
                'success' => true,
                'message' => "Le Daimyō {$targetUser['username']} a été {$statusText}.",
                'is_moderator' => $newStatus
            ]);
            break;

        // Prolonger l'immunité d'un joueur
        case 'extend_protection':
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            $days = max(1, min(90, (int)($_POST['days'] ?? 7)));
            if ($targetUserId <= 0) {
                throw new Exception("Utilisateur invalide.");
            }
            Auth::extendProtection($targetUserId, $days);
            $rem = Auth::getProtectionRemaining($targetUserId);
            echo json_encode([
                'success' => true,
                'message' => "L'immunité du joueur a été prolongée de {$days} jours avec succès !",
                'protection_until' => $rem['until_formatted'] ?? '',
                'protection_remaining' => $rem['formatted'] ?? ''
            ]);
            break;

        // Révoquer l'immunité d'un joueur
        case 'revoke_protection':
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            if ($targetUserId <= 0) {
                throw new Exception("Utilisateur invalide.");
            }
            Auth::revokeProtection($targetUserId);
            echo json_encode([
                'success' => true,
                'message' => "L'immunité du joueur a été levée avec succès !"
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

        // Déployer tous les 12 châteaux authentiques
        case 'spawn_all_castles':
            $castleEngine = new CastleEngine();
            $res = $castleEngine->spawnAllCastles();
            echo json_encode($res);
            break;

        // Retirer tous les 12 châteaux de la carte
        case 'despawn_all_castles':
            $castleEngine = new CastleEngine();
            $res = $castleEngine->despawnAllCastles();
            echo json_encode($res);
            break;

        // Déployer ou retirer un château individuel
        case 'toggle_castle':
            $castleId = (int)($_POST['castle_id'] ?? 0);
            $spawn = !empty($_POST['spawn']) && ($_POST['spawn'] === '1' || $_POST['spawn'] === 'true');
            $castleEngine = new CastleEngine();
            $castle = $castleEngine->getCastle($castleId);
            if (!$castle) throw new Exception("Château introuvable.");

            if ($spawn) {
                $x = isset($_POST['x']) ? (int)$_POST['x'] : ($castle['coord_x'] ?? $castle['default_x']);
                $y = isset($_POST['y']) ? (int)$_POST['y'] : ($castle['coord_y'] ?? $castle['default_y']);
                $ok = $castleEngine->spawnCastle($castleId, $x, $y);
                $msg = "Le {$castle['name']} a été déployé en [$x : $y].";
            } else {
                $ok = $castleEngine->despawnCastle($castleId);
                $msg = "Le {$castle['name']} a été retiré de la carte.";
            }
            echo json_encode(['success' => $ok, 'message' => $msg]);
            break;

        // Mettre à jour les coordonnées d'un château
        case 'update_castle_coords':
            $castleId = (int)($_POST['castle_id'] ?? 0);
            $x = (int)($_POST['x'] ?? 0);
            $y = (int)($_POST['y'] ?? 0);
            $castleEngine = new CastleEngine();
            $ok = $castleEngine->updateCastleCoords($castleId, $x, $y);
            echo json_encode(['success' => $ok, 'message' => "Coordonnées mises à jour en [$x : $y]."]);
            break;

        // Rééquilibrer / Générer les oasis selon le pourcentage de densité configuré
        case 'repopulate_oases':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode invalide.");
            }
            require_once __DIR__ . '/../core/OasisEngine.php';
            $oasisEngine = new OasisEngine();

            $density = max(0.5, min(20.0, (float)($_POST['density_percent'] ?? GameConfig::get('oasis_density_percent', 2.0))));
            $radius = max(10, min(50, (int)($_POST['radius'] ?? 28)));
            $clearUnoccupied = !empty($_POST['clear_unoccupied']) && ($_POST['clear_unoccupied'] === '1' || $_POST['clear_unoccupied'] === 'true');

            GameConfig::set('oasis_density_percent', $density);

            $result = $oasisEngine->spawnOasesByDensity($density, $radius, $clearUnoccupied);
            echo json_encode($result);
            break;

        // Obtenir les statistiques globales des oasis
        case 'get_oasis_stats':
            require_once __DIR__ . '/../core/OasisEngine.php';
            $oasisEngine = new OasisEngine();
            $stats = $oasisEngine->getOasisStatistics();
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;

        // Sauvegarder les positions calibrées en Drag & Drop (Cité ou Terroir)
        case 'save_slot_positions':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode invalide.");
            }
            require_once __DIR__ . '/../core/SlotPositionEngine.php';

            $view = $_POST['view'] ?? '';
            if (!in_array($view, ['resources', 'city'], true)) {
                throw new Exception("Vue invalide ('resources' ou 'city' attendu).");
            }

            $positionsRaw = $_POST['positions'] ?? null;
            if (is_string($positionsRaw)) {
                $positions = json_decode($positionsRaw, true);
            } elseif (is_array($positionsRaw)) {
                $positions = $positionsRaw;
            } else {
                $positions = [];
            }

            if (empty($positions) || !is_array($positions)) {
                throw new Exception("Données de positions invalides.");
            }

            $ok = SlotPositionEngine::savePositions($view, $positions);
            if (!$ok) {
                throw new Exception("Échec de la sauvegarde dans config/slot_positions.json.");
            }

            echo json_encode([
                'success' => true,
                'message' => "Positions calibrées sauvegardées avec succès !"
            ]);
            break;

        // Réinitialiser les positions aux valeurs par défaut
        case 'reset_slot_positions':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode invalide.");
            }
            require_once __DIR__ . '/../core/SlotPositionEngine.php';

            $view = $_POST['view'] ?? '';
            if (!in_array($view, ['resources', 'city'], true)) {
                throw new Exception("Vue invalide.");
            }

            SlotPositionEngine::resetPositions($view);
        // Vérifier les mises à jour GitHub
        case 'check_github_updates':
            require_once __DIR__ . '/../core/UpdateEngine.php';
            $updateEngine = new UpdateEngine();
            $result = $updateEngine->checkRemoteUpdates();
            echo json_encode($result);
            break;

        // Installer la mise à jour GitHub (git pull)
        case 'install_github_update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode invalide.");
            }
            require_once __DIR__ . '/../core/UpdateEngine.php';
            $updateEngine = new UpdateEngine();
            $autoStash = !isset($_POST['auto_stash']) || $_POST['auto_stash'] === '1' || $_POST['auto_stash'] === 'true';
            $result = $updateEngine->installUpdate($autoStash);
            echo json_encode($result);
            break;

        // Sauvegarder les paramètres GitHub (Token, branche, etc.)
        case 'save_github_settings':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode invalide.");
            }
            require_once __DIR__ . '/../core/UpdateEngine.php';
            $token = trim($_POST['github_token'] ?? '');
            $branch = trim($_POST['github_branch'] ?? 'main');
            $owner = trim($_POST['github_repo_owner'] ?? 'dnau1973-hash');
            $repo = trim($_POST['github_repo_name'] ?? 'openshogun');

            $updateEngine = new UpdateEngine();
            $success = $updateEngine->saveSettings($token, $branch, $owner, $repo);
            echo json_encode([
                'success' => $success,
                'message' => "Paramètres GitHub enregistrés avec succès !"
            ]);
            break;

        // Soigner un Samouraï Héros à 100%
        case 'admin_heal_hero':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            if ($targetUserId <= 0) throw new Exception("Utilisateur invalide.");
            require_once __DIR__ . '/../core/HeroEngine.php';
            $heroEngine = new HeroEngine();
            $hero = $heroEngine->getHeroByUserId($targetUserId);
            if (!$hero) throw new Exception("Héros introuvable.");
            $db = Database::getConnection();
            $db->prepare("UPDATE heroes SET health = 100.0, status = IF(status IN ('dead', 'reviving'), 'home', status), last_health_update = UNIX_TIMESTAMP() WHERE id = ?")->execute([$hero['id']]);
            echo json_encode(['success' => true, 'message' => "Le Samouraï {$hero['name']} a été soigné à 100% avec succès !"]);
            break;

        // Ressusciter instantanément un héros tombé au combat (sans attendre 24h)
        case 'admin_revive_hero':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            if ($targetUserId <= 0) throw new Exception("Utilisateur invalide.");
            require_once __DIR__ . '/../core/HeroEngine.php';
            $heroEngine = new HeroEngine();
            $hero = $heroEngine->getHeroByUserId($targetUserId);
            if (!$hero) throw new Exception("Héros introuvable.");
            $db = Database::getConnection();
            $db->prepare("UPDATE fleet_missions SET status = 'completed' WHERE user_id = ? AND mission_type = 'revive'")->execute([$targetUserId]);
            $db->prepare("UPDATE heroes SET status = 'home', health = 100.0, last_health_update = UNIX_TIMESTAMP() WHERE id = ?")->execute([$hero['id']]);
            echo json_encode(['success' => true, 'message' => "Le Samouraï {$hero['name']} a été ressuscité immédiatement par décret du Shōgun !"]);
            break;

        // Réinitialiser le quota quotidien de 3 aventures féodales
        case 'admin_reset_hero_quota':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            if ($targetUserId <= 0) throw new Exception("Utilisateur invalide.");
            $db = Database::getConnection();
            $startOfDay = strtotime('today midnight');
            $db->prepare("DELETE FROM fleet_missions WHERE user_id = ? AND mission_type = 'adventure' AND departure_time >= ?")->execute([$targetUserId, $startOfDay]);
            echo json_encode(['success' => true, 'message' => "Le quota d'aventures quotidiennes a été réinitialisé à 0/3 pour ce joueur."]);
            break;

        // Octroyer une relique féodale aléatoire inédite au joueur
        case 'admin_grant_relic':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            if ($targetUserId <= 0) throw new Exception("Utilisateur invalide.");
            require_once __DIR__ . '/../core/HeroEngine.php';
            $heroEngine = new HeroEngine();
            $item = $heroEngine->grantRandomEquipment($targetUserId);
            if (!$item) {
                echo json_encode(['success' => false, 'error' => "Ce joueur possède déjà l'intégralité des 35 reliques uniques du panthéon !"]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => "La relique « {$item['name']} » ({$item['type']}) a été octroyée au joueur !",
                    'item' => $item
                ]);
            }
            break;

        default:
            throw new Exception("Action inconnue.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
