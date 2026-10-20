<?php
/**
 * Game Studio - Contrôleur et Vue Maîtresse (Architecture MVC)
 * Plateforme Unifiée du Studio de Développement et de l'Administration Générale
 * Fichier : views/gamestudio.php
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/GameStudioEngine.php';
require_once __DIR__ . '/../core/ActivityTracker.php';

$auth = new Auth();
if (!Auth::check()) {
    header('Location: /');
    exit;
}

$currentUser = $auth->getCurrentUser();
$userId = (int)$currentUser['id'];

$gameStudioEngine = new GameStudioEngine();
if (!$gameStudioEngine->isAuthorized($userId)) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Accès Réservé - Game Studio</title>
        <link rel="stylesheet" href="/public/css/tabler/tabler.min.css?v=1.0.0-beta21">
        <link rel="stylesheet" href="/public/fontawesome/css/all.min.css">
    </head>
    <body class="d-flex flex-column justify-content-center min-vh-100 bg-light">
        <div class="container-xl py-5 text-center">
            <div class="empty">
                <div class="empty-icon text-danger" style="font-size: 3.5rem;"><i class="fa-solid fa-lock"></i></div>
                <p class="empty-title fs-2 fw-bold">Accès Réservé au Game Studio</p>
                <p class="empty-subtitle text-muted fs-4">
                    Cet espace est strictement réservé aux <strong>Administrateurs</strong> et membres de la <strong>Dev Team</strong> d'OpenShogun.
                </p>
                <div class="empty-action mt-4">
                    <a href="/?page=resources" class="btn btn-primary btn-lg">&larr; Retour au Fief Provincial</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Alias de redirection directe vers les sous-onglets de Général
if (($_GET['tab'] ?? '') === 'updates') {
    header('Location: ?page=gamestudio&tab=general&subtab=updates');
    exit;
}
if (($_GET['tab'] ?? '') === 'backups') {
    header('Location: ?page=gamestudio&tab=general&subtab=backups');
    exit;
}
if (in_array($_GET['tab'] ?? '', ['world', 'world_config', 'elevate_speed', 'elevate_survey', 'elevate_oases'], true)) {
    header('Location: ?page=gamestudio&tab=general&subtab=world_config');
    exit;
}

// 1. Détection de l'onglet actif dans la barre d'outils
$allowedTabs = [
    'dashboard', 'general',
    'elevate_research', 'elevate_bots',
    'elevate_combat', 'elevate_castles', 'elevate_village', 'elevate_buildings',
    'elevate_events', 'elevate_weekly_events',
    'narrative_scenarios', 'narrative_storyboard', 'narrative_lore',
    'liveops_events', 'liveops_features', 'liveops_broadcast', 'liveops_support',
    'dev_tickets', 'dev_backend_tasks'
];
$currentTab = in_array($_GET['tab'] ?? '', $allowedTabs, true) ? $_GET['tab'] : 'dashboard';
$currentSubTab = in_array($_GET['subtab'] ?? '', ['overview', 'world_config', 'backups', 'updates'], true) ? $_GET['subtab'] : 'overview';
$analyticsPeriod = in_array($_GET['period'] ?? '', ['today', '7d', '30d', 'all'], true) ? (string)$_GET['period'] : '30d';

// 2. Préparation des données selon l'onglet
if ($currentTab === 'dashboard') {
    $dashboardData = $gameStudioEngine->getDashboardData($analyticsPeriod);
    extract($dashboardData);
} elseif ($currentTab === 'general') {
    // Traitement des actions en POST pour Général
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'save_all_world_settings') {
            $warnings = [];

            // 1. Rayon du monde
            if (isset($_POST['world_radius'])) {
                $radiusRes = $gameStudioEngine->saveWorldRadius((int)$_POST['world_radius']);
                if (!empty($radiusRes['warning'])) {
                    $warnings[] = $radiusRes['warning'];
                }
            }

            // 2. Oasis
            if (isset($_POST['oasis_density_percent'])) {
                $gameStudioEngine->saveOasisSettings(
                    (float)$_POST['oasis_density_percent'],
                    !empty($_POST['oasis_respawn_on_capture'])
                );
            }

            // 3. Vitesses & Équilibrage
            $gameStudioEngine->saveSpeedBalancing($_POST);

            // 4. Dotation Initiale & Immunité Débutant
            $gameStudioEngine->saveStartingEndowment($_POST);

            // 5. Clans IA & Daimyōs Autonomes
            $gameStudioEngine->saveBotsSettings($_POST);

            // 6. Option de régénération
            if (isset($_POST['preserve_human_users'])) {
                $gameStudioEngine->saveRegenerationSettings(!empty($_POST['preserve_human_users']));
            }

            $_SESSION['general_flash'] = [
                'type' => 'success',
                'msg'  => "Ensemble des paramètres de la configuration du monde enregistré avec succès !" . (!empty($warnings) ? ' ' . implode(' ', $warnings) : '')
            ];
            $tabHash = !empty($_POST['active_config_tab']) ? '#' . preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$_POST['active_config_tab']) : '';
            header('Location: ?page=gamestudio&tab=general&subtab=world_config' . $tabHash);
            exit;
        } elseif ($_POST['action'] === 'save_world_radius') {
            $radius = (int)($_POST['world_radius'] ?? 35);
            $res = $gameStudioEngine->saveWorldRadius($radius);
            $_SESSION['general_flash'] = [
                'type' => $res['success'] ? (!empty($res['warning']) ? 'warning' : 'success') : 'danger',
                'msg'  => $res['message'] . (!empty($res['warning']) ? ' ' . $res['warning'] : '')
            ];
            header('Location: ?page=gamestudio&tab=general&subtab=world_config');
            exit;
        } elseif ($_POST['action'] === 'save_oasis_settings') {
            $density = (float)($_POST['oasis_density_percent'] ?? 2.0);
            $respawn = !empty($_POST['oasis_respawn_on_capture']);
            $res = $gameStudioEngine->saveOasisSettings($density, $respawn);
            $_SESSION['general_flash'] = [
                'type' => $res['success'] ? 'success' : 'danger',
                'msg'  => $res['message']
            ];
            header('Location: ?page=gamestudio&tab=general&subtab=world_config');
            exit;
        } elseif ($_POST['action'] === 'repopulate_world_oases') {
            $density = (float)($_POST['oasis_density_percent'] ?? 2.0);
            $clearUnoccupied = !empty($_POST['clear_unoccupied']);
            $res = $gameStudioEngine->repopulateWorldOases($density, null, $clearUnoccupied);
            $_SESSION['general_flash'] = [
                'type' => !empty($res['success']) ? 'success' : 'danger',
                'msg'  => $res['message'] ?? 'Génération des oasis terminée.'
            ];
            header('Location: ?page=gamestudio&tab=general&subtab=world_config');
            exit;
        } elseif ($_POST['action'] === 'respawn_wild_fauna') {
            $res = $gameStudioEngine->respawnWildFauna();
            $_SESSION['general_flash'] = [
                'type' => !empty($res['success']) ? 'success' : 'danger',
                'msg'  => $res['message'] ?? 'Faune sauvage régénérée.'
            ];
            header('Location: ?page=gamestudio&tab=general&subtab=world_config#section-world-oases-config');
            exit;
        } elseif ($_POST['action'] === 'save_world_speed_balancing') {
            $res = $gameStudioEngine->saveSpeedBalancing($_POST);
            $_SESSION['general_flash'] = [
                'type' => !empty($res['success']) ? 'success' : 'danger',
                'msg'  => $res['message'] ?? 'Vitesses et équilibrage enregistrés.'
            ];
            header('Location: ?page=gamestudio&tab=general&subtab=world_config#section-world-speed-config');
            exit;
        } elseif ($_POST['action'] === 'save_world_starting_endowment') {
            $res = $gameStudioEngine->saveStartingEndowment($_POST);
            $_SESSION['general_flash'] = [
                'type' => !empty($res['success']) ? 'success' : 'danger',
                'msg'  => $res['message'] ?? 'Dotation initiale et immunité enregistrées.'
            ];
            header('Location: ?page=gamestudio&tab=general&subtab=world_config#section-world-starting-endowment');
            exit;
        } elseif ($_POST['action'] === 'save_world_bots_settings') {
            $res = $gameStudioEngine->saveBotsSettings($_POST);
            $_SESSION['general_flash'] = [
                'type' => !empty($res['success']) ? 'success' : 'danger',
                'msg'  => $res['message'] ?? 'Directives des Clans IA enregistrées.'
            ];
            header('Location: ?page=gamestudio&tab=general&subtab=world_config#section-world-bots-config');
            exit;
        } elseif ($_POST['action'] === 'spawn_preset_bots') {
            $count = max(1, min(10, (int)($_POST['count'] ?? 3)));
            $res = $gameStudioEngine->spawnPresetBots($count);
            $_SESSION['general_flash'] = [
                'type' => !empty($res['success']) ? 'success' : 'danger',
                'msg'  => $res['message'] ?? 'Déploiement des seigneurs IA terminé.'
            ];
            header('Location: ?page=gamestudio&tab=general&subtab=world_config#section-world-bots-config');
            exit;
        } elseif ($_POST['action'] === 'trigger_spontaneous_spawn') {
            $res = $gameStudioEngine->triggerSpontaneousBotSpawn();
            $_SESSION['general_flash'] = [
                'type' => !empty($res['success']) ? 'success' : 'danger',
                'msg'  => $res['message'] ?? 'Éclosion spontanée PNJ terminée.'
            ];
            header('Location: ?page=gamestudio&tab=general&subtab=world_config#section-world-bots-config');
            exit;
        } elseif ($_POST['action'] === 'run_bot_cycle') {
            $res = $gameStudioEngine->runBotCycle();
            $_SESSION['general_flash'] = [
                'type' => !empty($res['success']) ? 'success' : 'danger',
                'msg'  => $res['message'] ?? 'Cycle IA terminé.'
            ];
            header('Location: ?page=gamestudio&tab=general&subtab=world_config#section-world-bots-config');
            exit;
        } elseif ($_POST['action'] === 'save_world_regeneration_settings') {
            $preserve = !empty($_POST['preserve_human_users']);
            $res = $gameStudioEngine->saveRegenerationSettings($preserve);
            $_SESSION['general_flash'] = [
                'type' => !empty($res['success']) ? 'success' : 'danger',
                'msg'  => $res['message'] ?? 'Option de régénération enregistrée.'
            ];
            header('Location: ?page=gamestudio&tab=general&subtab=world_config#section-world-regeneration-config');
            exit;
        } elseif ($_POST['action'] === 'regenerate_world_action') {
            $confirmKeyword = strtoupper(trim((string)($_POST['confirm_keyword'] ?? '')));
            if ($confirmKeyword !== 'RESET') {
                $_SESSION['general_flash'] = [
                    'type' => 'danger',
                    'msg'  => "Échec de régénération : Vous devez impérativement saisir le mot-clé RESET pour confirmer."
                ];
            } else {
                $preserve = !empty($_POST['preserve_human_users']);
                $neutralCount = max(5, min(50, (int)($_POST['neutral_planets'] ?? 12)));
                $deployBots = !empty($_POST['deploy_bots']);
                $adminPassword = !empty($_POST['admin_password']) ? trim((string)$_POST['admin_password']) : 'Gabriel125#';

                $res = $gameStudioEngine->regenerateWorld($preserve, $neutralCount, $deployBots, $adminPassword);
                $_SESSION['general_flash'] = [
                    'type' => !empty($res['success']) ? 'success' : 'danger',
                    'msg'  => $res['message'] ?? 'Régénération du monde terminée.'
                ];
            }
            header('Location: ?page=gamestudio&tab=general&subtab=world_config#section-world-regeneration-config');
            exit;
        }
    }
    $generalData = $gameStudioEngine->getGeneralData();
    extract($generalData);
    if ($currentSubTab === 'world_config') {
        $worldConfigData = $gameStudioEngine->getWorldConfigData();
        extract($worldConfigData);
    }
} elseif ($currentTab === 'elevate_research') {
    require_once __DIR__ . '/../core/ResearchElevateService.php';
    $elevateService = new ResearchElevateService();

    // Traitement du formulaire POST avant tout envoi de contenu HTML
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'save_global_coefficients') {
            $elevateService->updateGlobalCoefficients(
                (float)($_POST['research_time_coeff'] ?? 1.0),
                (float)($_POST['research_time_growth'] ?? 1.35),
                (float)($_POST['research_cost_coeff'] ?? 1.0),
                (float)($_POST['research_cost_growth'] ?? 1.65)
            );
            $_SESSION['elevate_flash'] = ['type' => 'success', 'msg' => 'Coefficients exponentiels de recherche enregistrés avec succès !'];
            header('Location: ?page=gamestudio&tab=elevate_research');
            exit;
        } elseif ($_POST['action'] === 'save_custom_research') {
            $elevateService->saveResearch($_POST);
            $_SESSION['elevate_flash'] = ['type' => 'success', 'msg' => 'Nouvelle technologie enregistrée dans l\'Académie des Savoirs !'];
            header('Location: ?page=gamestudio&tab=elevate_research');
            exit;
        }
    }

    $elevateData = $elevateService->getViewData();
    extract($elevateData);
} elseif ($currentTab === 'elevate_bots') {
    require_once __DIR__ . '/../core/BotEngine.php';
    require_once __DIR__ . '/../core/GameConfig.php';
    $botEngine = new BotEngine();
    $settings = GameConfig::load();
    $botsList = $botEngine->getBots();
    $botSpawnStatus = $botEngine->getBotSpawnStatus();
} elseif (str_starts_with($currentTab, 'liveops_')) {
    require_once __DIR__ . '/../core/LiveOpsEngine.php';
    $liveOpsEngine = new LiveOpsEngine();

    // Traitement des actions Live Ops en POST
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];
        try {
            if ($action === 'toggle_feature_flag') {
                $key = $_POST['key'] ?? '';
                $enabled = !empty($_POST['enabled']);
                $liveOpsEngine->setFeatureFlag($key, $enabled);
                $_SESSION['liveops_flash'] = ['type' => 'success', 'msg' => 'Feature Flag « ' . htmlspecialchars($key) . ' » mis à jour (' . ($enabled ? 'Activé' : 'Désactivé') . ') !'];
            } elseif ($action === 'trigger_live_event') {
                $res = $liveOpsEngine->triggerLiveEvent($_POST);
                $_SESSION['liveops_flash'] = ['type' => 'success', 'msg' => 'Événement Live « ' . htmlspecialchars($res['event']['title']) . ' » proclamé avec succès !'];
            } elseif ($action === 'stop_live_event') {
                $eventId = $_POST['event_id'] ?? '';
                $liveOpsEngine->stopLiveEvent($eventId);
                $_SESSION['liveops_flash'] = ['type' => 'info', 'msg' => 'L\'événement a été clos prématurément.'];
            } elseif ($action === 'set_broadcast') {
                $liveOpsEngine->setBroadcast(
                    $_POST['title'] ?? '',
                    $_POST['message'] ?? '',
                    $_POST['type'] ?? 'info',
                    (int)($_POST['duration_minutes'] ?? 60),
                    true
                );
                $_SESSION['liveops_flash'] = ['type' => 'success', 'msg' => 'Bannière Broadcast In-Game déployée en direct !'];
            } elseif ($action === 'clear_broadcast') {
                $liveOpsEngine->clearBroadcast();
                $_SESSION['liveops_flash'] = ['type' => 'info', 'msg' => 'Bannière Broadcast révoquée avec succès.'];
            } elseif ($action === 'set_maintenance') {
                $isActive = !empty($_POST['is_active']);
                $msg = $_POST['message'] ?? '';
                $sched = !empty($_POST['scheduled_end']) ? trim($_POST['scheduled_end']) : null;
                $whitelistRaw = $_POST['whitelist'] ?? '';
                $wl = array_filter(array_map('trim', explode(',', $whitelistRaw)));
                if (empty($wl)) $wl = [$userId];
                $liveOpsEngine->setMaintenance($isActive, $msg, $sched, $wl);
                $_SESSION['liveops_flash'] = ['type' => $isActive ? 'warning' : 'success', 'msg' => $isActive ? '⚠️ Mode Maintenance ACTIVÉ sur l\'univers !' : 'Mode Maintenance DÉSACTIVÉ. Forteresse ouverte à tous.'];
            } elseif ($action === 'send_compensation') {
                $sendToAll = !empty($_POST['send_to_all']);
                $targetUserId = $sendToAll ? null : (int)($_POST['user_id'] ?? 0);
                $res = $liveOpsEngine->sendCompensation(
                    $targetUserId,
                    [
                        'metal'     => (int)($_POST['metal'] ?? 0),
                        'crystal'   => (int)($_POST['crystal'] ?? 0),
                        'deuterium' => (int)($_POST['deuterium'] ?? 0),
                        'gold'      => (int)($_POST['gold'] ?? 0),
                        'honor'     => (int)($_POST['honor'] ?? 0)
                    ],
                    $_POST['reason'] ?? 'Décret de dédommagement officiel du Shōgunat',
                    $userId,
                    $sendToAll
                );
                $_SESSION['liveops_flash'] = ['type' => 'success', 'msg' => $res['message']];
            }
        } catch (Throwable $e) {
            $_SESSION['liveops_flash'] = ['type' => 'danger', 'msg' => 'Erreur : ' . $e->getMessage()];
        }
        header('Location: ?page=gamestudio&tab=' . $currentTab);
        exit;
    }
} elseif ($currentTab === 'dev_tickets') {
    require_once __DIR__ . '/../core/SupportEngine.php';
    $supportEngine = new SupportEngine();

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];
        try {
            if ($action === 'update_ticket_status') {
                $ticketId = (int)($_POST['ticket_id'] ?? 0);
                $newStatus = (string)($_POST['status'] ?? 'pending');
                $adminResponse = (string)($_POST['admin_response'] ?? '');
                $notifyUser = !empty($_POST['notify_user']);
                $adminResponseSanitized = SupportEngine::sanitizeHtml($adminResponse);

                $res = $supportEngine->updateTicketStatus(
                    $ticketId,
                    $newStatus,
                    $adminResponseSanitized ?: null,
                    $userId,
                    $notifyUser
                );
                $_SESSION['dev_flash'] = [
                    'type' => 'success',
                    'msg' => "Ticket #$ticketId mis à jour avec succès (Nouveau statut : $newStatus) !"
                ];
            } elseif ($action === 'delete_ticket') {
                $ticketId = (int)($_POST['ticket_id'] ?? 0);
                $deleted = $supportEngine->deleteTicket($ticketId);
                $_SESSION['dev_flash'] = [
                    'type' => $deleted ? 'success' : 'danger',
                    'msg' => $deleted ? "Ticket #$ticketId supprimé avec succès." : "Échec de la suppression du ticket #$ticketId."
                ];
            }
        } catch (Throwable $e) {
            $_SESSION['dev_flash'] = ['type' => 'danger', 'msg' => 'Erreur : ' . $e->getMessage()];
        }
        header('Location: ?page=gamestudio&tab=dev_tickets' . (!empty($_POST['filter_params']) ? '&' . ltrim($_POST['filter_params'], '&') : ''));
        exit;
    }
} elseif ($currentTab === 'dev_backend_tasks') {
    require_once __DIR__ . '/../core/BackendTaskEngine.php';
    $backendTaskEngine = new BackendTaskEngine();

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
        $action = (string)$_POST['action'];

        // Micro-actions AJAX
        if ($action === 'update_status_ajax') {
            while (ob_get_level() > 0) ob_end_clean();
            header('Content-Type: application/json; charset=UTF-8');
            $taskId = (int)($_POST['task_id'] ?? 0);
            $newStatus = (string)($_POST['status'] ?? 'todo');
            $success = $backendTaskEngine->updateTaskStatus($taskId, $newStatus);
            echo json_encode([
                'success' => $success,
                'metrics' => $backendTaskEngine->getMetrics()
            ]);
            exit;
        }

        if ($action === 'sync_scenarios_ajax') {
            while (ob_get_level() > 0) ob_end_clean();
            header('Content-Type: application/json; charset=UTF-8');
            $res = $backendTaskEngine->syncTasksFromScenarios();
            echo json_encode([
                'success' => true,
                'result'  => $res,
                'metrics' => $backendTaskEngine->getMetrics()
            ]);
            exit;
        }

        $redirectUrl = '?page=gamestudio&tab=dev_backend_tasks';

        try {
            if ($action === 'create_task') {
                $newId = $backendTaskEngine->createTask($_POST);
                $_SESSION['dev_backend_flash'] = [
                    'type' => $newId > 0 ? 'success' : 'danger',
                    'msg'  => $newId > 0 ? "Tâche Back-End #$newId créée avec succès !" : "Erreur lors de la création de la tâche."
                ];
                if ($newId > 0 && !empty($_POST['redirect_to_detail'])) {
                    $redirectUrl .= '&task_id=' . $newId;
                }
            } elseif ($action === 'update_task') {
                $taskId = (int)($_POST['task_id'] ?? 0);
                $ok = $backendTaskEngine->updateTask($taskId, $_POST);
                $_SESSION['dev_backend_flash'] = [
                    'type' => $ok ? 'success' : 'danger',
                    'msg'  => $ok ? "Tâche Back-End #$taskId mise à jour avec succès !" : "Erreur lors de la mise à jour."
                ];
                if (!empty($_POST['redirect_to_detail']) && $taskId > 0) {
                    $redirectUrl .= '&task_id=' . $taskId;
                }
            } elseif ($action === 'delete_task') {
                $taskId = (int)($_POST['task_id'] ?? 0);
                $ok = $backendTaskEngine->deleteTask($taskId);
                $_SESSION['dev_backend_flash'] = [
                    'type' => $ok ? 'success' : 'danger',
                    'msg'  => $ok ? "Tâche Back-End #$taskId supprimée avec succès." : "Erreur lors de la suppression."
                ];
            } elseif ($action === 'sync_scenarios') {
                $res = $backendTaskEngine->syncTasksFromScenarios();
                $_SESSION['dev_backend_flash'] = [
                    'type' => 'success',
                    'msg'  => "Synchronisation effectuée : {$res['imported']} tâche(s) importée(s), {$res['updated']} actualisée(s) sur {$res['total']} tâche(s) de scénarios trouvée(s)."
                ];
            } elseif ($action === 'add_annotation') {
                $taskId = (int)($_POST['task_id'] ?? 0);
                $content = trim((string)($_POST['content'] ?? ''));
                $authorName = trim((string)($_POST['author_name'] ?? '')) ?: ($currentUser['username'] ?? 'Développeur Back-End');
                if ($taskId > 0 && $content !== '') {
                    $annId = $backendTaskEngine->addAnnotation($taskId, [
                        'user_id'     => $userId,
                        'author_name' => $authorName,
                        'content'     => $content
                    ]);
                    $_SESSION['dev_backend_flash'] = [
                        'type' => $annId > 0 ? 'success' : 'danger',
                        'msg'  => $annId > 0 ? "Annotation ajoutée avec succès !" : "Erreur lors de l'ajout de l'annotation."
                    ];
                } else {
                    $_SESSION['dev_backend_flash'] = ['type' => 'warning', 'msg' => "Le contenu de l'annotation ne peut pas être vide."];
                }
                $redirectUrl .= '&task_id=' . $taskId;
            } elseif ($action === 'delete_annotation') {
                $taskId = (int)($_POST['task_id'] ?? 0);
                $annotationId = (int)($_POST['annotation_id'] ?? 0);
                $ok = $backendTaskEngine->deleteAnnotation($annotationId);
                $_SESSION['dev_backend_flash'] = [
                    'type' => $ok ? 'success' : 'danger',
                    'msg'  => $ok ? "Annotation supprimée." : "Erreur lors de la suppression de l'annotation."
                ];
                if ($taskId > 0) {
                    $redirectUrl .= '&task_id=' . $taskId;
                }
            }
        } catch (Throwable $e) {
            $_SESSION['dev_backend_flash'] = ['type' => 'danger', 'msg' => 'Erreur : ' . $e->getMessage()];
        }
        header('Location: ' . $redirectUrl);
        exit;
    }
} elseif ($currentTab === 'narrative_scenarios') {
    require_once __DIR__ . '/../core/ScenarioEngine.php';
    $scenarioEngine = new ScenarioEngine();

    // Exportation Markdown GDD en GET direct
    if (($_GET['action'] ?? '') === 'export_markdown') {
        $scId = (int)($_GET['scenario_id'] ?? 0);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/plain; charset=UTF-8');
        echo $scenarioEngine->exportMarkdown($scId);
        exit;
    }

    // Traitement des actions de formulaire et AJAX en POST
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];
        $isAjax = !empty($_POST['ajax']) || str_starts_with($action, 'ajax_');

        try {
            if ($action === 'ajax_update_task_status') {
                header('Content-Type: application/json; charset=UTF-8');
                $scId = (int)($_POST['scenario_id'] ?? 0);
                $taskId = trim((string)($_POST['task_id'] ?? ''));
                $taskStatus = (string)($_POST['task_status'] ?? 'todo');
                $updated = $scenarioEngine->updateTaskStatus($scId, $taskId, $taskStatus);
                $sc = $scenarioEngine->getScenarioById($scId);
                echo json_encode([
                    'ok'          => $updated,
                    'scenario_id' => $scId,
                    'task_id'     => $taskId,
                    'task_status' => $taskStatus,
                    'metrics'     => $sc['metrics'] ?? null,
                    'msg'         => $updated ? "Tâche #$taskId passée à « $taskStatus »." : "Impossible de modifier la tâche."
                ]);
                exit;
            } elseif ($action === 'ajax_update_scenario_status') {
                header('Content-Type: application/json; charset=UTF-8');
                $scId = (int)($_POST['scenario_id'] ?? 0);
                $newStatus = (string)($_POST['status'] ?? 'draft');
                $updated = $scenarioEngine->updateScenarioStatus($scId, $newStatus);
                $sc = $scenarioEngine->getScenarioById($scId);
                echo json_encode([
                    'ok'          => $updated,
                    'scenario_id' => $scId,
                    'status'      => $sc['status'] ?? $newStatus,
                    'metrics'     => $sc['metrics'] ?? null,
                    'msg'         => $updated ? "Statut du scénario #$scId mis à jour !" : "Impossible de modifier le statut."
                ]);
                exit;
            } elseif ($action === 'ajax_set_scenario_pipeline_anchor') {
                header('Content-Type: application/json; charset=UTF-8');
                $scId = (int)($_POST['scenario_id'] ?? 0);
                $afterStep = isset($_POST['after_step']) && $_POST['after_step'] !== '' && $_POST['after_step'] !== 'null'
                    ? (int)$_POST['after_step']
                    : null;
                $sequenceOrder = (int)($_POST['sequence_order'] ?? 0);
                $updated = $scenarioEngine->updateScenarioPipelinePosition($scId, $afterStep, $sequenceOrder);
                $sc = $scenarioEngine->getScenarioById($scId);
                echo json_encode([
                    'ok'                  => $updated,
                    'scenario_id'         => $scId,
                    'position_after_step' => $sc['position_after_step'] ?? $afterStep,
                    'sequence_order'      => $sc['sequence_order'] ?? $sequenceOrder,
                    'msg'                 => $updated ? "Position de l'événement dans le pipeline mise à jour !" : "Impossible de modifier la position."
                ]);
                exit;
            } elseif ($action === 'ajax_delete_scenario') {
                header('Content-Type: application/json; charset=UTF-8');
                $scId = (int)($_POST['scenario_id'] ?? 0);
                $deleted = $scenarioEngine->deleteScenario($scId);
                echo json_encode([
                    'ok'          => $deleted,
                    'scenario_id' => $scId,
                    'msg'         => $deleted ? "Scénario #$scId supprimé définitivement." : "Échec de la suppression du scénario #$scId."
                ]);
                exit;
            } elseif ($action === 'ajax_save_scenario') {
                header('Content-Type: application/json; charset=UTF-8');
                $scId = (int)($_POST['scenario_id'] ?? 0);
                if ($scId > 0) {
                    $updated = $scenarioEngine->updateScenario($scId, $_POST);
                    $sc = $scenarioEngine->getScenarioById($scId);
                    echo json_encode([
                        'ok'          => $updated,
                        'scenario_id' => $scId,
                        'is_new'      => false,
                        'metrics'     => $sc['metrics'] ?? null,
                        'msg'         => $updated ? "Scénario #$scId sauvegardé en direct !" : "Erreur lors de la sauvegarde."
                    ]);
                } else {
                    $newId = $scenarioEngine->saveScenarioFull($_POST);
                    $sc = $scenarioEngine->getScenarioById($newId);
                    echo json_encode([
                        'ok'          => $newId > 0,
                        'scenario_id' => $newId,
                        'is_new'      => true,
                        'redirect'    => "?page=gamestudio&tab=narrative_scenarios&action=edit&id=$newId",
                        'metrics'     => $sc['metrics'] ?? null,
                        'msg'         => "Nouveau scénario créé (#$newId) !"
                    ]);
                }
                exit;
            } elseif ($action === 'create_scenario') {
                $newId = $scenarioEngine->saveScenarioFull($_POST);
                $_SESSION['scenario_flash'] = [
                    'type' => 'success',
                    'msg'  => "Scénario créé avec succès (ID #$newId) avec ses tâches métiers !"
                ];
                $redirectUrl = (!empty($_POST['redirect_to']) && $_POST['redirect_to'] === 'catalog')
                    ? '?page=gamestudio&tab=narrative_scenarios'
                    : "?page=gamestudio&tab=narrative_scenarios&action=edit&id=$newId";
                header('Location: ' . $redirectUrl);
                exit;
            } elseif ($action === 'import_markdown_scenario') {
                $mdContent = trim((string)($_POST['md_content'] ?? ''));
                $filename  = trim((string)($_POST['md_filename'] ?? ''));
                if (!empty($_FILES['md_file']['tmp_name']) && is_uploaded_file($_FILES['md_file']['tmp_name'])) {
                    $mdContent = (string)file_get_contents($_FILES['md_file']['tmp_name']);
                    $filename  = (string)($_FILES['md_file']['name'] ?? $filename);
                }
                $parsed = $scenarioEngine->parseMarkdownToScenario($mdContent, $filename);
                $payload = [
                    'type'              => !empty($_POST['type']) ? (string)$_POST['type'] : 'step',
                    'step_number'       => $_POST['step_number'] ?? null,
                    'status'            => 'draft',
                    'title'             => !empty($_POST['title']) ? trim((string)$_POST['title']) : $parsed['title'],
                    'subtitle'          => !empty($_POST['subtitle']) ? trim((string)$_POST['subtitle']) : $parsed['subtitle'],
                    'era'               => !empty($_POST['era']) ? trim((string)$_POST['era']) : $parsed['era'],
                    'synopsis'          => $parsed['synopsis'],
                    'speaker_name'      => $parsed['speaker_name'] ?: 'Takayama Kagetora (Maître Artificier)',
                    'speaker_role'      => $parsed['speaker_role'] ?: 'Chroniqueur & Stratège du Shōgunat',
                    'speaker_avatar'    => $parsed['speaker_avatar'] ?: '🏯',
                    'dialogue'          => $parsed['dialogue'] ?: 'Noble Daimyō, voici les rouleaux stratégiques importés dans les archives du Shōgunat.',
                    'trigger_condition' => $parsed['trigger_condition'],
                    'rewards_summary'   => $parsed['rewards_summary'],
                    'created_by'        => $userId
                ];
                $newId = $scenarioEngine->saveScenarioFull($payload);
                $_SESSION['scenario_flash'] = [
                    'type' => 'success',
                    'msg'  => "Fichier Markdown « " . ($filename ?: 'GDD.md') . " » importé avec succès dans le Synopsis & Contexte Historique (Scénario #$newId) !"
                ];
                header("Location: ?page=gamestudio&tab=narrative_scenarios&action=edit&id=$newId");
                exit;
            } elseif ($action === 'update_scenario') {
                $scId = (int)($_POST['scenario_id'] ?? 0);
                $updated = $scenarioEngine->updateScenario($scId, $_POST);
                $_SESSION['scenario_flash'] = [
                    'type' => $updated ? 'success' : 'danger',
                    'msg'  => $updated ? "Scénario #$scId et ses tâches métiers enregistrés avec succès !" : "Erreur lors de la mise à jour du scénario #$scId."
                ];
                $redirectUrl = (!empty($_POST['redirect_to']) && $_POST['redirect_to'] === 'catalog')
                    ? '?page=gamestudio&tab=narrative_scenarios'
                    : "?page=gamestudio&tab=narrative_scenarios&action=edit&id=$scId";
                header('Location: ' . $redirectUrl);
                exit;
            } elseif ($action === 'update_scenario_status') {
                $scId = (int)($_POST['scenario_id'] ?? 0);
                $newStatus = (string)($_POST['status'] ?? 'draft');
                $updated = $scenarioEngine->updateScenarioStatus($scId, $newStatus);
                $_SESSION['scenario_flash'] = [
                    'type' => $updated ? 'success' : 'warning',
                    'msg'  => $updated ? "Statut du scénario #$scId mis à jour." : "Impossible de modifier le statut."
                ];
                header('Location: ?page=gamestudio&tab=narrative_scenarios');
                exit;
            } elseif ($action === 'update_task_status') {
                $scId = (int)($_POST['scenario_id'] ?? 0);
                $taskId = trim((string)($_POST['task_id'] ?? ''));
                $taskStatus = (string)($_POST['task_status'] ?? 'todo');
                $updated = $scenarioEngine->updateTaskStatus($scId, $taskId, $taskStatus);
                $_SESSION['scenario_flash'] = [
                    'type' => $updated ? 'success' : 'warning',
                    'msg'  => $updated ? "Statut de la tâche #$taskId mis à jour vers « $taskStatus »." : "Impossible de modifier la tâche."
                ];
                $returnAction = !empty($_POST['return_action']) ? $_POST['return_action'] : 'catalog';
                $redirectUrl = ($returnAction === 'edit' && $scId > 0)
                    ? "?page=gamestudio&tab=narrative_scenarios&action=edit&id=$scId"
                    : '?page=gamestudio&tab=narrative_scenarios';
                header('Location: ' . $redirectUrl);
                exit;
            } elseif ($action === 'duplicate_scenario') {
                $scId = (int)($_POST['scenario_id'] ?? 0);
                $clone = $scenarioEngine->duplicateScenario($scId, $userId);
                $_SESSION['scenario_flash'] = [
                    'type' => $clone ? 'success' : 'danger',
                    'msg'  => $clone ? "Scénario dupliqué avec succès (Nouveau ID #" . $clone['id'] . ") !" : "Échec de la duplication."
                ];
                $newId = $clone['id'] ?? 0;
                $redirectUrl = ($newId > 0)
                    ? "?page=gamestudio&tab=narrative_scenarios&action=edit&id=$newId"
                    : '?page=gamestudio&tab=narrative_scenarios';
                header('Location: ' . $redirectUrl);
                exit;
            } elseif ($action === 'delete_scenario') {
                $scId = (int)($_POST['scenario_id'] ?? 0);
                $deleted = $scenarioEngine->deleteScenario($scId);
                $_SESSION['scenario_flash'] = [
                    'type' => $deleted ? 'success' : 'danger',
                    'msg'  => $deleted ? "Scénario #$scId supprimé définitivement." : "Échec de la suppression."
                ];
                header('Location: ?page=gamestudio&tab=narrative_scenarios');
                exit;
            }
        } catch (Throwable $e) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok' => false, 'msg' => 'Erreur : ' . $e->getMessage()]);
                exit;
            }
            $_SESSION['scenario_flash'] = ['type' => 'danger', 'msg' => 'Erreur : ' . $e->getMessage()];
        }
        header('Location: ?page=gamestudio&tab=narrative_scenarios');
        exit;
    }
}

// 3. Journalisation télémétrique non-bloquante
ActivityTracker::logView($userId, 'gamestudio', $currentTab);
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenShogun Game Studio &bull; Plateforme Unifiée (<?= ucfirst($currentTab) ?>)</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="/public/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Tabler UI Framework -->
    <link rel="stylesheet" href="/public/css/tabler/tabler.min.css?v=1.0.0-beta21">
    <link rel="stylesheet" href="/public/css/style.css">

    <!-- ApexCharts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        :root {
            --studio-primary: #4f46e5;
            --studio-dark: #0f172a;
        }
        body {
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .studio-navbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .studio-nav-link {
            font-weight: 600;
            font-size: 0.92rem;
            color: #475569;
            padding: 0.55rem 1rem;
            border-radius: 6px;
            transition: all 0.15s ease-in-out;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .studio-nav-link:hover {
            color: #0f172a;
            background-color: #f1f5f9;
        }
        .studio-nav-link.active {
            color: #ffffff !important;
            background-color: var(--studio-primary) !important;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.25);
        }
        .studio-page-container {
            flex: 1 0 auto;
        }
    </style>
</head>
<body class="antialiased">

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- BARRE D'OUTILS PRINCIPALE GAME STUDIO (STANDALONE NAVBAR)           -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<header class="studio-navbar sticky-top py-2">
    <div class="container-fluid px-3 px-lg-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <!-- 1. Logo & Identité Game Studio + Menus de Navigation Alignés à Gauche -->
        <div class="d-flex align-items-center flex-wrap gap-3 gap-lg-4">
            <a href="?page=gamestudio&tab=dashboard" class="d-flex align-items-center gap-2 text-decoration-none text-dark me-2">
                <span class="avatar avatar-md rounded bg-indigo-lt text-indigo shadow-sm" style="font-size: 1.4rem;">
                    <i class="fa-solid fa-laptop-code"></i>
                </span>
                <div class="lh-1">
                    <div class="fw-bold font-game text-dark d-flex align-items-center gap-2" style="font-size: 1.15rem; letter-spacing: 0.04em;">
                        <span>Game Studio</span>
                        <span class="badge bg-indigo text-white font-monospace" style="font-size: 0.65rem;">MVC v<?= htmlspecialchars(defined('GAME_VERSION') ? GAME_VERSION : '1.27.0') ?></span>
                    </div>
                    <div class="text-secondary small mt-1" style="font-size: 0.72rem;">
                        Plateforme Unifiée &bull; Shogunat &amp; Dev Team
                    </div>
                </div>
            </a>

            <!-- 2. Barre d'Outils (Menus alignés à gauche : Dashboard, Général, Game Elevate Designer, Narrative Designer) -->
            <nav class="d-flex align-items-center flex-wrap gap-1">
                <a href="?page=gamestudio&tab=dashboard" 
                   class="studio-nav-link <?= $currentTab === 'dashboard' ? 'active' : '' ?>"
                   title="Tableau de bord exécutif et télémétrie en temps réel">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
                <!-- Dropdown Onglet Général avec Sous-Menu -->
                <div class="dropdown">
                    <a href="#" 
                       class="studio-nav-link dropdown-toggle <?= $currentTab === 'general' ? 'active' : '' ?>"
                       data-bs-toggle="dropdown" 
                       aria-expanded="false"
                       title="Synthèse générale et outils système du Game Studio">
                        <i class="fa-solid fa-sliders"></i>
                        <span>Général</span>
                    </a>
                    <div class="dropdown-menu shadow-sm">
                        <div class="dropdown-header text-uppercase text-secondary small fw-bold">
                            Administration &amp; Système
                        </div>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= ($currentTab === 'general' && $currentSubTab === 'overview') ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=general&subtab=overview">
                            <i class="fa-solid fa-gauge text-indigo"></i>
                            <div>
                                <div>Synthèse &amp; Dev Team</div>
                                <div class="small text-secondary">Environnement, KPIs &amp; Accréditations</div>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= ($currentTab === 'general' && $currentSubTab === 'world_config') ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=general&subtab=world_config">
                            <i class="fa-solid fa-earth-asia text-azure"></i>
                            <div>
                                <div>Configuration du Monde</div>
                                <div class="small text-secondary">Dimensions, tuiles générées &amp; arpentage</div>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= ($currentTab === 'general' && $currentSubTab === 'backups') ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=general&subtab=backups">
                            <i class="fa-solid fa-database text-primary"></i>
                            <div>
                                <div>Gestion des Sauvegardes</div>
                                <div class="small text-secondary">Sauvegardes DDL/DML, USB &amp; Restauration</div>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= ($currentTab === 'general' && $currentSubTab === 'updates') ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=general&subtab=updates">
                            <i class="fa-solid fa-arrows-rotate text-teal"></i>
                            <div>
                                <div>GitHub Sync &amp; Mises à Jour</div>
                                <div class="small text-secondary">Déploiement continu, commits &amp; synchronisation</div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Dropdown Game Elevate Designer -->
                <div class="dropdown">
                    <a href="#" 
                       class="studio-nav-link dropdown-toggle <?= str_starts_with($currentTab, 'elevate_') ? 'active' : '' ?>"
                       data-bs-toggle="dropdown" 
                       aria-expanded="false"
                       title="Concepteur de progression et équilibrage de jeu (Game Elevate Designer)">
                        <i class="fa-solid fa-compass-drafting text-warning"></i>
                        <span>Game Elevate Designer</span>
                    </a>
                    <div class="dropdown-menu shadow-sm">
                        <div class="dropdown-header text-uppercase text-secondary small fw-bold">
                            Équilibrage &amp; Systèmes de Jeu
                        </div>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'elevate_research' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=elevate_research">
                            <i class="fa-solid fa-scroll text-info"></i>
                            <div>
                                <div>Académie des Savoirs &amp; Forge</div>
                                <div class="small text-secondary">Technologies, Castes féodales, Coûts &amp; Délais</div>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'elevate_bots' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=elevate_bots">
                            <i class="fa-solid fa-robot text-indigo"></i>
                            <div>
                                <div>Clans IA &amp; Daimyōs Autonomes</div>
                                <div class="small text-secondary">Supervision, Cycles de décision &amp; Éclosion PNJ</div>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'elevate_combat' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=elevate_combat">
                            <i class="fa-solid fa-shield-halved text-danger"></i>
                            <div>
                                <div>Simulateur de Combat</div>
                                <div class="small text-secondary">Split-screen tactique, Murailles &amp; Sièges</div>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'elevate_castles' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=elevate_castles">
                            <i class="fa-solid fa-chess-rook text-warning"></i>
                            <div>
                                <div>Siège des 12 Châteaux Authentiques</div>
                                <div class="small text-secondary">3 Échelons tactiques, Topographie, Météo &amp; Reliques</div>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'elevate_village' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=elevate_village">
                            <i class="fa-solid fa-people-roof text-success"></i>
                            <div>
                                <div>Vie du Village</div>
                                <div class="small text-secondary">Sandbox démographique, Contentement &amp; Saké</div>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'elevate_buildings' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=elevate_buildings">
                            <i class="fa-solid fa-chart-line text-info"></i>
                            <div>
                                <div>Dérivation des Bâtiments</div>
                                <div class="small text-secondary">Courbes de progression, Coûts &amp; Délais chantiers</div>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= in_array($currentTab, ['elevate_events', 'elevate_weekly_events'], true) ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=elevate_events">
                            <i class="fa-solid fa-scroll text-warning"></i>
                            <div>
                                <div>Chroniques &amp; Événements Hebdo</div>
                                <div class="small text-secondary">Récits historiques du Sengoku, Actes narratifs &amp; Quêtes</div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Dropdown Narrative Designer -->
                <div class="dropdown">
                    <a href="#" 
                       class="studio-nav-link dropdown-toggle <?= str_starts_with($currentTab, 'narrative_') ? 'active' : '' ?>"
                       data-bs-toggle="dropdown" 
                       aria-expanded="false"
                       title="Scénarisation, storyboards vidéo et univers féodal (Narrative Designer)">
                        <i class="fa-solid fa-feather-pointed text-purple"></i>
                        <span>Narrative Designer</span>
                    </a>
                    <div class="dropdown-menu shadow-sm">
                        <div class="dropdown-header text-uppercase text-secondary small fw-bold">
                            Narration &amp; Univers
                        </div>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'narrative_scenarios' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=narrative_scenarios">
                            <i class="fa-solid fa-book-journal-whills text-purple"></i>
                            <div>
                                <div>Scénarios de Jeu &amp; Tâches Métiers</div>
                                <div class="small text-secondary">Steps de gameplay, Événements spéciaux &amp; Matrice des 10 métiers</div>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'narrative_storyboard' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=narrative_storyboard">
                            <i class="fa-solid fa-film text-purple"></i>
                            <div>
                                <div>Gestionnaire de Storyboard</div>
                                <div class="small text-secondary">Timeline interactive, Drag &amp; Drop, Vidéos &amp; Événements</div>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'narrative_lore' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=narrative_lore">
                            <i class="fa-solid fa-scroll text-warning"></i>
                            <div>
                                <div>Charte Narrative &amp; Lore Féodal</div>
                                <div class="small text-secondary">Lexique d'époque, Factions &amp; Cohérence d'univers</div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Dropdown Producteur & Live Ops -->
                <div class="dropdown">
                    <a href="#" 
                       class="studio-nav-link dropdown-toggle <?= str_starts_with($currentTab, 'liveops_') ? 'active' : '' ?>"
                       data-bs-toggle="dropdown" 
                       aria-expanded="false"
                       title="Tour de contrôle en direct, Roadmap, Événements & Compensations (Producteur & Live Ops)">
                        <i class="fa-solid fa-crown text-warning"></i>
                        <span>Producteur &amp; Live Ops</span>
                    </a>
                    <div class="dropdown-menu shadow-sm">
                        <div class="dropdown-header text-uppercase text-secondary small fw-bold">
                            Gouvernance &amp; Opérations Live
                        </div>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'liveops_events' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=liveops_events">
                            <i class="fa-solid fa-calendar-check text-warning"></i>
                            <div>
                                <div>Événements Live &amp; Saisons</div>
                                <div class="small text-secondary">Planificateur d'événements, Boosts d'univers &amp; Quêtes</div>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'liveops_features' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=liveops_features">
                            <i class="fa-solid fa-toggle-on text-success"></i>
                            <div>
                                <div>Feature Flags &amp; Roadmap</div>
                                <div class="small text-secondary">Interrupteurs à chaud On/Off &amp; Jalons de versions</div>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'liveops_broadcast' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=liveops_broadcast">
                            <i class="fa-solid fa-bullhorn text-danger"></i>
                            <div>
                                <div>Broadcast &amp; Mode Maintenance</div>
                                <div class="small text-secondary">Bannière in-game défilante, Alertes &amp; Coupure d'urgence</div>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'liveops_support' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=liveops_support">
                            <i class="fa-solid fa-gift text-purple"></i>
                            <div>
                                <div>Support &amp; Compensations Daimyō</div>
                                <div class="small text-secondary">Recherche de fief, Colis de dédommagement &amp; Audit logs</div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Dropdown Développeur Back-end, Front-end & Réseau -->
                <div class="dropdown">
                    <a href="#" 
                       class="studio-nav-link dropdown-toggle <?= str_starts_with($currentTab, 'dev_') ? 'active' : '' ?>"
                       data-bs-toggle="dropdown" 
                       aria-expanded="false"
                       title="Ingénierie logicielle, back-end, front-end, réseau et résolution de bugs (Développeur)">
                        <i class="fa-solid fa-code text-teal"></i>
                        <span>Développeur (Back, Front &amp; Réseau)</span>
                    </a>
                    <div class="dropdown-menu shadow-sm">
                        <div class="dropdown-header text-uppercase text-secondary small fw-bold">
                            Ingénierie &amp; Résolution de Bugs
                        </div>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'dev_tickets' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=dev_tickets">
                            <i class="fa-solid fa-bug text-danger"></i>
                            <div>
                                <div>Traitement des Tickets Support &amp; Bugs</div>
                                <div class="small text-secondary">Signalements joueurs, triage, diagnostic technique &amp; résolution</div>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'dev_backend_tasks' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=dev_backend_tasks">
                            <i class="fa-solid fa-server text-teal"></i>
                            <div>
                                <div>Backlog &amp; Tâches Développeur Back-End</div>
                                <div class="small text-secondary">Feuille de route technique, synchronisation scénarios &amp; GDD .md</div>
                            </div>
                        </a>
                    </div>
                </div>
            </nav>
        </div>

        <!-- 3. Profil Utilisateur (Aligné à droite) -->
        <div class="d-flex align-items-center gap-2">
            <!-- Badge Utilisateur -->
            <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-sm rounded-circle bg-indigo-lt text-indigo fw-bold">
                    <?= strtoupper(substr($currentUser['username'] ?? 'U', 0, 2)) ?>
                </span>
                <div class="d-none d-sm-block lh-1 text-start">
                    <div class="fw-bold small"><?= htmlspecialchars($currentUser['username'] ?? 'Daimyō') ?></div>
                    <div class="text-secondary font-monospace" style="font-size:0.65rem;">
                        <?= $auth->isAdmin() ? 'Administrateur' : 'Dev Team' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- CORPS PRINCIPAL DE LA VUE GAME STUDIO                               -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<main class="studio-page-container py-3 py-lg-4">
    <div class="container-fluid px-3 px-lg-4">
        <?php
        $subviewFile = __DIR__ . '/gamestudio/' . $currentTab . '.php';
        if (file_exists($subviewFile)) {
            require $subviewFile;
        } else {
            require __DIR__ . '/gamestudio/dashboard.php';
        }
        ?>
    </div>
</main>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- PIED DE PAGE TECHNIQUE DU GAME STUDIO                               -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<footer class="footer footer-transparent d-print-none py-3 border-top mt-auto bg-white">
    <div class="container-fluid px-3 px-lg-4">
        <div class="row text-center align-items-center flex-row-reverse">
            <div class="col-lg-auto ms-lg-auto">
                <ul class="list-inline list-inline-dots mb-0 small">
                    <li class="list-inline-item">
                        <a href="/?page=docs" target="_blank" class="link-secondary">Documentation API</a>
                    </li>
                    <li class="list-inline-item">
                        <a href="/?page=changelog" target="_blank" class="link-secondary">Notes de Version</a>
                    </li>
                    <li class="list-inline-item">
                        <a href="/?page=dev_team" target="_blank" class="link-secondary">Studio Métiers</a>
                    </li>
                </ul>
            </div>
            <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                <ul class="list-inline list-inline-dots mb-0 small text-secondary">
                    <li class="list-inline-item">
                        <strong>OpenShogun Game Studio</strong> &bull; Architecture Modulaire MVC
                    </li>
                    <li class="list-inline-item">
                        Moteur Télémétrique &amp; Monitoring Intégré
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<!-- Modale Universelle pour Messages, Alertes & Confirmations Tabler -->
<div class="modal-overlay" id="customAlertModal">
    <div class="modal-card alert-modal-card" id="customAlertCard">
        <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title text-white m-0" id="customAlertTitle">Décret du Shogunat</h3>
            <button onclick="closeCustomAlert()" class="modal-close-btn text-white btn-close" aria-label="Fermer"></button>
        </div>
        <div class="card-body p-4">
            <div class="alert-modal-content d-flex align-items-center gap-3">
                <span class="alert-modal-icon fs-1" id="customAlertIcon"><i class="fa-solid fa-circle-info text-primary"></i></span>
                <p class="alert-modal-text m-0 fs-3" id="customAlertText"></p>
            </div>
            <div class="alert-modal-actions d-flex justify-content-end gap-2 mt-4" id="customAlertActions">
                <button class="btn btn-primary" id="customAlertBtnOk">Compris</button>
            </div>
        </div>
    </div>
</div>

<!-- Conteneur Universel de Toasts Tabler.io pour Confirmations d'Actions & Notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="tablerToastContainer" style="z-index: 99999; max-width: 420px;"></div>

<!-- Modal Universelle de Confirmation Dev & Studio -->
<div class="modal fade" id="dev-universal-confirm-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-2">
                <h5 class="modal-title d-flex align-items-center gap-2 m-0" id="dev-confirm-title">
                    Confirmation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="fs-1 text-danger" id="dev-confirm-icon-box"></span>
                    <div id="dev-confirm-message" class="fs-4 text-dark mb-0"></div>
                </div>
            </div>
            <div class="modal-footer border-top-0 d-flex justify-content-end gap-2 p-3">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="dev-confirm-btn-action">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<!-- Tabler Core JS & Bootstrap Bundle -->
<script src="/public/css/tabler/tabler.min.js?v=1.0.0-beta21"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="/public/js/app.js"></script>

<script>
function showDevAlert(type, message) {
    const alertBox = document.getElementById('dev-alert');
    const contentBox = document.getElementById('dev-alert-content');
    if (alertBox && contentBox) {
        alertBox.className = `alert alert-${type} alert-dismissible shadow-sm mb-3`;
        contentBox.innerHTML = message;
        alertBox.classList.remove('d-none');
        window.scrollTo({ top: alertBox.offsetTop - 80, behavior: 'smooth' });
    } else {
        alert(message.replace(/<[^>]*>?/gm, ''));
    }
}

function showConfirmModal(title, message, onConfirm, btnClass = 'btn-danger', iconHtml = '<i class="fa-solid fa-triangle-exclamation"></i>') {
    const modalEl = document.getElementById('dev-universal-confirm-modal');
    if (!modalEl) {
        if (confirm(message.replace(/<[^>]*>?/gm, ''))) onConfirm();
        return;
    }
    const titleEl = document.getElementById('dev-confirm-title');
    const msgEl = document.getElementById('dev-confirm-message');
    const iconEl = document.getElementById('dev-confirm-icon-box');
    const actionBtn = document.getElementById('dev-confirm-btn-action');

    if (titleEl) titleEl.innerHTML = title;
    if (msgEl) msgEl.innerHTML = message;
    if (iconEl) iconEl.innerHTML = iconHtml;

    if (actionBtn) {
        actionBtn.className = `btn ${btnClass} w-100`;
        const newBtn = actionBtn.cloneNode(true);
        actionBtn.parentNode.replaceChild(newBtn, actionBtn);
        newBtn.addEventListener('click', () => {
            const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            if (bsModal) bsModal.hide();
            onConfirm();
        });
    }

    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
}
</script>

</body>
</html>
