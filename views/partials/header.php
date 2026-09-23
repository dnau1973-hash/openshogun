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
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();
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
    ['page' => 'ranking',   'match' => ['ranking'],           'icon' => '🏆', 'label' => 'Classement',         'title' => 'Tableau d\'honneur'],
    ['page' => 'reports',   'match' => ['reports'],           'icon' => '📜', 'label' => 'Chroniques',         'title' => 'Rapports de bataille'],
    ['page' => 'messages',  'match' => ['messages'],          'icon' => '✉️', 'label' => 'Missives',           'title' => 'Correspondance des clans',
     'badge' => $unreadMessagesCount > 0 ? $unreadMessagesCount : null],
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

        <!-- ── BARRE 1 : Ressources + Héros + Profil Daimyō ── -->
        <header class="navbar navbar-expand-md d-print-none"
                style="border-bottom:1px solid rgba(194,37,43,0.2);
                       background:linear-gradient(135deg,rgba(194,37,43,0.04) 0%,rgba(255,255,255,0.98) 100%);
                       padding:0; min-height:52px;">
            <div class="container-fluid px-3">

                <!-- Fief & coordonnées -->
                <div class="d-flex align-items-center gap-2 me-4">
                    <span style="font-size:1.25rem;">🏯</span>
                    <div>
                        <div style="font-weight:800; font-size:0.88rem; color:#1e293b; line-height:1.1;"><?= htmlspecialchars($planet['name']) ?></div>
                        <div style="font-size:0.7rem; color:#dc2626; font-family:monospace; font-weight:700;">[<?= $planet['coord_x'] ?>|<?= $planet['coord_y'] ?>]</div>
                    </div>
                </div>

                <!-- Barres de ressources (masquées sur mobile) -->
                <div class="d-none d-md-flex align-items-center gap-3 flex-fill" style="min-width:0;">
                    <?php
                    $resItems = [
                        ['id'=>'metal',   'icon'=>'🪵', 'label'=>'Bois',   'cur'=>$planet['metal'],     'max'=>$planet['metal_max'],     'prod'=>$planet['prod_rates']['metal'],     'color'=>'#92400e', 'bar'=>'bg-warning'],
                        ['id'=>'crystal', 'icon'=>'🪨', 'label'=>'Pierre', 'cur'=>$planet['crystal'],   'max'=>$planet['crystal_max'],   'prod'=>$planet['prod_rates']['crystal'],   'color'=>'#1e40af', 'bar'=>'bg-primary'],
                        ['id'=>'deut',    'icon'=>'🌾', 'label'=>'Riz',    'cur'=>$planet['deuterium'], 'max'=>$planet['deuterium_max'], 'prod'=>$planet['prod_rates']['deuterium'], 'color'=>'#166534', 'bar'=>'bg-success'],
                    ];
                    foreach ($resItems as $r):
                        $pct = min(100, ($r['cur'] / max(1, $r['max'])) * 100);
                    ?>
                    <div class="d-flex align-items-center gap-1"
                         style="min-width:110px; max-width:190px; flex:1;"
                         title="<?= $r['label'] ?> : <?= number_format((int)$r['cur']) ?> / <?= number_format($r['max']) ?> (+<?= number_format($r['prod']) ?>/h)">
                        <span style="font-size:0.9rem;"><?= $r['icon'] ?></span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:0.7rem; font-weight:700; color:<?= $r['color'] ?>; white-space:nowrap;">
                                <span id="res-val-<?= $r['id'] ?>"
                                      data-current="<?= $r['cur'] ?>"
                                      data-max="<?= $r['max'] ?>"
                                      data-prod="<?= $r['prod'] ?>"><?= number_format((int)$r['cur']) ?></span>
                                <span style="opacity:0.55; font-weight:400;">/ <?= number_format($r['max']) ?></span>
                            </div>
                            <div class="progress" style="height:4px; border-radius:2px; margin-top:2px;">
                                <div class="progress-bar <?= $r['bar'] ?>" id="bar-<?= $r['id'] ?>" style="width:<?= $pct ?>%;"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Sérénité -->
                    <?php $eBalance = $planet['energy_max'] - $planet['energy_used']; $eOk = ($eBalance >= 0); ?>
                    <div class="d-flex align-items-center gap-1"
                         title="Sérénité Shinto : <?= $planet['energy_used'] ?> / <?= $planet['energy_max'] ?>">
                        <span style="font-size:0.9rem;">⛩️</span>
                        <span style="font-size:0.8rem; font-weight:800; color:<?= $eOk ? '#166534' : '#dc2626' ?>;"><?= $eBalance ?></span>
                    </div>
                </div>

                <!-- Héros + Profil -->
                <div class="d-flex align-items-center gap-2 ms-3">
                    <?php
                    $hHp = $heroHeader ? round((float)$heroHeader['health']) : 100;
                    $hHpCol = ($hHp >= 60) ? '#16a34a' : (($hHp >= 25) ? '#d97706' : '#dc2626');
                    $hLvl = $heroHeader ? (int)$heroHeader['level'] : 1;
                    $hasPoints = ($heroHeader && (int)$heroHeader['unassigned_points'] > 0);
                    ?>
                    <a href="?page=hero" class="position-relative"
                       title="Samouraï Héros Niv.<?= $hLvl ?> — Vitalité <?= $hHp ?>%">
                        <img src="/public/assets/hero_samurai.jpg" alt="Héros"
                             style="width:34px; height:34px; border-radius:50%; border:2px solid <?= $hHpCol ?>; object-fit:cover;">
                        <span class="badge bg-dark text-white position-absolute"
                              style="bottom:-3px; right:-3px; font-size:0.58rem; padding:1px 3px; border-radius:3px;"><?= $hLvl ?></span>
                        <?php if ($hasPoints): ?>
                            <span class="badge bg-danger position-absolute"
                                  style="top:-3px; right:-3px; font-size:0.58rem; padding:1px 4px; border-radius:50%;">+</span>
                        <?php endif; ?>
                    </a>

                    <?php if ($isUserProtected): ?>
                        <span class="badge bg-success-lt text-success d-none d-sm-inline-flex align-items-center gap-1"
                              style="font-size:0.72rem; padding:0.25rem 0.55rem; cursor:help; border: 1px solid rgba(22, 163, 74, 0.35); border-radius:6px;"
                              title="🔰 Immunité Féodale des Nouveaux Joueurs active jusqu'au <?= htmlspecialchars($userProtection['until_formatted']) ?> (aucun assaut ni espionnage possible sur vos fiefs)">
                            <span>🔰</span>
                            <span>Immunité <?= htmlspecialchars($userProtection['formatted']) ?></span>
                        </span>
                    <?php endif; ?>

                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center gap-2 text-decoration-none"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <span style="width:8px; height:8px; border-radius:50%; display:inline-block;
                                         background:<?= $factionInfo['color'] ?? '#dc2626' ?>;"></span>
                            <span style="font-weight:700; font-size:0.85rem; color:#1e293b;"><?= htmlspecialchars($user['username']) ?></span>
                            <?php if ($isUserProtected): ?>
                                <span class="d-sm-none" title="Immunité active">🔰</span>
                            <?php endif; ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
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
                            <a href="javascript:void(0)" class="dropdown-item"
                               onclick="openPlayerProfileModal(<?= (int)$user['id'] ?>)">👤 Ma Fiche Daimyō</a>
                            <a href="?page=alliance" class="dropdown-item">🎌 Mon Alliance</a>
                            <a href="javascript:void(0)" class="dropdown-item"
                               onclick="openEditMottoModal()">📜 Ma Devise</a>
                            <a href="?page=support" class="dropdown-item">📮 Support</a>
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
        </header>

        <!-- ── BARRE 2 : Navigation principale ── -->
        <nav class="navbar navbar-expand-md d-print-none"
             style="background:linear-gradient(135deg,#1a0a00 0%,#2d1200 60%,#1a0a00 100%);
                    border-bottom:2px solid rgba(194,37,43,0.5);
                    min-height:44px; padding:0;">
            <div class="container-fluid px-3">

                <!-- Toggler mobile -->
                <button class="navbar-toggler border-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#mainNavBar"
                        style="color:#f59e0b;">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNavBar">
                    <ul class="navbar-nav me-auto">
                        <?php foreach ($navItems as $nav):
                            $isActive = in_array($page, $nav['match']);
                        ?>
                        <li class="nav-item">
                            <a class="nav-link px-3 py-2 d-flex align-items-center gap-1 position-relative
                                       <?= $isActive ? 'active fw-bold' : '' ?>"
                               href="?page=<?= $nav['page'] ?>"
                               title="<?= htmlspecialchars($nav['title']) ?>"
                               style="color:<?= $isActive ? '#fbbf24' : '#e2d9c8' ?>;
                                      font-size:0.82rem; white-space:nowrap;
                                      border-bottom:<?= $isActive ? '2px solid #dc2626' : '2px solid transparent' ?>;
                                      border-radius:0;">
                                <span><?= $nav['icon'] ?></span>
                                <span><?= $nav['label'] ?></span>
                                <?php if (!empty($nav['badge'])): ?>
                                    <span class="badge bg-danger ms-1" style="font-size:0.6rem;"><?= $nav['badge'] ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>

                        <?php if ($questSummary): ?>
                        <li class="nav-item">
                            <a class="nav-link px-3 py-2 d-flex align-items-center gap-1 position-relative"
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

                    <!-- Outils à droite -->
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link px-2 py-2" href="?page=docs"
                               title="Codex du Sengoku" style="color:#e2d9c8; font-size:0.82rem;">📖</a>
                        </li>
                        <?php if ($auth->isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link px-2 py-2" href="?page=admin"
                               title="Administration" style="color:#fbbf24; font-size:0.82rem;">⚙️ Admin</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>

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


