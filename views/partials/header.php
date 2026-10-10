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
            'building_tenshu.jpg' => [
                'name' => 'Tenshu (Donjon Castral)',
                'badge' => 'Siège du Commandement',
                'desc' => 'Palais fortifié du Daimyō et réduction du temps de construction des édifices.',
                'icon' => 'fa-solid fa-chess-rook text-danger'
            ],
            'building_barracks.jpg' => [
                'name' => 'Dojo Militaire & Caserne',
                'badge' => 'Infanterie Féodale',
                'desc' => 'Enrôlement des Samouraïs d\'élite, archers Yumi et mousquetaires Tanegashima.',
                'icon' => 'fa-solid fa-user-ninja text-primary'
            ],
            'building_shipyard.jpg' => [
                'name' => 'Atelier de Siège & Écuries',
                'badge' => 'Cavalerie & Génie Militaire',
                'desc' => 'Élevage de coursiers de guerre, catapultes géantes et convois de ravitaillement.',
                'icon' => 'fa-solid fa-horse text-warning'
            ],
            'building_wall.jpg' => [
                'name' => 'Muraille & Remparts de Cité',
                'badge' => 'Enceinte Fortifiée',
                'desc' => 'Murailles en pierre cyclopéenne et douves protégeant le fief (+4% défense garnison/niv).',
                'icon' => 'fa-solid fa-shield-halved text-success'
            ],
            'building_storage.jpg' => [
                'name' => 'Hangar de Cèdre & Dépôt',
                'badge' => 'Entrepôt de Bois',
                'desc' => 'Réserves stratégiques de bois de cèdre et poutres de charpente.',
                'icon' => 'fa-solid fa-tree text-success'
            ],
            'building_tank.jpg' => [
                'name' => 'Grenier Kura & Réserve de Granit',
                'badge' => 'Réserves Vivrières & Roches',
                'desc' => 'Grands silos kura protégeant la pierre de taille et le riz impérial.',
                'icon' => 'fa-solid fa-mountain text-secondary'
            ],
            'building_research_lab.jpg' => [
                'name' => 'Académie des Savoirs & Forge',
                'badge' => 'Recherche Stratégique',
                'desc' => 'Perfectionnement du tamahagane, métallurgie et doctrines de guerre ancestrales.',
                'icon' => 'fa-solid fa-scroll text-info'
            ],
            'building_market.jpg' => [
                'name' => 'Marché Féodal & Caravanes',
                'badge' => 'Commerce Provincial',
                'desc' => 'Échanges de denrées et marchandises avec les marchands itinérants.',
                'icon' => 'fa-solid fa-coins text-warning'
            ],
            'building_radar.jpg' => [
                'name' => 'Tour de Guet Yagura',
                'badge' => 'Vigie & Feux d\'Alarme',
                'desc' => 'Surveillance provinciale et détection précoce des armées et espions ennemis.',
                'icon' => 'fa-solid fa-eye text-danger'
            ],
            'building_quantum_vault.jpg' => [
                'name' => 'Cachette Secrète Sous Terre',
                'badge' => 'Caveau Anti-Pillage',
                'desc' => 'Réserve inviolable dissimulée sous terre protégeant les stocks du pillage.',
                'icon' => 'fa-solid fa-vault text-purple'
            ],
            'building_embassy.jpg' => [
                'name' => 'Pavillon Diplomatique des Clans',
                'badge' => 'Traités d\'Alliance',
                'desc' => 'Pactes d\'alliance scellés sous les auspices des cérémonies du thé.',
                'icon' => 'fa-solid fa-handshake text-primary'
            ],
            'building_sawmill.jpg' => [
                'name' => 'Scierie Kizukuri',
                'badge' => 'Atelier (+5% Bois)',
                'desc' => 'Façonnage des poutres et débitage des troncs de cèdre.',
                'icon' => 'fa-solid fa-hammer text-warning'
            ],
            'building_stonemason.jpg' => [
                'name' => 'Tailleuse de Granit',
                'badge' => 'Atelier (+5% Pierre)',
                'desc' => 'Briqueterie et polissage des blocs de granit pour les forteresses.',
                'icon' => 'fa-solid fa-trowel-bricks text-secondary'
            ],
            'building_grain_mill.jpg' => [
                'name' => 'Meunerie de Riz Impérial',
                'badge' => 'Atelier (+5% Riz)',
                'desc' => 'Moulins à eau convertissant le riz koku en farine nourricière.',
                'icon' => 'fa-solid fa-wheat-awn text-warning'
            ],
            'building_blacksmith.jpg' => [
                'name' => 'Forge d\'Armes & d\'Armures',
                'badge' => 'Armurerie Ancestrale',
                'desc' => 'Fabrication des lames légendaires et des armures de samouraï.',
                'icon' => 'fa-solid fa-fire-burner text-danger'
            ],
            'building_teahouse.jpg' => [
                'name' => 'Pavillon de Thé Chashitsu',
                'badge' => 'Sérénité Démographique',
                'desc' => 'Cérémonie du thé favorisant le contentement et l\'attrait du fief.',
                'icon' => 'fa-solid fa-leaf text-teal'
            ],
            'building_tournament_square.jpg' => [
                'name' => 'Place d\'Armes & Tournoi',
                'badge' => 'Joutes Féodales',
                'desc' => 'Arène martiale et ralliement des troupes provinciales.',
                'icon' => 'fa-solid fa-flag text-danger'
            ]
        ];

        // Découverte dynamique de tous les fichiers présents dans assets/buildings
        $buildingsAssetsDir = __DIR__ . '/../../public/assets/buildings';
        $carouselSlides = [];
        if (is_dir($buildingsAssetsDir)) {
            $foundFiles = glob($buildingsAssetsDir . '/*.jpg');
            if ($foundFiles) {
                // Trier pour placer tenshu en premier
                usort($foundFiles, function($a, $b) {
                    $baseA = basename($a);
                    $baseB = basename($b);
                    if ($baseA === 'building_tenshu.jpg') return -1;
                    if ($baseB === 'building_tenshu.jpg') return 1;
                    return strcmp($baseA, $baseB);
                });

                foreach ($foundFiles as $filePath) {
                    $bName = basename($filePath);
                    $meta = $carouselBuildingsMeta[$bName] ?? [
                        'name' => ucwords(str_replace(['building_', '_', '.jpg'], ['', ' ', ''], $bName)),
                        'badge' => 'Édifice Castral',
                        'desc' => 'Bâtiment traditionnel du domaine féodal.',
                        'icon' => 'fa-solid fa-landmark text-warning'
                    ];
                    $carouselSlides[] = [
                        'url' => '/public/assets/buildings/' . $bName,
                        'name' => $meta['name'],
                        'badge' => $meta['badge'],
                        'desc' => $meta['desc'],
                        'icon' => $meta['icon']
                    ];
                }
            }
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
                                     style="object-fit: cover; object-position: center 30%; filter: brightness(0.92);">
                                
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


