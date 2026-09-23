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

    $incomingHostile = [];
    $incomingSpy = [];
    $outgoingMissions = [];
    foreach ($activeMissions as $m) {
        if ($m['target_planet_id'] == $planet['id'] && $m['status'] === 'en_route') {
            if ($m['mission_type'] === 'spy') {
                $incomingSpy[] = $m;
            } else {
                $incomingHostile[] = $m;
            }
        } else {
            $outgoingMissions[] = $m;
        }
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
    ['page' => 'map',       'match' => ['map','galaxy'],      'icon' => '🗾', 'label' => 'Carte',               'title' => 'Carte des Provinces'],
    ['page' => 'fleet',     'match' => ['fleet'],             'icon' => '⚔️', 'label' => 'Armées',             'title' => 'Expéditions militaires'],
    ['page' => 'hero',      'match' => ['hero'],              'icon' => '🥋', 'label' => 'Héros',              'title' => 'Votre Samouraï Héros'],
    ['page' => 'alliance',  'match' => ['alliance'],          'icon' => '🎌', 'label' => 'Alliance',           'title' => 'Pacte Féodal & Ambassade'],
];
?>

<!-- ===== LAYOUT TABLER PLEINE LARGEUR (pas de sidebar) ===== -->
<div class="page">
    <div class="page-wrapper">

        <!-- 🏯 LOGO EN HAUT AU MILIEU EN GRAND -->
        <div class="text-center py-3 d-print-none" style="display:flex; justify-content:center; align-items:center;">
            <a href="?page=resources" class="brand-logo-link" title="<?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?>">
                <img src="/public/assets/logo_transparent.png?v=<?= file_exists(__DIR__ . '/../../public/assets/logo_transparent.png') ? filemtime(__DIR__ . '/../../public/assets/logo_transparent.png') : 1 ?>" 
                     alt="<?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?>" 
                     class="brand-logo-img"
                     style="height: 130px; max-height: 145px; width: auto; max-width: 90vw; object-fit: contain; filter: drop-shadow(0 4px 14px rgba(0, 0, 0, 0.2)); transition: transform 0.2s ease;">
            </a>
        </div>

        <!-- ── BARRE DE NAVIGATION PRINCIPALE (Largeur frame centrale container-xl) ── -->
        <div class="container-xl d-print-none px-3 px-xl-0">
            <nav class="navbar navbar-expand-lg"
                 style="background:linear-gradient(135deg,#1a0a00 0%,#2d1200 60%,#1a0a00 100%);
                        border:2px solid rgba(194,37,43,0.5);
                        border-radius:10px;
                        min-height:48px; padding:0.25rem 0.65rem;
                        box-shadow:0 4px 15px rgba(0,0,0,0.2);">
                <div class="container-fluid px-1">

                    <!-- Gauche : Fief & Héros -->
                    <div class="d-flex align-items-center gap-2 me-3">
                        <?php
                        $hHp = $heroHeader ? round((float)$heroHeader['health']) : 100;
                        $hHpCol = ($hHp >= 60) ? '#16a34a' : (($hHp >= 25) ? '#d97706' : '#dc2626');
                        $hLvl = $heroHeader ? (int)$heroHeader['level'] : 1;
                        $hasPoints = ($heroHeader && (int)$heroHeader['unassigned_points'] > 0);
                        ?>
                        <a href="?page=hero" class="position-relative d-inline-flex align-items-center"
                           title="Samouraï Héros Niv.<?= $hLvl ?> — Vitalité <?= $hHp ?>%">
                            <img src="/public/assets/hero_samurai.jpg" alt="Héros"
                                 style="width:32px; height:32px; border-radius:50%; border:2px solid <?= $hHpCol ?>; object-fit:cover;">
                            <span class="badge bg-dark text-white position-absolute"
                                  style="bottom:-3px; right:-3px; font-size:0.55rem; padding:1px 3px; border-radius:3px;"><?= $hLvl ?></span>
                            <?php if ($hasPoints): ?>
                                <span class="badge bg-danger position-absolute"
                                      style="top:-3px; right:-3px; font-size:0.55rem; padding:1px 4px; border-radius:50%;">+</span>
                            <?php endif; ?>
                        </a>

                        <?php if ($planet): ?>
                        <div class="dropdown ms-1">
                            <button class="btn btn-sm dropdown-toggle d-flex flex-column text-start p-1 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background:rgba(255,255,255,0.07); border:1px solid rgba(245,158,11,0.25); border-radius:6px; max-width:170px;">
                                <div style="font-weight:800; font-size:0.82rem; color:#fef3c7; line-height:1.1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= !empty($planet['is_capital']) ? '👑 ' : '🏯 ' ?><?= htmlspecialchars($planet['name']) ?>
                                </div>
                                <div style="font-size:0.67rem; color:#f87171; font-family:monospace; font-weight:700;">
                                    [<?= $planet['coord_x'] ?>|<?= $planet['coord_y'] ?>] <?= !empty($planet['is_capital']) ? '<span style="color:#fbbf24; font-size:0.60rem;">(Capitale)</span>' : '' ?>
                                </div>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark shadow" style="background:#1e293b; border:1px solid rgba(245,158,11,0.3); min-width:220px; z-index:1050;">
                                <li class="dropdown-header text-uppercase text-warning fw-bold d-flex justify-content-between align-items-center" style="font-size:0.68rem; letter-spacing:0.5px;">
                                    <span>Vos Fiefs Féodaux</span>
                                    <span class="badge bg-secondary"><?= count($allUserPlanets) ?></span>
                                </li>
                                <?php foreach ($allUserPlanets as $p): 
                                    $isCurrent = ((int)$p['id'] === (int)$planet['id']);
                                ?>
                                    <li>
                                        <a class="dropdown-item d-flex justify-content-between align-items-center py-2 <?= $isCurrent ? 'active fw-bold' : '' ?>" href="?switch_planet=<?= (int)$p['id'] ?>" style="<?= $isCurrent ? 'background:rgba(245,158,11,0.2); color:#fbbf24;' : '' ?>">
                                            <div>
                                                <div style="font-size:0.84rem; line-height:1.2;">
                                                    <?= !empty($p['is_capital']) ? '👑' : '🏯' ?> <?= htmlspecialchars($p['name']) ?>
                                                </div>
                                                <div style="font-size:0.7rem; color:#94a3b8; font-family:monospace;">
                                                    [<?= $p['coord_x'] ?>|<?= $p['coord_y'] ?>] <?= !empty($p['is_capital']) ? '<span class="text-warning">Capitale</span>' : '' ?>
                                                </div>
                                            </div>
                                            <?php if ($isCurrent): ?>
                                                <span class="badge bg-warning text-dark" style="font-size:0.65rem;">Actif</span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Toggler mobile -->
                    <button class="navbar-toggler border-0" type="button"
                            data-bs-toggle="collapse" data-bs-target="#mainNavBar"
                            style="color:#f59e0b;">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <!-- Navigation centrale + Menu Utilisateur descendu à droite -->
                    <div class="collapse navbar-collapse" id="mainNavBar">
                        <ul class="navbar-nav me-auto">
                            <?php foreach ($navItems as $nav):
                                $isActive = in_array($page, $nav['match']);
                            ?>
                            <li class="nav-item">
                                <a class="nav-link px-2 py-2 d-flex align-items-center gap-1 position-relative
                                           <?= $isActive ? 'active fw-bold' : '' ?>"
                                   href="?page=<?= $nav['page'] ?>"
                                   title="<?= htmlspecialchars($nav['title']) ?>"
                                   style="color:<?= $isActive ? '#fbbf24' : '#e2d9c8' ?>;
                                          font-size:0.82rem; white-space:nowrap;
                                          border-bottom:<?= $isActive ? '2px solid #dc2626' : '2px solid transparent' ?>;
                                          border-radius:0;">
                                    <span><?= $nav['icon'] ?></span>
                                    <span><?= $nav['label'] ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>

                            <?php if ($questSummary): ?>
                            <li class="nav-item">
                                <a class="nav-link px-2 py-2 d-flex align-items-center gap-1 position-relative"
                                   href="javascript:void(0)" onclick="openQuestModal()"
                                   title="Didacticiel & Quêtes Féodales"
                                   style="color:#e2d9c8; font-size:0.82rem; white-space:nowrap;
                                          border-bottom:2px solid transparent; border-radius:0;">
                                    <span>🎯</span>
                                    <span>Quêtes</span>
                                    <?php if ($questSummary['claimable_count'] > 0): ?>
                                        <span class="badge bg-success ms-1" style="font-size:0.6rem;"><?= $questSummary['claimable_count'] ?></span>
                                    <?php elseif (!$questSummary['all_completed']): ?>
                                        <span class="badge bg-secondary ms-1" style="font-size:0.6rem;"><?= $questSummary['claimed_count'] ?>/<?= $questSummary['total_quests'] ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>

                        <!-- Outils à droite + Bouton Menu Utilisateur (sans redondance) -->
                        <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
                            <?php if ($isUserProtected): ?>
                                <span class="badge bg-success-lt text-success d-none d-xl-inline-flex align-items-center gap-1"
                                      style="font-size:0.72rem; padding:0.25rem 0.55rem; cursor:help; border: 1px solid rgba(22, 163, 74, 0.45); border-radius:6px;"
                                      title="🔰 Immunité Féodale active jusqu'au <?= htmlspecialchars($userProtection['until_formatted']) ?> (aucun assaut ni espionnage possible sur vos fiefs)">
                                    <span>🔰</span>
                                    <span><?= htmlspecialchars($userProtection['formatted']) ?></span>
                                </span>
                            <?php endif; ?>

                            <!-- Menu Déroulant Utilisateur (Daimyō) : Profil, Communications, Codex, Admin, Déconnexion -->
                            <div class="dropdown">
                                <a href="#" class="btn btn-sm btn-dark d-flex align-items-center gap-2 text-decoration-none px-2 py-1"
                                   data-bs-toggle="dropdown" aria-expanded="false"
                                   style="background:rgba(255,255,255,0.08); border:1px solid rgba(251,191,36,0.35); border-radius:6px;">
                                    <span style="width:8px; height:8px; border-radius:50%; display:inline-block;
                                                 background:<?= $factionInfo['color'] ?? '#dc2626' ?>;"></span>
                                    <span style="font-weight:700; font-size:0.82rem; color:#fef3c7;"><?= htmlspecialchars($user['username']) ?></span>
                                    <?php if ($unreadMessagesCount > 0): ?>
                                        <span class="badge bg-danger rounded-pill" style="font-size:0.6rem; padding:2px 5px;" title="<?= $unreadMessagesCount ?> missive(s) non lue(s)"><?= $unreadMessagesCount ?></span>
                                    <?php endif; ?>
                                    <?php if ($isUserProtected): ?>
                                        <span class="d-sm-none" title="Immunité active">🔰</span>
                                    <?php endif; ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#fbbf24;">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width:230px; z-index:1060;">
                                    <?php if ($isUserProtected): ?>
                                        <div class="dropdown-item-text small" style="background: rgba(22, 163, 74, 0.08); border-left: 3px solid #16a34a; padding: 0.5rem 0.75rem; margin-bottom: 0.25rem;">
                                            <div style="font-weight: 700; color: #15803d; display:flex; align-items:center; gap:0.3rem;">
                                                <span>🔰</span> Immunité Débutant Active
                                            </div>
                                            <div class="text-secondary" style="font-size: 0.75rem; margin-top:0.2rem;">
                                                Jusqu'au <?= htmlspecialchars($userProtection['until_formatted']) ?> (encore <?= htmlspecialchars($userProtection['formatted']) ?>)
                                            </div>
                                        </div>
                                        <div class="dropdown-divider"></div>
                                    <?php endif; ?>

                                    <!-- Profil Daimyō -->
                                    <a href="javascript:void(0)" class="dropdown-item"
                                       onclick="openPlayerProfileModal(<?= (int)$user['id'] ?>)">👤 Ma Fiche Daimyō</a>
                                    <a href="javascript:void(0)" class="dropdown-item"
                                       onclick="openEditMottoModal()">📜 Ma Devise</a>

                                    <!-- Communications & Décrets -->
                                    <div class="dropdown-divider"></div>
                                    <div class="dropdown-header text-uppercase small text-muted" style="font-size:0.65rem;">Communications & Décrets</div>
                                    <a href="?page=messages" class="dropdown-item d-flex align-items-center justify-content-between <?= $page === 'messages' ? 'active' : '' ?>">
                                        <span>✉️ Missives</span>
                                        <?php if ($unreadMessagesCount > 0): ?>
                                            <span class="badge bg-danger rounded-pill" style="font-size:0.65rem;"><?= $unreadMessagesCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="?page=chat" class="dropdown-item <?= $page === 'chat' ? 'active' : '' ?>">🏮 Chat Féodal</a>
                                    <a href="?page=forum" class="dropdown-item <?= $page === 'forum' ? 'active' : '' ?>">💬 Forum Féodal</a>
                                    <a href="?page=reports" class="dropdown-item <?= $page === 'reports' ? 'active' : '' ?>">📜 Chroniques</a>
                                    <a href="?page=ranking" class="dropdown-item <?= $page === 'ranking' ? 'active' : '' ?>">🏆 Classement</a>

                                    <!-- Savoir & Administration -->
                                    <div class="dropdown-divider"></div>
                                    <div class="dropdown-header text-uppercase small text-muted" style="font-size:0.65rem;">Savoir & Shogunat</div>
                                    <a href="?page=docs" class="dropdown-item <?= $page === 'docs' ? 'active' : '' ?>">📖 Codex du Sengoku</a>
                                    <?php if ($auth->isAdmin()): ?>
                                        <a href="?page=admin" class="dropdown-item text-warning fw-bold <?= $page === 'admin' ? 'active' : '' ?>">⚙️ Administration</a>
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
            </nav>
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
                    <div class="card shadow-sm" style="background:#ffffff; border:1px solid #e7e5e4; border-radius:6px; border-left:3px solid #92400e !important; padding:0.35rem 0.65rem;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-1 text-truncate">
                                <span style="font-size:0.95rem;">🪵</span>
                                <strong style="font-size:0.78rem; color:#92400e;">Bois</strong>
                                <span class="text-muted" style="font-size:0.65rem;">(+<?= number_format($planet['prod_rates']['metal']) ?>/h)</span>
                            </div>
                            <div class="text-end" style="font-variant-numeric:tabular-nums; white-space:nowrap;">
                                <span class="fw-bold" style="font-size:0.82rem; color:#1c1917;"
                                      id="res-val-metal"
                                      data-current="<?= $planet['metal'] ?>"
                                      data-max="<?= $planet['metal_max'] ?>"
                                      data-prod="<?= $planet['prod_rates']['metal'] ?>">
                                    <?= number_format((int)$planet['metal']) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;">/ <?= number_format($planet['metal_max']) ?></span>
                            </div>
                        </div>
                        <div class="progress" style="height:3px; border-radius:2px; background:#f0eeeb; margin-top:4px;">
                            <div class="progress-bar bg-warning" id="bar-metal" style="width:<?= $pctMetal ?>%;"></div>
                        </div>
                    </div>
                </div>

                <!-- 2. Pierre de Taille -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card shadow-sm" style="background:#ffffff; border:1px solid #e7e5e4; border-radius:6px; border-left:3px solid #1e40af !important; padding:0.35rem 0.65rem;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-1 text-truncate">
                                <span style="font-size:0.95rem;">🪨</span>
                                <strong style="font-size:0.78rem; color:#1e40af;">Pierre</strong>
                                <span class="text-muted" style="font-size:0.65rem;">(+<?= number_format($planet['prod_rates']['crystal']) ?>/h)</span>
                            </div>
                            <div class="text-end" style="font-variant-numeric:tabular-nums; white-space:nowrap;">
                                <span class="fw-bold" style="font-size:0.82rem; color:#1c1917;"
                                      id="res-val-crystal"
                                      data-current="<?= $planet['crystal'] ?>"
                                      data-max="<?= $planet['crystal_max'] ?>"
                                      data-prod="<?= $planet['prod_rates']['crystal'] ?>">
                                    <?= number_format((int)$planet['crystal']) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;">/ <?= number_format($planet['crystal_max']) ?></span>
                            </div>
                        </div>
                        <div class="progress" style="height:3px; border-radius:2px; background:#f0eeeb; margin-top:4px;">
                            <div class="progress-bar bg-primary" id="bar-crystal" style="width:<?= $pctCrystal ?>%;"></div>
                        </div>
                    </div>
                </div>

                <!-- 3. Riz Impérial -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card shadow-sm" style="background:#ffffff; border:1px solid #e7e5e4; border-radius:6px; border-left:3px solid #166534 !important; padding:0.35rem 0.65rem;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-1 text-truncate">
                                <span style="font-size:0.95rem;">🌾</span>
                                <strong style="font-size:0.78rem; color:#166534;">Riz</strong>
                                <span class="text-muted" style="font-size:0.65rem;">(+<?= number_format($planet['prod_rates']['deuterium']) ?>/h)</span>
                            </div>
                            <div class="text-end" style="font-variant-numeric:tabular-nums; white-space:nowrap;">
                                <span class="fw-bold" style="font-size:0.82rem; color:#1c1917;"
                                      id="res-val-deut"
                                      data-current="<?= $planet['deuterium'] ?>"
                                      data-max="<?= $planet['deuterium_max'] ?>"
                                      data-prod="<?= $planet['prod_rates']['deuterium'] ?>">
                                    <?= number_format((int)$planet['deuterium']) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;">/ <?= number_format($planet['deuterium_max']) ?></span>
                            </div>
                        </div>
                        <div class="progress" style="height:3px; border-radius:2px; background:#f0eeeb; margin-top:4px;">
                            <div class="progress-bar bg-success" id="bar-deut" style="width:<?= $pctDeut ?>%;"></div>
                        </div>
                    </div>
                </div>

                <!-- 4. Farine de Riz (Komeko) -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card shadow-sm" style="background:#ffffff; border:1px solid #e7e5e4; border-radius:6px; border-left:3px solid #64748b !important; padding:0.35rem 0.65rem;" title="Farine de Riz (Raffinée en Meunerie)">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-1 text-truncate">
                                <span style="font-size:0.95rem;">🍚</span>
                                <strong style="font-size:0.78rem; color:#475569;">Farine</strong>
                            </div>
                            <div class="text-end" style="font-variant-numeric:tabular-nums; white-space:nowrap;">
                                <span class="fw-bold" style="font-size:0.82rem; color:#1c1917;"
                                      id="res-val-rice-flour"
                                      data-current="<?= $flourStock ?>"
                                      data-max="<?= $flourMax ?>">
                                    <?= number_format((int)$flourStock) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;">/ <?= number_format($flourMax) ?></span>
                            </div>
                        </div>
                        <div class="progress" style="height:3px; border-radius:2px; background:#f0eeeb; margin-top:4px;">
                            <div class="progress-bar bg-secondary" id="bar-rice-flour" style="width:<?= $pctFlour ?>%;"></div>
                        </div>
                    </div>
                </div>

                <!-- 5. Saké Féodal (Sakagura) -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card shadow-sm" style="background:#ffffff; border:1px solid #e7e5e4; border-radius:6px; border-left:3px solid #d97706 !important; padding:0.35rem 0.65rem;" title="Saké Impérial (Brassé en Meunerie / Sakagura)">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-1 text-truncate">
                                <span style="font-size:0.95rem;">🍶</span>
                                <strong style="font-size:0.78rem; color:#b45309;">Saké</strong>
                            </div>
                            <div class="text-end" style="font-variant-numeric:tabular-nums; white-space:nowrap;">
                                <span class="fw-bold" style="font-size:0.82rem; color:#1c1917;"
                                      id="res-val-sake"
                                      data-current="<?= $sakeStock ?>"
                                      data-max="<?= $sakeMax ?>">
                                    <?= number_format((int)$sakeStock) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;">/ <?= number_format($sakeMax) ?></span>
                            </div>
                        </div>
                        <div class="progress" style="height:3px; border-radius:2px; background:#f0eeeb; margin-top:4px;">
                            <div class="progress-bar bg-warning" id="bar-sake" style="width:<?= $pctSake ?>%;"></div>
                        </div>
                    </div>
                </div>

                <!-- 6. Sérénité Shinto -->
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card shadow-sm" style="background:#ffffff; border:1px solid #e7e5e4; border-radius:6px; border-left:3px solid <?= $eOk ? '#166534' : '#dc2626' ?> !important; padding:0.35rem 0.65rem;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-1 text-truncate">
                                <span style="font-size:0.95rem;">⛩️</span>
                                <strong style="font-size:0.78rem; color:<?= $eOk ? '#166534' : '#dc2626' ?>;">Sérénité</strong>
                                <span class="badge <?= $eOk ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' ?>" style="font-size:0.58rem; padding:1px 4px;">
                                    <?= $eOk ? 'OK' : 'Déficit' ?>
                                </span>
                            </div>
                            <div class="text-end" style="font-variant-numeric:tabular-nums; white-space:nowrap;">
                                <span class="fw-bold" style="font-size:0.82rem; color:<?= $eOk ? '#166534' : '#dc2626' ?>;">
                                    <?= ($eBalance >= 0 ? '+' : '') . number_format($eBalance) ?>
                                </span>
                                <span class="text-muted" style="font-size:0.62rem;">(<?= number_format($planet['energy_used']) ?>/<?= number_format($planet['energy_max']) ?>)</span>
                            </div>
                        </div>
                        <div class="progress" style="height:3px; border-radius:2px; background:#f0eeeb; margin-top:4px;">
                            <div class="progress-bar <?= $eOk ? 'bg-success' : 'bg-danger' ?>" style="width:<?= $pctEnergy ?>%;"></div>
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
        <?php endif; ?>

        <!-- CORPS DE PAGE -->
        <div class="page-body">
            <div class="container-xl <?= ($page === 'map' || $page === 'galaxy') ? 'container-fluid px-0' : '' ?>">


