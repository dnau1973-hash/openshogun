<?php
/**
 * Header Partiel OpenGalaxy
 */
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/PlanetEngine.php';
require_once __DIR__ . '/../../core/BuildingEngine.php';
require_once __DIR__ . '/../../core/FleetEngine.php';
require_once __DIR__ . '/../../core/MessageEngine.php';
require_once __DIR__ . '/../../core/QuestEngine.php';
require_once __DIR__ . '/../../core/HeroEngine.php';
require_once __DIR__ . '/../../core/ImperialSealEngine.php';
require_once __DIR__ . '/../../core/DevTeamEngine.php';
require_once __DIR__ . '/../../core/AiPromptHelper.php';
require_once __DIR__ . '/../../config/game_constants.php';

$auth = new Auth();
if (!empty($_GET['switch_planet'])) {
    $auth->setCurrentPlanet((int)$_GET['switch_planet']);
    $cleanUri = preg_replace('/([&?])switch_planet=\d+(&|$)/', '$1', $_SERVER['REQUEST_URI'] ?? '');
    $cleanUri = rtrim($cleanUri, '?&');
    if (empty($cleanUri)) $cleanUri = 'index.php';
    header("Location: $cleanUri");
    exit;
}
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();
$planetEngine = new PlanetEngine();
$allUserPlanets = ($user && $planet) ? $planetEngine->getUserPlanets((int)$user['id']) : [];
$db = Database::getConnection();
$messageEngine = new MessageEngine();
$unreadMessagesCount = ($user && !empty($user['id'])) ? $messageEngine->getUnreadCount((int)$user['id']) : 0;

// Rapports de combat non lus
$unreadReportsCount = 0;
if ($user && !empty($user['id'])) {
    try {
        $crCols = $db->query("SHOW COLUMNS FROM combat_reports")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('read_by_attacker', $crCols) && in_array('read_by_defender', $crCols)) {
            $stmtRep = $db->prepare("
                SELECT COUNT(*) FROM combat_reports
                WHERE (attacker_id = :uid1 AND read_by_attacker = 0)
                   OR (defender_id = :uid2 AND read_by_defender = 0)
            ");
            $stmtRep->execute([':uid1' => (int)$user['id'], ':uid2' => (int)$user['id']]);
            $unreadReportsCount = (int)$stmtRep->fetchColumn();
        }
    } catch (Throwable $e) {
        $unreadReportsCount = 0;
    }
}

// Chuchotements de Chat non lus
$unreadChatCount = 0;
if ($user && !empty($user['id'])) {
    try {
        $chatCols = $db->query("SHOW COLUMNS FROM chat_messages")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('is_read', $chatCols)) {
            $db->exec("ALTER TABLE chat_messages ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER is_deleted");
            $db->exec("ALTER TABLE chat_messages ADD INDEX idx_chat_unread (recipient_id, is_read)");
        }
        $stmtChat = $db->prepare("
            SELECT COUNT(*) FROM chat_messages
            WHERE recipient_id = ? AND is_read = 0 AND is_deleted = 0
        ");
        $stmtChat->execute([(int)$user['id']]);
        $unreadChatCount = (int)$stmtChat->fetchColumn();
    } catch (Throwable $e) {
        $unreadChatCount = 0;
    }
}

// Total combiné pour le badge du bouton Communication (Messagerie + Chat + Rapports)
$totalUnreadComm = $unreadMessagesCount + $unreadReportsCount + $unreadChatCount;

$userProtection = ($user && !empty($user['id'])) ? Auth::getProtectionRemaining($user) : null;
$isUserProtected = $userProtection && !empty($userProtection['is_protected']);

$questEngine = new QuestEngine();
$questSummary = ($user && $planet) ? $questEngine->getPlayerQuestsStatus((int)$user['id'], (int)$planet['id']) : null;

$heroEngine = new HeroEngine();
if ($user) {
    $heroEngine->ensureAvailableAdventures((int)$user['id'], 3);
}
$heroHeader = $user ? $heroEngine->getHeroByUserId((int)$user['id']) : null;

$sealEngine = new ImperialSealEngine();
$sealStatus = $user ? $sealEngine->getSealStatus((int)$user['id']) : null;
$isSealActive = $sealStatus && !empty($sealStatus['active']);
$userGoldCoins = $sealStatus ? (int)$sealStatus['gold'] : 0;

$devTeamEngine = new DevTeamEngine();
$isDevTeamMember = ($user && !empty($user['id'])) ? $devTeamEngine->isDevTeamMember((int)$user['id']) : false;

