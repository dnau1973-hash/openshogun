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
        ?>

        <style>
        .resource-dropdown-item {
            transition: background-color 0.15s ease;
            border-radius: 6px;
        }
        .resource-dropdown-item:hover {
            background-color: rgba(0, 0, 0, 0.04);
        }
        </style>

        <div class="border-bottom bg-light-subtle py-1 px-2 px-lg-3 shadow-sm d-print-none position-relative" style="z-index: 1050;">
            <div class="container-fluid px-3 px-lg-4 d-flex align-items-center justify-content-start gap-2 gap-md-3 flex-wrap flex-sm-nowrap py-0.5">
                
                <!-- 1. BOUTON & MENU DÉROULANT : CONSTRUCTION -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary bg-white d-flex align-items-center gap-2 px-3 py-1 rounded-2 shadow-sm dropdown-toggle text-dark border"
                            type="button" id="dropdownConstruction" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                            title="Cliquer pour afficher les matériaux de construction">
                        <i class="fa-solid fa-hammer text-primary fs-3"></i>
                        <div class="text-start lh-1">
                            <span class="fw-bold" style="font-size:0.84rem;">Construction</span>
                            <div class="text-muted d-none d-md-block font-monospace" style="font-size:0.65rem;">Bois &bull; Pierre &bull; Argile &bull; Poutres &bull; Charbon</div>
                        </div>
                        <span class="badge bg-secondary-lt text-secondary font-monospace ms-1" style="font-size:0.65rem;">5</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-start shadow-lg p-2 border" aria-labelledby="dropdownConstruction" style="min-width:320px; max-width:95vw; z-index:1060;">
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 mb-1 border-bottom">
                            <div class="fw-bold text-dark d-flex align-items-center gap-2" style="font-size:0.82rem;">
                                <i class="fa-solid fa-hammer text-primary"></i> Matériaux de Construction
                            </div>
                            <a href="?page=resources" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:0.68rem;">Gérer &rarr;</a>
                        </div>

                        <!-- 1. Bois de Cèdre -->
                        <a href="?page=resources" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                           title="Bois de Cèdre : Stock <?= number_format((int)$planet['metal']) ?> / <?= number_format($metalMax) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-tree text-success fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                <div class="lh-1">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;">Bois de Cèdre</div>
                                    <div class="text-muted font-monospace" style="font-size:0.68rem;">Plafond : <?= number_format($metalMax) ?></div>
                                </div>
                            </div>
                            <div class="text-end lh-1">
                                <div class="d-flex align-items-baseline gap-1 justify-content-end font-monospace">
                                    <span class="fw-bold text-dark" style="font-size:0.84rem;"
                                          id="res-val-metal"
                                          data-current="<?= (float)$planet['metal'] ?>"
                                          data-max="<?= $metalMax ?>"
                                          data-prod="<?= $prodMetal ?>">
                                        <?= number_format((int)$planet['metal']) ?>
                                    </span>
                                    <span class="text-success" style="font-size:0.68rem;">+<?= number_format($prodMetal) ?>/h</span>
                                </div>
                                <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                    <div class="progress-bar bg-success" id="bar-metal" style="width:<?= $pctMetal ?>%;"></div>
                                </div>
                            </div>
                        </a>

                        <!-- 2. Pierre de Granit -->
                        <a href="?page=resources" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                           title="Pierre de Granit : Stock <?= number_format((int)$planet['crystal']) ?> / <?= number_format($crystalMax) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-mountain text-primary fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                <div class="lh-1">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;">Pierre de Granit</div>
                                    <div class="text-muted font-monospace" style="font-size:0.68rem;">Plafond : <?= number_format($crystalMax) ?></div>
                                </div>
                            </div>
                            <div class="text-end lh-1">
                                <div class="d-flex align-items-baseline gap-1 justify-content-end font-monospace">
                                    <span class="fw-bold text-dark" style="font-size:0.84rem;"
                                          id="res-val-crystal"
                                          data-current="<?= (float)$planet['crystal'] ?>"
                                          data-max="<?= $crystalMax ?>"
                                          data-prod="<?= $prodCrystal ?>">
                                        <?= number_format((int)$planet['crystal']) ?>
                                    </span>
                                    <span class="text-primary" style="font-size:0.68rem;">+<?= number_format($prodCrystal) ?>/h</span>
                                </div>
                                <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                    <div class="progress-bar bg-primary" id="bar-crystal" style="width:<?= $pctCrystal ?>%;"></div>
                                </div>
                            </div>
                        </a>

                        <!-- 3. Argile Alluviale -->
                        <a href="?page=resources" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                           title="Argile Alluviale : Stock <?= number_format((int)$clayStock) ?> / <?= number_format($clayMax) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-cubes-stacked text-orange fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                <div class="lh-1">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;">Argile Alluviale</div>
                                    <div class="text-muted font-monospace" style="font-size:0.68rem;">Plafond : <?= number_format($clayMax) ?></div>
                                </div>
                            </div>
                            <div class="text-end lh-1">
                                <div class="d-flex align-items-baseline gap-1 justify-content-end font-monospace">
                                    <span class="fw-bold text-dark" style="font-size:0.84rem;"
                                          id="res-val-clay"
                                          data-current="<?= $clayStock ?>"
                                          data-max="<?= $clayMax ?>"
                                          data-prod="<?= $clayHourly ?>">
                                        <?= number_format((int)$clayStock) ?>
                                    </span>
                                    <span class="text-orange" style="font-size:0.68rem;">+<?= number_format($clayHourly) ?>/h</span>
                                </div>
                                <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                    <div class="progress-bar bg-orange" id="bar-clay" style="width:<?= $pctClayStock ?>%;"></div>
                                </div>
                            </div>
                        </a>

                        <!-- 4. Poutres de Charpente -->
                        <a href="?page=city" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                           title="Poutres de Charpente : Stock <?= number_format((int)$beamsStock) ?> / <?= number_format($beamsMax) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-hammer text-orange fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                <div class="lh-1">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;">Poutres de Charpente</div>
                                    <div class="text-muted font-monospace" style="font-size:0.68rem;">Plafond : <?= number_format($beamsMax) ?></div>
                                </div>
                            </div>
                            <div class="text-end lh-1">
                                <div class="d-flex align-items-baseline gap-1 justify-content-end font-monospace">
                                    <span class="fw-bold text-dark" style="font-size:0.84rem;"
                                          id="res-val-wooden-beams"
                                          data-current="<?= $beamsStock ?>"
                                          data-max="<?= $beamsMax ?>">
                                        <?= number_format((int)$beamsStock) ?>
                                    </span>
                                    <span class="text-muted" style="font-size:0.68rem;">Urbain</span>
                                </div>
                                <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                    <div class="progress-bar bg-orange" id="bar-wooden-beams" style="width:<?= $pctBeams ?>%;"></div>
                                </div>
                            </div>
                        </a>

                        <!-- 5. Charbon de Bois -->
                        <a href="?page=building&type=sawmill" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                           title="Charbon de Bois : Stock <?= number_format((int)$charcoalStock) ?> / <?= number_format($charcoalMax) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-fire text-dark fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                <div class="lh-1">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;">Charbon de Bois</div>
                                    <div class="text-muted font-monospace" style="font-size:0.68rem;">Plafond : <?= number_format($charcoalMax) ?></div>
                                </div>
                            </div>
                            <div class="text-end lh-1">
                                <div class="d-flex align-items-baseline gap-1 justify-content-end font-monospace">
                                    <span class="fw-bold text-dark" style="font-size:0.84rem;"
                                          id="res-val-charcoal"
                                          data-current="<?= $charcoalStock ?>"
                                          data-max="<?= $charcoalMax ?>">
                                        <?= number_format((int)$charcoalStock) ?>
                                    </span>
                                    <span class="text-muted" style="font-size:0.68rem;">Scierie</span>
                                </div>
                                <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                    <div class="progress-bar bg-dark" id="bar-charcoal" style="width:<?= $pctCharcoal ?>%;"></div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- 2. BOUTON & MENU DÉROULANT : MINERAIS -->
                <div class="dropdown">
                    <button class="btn btn-outline-danger bg-white d-flex align-items-center gap-2 px-3 py-1 rounded-2 shadow-sm dropdown-toggle text-dark border"
                            type="button" id="dropdownMinerais" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                            title="Cliquer pour afficher les minerais et ressources de montagne">
                        <i class="fa-solid fa-gem text-danger fs-3"></i>
                        <div class="text-start lh-1">
                            <span class="fw-bold" style="font-size:0.84rem;">Minerais</span>
                            <div class="text-muted d-none d-md-block font-monospace" style="font-size:0.65rem;">Sable &bull; Soufre &bull; Nitrate</div>
                        </div>
                        <span class="badge bg-danger-lt text-danger font-monospace ms-1" style="font-size:0.65rem;">3</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-start shadow-lg p-2 border" aria-labelledby="dropdownMinerais" style="min-width:320px; max-width:95vw; z-index:1060;">
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 mb-1 border-bottom">
                            <div class="fw-bold text-dark d-flex align-items-center gap-2" style="font-size:0.82rem;">
                                <i class="fa-solid fa-gem text-danger"></i> Minerais &amp; Montagnes
                            </div>
                            <a href="?page=building&code=stonemason#craftSection" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size:0.68rem;">Raffiner &rarr;</a>
                        </div>

                        <!-- 1. Sable de Fer (Satetsu) -->
                        <a href="?page=building&code=blacksmith#craftSection" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                           title="Sable de Fer (Satetsu) : Stock <?= number_format((int)$ironSandStock) ?> / <?= number_format($ironSandMax) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-gem text-secondary fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                <div class="lh-1">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;">Sable de Fer (Satetsu)</div>
                                    <div class="text-muted font-monospace" style="font-size:0.68rem;">Plafond : <?= number_format($ironSandMax) ?></div>
                                </div>
                            </div>
                            <div class="text-end lh-1">
                                <div class="d-flex align-items-baseline gap-1 justify-content-end font-monospace">
                                    <span class="fw-bold text-dark" style="font-size:0.84rem;"
                                          id="res-val-iron-sand"
                                          data-current="<?= $ironSandStock ?>"
                                          data-max="<?= $ironSandMax ?>">
                                        <?= number_format((int)$ironSandStock) ?>
                                    </span>
                                    <span class="text-muted" style="font-size:0.68rem;">Forge</span>
                                </div>
                                <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                    <div class="progress-bar bg-secondary" id="bar-iron-sand" style="width:<?= $pctIronSand ?>%;"></div>
                                </div>
                            </div>
                        </a>

                        <!-- 2. Soufre Volcanique -->
                        <a href="?page=building&code=stonemason#craftSection" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                           title="Soufre Volcanique : Stock <?= number_format((int)$sulfurStock) ?> / <?= number_format($sulfurMax) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-volcano text-warning fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                <div class="lh-1">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;">Soufre Volcanique</div>
                                    <div class="text-muted font-monospace" style="font-size:0.68rem;">Plafond : <?= number_format($sulfurMax) ?></div>
                                </div>
                            </div>
                            <div class="text-end lh-1">
                                <div class="d-flex align-items-baseline gap-1 justify-content-end font-monospace">
                                    <span class="fw-bold text-dark" style="font-size:0.84rem;"
                                          id="res-val-sulfur"
                                          data-current="<?= $sulfurStock ?>"
                                          data-max="<?= $sulfurMax ?>">
                                        <?= number_format((int)$sulfurStock) ?>
                                    </span>
                                    <span class="text-muted" style="font-size:0.68rem;">Poudre</span>
                                </div>
                                <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                    <div class="progress-bar bg-warning" id="bar-sulfur" style="width:<?= $pctSulfur ?>%;"></div>
                                </div>
                            </div>
                        </a>

                        <!-- 3. Nitrate de Potasse -->
                        <a href="?page=building&code=stonemason#craftSection" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                           title="Nitrate de Potasse (Salpêtre) : Stock <?= number_format((int)$nitrateStock) ?> / <?= number_format($nitrateMax) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-flask-vial text-info fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                <div class="lh-1">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;">Nitrate de Potasse</div>
                                    <div class="text-muted font-monospace" style="font-size:0.68rem;">Plafond : <?= number_format($nitrateMax) ?></div>
                                </div>
                            </div>
                            <div class="text-end lh-1">
                                <div class="d-flex align-items-baseline gap-1 justify-content-end font-monospace">
                                    <span class="fw-bold text-dark" style="font-size:0.84rem;"
                                          id="res-val-nitrate"
                                          data-current="<?= $nitrateStock ?>"
                                          data-max="<?= $nitrateMax ?>">
                                        <?= number_format((int)$nitrateStock) ?>
                                    </span>
                                    <span class="text-muted" style="font-size:0.68rem;">Salpêtre</span>
                                </div>
                                <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                    <div class="progress-bar bg-info" id="bar-nitrate" style="width:<?= $pctNitrate ?>%;"></div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- 3. BOUTON & MENU DÉROULANT : ALIMENTAIRE -->
                <div class="dropdown">
                    <button class="btn btn-outline-warning bg-white d-flex align-items-center gap-2 px-3 py-1 rounded-2 shadow-sm dropdown-toggle text-dark border"
                            type="button" id="dropdownAlimentaire" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                            title="Cliquer pour afficher les vivres et denrées alimentaires">
                        <i class="fa-solid fa-bowl-rice text-warning fs-3"></i>
                        <div class="text-start lh-1">
                            <span class="fw-bold" style="font-size:0.84rem;">Alimentaire</span>
                            <div class="text-muted d-none d-md-block font-monospace" style="font-size:0.65rem;">Riz &bull; Poisson &bull; Thé &bull; Soja...</div>
                        </div>
                        <span class="badge bg-warning-lt text-warning font-monospace ms-1" style="font-size:0.65rem;">6</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-start shadow-lg p-2 border" aria-labelledby="dropdownAlimentaire" style="min-width:330px; max-width:95vw; z-index:1060;">
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 mb-1 border-bottom">
                            <div class="fw-bold text-dark d-flex align-items-center gap-2" style="font-size:0.82rem;">
                                <i class="fa-solid fa-bowl-rice text-warning"></i> Vivres &amp; Ressources Alimentaires
                            </div>
                            <a href="?page=resources" class="btn btn-xs btn-outline-warning py-0 px-2" style="font-size:0.68rem;">Terroirs &rarr;</a>
                        </div>

                        <!-- 1. Riz Impérial -->
                        <a href="?page=resources" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                           title="Riz Impérial : Stock <?= number_format((int)$planet['deuterium']) ?> / <?= number_format($deutMax) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-wheat-awn text-warning fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                <div class="lh-1">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;">Riz Impérial (Koku)</div>
                                    <div class="text-muted font-monospace" style="font-size:0.68rem;">Grenier Kura : <?= number_format($deutMax) ?></div>
                                </div>
                            </div>
                            <div class="text-end lh-1">
                                <div class="d-flex align-items-baseline gap-1 justify-content-end font-monospace">
                                    <span class="fw-bold text-dark" style="font-size:0.84rem;"
                                          id="res-val-deut"
                                          data-current="<?= (float)$planet['deuterium'] ?>"
                                          data-max="<?= $deutMax ?>"
                                          data-prod="<?= $prodDeut ?>">
                                        <?= number_format((int)$planet['deuterium']) ?>
                                    </span>
                                    <span class="text-warning" style="font-size:0.68rem;">+<?= number_format($prodDeut) ?>/h</span>
                                </div>
                                <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                    <div class="progress-bar bg-warning" id="bar-deut" style="width:<?= $pctDeut ?>%;"></div>
                                </div>
                            </div>
                        </a>

                        <!-- 2. Poisson Frais -->
                        <a href="?page=resources" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                           title="Poisson Frais : Stock <?= number_format((int)$fishStock) ?> / <?= number_format($fishMax) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-fish text-cyan fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                <div class="lh-1">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;">Poisson Frais</div>
                                    <div class="text-muted font-monospace" style="font-size:0.68rem;">Grenier Kura : <?= number_format($fishMax) ?></div>
                                </div>
                            </div>
                            <div class="text-end lh-1">
                                <div class="d-flex align-items-baseline gap-1 justify-content-end font-monospace">
                                    <span class="fw-bold text-dark" style="font-size:0.84rem;"
                                          id="res-val-fish"
                                          data-current="<?= $fishStock ?>"
                                          data-max="<?= $fishMax ?>">
                                        <?= number_format((int)$fishStock) ?>
                                    </span>
                                    <span class="text-cyan" style="font-size:0.68rem;">Pêche</span>
                                </div>
                                <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                    <div class="progress-bar bg-cyan" id="bar-fish" style="width:<?= $pctFish ?>%;"></div>
                                </div>
                            </div>
                        </a>

                        <!-- 3. Feuilles de Thé -->
                        <a href="?page=resources" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                           title="Feuilles de Thé : Stock <?= number_format((int)$teaStock) ?> / <?= number_format($teaMax) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-leaf text-teal fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                <div class="lh-1">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;">Feuilles de Thé</div>
                                    <div class="text-muted font-monospace" style="font-size:0.68rem;">Plafond : <?= number_format($teaMax) ?></div>
                                </div>
                            </div>
                            <div class="text-end lh-1">
                                <div class="d-flex align-items-baseline gap-1 justify-content-end font-monospace">
                                    <span class="fw-bold text-dark" style="font-size:0.84rem;"
                                          id="res-val-tea"
                                          data-current="<?= $teaStock ?>"
                                          data-max="<?= $teaMax ?>"
                                          data-prod="<?= $teaHourly ?>">
                                        <?= number_format((int)$teaStock) ?>
                                    </span>
                                    <span class="text-teal" style="font-size:0.68rem;">+<?= number_format($teaHourly) ?>/h</span>
                                </div>
                                <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                    <div class="progress-bar bg-teal" id="bar-tea" style="width:<?= $pctTeaStock ?>%;"></div>
                                </div>
                            </div>
                        </a>

                        <!-- 4. Gousses de Soja -->
                        <a href="?page=resources" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                           title="Gousses de Soja : Stock <?= number_format((int)$soyStock) ?> / <?= number_format($soyMax) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-seedling text-lime fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                <div class="lh-1">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;">Gousses de Soja</div>
                                    <div class="text-muted font-monospace" style="font-size:0.68rem;">Plafond : <?= number_format($soyMax) ?></div>
                                </div>
                            </div>
                            <div class="text-end lh-1">
                                <div class="d-flex align-items-baseline gap-1 justify-content-end font-monospace">
                                    <span class="fw-bold text-dark" style="font-size:0.84rem;"
                                          id="res-val-soybean"
                                          data-current="<?= $soyStock ?>"
                                          data-max="<?= $soyMax ?>"
                                          data-prod="<?= $soyHourly ?>">
                                        <?= number_format((int)$soyStock) ?>
                                    </span>
                                    <span class="text-lime" style="font-size:0.68rem;">+<?= number_format($soyHourly) ?>/h</span>
                                </div>
                                <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                    <div class="progress-bar bg-lime" id="bar-soybean" style="width:<?= $pctSoyStock ?>%;"></div>
                                </div>
                            </div>
                        </a>

                        <!-- 5. Farine de Riz -->
                        <a href="?page=city" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                           title="Farine de Riz : Stock <?= number_format((int)$flourStock) ?> / <?= number_format($flourMax) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-bowl-rice text-secondary fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                <div class="lh-1">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;">Farine de Riz (Komeko)</div>
                                    <div class="text-muted font-monospace" style="font-size:0.68rem;">Plafond : <?= number_format($flourMax) ?></div>
                                </div>
                            </div>
                            <div class="text-end lh-1">
                                <div class="d-flex align-items-baseline gap-1 justify-content-end font-monospace">
                                    <span class="fw-bold text-dark" style="font-size:0.84rem;"
                                          id="res-val-rice-flour"
                                          data-current="<?= $flourStock ?>"
                                          data-max="<?= $flourMax ?>">
                                        <?= number_format((int)$flourStock) ?>
                                    </span>
                                    <span class="text-muted" style="font-size:0.68rem;">Urbain</span>
                                </div>
                                <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                    <div class="progress-bar bg-secondary" id="bar-rice-flour" style="width:<?= $pctFlour ?>%;"></div>
                                </div>
                            </div>
                        </a>

                        <!-- 6. Saké Impérial -->
                        <a href="?page=city" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                           title="Saké Impérial : Stock <?= number_format((int)$sakeStock) ?> / <?= number_format($sakeMax) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-wine-bottle text-purple fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                <div class="lh-1">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;">Saké Impérial</div>
                                    <div class="text-muted font-monospace" style="font-size:0.68rem;">Plafond : <?= number_format($sakeMax) ?></div>
                                </div>
                            </div>
                            <div class="text-end lh-1">
                                <div class="d-flex align-items-baseline gap-1 justify-content-end font-monospace">
                                    <span class="fw-bold text-dark" style="font-size:0.84rem;"
                                          id="res-val-sake"
                                          data-current="<?= $sakeStock ?>"
                                          data-max="<?= $sakeMax ?>">
                                        <?= number_format((int)$sakeStock) ?>
                                    </span>
                                    <span class="badge bg-purple-lt text-purple font-monospace py-0 px-1" style="font-size:0.6rem;">+15%</span>
                                </div>
                                <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                    <div class="progress-bar bg-purple" id="bar-sake" style="width:<?= $pctSake ?>%;"></div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- 3. BOUTON & MENU DÉROULANT : DOMAINE -->
                <div class="dropdown">
                    <button class="btn btn-outline-info bg-white d-flex align-items-center gap-2 px-3 py-1 rounded-2 shadow-sm dropdown-toggle text-dark border"
                            type="button" id="dropdownDomaine" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                            title="Cliquer pour afficher la vitalité du domaine et la démographie">
                        <i class="fa-solid fa-landmark text-azure fs-3"></i>
                        <div class="text-start lh-1">
                            <span class="fw-bold" style="font-size:0.84rem;">Domaine</span>
                            <div class="text-muted d-none d-md-block font-monospace" style="font-size:0.65rem;">Sérénité &bull; Peuple &bull; Kobans</div>
                        </div>
                        <span class="badge bg-azure-lt text-azure font-monospace ms-1" style="font-size:0.65rem;">5</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg p-2 border" aria-labelledby="dropdownDomaine" style="min-width:330px; max-width:95vw; z-index:1060;">
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 mb-1 border-bottom">
                            <div class="fw-bold text-dark d-flex align-items-center gap-2" style="font-size:0.82rem;">
                                <i class="fa-solid fa-landmark text-azure"></i> Domaine &amp; Démographie
                            </div>
                            <a href="?page=city" class="btn btn-xs btn-outline-info py-0 px-2" style="font-size:0.68rem;">Cité &rarr;</a>
                        </div>

                        <!-- 1. Sérénité Shinto -->
                        <a href="?page=resources" class="dropdown-item resource-dropdown-item d-flex align-items-center justify-content-between p-2 rounded text-reset text-decoration-none"
                           title="Sérénité Shintō : Solde <?= ($eBalance >= 0 ? '+' : '') . number_format($eBalance) ?> ferveur">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-torii-gate <?= $eOk ? 'text-teal' : 'text-danger' ?> fs-2 flex-shrink-0" style="width:24px; text-align:center;"></i>
                                <div class="lh-1">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;">Sérénité Shintō</div>
                                    <div class="text-muted font-monospace" style="font-size:0.68rem;">Conso : <?= number_format((int)($planet['energy_used'] ?? 0)) ?> / <?= number_format((int)($planet['energy_max'] ?? 0)) ?></div>
                                </div>
                            </div>
                            <div class="text-end lh-1">
                                <div class="font-monospace fw-bold <?= $eOk ? 'text-teal' : 'text-danger' ?>" style="font-size:0.84rem;">
                                    <?= ($eBalance >= 0 ? '+' : '') . number_format($eBalance) ?>
                                </div>
                                <div class="progress progress-xs mt-1 ms-auto" style="height:3px; width:72px;">
                                    <div class="progress-bar <?= $eOk ? 'bg-teal' : 'bg-danger' ?>" style="width:<?= $pctEnergy ?>%;"></div>
                                </div>
                            </div>
                        </a>

                        <!-- 2. Population & Logements avec sous-menu de répartition par castes (Shinōkōshō) -->
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

                        <!-- 3. Main-d'œuvre Féodale -->
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

                        <!-- 4. Moral des Villageois -->
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

                        <!-- 5. Trésor Impérial -->
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

            </div>
        </div>
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


