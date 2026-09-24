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
$messageEngine = new MessageEngine();
$unreadMessagesCount = $messageEngine->getUnreadCount((int)$user['id']);

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

if ($planet) {
    $planetEngine = new PlanetEngine();
    $fleetEngine = new FleetEngine();
    
    // Mettre à jour les flottes et la planète
    $fleetEngine->processFleetMissions();
    $planet = $planetEngine->updatePlanet((int)$planet['id']);
    
    // ⚔️ Simulation autonome des PNJ / Bots (espionnage, raids, chantiers)
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
    <!-- Tabler UI Framework (local) -->
    <link rel="stylesheet" href="/public/css/tabler/tabler.min.css?v=1.0.0-beta21">
    <!-- HUD Travian Féodal (header circulaire, barres de ressources, alertes) -->
    <link rel="stylesheet" href="/public/css/style.css?v=<?= file_exists(__DIR__ . '/../../public/css/style.css') ? filemtime(__DIR__ . '/../../public/css/style.css') : time() ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏯</text></svg>">
</head>
<body class="antialiased">

<?php
$navItems = [
    ['page' => 'resources', 'match' => ['resources','field'], 'icon' => '🌾', 'label' => 'Terroir Féodal',     'title' => 'Terroir & Récoltes'],
    ['page' => 'city',      'match' => ['city','building'],   'icon' => '🏯', 'label' => 'Cité Castrale',      'title' => 'Bâtiments & Châteaux'],
    ['page' => 'empire',    'match' => ['empire'],            'icon' => '👑', 'label' => 'Empire',             'title' => 'Grand Tableau de Bord des Fiefs'],
    ['page' => 'map',       'match' => ['map','galaxy'],      'icon' => '🗾', 'label' => 'Carte',               'title' => 'Carte des Provinces'],
    ['page' => 'fleet',     'match' => ['fleet'],             'icon' => '⚔️', 'label' => 'Armées',             'title' => 'Expéditions militaires'],
    ['page' => 'hero',      'match' => ['hero'],              'icon' => '🥋', 'label' => 'Héros',              'title' => 'Votre Samouraï Héros'],
    ['page' => 'alliance',  'match' => ['alliance'],          'icon' => '🎌', 'label' => 'Alliance',           'title' => 'Pacte Féodal & Ambassade'],
];
?>

<!-- ===== LAYOUT TABLER PLEINE LARGEUR (pas de sidebar) ===== -->
<div class="page">
    <div class="page-wrapper">

        <!-- 🎖️ BADGES DU DAIMYŌ (IMMUNITÉ, KOBAN, SCEAU IMPÉRIAL) EN HAUT À DROITE AU-DESSUS DU LOGO -->
        <div class="container-xl d-print-none px-3 px-xl-0 pt-2 pb-0">
            <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                <?php if ($isUserProtected): ?>
                    <span class="badge bg-success-lt d-inline-flex align-items-center gap-1 py-1 px-2"
                          title="🔰 Immunité Féodale active jusqu'au <?= htmlspecialchars($userProtection['until_formatted']) ?> (aucun assaut ni espionnage possible sur vos fiefs)">
                        <span>🔰</span>
                        <span class="fw-bold"><?= htmlspecialchars($userProtection['formatted']) ?></span>
                    </span>
                <?php endif; ?>

                <!-- 🪙 Trésor en Koban (Pièces d'Or) -->
                <a href="?page=privilege" class="badge bg-warning-lt text-warning d-inline-flex align-items-center gap-1 py-1 px-3 text-decoration-none shadow-sm"
                   title="Trésor Impérial : <?= number_format($userGoldCoins) ?> Koban. Cliquez pour ouvrir la page des Privilèges du Shōgun.">
                    <span>🪙</span>
                    <strong><?= number_format($userGoldCoins) ?></strong>
                    <span class="text-muted">Koban</span>
                </a>

                <!-- 👑 Sceau Impérial -->
                <a href="?page=privilege" 
                   class="badge <?= $isSealActive ? 'bg-warning text-warning-fg' : 'bg-secondary-lt' ?> d-inline-flex align-items-center gap-1 py-1 px-3 text-decoration-none shadow-sm"
                   title="<?= $isSealActive ? 'Sceau Impérial Actif : ' . $sealStatus['remaining_formatted'] : 'Décrétez le Sceau Impérial du Shōgun' ?>">
                    <span>👑</span>
                    <span><?= $isSealActive ? 'Sceau Actif (' . $sealStatus['remaining_formatted'] . ')' : 'Sceau Impérial' ?></span>
                </a>
            </div>
        </div>

        <!-- 🏯 LOGO EN HAUT AU MILIEU -->
        <div class="text-center py-2 d-print-none">
            <a href="?page=resources" class="brand-logo-link" title="<?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?>">
                <img src="/public/assets/logo_transparent.png?v=<?= file_exists(__DIR__ . '/../../public/assets/logo_transparent.png') ? filemtime(__DIR__ . '/../../public/assets/logo_transparent.png') : 1 ?>" 
                     alt="<?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?>" 
                     style="height: 160px; max-height: 185px; width: auto; max-width: 92vw; object-fit: contain;">
            </a>
        </div>

        <!-- ── BARRE DE NAVIGATION TABLER NATIVE (Largeur frame centrale container-xl) ── -->
        <div class="container-xl d-print-none px-3 px-xl-0">
            <header class="navbar navbar-expand-md navbar-light bg-white border rounded shadow-sm px-2">
                <div class="container-fluid px-1">

                    <!-- Gauche : Fief & Héros -->
                    <div class="d-flex align-items-center gap-2 me-3">
                        <?php
                        $hHp = $heroHeader ? round((float)$heroHeader['health']) : 100;
                        $hHpCol = ($hHp >= 60) ? 'border-success' : (($hHp >= 25) ? 'border-warning' : 'border-danger');
                        $hLvl = $heroHeader ? (int)$heroHeader['level'] : 1;
                        $hasPoints = ($heroHeader && (int)$heroHeader['unassigned_points'] > 0);
                        ?>
                        <a href="?page=hero" class="position-relative d-inline-flex align-items-center"
                           title="Samouraï Héros Niv.<?= $hLvl ?> — Vitalité <?= $hHp ?>%">
                            <span class="avatar avatar-sm rounded-circle border <?= $hHpCol ?>" style="background-image: url(/public/assets/hero_samurai.jpg)"></span>
                            <span class="badge bg-dark text-white position-absolute"
                                  style="bottom:-3px; right:-3px; font-size:0.55rem; padding:1px 3px; border-radius:3px;"><?= $hLvl ?></span>
                            <?php if ($hasPoints): ?>
                                <span class="badge bg-danger position-absolute"
                                      style="top:-3px; right:-3px; font-size:0.55rem; padding:1px 4px; border-radius:50%;">+</span>
                            <?php endif; ?>
                        </a>

                        <?php if ($planet): ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex flex-column text-start px-2 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="max-width:180px;">
                                <span class="fw-bold text-truncate" style="font-size:0.82rem; line-height:1.2;">
                                    <?= !empty($planet['is_capital']) ? '👑 ' : '🏯 ' ?><?= htmlspecialchars($planet['name']) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.68rem; font-family:monospace;">
                                    [<?= $planet['coord_x'] ?>|<?= $planet['coord_y'] ?>] <?= !empty($planet['is_capital']) ? '<span class="text-warning fw-bold">(Capitale)</span>' : '' ?>
                                </span>
                            </button>
                            <ul class="dropdown-menu shadow-sm" style="min-width:220px; z-index:1050;">
                                <li class="dropdown-header text-uppercase fw-bold d-flex justify-content-between align-items-center">
                                    <span>Vos Fiefs Féodaux</span>
                                    <span class="badge bg-secondary-lt"><?= count($allUserPlanets) ?></span>
                                </li>
                                <?php foreach ($allUserPlanets as $p): 
                                    $isCurrent = ((int)$p['id'] === (int)$planet['id']);
                                ?>
                                    <li>
                                        <a class="dropdown-item d-flex justify-content-between align-items-center py-2 <?= $isCurrent ? 'active' : '' ?>" href="?switch_planet=<?= (int)$p['id'] ?>">
                                            <div>
                                                <div class="fw-bold" style="font-size:0.85rem;">
                                                    <?= !empty($p['is_capital']) ? '👑' : '🏯' ?> <?= htmlspecialchars($p['name']) ?>
                                                </div>
                                                <div class="text-muted small" style="font-family:monospace;">
                                                    [<?= $p['coord_x'] ?>|<?= $p['coord_y'] ?>] <?= !empty($p['is_capital']) ? '<span class="text-warning">Capitale</span>' : '' ?>
                                                </div>
                                            </div>
                                            <?php if ($isCurrent): ?>
                                                <span class="badge bg-primary text-white" style="font-size:0.65rem;">Actif</span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Toggler mobile -->
                    <button class="navbar-toggler" type="button"
                            data-bs-toggle="collapse" data-bs-target="#mainNavBar">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <!-- Navigation centrale + Menu Utilisateur à droite -->
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

                            <?php if ($questSummary): ?>
                            <li class="nav-item">
                                <a class="nav-link"
                                   href="javascript:void(0)" onclick="openQuestModal()"
                                   title="Didacticiel & Quêtes Féodales">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">🎯</span>
                                    <span class="nav-link-title">Quêtes</span>
                                    <?php if ($questSummary['claimable_count'] > 0): ?>
                                        <span class="badge bg-success ms-1" style="font-size:0.6rem;"><?= $questSummary['claimable_count'] ?></span>
                                    <?php elseif (!$questSummary['all_completed']): ?>
                                        <span class="badge bg-secondary-lt ms-1" style="font-size:0.6rem;"><?= $questSummary['claimed_count'] ?>/<?= $questSummary['total_quests'] ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>

                        <!-- Menu Utilisateur Tabler à droite -->
                        <div class="navbar-nav flex-row order-md-last">
                            <div class="nav-item dropdown">
                                <a href="#" class="nav-link d-flex lh-1 text-reset p-0"
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="avatar avatar-sm" style="background-color: <?= $factionInfo['color'] ?? '#dc2626' ?>; color: #fff; font-weight:bold;">
                                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                    </span>
                                    <div class="d-none d-xl-block ps-2">
                                        <div><?= htmlspecialchars($user['username']) ?></div>
                                        <div class="mt-1 small text-muted"><?= htmlspecialchars($factionInfo['name'] ?? 'Daimyō') ?></div>
                                    </div>
                                    <?php if ($unreadMessagesCount > 0): ?>
                                        <span class="badge bg-danger ms-1"><?= $unreadMessagesCount ?></span>
                                    <?php endif; ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end shadow">
                                    <?php if ($isUserProtected): ?>
                                        <div class="dropdown-item-text small bg-success-lt text-success fw-bold">
                                            🔰 Immunité active : <?= htmlspecialchars($userProtection['formatted']) ?>
                                        </div>
                                        <div class="dropdown-divider"></div>
                                    <?php endif; ?>

                                    <!-- Privilèges & Empire du Shōgun -->
                                    <div class="dropdown-header text-uppercase small text-muted">Privilèges du Shōgun</div>
                                    <a href="?page=empire" class="dropdown-item d-flex align-items-center justify-content-between <?= $page === 'empire' ? 'active' : '' ?>">
                                        <span>👑 Tableau de Bord de l'Empire</span>
                                        <?php if ($isSealActive): ?>
                                            <span class="badge bg-warning text-warning-fg" style="font-size:0.6rem;">Actif</span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="?page=privilege" class="dropdown-item d-flex align-items-center justify-content-between <?= $page === 'privilege' ? 'active' : '' ?>">
                                        <span>📜 Privilèges du Shōgun</span>
                                        <span class="badge bg-warning-lt" style="font-size:0.6rem;"><?= number_format($userGoldCoins) ?> 🪙</span>
                                    </a>

                                    <div class="dropdown-divider"></div>

                                    <!-- Profil Daimyō -->
                                    <a href="javascript:void(0)" class="dropdown-item"
                                       onclick="openPlayerProfileModal(<?= (int)$user['id'] ?>)">👤 Ma Fiche Daimyō</a>
                                    <a href="javascript:void(0)" class="dropdown-item"
                                       onclick="openEditMottoModal()">📜 Ma Devise</a>

                                    <!-- Communications & Décrets -->
                                    <div class="dropdown-divider"></div>
                                    <div class="dropdown-header text-uppercase small text-muted">Communications</div>
                                    <a href="?page=messages" class="dropdown-item d-flex align-items-center justify-content-between <?= $page === 'messages' ? 'active' : '' ?>">
                                        <span>✉️ Missives</span>
                                        <?php if ($unreadMessagesCount > 0): ?>
                                            <span class="badge bg-danger rounded-pill"><?= $unreadMessagesCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="?page=chat" class="dropdown-item <?= $page === 'chat' ? 'active' : '' ?>">🏮 Chat Féodal</a>
                                    <a href="?page=forum" class="dropdown-item <?= $page === 'forum' ? 'active' : '' ?>">💬 Forum Féodal</a>
                                    <a href="?page=reports" class="dropdown-item <?= $page === 'reports' ? 'active' : '' ?>">📜 Chroniques</a>
                                    <a href="?page=ranking" class="dropdown-item <?= $page === 'ranking' ? 'active' : '' ?>">🏆 Classement</a>

                                    <!-- Savoir & Administration -->
                                    <div class="dropdown-divider"></div>
                                    <div class="dropdown-header text-uppercase small text-muted">Savoir & Shogunat</div>
                                    <a href="?page=docs" class="dropdown-item <?= $page === 'docs' ? 'active' : '' ?>">📖 Codex du Sengoku</a>
                                    <?php if ($auth->isAdmin()): ?>
                                        <a href="?page=admin" class="dropdown-item text-primary fw-bold <?= $page === 'admin' ? 'active' : '' ?>">⚙️ Administration</a>
                                    <?php endif; ?>
                                    <a href="?page=support" class="dropdown-item <?= $page === 'support' ? 'active' : '' ?>">📮 Support &amp; Aide</a>

                                    <!-- Préférences & Déconnexion -->
                                    <div class="dropdown-divider"></div>
                                    <button type="button" class="dropdown-item" id="shogun-audio-btn"
                                            onclick="window.shogunAudio && window.shogunAudio.toggle()">
                                        <span id="shogun-audio-icon">🔇</span> Ambiance sonore
                                    </button>
                                    <button type="button" class="dropdown-item" onclick="toggleTheme()">🌓 Thème clair / sombre</button>
                                    <div class="dropdown-divider"></div>
                                    <a href="?action=logout" class="dropdown-item text-danger">🚪 Déconnexion</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
        </div>

        <?php if ($planet): ?>
        <!-- ── 6 CARRÉS DE RESSOURCES CENTRÉS (Ultra-compacts, largeur frame centrale container-xl) ── -->
        <div class="container-xl d-print-none mt-3 mb-2 px-3 px-xl-0">
            <div class="row g-2 justify-content-center">
                <?php
                $pctMetal = min(100, ($planet['metal'] / max(1, $planet['metal_max'])) * 100);
                $pctCrystal = min(100, ($planet['crystal'] / max(1, $planet['crystal_max'])) * 100);
                $pctDeut = min(100, ($planet['deuterium'] / max(1, $planet['deuterium_max'])) * 100);

                $flourStock = (float)($planet['rice_flour'] ?? 0);
                $flourMax = max(1, (int)($planet['rice_flour_max'] ?? 10000));
                $pctFlour = min(100, ($flourStock / $flourMax) * 100);

                $sakeStock = (float)($planet['sake'] ?? 0);
                $sakeMax = max(1, (int)($planet['sake_max'] ?? 10000));
                $pctSake = min(100, ($sakeStock / $sakeMax) * 100);

                $eBalance = $planet['energy_max'] - $planet['energy_used'];
                $eOk = ($eBalance >= 0);
                $pctEnergy = ($planet['energy_max'] > 0) ? min(100, ($planet['energy_used'] / $planet['energy_max']) * 100) : 0;
                ?>

                <!-- 1. Bois de Cèdre -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card card-sm shadow-sm border-start border-1 border-warning">
                        <div class="card-body p-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-1 text-truncate">
                                    <span style="font-size:0.95rem;">🪵</span>
                                    <strong class="text-warning" style="font-size:0.80rem;">Bois</strong>
                                    <span class="text-muted" style="font-size:0.65rem;">(+<?= number_format($planet['prod_rates']['metal']) ?>/h)</span>
                                </div>
                                <div class="text-end" style="font-variant-numeric:tabular-nums; white-space:nowrap;">
                                    <span class="fw-bold" style="font-size:0.82rem;"
                                          id="res-val-metal"
                                          data-current="<?= $planet['metal'] ?>"
                                          data-max="<?= $planet['metal_max'] ?>"
                                          data-prod="<?= $planet['prod_rates']['metal'] ?>">
                                        <?= number_format((int)$planet['metal']) ?>
                                    </span>
                                    <span class="text-muted" style="font-size:0.62rem;">/ <?= number_format($planet['metal_max']) ?></span>
                                </div>
                            </div>
                            <div class="progress progress-xs mt-1">
                                <div class="progress-bar bg-warning" id="bar-metal" style="width:<?= $pctMetal ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Pierre de Taille -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card card-sm shadow-sm border-start border-1 border-primary">
                        <div class="card-body p-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-1 text-truncate">
                                    <span style="font-size:0.95rem;">🪨</span>
                                    <strong class="text-primary" style="font-size:0.80rem;">Pierre</strong>
                                    <span class="text-muted" style="font-size:0.65rem;">(+<?= number_format($planet['prod_rates']['crystal']) ?>/h)</span>
                                </div>
                                <div class="text-end" style="font-variant-numeric:tabular-nums; white-space:nowrap;">
                                    <span class="fw-bold" style="font-size:0.82rem;"
                                          id="res-val-crystal"
                                          data-current="<?= $planet['crystal'] ?>"
                                          data-max="<?= $planet['crystal_max'] ?>"
                                          data-prod="<?= $planet['prod_rates']['crystal'] ?>">
                                        <?= number_format((int)$planet['crystal']) ?>
                                    </span>
                                    <span class="text-muted" style="font-size:0.62rem;">/ <?= number_format($planet['crystal_max']) ?></span>
                                </div>
                            </div>
                            <div class="progress progress-xs mt-1">
                                <div class="progress-bar bg-primary" id="bar-crystal" style="width:<?= $pctCrystal ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Riz Impérial -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card card-sm shadow-sm border-start border-1 border-success">
                        <div class="card-body p-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-1 text-truncate">
                                    <span style="font-size:0.95rem;">🌾</span>
                                    <strong class="text-success" style="font-size:0.80rem;">Riz</strong>
                                    <span class="text-muted" style="font-size:0.65rem;">(+<?= number_format($planet['prod_rates']['deuterium']) ?>/h)</span>
                                </div>
                                <div class="text-end" style="font-variant-numeric:tabular-nums; white-space:nowrap;">
                                    <span class="fw-bold" style="font-size:0.82rem;"
                                          id="res-val-deut"
                                          data-current="<?= $planet['deuterium'] ?>"
                                          data-max="<?= $planet['deuterium_max'] ?>"
                                          data-prod="<?= $planet['prod_rates']['deuterium'] ?>">
                                        <?= number_format((int)$planet['deuterium']) ?>
                                    </span>
                                    <span class="text-muted" style="font-size:0.62rem;">/ <?= number_format($planet['deuterium_max']) ?></span>
                                </div>
                            </div>
                            <div class="progress progress-xs mt-1">
                                <div class="progress-bar bg-success" id="bar-deut" style="width:<?= $pctDeut ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Farine de Riz (Komeko) -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card card-sm shadow-sm border-start border-1 border-secondary" title="Farine de Riz (Raffinée en Meunerie)">
                        <div class="card-body p-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-1 text-truncate">
                                    <span style="font-size:0.95rem;">🍚</span>
                                    <strong class="text-secondary" style="font-size:0.80rem;">Farine</strong>
                                </div>
                                <div class="text-end" style="font-variant-numeric:tabular-nums; white-space:nowrap;">
                                    <span class="fw-bold" style="font-size:0.82rem;"
                                          id="res-val-rice-flour"
                                          data-current="<?= $flourStock ?>"
                                          data-max="<?= $flourMax ?>">
                                        <?= number_format((int)$flourStock) ?>
                                    </span>
                                    <span class="text-muted" style="font-size:0.62rem;">/ <?= number_format($flourMax) ?></span>
                                </div>
                            </div>
                            <div class="progress progress-xs mt-1">
                                <div class="progress-bar bg-secondary" id="bar-rice-flour" style="width:<?= $pctFlour ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Saké Féodal (Sakagura) -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card card-sm shadow-sm border-start border-1 border-purple" title="Saké Impérial (Brassé en Meunerie / Sakagura)">
                        <div class="card-body p-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-1 text-truncate">
                                    <span style="font-size:0.95rem;">🍶</span>
                                    <strong class="text-purple" style="font-size:0.80rem;">Saké</strong>
                                </div>
                                <div class="text-end" style="font-variant-numeric:tabular-nums; white-space:nowrap;">
                                    <span class="fw-bold" style="font-size:0.82rem;"
                                          id="res-val-sake"
                                          data-current="<?= $sakeStock ?>"
                                          data-max="<?= $sakeMax ?>">
                                        <?= number_format((int)$sakeStock) ?>
                                    </span>
                                    <span class="text-muted" style="font-size:0.62rem;">/ <?= number_format($sakeMax) ?></span>
                                </div>
                            </div>
                            <div class="progress progress-xs mt-1">
                                <div class="progress-bar bg-purple" id="bar-sake" style="width:<?= $pctSake ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Sérénité Shinto -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card card-sm shadow-sm border-start border-1 <?= $eOk ? 'border-teal' : 'border-danger' ?>">
                        <div class="card-body p-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-1 text-truncate">
                                    <span style="font-size:0.95rem;">⛩️</span>
                                    <strong class="<?= $eOk ? 'text-teal' : 'text-danger' ?>" style="font-size:0.80rem;">Sérénité</strong>
                                    <span class="badge <?= $eOk ? 'bg-teal-lt text-teal' : 'bg-danger-lt text-danger' ?>" style="font-size:0.58rem; padding:1px 4px;">
                                        <?= $eOk ? 'OK' : 'Déficit' ?>
                                    </span>
                                </div>
                                <div class="text-end" style="font-variant-numeric:tabular-nums; white-space:nowrap;">
                                    <span class="fw-bold <?= $eOk ? 'text-teal' : 'text-danger' ?>" style="font-size:0.82rem;">
                                        <?= ($eBalance >= 0 ? '+' : '') . number_format($eBalance) ?>
                                    </span>
                                    <span class="text-muted" style="font-size:0.62rem;">(<?= number_format($planet['energy_used']) ?>/<?= number_format($planet['energy_max']) ?>)</span>
                                </div>
                            </div>
                            <div class="progress progress-xs mt-1">
                                <div class="progress-bar <?= $eOk ? 'bg-teal' : 'bg-danger' ?>" style="width:<?= $pctEnergy ?>%;"></div>
                            </div>
                        </div>
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
        <div class="container-xl d-print-none px-3 px-xl-0">
            <div class="travian-alert-banner <?= $alertClass ?>"
                 onclick="openWatchtowerModal()"
                 title="Cliquer pour afficher le registre de la Tour de Guet (<?= count($activeMissions) ?> mouvements)">
                <div class="alert-banner-left">
                    <?php if ($hasHostile): ?>
                        <span class="alert-status-badge threat">🚨 TOUR DE GUET</span>
                        <span class="alert-headline"><strong><?= count($incomingHostile) ?> incursion(s) armée(s)</strong> en approche !</span>
                        <span class="alert-countdown-chip">Impact dans <strong data-countdown="<?= $closestTime ?>">Calcul...</strong></span>
                    <?php elseif ($hasSpy): ?>
                        <span class="alert-status-badge spy">🥷 TOUR DE GUET</span>
                        <span class="alert-headline"><strong>Infiltration Shinobi détectée</strong> vers votre domaine !</span>
                        <span class="alert-countdown-chip">Arrivée dans <strong data-countdown="<?= $closestTime ?>">Calcul...</strong></span>
                    <?php else: ?>
                        <span class="alert-status-badge info">🐎 EXPÉDITIONS</span>
                        <span class="alert-headline"><strong><?= count($outgoingMissions) ?> troupe(s)</strong> en marche sur les provinces.</span>
                        <span class="alert-countdown-chip">Retour dans <strong data-countdown="<?= $closestTime ?>">Calcul...</strong></span>
                    <?php endif; ?>
                </div>
                <div class="alert-banner-right">
                    <span class="alert-cta-btn">
                        <span>📜 Détails (<?= count($activeMissions) ?>)</span>
                        <span class="alert-cta-arrow">&rarr;</span>
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- CORPS DE PAGE -->
        <div class="page-body">
            <div class="container-xl <?= ($page === 'map' || $page === 'galaxy') ? 'container-fluid px-0' : '' ?>">