if ($planet) {
    $planetEngine = new PlanetEngine();
    $fleetEngine = new FleetEngine();
    
    // Mettre à jour les flottes et la planète
    $fleetEngine->processFleetMissions();
    $planet = $planetEngine->updatePlanet((int)$planet['id']);
    
    // Simulation autonome des PNJ / Bots (espionnage, raids, chantiers)
    require_once __DIR__ . '/../../core/BotEngine.php';
    $botEngine = new BotEngine();
    $botEngine->tickPeriodicSimulation();
    
    // Vérifier les flottes en mouvement pour l'alerte HUD (avec détails des fiefs et clans)
    $db = Database::getConnection();
    $stmtMissions = $db->prepare("
        SELECT m.*, 
               u_sender.username as sender_username, u_sender.faction as sender_faction,
               p_src.name as source_planet_name, p_src.coord_x as source_coord_x, p_src.coord_y as source_coord_y,
               COALESCE(p_tgt.name, CONCAT('Oasis ', o.name), 'Province Sauvage') as target_planet_name,
               COALESCE(p_tgt.coord_x, o.coord_x, 0) as target_coord_x,
               COALESCE(p_tgt.coord_y, o.coord_y, 0) as target_coord_y
        FROM fleet_missions m
        LEFT JOIN users u_sender ON m.user_id = u_sender.id
        LEFT JOIN planets p_src ON m.source_planet_id = p_src.id
        LEFT JOIN planets p_tgt ON m.target_planet_id = p_tgt.id
        LEFT JOIN oases o ON m.target_oasis_id = o.id
        WHERE (m.user_id = ? OR m.target_planet_id = ?) AND m.status IN ('en_route', 'returning') 
        ORDER BY m.arrival_time ASC
    ");
    $stmtMissions->execute([$user['id'], $planet['id']]);
    $activeMissions = $stmtMissions->fetchAll();

    $uRows = $db->query("SELECT code, name, icon FROM units")->fetchAll(PDO::FETCH_ASSOC);
    $unitsMap = [];
    foreach ($uRows as $ur) {
        $unitsMap[$ur['code']] = $ur;
    }
    $sRows = $db->query("SELECT code, name FROM ships")->fetchAll(PDO::FETCH_ASSOC);
    $shipsMap = [];
    foreach ($sRows as $sr) {
        $shipsMap[$sr['code']] = $sr;
    }

    $planetBuildings = $planetEngine->getBuildings((int)$planet['id']);
    $watchtowerLevel = (int)($planetBuildings['radar'] ?? 0);

    $incomingHostile = [];
    $incomingSpy = [];
    $outgoingMissions = [];
    foreach ($activeMissions as $m) {
        if ($m['target_planet_id'] == $planet['id'] && $m['status'] === 'en_route') {
            // Détection opérationnelle UNIQUEMENT si la Tour de Guet (radar) est construite (Niveau >= 1)
            if ($watchtowerLevel >= 1) {
                if ($m['mission_type'] === 'spy') {
                    $incomingSpy[] = $m;
                } else {
                    $incomingHostile[] = $m;
                }
            }
        } else {
            $outgoingMissions[] = $m;
        }
    }

    // Si la Tour de Guet n'est pas construite, les alertes d'armées ennemies ne sont pas visibles
    if ($watchtowerLevel < 1) {
        $activeMissions = $outgoingMissions;
    }
}

$page = $_GET['page'] ?? 'resources';
$factionInfo = FACTIONS[$user['faction']] ?? FACTIONS['terran'];
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?> - Chroniques Féodales du Sengoku</title>
    <!-- Google Fonts: Dela Gothic One (Immersion Féodale Sengoku) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 (Icônes vectorielles professionnelles) -->
    <link rel="stylesheet" href="/public/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- Tabler UI Framework (local) -->
    <link rel="stylesheet" href="/public/css/tabler/tabler.min.css?v=1.0.0-beta21">
    <!-- HUD Travian Féodal (header circulaire, barres de ressources, alertes) -->
    <link rel="stylesheet" href="/public/css/style.css?v=<?= file_exists(__DIR__ . '/../../public/css/style.css') ? filemtime(__DIR__ . '/../../public/css/style.css') : time() ?>">
    <!-- Transparence & Détails des Prompts Images IA -->
    <link rel="stylesheet" href="/public/css/ai_prompt_modal.css?v=<?= file_exists(__DIR__ . '/../../public/css/ai_prompt_modal.css') ? filemtime(__DIR__ . '/../../public/css/ai_prompt_modal.css') : time() ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏯</text></svg>">
    <script>
        window.IS_ADMIN = <?= (!empty($user['is_admin']) || (($user['role'] ?? '') === 'admin') || (isset($auth) && $auth->isAdmin())) ? 'true' : 'false' ?>;
    </script>
</head>
<body class="antialiased">

<?php
$navItems = [
    ['page' => 'resources', 'match' => ['resources','field'], 'icon' => '<i class="fa-solid fa-wheat-awn text-success"></i>',      'label' => 'Terroir Féodal', 'title' => 'Terroir & Récoltes'],
    ['page' => 'city',      'match' => ['city','building'],   'icon' => '<i class="fa-solid fa-torii-gate text-primary"></i>',     'label' => 'Cité Castrale',  'title' => 'Bâtiments & Châteaux'],
    ['page' => 'map',       'match' => ['map','galaxy'],      'icon' => '<i class="fa-solid fa-map-location-dot text-info"></i>', 'label' => 'Carte',          'title' => 'Carte des Provinces'],
    ['page' => 'ranking',   'match' => ['ranking'],           'icon' => '<i class="fa-solid fa-trophy text-warning"></i>',        'label' => 'Classement',     'title' => 'Classement des Daimyōs & Alliances'],
];
?>

<!-- ===== LAYOUT TABLER PLEINE LARGEUR (pas de sidebar) ===== -->
<div class="page">
    <div class="page-wrapper">

        <?php
        // Bannière Flash Flottante LiveOps Broadcast (si active)
        if (file_exists(__DIR__ . '/../../core/LiveOpsEngine.php')) {
            require_once __DIR__ . '/../../core/LiveOpsEngine.php';
            $liveOpsEngineHeader = new LiveOpsEngine();
            $activeBroadcastHeader = $liveOpsEngineHeader->getActiveBroadcast();
            if ($activeBroadcastHeader) {
                $bcType = htmlspecialchars($activeBroadcastHeader['type'] ?? 'info');
                $bcIcon = htmlspecialchars($activeBroadcastHeader['icon'] ?? 'fa-bullhorn');
                $bcTitle = htmlspecialchars($activeBroadcastHeader['title'] ?? 'Dépêche Impériale');
                $bcMsg = htmlspecialchars($activeBroadcastHeader['message'] ?? '');
                ?>
                <div class="alert alert-<?= $bcType ?> alert-dismissible mb-0 rounded-0 border-0 border-bottom d-flex align-items-center justify-content-between px-3 py-2 shadow-sm d-print-none" role="alert" style="z-index: 1060; font-size: 0.9rem;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-3"><i class="fa-solid <?= $bcIcon ?>"></i></span>
                        <div>
                            <strong><?= $bcTitle ?> :</strong>
                            <span><?= $bcMsg ?></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding:0.75rem;"></button>
                </div>
                <?php
            }
        }
        ?>

        <!-- ── 1. BARRE DE NAVIGATION TABLER EN HAUT DE PAGE (Pleine largeur container-fluid) ── -->
        <header class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm d-print-none sticky-top py-2">
            <div class="container-fluid px-3 px-lg-4">

                <!-- Toggler mobile -->
                <button class="navbar-toggler me-2" type="button"
                        data-bs-toggle="collapse" data-bs-target="#mainNavBar" aria-controls="mainNavBar" aria-expanded="false" aria-label="Menu principal">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Brand / Logo Féodal -->
                <a href="?page=resources" class="navbar-brand d-inline-flex align-items-center gap-2 me-3 text-decoration-none" title="<?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?>">
                    <span class="fs-2 lh-1 text-danger"><i class="fa-solid fa-torii-gate"></i></span>
                    <span class="fw-bold text-dark font-game d-none d-sm-inline" style="letter-spacing:0.04em;"><?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?></span>
                </a>

                <!-- Navigation principale & Menus déroulants -->
                <div class="collapse navbar-collapse" id="mainNavBar">
                    <ul class="navbar-nav me-auto">
                        <?php foreach ($navItems as $nav):
                            $isActive = in_array($page, $nav['match']);
                        ?>
                        <li class="nav-item <?= $isActive ? 'active' : '' ?>">
                            <a class="nav-link <?= $isActive ? 'active fw-bold' : '' ?>"
                               href="?page=<?= $nav['page'] ?>"
                               title="<?= htmlspecialchars($nav['title']) ?>">
                                <span class="nav-link-icon d-md-none d-lg-inline-block"><?= $nav['icon'] ?></span>
                                <span class="nav-link-title"><?= $nav['label'] ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>

                        <!-- Position 4 : Menu déroulant « Mon Empire » -->
                        <?php 
                        $isEmpireActive = in_array($page, ['fleet', 'hero', 'alliance', 'empire']);
                        ?>
                        <li class="nav-item dropdown <?= $isEmpireActive ? 'active' : '' ?>">
                            <a class="nav-link dropdown-toggle <?= $isEmpireActive ? 'active fw-bold' : '' ?>" 
                               href="#navbar-empire" 
                               data-bs-toggle="dropdown" 
                               data-bs-auto-close="outside" 
                               role="button" 
                               aria-expanded="<?= $isEmpireActive ? 'true' : 'false' ?>"
                               title="Mon Empire &amp; Puissance Féodale">
                                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="fa-solid fa-crown text-warning"></i></span>
                                <span class="nav-link-title">Mon Empire</span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item d-flex align-items-center gap-2 <?= $page === 'fleet' ? 'active fw-bold' : '' ?>" href="?page=fleet">
                                    <span class="dropdown-item-icon"><i class="fa-solid fa-khanda text-danger"></i></span>
                                    <span>Armée</span>
                                </a>
                                <a class="dropdown-item d-flex align-items-center gap-2 <?= $page === 'hero' ? 'active fw-bold' : '' ?>" href="?page=hero">
                                    <span class="dropdown-item-icon"><i class="fa-solid fa-user-ninja text-primary"></i></span>
                                    <span>Héros</span>
                                </a>
                                <a class="dropdown-item d-flex align-items-center gap-2 <?= $page === 'alliance' ? 'active fw-bold' : '' ?>" href="?page=alliance">
                                    <span class="dropdown-item-icon"><i class="fa-solid fa-flag text-danger"></i></span>
                                    <span>Alliance</span>
                                </a>                                    
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item d-flex align-items-center gap-2 <?= $page === 'poster' ? 'active fw-bold' : '' ?>" href="?page=poster">
                                    <span class="dropdown-item-icon"><i class="fa-solid fa-user-shield text-info"></i></span>
                                    <span>Mon Affiche Féodale</span>
                                </a>
                                <a href="javascript:void(0)" class="dropdown-item d-flex align-items-center gap-2" onclick="openEditMottoModal()">
                                    <span class="dropdown-item-icon"><i class="fa-solid fa-scroll text-warning"></i></span>
                                    <span>Ma Devise</span>
                                </a>
                                <?php if ($isSealActive): ?>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item d-flex align-items-center justify-content-between <?= $page === 'empire' ? 'active fw-bold' : '' ?>" href="?page=empire">
                                        <span class="d-flex align-items-center gap-2">
                                            <span class="dropdown-item-icon"><i class="fa-solid fa-crown text-warning"></i></span>
                                            <span>Tableau de bord de l'empire</span>
                                        </span>
                                        <span class="badge bg-warning text-warning-fg ms-2" style="font-size:0.6rem;">Sceau Actif</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </li>

                        <!-- Position 5 : Menu déroulant « Communication » -->
                        <?php 
                        $isCommActive = in_array($page, ['messages', 'chat', 'forum', 'reports']);
                        ?>
                        <li class="nav-item dropdown <?= $isCommActive ? 'active' : '' ?>">
                            <a class="nav-link dropdown-toggle <?= $isCommActive ? 'active fw-bold' : '' ?>" 
                               href="#navbar-communication" 
                               data-bs-toggle="dropdown" 
                               data-bs-auto-close="outside" 
                               role="button" 
                               aria-expanded="<?= $isCommActive ? 'true' : 'false' ?>"
                               title="Espace de Communication Féodale">
                                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="fa-solid fa-comments text-primary"></i></span>
                                <span class="nav-link-title">Communication</span>
                                <?php if ($totalUnreadComm > 0): ?>
                                    <span class="badge bg-danger text-white rounded-pill ms-1" style="font-size:0.65rem; padding: 2px 6px;">
                                        <?= $totalUnreadComm > 99 ? '99+' : $totalUnreadComm ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item d-flex align-items-center justify-content-between <?= $page === 'messages' ? 'active fw-bold' : '' ?>" href="?page=messages">
                                    <span class="d-flex align-items-center gap-2">
                                        <span class="dropdown-item-icon"><i class="fa-solid fa-envelope text-info"></i></span>
                                        <span>Missives &amp; Messages</span>
                                    </span>
                                    <?php if ($unreadMessagesCount > 0): ?>
                                        <span class="badge bg-danger text-white rounded-pill" style="font-size:0.65rem; padding: 2px 6px;">
                                            <?= $unreadMessagesCount ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                                <a class="dropdown-item d-flex align-items-center justify-content-between <?= $page === 'reports' ? 'active fw-bold' : '' ?>" href="?page=reports">
                                    <span class="d-flex align-items-center gap-2">
                                        <span class="dropdown-item-icon"><i class="fa-solid fa-scroll text-danger"></i></span>
                                        <span>Chroniques de Siège &amp; Rapports</span>
                                    </span>
                                    <?php if ($unreadReportsCount > 0): ?>
                                        <span class="badge bg-danger text-white rounded-pill" style="font-size:0.65rem; padding: 2px 6px;">
                                            <?= $unreadReportsCount ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                                <a class="dropdown-item d-flex align-items-center justify-content-between <?= $page === 'chat' ? 'active fw-bold' : '' ?>" href="?page=chat">
                                    <span class="d-flex align-items-center gap-2">
                                        <span class="dropdown-item-icon"><i class="fa-solid fa-shield-halved text-warning"></i></span>
                                        <span>Conseil de Guerre (Général)</span>
                                    </span>
                                    <?php if ($unreadChatCount > 0): ?>
                                        <span class="badge bg-danger text-white rounded-pill" style="font-size:0.65rem; padding: 2px 6px;">
                                            <?= $unreadChatCount ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item d-flex align-items-center gap-2 <?= $page === 'forum' ? 'active fw-bold' : '' ?>" href="?page=forum">
                                    <span class="dropdown-item-icon"><i class="fa-solid fa-building-columns text-secondary"></i></span>
                                    <span>Archives &amp; Chroniques Féodales</span>
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item d-flex align-items-center gap-2 <?= $page === 'support' ? 'active fw-bold' : '' ?>" href="?page=support">
                                    <span class="dropdown-item-icon"><i class="fa-solid fa-headset text-teal"></i></span>
                                    <span>Assistance &amp; Signalements (📮)</span>
                                </a>
                            </div>
                        </li>
                    </ul>

                    <!-- Bloc de droite (Badges, Outils, Fief, Héros, Déconnexion) -->
                    <div class="navbar-nav flex-row order-md-last ms-auto align-items-center gap-2 flex-wrap">
                        <?php if ($isUserProtected): ?>
                            <span class="badge bg-success-lt d-inline-flex align-items-center gap-1 py-1 px-2"
                                  data-bs-toggle="tooltip" data-bs-placement="bottom"
                                  title="Immunité Féodale active jusqu'au <?= htmlspecialchars($userProtection['until_formatted']) ?>">
                                <i class="fa-solid fa-shield-halved text-success"></i>
                                <span class="fw-bold"><?= htmlspecialchars($userProtection['formatted']) ?></span>
                            </span>
                        <?php endif; ?>

                        <?php
                        $hHp = $heroHeader ? round((float)$heroHeader['health']) : 100;
                        $hHpCol = ($hHp >= 60) ? 'border-success' : (($hHp >= 25) ? 'border-warning' : 'border-danger');
                        $hLvl = $heroHeader ? (int)$heroHeader['level'] : 1;
                        $hasPoints = ($heroHeader && (int)$heroHeader['unassigned_points'] > 0);
                        ?>

                        <!-- Sélecteur de Fiefs -->
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link d-flex align-items-center gap-2 text-reset p-1 rounded border bg-light-subtle"
                                data-bs-toggle="dropdown" aria-expanded="false" title="Changer de fief féodal">
                                <span class="fs-3 lh-1 ps-1"><?= !empty($planet['is_capital']) ? '<i class="fa-solid fa-crown text-warning"></i>' : '<i class="fa-solid fa-torii-gate text-secondary"></i>' ?></span>
                                <div class="d-none d-sm-block text-start lh-1">
                                    <div class="fw-bold text-dark text-truncate" style="font-size:0.82rem; max-width:130px;">
                                        <?= htmlspecialchars($planet['name'] ?? 'Fief') ?>
                                    </div>
                                    <div class="text-secondary small mt-1 font-monospace" style="font-size:0.65rem;">
                                        [<?= $planet['coord_x'] ?? 0 ?>|<?= $planet['coord_y'] ?? 0 ?>] <?= !empty($planet['is_capital']) ? '<span class="text-warning fw-bold">Capitale</span>' : '' ?>
                                    </div>
                                </div>
                                <span class="dropdown-toggle text-secondary ms-1 me-1"></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:240px; z-index:1050;">
                                <?php if ($isUserProtected): ?>
                                    <div class="dropdown-item-text small bg-success-lt text-success fw-bold">
                                        <i class="fa-solid fa-shield-halved me-1"></i> Immunité active : <?= htmlspecialchars($userProtection['formatted']) ?>
                                    </div>
                                    <div class="dropdown-divider"></div>
                                <?php endif; ?>
                                <div class="dropdown-header text-uppercase fw-bold d-flex justify-content-between align-items-center py-2 bg-light-subtle">
                                    <span>Vos Fiefs Féodaux</span>
                                    <span class="badge bg-secondary-lt"><?= count($allUserPlanets) ?></span>
                                </div>
                                <?php foreach ($allUserPlanets as $p): 
                                    $isCurrent = ((int)$p['id'] === (int)$planet['id']);
                                ?>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center py-2 <?= $isCurrent ? 'active' : '' ?>" href="?switch_planet=<?= (int)$p['id'] ?>">
                                        <div>
                                            <div class="fw-bold d-flex align-items-center gap-1" style="font-size:0.85rem;">
                                                <?= !empty($p['is_capital']) ? '<i class="fa-solid fa-crown text-warning me-1"></i>' : '<i class="fa-solid fa-torii-gate text-secondary me-1"></i>' ?> <?= htmlspecialchars($p['name']) ?>
                                            </div>
                                            <div class="text-secondary small font-monospace" style="font-size:0.72rem;">
                                                [<?= $p['coord_x'] ?>|<?= $p['coord_y'] ?>] <?= !empty($p['is_capital']) ? '<span class="text-warning">Capitale</span>' : '' ?>
                                            </div>
                                        </div>
                                        <?php if ($isCurrent): ?>
                                            <span class="badge bg-primary text-white" style="font-size:0.65rem;">Actif</span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                                <div class="dropdown-divider m-0"></div>
                                <a class="dropdown-item py-2 text-secondary small" href="javascript:void(0)" onclick="openCityRenamePrompt(<?= (int)$planet['id'] ?>, <?= htmlspecialchars(json_encode($planet['name']), ENT_QUOTES, 'UTF-8') ?>)">
                                    <i class="fa-solid fa-pen-nib me-2 text-danger"></i>Renommer le fief actif
                                </a>
                            </div>
                        </div>

                        <!-- Avatar Samouraï Héros -->
                        <div class="nav-item">
                            <a href="?page=hero" class="nav-link p-0 position-relative d-inline-flex align-items-center"
                               title="Samouraï Héros &bull; Niveau <?= $hLvl ?> (Santé : <?= $hHp ?>%)">
                                <span class="avatar avatar-sm rounded-circle border border-2 <?= $hHpCol ?> shadow-sm"
                                      style="background-image: url(/public/assets/hero_samurai.jpg); width: 34px; height: 34px;"></span>
                                <span class="badge bg-danger text-white rounded-circle position-absolute d-inline-flex align-items-center justify-content-center fw-bold shadow-sm"
                                      style="bottom: -3px; right: -3px; width: 18px; height: 18px; font-size: 0.65rem; border: 2px solid #ffffff; line-height: 1; padding: 0;">
                                    <?= $hLvl ?>
                                </span>
                                <?php if ($hasPoints): ?>
                                    <span class="badge bg-warning text-dark rounded-circle position-absolute d-inline-flex align-items-center justify-content-center fw-bold"
                                          style="top: -3px; right: -3px; width: 15px; height: 15px; font-size: 0.6rem; border: 2px solid #ffffff; line-height: 1; padding: 0;"
                                          title="<?= (int)$heroHeader['unassigned_points'] ?> point(s) d'attribut à distribuer !">+</span>
                                <?php endif; ?>
                            </a>
                        </div>

                        <!-- Déconnexion -->
                        <a href="?action=logout" class="btn btn-sm btn-icon btn-ghost-danger border-0 p-1" title="Fermer la session">
                            <i class="fa-solid fa-arrow-right-from-bracket fs-3"></i>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- ── SOUS-BARRE DÉDIÉE : STATUTS & OUTILS RAPIDES (Alignée à droite) ── -->
        <div class="sub-navbar border-bottom bg-light-subtle py-1 d-print-none shadow-none">
            <div class="container-fluid px-3 px-lg-4 d-flex align-items-center justify-content-end gap-2 flex-wrap">
                
                <!-- 1. Trésor en Koban (Icône seule) -->
                <a href="?page=privilege" 
                   class="badge bg-warning-lt text-warning p-2 d-inline-flex align-items-center justify-content-center text-decoration-none shadow-none rounded"
                   data-bs-toggle="tooltip" data-bs-placement="bottom"
                   title="Trésor Impérial : <?= number_format($userGoldCoins) ?> Kobans">
                    <i class="fa-solid fa-coins fs-3"></i>
                </a>

                <!-- 2. Sceau Impérial Actif (Icône seule) -->
                <a href="?page=privilege" 
                   class="badge <?= $isSealActive ? 'bg-warning text-warning-fg' : 'bg-secondary-lt text-secondary' ?> p-2 d-inline-flex align-items-center justify-content-center text-decoration-none shadow-none rounded"
                   data-bs-toggle="tooltip" data-bs-placement="bottom"
                   title="<?= $isSealActive ? 'Sceau Impérial Actif (' . htmlspecialchars($sealStatus['remaining_formatted']) . ')' : 'Sceau Impérial Inactif — Décréter le Sceau' ?>">
                    <i class="fa-solid fa-crown fs-3"></i>
                </a>

                <!-- 2bis. Chroniques Féodales du Sengoku (Événement Hebdomadaire) -->
                <button type="button" 
                        class="badge bg-yellow-lt text-warning p-2 border-0 d-inline-flex align-items-center justify-content-center shadow-none rounded cursor-pointer" 
                        onclick="if(window.openWeeklyChronicleModal){window.openWeeklyChronicleModal();} return false;"
                        data-bs-toggle="modal" data-bs-target="#weeklyChronicleModal"
                        title="Chroniques Féodales du Sengoku (Événement Hebdomadaire)">
                    <i class="fa-solid fa-scroll fs-3"></i>
                </button>

                <?php if ($isDevTeamMember): ?>
                    <!-- 3. Studio Dev Team (Icône seule) -->
                    <a href="?page=dev_team" 
                       class="badge bg-purple-lt text-purple p-2 d-inline-flex align-items-center justify-content-center text-decoration-none shadow-none rounded <?= $page === 'dev_team' ? 'border border-purple' : '' ?>"
                       data-bs-toggle="tooltip" data-bs-placement="bottom"
                       title="Studio Dev Team &amp; Métiers du Jeu Vidéo">
                        <i class="fa-solid fa-hammer fs-3"></i>
                    </a>
                <?php endif; ?>

                <?php if ($auth->isAdmin()): ?>
                    <!-- 4. Panneau d'Administration (Icône seule) -->
                    <a href="?page=admin" 
                       class="badge bg-blue-lt text-primary p-2 d-inline-flex align-items-center justify-content-center text-decoration-none shadow-none rounded <?= $page === 'admin' ? 'border border-primary' : '' ?>"
                       data-bs-toggle="tooltip" data-bs-placement="bottom"
                       title="Panneau d'Administration Générale">
                        <i class="fa-solid fa-gear fs-3"></i>
                    </a>
                <?php endif; ?>

                <?php if ($auth->isAdmin() || $isDevTeamMember): ?>
                    <!-- Game Studio (Fusion Studio & Administration) -->
                    <a href="?page=gamestudio" target="_blank"
                       class="badge bg-indigo-lt text-indigo p-2 d-inline-flex align-items-center justify-content-center text-decoration-none shadow-none rounded <?= $page === 'gamestudio' ? 'border border-indigo' : '' ?>"
                       data-bs-toggle="tooltip" data-bs-placement="bottom"
                       title="Game Studio (Ouvrir dans une nouvelle page)">
                        <i class="fa-solid fa-laptop-code fs-3"></i>
                    </a>
                <?php endif; ?>

                <!-- 5. Ambiance Sonore (Icône seule Font Awesome) -->
                <button type="button" 
                        class="badge bg-secondary-lt text-secondary p-2 border-0 d-inline-flex align-items-center justify-content-center shadow-none rounded cursor-pointer" 
                        id="shogun-audio-btn" 
                        onclick="window.shogunAudio && window.shogunAudio.toggle()" 
                        data-bs-toggle="tooltip" data-bs-placement="bottom"
                        title="Ambiance Sonore : Activer / Couper la musique féodale">
                    <i class="fa-solid fa-volume-high fs-3" id="shogun-audio-icon"></i>
                </button>

            </div>
        </div>

        <!-- ── 2. CARROUSEL AUTOMATIQUE DES BÂTIMENTS FÉODAUX (Bannière Cité Castrale) ── -->
        <?php
        $carouselBuildingsMeta = [
            // ── 10 PANORAMAS DU DOMAINE (Dossier assets/carrousel) ──
            'carrousel_01_port_fluvial.jpg' => [
                'name' => 'Port Fluvial & Jonques (Kawa-Minato)',
                'badge' => 'Commerce Fluvial',
                'desc' => 'Quais de déchargement des cargaisons de riz, bois de cèdre et barriques de saké.',
                'icon' => 'fa-solid fa-ship text-info',
                'position' => 'center center'
            ],
            'carrousel_02_porte_otemon.jpg' => [
                'name' => 'Grande Porte Fortifiée (Ōtemon)',
                'badge' => 'Défense de Cité',
                'desc' => 'Entrée monumentale en chêne ferré et pont arqué sur les douves surveillées par la garnison.',
                'icon' => 'fa-solid fa-archway text-warning',
                'position' => 'center center'
            ],
            'carrousel_03_conseil_guerre.jpg' => [
                'name' => 'Conseil de Guerre & Tactique (Gunbai-dō)',
                'badge' => 'Stratégie Militaire',
                'desc' => 'Pavillon seigneurial où sont arrêtés les plans de campagne et alliances provinciales.',
                'icon' => 'fa-solid fa-chess-rook text-danger',
                'position' => 'center center'
            ],
            'carrousel_04_sanctuaire_shinto.jpg' => [
                'name' => 'Sanctuaire Sacré & Cerisiers (Jinja)',
                'badge' => 'Sérénité Divine',
                'desc' => 'Allée de toriis vermillon et prières aux esprits kami protecteurs du fief.',
                'icon' => 'fa-solid fa-torii-gate text-danger',
                'position' => 'center center'
            ],
            'carrousel_05_tir_archers.jpg' => [
                'name' => 'Dojo des Maîtres Archers (Kyūdōjō)',
                'badge' => 'Tir à l\'Arc Yumi',
                'desc' => 'Entraînement de précision martiale et discipline des archers d\'élite du clan.',
                'icon' => 'fa-solid fa-bullseye text-primary',
                'position' => 'center center'
            ],
            'carrousel_06_atelier_potiers.jpg' => [
                'name' => 'Atelier de Céramique & Four Noborigama',
                'badge' => 'Artisanat de l\'Argile',
                'desc' => 'Façonnage des jarres étanches et cuisson des tuiles incombustibles pour la forteresse.',
                'icon' => 'fa-solid fa-cubes-stacked text-orange',
                'position' => 'center center'
            ],
            'carrousel_07_jardin_zen.jpg' => [
                'name' => 'Jardin Zen & Bassin de Koï (Chisen Teien)',
                'badge' => 'Harmonie du Fief',
                'desc' => 'Pont vermillon, carpes sacrées et quiétude inspirant la gouvernance du Daimyō.',
                'icon' => 'fa-solid fa-leaf text-success',
                'position' => 'center center'
            ],
            'carrousel_08_fonderie_canons.jpg' => [
                'name' => 'Fonderie d\'Armes & Canons (Imoji-ya)',
                'badge' => 'Génie & Métallurgie',
                'desc' => 'Coulée de bronze et de fer pour les mortiers, cloches monumentales et défenses.',
                'icon' => 'fa-solid fa-fire-burner text-danger',
                'position' => 'center center'
            ],
            'carrousel_09_moulin_hydraulique.jpg' => [
                'name' => 'Moulin Hydraulique & Moissons (Mizuguruma)',
                'badge' => 'Terroirs d\'Abondance',
                'desc' => 'Meunerie hydraulique au cœur des rizières impériales mûres prêtes pour la récolte.',
                'icon' => 'fa-solid fa-wheat-awn text-warning',
                'position' => 'center center'
            ],
            'carrousel_10_revue_troupes.jpg' => [
                'name' => 'Grande Parade & Revue Martiale (Gunkō Emaki)',
                'badge' => 'Ost Seigneurial',
                'desc' => 'Ralliement solennel des samouraïs et tambours taiko géants sous le crépuscule.',
                'icon' => 'fa-solid fa-flag text-danger',
                'position' => 'center center'
            ],

            // ── BÂTIMENTS URBAINS (Dossier assets/buildings) ──
            'building_tenshu.jpg' => [
                'name' => 'Tenshu (Donjon Castral)',
                'badge' => 'Siège du Commandement',
                'desc' => 'Palais fortifié du Daimyō et réduction du temps de construction des édifices.',
                'icon' => 'fa-solid fa-chess-rook text-danger',
                'position' => 'center center'
            ],
            'building_barracks.jpg' => [
                'name' => 'Dojo Militaire & Caserne',
                'badge' => 'Infanterie Féodale',
                'desc' => 'Enrôlement des Samouraïs d\'élite, archers Yumi et mousquetaires Tanegashima.',
                'icon' => 'fa-solid fa-user-ninja text-primary',
                'position' => 'center center'
            ],
            'building_shipyard.jpg' => [
                'name' => 'Atelier de Siège & Écuries',
                'badge' => 'Cavalerie & Génie Militaire',
                'desc' => 'Élevage de coursiers de guerre, catapultes géantes et convois de ravitaillement.',
                'icon' => 'fa-solid fa-horse text-warning',
                'position' => 'center center'
            ],
            'building_wall.jpg' => [
                'name' => 'Muraille & Remparts de Cité',
                'badge' => 'Enceinte Fortifiée',
                'desc' => 'Murailles en pierre cyclopéenne et douves protégeant le fief (+4% défense garnison/niv).',
                'icon' => 'fa-solid fa-shield-halved text-success',
                'position' => 'center center'
            ],
            'building_storage.jpg' => [
                'name' => 'Hangar de Cèdre & Dépôt',
                'badge' => 'Entrepôt de Bois',
                'desc' => 'Réserves stratégiques de bois de cèdre et poutres de charpente.',
                'icon' => 'fa-solid fa-tree text-success',
                'position' => 'center center'
            ],
            'building_tank.jpg' => [
                'name' => 'Grenier Kura & Réserve de Granit',
                'badge' => 'Réserves Vivrières & Roches',
                'desc' => 'Grands silos kura protégeant la pierre de taille et le riz impérial.',
                'icon' => 'fa-solid fa-mountain text-secondary',
                'position' => 'center center'
            ],
            'building_research_lab.jpg' => [
                'name' => 'Académie des Savoirs & Forge',
                'badge' => 'Recherche Stratégique',
                'desc' => 'Perfectionnement du tamahagane, métallurgie et doctrines de guerre ancestrales.',
                'icon' => 'fa-solid fa-scroll text-info',
                'position' => 'center center'
            ],
            'building_market.jpg' => [
                'name' => 'Marché Féodal & Caravanes',
                'badge' => 'Commerce Provincial',
                'desc' => 'Échanges de denrées et marchandises avec les marchands itinérants.',
                'icon' => 'fa-solid fa-coins text-warning',
                'position' => 'center center'
            ],
            'building_radar.jpg' => [
                'name' => 'Tour de Guet Yagura',
                'badge' => 'Vigie & Feux d\'Alarme',
                'desc' => 'Surveillance provinciale et détection précoce des armées et espions ennemis.',
                'icon' => 'fa-solid fa-eye text-danger',
                'position' => 'center center'
            ],
            'building_quantum_vault.jpg' => [
                'name' => 'Cachette Secrète Sous Terre',
                'badge' => 'Caveau Anti-Pillage',
                'desc' => 'Réserve inviolable dissimulée sous terre protégeant les stocks du pillage.',
                'icon' => 'fa-solid fa-vault text-purple',
                'position' => 'center center'
            ],
            'building_embassy.jpg' => [
                'name' => 'Pavillon Diplomatique des Clans',
                'badge' => 'Traités d\'Alliance',
                'desc' => 'Pactes d\'alliance scellés sous les auspices des cérémonies du thé.',
                'icon' => 'fa-solid fa-handshake text-primary',
                'position' => 'center center'
            ],
            'building_sawmill.jpg' => [
                'name' => 'Scierie Kizukuri',
                'badge' => 'Atelier (+5% Bois)',
                'desc' => 'Façonnage des poutres et débitage des troncs de cèdre.',
                'icon' => 'fa-solid fa-hammer text-warning',
                'position' => 'center center'
            ],
            'building_stonemason.jpg' => [
                'name' => 'Tailleuse de Granit',
                'badge' => 'Atelier (+5% Pierre)',
                'desc' => 'Briqueterie et polissage des blocs de granit pour les forteresses.',
                'icon' => 'fa-solid fa-trowel-bricks text-secondary',
                'position' => 'center center'
            ],
            'building_grain_mill.jpg' => [
                'name' => 'Meunerie de Riz Impérial',
                'badge' => 'Atelier (+5% Riz)',
                'desc' => 'Moulins à eau convertissant le riz koku en farine nourricière.',
                'icon' => 'fa-solid fa-wheat-awn text-warning',
                'position' => 'center center'
            ],
            'building_blacksmith.jpg' => [
                'name' => 'Forge d\'Armes & d\'Armures',
                'badge' => 'Armurerie Ancestrale',
                'desc' => 'Fabrication des lames légendaires et des armures de samouraï.',
                'icon' => 'fa-solid fa-fire-burner text-danger',
                'position' => 'center center'
            ],
            'building_teahouse.jpg' => [
                'name' => 'Pavillon de Thé Chashitsu',
                'badge' => 'Sérénité Démographique',
                'desc' => 'Cérémonie du thé favorisant le contentement et l\'attrait du fief.',
                'icon' => 'fa-solid fa-leaf text-teal',
                'position' => 'center center'
            ],
            'building_tournament_square.jpg' => [
                'name' => 'Place d\'Armes & Tournoi',
                'badge' => 'Joutes Féodales',
                'desc' => 'Arène martiale et ralliement des troupes provinciales.',
                'icon' => 'fa-solid fa-flag text-danger',
                'position' => 'center center'
            ]
        ];

        // 1. Découverte prioritaire du dossier public/assets/carrousel
        $carrouselAssetsDir = __DIR__ . '/../../public/assets/carrousel';
        $buildingsAssetsDir = __DIR__ . '/../../public/assets/buildings';
        $carouselSlides = [];

        if (is_dir($carrouselAssetsDir)) {
            $foundCarrouselFiles = glob($carrouselAssetsDir . '/*.jpg');
            if (!empty($foundCarrouselFiles)) {
                // Mélange aléatoire des estampes pour une rotation homogène à chaque rechargement
                shuffle($foundCarrouselFiles);
                foreach ($foundCarrouselFiles as $filePath) {
                    $bName = basename($filePath);
                    $meta = $carouselBuildingsMeta[$bName] ?? null;
                    if (!$meta) {
                        $cleanTitle = trim(str_replace(['carrousel_', '.jpg', '.jpeg', '.png'], '', strtolower($bName)));
                        $slideName = is_numeric($cleanTitle) ? 'Domaine Féodal #' . (int)$cleanTitle : ucwords(str_replace('_', ' ', $cleanTitle));
                        $meta = [
                            'name' => $slideName,
                            'badge' => 'Domaine Féodal',
                            'desc' => 'Panorama stratégique du Japon féodal.',
                            'icon' => 'fa-solid fa-mountain-sun text-warning',
                            'position' => 'center center'
                        ];
                    }
                    $carouselSlides[] = [
                        'url' => '/public/assets/carrousel/' . $bName,
                        'name' => $meta['name'],
                        'badge' => $meta['badge'],
                        'desc' => $meta['desc'],
                        'icon' => $meta['icon'],
                        'position' => $meta['position'] ?? 'center center'
                    ];
                }
            }
        }

        // 2. Fallback sur assets/buildings si le carrousel dédié est vide
        if (empty($carouselSlides) && is_dir($buildingsAssetsDir)) {
            $foundFiles = glob($buildingsAssetsDir . '/*.jpg');
            if ($foundFiles) {
                shuffle($foundFiles);

                foreach ($foundFiles as $filePath) {
                    $bName = basename($filePath);
                    $meta = $carouselBuildingsMeta[$bName] ?? [
                        'name' => ucwords(str_replace(['building_', '_', '.jpg'], ['', ' ', ''], $bName)),
                        'badge' => 'Édifice Castral',
                        'desc' => 'Bâtiment traditionnel du domaine féodal.',
                        'icon' => 'fa-solid fa-landmark text-warning',
                        'position' => 'center center'
                    ];
                    $carouselSlides[] = [
                        'url' => '/public/assets/buildings/' . $bName,
                        'name' => $meta['name'],
                        'badge' => $meta['badge'],
                        'desc' => $meta['desc'],
                        'icon' => $meta['icon'],
                        'position' => $meta['position'] ?? 'center center'
                    ];
                }
            }
        }

        // 3. Brassage aléatoire final des diapositives pour garantir un affichage homogène et varié
        if (!empty($carouselSlides)) {
            shuffle($carouselSlides);
        }
        ?>

        <style>
        #headerBuildingsCarousel {
            width: 100% !important;
            height: 250px !important;
            background: #0f172a;
        }
        @media (max-width: 768px) {
            #headerBuildingsCarousel {
                height: 150px !important;
            }
        }
        </style>

        <div class="w-100 p-0 m-0 d-print-none">
            <div id="headerBuildingsCarousel" 
                 class="carousel slide carousel-fade border-top border-bottom border-dark border-opacity-25 overflow-hidden position-relative w-100" 
                 data-bs-ride="carousel" 
                 data-bs-interval="3500" 
                 data-bs-pause="hover">
                
                <!-- Diapositives -->
                <div class="carousel-inner h-100 w-100">
                    <?php foreach ($carouselSlides as $idx => $slide): ?>
                        <div class="carousel-item h-100 w-100 <?= $idx === 0 ? 'active' : '' ?>">
                            <a href="?page=city" class="d-block w-100 h-100 position-relative text-decoration-none" title="<?= htmlspecialchars($slide['name']) ?> - Visiter la Cité Castrale">
                                <img src="<?= htmlspecialchars($slide['url']) ?>" 
                                     class="d-block w-100 h-100" 
                                     alt="<?= htmlspecialchars($slide['name']) ?>"
                                     style="object-fit: cover; object-position: <?= htmlspecialchars($slide['position'] ?? 'center center') ?>; filter: brightness(0.92);">
                                
                                <!-- Voile dégradé cinéma -->
                                <div class="position-absolute top-0 start-0 w-100 h-100" 
                                     style="background: linear-gradient(180deg, rgba(15,23,42,0.2) 0%, rgba(15,23,42,0.35) 40%, rgba(15,23,42,0.85) 100%);"></div>

                                <!-- Légende supérieure : Tag du Domaine -->
                                <div class="position-absolute top-0 start-0 p-3 d-flex align-items-center gap-2">
                                    <span class="badge bg-dark bg-opacity-75 text-warning font-monospace border border-secondary shadow-sm" style="backdrop-filter: blur(4px); font-size: 0.72rem;">
                                        <i class="fa-solid fa-torii-gate me-1"></i><?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?>
                                    </span>
                                    <span class="badge bg-primary bg-opacity-75 text-white font-monospace border border-primary-subtle shadow-sm" style="backdrop-filter: blur(4px); font-size: 0.72rem;">
                                        <?= htmlspecialchars($slide['badge']) ?>
                                    </span>
                                </div>

                                <!-- Bouton raccourci Cité en haut à droite -->
                                <div class="position-absolute top-0 end-0 p-3">
                                    <span class="badge bg-danger bg-opacity-90 text-white font-monospace shadow-sm" style="backdrop-filter: blur(4px); font-size: 0.72rem;">
                                        <i class="fa-solid fa-city me-1"></i>Cité Castrale
                                    </span>
                                </div>

                                <!-- Légende inférieure : Titre et description du bâtiment -->
                                <div class="position-absolute bottom-0 start-0 end-0 px-3 px-lg-4 pb-3 text-start text-white">
                                    <h3 class="mb-1 fw-bold d-flex align-items-center gap-2 text-white" style="font-size: 1.15rem; text-shadow: 0 2px 6px rgba(0,0,0,0.9);">
                                        <i class="<?= htmlspecialchars($slide['icon']) ?>"></i>
                                        <span><?= htmlspecialchars($slide['name']) ?></span>
                                    </h3>
                                    <p class="mb-0 text-white-50 text-truncate d-none d-sm-block" style="font-size: 0.82rem; text-shadow: 0 1px 4px rgba(0,0,0,0.9); max-width: 80%;">
                                        <?= htmlspecialchars($slide['desc']) ?>
                                    </p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Contrôles Précédent / Suivant -->
                <button class="carousel-control-prev" type="button" data-bs-target="#headerBuildingsCarousel" data-bs-slide="prev" style="width: 4%;">
                    <span class="carousel-control-prev-icon p-2 rounded bg-dark bg-opacity-50" aria-hidden="true" style="width: 28px; height: 28px; background-size: 60%;"></span>
                    <span class="visually-hidden">Précédent</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#headerBuildingsCarousel" data-bs-slide="next" style="width: 4%;">
                    <span class="carousel-control-next-icon p-2 rounded bg-dark bg-opacity-50" aria-hidden="true" style="width: 28px; height: 28px; background-size: 60%;"></span>
                    <span class="visually-hidden">Suivant</span>
                </button>
            </div>
        </div>

        <script>
        document.addEventListener("DOMContentLoaded", function () {
            var carEl = document.getElementById('headerBuildingsCarousel');
            if (carEl && typeof bootstrap !== 'undefined' && bootstrap.Carousel) {
                new bootstrap.Carousel(carEl, {
                    interval: 3500,
                    ride: 'carousel',
                    pause: 'hover'
                });
            }
        });
        </script>

        <!-- ── BARRE COMPACTE D'AFFICHAGE DES RESSOURCES (Option A : Tabler Clair Épuré) ── -->
        <?php if ($planet): ?>
        <?php
        $metalMax = max(1, (float)($planet['metal_max'] ?? 15000));
        $pctMetal = min(100, max(0, ((float)($planet['metal'] ?? 0) / $metalMax) * 100));

        $crystalMax = max(1, (float)($planet['crystal_max'] ?? 15000));
        $pctCrystal = min(100, max(0, ((float)($planet['crystal'] ?? 0) / $crystalMax) * 100));

        $deutMax = max(1, (float)($planet['deuterium_max'] ?? 15000));
        $pctDeut = min(100, max(0, ((float)($planet['deuterium'] ?? 0) / $deutMax) * 100));

        $fishStock = (float)($planet['fish'] ?? 0);
        $fishMax = max(1, (float)($planet['fish_max'] ?? $deutMax));
        $pctFish = min(100, max(0, ($fishStock / $fishMax) * 100));

        $prodMetal = (float)($planet['prod_rates']['metal'] ?? 0);
        $prodCrystal = (float)($planet['prod_rates']['crystal'] ?? 0);
        $prodDeut = (float)($planet['prod_rates']['deuterium'] ?? 0);

        // Terroirs agricoles spécialisés (Argile, Thé, Soja)
        $ruralPlotsData = [];
        if (!empty($planet['id'])) {
            try {
                $stmtRural = $db->prepare("SELECT structure_type, level, max_level, prod_hourly FROM planet_rural_plots WHERE planet_id = ?");
                $stmtRural->execute([(int)$planet['id']]);
                while ($rRow = $stmtRural->fetch(PDO::FETCH_ASSOC)) {
                    $ruralPlotsData[$rRow['structure_type']] = $rRow;
                }
            } catch (Throwable $e) {
                $ruralPlotsData = [];
            }
        }
        $clayHourly = (float)($planet['prod_rates']['clay'] ?? ($ruralPlotsData['fosse_argile']['prod_hourly'] ?? 0));
        $clayLvl = (int)($ruralPlotsData['fosse_argile']['level'] ?? 1);
        $clayMaxLvl = max(1, (int)($ruralPlotsData['fosse_argile']['max_level'] ?? 30));
        $clayStock = (float)($planet['clay'] ?? 0);
        $clayMax = max(1, (int)($planet['clay_max'] ?? 10000));
        $pctClayStock = min(100, max(0, ($clayStock / $clayMax) * 100));

        $teaHourly = (float)($planet['prod_rates']['tea'] ?? ($ruralPlotsData['culture_the']['prod_hourly'] ?? 0));
        $teaLvl = (int)($ruralPlotsData['culture_the']['level'] ?? 1);
        $teaMaxLvl = max(1, (int)($ruralPlotsData['culture_the']['max_level'] ?? 30));
        $teaStock = (float)($planet['tea'] ?? 0);
        $teaMax = max(1, (int)($planet['tea_max'] ?? 10000));
        $pctTeaStock = min(100, max(0, ($teaStock / $teaMax) * 100));

        $soyHourly = (float)($planet['prod_rates']['soybean'] ?? ($ruralPlotsData['champ_soja']['prod_hourly'] ?? 0));
        $soyLvl = (int)($ruralPlotsData['champ_soja']['level'] ?? 1);
        $soyMaxLvl = max(1, (int)($ruralPlotsData['champ_soja']['max_level'] ?? 30));
        $soyStock = (float)($planet['soybean'] ?? 0);
        $soyMax = max(1, (int)($planet['soybean_max'] ?? 10000));
        $pctSoyStock = min(100, max(0, ($soyStock / $soyMax) * 100));

        $flourStock = (float)($planet['rice_flour'] ?? 0);
        $flourMax = max(1, (int)($planet['rice_flour_max'] ?? 10000));
        $pctFlour = min(100, max(0, ($flourStock / $flourMax) * 100));

        $sakeStock = (float)($planet['sake'] ?? 0);
        $sakeMax = max(1, (int)($planet['sake_max'] ?? 10000));
        $pctSake = min(100, max(0, ($sakeStock / $sakeMax) * 100));

        $beamsStock = (float)($planet['wooden_beams'] ?? 0);
        $beamsMax = max(1, (int)($planet['wooden_beams_max'] ?? 10000));
        $pctBeams = min(100, max(0, ($beamsStock / $beamsMax) * 100));

        $charcoalStock = (float)($planet['charcoal'] ?? 0);
        $charcoalMax = max(1, (int)($planet['charcoal_max'] ?? 10000));
        $pctCharcoal = min(100, max(0, ($charcoalStock / $charcoalMax) * 100));

        $ironSandStock = (float)($planet['iron_sand'] ?? 0);
        $ironSandMax = max(1, (int)($planet['iron_sand_max'] ?? 15000));
        $pctIronSand = min(100, max(0, ($ironSandStock / $ironSandMax) * 100));

        $sulfurStock = (float)($planet['sulfur'] ?? 0);
        $sulfurMax = max(1, (int)($planet['sulfur_max'] ?? 10000));
        $pctSulfur = min(100, max(0, ($sulfurStock / $sulfurMax) * 100));

        $nitrateStock = (float)($planet['nitrate'] ?? 0);
        $nitrateMax = max(1, (int)($planet['nitrate_max'] ?? 10000));
        $pctNitrate = min(100, max(0, ($nitrateStock / $nitrateMax) * 100));

        $eBalance = (int)($planet['energy_max'] ?? 0) - (int)($planet['energy_used'] ?? 0);
        $eOk = ($eBalance >= 0);
        $pctEnergy = ($planet['energy_max'] > 0) ? min(100, max(0, ((int)($planet['energy_used'] ?? 0) / (int)$planet['energy_max']) * 100)) : 0;

        $curPop = (int)($planet['population'] ?? 100);
        $maxPop = max(1, (int)($planet['population_max'] ?? 100));
        $pctPop = min(100, max(0, ($curPop / $maxPop) * 100));

        // Répartition des villageois par Castes Féodales (Shinōkōshō)
        require_once __DIR__ . '/../../core/SocialCasteEngine.php';
        $socialCastesData = $planet['social_castes'] ?? null;
        if (!$socialCastesData) {
            $socialCastesData = SocialCasteEngine::processSocialTick($planet, 0.0);
            $planet['social_castes'] = $socialCastesData;
            $planet['civilization_level'] = $socialCastesData['civilization_level'];
            $planet['civilization_name'] = $socialCastesData['civilization_name'];
        }
        $civLevel = (int)($socialCastesData['civilization_level'] ?? 1);
        $civName = (string)($socialCastesData['civilization_name'] ?? 'Nōmin (Paysans & Ouvriers)');
        $castesList = $socialCastesData['castes'] ?? [];

        // Main-d'œuvre du domaine (Ouvriers affectés / pourvus vs requis)
        $workforce = $planet['workforce'] ?? null;
        if (!$workforce && !empty($planet['id'])) {
            require_once __DIR__ . '/../../core/PopulationEngine.php';
            $pBld = $planetBuildings ?? $planetEngine->getBuildings((int)$planet['id']);
            $pFld = $planetEngine->getFields((int)$planet['id']);
            $workforce = PopulationEngine::calculateWorkforceSummary($planet, $pBld, $pFld, $maxPop);
        }
        $assignedWorkers = (int)($workforce['assigned_workers'] ?? 0);
        $requiredWorkers = (int)($workforce['required_workers'] ?? 0);
        $isUnderstaffed = !empty($workforce['is_understaffed']);
        $understaffedMalus = (float)($workforce['understaffed_malus_pct'] ?? 0);
        $idleWorkers = (int)($workforce['idle_workers'] ?? 0);
        $pctWorkforce = ($requiredWorkers > 0) ? min(100, max(0, round(($assignedWorkers / $requiredWorkers) * 100))) : 100;

        $workforceTooltipParts = [
            "<strong>Main-d'œuvre &amp; Postes de Travail</strong>",
            "Pourvus : <strong class='" . ($isUnderstaffed ? 'text-warning' : 'text-success') . "'>" . number_format($assignedWorkers) . "</strong> / " . number_format($requiredWorkers) . " requis (" . $pctWorkforce . "%)"
        ];
        if ($isUnderstaffed) {
            $workforceTooltipParts[] = "<span class='text-warning'><i class='fa-solid fa-triangle-exclamation me-1'></i>Sous-effectif : -" . $understaffedMalus . "% vitesse</span>";
        } else {
            $workforceTooltipParts[] = "<span class='text-success'><i class='fa-solid fa-check me-1'></i>Effectif complet (+0% malus)</span>";
        }
        if ($idleWorkers > 0) {
            $workforceTooltipParts[] = "<span class='text-muted'><i class='fa-solid fa-user-clock me-1'></i>Sans affectation : " . number_format($idleWorkers) . "</span>";
        }
        $workforceTooltip = implode('<br>', $workforceTooltipParts);

        // Moral & Contentement féodal des villageois (0 - 100%)
        require_once __DIR__ . '/../../core/PopulationEngine.php';
        $contentmentDetails = $planet['contentment_details'] ?? null;
        if (!$contentmentDetails && !empty($planet['id'])) {
            $pBld = $planetBuildings ?? ($planetEngine ? $planetEngine->getBuildings((int)$planet['id']) : []);
            $activeFeast = $planetEngine ? $planetEngine->getActiveFeast((int)$planet['id']) : null;
            $contentmentDetails = PopulationEngine::calculateContentment($planet, $activeFeast, $workforce, $pBld);
        }
        $contentmentScore = (int)($contentmentDetails['score'] ?? ($planet['contentment'] ?? 85));
        $contentmentStatus = $contentmentDetails['status_label'] ?? (($contentmentScore >= 80) ? 'Euphorique & Prospère' : (($contentmentScore >= 50) ? 'Paisible & Satisfait' : (($contentmentScore >= 25) ? 'Inquiet & Tendu' : 'Exode Imminent !')));
        $contentmentBadgeColor = $contentmentDetails['badge_color'] ?? (($contentmentScore >= 80) ? 'success' : (($contentmentScore >= 50) ? 'teal' : (($contentmentScore >= 25) ? 'warning' : 'danger')));
        $contentmentIcon = $contentmentDetails['icon'] ?? (($contentmentScore >= 80) ? 'fa-face-laugh-beam' : (($contentmentScore >= 50) ? 'fa-face-smile' : (($contentmentScore >= 25) ? 'fa-face-meh' : 'fa-face-frown')));

        $moraleTooltipParts = [
            "<strong>Moral &amp; Contentement Féodal</strong>",
            "Indice : <strong class='text-{$contentmentBadgeColor}'>{$contentmentScore}%</strong> &bull; <span class='text-muted'>{$contentmentStatus}</span>"
        ];
        if (!empty($contentmentDetails['has_food'])) {
            $moraleTooltipParts[] = "<span class='text-success'><i class='fa-solid fa-bowl-rice me-1'></i>Vivres assurées (+15%)</span>";
        } else {
            $moraleTooltipParts[] = "<span class='text-danger'><i class='fa-solid fa-triangle-exclamation me-1'></i>Famine / Pénurie de riz (-55%)</span>";
        }
        if (!empty($contentmentDetails['has_energy'])) {
            $moraleTooltipParts[] = "<span class='text-teal'><i class='fa-solid fa-torii-gate me-1'></i>Sérénité Shintō stable</span>";
        } else {
            $moraleTooltipParts[] = "<span class='text-danger'><i class='fa-solid fa-triangle-exclamation me-1'></i>Déficit spirituel (-20%)</span>";
        }
        if (!empty($contentmentDetails['sake_bonus_active'])) {
            $moraleTooltipParts[] = "<span class='text-purple'><i class='fa-solid fa-wine-bottle me-1'></i>Saké Impérial en réserve (+15%)</span>";
        }
        if (!empty($contentmentDetails['feast_points'])) {
            $moraleTooltipParts[] = "<span class='text-warning'><i class='fa-solid fa-drum me-1'></i>Célébration Matsuri (+10%)</span>";
        }
        if (!empty($contentmentDetails['delinquency_delta']) && $contentmentDetails['delinquency_delta'] < 0) {
            $moraleTooltipParts[] = "<span class='text-warning'><i class='fa-solid fa-user-slash me-1'></i>Chômage / Inactifs (" . $contentmentDetails['delinquency_delta'] . "%)</span>";
        }
        $moraleTooltip = implode('<br>', $moraleTooltipParts);

        // Niveaux des terroirs ruraux & bonus détaillés pour les fiches au clic
        $foretLvl      = (int)($ruralPlotsData['foret']['level'] ?? 1);
        $foretMaxLvl   = max(1, (int)($ruralPlotsData['foret']['max_level'] ?? 30));
        $carriereLvl   = (int)($ruralPlotsData['carriere']['level'] ?? 1);
        $carriereMaxLvl= max(1, (int)($ruralPlotsData['carriere']['max_level'] ?? 30));
        $riziereLvl    = (int)($ruralPlotsData['riziere']['level'] ?? 1);
        $riziereMaxLvl = max(1, (int)($ruralPlotsData['riziere']['max_level'] ?? 30));
        $shrineLvl     = (int)($ruralPlotsData['sanctuaire_shinto']['level'] ?? 1);
        $shrineMaxLvl  = max(1, (int)($ruralPlotsData['sanctuaire_shinto']['max_level'] ?? 30));

        $bldBonuses    = $planet['prod_rates']['building_bonuses'] ?? [];
        $sawmillBonus  = (int)($bldBonuses['sawmill'] ?? 0);
        $stonemasonBonus = (int)($bldBonuses['stonemason'] ?? 0);
        $grainMillBonus = (int)($bldBonuses['grain_mill'] ?? 0);
        $teahouseBonus = (int)($bldBonuses['teahouse'] ?? 0);

        $oasisBonuses  = $planet['prod_rates']['oasis_bonuses'] ?? ['wood' => 0, 'stone' => 0, 'rice' => 0];
        $oasisWood     = (int)($oasisBonuses['wood'] ?? 0);
        $oasisStone    = (int)($oasisBonuses['stone'] ?? 0);
        $oasisRice     = (int)($oasisBonuses['rice'] ?? 0);

        $heroBonuses   = $planet['prod_rates']['hero_bonuses'] ?? ['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
        $heroMetal     = (int)($heroBonuses['metal'] ?? 0);
        $heroCrystal   = (int)($heroBonuses['crystal'] ?? 0);
        $heroDeut      = (int)($heroBonuses['deuterium'] ?? 0);

        $matsuriBonus  = (int)($planet['prod_rates']['matsuri_bonus'] ?? 0);
        $energyRatio   = (float)($planet['prod_rates']['energy_ratio'] ?? ($eOk ? 1.0 : 0.10));
        $workforceRatio= (float)($planet['prod_rates']['workforce_ratio'] ?? ($workforce['workforce_ratio'] ?? 1.0));
        $globalEffPct  = (int)round($energyRatio * $workforceRatio * 100);

        $formatEtaFull = static function (float $current, float $max, float $prodHourly): string {
            if ($current >= $max) {
                return 'Entrepôt saturé (100%)';
            }
            if ($prodHourly <= 0) {
                return 'Production à l\'arrêt';
            }
            $hoursLeft = ($max - $current) / $prodHourly;
            if ($hoursLeft < 1) {
                return 'Saturé dans ~' . max(1, (int)round($hoursLeft * 60)) . ' min';
            }
            $h = (int)floor($hoursLeft);
            $m = (int)round(($hoursLeft - $h) * 60);
            if ($h >= 48) {
                return 'Saturé dans ~' . round($hoursLeft / 24, 1) . ' j';
            }
            return 'Saturé dans ~' . $h . 'h' . ($m > 0 ? ' ' . str_pad((string)$m, 2, '0', STR_PAD_LEFT) . 'm' : '');
        };
        ?>

        <style>
        .resource-dropdown-item {
            transition: background-color 0.15s ease;
            border-radius: 6px;
        }
        .resource-dropdown-item:hover {
            background-color: rgba(0, 0, 0, 0.04);
        }
        .hud-res-track {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-behavior: smooth;
            scrollbar-width: none;
            -ms-overflow-style: none;
            flex: 1 1 auto;
            min-width: 0;
            padding: 2px 1px;
        }
        .hud-res-track::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
        .hud-res-pill {
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 8px;
            padding: 4px 10px 5px 10px;
            min-width: 122px;
            flex-shrink: 0;
            transition: all 0.15s ease;
            cursor: pointer;
            text-align: left;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .hud-res-pill:hover, .hud-res-pill[aria-expanded="true"] {
            border-color: rgba(32, 107, 196, 0.45);
            background: #f8fafc;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
            transform: translateY(-1px);
        }
        .hud-res-pill::after {
            display: none !important;
        }
        .hud-slider-arrow {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 46px;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid rgba(15, 23, 42, 0.14);
            color: #1e293b;
            flex-shrink: 0;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
            padding: 0;
        }
        .hud-slider-arrow:hover:not(:disabled) {
            background: #f1f5f9;
            border-color: rgba(32, 107, 196, 0.45);
            color: #206bc4;
        }
        .hud-slider-arrow:disabled {
            opacity: 0.38;
            cursor: default;
        }
        @media (max-width: 991.98px) {
            .hud-res-pill {
                min-width: 110px;
                padding: 3px 8px 4px 8px;
            }
            .hud-slider-arrow {
                width: 28px;
                height: 42px;
            }
        }
        </style>

        <div class="border-bottom bg-light-subtle py-1.5 shadow-sm d-print-none position-relative" style="z-index: 1050;" id="hudResHubWrapper">
            <div class="container-fluid px-3 px-lg-4">
                <div class="d-flex align-items-center justify-content-between gap-2 w-100">

                    <!-- Flèche de glissement Gauche (alignée sur le bord gauche des cards du corps de page) -->
                    <button type="button" class="hud-slider-arrow" id="hudScrollLeft" title="Faire glisser les ressources vers la gauche" aria-label="Précédent">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <!-- Piste horizontale glissante sans barre de scroll visible -->
                    <div class="hud-res-track" id="hudResTrack">

                    <!-- 1. BOIS DE CÈDRE -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle" type="button" id="pillResWood" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Bois de Cèdre : cliquer pour le détail de production">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-tree text-success"></i> Bois
                                </span>
                                <span class="font-monospace fw-bold text-success" style="font-size:0.7rem;">+<?= number_format($prodMetal) ?>/h</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold text-dark" style="font-size:0.86rem;"
                                      id="res-val-metal"
                                      data-current="<?= (float)$planet['metal'] ?>"
                                      data-max="<?= $metalMax ?>"
                                      data-prod="<?= $prodMetal ?>">
                                    <?= number_format((int)$planet['metal']) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;"><?= (int)round($pctMetal) ?>%</span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar <?= $pctMetal >= 95 ? 'bg-danger' : ($pctMetal >= 80 ? 'bg-warning' : 'bg-success') ?>" id="bar-metal" style="width:<?= $pctMetal ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-lg p-3 border" aria-labelledby="pillResWood" style="min-width:290px; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-success-lt text-success rounded"><i class="fa-solid fa-tree"></i></span>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;">Bois de Cèdre</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Forêt de Cèdres &bull; Niv. <?= $foretLvl ?>/<?= $foretMaxLvl ?></div>
                                    </div>
                                </div>
                                <span class="badge bg-success-lt text-success font-monospace">+<?= number_format($prodMetal) ?>/h</span>
                            </div>
                            <div class="small font-monospace d-flex flex-column gap-1 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Stock / Entrepôt :</span>
                                    <strong class="text-dark"><?= number_format((int)$planet['metal']) ?> / <?= number_format($metalMax) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Saturation :</span>
                                    <span class="<?= $pctMetal >= 95 ? 'text-danger fw-bold' : 'text-secondary' ?>"><?= $formatEtaFull((float)$planet['metal'], $metalMax, $prodMetal) ?></span>
                                </div>
                                <hr class="my-1">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Atelier (Scierie) :</span>
                                    <span class="<?= $sawmillBonus > 0 ? 'text-success fw-bold' : 'text-muted' ?>">+<?= $sawmillBonus ?>%</span>
                                </div>
                                <?php if ($oasisWood > 0): ?>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Oasis forestières :</span>
                                    <span class="text-teal fw-bold">+<?= $oasisWood ?>%</span>
                                </div>
                                <?php endif; ?>
                                <?php if ($heroMetal > 0): ?>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Héros Samouraï :</span>
                                    <span class="text-warning fw-bold">+<?= number_format($heroMetal) ?>/h</span>
                                </div>
                                <?php endif; ?>
                                <?php if ($matsuriBonus > 0): ?>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Fête Matsuri :</span>
                                    <span class="text-purple fw-bold">+<?= $matsuriBonus ?>%</span>
                                </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Efficacité globale :</span>
                                    <span class="<?= $globalEffPct < 100 ? 'text-danger fw-bold' : 'text-success' ?>"><?= $globalEffPct ?>%</span>
                                </div>
                                <div class="d-flex justify-content-between pt-1 border-top">
                                    <span class="text-muted">Projection 24h :</span>
                                    <strong class="text-success">+<?= number_format($prodMetal * 24) ?> / jour</strong>
                                </div>
                            </div>
                            <div class="d-flex gap-1 pt-1">
                                <a href="?page=resources" class="btn btn-xs btn-success flex-fill"><i class="fa-solid fa-tree me-1"></i>Forêt</a>
                                <a href="?page=building&code=sawmill" class="btn btn-xs btn-outline-secondary flex-fill"><i class="fa-solid fa-saw-blade me-1"></i>Scierie</a>
                            </div>
                        </div>
                    </div>

                    <!-- 2. PIERRE DE GRANIT -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle" type="button" id="pillResStone" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Pierre de Granit : cliquer pour le détail de production">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-mountain text-primary"></i> Granit
                                </span>
                                <span class="font-monospace fw-bold text-primary" style="font-size:0.7rem;">+<?= number_format($prodCrystal) ?>/h</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold text-dark" style="font-size:0.86rem;"
                                      id="res-val-crystal"
                                      data-current="<?= (float)$planet['crystal'] ?>"
                                      data-max="<?= $crystalMax ?>"
                                      data-prod="<?= $prodCrystal ?>">
                                    <?= number_format((int)$planet['crystal']) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;"><?= (int)round($pctCrystal) ?>%</span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar <?= $pctCrystal >= 95 ? 'bg-danger' : ($pctCrystal >= 80 ? 'bg-warning' : 'bg-primary') ?>" id="bar-crystal" style="width:<?= $pctCrystal ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-lg p-3 border" aria-labelledby="pillResStone" style="min-width:290px; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-primary-lt text-primary rounded"><i class="fa-solid fa-mountain"></i></span>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;">Pierre de Granit</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Carrière de Granit &bull; Niv. <?= $carriereLvl ?>/<?= $carriereMaxLvl ?></div>
                                    </div>
                                </div>
                                <span class="badge bg-primary-lt text-primary font-monospace">+<?= number_format($prodCrystal) ?>/h</span>
                            </div>
                            <div class="small font-monospace d-flex flex-column gap-1 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Stock / Entrepôt :</span>
                                    <strong class="text-dark"><?= number_format((int)$planet['crystal']) ?> / <?= number_format($crystalMax) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Saturation :</span>
                                    <span class="<?= $pctCrystal >= 95 ? 'text-danger fw-bold' : 'text-secondary' ?>"><?= $formatEtaFull((float)$planet['crystal'], $crystalMax, $prodCrystal) ?></span>
                                </div>
                                <hr class="my-1">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Tailleur de Pierre :</span>
                                    <span class="<?= $stonemasonBonus > 0 ? 'text-primary fw-bold' : 'text-muted' ?>">+<?= $stonemasonBonus ?>%</span>
                                </div>
                                <?php if ($oasisStone > 0): ?>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Oasis rocheuses :</span>
                                    <span class="text-teal fw-bold">+<?= $oasisStone ?>%</span>
                                </div>
                                <?php endif; ?>
                                <?php if ($heroCrystal > 0): ?>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Héros Samouraï :</span>
                                    <span class="text-warning fw-bold">+<?= number_format($heroCrystal) ?>/h</span>
                                </div>
                                <?php endif; ?>
                                <?php if ($matsuriBonus > 0): ?>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Fête Matsuri :</span>
                                    <span class="text-purple fw-bold">+<?= $matsuriBonus ?>%</span>
                                </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Efficacité globale :</span>
                                    <span class="<?= $globalEffPct < 100 ? 'text-danger fw-bold' : 'text-success' ?>"><?= $globalEffPct ?>%</span>
                                </div>
                                <div class="d-flex justify-content-between pt-1 border-top">
                                    <span class="text-muted">Projection 24h :</span>
                                    <strong class="text-primary">+<?= number_format($prodCrystal * 24) ?> / jour</strong>
                                </div>
                            </div>
                            <div class="d-flex gap-1 pt-1">
                                <a href="?page=resources" class="btn btn-xs btn-primary flex-fill"><i class="fa-solid fa-mountain me-1"></i>Carrière</a>
                                <a href="?page=building&code=stonemason" class="btn btn-xs btn-outline-secondary flex-fill"><i class="fa-solid fa-hammer me-1"></i>Tailleur</a>
                            </div>
                        </div>
                    </div>

                    <!-- 3. TERRE ARGILE -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle" type="button" id="pillResClay" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Terre Argile (Argile Alluviale) : cliquer pour le détail de production">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-cubes-stacked text-orange"></i> Argile
                                </span>
                                <span class="font-monospace fw-bold text-orange" style="font-size:0.7rem;">+<?= number_format($clayHourly) ?>/h</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold text-dark" style="font-size:0.86rem;"
                                      id="res-val-clay"
                                      data-current="<?= $clayStock ?>"
                                      data-max="<?= $clayMax ?>"
                                      data-prod="<?= $clayHourly ?>">
                                    <?= number_format((int)$clayStock) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;"><?= (int)round($pctClayStock) ?>%</span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar bg-orange" id="bar-clay" style="width:<?= $pctClayStock ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-lg p-3 border" aria-labelledby="pillResClay" style="min-width:290px; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-orange-lt text-orange rounded"><i class="fa-solid fa-cubes-stacked"></i></span>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;">Terre Argile (Alluviale)</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Fosse d'Argile &bull; Niv. <?= $clayLvl ?>/<?= $clayMaxLvl ?></div>
                                    </div>
                                </div>
                                <span class="badge bg-orange-lt text-orange font-monospace">+<?= number_format($clayHourly) ?>/h</span>
                            </div>
                            <div class="small font-monospace d-flex flex-column gap-1 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Stock / Entrepôt :</span>
                                    <strong class="text-dark"><?= number_format((int)$clayStock) ?> / <?= number_format($clayMax) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Saturation :</span>
                                    <span class="<?= $pctClayStock >= 95 ? 'text-danger fw-bold' : 'text-secondary' ?>"><?= $formatEtaFull($clayStock, $clayMax, $clayHourly) ?></span>
                                </div>
                                <hr class="my-1">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Niveau de la Fosse :</span>
                                    <span class="text-dark fw-bold">Niv. <?= $clayLvl ?> / <?= $clayMaxLvl ?></span>
                                </div>
                                <?php if ($matsuriBonus > 0): ?>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Fête Matsuri :</span>
                                    <span class="text-purple fw-bold">+<?= $matsuriBonus ?>%</span>
                                </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Efficacité globale :</span>
                                    <span class="<?= $globalEffPct < 100 ? 'text-danger fw-bold' : 'text-success' ?>"><?= $globalEffPct ?>%</span>
                                </div>
                                <div class="d-flex justify-content-between pt-1 border-top">
                                    <span class="text-muted">Projection 24h :</span>
                                    <strong class="text-orange">+<?= number_format($clayHourly * 24) ?> / jour</strong>
                                </div>
                            </div>
                            <div class="d-flex gap-1 pt-1">
                                <a href="?page=resources" class="btn btn-xs btn-warning flex-fill"><i class="fa-solid fa-cubes-stacked me-1"></i>Fosse d'Argile</a>
                            </div>
                        </div>
                    </div>

                    <!-- 4. RIZ IMPÉRIAL -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle" type="button" id="pillResRice" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Riz Impérial (Koku) : cliquer pour le détail de production">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-wheat-awn text-warning"></i> Riz
                                </span>
                                <span class="font-monospace fw-bold text-warning" style="font-size:0.7rem;">+<?= number_format($prodDeut) ?>/h</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold text-dark" style="font-size:0.86rem;"
                                      id="res-val-deut"
                                      data-current="<?= (float)$planet['deuterium'] ?>"
                                      data-max="<?= $deutMax ?>"
                                      data-prod="<?= $prodDeut ?>">
                                    <?= number_format((int)$planet['deuterium']) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;"><?= (int)round($pctDeut) ?>%</span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar <?= $pctDeut >= 95 ? 'bg-danger' : 'bg-warning' ?>" id="bar-deut" style="width:<?= $pctDeut ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-lg p-3 border" aria-labelledby="pillResRice" style="min-width:290px; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-warning-lt text-warning rounded"><i class="fa-solid fa-wheat-awn"></i></span>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;">Riz Impérial (Koku)</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Rizières en Terrasses &bull; Niv. <?= $riziereLvl ?>/<?= $riziereMaxLvl ?></div>
                                    </div>
                                </div>
                                <span class="badge bg-warning-lt text-warning font-monospace">+<?= number_format($prodDeut) ?>/h</span>
                            </div>
                            <div class="small font-monospace d-flex flex-column gap-1 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Stock / Grenier Kura :</span>
                                    <strong class="text-dark"><?= number_format((int)$planet['deuterium']) ?> / <?= number_format($deutMax) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Saturation :</span>
                                    <span class="<?= $pctDeut >= 95 ? 'text-danger fw-bold' : 'text-secondary' ?>"><?= $formatEtaFull((float)$planet['deuterium'], $deutMax, $prodDeut) ?></span>
                                </div>
                                <hr class="my-1">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Meunerie &amp; Brasserie :</span>
                                    <span class="<?= $grainMillBonus > 0 ? 'text-warning fw-bold' : 'text-muted' ?>">+<?= $grainMillBonus ?>%</span>
                                </div>
                                <?php if ($oasisRice > 0): ?>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Oasis fertiles :</span>
                                    <span class="text-teal fw-bold">+<?= $oasisRice ?>%</span>
                                </div>
                                <?php endif; ?>
                                <?php if ($heroDeut > 0): ?>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Héros Samouraï :</span>
                                    <span class="text-warning fw-bold">+<?= number_format($heroDeut) ?>/h</span>
                                </div>
                                <?php endif; ?>
                                <?php if ($matsuriBonus > 0): ?>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Fête Matsuri :</span>
                                    <span class="text-purple fw-bold">+<?= $matsuriBonus ?>%</span>
                                </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Efficacité globale :</span>
                                    <span class="<?= $globalEffPct < 100 ? 'text-danger fw-bold' : 'text-success' ?>"><?= $globalEffPct ?>%</span>
                                </div>
                                <div class="d-flex justify-content-between pt-1 border-top">
                                    <span class="text-muted">Projection 24h :</span>
                                    <strong class="text-warning">+<?= number_format($prodDeut * 24) ?> / jour</strong>
                                </div>
                            </div>
                            <div class="d-flex gap-1 pt-1">
                                <a href="?page=resources" class="btn btn-xs btn-warning flex-fill"><i class="fa-solid fa-wheat-awn me-1"></i>Rizières</a>
                                <a href="?page=building&code=grain_mill" class="btn btn-xs btn-outline-secondary flex-fill"><i class="fa-solid fa-bowl-rice me-1"></i>Meunerie</a>
                            </div>
                        </div>
                    </div>

                    <!-- 5. FEUILLES DE THÉ -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle" type="button" id="pillResTea" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Feuilles de Thé : cliquer pour le détail de production">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-leaf text-teal"></i> Thé
                                </span>
                                <span class="font-monospace fw-bold text-teal" style="font-size:0.7rem;">+<?= number_format($teaHourly) ?>/h</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold text-dark" style="font-size:0.86rem;"
                                      id="res-val-tea"
                                      data-current="<?= $teaStock ?>"
                                      data-max="<?= $teaMax ?>"
                                      data-prod="<?= $teaHourly ?>">
                                    <?= number_format((int)$teaStock) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;"><?= (int)round($pctTeaStock) ?>%</span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar bg-teal" id="bar-tea" style="width:<?= $pctTeaStock ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-lg p-3 border" aria-labelledby="pillResTea" style="min-width:290px; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-teal-lt text-teal rounded"><i class="fa-solid fa-leaf"></i></span>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;">Feuilles de Thé</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Plantation de Thé &bull; Niv. <?= $teaLvl ?>/<?= $teaMaxLvl ?></div>
                                    </div>
                                </div>
                                <span class="badge bg-teal-lt text-teal font-monospace">+<?= number_format($teaHourly) ?>/h</span>
                            </div>
                            <div class="small font-monospace d-flex flex-column gap-1 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Stock / Entrepôt :</span>
                                    <strong class="text-dark"><?= number_format((int)$teaStock) ?> / <?= number_format($teaMax) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Saturation :</span>
                                    <span class="<?= $pctTeaStock >= 95 ? 'text-danger fw-bold' : 'text-secondary' ?>"><?= $formatEtaFull($teaStock, $teaMax, $teaHourly) ?></span>
                                </div>
                                <hr class="my-1">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Plantation rurale :</span>
                                    <span class="text-dark fw-bold">Niv. <?= $teaLvl ?> / <?= $teaMaxLvl ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Pavillon de Thé (Ferveur) :</span>
                                    <span class="<?= $teahouseBonus > 0 ? 'text-teal fw-bold' : 'text-muted' ?>">+<?= $teahouseBonus ?>%</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Efficacité globale :</span>
                                    <span class="<?= $globalEffPct < 100 ? 'text-danger fw-bold' : 'text-success' ?>"><?= $globalEffPct ?>%</span>
                                </div>
                                <div class="d-flex justify-content-between pt-1 border-top">
                                    <span class="text-muted">Projection 24h :</span>
                                    <strong class="text-teal">+<?= number_format($teaHourly * 24) ?> / jour</strong>
                                </div>
                            </div>
                            <div class="d-flex gap-1 pt-1">
                                <a href="?page=resources" class="btn btn-xs btn-teal flex-fill"><i class="fa-solid fa-leaf me-1"></i>Plantation</a>
                                <a href="?page=building&code=teahouse" class="btn btn-xs btn-outline-secondary flex-fill"><i class="fa-solid fa-mug-hot me-1"></i>Pavillon</a>
                            </div>
                        </div>
                    </div>

                    <!-- 6. GOUSSES DE SOJA -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle" type="button" id="pillResSoy" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Gousses de Soja : cliquer pour le détail de production">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-seedling text-lime"></i> Soja
                                </span>
                                <span class="font-monospace fw-bold text-lime" style="font-size:0.7rem;">+<?= number_format($soyHourly) ?>/h</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold text-dark" style="font-size:0.86rem;"
                                      id="res-val-soybean"
                                      data-current="<?= $soyStock ?>"
                                      data-max="<?= $soyMax ?>"
                                      data-prod="<?= $soyHourly ?>">
                                    <?= number_format((int)$soyStock) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;"><?= (int)round($pctSoyStock) ?>%</span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar bg-lime" id="bar-soybean" style="width:<?= $pctSoyStock ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-lg p-3 border" aria-labelledby="pillResSoy" style="min-width:290px; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-lime-lt text-lime rounded"><i class="fa-solid fa-seedling"></i></span>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;">Gousses de Soja</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Champ de Soja &bull; Niv. <?= $soyLvl ?>/<?= $soyMaxLvl ?></div>
                                    </div>
                                </div>
                                <span class="badge bg-lime-lt text-lime font-monospace">+<?= number_format($soyHourly) ?>/h</span>
                            </div>
                            <div class="small font-monospace d-flex flex-column gap-1 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Stock / Entrepôt :</span>
                                    <strong class="text-dark"><?= number_format((int)$soyStock) ?> / <?= number_format($soyMax) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Saturation :</span>
                                    <span class="<?= $pctSoyStock >= 95 ? 'text-danger fw-bold' : 'text-secondary' ?>"><?= $formatEtaFull($soyStock, $soyMax, $soyHourly) ?></span>
                                </div>
                                <hr class="my-1">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Champ de Soja :</span>
                                    <span class="text-dark fw-bold">Niv. <?= $soyLvl ?> / <?= $soyMaxLvl ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Efficacité globale :</span>
                                    <span class="<?= $globalEffPct < 100 ? 'text-danger fw-bold' : 'text-success' ?>"><?= $globalEffPct ?>%</span>
                                </div>
                                <div class="d-flex justify-content-between pt-1 border-top">
                                    <span class="text-muted">Projection 24h :</span>
                                    <strong class="text-lime">+<?= number_format($soyHourly * 24) ?> / jour</strong>
                                </div>
                            </div>
                            <div class="d-flex gap-1 pt-1">
                                <a href="?page=resources" class="btn btn-xs btn-success flex-fill"><i class="fa-solid fa-seedling me-1"></i>Champ de Soja</a>
                            </div>
                        </div>
                    </div>

                    <!-- 7. NIVEAU DE SÉRÉNITé SHINTŌ -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle <?= !$eOk ? 'border-danger bg-danger-lt' : '' ?>" type="button" id="pillResSerenity" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Sérénité Shintō : cliquer pour le bilan énergétique et spirituel">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-torii-gate <?= $eOk ? 'text-teal' : 'text-danger' ?>"></i> Sérénité
                                </span>
                                <span class="badge <?= $eOk ? 'bg-teal-lt text-teal' : 'bg-danger text-white' ?> font-monospace py-0 px-1" style="font-size:0.62rem;">
                                    <?= $eOk ? '100%' : '10%' ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold <?= $eOk ? 'text-teal' : 'text-danger' ?>" style="font-size:0.86rem;">
                                    <?= ($eBalance >= 0 ? '+' : '') . number_format($eBalance) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;">
                                    <?= number_format((int)($planet['energy_used'] ?? 0)) ?>/<?= number_format((int)($planet['energy_max'] ?? 0)) ?>
                                </span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar <?= $eOk ? 'bg-teal' : 'bg-danger' ?>" style="width:<?= $pctEnergy ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-lg p-3 border" aria-labelledby="pillResSerenity" style="min-width:295px; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs <?= $eOk ? 'bg-teal-lt text-teal' : 'bg-danger-lt text-danger' ?> rounded"><i class="fa-solid fa-torii-gate"></i></span>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;">Sérénité Shintō</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Sanctuaire Shintō &bull; Niv. <?= $shrineLvl ?>/<?= $shrineMaxLvl ?></div>
                                    </div>
                                </div>
                                <span class="badge <?= $eOk ? 'bg-teal-lt text-teal' : 'bg-danger text-white' ?> font-monospace">
                                    <?= ($eBalance >= 0 ? '+' : '') . number_format($eBalance) ?>
                                </span>
                            </div>
                            <div class="small font-monospace d-flex flex-column gap-1 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Ferveur générée :</span>
                                    <strong class="text-teal">+<?= number_format((int)($planet['energy_max'] ?? 0)) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Ferveur mobilisée :</span>
                                    <strong class="text-dark">-<?= number_format((int)($planet['energy_used'] ?? 0)) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Pavillon de Thé :</span>
                                    <span class="<?= $teahouseBonus > 0 ? 'text-teal fw-bold' : 'text-muted' ?>">+<?= $teahouseBonus ?>% ferveur</span>
                                </div>
                                <hr class="my-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Rendement des récoltes :</span>
                                    <?php if ($eOk): ?>
                                        <span class="badge bg-success-lt text-success">Harmonie (100%)</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-lt text-danger">Déficit (Récoltes à 10%)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex gap-1 pt-1">
                                <a href="?page=resources" class="btn btn-xs btn-teal flex-fill"><i class="fa-solid fa-torii-gate me-1"></i>Sanctuaire</a>
                                <a href="?page=building&code=teahouse" class="btn btn-xs btn-outline-secondary flex-fill"><i class="fa-solid fa-mug-hot me-1"></i>Pavillon</a>
                            </div>
                        </div>
                    </div>

                    <!-- 8. POUTRES DE CHARPENTE -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle" type="button" id="pillResBeams" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Poutres de Charpente : cliquer pour le détail">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-hammer text-orange"></i> Poutres
                                </span>
                                <span class="font-monospace text-orange fw-semibold" style="font-size:0.65rem;">Scierie</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold text-dark" style="font-size:0.86rem;"
                                      id="res-val-wooden-beams"
                                      data-current="<?= $beamsStock ?>"
                                      data-max="<?= $beamsMax ?>">
                                    <?= number_format((int)$beamsStock) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;"><?= (int)round($pctBeams) ?>%</span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar bg-orange" id="bar-wooden-beams" style="width:<?= $pctBeams ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-lg p-3 border" aria-labelledby="pillResBeams" style="min-width:280px; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-orange-lt text-orange rounded"><i class="fa-solid fa-hammer"></i></span>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;">Poutres de Charpente</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Matériau d'atelier urbain</div>
                                    </div>
                                </div>
                                <span class="badge bg-orange-lt text-orange font-monospace"><?= (int)round($pctBeams) ?>%</span>
                            </div>
                            <div class="small font-monospace d-flex flex-column gap-1 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Stock / Entrepôt :</span>
                                    <strong class="text-dark"><?= number_format((int)$beamsStock) ?> / <?= number_format($beamsMax) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Atelier :</span>
                                    <span class="text-dark fw-bold">Scierie &amp; Charpenterie</span>
                                </div>
                            </div>
                            <div class="d-flex gap-1 pt-1">
                                <a href="?page=building&code=sawmill" class="btn btn-xs btn-warning flex-fill"><i class="fa-solid fa-hammer me-1"></i>Ouvrir la Scierie</a>
                            </div>
                        </div>
                    </div>

                    <!-- 9. CHARBON DE BOIS -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle" type="button" id="pillResCharcoal" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Charbon de Bois : cliquer pour le détail">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-fire text-dark"></i> Charbon
                                </span>
                                <span class="font-monospace text-secondary fw-semibold" style="font-size:0.65rem;">Four</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold text-dark" style="font-size:0.86rem;"
                                      id="res-val-charcoal"
                                      data-current="<?= $charcoalStock ?>"
                                      data-max="<?= $charcoalMax ?>">
                                    <?= number_format((int)$charcoalStock) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;"><?= (int)round($pctCharcoal) ?>%</span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar bg-dark" id="bar-charcoal" style="width:<?= $pctCharcoal ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-lg p-3 border" aria-labelledby="pillResCharcoal" style="min-width:280px; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-secondary-lt text-dark rounded"><i class="fa-solid fa-fire"></i></span>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;">Charbon de Bois</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Combustible de haute chauffe</div>
                                    </div>
                                </div>
                                <span class="badge bg-secondary-lt text-dark font-monospace"><?= (int)round($pctCharcoal) ?>%</span>
                            </div>
                            <div class="small font-monospace d-flex flex-column gap-1 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Stock / Entrepôt :</span>
                                    <strong class="text-dark"><?= number_format((int)$charcoalStock) ?> / <?= number_format($charcoalMax) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Usage :</span>
                                    <span class="text-dark fw-bold">Forge Tamahagane &amp; Poudre</span>
                                </div>
                            </div>
                            <div class="d-flex gap-1 pt-1">
                                <a href="?page=building&code=sawmill" class="btn btn-xs btn-dark flex-fill"><i class="fa-solid fa-fire me-1"></i>Four à Charbon</a>
                            </div>
                        </div>
                    </div>

                    <!-- 10. SABLE DE FER (SATETSU) -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle" type="button" id="pillResIronSand" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Sable de Fer (Satetsu) : cliquer pour le détail">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-gem text-secondary"></i> Satetsu
                                </span>
                                <span class="font-monospace text-secondary fw-semibold" style="font-size:0.65rem;">Minerai</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold text-dark" style="font-size:0.86rem;"
                                      id="res-val-iron-sand"
                                      data-current="<?= $ironSandStock ?>"
                                      data-max="<?= $ironSandMax ?>">
                                    <?= number_format((int)$ironSandStock) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;"><?= (int)round($pctIronSand) ?>%</span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar bg-secondary" id="bar-iron-sand" style="width:<?= $pctIronSand ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-lg p-3 border" aria-labelledby="pillResIronSand" style="min-width:280px; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-secondary-lt text-secondary rounded"><i class="fa-solid fa-gem"></i></span>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;">Sable de Fer (Satetsu)</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Minerai ferreux de montagne</div>
                                    </div>
                                </div>
                                <span class="badge bg-secondary-lt text-secondary font-monospace"><?= (int)round($pctIronSand) ?>%</span>
                            </div>
                            <div class="small font-monospace d-flex flex-column gap-1 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Stock / Entrepôt :</span>
                                    <strong class="text-dark"><?= number_format((int)$ironSandStock) ?> / <?= number_format($ironSandMax) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Raffinage :</span>
                                    <span class="text-dark fw-bold">Acier Tamahagane</span>
                                </div>
                            </div>
                            <div class="d-flex gap-1 pt-1">
                                <a href="?page=building&code=blacksmith#craftSection" class="btn btn-xs btn-secondary flex-fill"><i class="fa-solid fa-fire-burner me-1"></i>Grande Forge</a>
                            </div>
                        </div>
                    </div>

                    <!-- 11. SOUFRE VOLCANIQUE -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle" type="button" id="pillResSulfur" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Soufre Volcanique : cliquer pour le détail">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-volcano text-warning"></i> Soufre
                                </span>
                                <span class="font-monospace text-warning fw-semibold" style="font-size:0.65rem;">Minerai</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold text-dark" style="font-size:0.86rem;"
                                      id="res-val-sulfur"
                                      data-current="<?= $sulfurStock ?>"
                                      data-max="<?= $sulfurMax ?>">
                                    <?= number_format((int)$sulfurStock) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;"><?= (int)round($pctSulfur) ?>%</span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar bg-warning" id="bar-sulfur" style="width:<?= $pctSulfur ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-lg p-3 border" aria-labelledby="pillResSulfur" style="min-width:280px; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-warning-lt text-warning rounded"><i class="fa-solid fa-volcano"></i></span>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;">Soufre Volcanique</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Minerai pyrotechnique</div>
                                    </div>
                                </div>
                                <span class="badge bg-warning-lt text-warning font-monospace"><?= (int)round($pctSulfur) ?>%</span>
                            </div>
                            <div class="small font-monospace d-flex flex-column gap-1 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Stock / Entrepôt :</span>
                                    <strong class="text-dark"><?= number_format((int)$sulfurStock) ?> / <?= number_format($sulfurMax) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Usage :</span>
                                    <span class="text-dark fw-bold">Poudre Noire &amp; Artillerie</span>
                                </div>
                            </div>
                            <div class="d-flex gap-1 pt-1">
                                <a href="?page=building&code=stonemason#craftSection" class="btn btn-xs btn-warning flex-fill"><i class="fa-solid fa-volcano me-1"></i>Raffiner</a>
                            </div>
                        </div>
                    </div>

                    <!-- 12. NITRATE DE POTASSE -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle" type="button" id="pillResNitrate" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Nitrate de Potasse (Salpêtre) : cliquer pour le détail">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-flask-vial text-info"></i> Nitrate
                                </span>
                                <span class="font-monospace text-info fw-semibold" style="font-size:0.65rem;">Salpêtre</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold text-dark" style="font-size:0.86rem;"
                                      id="res-val-nitrate"
                                      data-current="<?= $nitrateStock ?>"
                                      data-max="<?= $nitrateMax ?>">
                                    <?= number_format((int)$nitrateStock) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;"><?= (int)round($pctNitrate) ?>%</span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar bg-info" id="bar-nitrate" style="width:<?= $pctNitrate ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-lg p-3 border" aria-labelledby="pillResNitrate" style="min-width:280px; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-info-lt text-info rounded"><i class="fa-solid fa-flask-vial"></i></span>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;">Nitrate de Potasse</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Salpêtre raffiné</div>
                                    </div>
                                </div>
                                <span class="badge bg-info-lt text-info font-monospace"><?= (int)round($pctNitrate) ?>%</span>
                            </div>
                            <div class="small font-monospace d-flex flex-column gap-1 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Stock / Entrepôt :</span>
                                    <strong class="text-dark"><?= number_format((int)$nitrateStock) ?> / <?= number_format($nitrateMax) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Usage :</span>
                                    <span class="text-dark fw-bold">Arquebuses Teppō &amp; Munitions</span>
                                </div>
                            </div>
                            <div class="d-flex gap-1 pt-1">
                                <a href="?page=building&code=stonemason#craftSection" class="btn btn-xs btn-info flex-fill"><i class="fa-solid fa-flask-vial me-1"></i>Raffiner</a>
                            </div>
                        </div>
                    </div>

                    <!-- 13. POISSON FRAIS -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle" type="button" id="pillResFish" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Poisson Frais : cliquer pour le détail">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-fish text-cyan"></i> Poisson
                                </span>
                                <span class="font-monospace text-cyan fw-semibold" style="font-size:0.65rem;">Pêche</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold text-dark" style="font-size:0.86rem;"
                                      id="res-val-fish"
                                      data-current="<?= $fishStock ?>"
                                      data-max="<?= $fishMax ?>">
                                    <?= number_format((int)$fishStock) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;"><?= (int)round($pctFish) ?>%</span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar bg-cyan" id="bar-fish" style="width:<?= $pctFish ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-lg p-3 border" aria-labelledby="pillResFish" style="min-width:280px; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-cyan-lt text-cyan rounded"><i class="fa-solid fa-fish"></i></span>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;">Poisson Frais</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Vivres maritimes (Grenier Kura)</div>
                                    </div>
                                </div>
                                <span class="badge bg-cyan-lt text-cyan font-monospace"><?= (int)round($pctFish) ?>%</span>
                            </div>
                            <div class="small font-monospace d-flex flex-column gap-1 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Stock / Grenier Kura :</span>
                                    <strong class="text-dark"><?= number_format((int)$fishStock) ?> / <?= number_format($fishMax) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Provenance :</span>
                                    <span class="text-dark fw-bold">Flottilles de Pêche</span>
                                </div>
                            </div>
                            <div class="d-flex gap-1 pt-1">
                                <a href="?page=resources" class="btn btn-xs btn-cyan flex-fill"><i class="fa-solid fa-fish me-1"></i>Voir les Terroirs</a>
                            </div>
                        </div>
                    </div>

                    <!-- 14. FARINE DE RIZ -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle" type="button" id="pillResFlour" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Farine de Riz (Komeko) : cliquer pour le détail">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-bowl-rice text-secondary"></i> Farine
                                </span>
                                <span class="font-monospace text-secondary fw-semibold" style="font-size:0.65rem;">Niv. 5</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold text-dark" style="font-size:0.86rem;"
                                      id="res-val-rice-flour"
                                      data-current="<?= $flourStock ?>"
                                      data-max="<?= $flourMax ?>">
                                    <?= number_format((int)$flourStock) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;"><?= (int)round($pctFlour) ?>%</span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar bg-secondary" id="bar-rice-flour" style="width:<?= $pctFlour ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-lg p-3 border" aria-labelledby="pillResFlour" style="min-width:280px; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-secondary-lt text-secondary rounded"><i class="fa-solid fa-bowl-rice"></i></span>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;">Farine de Riz (Komeko)</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Meunerie &amp; Brasserie (Niv. 5)</div>
                                    </div>
                                </div>
                                <span class="badge bg-secondary-lt text-secondary font-monospace"><?= (int)round($pctFlour) ?>%</span>
                            </div>
                            <div class="small font-monospace d-flex flex-column gap-1 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Stock / Entrepôt :</span>
                                    <strong class="text-dark"><?= number_format((int)$flourStock) ?> / <?= number_format($flourMax) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Usage :</span>
                                    <span class="text-dark fw-bold">Rations &amp; Ravitaillement</span>
                                </div>
                            </div>
                            <div class="d-flex gap-1 pt-1">
                                <a href="?page=building&code=grain_mill" class="btn btn-xs btn-secondary flex-fill"><i class="fa-solid fa-bowl-rice me-1"></i>Meunerie</a>
                            </div>
                        </div>
                    </div>

                    <!-- 15. SAKÉ IMPÉRIAL -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle" type="button" id="pillResSake" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Saké Impérial : cliquer pour le détail">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-wine-bottle text-purple"></i> Saké
                                </span>
                                <span class="badge bg-purple-lt text-purple font-monospace py-0 px-1" style="font-size:0.58rem;">+15%</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold text-dark" style="font-size:0.86rem;"
                                      id="res-val-sake"
                                      data-current="<?= $sakeStock ?>"
                                      data-max="<?= $sakeMax ?>">
                                    <?= number_format((int)$sakeStock) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;"><?= (int)round($pctSake) ?>%</span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar bg-purple" id="bar-sake" style="width:<?= $pctSake ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start shadow-lg p-3 border" aria-labelledby="pillResSake" style="min-width:280px; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-purple-lt text-purple rounded"><i class="fa-solid fa-wine-bottle"></i></span>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;">Saké Impérial</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Meunerie &amp; Brasserie (Niv. 10)</div>
                                    </div>
                                </div>
                                <span class="badge bg-purple-lt text-purple font-monospace">+15% Moral</span>
                            </div>
                            <div class="small font-monospace d-flex flex-column gap-1 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Stock / Caves :</span>
                                    <strong class="text-dark"><?= number_format((int)$sakeStock) ?> / <?= number_format($sakeMax) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Effet :</span>
                                    <span class="text-purple fw-bold">Moral +15% &amp; Fêtes Matsuri</span>
                                </div>
                            </div>
                            <div class="d-flex gap-1 pt-1">
                                <a href="?page=building&code=grain_mill" class="btn btn-xs btn-purple flex-fill"><i class="fa-solid fa-wine-bottle me-1"></i>Brasserie</a>
                            </div>
                        </div>
                    </div>

                    <!-- 16. PEUPLE & DOMAINE (Même gabarit .hud-res-pill) -->
                    <div class="dropdown">
                        <button class="hud-res-pill dropdown-toggle" type="button" id="dropdownDomaine" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                                title="Peuple & Domaine : Population, Ordre Shinōkōshō, Main-d'œuvre, Moral et Kobans">
                            <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark" style="font-size:0.74rem;">
                                    <i class="fa-solid fa-users text-azure"></i> Peuple &amp; Domaine
                                </span>
                                <span class="badge bg-<?= $contentmentBadgeColor ?>-lt text-<?= $contentmentBadgeColor ?> font-monospace py-0 px-1" style="font-size:0.6rem;"><?= $contentmentScore ?>%</span>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between gap-1 mt-0.5 font-monospace">
                                <span class="fw-bold text-dark" style="font-size:0.84rem;">
                                    <?= number_format($curPop) ?><span class="text-muted" style="font-size:0.65rem;">/<?= number_format($maxPop) ?></span>
                                </span>
                                <span class="text-warning fw-bold" style="font-size:0.65rem;"><?= number_format($userGoldCoins) ?> 🪙</span>
                            </div>
                            <div class="progress progress-xs mt-1 w-100" style="height:3px;">
                                <div class="progress-bar bg-primary" style="width:<?= $pctPop ?>%;"></div>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-lg p-2 border" aria-labelledby="dropdownDomaine" style="min-width:335px; max-width:95vw; z-index:1060;">
                            <div class="d-flex justify-content-between align-items-center px-2 py-1 mb-1 border-bottom">
                                <div class="fw-bold text-dark d-flex align-items-center gap-2" style="font-size:0.82rem;">
                                    <i class="fa-solid fa-landmark text-azure"></i> Domaine &amp; Démographie
                                </div>
                                <a href="?page=city" class="btn btn-xs btn-outline-info py-0 px-2" style="font-size:0.68rem;">Cité &rarr;</a>
                            </div>

                            <!-- 1. Population & Logements avec sous-menu de répartition par castes (Shinōkōshō) -->
                            <div class="rounded border p-1 mb-1 bg-light-subtle">
                                <div class="d-flex align-items-center justify-content-between p-1 rounded text-reset">
                                    <a href="?page=city" class="d-flex align-items-center gap-2 text-decoration-none text-reset flex-grow-1"
                                       title="Démographie : <?= number_format($curPop) ?> / <?= number_format($maxPop) ?> habitants">
                                        <i class="fa-solid fa-users text-blue fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                        <div class="lh-1">
                                            <div class="fw-bold text-dark d-flex align-items-center gap-1" style="font-size:0.82rem;">
                                                <span>Population (Minka)</span>
                                                <span class="badge bg-indigo-lt text-indigo" style="font-size:0.6rem; padding:1px 4px;">Niv. <?= $civLevel ?></span>
                                            </div>
                                            <div class="text-muted font-monospace" style="font-size:0.68rem;">Capacité : <?= number_format($maxPop) ?></div>
                                        </div>
                                    </a>
                                    <div class="text-end lh-1 me-2">
                                        <div class="font-monospace fw-bold text-dark" style="font-size:0.84rem;">
                                            <?= number_format($curPop) ?> <span class="text-muted" style="font-size:0.68rem;">/ <?= number_format($maxPop) ?></span>
                                        </div>
                                        <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:64px;">
                                            <div class="progress-bar bg-primary" style="width:<?= $pctPop ?>%;"></div>
                                        </div>
                                    </div>
                                    <button class="btn btn-xs btn-outline-secondary py-0 px-2 d-flex align-items-center gap-1 flex-shrink-0"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#collapseCastesBreakdown"
                                            aria-expanded="true"
                                            aria-controls="collapseCastesBreakdown"
                                            title="Développer / Réduire la répartition des castes">
                                        <span style="font-size:0.65rem;">Castes</span>
                                        <i class="fa-solid fa-chevron-down" style="font-size:0.6rem;"></i>
                                    </button>
                                </div>

                                <!-- Sous-menu : Répartition des villageois par castes féodales (Shinōkōshō) -->
                                <div class="collapse show mt-1 pt-1 border-top" id="collapseCastesBreakdown">
                                    <div class="px-2 py-1 d-flex justify-content-between align-items-center bg-white rounded border-bottom mb-1">
                                        <span class="text-muted fw-bold font-monospace text-uppercase" style="font-size:0.65rem;">
                                            <i class="fa-solid fa-layer-group text-primary me-1"></i>Ordre Féodal (Shinōkōshō)
                                        </span>
                                        <div class="d-flex align-items-center gap-1">
                                            <button type="button" class="btn btn-xs btn-outline-warning py-0 px-1 d-inline-flex align-items-center gap-1"
                                                    onclick="checkWisdomAdvice(true)"
                                                    title="Consulter les conseils du Maître de la Sagesse pour faire évoluer votre société"
                                                    style="font-size: 0.62rem;">
                                                <i class="fa-solid fa-scroll text-warning"></i>
                                                <span>Sagesse</span>
                                            </button>
                                            <span class="badge bg-primary text-white font-monospace" style="font-size:0.62rem;">
                                                <?= htmlspecialchars($civName) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column gap-1 px-1">
                                        <?php foreach ($castesList as $t => $cData):
                                            $cMeta = $cData['meta'] ?? [];
                                            $cCount = (int)($cData['count'] ?? 0);
                                            $cPct = (float)($cData['pct'] ?? 0.0);
                                            $isUnlocked = ($civLevel >= $t);
                                        ?>
                                            <div class="d-flex align-items-center justify-content-between px-2 py-1 rounded <?= $isUnlocked ? 'bg-white' : 'bg-light text-muted' ?>" style="font-size:0.75rem; border-left: 3px solid <?= htmlspecialchars($cMeta['color'] ?? '#94a3b8') ?>;">
                                                <div class="d-flex align-items-center gap-2 overflow-hidden me-1">
                                                    <i class="fa-solid <?= htmlspecialchars($cMeta['icon'] ?? 'fa-circle') ?> flex-shrink-0" style="color:<?= htmlspecialchars($cMeta['color'] ?? '#64748b') ?>; width:14px; text-align:center;"></i>
                                                    <span class="text-truncate fw-semibold" title="<?= htmlspecialchars($cMeta['name'] ?? '') ?>">
                                                        <span class="font-monospace text-muted me-1" style="font-size:0.68rem;"><?= htmlspecialchars($cMeta['kanji'] ?? '') ?></span>
                                                        <?= htmlspecialchars($cMeta['short_name'] ?? $cMeta['name'] ?? '') ?>
                                                    </span>
                                                </div>
                                                <div class="text-end font-monospace flex-shrink-0" style="min-width:70px;">
                                                    <span class="fw-bold <?= $isUnlocked ? 'text-dark' : 'text-muted' ?>">
                                                        <?= number_format($cCount) ?>
                                                    </span>
                                                    <span class="text-muted" style="font-size:0.65rem;">(<?= number_format($cPct, 1) ?>%)</span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Main-d'œuvre Féodale -->
                            <a href="?page=city" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                               title="Main-d'œuvre : <?= number_format($assignedWorkers) ?> / <?= number_format($requiredWorkers) ?> ouvriers requis">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-person-digging <?= $isUnderstaffed ? 'text-warning' : 'text-success' ?> fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.82rem;">Main-d'œuvre</div>
                                        <div class="font-monospace <?= $isUnderstaffed ? 'text-warning' : 'text-success' ?>" style="font-size:0.68rem;">
                                            <?= $isUnderstaffed ? 'Sous-effectif (-' . $understaffedMalus . '%)' : 'Effectif complet' ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end lh-1">
                                    <div class="font-monospace fw-bold <?= $isUnderstaffed ? 'text-warning' : 'text-dark' ?>" style="font-size:0.84rem;">
                                        <?= number_format($assignedWorkers) ?> <span class="text-muted" style="font-size:0.68rem;">/ <?= number_format($requiredWorkers) ?></span>
                                    </div>
                                    <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                        <div class="progress-bar <?= $isUnderstaffed ? 'bg-warning' : 'bg-success' ?>" style="width:<?= $pctWorkforce ?>%;"></div>
                                    </div>
                                </div>
                            </a>

                            <!-- 3. Moral des Villageois -->
                            <a href="?page=city" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                               title="Contentement : <?= $contentmentScore ?>% (<?= $contentmentStatus ?>)">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid <?= $contentmentIcon ?> text-<?= $contentmentBadgeColor ?> fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.82rem;">Moral des Villageois</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;"><?= $contentmentStatus ?></div>
                                    </div>
                                </div>
                                <div class="text-end lh-1">
                                    <div class="font-monospace fw-bold text-<?= $contentmentBadgeColor ?>" style="font-size:0.84rem;">
                                        <span id="res-val-contentment"><?= $contentmentScore ?>%</span>
                                    </div>
                                    <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                        <div class="progress-bar bg-<?= $contentmentBadgeColor ?>" id="bar-contentment" style="width:<?= $contentmentScore ?>%;"></div>
                                    </div>
                                </div>
                            </a>

                            <!-- 4. Trésor Impérial -->
                            <a href="?page=privilege" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                               title="Trésor Impérial : <?= number_format($userGoldCoins) ?> Kobans d'or">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-coins text-warning fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                    <div class="lh-1">
                                        <div class="fw-bold text-dark" style="font-size:0.82rem;">Trésor Impérial</div>
                                        <div class="text-muted font-monospace" style="font-size:0.68rem;">Trésor du clan</div>
                                    </div>
                                </div>
                                <div class="text-end lh-1">
                                    <div class="font-monospace fw-bold text-dark" style="font-size:0.84rem;">
                                        <?= number_format($userGoldCoins) ?> <span class="text-warning small" style="font-size:0.68rem;">Kobans</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    </div><!-- /#hudResTrack -->

                    <!-- Flèche de glissement Droite (alignée sur le bord droit des cards du corps de page) -->
                    <button type="button" class="hud-slider-arrow" id="hudScrollRight" title="Faire glisser les ressources vers la droite" aria-label="Suivant">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>

                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const track = document.getElementById('hudResTrack');
            const btnLeft = document.getElementById('hudScrollLeft');
            const btnRight = document.getElementById('hudScrollRight');
            if (!track || !btnLeft || !btnRight) return;

            const updateArrows = () => {
                const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
                btnLeft.disabled = track.scrollLeft <= 2;
                btnRight.disabled = maxScroll <= 2 || track.scrollLeft >= (maxScroll - 2);
            };

            const closeOpenHudDropdowns = () => {
                track.querySelectorAll('.hud-res-pill[aria-expanded="true"]').forEach((btn) => {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                        const inst = bootstrap.Dropdown.getInstance(btn);
                        if (inst) inst.hide();
                    }
                });
            };

            btnLeft.addEventListener('click', () => {
                closeOpenHudDropdowns();
                track.scrollBy({ left: -380, behavior: 'smooth' });
            });

            btnRight.addEventListener('click', () => {
                closeOpenHudDropdowns();
                track.scrollBy({ left: 380, behavior: 'smooth' });
            });

            track.addEventListener('scroll', updateArrows, { passive: true });
            window.addEventListener('resize', updateArrows);

            // Positionnement fixed des menus déroulants du ruban pour éviter toute coupure par overflow-x
            track.querySelectorAll('.dropdown').forEach((dd) => {
                dd.addEventListener('shown.bs.dropdown', function () {
                    const btn = dd.querySelector('.hud-res-pill');
                    const menu = dd.querySelector('.dropdown-menu');
                    if (!btn || !menu) return;
                    const rect = btn.getBoundingClientRect();
                    menu.style.position = 'fixed';
                    menu.style.inset = 'auto';
                    menu.style.transform = 'none';
                    menu.style.top = (rect.bottom + 6) + 'px';
                    const menuWidth = menu.offsetWidth || 300;
                    const maxLeft = Math.max(8, window.innerWidth - menuWidth - 12);
                    menu.style.left = Math.max(8, Math.min(rect.left, maxLeft)) + 'px';
                    menu.style.zIndex = '1090';
                });
            });

            setTimeout(updateArrows, 60);
        });
        </script>
        <?php endif; ?>



        <!-- Bannière d'alerte Tour de Guet -->
        <?php if (!empty($activeMissions)): ?>
        <?php
            $hasHostile = count($incomingHostile) > 0;
            $hasSpy     = count($incomingSpy) > 0;
            $alertClass = $hasHostile ? 'alert-threat' : ($hasSpy ? 'alert-spy' : 'alert-info');
            $closest    = !empty($incomingHostile) ? $incomingHostile[0] : (!empty($incomingSpy) ? $incomingSpy[0] : $outgoingMissions[0]);
            $closestTime = ($closest['status'] === 'en_route') ? $closest['arrival_time'] : $closest['return_time'];
        ?>
        <div class="container-fluid d-print-none px-3 px-lg-4">
            <div class="travian-alert-banner <?= $alertClass ?>"
                 onclick="openWatchtowerModal()"
                 title="Cliquer pour afficher le registre de la Tour de Guet (<?= count($activeMissions) ?> mouvements)">
                <div class="alert-banner-left">
                    <?php if ($hasHostile): ?>
                        <span class="alert-status-badge threat"><i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> TOUR DE GUET</span>
                        <span class="alert-headline"><strong><?= count($incomingHostile) ?> incursion(s) armée(s)</strong> en approche !</span>
                        <span class="alert-countdown-chip">Impact dans <strong data-countdown="<?= $closestTime ?>">Calcul...</strong></span>
                    <?php elseif ($hasSpy): ?>
                        <span class="alert-status-badge spy"><i class="fa-solid fa-user-ninja text-warning me-1"></i> TOUR DE GUET</span>
                        <span class="alert-headline"><strong>Infiltration Shinobi détectée</strong> vers votre domaine !</span>
                        <span class="alert-countdown-chip">Arrivée dans <strong data-countdown="<?= $closestTime ?>">Calcul...</strong></span>
                    <?php else: ?>
                        <span class="alert-status-badge info"><i class="fa-solid fa-horse text-info me-1"></i> EXPÉDITIONS</span>
                        <span class="alert-headline"><strong><?= count($outgoingMissions) ?> troupe(s)</strong> en marche sur les provinces.</span>
                        <span class="alert-countdown-chip">Retour dans <strong data-countdown="<?= $closestTime ?>">Calcul...</strong></span>
                    <?php endif; ?>
                </div>
                <div class="alert-banner-right">
                    <span class="alert-cta-btn">
                        <span><i class="fa-solid fa-scroll me-1"></i> Détails (<?= count($activeMissions) ?>)</span>
                        <span class="alert-cta-arrow">&rarr;</span>
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- CORPS DE PAGE (Pleine largeur alignée sur la barre de ressources) -->
        <div class="page-body">
            <div class="container-fluid px-3 px-lg-4">


