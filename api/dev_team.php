<?php
/**
 * API REST : Dev Team & Métiers du Jeu Vidéo (OpenShogun)
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/DevTeamEngine.php';
require_once __DIR__ . '/../core/BotEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/FeatureRegistry.php';
require_once __DIR__ . '/../core/QASyntaxChecker.php';
require_once __DIR__ . '/../core/GameConfig.php';
require_once __DIR__ . '/../core/WorldGenerator.php';
require_once __DIR__ . '/../core/OasisEngine.php';

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

        // 3. Attribution d'un ou plusieurs rôles métiers (Direction / Team manage)
        case 'assign_role':
        case 'assign_roles':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            DevTeamEngine::authorize('team.manage', $currentUserId);

            $targetId = (int)($_POST['user_id'] ?? 0);
            $roleIds = [];
            if (!empty($_POST['role_ids'])) {
                $roleIds = is_array($_POST['role_ids']) ? $_POST['role_ids'] : explode(',', (string)$_POST['role_ids']);
            } elseif (!empty($_POST['role_id'])) {
                $roleIds = is_array($_POST['role_id']) ? $_POST['role_id'] : [$_POST['role_id']];
            }

            if ($targetId <= 0 || empty($roleIds)) {
                throw new Exception("Veuillez sélectionner un membre et au moins un métier valide.");
            }

            $checkBot = $db->prepare("SELECT is_bot FROM users WHERE id = ?");
            $checkBot->execute([$targetId]);
            $isBotTarget = (int)$checkBot->fetchColumn();
            if ($isBotTarget) {
                throw new Exception("Opération impossible : les profils IA / bots ne peuvent pas être intégrés à la Dev Team.");
            }

            $assigned = $devEngine->assignRoles($targetId, $roleIds);
            if (empty($assigned)) {
                throw new Exception("Aucun nouveau métier n'a été assigné (métiers déjà possédés par le joueur ou invalides).");
            }

            $updatedRoles = $devEngine->getUserRoles($targetId);
            $count = count($assigned);
            $roleTitles = implode(', ', array_map(fn($r) => $r['title'], $assigned));

            echo json_encode([
                'success'        => true,
                'message'        => ($count === 1) ? "Le métier « {$roleTitles} » a été assigné !" : "{$count} métiers assignés avec succès : {$roleTitles} !",
                'assigned_roles' => $assigned,
                'all_roles'      => $updatedRoles,
                'user_id'        => $targetId
            ]);
            break;

        // 4. Révocation d'un rôle métier
        case 'remove_role':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            DevTeamEngine::authorize('team.manage', $currentUserId);

            $targetId = (int)($_POST['user_id'] ?? 0);
            $roleId = trim((string)($_POST['role_id'] ?? ''));

            if ($targetId <= 0 || empty($roleId)) {
                throw new Exception("Paramètres manquants.");
            }

            $success = $devEngine->removeRole($targetId, $roleId);
            $updatedRoles = $devEngine->getUserRoles($targetId);
            $roleTitle = DevTeamEngine::ROLES[$roleId]['title'] ?? $roleId;

            echo json_encode([
                'success'   => $success,
                'message'   => "Le métier « {$roleTitle} » a été retiré.",
                'user_id'   => $targetId,
                'role_id'   => $roleId,
                'all_roles' => $updatedRoles
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

        // 8. Récupérer l'état du registre QA et du contrôle syntaxique
        case 'get_qa_status':
            $features = FeatureRegistry::getAllFeatures();
            $stats = FeatureRegistry::getStatistics();
            $syntax = QASyntaxChecker::getLastCheckResult();
            echo json_encode([
                'success'  => true,
                'features' => $features,
                'stats'    => $stats,
                'syntax'   => $syntax
            ]);
            break;

        // 9. Mettre à jour le statut d'une fonctionnalité (Recette QA)
        case 'update_feature_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            
            // Permettre la recette aux profils QA, Game Designers, Producteurs ou Admins
            $canReview = $devEngine->hasPermission($currentUserId, 'bugs.manage') 
                      || $devEngine->hasPermission($currentUserId, 'debug.sandbox') 
                      || $auth->isAdmin();
            if (!$canReview) {
                throw new Exception("Habilitation QA requise pour valider ou rejeter une fonctionnalité.");
            }

            $featureId  = trim((string)($_POST['feature_id'] ?? ''));
            $newStatus  = trim((string)($_POST['new_status'] ?? ''));
            $testerName = (string)($_SESSION['username'] ?? 'Testeur QA');

            if (empty($featureId) || empty($newStatus)) {
                throw new Exception("Paramètres manquants pour la mise à jour du statut QA.");
            }

            $success = FeatureRegistry::updateStatus($featureId, $newStatus, $testerName);
            if (!$success) {
                throw new Exception("Impossible de mettre à jour le statut de cette fonctionnalité dans fonctionnalités.md.");
            }

            // Récompenser le testeur en XP de Forge
            $devEngine->addForgeXp($currentUserId, 25, 'qa_review', "Validation QA fonctionnalité (statut: {$newStatus})");

            $updatedStats = FeatureRegistry::getStatistics();
            echo json_encode([
                'success'    => true,
                'message'    => "Statut de la fonctionnalité mis à jour en « {$newStatus} » !",
                'feature_id' => $featureId,
                'new_status' => $newStatus,
                'stats'      => $updatedStats
            ]);
            break;

        // 10. Exécuter un contrôle syntaxique automatisé (php -l)
        case 'run_syntax_check':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            
            $canRunSyntax = $devEngine->hasPermission($currentUserId, 'bugs.manage') 
                         || $devEngine->hasPermission($currentUserId, 'debug.sandbox') 
                         || $auth->isAdmin();
            if (!$canRunSyntax) {
                throw new Exception("Habilitation QA requise pour exécuter le contrôle de syntaxe.");
            }

            $syntaxResult = QASyntaxChecker::runSyntaxCheck();
            if ($syntaxResult['is_clean']) {
                $devEngine->addForgeXp($currentUserId, 15, 'syntax_check_clean', "Contrôle technique automatisé réussi (100% propre)");
            }

            echo json_encode([
                'success' => true,
                'message' => $syntaxResult['is_clean'] 
                    ? "Feu vert technique accordé : 0 erreur de syntaxe sur {$syntaxResult['total_files']} fichiers !" 
                    : "Attention : {$syntaxResult['error_count']} erreur(s) de syntaxe détectée(s).",
                'syntax'  => $syntaxResult
            ]);
            break;

        // 11. Récupération des abonnés et statistiques de la Mailing List
        case 'get_mailing_subscribers':
            DevTeamEngine::authorize('community.mailing', $currentUserId);
            require_once __DIR__ . '/../core/MailingListEngine.php';
            $mailingEngine = new MailingListEngine();

            $filters = [
                'search'  => trim((string)($_GET['search'] ?? '')),
                'optin'   => $_GET['optin'] ?? 'all',
                'status'  => $_GET['status'] ?? 'all',
                'role'    => $_GET['role'] ?? 'all',
                'faction' => $_GET['faction'] ?? 'all'
            ];
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 25)));
            $page = max(1, (int)($_GET['page'] ?? 1));
            $offset = ($page - 1) * $limit;

            $data = $mailingEngine->getSubscribers($filters, $limit, $offset);
            $stats = $mailingEngine->getStatistics();

            echo json_encode([
                'success'     => true,
                'subscribers' => $data['subscribers'],
                'total'       => $data['total'],
                'page'        => $page,
                'limit'       => $limit,
                'stats'       => $stats
            ]);
            break;

        // 12. Basculer le statut Opt-in d'un abonné (Toggle Newsletter)
        case 'toggle_subscriber_optin':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            DevTeamEngine::authorize('community.mailing', $currentUserId);
            require_once __DIR__ . '/../core/MailingListEngine.php';
            $mailingEngine = new MailingListEngine();

            $targetUserId = (int)($_POST['user_id'] ?? 0);
            if ($targetUserId <= 0) throw new Exception("Identifiant utilisateur invalide.");

            $forceState = isset($_POST['optin']) ? (bool)$_POST['optin'] : null;
            $ok = $mailingEngine->toggleOptin($targetUserId, $forceState);
            if (!$ok) throw new Exception("Impossible de mettre à jour le statut opt-in.");

            $stats = $mailingEngine->getStatistics();

            echo json_encode([
                'success' => true,
                'message' => "Statut d'adhésion à la newsletter mis à jour avec succès !",
                'stats'   => $stats
            ]);
            break;

        // 13. Expédier une campagne de newsletter / missive impériale
        case 'send_newsletter_campaign':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            DevTeamEngine::authorize('community.mailing', $currentUserId);
            require_once __DIR__ . '/../core/MailingListEngine.php';
            $mailingEngine = new MailingListEngine();

            $subject = trim((string)($_POST['subject'] ?? ''));
            $targetGroup = trim((string)($_POST['target_group'] ?? 'all_optin'));
            $bodyHtml = trim((string)($_POST['body_html'] ?? ''));

            $currentUser = $auth->getCurrentUser();
            $senderName = $currentUser['username'] ?? 'Le Shōgunat';

            $result = $mailingEngine->sendCampaign($currentUserId, $senderName, $subject, $targetGroup, $bodyHtml);
            if (!$result['success']) {
                throw new Exception($result['error'] ?? "Échec de l'envoi de la campagne.");
            }

            echo json_encode([
                'success'         => true,
                'message'         => $result['message'],
                'recipient_count' => $result['recipient_count'],
                'stats'           => $mailingEngine->getStatistics()
            ]);
            break;

        // 14. Exporter la liste des abonnés au format CSV
        case 'export_subscribers_csv':
            DevTeamEngine::authorize('community.mailing', $currentUserId);
            require_once __DIR__ . '/../core/MailingListEngine.php';
            $mailingEngine = new MailingListEngine();

            $filters = [
                'search'  => trim((string)($_GET['search'] ?? '')),
                'optin'   => $_GET['optin'] ?? 'all',
                'status'  => $_GET['status'] ?? 'all',
                'role'    => $_GET['role'] ?? 'all',
                'faction' => $_GET['faction'] ?? 'all'
            ];

            $csv = $mailingEngine->exportCsv($filters);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="openshogun_abonnes_' . date('Y-m-d_His') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');
            echo $csv;
            exit;

        // 15. Historique des campagnes envoyées
        case 'get_campaign_history':
            DevTeamEngine::authorize('community.mailing', $currentUserId);
            require_once __DIR__ . '/../core/MailingListEngine.php';
            $mailingEngine = new MailingListEngine();

            $history = $mailingEngine->getCampaignHistory(20);
            echo json_encode(['success' => true, 'history' => $history]);
            break;

        // 16. Enregistrement des constantes et équilibrage des vitesses (Game Elevate Designer)
        case 'save_game_settings':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            if (!$devEngine->hasRole($currentUserId, 'game_designer')) {
                http_response_code(403);
                throw new Exception("Accès interdit : cette configuration est strictement réservée au métier Game Elevate Designer.");
            }

            $gameSpeed = max(1, min(100, (int)($_POST['game_speed'] ?? 5)));
            $resourceSpeed = max(1, min(100, (int)($_POST['resource_speed'] ?? 5)));
            $fleetSpeed = max(1, min(50, (int)($_POST['fleet_speed'] ?? 5)));
            $oasisDensity = max(0.5, min(20.0, (float)($_POST['oasis_density_percent'] ?? 2.0)));
            $oasisRespawn = !empty($_POST['oasis_respawn_on_capture']) && ($_POST['oasis_respawn_on_capture'] === '1' || $_POST['oasis_respawn_on_capture'] === 'true');
            $beginnerProtectionDays = max(0, min(365, (int)($_POST['beginner_protection_days'] ?? 7)));

            $famineEnabled = !empty($_POST['famine_enabled']) && ($_POST['famine_enabled'] === '1' || $_POST['famine_enabled'] === 'true');
            $famineRate = max(0.5, min(50.0, (float)($_POST['famine_rate'] ?? 3.0)));
            $famineConsumption = max(0.1, min(20.0, (float)($_POST['famine_flour_consumption'] ?? 1.0)));
            $heroCageDropRate = max(0, min(100, (int)($_POST['hero_cage_drop_rate'] ?? 25)));
            $heroXpRate = max(10, min(500, (int)($_POST['hero_xp_rate_percent'] ?? 100)));

            GameConfig::set('game_speed', $gameSpeed);
            GameConfig::set('resource_speed', $resourceSpeed);
            GameConfig::set('fleet_speed', $fleetSpeed);
            GameConfig::set('oasis_density_percent', $oasisDensity);
            GameConfig::set('oasis_respawn_on_capture', $oasisRespawn ? 1 : 0);
            GameConfig::set('beginner_protection_days', $beginnerProtectionDays);

            GameConfig::set('famine_enabled', $famineEnabled ? 1 : 0);
            GameConfig::set('famine_rate', $famineRate);
            GameConfig::set('famine_flour_consumption', $famineConsumption);
            GameConfig::set('hero_cage_drop_rate', $heroCageDropRate);
            GameConfig::set('hero_xp_rate_percent', $heroXpRate);

            $devEngine->addForgeXp($currentUserId, 30, 'game_balance', "Ajustement des constantes et équilibrage des vitesses (Game Elevate Designer)");

            echo json_encode([
                'success' => true,
                'message' => "Constantes et équilibrage du jeu sauvegardés avec succès ! (+30 XP Forge)"
            ]);
            break;

        // 17. Expansion et arpentage des provinces (Game Elevate Designer)
        case 'generate_world':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            if (!$devEngine->hasRole($currentUserId, 'game_designer')) {
                http_response_code(403);
                throw new Exception("Accès interdit : l'arpentage et l'expansion des provinces sont strictement réservés au métier Game Elevate Designer.");
            }

            $count = max(1, min(100, (int)($_POST['planet_count'] ?? 12)));
            $radius = max(5, min(50, (int)($_POST['radius'] ?? 10)));
            $clearUninhabited = !empty($_POST['clear_uninhabited']) && ($_POST['clear_uninhabited'] === '1' || $_POST['clear_uninhabited'] === 'true');
            $types = !empty($_POST['types']) && is_array($_POST['types']) ? $_POST['types'] : [];

            $worldGen = new WorldGenerator();
            $res = $worldGen->generatePlanets($count, $radius, $types, $clearUninhabited);

            if (!empty($res['success'])) {
                $devEngine->addForgeXp($currentUserId, 40, 'world_expansion', "Arpentage procédural et déploiement de {$res['generated_count']} terres (Game Elevate Designer)");
            }

            echo json_encode($res);
            break;

        // 18. Rééquilibrage et génération des oasis (Game Elevate Designer)
        case 'repopulate_oases':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Méthode invalide.");
            if (!$devEngine->hasRole($currentUserId, 'game_designer')) {
                http_response_code(403);
                throw new Exception("Accès interdit : la gestion de l'écosystème des oasis est strictement réservée au métier Game Elevate Designer.");
            }

            $oasisEngine = new OasisEngine();
            $density = max(0.5, min(20.0, (float)($_POST['density_percent'] ?? GameConfig::get('oasis_density_percent', 2.0))));
            $radius = max(10, min(50, (int)($_POST['radius'] ?? 28)));
            $clearUnoccupied = !empty($_POST['clear_unoccupied']) && ($_POST['clear_unoccupied'] === '1' || $_POST['clear_unoccupied'] === 'true');

            GameConfig::set('oasis_density_percent', $density);
            $res = $oasisEngine->spawnOasesByDensity($density, $radius, $clearUnoccupied);

            if (!empty($res['success'])) {
                $devEngine->addForgeXp($currentUserId, 35, 'oases_repopulate', "Rééquilibrage de l'écosystème des oasis et de la faune (Game Elevate Designer)");
            }

            echo json_encode($res);
            break;

        default:
            throw new Exception("Action Dev Team non reconnue.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
