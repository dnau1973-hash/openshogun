<?php
/**
 * Vue Studio Dev Team & Métiers du Jeu Vidéo (OpenShogun)
 * Espace collaboratif de développement, gestion des métiers RBAC, Sandbox QA et Forge XP
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/DevTeamEngine.php';
require_once __DIR__ . '/../core/Database.php';

$auth = new Auth();
if (!Auth::check()) {
    header('Location: /');
    exit;
}

$currentUser = $auth->getCurrentUser();
$userId = (int)$currentUser['id'];
$devEngine = new DevTeamEngine();

// Contrôle d'accès : Seuls les membres de la Dev Team ou Admins peuvent voir cette page
if (!$devEngine->isDevTeamMember($userId)) {
    ?>
    <div class="container-xl py-5 text-center">
        <div class="empty">
            <div class="empty-icon text-danger" style="font-size: 3rem;">🔒</div>
            <p class="empty-title">Accès Réservé au Studio de Développement</p>
            <p class="empty-subtitle text-muted">
                Cet espace est strictement réservé aux membres de la <strong>Dev Team</strong> d'OpenShogun.<br>
                Si vous participez au développement du projet, demandez à un Producteur de vous assigner un métier.
            </p>
            <div class="empty-action">
                <a href="/?page=resources" class="btn btn-primary">Retour au Terroir Féodal</a>
            </div>
        </div>
    </div>
    <?php
    return;
}

$db = Database::getConnection();
$myProfile = $devEngine->getDevProfile($userId);
$myRoles = $myProfile['roles'] ?? [];
$myPerms = $myProfile['permissions'] ?? [];
$levelInfo = $myProfile['forge_level'];
$allMembers = $devEngine->getAllDevTeamMembers();
$forgeHistory = $devEngine->getForgeHistory(25);

// Permissions courantes
$canManageTeam    = $devEngine->hasPermission($userId, 'team.manage') || $auth->isAdmin();
$canUseSandbox    = $devEngine->hasPermission($userId, 'debug.sandbox') || $auth->isAdmin();
$canTriggerCron   = $devEngine->hasPermission($userId, 'cron.trigger') || $auth->isAdmin();
$canMonitorSystem = $devEngine->hasPermission($userId, 'system.monitoring') || $auth->isAdmin();
$canManageSprints   = $devEngine->hasPermission($userId, 'sprints.manage') || $auth->isAdmin();
$canReviewQA        = $devEngine->hasPermission($userId, 'bugs.manage') || $devEngine->hasPermission($userId, 'debug.sandbox') || $auth->isAdmin();
$canManageCommunity = $devEngine->hasPermission($userId, 'community.mailing') 
    || in_array('community_manager', array_column($myRoles, 'id'), true) 
    || $auth->isAdmin();

require_once __DIR__ . '/../core/FeatureRegistry.php';
require_once __DIR__ . '/../core/QASyntaxChecker.php';
require_once __DIR__ . '/../core/MailingListEngine.php';

$qaFeatures         = FeatureRegistry::getAllFeatures();
$qaStats            = FeatureRegistry::getStatistics();
$syntaxStatus       = QASyntaxChecker::getLastCheckResult();
$mailingEngine      = new MailingListEngine($db);
$mailingStats       = $mailingEngine->getStatistics();
$initialSubscribers = $canManageCommunity ? $mailingEngine->getSubscribers([], 20, 0) : ['subscribers' => [], 'total' => 0];
$recentCampaigns    = $canManageCommunity ? $mailingEngine->getCampaignHistory(10) : [];

// Liste de tous les utilisateurs pour le formulaire d'attribution
$allUsersList = [];
if ($canManageTeam) {
    $stmtUsers = $db->query("SELECT id, username FROM users ORDER BY username ASC");
    $allUsersList = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
}

// Statistiques de la Dev Team
$roleDistribution = [];
foreach (DevTeamEngine::ROLES as $rKey => $rVal) {
    $roleDistribution[$rKey] = [];
}
foreach ($allMembers as $m) {
    foreach ($m['roles'] as $r) {
        if (isset($roleDistribution[$r['id']])) {
            $roleDistribution[$r['id']][] = $m['username'];
        }
    }
}

$membersRolesMap = [];
foreach ($allMembers as $m) {
    $membersRolesMap[$m['user_id']] = array_map(fn($r) => $r['id'], $m['roles']);
}

// Infos système pour l'onglet Monitoring
$totalPlayersCount = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPlanetsCount = (int)$db->query("SELECT COUNT(*) FROM planets")->fetchColumn();
$activeQueuesCount = (int)$db->query("SELECT COUNT(*) FROM construction_queue WHERE finishes_at > " . time())->fetchColumn();

// ── Matrice des onglets autorisés pour l'utilisateur connecté ──────────────────
$allowedTabs = $devEngine->getAllowedTabs($userId, $auth->isAdmin());
if (empty($allowedTabs)) {
    http_response_code(403);
    ?>
    <div class="container-xl py-5 text-center">
        <div class="empty">
            <div class="empty-icon text-danger" style="font-size: 3rem;">🔒</div>
            <p class="empty-title">Accès Interdit au Studio</p>
            <p class="empty-subtitle text-muted">Aucun onglet du studio de développement n'est habilité pour votre profil.</p>
        </div>
    </div>
    <?php
    return;
}

// ── Données conditionnelles pour les modules Game Elevate Designer ────────────
$canAccessGameSpeeds     = in_array('game_speeds', $allowedTabs, true);
$canAccessWorldExpansion = in_array('world_expansion', $allowedTabs, true);
$canAccessOasesEcosystem = in_array('oases_ecosystem', $allowedTabs, true);

$gameSettings = [];
$mapTileStats = [];
$mapTileCategories = [];
$oasisStats = ['total_oases' => 0, 'captured_oases' => 0, 'wild_oases' => 0, 'total_wild_animals' => 0];
$allOases = [];

if ($canAccessGameSpeeds || $canAccessWorldExpansion || $canAccessOasesEcosystem) {
    require_once __DIR__ . '/../core/GameConfig.php';
    require_once __DIR__ . '/../core/WorldGenerator.php';
    require_once __DIR__ . '/../core/OasisEngine.php';
    $gameSettings = GameConfig::load();
}

if ($canAccessWorldExpansion) {
    $mapRadius = 12;
    $totalTilesCount = ($mapRadius * 2 + 1) * ($mapRadius * 2 + 1);

    $stmtCastles = $db->query("SELECT coord_x, coord_y FROM authentic_castles WHERE is_spawned = 1");
    $spawnedCastleCoords = [];
    while ($row = $stmtCastles->fetch(PDO::FETCH_ASSOC)) {
        $spawnedCastleCoords[$row['coord_x'] . ':' . $row['coord_y']] = true;
    }

    $allPlanetsRows = $db->query("SELECT coord_x, coord_y, user_id FROM planets")->fetchAll(PDO::FETCH_ASSOC);
    $villageCoords = [];
    $freeLandCoords = [];
    foreach ($allPlanetsRows as $p) {
        $k = ((int)$p['coord_x']) . ':' . ((int)$p['coord_y']);
        if (isset($spawnedCastleCoords[$k])) {
            continue;
        }
        if (!empty($p['user_id'])) {
            $villageCoords[$k] = true;
        } else {
            $freeLandCoords[$k] = true;
        }
    }

    $oasisCoords = OasisEngine::getOasesCoordinatesMap();

    $mapTileStats = [
        'radius' => $mapRadius,
        'total_tiles' => $totalTilesCount,
        'villages' => count($villageCoords),
        'free_lands' => count($freeLandCoords),
        'castles' => count($spawnedCastleCoords),
        'oases' => count($oasisCoords),
        'plains' => 0,
        'forest' => 0,
        'mountain' => 0,
        'hills' => 0,
        'lake' => 0,
    ];

    for ($y = -$mapRadius; $y <= $mapRadius; $y++) {
        for ($x = -$mapRadius; $x <= $mapRadius; $x++) {
            $k = $x . ':' . $y;
            if (isset($villageCoords[$k]) || isset($freeLandCoords[$k]) || isset($spawnedCastleCoords[$k]) || isset($oasisCoords[$k])) {
                continue;
            }
            $seed = abs((int)(($x * 73856093) ^ ($y * 19349663))) % 1000;
            if ($seed < 600) {
                $mapTileStats['plains']++;
            } elseif ($seed < 750) {
                $mapTileStats['forest']++;
            } elseif ($seed < 870) {
                $mapTileStats['mountain']++;
            } elseif ($seed < 950) {
                $mapTileStats['hills']++;
            } else {
                $mapTileStats['lake']++;
            }
        }
    }

    $mapTileCategories = [
        'villages' => [
            'name' => 'Fiefs Occupés',
            'sub' => 'Daimyōs & PNJ',
            'count' => $mapTileStats['villages'],
            'icon' => '🏯',
            'badge_bg' => 'bg-purple-lt text-purple',
            'bar_color' => 'bg-purple',
            'img' => '/public/assets/map/tile_village.jpg?v=2',
            'desc' => 'Capitales et fiefs colonisés par les joueurs et clans IA.'
        ],
        'free_lands' => [
            'name' => 'Terres Libres',
            'sub' => 'Emplacements arpentés',
            'count' => $mapTileStats['free_lands'],
            'icon' => '🏳️',
            'badge_bg' => 'bg-secondary-lt text-secondary',
            'bar_color' => 'bg-secondary',
            'img' => '/public/assets/map/tile_plains.jpg?v=2',
            'desc' => 'Emplacements arpentés disponibles pour fondation de colonie.'
        ],
        'castles' => [
            'name' => 'Donjons Sacrés',
            'sub' => '現存十二天守',
            'count' => $mapTileStats['castles'],
            'icon' => '👑',
            'badge_bg' => 'bg-warning-lt text-warning',
            'bar_color' => 'bg-warning',
            'img' => '/public/assets/map/tile_authentic_castle.jpg?v=2',
            'desc' => 'Les 12 forteresses impériales historiques du Japon féodal.'
        ],
        'oases' => [
            'name' => 'Oasis Naturelles',
            'sub' => 'Faune & Bonus',
            'count' => $mapTileStats['oases'],
            'icon' => '🌿',
            'badge_bg' => 'bg-teal-lt text-teal',
            'bar_color' => 'bg-teal',
            'img' => '/public/assets/map/tile_lake.jpg?v=2',
            'desc' => 'Havres de faune sauvage procurant des bonus de production.'
        ],
        'plains' => [
            'name' => 'Plaines Fertiles',
            'sub' => 'Prairies & Terres',
            'count' => $mapTileStats['plains'],
            'icon' => '🌾',
            'badge_bg' => 'bg-lime-lt text-lime',
            'bar_color' => 'bg-lime',
            'img' => '/public/assets/map/tile_plains.jpg?v=2',
            'desc' => 'Terres arables verdoyantes et plaines propices à l\'agriculture.'
        ],
        'forest' => [
            'name' => 'Forêt de Cèdres',
            'sub' => 'Sugi centenaires',
            'count' => $mapTileStats['forest'],
            'icon' => '🌲',
            'badge_bg' => 'bg-green-lt text-green',
            'bar_color' => 'bg-green',
            'img' => '/public/assets/map/tile_forest.jpg?v=2',
            'desc' => 'Massifs sylvestres denses pourvoyeurs de bois de construction.'
        ],
        'mountain' => [
            'name' => 'Pics & Montagnes',
            'sub' => 'Crêtes rocheuses',
            'count' => $mapTileStats['mountain'],
            'icon' => '⛰️',
            'badge_bg' => 'bg-dark-lt text-dark',
            'bar_color' => 'bg-dark',
            'img' => '/public/assets/map/tile_mountain.jpg?v=2',
            'desc' => 'Reliefs escarpés et carrières granitiques des monts du Japon.'
        ],
        'hills' => [
            'name' => 'Collines & Coteaux',
            'sub' => 'Cultures en terrasse',
            'count' => $mapTileStats['hills'],
            'icon' => '🏞️',
            'badge_bg' => 'bg-orange-lt text-orange',
            'bar_color' => 'bg-orange',
            'img' => '/public/assets/map/tile_hills.jpg?v=2',
            'desc' => 'Versants vallonnés et vergers suspendus de l\'archipel.'
        ],
        'lake' => [
            'name' => 'Lacs & Eaux Calmes',
            'sub' => 'Rivières & Bassins',
            'count' => $mapTileStats['lake'],
            'icon' => '🌊',
            'badge_bg' => 'bg-cyan-lt text-cyan',
            'bar_color' => 'bg-cyan',
            'img' => '/public/assets/map/tile_lake.jpg?v=2',
            'desc' => 'Étendues d\'eau douce, étangs sacrés et méandres fluviaux.'
        ],
    ];
}

if ($canAccessOasesEcosystem) {
    $oasisStats = OasisEngine::getStats();
    $allOases = OasisEngine::getAllOases();
}

$requestedTab = trim((string)($_GET['tab'] ?? ''));
if ($requestedTab !== '' && in_array($requestedTab, $allowedTabs, true)) {
    $activeTab = $requestedTab;
} else {
    $activeTab = $allowedTabs[0] ?? 'roster';
}
$isForbiddenRedirect = !empty($_GET['forbidden']);
?>

<div class="container-xl py-4" style="max-width: 1200px;">

    <!-- ── HÉROS BANNER : STUDIO DEV & PROFIL CRÉATEUR ── -->
    <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #1e1e2d 0%, #2a223f 100%); color: #fff; border-radius: 12px; overflow: hidden;">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <div class="avatar avatar-xl rounded-circle shadow" style="background: linear-gradient(135deg, #8b5cf6, #ec4899); font-size: 2rem; border: 3px solid rgba(255,255,255,0.2);">
                        🛠️
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <span class="badge bg-purple-lt text-white px-2 py-1" style="background: rgba(139, 92, 246, 0.3) !important;">
                            🎮 OpenShogun Studio Lab
                        </span>
                        <span class="badge bg-warning text-dark fw-bold">
                            Niveau <?= $levelInfo['level'] ?> &bull; <?= htmlspecialchars($levelInfo['title']) ?>
                        </span>
                        <?php if ($auth->isAdmin()): ?>
                            <span class="badge bg-danger">👑 Administrateur Suprême</span>
                        <?php endif; ?>
                    </div>
                    <h1 class="h2 mb-1 text-white fw-bold d-flex align-items-center gap-2">
                        <span>Studio de Développement</span>
                        <span class="text-white-50 fs-5 font-monospace fw-normal">&bull; <?= htmlspecialchars($currentUser['username']) ?></span>
                    </h1>
                    <div class="d-flex align-items-center gap-2 flex-wrap text-white-50 small">
                        <span>Métier(s) actuel(s) :</span>
                        <?php if (empty($myRoles)): ?>
                            <span class="badge bg-secondary">Observateur</span>
                        <?php else: ?>
                            <?php foreach ($myRoles as $r): ?>
                                <span class="badge bg-dark text-light border border-secondary" title="<?= htmlspecialchars($r['honor_title'] ?? '') ?>">
                                    <?= $r['icon'] ?? '🛠️' ?> <?= htmlspecialchars($r['title']) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Jauge de Forge XP -->
                <div class="col-12 col-md-4">
                    <div class="p-3 rounded" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);">
                        <div class="d-flex justify-content-between align-items-center mb-1 small text-white-50">
                            <span>🔨 Forge XP : <strong><?= number_format($levelInfo['xp']) ?> XP</strong></span>
                            <?php if ($levelInfo['next_min_xp']): ?>
                                <span>Suivant : <?= number_format($levelInfo['next_min_xp']) ?> XP</span>
                            <?php else: ?>
                                <span class="text-warning fw-bold">Niveau Max !</span>
                            <?php endif; ?>
                        </div>
                        <div class="progress progress-sm" style="height: 8px; background: rgba(255,255,255,0.15);">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $levelInfo['progress_pct'] ?>%;" aria-valuenow="<?= $levelInfo['progress_pct'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="mt-2 text-end text-white-50" style="font-size: 0.75rem;">
                            Prochain palier dans <strong><?= $levelInfo['next_min_xp'] ? ($levelInfo['next_min_xp'] - $levelInfo['xp']) : 0 ?> XP</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── ALERTE FLASH FEEDBACK VIA JS ── -->
    <div id="dev-alert" class="alert d-none mb-3 shadow-sm alert-dismissible" role="alert">
        <div id="dev-alert-content"></div>
        <button type="button" class="btn-close" onclick="document.getElementById('dev-alert').classList.add('d-none');"></button>
    </div>

    <!-- ── ALERTE REDIRECTION FORCÉE (ACCÈS INTERDIT À L'ONGLET REQUIS) ── -->
    <?php if ($isForbiddenRedirect): ?>
    <div class="alert alert-warning alert-dismissible shadow-sm mb-3" role="alert">
        <div class="d-flex align-items-center gap-2">
            <span class="fs-2">🔒</span>
            <div>
                <h4 class="alert-title mb-1">Accès Restreint &bull; Métier Requis</h4>
                <div class="text-muted small">Vous avez été redirigé(e) vers un onglet autorisé car votre profil ne possède pas le métier ou les habilitations nécessaires pour accéder à l'onglet demandé.</div>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
    <?php endif; ?>

    <!-- ── ONGLETS DE NAVIGATION DU STUDIO (CONDITIONNEMENT STRICT PAR MÉTIER) ── -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header border-bottom">
            <ul class="nav nav-tabs card-header-tabs" id="dev-team-tabs" role="tablist">
                <?php if (in_array('roster', $allowedTabs, true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'roster' ? 'active' : '' ?>" id="tab-roster-btn" data-bs-toggle="tab" href="#tab-roster" role="tab" onclick="switchDevTab('roster')">
                        <span>👥</span> Studio Roster &amp; Métiers
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array('qa', $allowedTabs, true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'qa' ? 'active' : '' ?>" id="tab-qa-btn" data-bs-toggle="tab" href="#tab-qa" role="tab" onclick="switchDevTab('qa')">
                        <span>📋</span> QA &amp; Recette
                        <?php if ($qaStats['pending'] > 0): ?>
                            <span class="badge bg-danger text-white ms-1" id="nav-qa-pending-badge"><?= $qaStats['pending'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array('mailing', $allowedTabs, true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'mailing' ? 'active' : '' ?>" id="tab-mailing-btn" data-bs-toggle="tab" href="#tab-mailing" role="tab" onclick="switchDevTab('mailing')">
                        <span>📢</span> Mailing List
                        <span class="badge bg-teal-lt text-teal ms-1" id="nav-mailing-count-badge"><?= $mailingStats['newsletter_subscribers'] ?></span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array('sandbox', $allowedTabs, true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'sandbox' ? 'active' : '' ?>" id="tab-sandbox-btn" data-bs-toggle="tab" href="#tab-sandbox" role="tab" onclick="switchDevTab('sandbox')">
                        <span>🧪</span> Atelier QA &amp; Sandbox
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array('system', $allowedTabs, true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'system' ? 'active' : '' ?>" id="tab-system-btn" data-bs-toggle="tab" href="#tab-system" role="tab" onclick="switchDevTab('system')">
                        <span>⚙️</span> Live Ops &amp; Serveur
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array('lore', $allowedTabs, true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'lore' ? 'active' : '' ?>" id="tab-lore-btn" data-bs-toggle="tab" href="#tab-lore" role="tab" onclick="switchDevTab('lore')">
                        <span>📜</span> Univers &amp; Lore
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array('forge', $allowedTabs, true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'forge' ? 'active' : '' ?>" id="tab-forge-btn" data-bs-toggle="tab" href="#tab-forge" role="tab" onclick="switchDevTab('forge')">
                        <span>🔨</span> Journal de Forge &amp; Trophées
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array('game_speeds', $allowedTabs, true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'game_speeds' ? 'active' : '' ?>" id="tab-game_speeds-btn" data-bs-toggle="tab" href="#tab-game_speeds" role="tab" onclick="switchDevTab('game_speeds')">
                        <span>⚡</span> Vitesses &amp; Équilibrage
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array('world_expansion', $allowedTabs, true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'world_expansion' ? 'active' : '' ?>" id="tab-world_expansion-btn" data-bs-toggle="tab" href="#tab-world_expansion" role="tab" onclick="switchDevTab('world_expansion')">
                        <span>🗾</span> Arpentage &amp; Provinces
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array('oases_ecosystem', $allowedTabs, true)): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'oases_ecosystem' ? 'active' : '' ?>" id="tab-oases_ecosystem-btn" data-bs-toggle="tab" href="#tab-oases_ecosystem" role="tab" onclick="switchDevTab('oases_ecosystem')">
                        <span>🌿</span> Écosystème des Oasis
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content">

                <!-- ═══════════════════════════════════════════════════════════════════════
                     ONGLET 1 : ROSTER & LES MÉTIERS DU JEU VIDÉO
                     ═══════════════════════════════════════════════════════════════════════ -->
                <?php if (in_array('roster', $allowedTabs, true)): ?>
                <div class="tab-pane fade <?= $activeTab === 'roster' ? 'show active' : '' ?>" id="tab-roster" role="tabpanel">
                    
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                        <div>
                            <h3 class="card-title mb-1">Les Métiers de l'Équipe de Développement (<?= count(DevTeamEngine::ROLES) ?> Métiers)</h3>
                            <p class="text-muted small mb-0">Chaque métier confère des habilitations concrètes en jeu et dans les coulisses de la production.</p>
                        </div>
                        <?php if ($canManageTeam): ?>
                        <div>
                            <button type="button" class="btn btn-purple d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modal-assign-role">
                                <span>➕</span> Assigner un Métier
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Grille des 8 Rôles Métiers -->
                    <div class="row row-cards mb-4">
                        <?php foreach (DevTeamEngine::ROLES as $rKey => $role): ?>
                            <?php $assignedUsers = $roleDistribution[$rKey] ?? []; ?>
                            <div class="col-md-6 col-xl-3">
                                <div class="card h-100 border shadow-none" style="background: var(--bg-surface, #fff); border-radius: 8px;">
                                    <div class="card-body p-3 d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="fs-1"><?= $role['icon'] ?></span>
                                            <span class="badge <?= $role['badge_color'] ?>"><?= htmlspecialchars($role['category']) ?></span>
                                        </div>
                                        <h4 class="card-title mb-1 text-truncate" title="<?= htmlspecialchars($role['title']) ?>">
                                            <?= htmlspecialchars($role['title']) ?>
                                        </h4>
                                        <div class="text-muted small fst-italic mb-2" style="font-size: 0.75rem;">
                                            « <?= htmlspecialchars($role['honor_title']) ?> »
                                        </div>
                                        <p class="text-muted small flex-grow-1 mb-3" style="font-size: 0.8rem; line-height: 1.4;">
                                            <?= htmlspecialchars($role['description']) ?>
                                        </p>

                                        <!-- Membres actifs détenant le rôle -->
                                        <div class="border-top pt-2 mt-auto">
                                            <div class="text-muted small mb-1" style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">Membres assignés :</div>
                                            <div class="d-flex flex-wrap gap-1" id="role-dist-list-<?= $rKey ?>">
                                                <?php if (empty($assignedUsers)): ?>
                                                    <span class="text-muted fst-italic small no-member-tag" style="font-size: 0.75rem;">Aucun pour le moment</span>
                                                <?php else: ?>
                                                    <?php foreach ($assignedUsers as $uName): ?>
                                                        <span class="badge bg-light text-dark border dist-user-tag" data-username="<?= htmlspecialchars($uName) ?>" style="font-size: 0.75rem;">👤 <?= htmlspecialchars($uName) ?></span>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Tableau de la Dev Team -->
                    <div class="card border">
                        <div class="card-header bg-light">
                            <h4 class="card-title mb-0">Membres Actifs du Studio (<?= count($allMembers) ?>)</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-striped" id="dev-roster-table">
                                <thead>
                                    <tr>
                                        <th>Développeur</th>
                                        <th>Faction</th>
                                        <th>Métiers Assignés</th>
                                        <th>Rang &amp; Forge XP</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allMembers as $member): ?>
                                        <tr id="member-row-<?= $member['user_id'] ?>" data-user-id="<?= $member['user_id'] ?>">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="avatar avatar-sm rounded-circle bg-purple-lt fw-bold">
                                                        <?= strtoupper(substr($member['username'], 0, 1)) ?>
                                                    </span>
                                                    <div>
                                                        <strong class="text-reset member-name"><?= htmlspecialchars($member['username']) ?></strong>
                                                        <?php if ($member['is_admin']): ?>
                                                            <span class="badge bg-danger-lt ms-1" style="font-size: 0.65rem;">Admin</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary-lt text-capitalize"><?= htmlspecialchars($member['faction']) ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap align-items-center gap-1 roles-container" id="user-roles-<?= $member['user_id'] ?>">
                                                    <?php if (empty($member['roles'])): ?>
                                                        <span class="text-muted small fst-italic no-roles-placeholder">Aucun métier</span>
                                                    <?php else: ?>
                                                        <?php foreach ($member['roles'] as $r): ?>
                                                            <span class="badge <?= DevTeamEngine::ROLES[$r['id']]['badge_color'] ?? 'bg-secondary' ?> d-inline-flex align-items-center gap-1 dev-role-badge shadow-none"
                                                                  id="badge-role-<?= $member['user_id'] ?>-<?= $r['id'] ?>"
                                                                  data-user-id="<?= $member['user_id'] ?>"
                                                                  data-role-id="<?= $r['id'] ?>"
                                                                  title="<?= htmlspecialchars($r['honor_title'] ?? '') ?>">
                                                                <span><?= $r['icon'] ?? '🛠️' ?> <?= htmlspecialchars($r['title']) ?></span>
                                                                <?php if ($canManageTeam): ?>
                                                                    <button type="button"
                                                                            class="btn-close btn-close-white ms-1 dev-role-close-btn"
                                                                            style="font-size: 0.55rem; width: 0.7em; height: 0.7em; opacity: 0.85; cursor: pointer;"
                                                                            onclick="confirmRemoveDevRole(<?= $member['user_id'] ?>, '<?= $r['id'] ?>', '<?= htmlspecialchars(addslashes($member['username'])) ?>', '<?= htmlspecialchars(addslashes($r['title'])) ?>')"
                                                                            title="Retirer ce métier">
                                                                    </button>
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge <?= $member['forge_level']['badge'] ?>">
                                                        Niv. <?= $member['forge_level']['level'] ?> &bull; <?= htmlspecialchars($member['forge_level']['title']) ?>
                                                    </span>
                                                    <span class="text-muted small font-monospace"><?= number_format($member['forge_level']['xp']) ?> XP</span>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-list justify-content-end">
                                                    <?php if ($canManageTeam): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-purple d-inline-flex align-items-center gap-1"
                                                                onclick="openAssignRoleModal(<?= $member['user_id'] ?>, '<?= htmlspecialchars(addslashes($member['username'])) ?>')"
                                                                title="Attribuer des métiers à ce membre">
                                                            <span>➕</span> <span class="d-none d-md-inline">Métier</span>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($canManageSprints): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="openAwardXpModal(<?= $member['user_id'] ?>, '<?= htmlspecialchars(addslashes($member['username'])) ?>')">
                                                            <span>⭐</span> Récompenser XP
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
                <?php endif; ?>

                <!-- ═══════════════════════════════════════════════════════════════════════
                     ONGLET 2 : QA & RECETTE (FONCTIONNALITÉS & TESTS DE SYNTAXE)
                     ═══════════════════════════════════════════════════════════════════════ -->
                <?php if (in_array('qa', $allowedTabs, true)): ?>
                <div class="tab-pane fade <?= $activeTab === 'qa' ? 'show active' : '' ?>" id="tab-qa" role="tabpanel">

                    <!-- En-tête QA & Action Rapide -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                        <div>
                            <h3 class="card-title d-flex align-items-center gap-2 mb-1">
                                <span>📋</span> Registre de Recette QA &amp; Contrôle Qualité
                            </h3>
                            <p class="text-muted small mb-0">
                                Suivi des fonctionnalités consignées dans <code>fonctionnalités.md</code>, validation manuelle par l'équipe QA et contrôle automatisé de la syntaxe PHP (<code>php -l</code>).
                            </p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-teal d-flex align-items-center gap-2 shadow-sm" id="btn-run-syntax" onclick="handleRunSyntaxCheck(this)">
                                <span>⚡</span> <span id="btn-run-syntax-text">Lancer le Contrôle de Syntaxe</span>
                            </button>
                        </div>
                    </div>

                    <!-- ── INDICATEURS CLÉS QA & ÉTAT TECHNIQUE ── -->
                    <div class="row row-cards mb-4">
                        <!-- 1. Feu Vert Technique (Syntaxe Automatisée) -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none" id="card-syntax-status">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">Contrôle Syntaxe</span>
                                        <span class="badge <?= $syntaxStatus['is_clean'] ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' ?>" id="badge-syntax-status">
                                            <?= $syntaxStatus['is_clean'] ? '✔ 100% VALIDE' : '✗ ERREURS DÉTECTÉES' ?>
                                        </span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace d-flex align-items-center gap-2" id="text-syntax-summary">
                                        <span id="icon-syntax-clean"><?= $syntaxStatus['is_clean'] ? '🟢' : '🔴' ?></span>
                                        <span id="text-syntax-passed"><?= $syntaxStatus['passed_count'] ?></span>
                                        <span class="text-muted fs-5 fw-normal">/ <span id="text-syntax-total"><?= $syntaxStatus['total_files'] ?></span> fichiers</span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Dernier scan : <span id="val-syntax-date"><?= htmlspecialchars($syntaxStatus['checked_at'] ?? 'Jamais') ?></span> (<span id="val-syntax-dur"><?= $syntaxStatus['duration_ms'] ?? 0 ?></span> ms)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Fonctionnalités À Tester -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">À Tester</span>
                                        <span class="badge bg-warning-lt text-warning">En attente QA</span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace text-warning d-flex align-items-center gap-2">
                                        <span>⏳</span> <span id="qa-stat-pending"><?= $qaStats['pending'] ?></span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Nouveautés en attente de recette manuelle
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Fonctionnalités Validées -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">Validées</span>
                                        <span class="badge bg-success-lt text-success">Recette OK</span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace text-success d-flex align-items-center gap-2">
                                        <span>✔</span> <span id="qa-stat-validated"><?= $qaStats['validated'] ?></span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Fonctionnalités prêtes pour la production
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Fonctionnalités Rejetées -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">Rejetées</span>
                                        <span class="badge bg-danger-lt text-danger">Failles / Bugs</span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace text-danger d-flex align-items-center gap-2">
                                        <span>✗</span> <span id="qa-stat-rejected"><?= $qaStats['rejected'] ?></span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Nécessite des correctifs du développeur
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rapport d'erreur syntaxe si erreurs détectées -->
                    <div id="qa-syntax-errors-box" class="alert alert-danger <?= empty($syntaxStatus['errors']) ? 'd-none' : '' ?> mb-4 shadow-sm" role="alert">
                        <h4 class="alert-title d-flex align-items-center gap-2">
                            <span>🚨</span> Erreurs de syntaxe PHP détectées lors du scan :
                        </h4>
                        <ul class="mb-0 small font-monospace" id="qa-syntax-errors-list">
                            <?php foreach (($syntaxStatus['errors'] ?? []) as $err): ?>
                                <li><strong><?= htmlspecialchars($err['file']) ?> :</strong> <?= htmlspecialchars($err['message']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- ── TABLEAU DU REGISTRE DE RECETTE (FONCTIONNALITÉS.MD) ── -->
                    <div class="card border">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title mb-0">Registre des Fonctionnalités &amp; Historique de Recette</h4>
                                <div class="text-muted small mt-1">Source synchronisée : <code>fonctionnalités.md</code></div>
                            </div>
                            <span class="badge bg-purple-lt" id="qa-features-total-badge"><?= count($qaFeatures) ?> entrée(s)</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover" id="table-qa-features">
                                <thead>
                                    <tr>
                                        <th style="width: 120px;">Date &amp; Module</th>
                                        <th>Fonctionnalité &amp; Spécifications</th>
                                        <th>Vérification QA Attendue</th>
                                        <th style="width: 140px;">Statut Actuel</th>
                                        <th class="text-end" style="width: 180px;">Action de Recette</th>
                                    </tr>
                                </thead>
                                <tbody id="qa-features-tbody">
                                    <?php if (empty($qaFeatures)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4 fst-italic">
                                                Aucune fonctionnalité enregistrée dans <code>fonctionnalités.md</code>.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($qaFeatures as $f): ?>
                                            <?php
                                            $st = mb_strtolower($f['status']);
                                            if (str_contains($st, 'valid')) {
                                                $statusBadge = 'bg-success-lt text-success border border-success';
                                                $statusIcon = '✔';
                                            } elseif (str_contains($st, 'rejet') || str_contains($st, 'refus')) {
                                                $statusBadge = 'bg-danger-lt text-danger border border-danger';
                                                $statusIcon = '✗';
                                            } else {
                                                $statusBadge = 'bg-warning-lt text-warning border border-warning';
                                                $statusIcon = '⏳';
                                            }
                                            ?>
                                            <tr id="feature-row-<?= $f['id'] ?>" data-feature-id="<?= $f['id'] ?>">
                                                <td>
                                                    <div class="font-monospace small text-dark fw-bold"><?= htmlspecialchars($f['date']) ?></div>
                                                    <span class="badge bg-blue-lt text-uppercase font-monospace mt-1" style="font-size: 0.65rem;">
                                                        <?= htmlspecialchars($f['module']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark mb-1" id="feature-title-<?= $f['id'] ?>"><?= htmlspecialchars($f['title']) ?></div>
                                                    <div class="text-secondary small" style="line-height: 1.4;">
                                                        <?= htmlspecialchars($f['description']) ?>
                                                    </div>
                                                    <?php if (!empty($f['files'])): ?>
                                                        <div class="text-muted small mt-1 font-monospace" style="font-size: 0.75rem;">
                                                            📂 <?= htmlspecialchars($f['files']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($f['validated_by'])): ?>
                                                        <div class="text-muted small mt-1 fst-italic" style="font-size: 0.75rem;" id="feature-validator-<?= $f['id'] ?>">
                                                            👤 Validé par : <?= htmlspecialchars($f['validated_by']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($f['qa_check'])): ?>
                                                        <div class="small text-secondary bg-light p-2 rounded border" style="font-size: 0.8rem; line-height: 1.35;">
                                                            🎯 <?= htmlspecialchars($f['qa_check']) ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted small fst-italic">Non spécifié</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $statusBadge ?> px-2 py-1" id="feature-badge-<?= $f['id'] ?>">
                                                        <?= $statusIcon ?> <?= htmlspecialchars($f['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-success" 
                                                                onclick="setFeatureStatus('<?= $f['id'] ?>', 'Validée', '<?= htmlspecialchars(addslashes($f['title'])) ?>')"
                                                                title="Marquer comme validée">
                                                            ✔ Valider
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="setFeatureStatus('<?= $f['id'] ?>', 'Rejetée', '<?= htmlspecialchars(addslashes($f['title'])) ?>')"
                                                                title="Rejeter (anomalie détectée)">
                                                            ✗ Rejeter
                                                        </button>
                                                        <button type="button" class="btn btn-outline-warning" 
                                                                onclick="setFeatureStatus('<?= $f['id'] ?>', 'À tester', '<?= htmlspecialchars(addslashes($f['title'])) ?>')"
                                                                title="Remettre à tester">
                                                            ⏳ Reset
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
                <?php endif; ?>

                <!-- ═══════════════════════════════════════════════════════════════════════
                     ONGLET : MAILING LIST & COMMUNAUTÉ
                     ═══════════════════════════════════════════════════════════════════════ -->
                <?php if (in_array('mailing', $allowedTabs, true)): ?>
                <div class="tab-pane fade <?= $activeTab === 'mailing' ? 'show active' : '' ?>" id="tab-mailing" role="tabpanel">

                    <!-- En-tête de section -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                        <div>
                            <h3 class="card-title mb-1 d-flex align-items-center gap-2">
                                <span>📢</span> Mailing List &amp; Diffusion Communautaire
                            </h3>
                            <p class="text-muted small mb-0">
                                Gestion des abonnés aux chroniques impériales, composition de missives officielles et campagnes ciblées par faction ou statut.
                            </p>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2" onclick="handleExportMailingCsv()">
                                <span>📥</span> Exporter la Liste (CSV)
                            </button>
                            <button type="button" class="btn btn-teal text-white d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modal-compose-newsletter">
                                <span>✍️</span> Composer une Missive
                            </button>
                        </div>
                    </div>

                    <!-- Cartes KPI de la Mailing List -->
                    <div class="row row-cards mb-4">
                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">Total Inscrits</span>
                                        <span class="badge bg-secondary-lt text-secondary">Base Globale</span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace text-dark d-flex align-items-center gap-2">
                                        <span>👥</span> <span id="kpi-mailing-total"><?= $mailingStats['total_users'] ?></span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Tous les joueurs humains enregistrés
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">Abonnés Newsletter</span>
                                        <span class="badge bg-teal-lt text-teal">Opt-in Actif</span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace text-teal d-flex align-items-center gap-2">
                                        <span>📬</span> <span id="kpi-mailing-optin"><?= $mailingStats['newsletter_subscribers'] ?></span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Joueurs ayant consenti à la diffusion
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">Taux d'Adhésion</span>
                                        <span class="badge bg-cyan-lt text-cyan">Conformité RGPD</span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace text-cyan d-flex align-items-center gap-2">
                                        <span>📊</span> <span id="kpi-mailing-rate"><?= $mailingStats['optin_rate_percent'] ?>%</span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Proportion d'abonnés volontaires
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">Missives Expédiées</span>
                                        <span class="badge bg-purple-lt text-purple">Historique</span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace text-purple d-flex align-items-center gap-2">
                                        <span>📜</span> <span id="kpi-mailing-campaigns"><?= $mailingStats['total_campaigns'] ?></span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Campagnes envoyées depuis la fondation
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Barre de Recherche et Filtres -->
                    <div class="card border mb-4">
                        <div class="card-body p-3">
                            <form id="mailing-filter-form" onsubmit="event.preventDefault(); loadMailingSubscribers(1);" class="row g-2 align-items-center">
                                <div class="col-md-3">
                                    <div class="input-icon">
                                        <span class="input-icon-addon">🔍</span>
                                        <input type="text" class="form-control" id="filter-mailing-search" placeholder="Daimyō ou adresse e-mail..." oninput="debounceMailingSearch()">
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-2">
                                    <select class="form-select" id="filter-mailing-optin" onchange="loadMailingSubscribers(1)">
                                        <option value="all">Diffusion : Tous</option>
                                        <option value="1">✓ Abonnés (Opt-in)</option>
                                        <option value="0">✗ Non abonnés</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 col-md-2">
                                    <select class="form-select" id="filter-mailing-status" onchange="loadMailingSubscribers(1)">
                                        <option value="all">Compte : Tous</option>
                                        <option value="active">Actifs (Vérifiés)</option>
                                        <option value="pending">En attente d'activation</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 col-md-2">
                                    <select class="form-select" id="filter-mailing-faction" onchange="loadMailingSubscribers(1)">
                                        <option value="all">Clan : Tous</option>
                                        <option value="terran">Clan Tokugawa</option>
                                        <option value="vorash">Clan Oda</option>
                                        <option value="aethelis">Clan Takeda</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 col-md-2">
                                    <select class="form-select" id="filter-mailing-role" onchange="loadMailingSubscribers(1)">
                                        <option value="all">Rôle : Tous</option>
                                        <option value="player">Joueurs simples</option>
                                        <option value="dev_team">Membres Dev Team</option>
                                        <option value="moderator">Modérateurs</option>
                                        <option value="admin">Administrateurs</option>
                                    </select>
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-outline-secondary w-100" onclick="resetMailingFilters()" title="Réinitialiser les filtres">
                                        🔄
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tableau des Abonnés -->
                    <div class="card border mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">Registre des Joueurs &amp; Statut de Diffusion (<span id="mailing-subscribers-count"><?= $initialSubscribers['total'] ?></span>)</h4>
                            <span class="text-muted small" id="mailing-pagination-indicator">Page 1</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover" id="table-mailing-subscribers">
                                <thead>
                                    <tr>
                                        <th>Seigneur Daimyō</th>
                                        <th>E-mail</th>
                                        <th>Clan</th>
                                        <th>Métiers &amp; Profil</th>
                                        <th class="text-center">Abonnement Newsletter</th>
                                        <th>Date d'Inscription</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="mailing-subscribers-tbody">
                                    <?php if (empty($initialSubscribers['subscribers'])): ?>
                                        <tr class="no-subscribers-row">
                                            <td colspan="7" class="text-center text-muted py-4 fst-italic">
                                                Aucun joueur ne correspond aux critères sélectionnés.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($initialSubscribers['subscribers'] as $s): ?>
                                            <tr id="subscriber-row-<?= $s['id'] ?>">
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="avatar avatar-sm bg-blue-lt">👤</span>
                                                        <div>
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($s['username']) ?></div>
                                                            <div class="text-muted small font-monospace">ID #<?= $s['id'] ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-dark font-monospace small"><?= htmlspecialchars($s['email']) ?></div>
                                                    <?php if ($s['is_active']): ?>
                                                        <span class="badge bg-success-lt" style="font-size: 0.68rem;">✔ Confirmé</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-lt" style="font-size: 0.68rem;">⏳ En attente</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $fNames = ['terran' => 'Tokugawa', 'vorash' => 'Oda', 'aethelis' => 'Takeda'];
                                                    $fColors = ['terran' => 'bg-blue-lt text-blue', 'vorash' => 'bg-red-lt text-red', 'aethelis' => 'bg-green-lt text-green'];
                                                    $fName = $fNames[$s['faction']] ?? $s['faction'];
                                                    $fColor = $fColors[$s['faction']] ?? 'bg-secondary-lt text-secondary';
                                                    ?>
                                                    <span class="badge <?= $fColor ?>"><?= htmlspecialchars($fName) ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php if ($s['is_admin']): ?>
                                                            <span class="badge bg-danger text-white">👑 Admin</span>
                                                        <?php endif; ?>
                                                        <?php if ($s['is_moderator']): ?>
                                                            <span class="badge bg-warning text-white">🛡️ Modo</span>
                                                        <?php endif; ?>
                                                        <?php if (empty($s['dev_roles']) && !$s['is_admin'] && !$s['is_moderator']): ?>
                                                            <span class="badge bg-light text-muted">Joueur</span>
                                                        <?php else: ?>
                                                            <?php foreach ($s['dev_roles'] as $dr): ?>
                                                                <span class="badge bg-purple-lt"><?= $dr['icon'] ?> <?= htmlspecialchars($dr['title']) ?></span>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="text-center" id="subscriber-optin-cell-<?= $s['id'] ?>">
                                                    <?php if ($s['newsletter_optin']): ?>
                                                        <span class="badge bg-success-lt text-success" title="Inscrit volontairement à la newsletter">
                                                            ✓ Abonné
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-lt text-muted" title="Non inscrit">
                                                            ✗ Non abonné
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small text-muted font-monospace">
                                                    <?= htmlspecialchars(substr($s['created_at'], 0, 10)) ?>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                            onclick="toggleSubscriberOptin(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['username'])) ?>', <?= $s['newsletter_optin'] ? 'true' : 'false' ?>)"
                                                            title="Basculer le statut d'adhésion">
                                                        <?= $s['newsletter_optin'] ? 'Désinscrire' : 'Abonner' ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between py-2" id="mailing-pagination-box">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-mailing-prev" onclick="changeMailingPage(-1)" disabled>
                                &larr; Précédent
                            </button>
                            <span class="small text-muted" id="mailing-page-info">Affichage des 20 premiers</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-mailing-next" onclick="changeMailingPage(1)" <?= ($initialSubscribers['total'] <= 20) ? 'disabled' : '' ?>>
                                Suivant &rarr;
                            </button>
                        </div>
                    </div>

                    <!-- Journal des Missives & Campagnes Expédiées -->
                    <div class="card border">
                        <div class="card-header bg-light">
                            <h4 class="card-title mb-0 d-flex align-items-center gap-2">
                                <span>📜</span> Dernières Missives Expédiées
                            </h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-striped" id="table-mailing-campaigns">
                                <thead>
                                    <tr>
                                        <th>Date d'Envoi</th>
                                        <th>Sujet de la Missive</th>
                                        <th>Cible</th>
                                        <th class="text-center">Destinataires</th>
                                        <th>Héraut / Expéditeur</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody id="mailing-campaigns-tbody">
                                    <?php if (empty($recentCampaigns)): ?>
                                        <tr class="no-campaign-row">
                                            <td colspan="6" class="text-center text-muted py-4 fst-italic">
                                                Aucune missive groupée n'a encore été expédiée. Utilisez le bouton « Composer une Missive » pour lancer votre première campagne.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentCampaigns as $camp): ?>
                                            <tr>
                                                <td class="small font-monospace"><?= htmlspecialchars($camp['sent_at']) ?></td>
                                                <td class="fw-bold text-dark"><?= htmlspecialchars($camp['subject']) ?></td>
                                                <td><span class="badge bg-secondary-lt"><?= htmlspecialchars($camp['target_group']) ?></span></td>
                                                <td class="text-center font-monospace fw-bold text-teal"><?= (int)$camp['recipient_count'] ?></td>
                                                <td class="small"><?= htmlspecialchars($camp['sender_name']) ?></td>
                                                <td>
                                                    <?php if ($camp['status'] === 'sent'): ?>
                                                        <span class="badge bg-success-lt text-success">✔ Expédiée</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger-lt text-danger">✗ Échec</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
                <?php endif; ?>

                <!-- ═══════════════════════════════════════════════════════════════════════
                     ONGLET 3 : ATELIER QA & SANDBOX
                     ═══════════════════════════════════════════════════════════════════════ -->
                <?php if (in_array('sandbox', $allowedTabs, true)): ?>
                <div class="tab-pane fade <?= $activeTab === 'sandbox' ? 'show active' : '' ?>" id="tab-sandbox" role="tabpanel">
                    
                    <div class="alert alert-warning d-flex align-items-center gap-3 mb-4 shadow-sm">
                        <div class="fs-1">🧪</div>
                        <div>
                            <h4 class="alert-title mb-1">Console Sandbox & Débogage QA</h4>
                            <div class="small">
                                Ces commandes directes sont destinées aux tests de non-régression, vérifications d'équilibrage et tests de résistance.<br>
                                <em>Chaque action exécutée ici est enregistrée dans le journal de forge et octroie de l'XP de contribution.</em>
                            </div>
                        </div>
                    </div>

                    <div class="row row-cards">
                        <!-- Action 1 : Injection de Ressources -->
                        <div class="col-md-6">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <span class="fs-2">🌾</span>
                                        <div>
                                            <h3 class="card-title mb-0">Approvisionnement Rapide (QA Test)</h3>
                                            <span class="text-muted small">Injection directe sur votre fief actuel</span>
                                        </div>
                                    </div>
                                    <p class="text-muted small">
                                        Crédite immédiatement <strong>100 000 Métal</strong>, <strong>100 000 Cristal</strong> et <strong>100 000 Deutérium</strong> pour tester les chantiers, recherches et levées d'armées sans attendre la récolte passive.
                                    </p>
                                    <div class="mt-4">
                                        <button type="button" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2" onclick="triggerSandboxAction('give_resources', this)">
                                            <span>⚡</span> Injecter +100 000 Ressources
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action 2 : Achèvement Rapide des Bâtiments -->
                        <div class="col-md-6">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <span class="fs-2">🏯</span>
                                        <div>
                                            <h3 class="card-title mb-0">Achèvement Instantané des Chantiers</h3>
                                            <span class="text-muted small">Passage forcé à zéro du temps d'attente</span>
                                        </div>
                                    </div>
                                    <p class="text-muted small">
                                        Met instantanément à jour le timer de tous vos bâtiments actuellement en construction ou montée de niveau sur votre fief pour valider immédiatement le rendu UI et les nouveaux bonus.
                                    </p>
                                    <div class="mt-4">
                                        <button type="button" class="btn btn-warning w-100 d-flex align-items-center justify-content-center gap-2 text-dark" onclick="triggerSandboxAction('instant_finish_constructions', this)">
                                            <span>⏩</span> Achever Immédiatement les Chantiers
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <?php endif; ?>

                <!-- ═══════════════════════════════════════════════════════════════════════
                     ONGLET 3 : LIVE OPS & SERVEUR
                     ═══════════════════════════════════════════════════════════════════════ -->
                <?php if (in_array('system', $allowedTabs, true)): ?>
                <div class="tab-pane fade <?= $activeTab === 'system' ? 'show active' : '' ?>" id="tab-system" role="tabpanel">

                    <h3 class="card-title mb-3">Indicateurs de Santé & Opérations Réseau</h3>

                    <div class="row row-cards mb-4">
                        <div class="col-sm-6 col-xl-3">
                            <div class="card card-sm border">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-primary text-white avatar">🐘</span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">Version Moteur PHP</div>
                                            <div class="text-muted small"><?= phpversion() ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card card-sm border">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-success text-white avatar">👥</span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium"><?= number_format($totalPlayersCount) ?> Joueurs</div>
                                            <div class="text-muted small"><?= number_format($totalPlanetsCount) ?> Fiefs & Provinces</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card card-sm border">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-warning text-white avatar">🏗️</span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium"><?= number_format($activeQueuesCount) ?> Chantiers Actifs</div>
                                            <div class="text-muted small">File d'attente féodale</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card card-sm border">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-danger text-white avatar">🧠</span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">Mémoire Utilisée</div>
                                            <div class="text-muted small"><?= round(memory_get_usage() / 1024 / 1024, 2) ?> Mo (Pic: <?= round(memory_get_peak_usage() / 1024 / 1024, 2) ?> Mo)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Déclenchement de Cron / IA -->
                    <?php if ($canTriggerCron): ?>
                    <div class="card border">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <h4 class="card-title mb-1">🤖 Déclenchement Forcé de la Boucle d'IA & Crons</h4>
                                    <p class="text-muted small mb-0">Exécute immédiatement le cycle autonome de réflexion des bots PNJ, calcul des flottes et mise à jour de la carte.</p>
                                </div>
                                <button type="button" class="btn btn-indigo d-flex align-items-center gap-2" onclick="triggerBotCycle(this)">
                                    <span>🔄</span> Forcer le Cycle IA
                                </button>
                            </div>
                            <div id="bot-cycle-log" class="mt-3 font-monospace p-3 bg-dark text-light rounded small d-none" style="max-height: 250px; overflow-y: auto;"></div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
                <?php endif; ?>

                <!-- ═══════════════════════════════════════════════════════════════════════
                     ONGLET 4 : UNIVERS & LORE
                     ═══════════════════════════════════════════════════════════════════════ -->
                <?php if (in_array('lore', $allowedTabs, true)): ?>
                <div class="tab-pane fade <?= $activeTab === 'lore' ? 'show active' : '' ?>" id="tab-lore" role="tabpanel">
                    
                    <div class="card border mb-3">
                        <div class="card-body">
                            <h3 class="card-title mb-2">📜 La Charte Narrative du Sengoku Céleste</h3>
                            <p class="text-muted">
                                Bienvenue dans l'espace réservé au <strong>Narrative Designer</strong> et à la cohérence de l'univers féodal d'OpenShogun. 
                                Le projet allie le réalisme historique de l'ère Sengoku (Daimyōs, Samouraïs, Châteaux forts, Ronins) à une touche de mystère et de poésie shintoïste.
                            </p>

                            <div class="row g-3 mt-2">
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded border h-100">
                                        <h4 class="fw-bold mb-1">🏯 Les 3 Factions</h4>
                                        <p class="small text-muted mb-0">
                                            <strong>Oda (Terran) :</strong> Maîtres de la poudre noire, de la stratégie agressive et de l'innovation militaire.<br>
                                            <strong>Takeda (Cyborg) :</strong> Cavalerie légendaire, rigueur martiale et fortifications montagnardes inexpugnables.<br>
                                            <strong>Mori (Alien) :</strong> Domination maritime, spiritualité des sanctuaires et diplomatie raffinée.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded border h-100">
                                        <h4 class="fw-bold mb-1">⚔️ Ton & Vocabulaire</h4>
                                        <p class="small text-muted mb-0">
                                            Privilégier le lexique d'époque : <em>Koban, Fiefs, Terroir, Cité Castrale, Dojo, Ronins, Seppuku, Daimyō, Shōgun</em>.
                                            Éviter les anachronismes ou anglicismes non traduits pour maintenir l'immersion des joueurs.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded border h-100">
                                        <h4 class="fw-bold mb-1">🎯 Quêtes & Chroniques</h4>
                                        <p class="small text-muted mb-0">
                                            Chaque quête doit raconter l'ascension d'un jeune seigneur de province jusqu'au trône de Kyōto, entre intrigues de cour et batailles épiques.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <?php endif; ?>

                <!-- ═══════════════════════════════════════════════════════════════════════
                     ONGLET 5 : JOURNAL DE FORGE & XP
                     ═══════════════════════════════════════════════════════════════════════ -->
                <?php if (in_array('forge', $allowedTabs, true)): ?>
                <div class="tab-pane fade <?= $activeTab === 'forge' ? 'show active' : '' ?>" id="tab-forge" role="tabpanel">

                    <!-- Paliers de Progression -->
                    <h3 class="card-title mb-3">🏆 Paliers des Créateurs de la Forge</h3>
                    <div class="row row-cards mb-4">
                        <?php 
                        $tiersDisplay = [
                            ['lvl' => 1, 'min' => 0,    'title' => 'Apprenti Forgeron',          'badge' => 'bg-secondary-lt', 'desc' => 'Prise en main du code et découverte des mécaniques.'],
                            ['lvl' => 2, 'min' => 150,  'title' => 'Artisan du Code & Lore',     'badge' => 'bg-cyan-lt',      'desc' => 'Premières quêtes validées et corrections de bugs.'],
                            ['lvl' => 3, 'min' => 450,  'title' => 'Maître Bâtisseur Féodal',    'badge' => 'bg-info-lt',      'desc' => 'Architecture de nouveaux modules et équilibrages majeurs.'],
                            ['lvl' => 4, 'min' => 1000, 'title' => 'Grand Concepteur du Royaume', 'badge' => 'bg-purple-lt',    'desc' => 'Direction technique et leadership sur les sorties.'],
                            ['lvl' => 5, 'min' => 2000, 'title' => 'Légende Vivante du Studio',   'badge' => 'bg-warning-lt',   'desc' => 'Auteur fondateur de l\'univers OpenShogun.'],
                        ];
                        ?>
                        <?php foreach ($tiersDisplay as $t): ?>
                            <?php $isCurrent = ($levelInfo['level'] === $t['lvl']); ?>
                            <div class="col">
                                <div class="card text-center h-100 border <?= $isCurrent ? 'border-warning shadow' : '' ?>">
                                    <div class="card-body p-3">
                                        <div class="badge <?= $t['badge'] ?> mb-2">Palier <?= $t['lvl'] ?></div>
                                        <h4 class="card-title mb-1" style="font-size: 0.95rem;"><?= htmlspecialchars($t['title']) ?></h4>
                                        <div class="text-muted small font-monospace mb-2"><?= number_format($t['min']) ?> XP</div>
                                        <p class="text-muted small mb-0" style="font-size: 0.75rem;"><?= htmlspecialchars($t['desc']) ?></p>
                                    </div>
                                    <?php if ($isCurrent): ?>
                                        <div class="card-footer p-1 bg-warning-lt text-warning fw-bold small">
                                            Votre Rang Actuel
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Historique de Forge -->
                    <div class="card border">
                        <div class="card-header bg-light">
                            <h4 class="card-title mb-0">Dernières Contributions & XP Gagnés</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Contributeur</th>
                                        <th>Action</th>
                                        <th>Détails</th>
                                        <th class="text-end">XP Obtenu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($forgeHistory)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">Aucune activité enregistrée pour le moment.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($forgeHistory as $fh): ?>
                                            <tr>
                                                <td class="text-muted small"><?= date('d/m/Y H:i', (int)$fh['created_at']) ?></td>
                                                <td><strong><?= htmlspecialchars($fh['username']) ?></strong></td>
                                                <td><span class="badge bg-purple-lt"><?= htmlspecialchars($fh['action_type']) ?></span></td>
                                                <td class="small text-muted"><?= htmlspecialchars($fh['description']) ?></td>
                                                <td class="text-end"><span class="badge bg-success-lt font-monospace">+<?= (int)$fh['xp_amount'] ?> XP</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
                <?php endif; ?>

                <!-- ═══════════════════════════════════════════════════════════════════════
                     ONGLET 6 : VITESSES & ÉQUILIBRAGE (GAME ELEVATE DESIGNER)
                     ═══════════════════════════════════════════════════════════════════════ -->
                <?php if (in_array('game_speeds', $allowedTabs, true)): ?>
                <div class="tab-pane fade <?= $activeTab === 'game_speeds' ? 'show active' : '' ?>" id="tab-game_speeds" role="tabpanel">
                    <div class="card mb-4 border-0 shadow-sm" style="border-top: 3px solid #ef4444 !important;">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-purple-lt text-purple fw-bold">🎮 Game Elevate Designer</span>
                                    <span class="badge bg-danger-lt fw-bold">⚡ Constantes Monde</span>
                                </div>
                                <h3 class="card-title d-flex align-items-center gap-2 m-0 text-danger">
                                    <span>⚡</span> Constantes &amp; Équilibrage des Vitesses de Jeu
                                </h3>
                                <div class="text-secondary small mt-1">
                                    Facteurs d'accélération des chantiers, de production des ressources et de marche des armées féodales.
                                </div>
                            </div>
                            <div class="d-flex gap-1 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDevSpeedPreset(1, 1, 1)">1x Classique</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDevSpeedPreset(5, 5, 5)">5x Standard</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDevSpeedPreset(20, 20, 10)">20x Éclair</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDevSpeedPreset(50, 50, 20)">50x Hyper</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="devGameSettingsForm" onsubmit="saveDevGameSettings(event)">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span>⚡ Vitesse du Jeu (Chantiers &amp; Recherches)</span>
                                            <span class="badge bg-danger-lt" id="dev_badge_game_speed">x<?= (int)($gameSettings['game_speed'] ?? 5) ?></span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_game_speed_range" min="1" max="100" value="<?= (int)($gameSettings['game_speed'] ?? 5) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_game_speed_input').value = this.value; document.getElementById('dev_badge_game_speed').textContent = 'x' + this.value;">
                                            <input type="number" id="dev_game_speed_input" name="game_speed" min="1" max="100" 
                                                   value="<?= (int)($gameSettings['game_speed'] ?? 5) ?>" class="form-control text-center font-weight-bold" style="width: 75px; min-height: 36px;"
                                                   oninput="document.getElementById('dev_game_speed_range').value = this.value; document.getElementById('dev_badge_game_speed').textContent = 'x' + this.value;">
                                        </div>
                                        <div class="form-hint">Divise le temps nécessaire aux chantiers, Tenshu, académies et entraînements.</div>
                                    </div>

                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span>⛏️ Production des Ressources</span>
                                            <span class="badge bg-warning-lt" id="dev_badge_resource_speed">x<?= (int)($gameSettings['resource_speed'] ?? 5) ?></span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_resource_speed_range" min="1" max="100" value="<?= (int)($gameSettings['resource_speed'] ?? 5) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_resource_speed_input').value = this.value; document.getElementById('dev_badge_resource_speed').textContent = 'x' + this.value;">
                                            <input type="number" id="dev_resource_speed_input" name="resource_speed" min="1" max="100" 
                                                   value="<?= (int)($gameSettings['resource_speed'] ?? 5) ?>" class="form-control text-center font-weight-bold" style="width: 75px; min-height: 36px;"
                                                   oninput="document.getElementById('dev_resource_speed_range').value = this.value; document.getElementById('dev_badge_resource_speed').textContent = 'x' + this.value;">
                                        </div>
                                        <div class="form-hint">Multiplie la production horaire de Bois de Cèdre 🪵, Pierre 🪨 et Koku de Riz 🌾.</div>
                                    </div>

                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span>🐎 Marche des Troupes &amp; Expéditions</span>
                                            <span class="badge bg-primary-lt" id="dev_badge_fleet_speed">x<?= (int)($gameSettings['fleet_speed'] ?? 5) ?></span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_fleet_speed_range" min="1" max="50" value="<?= (int)($gameSettings['fleet_speed'] ?? 5) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_fleet_speed_input').value = this.value; document.getElementById('dev_badge_fleet_speed').textContent = 'x' + this.value;">
                                            <input type="number" id="dev_fleet_speed_input" name="fleet_speed" min="1" max="50" 
                                                   value="<?= (int)($gameSettings['fleet_speed'] ?? 5) ?>" class="form-control text-center font-weight-bold" style="width: 75px; min-height: 36px;"
                                                   oninput="document.getElementById('dev_fleet_speed_range').value = this.value; document.getElementById('dev_badge_fleet_speed').textContent = 'x' + this.value;">
                                        </div>
                                        <div class="form-hint">Accélère les trajets des régiments pour les assauts, convois de tributs et fondations.</div>
                                    </div>

                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span>🔰 Durée d'Immunité Débutant</span>
                                            <span class="badge bg-info-lt" id="dev_badge_protection_days"><?= (int)($gameSettings['beginner_protection_days'] ?? 7) ?> j</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_beginner_protection_days_range" min="0" max="30" value="<?= (int)($gameSettings['beginner_protection_days'] ?? 7) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_beginner_protection_days_input').value = this.value; document.getElementById('dev_badge_protection_days').textContent = this.value + ' j';">
                                            <div class="input-group" style="width: 85px;">
                                                <input type="number" id="dev_beginner_protection_days_input" name="beginner_protection_days" min="0" max="60" 
                                                       value="<?= (int)($gameSettings['beginner_protection_days'] ?? 7) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                                       oninput="document.getElementById('dev_beginner_protection_days_range').value = this.value; document.getElementById('dev_badge_protection_days').textContent = this.value + ' j';">
                                                <span class="input-group-text px-1 text-muted">j</span>
                                            </div>
                                        </div>
                                        <div class="form-hint">Durée accordée automatiquement lors de l'inscription (0 pour désactiver).</div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3 border-top pt-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span>🌿 Densité des Oasis sur la Carte (%)</span>
                                            <span class="badge bg-success-lt" id="dev_badge_oasis_density"><?= (float)($gameSettings['oasis_density_percent'] ?? 2.0) ?> %</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_oasis_density_percent_range" min="0.5" max="15.0" step="0.5" value="<?= (float)($gameSettings['oasis_density_percent'] ?? 2.0) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_oasis_density_percent_input').value = this.value; document.getElementById('dev_badge_oasis_density').textContent = this.value + ' %';">
                                            <div class="input-group" style="width: 95px;">
                                                <input type="number" id="dev_oasis_density_percent_input" name="oasis_density_percent" min="0.5" max="20" step="0.5" 
                                                       value="<?= (float)($gameSettings['oasis_density_percent'] ?? 2.0) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                                       oninput="document.getElementById('dev_oasis_density_percent_range').value = this.value; document.getElementById('dev_badge_oasis_density').textContent = this.value + ' %';">
                                                <span class="input-group-text px-1 text-muted">%</span>
                                            </div>
                                        </div>
                                        <div class="form-hint">Proportion de tuiles réservées aux oasis naturelles par rapport à la superficie totale.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark mb-1">
                                            🔄 Réapparition Continue d'Oasis après Capture
                                        </label>
                                        <div class="d-flex align-items-center" style="min-height: 38px;">
                                            <label class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" id="dev_oasis_respawn_on_capture" name="oasis_respawn_on_capture" value="1" 
                                                       <?= !empty($gameSettings['oasis_respawn_on_capture']) ? 'checked' : '' ?>>
                                                <span class="form-check-label fw-medium text-dark">
                                                    Faire éclore une nouvelle oasis sauvage lors de l'annexion d'une oasis par un joueur
                                                </span>
                                            </label>
                                        </div>
                                        <div class="form-hint">Maintient le réservoir d'oasis sauvages et de faune active pour l'ensemble des seigneurs.</div>
                                    </div>
                                </div>

                                <!-- Paramètres des Quêtes Féodales & Samouraï Héros -->
                                <div class="row g-3 mb-3 border-top pt-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span>🎋 Cages de Capture (Kago) en Quête (%)</span>
                                            <span class="badge bg-green-lt fw-bold" id="dev_badge_hero_cage_drop_rate"><?= (int)($gameSettings['hero_cage_drop_rate'] ?? 25) ?> %</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_hero_cage_drop_rate_range" min="0" max="100" step="1" 
                                                   value="<?= (int)($gameSettings['hero_cage_drop_rate'] ?? 25) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_hero_cage_drop_rate_input').value = this.value; document.getElementById('dev_badge_hero_cage_drop_rate').textContent = this.value + ' %';">
                                            <div class="input-group" style="width: 95px;">
                                                <input type="number" id="dev_hero_cage_drop_rate_input" name="hero_cage_drop_rate" min="0" max="100" step="1" 
                                                       value="<?= (int)($gameSettings['hero_cage_drop_rate'] ?? 25) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                                       oninput="document.getElementById('dev_hero_cage_drop_rate_range').value = this.value; document.getElementById('dev_badge_hero_cage_drop_rate').textContent = this.value + ' %';">
                                                <span class="input-group-text px-1 text-muted">%</span>
                                            </div>
                                        </div>
                                        <div class="form-hint">Probabilité pour le Samouraï de rapporter un lot de Cages (Kago 🎋) pour capturer les bêtes sauvages des oasis sans combat.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span>🥋 Gain d'Expérience (XP) en Aventure (%)</span>
                                            <span class="badge bg-primary-lt fw-bold" id="dev_badge_hero_xp_rate"><?= (int)($gameSettings['hero_xp_rate_percent'] ?? 100) ?> %</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_hero_xp_rate_percent_range" min="10" max="500" step="5" 
                                                   value="<?= (int)($gameSettings['hero_xp_rate_percent'] ?? 100) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_hero_xp_rate_percent_input').value = this.value; document.getElementById('dev_badge_hero_xp_rate').textContent = this.value + ' %';">
                                            <div class="input-group" style="width: 95px;">
                                                <input type="number" id="dev_hero_xp_rate_percent_input" name="hero_xp_rate_percent" min="10" max="500" step="5" 
                                                       value="<?= (int)($gameSettings['hero_xp_rate_percent'] ?? 100) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                                       oninput="document.getElementById('dev_hero_xp_rate_percent_range').value = this.value; document.getElementById('dev_badge_hero_xp_rate').textContent = this.value + ' %';">
                                                <span class="input-group-text px-1 text-muted">%</span>
                                            </div>
                                        </div>
                                        <div class="form-hint">Multiplicateur du gain d'XP du Héros lors des aventures. Diminuez ce pourcentage pour ralentir la montée de niveau du Samouraï.</div>
                                    </div>
                                </div>

                                <!-- Section Famine & Vivres Féodaux -->
                                <div class="row g-3 mb-3 border-top pt-3" style="background: #fef2f2; border-radius: 8px; padding: 1rem; border: 1px solid #fecaca;">
                                    <div class="col-12">
                                        <h4 class="m-0 fw-bold text-danger d-flex align-items-center gap-2">
                                            <span>🌾</span> Mécanisme de Famine &amp; Vivres Féodaux (Optionnel)
                                        </h4>
                                        <div class="text-secondary small mt-1">
                                            Si activé, les régiments d'élite (Tier 2, 3 et 4) exigent un entretien régulier en farine de riz. En cas de pénurie totale (stock de farine à 0), une famine s'abat sur le fief et décime progressivement les troupes d'élite.
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark mb-1">
                                            ⚠️ Activer la Famine (Disette de Farine)
                                        </label>
                                        <div class="d-flex align-items-center" style="min-height: 38px;">
                                            <label class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" id="dev_famine_enabled" name="famine_enabled" value="1" 
                                                       <?= !empty($gameSettings['famine_enabled']) ? 'checked' : '' ?>>
                                                <span class="form-check-label fw-bold text-danger">
                                                    Activer le péril de la famine
                                                </span>
                                            </label>
                                        </div>
                                        <div class="form-hint">Désactivé par défaut. Les troupes d'élite ne meurent pas si décoché.</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span>💀 Taux de Pertes Horaire en Famine (%)</span>
                                            <span class="badge bg-danger text-white" id="dev_badge_famine_rate"><?= (float)($gameSettings['famine_rate'] ?? 3.0) ?> %</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_famine_rate_range" min="0.5" max="25.0" step="0.5" value="<?= (float)($gameSettings['famine_rate'] ?? 3.0) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_famine_rate_input').value = this.value; document.getElementById('dev_badge_famine_rate').textContent = this.value + ' %';">
                                            <div class="input-group" style="width: 95px;">
                                                <input type="number" id="dev_famine_rate_input" name="famine_rate" min="0.5" max="50" step="0.5" 
                                                       value="<?= (float)($gameSettings['famine_rate'] ?? 3.0) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                                       oninput="document.getElementById('dev_famine_rate_range').value = this.value; document.getElementById('dev_badge_famine_rate').textContent = this.value + ' %';">
                                                <span class="input-group-text px-1 text-muted">%</span>
                                            </div>
                                        </div>
                                        <div class="form-hint">Pourcentage de soldats d'élite mourant de faim ou désertant par heure de rupture de farine.</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span>🍚 Rations Requises (Farine / 100 soldats / h)</span>
                                            <span class="badge bg-warning text-dark" id="dev_badge_famine_flour"><?= (float)($gameSettings['famine_flour_consumption'] ?? 1.0) ?></span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_famine_flour_range" min="0.1" max="10.0" step="0.1" value="<?= (float)($gameSettings['famine_flour_consumption'] ?? 1.0) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_famine_flour_consumption_input').value = this.value; document.getElementById('dev_badge_famine_flour').textContent = this.value;">
                                            <div class="input-group" style="width: 95px;">
                                                <input type="number" id="dev_famine_flour_consumption_input" name="famine_flour_consumption" min="0.1" max="20" step="0.1" 
                                                       value="<?= (float)($gameSettings['famine_flour_consumption'] ?? 1.0) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                                       oninput="document.getElementById('dev_famine_flour_range').value = this.value; document.getElementById('dev_badge_famine_flour').textContent = this.value;">
                                                <span class="input-group-text px-1 text-muted">🍚</span>
                                            </div>
                                        </div>
                                        <div class="form-hint">Unités de farine consommées par heure pour maintenir 100 troupes d'élite rassasiées.</div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                                        💾 Enregistrer les Constantes de Jeu
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ═══════════════════════════════════════════════════════════════════════
                     ONGLET 7 : ARPENTAGE & EXPANSION DES PROVINCES (GAME ELEVATE DESIGNER)
                     ═══════════════════════════════════════════════════════════════════════ -->
                <?php if (in_array('world_expansion', $allowedTabs, true)): ?>
                <div class="tab-pane fade <?= $activeTab === 'world_expansion' ? 'show active' : '' ?>" id="tab-world_expansion" role="tabpanel">
                    
                    <!-- Répartition Détaillée des Tuiles -->
                    <?php if (!empty($mapTileStats)): ?>
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-purple-lt text-purple fw-bold">🎮 Game Elevate Designer</span>
                                    <span class="badge bg-indigo-lt fw-bold">🗺️ Carte Féodale</span>
                                </div>
                                <h3 class="card-title d-flex align-items-center gap-2 m-0 text-dark">
                                    <span>🗺️</span> Répartition des Tuiles du Monde Féodal
                                </h3>
                                <div class="text-secondary small mt-1">
                                    Grille de <?= ($mapTileStats['radius'] * 2 + 1) ?>&times;<?= ($mapTileStats['radius'] * 2 + 1) ?> cases &bull; Rayon &plusmn;<?= $mapTileStats['radius'] ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary text-white fw-bold">
                                    <?= number_format($mapTileStats['total_tiles']) ?> Tuiles au total
                                </span>
                                <a href="/?page=map" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    🗾 Ouvrir la Carte &rarr;
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Barre de progression proportionnelle multi-segments -->
                            <div class="progress progress-separated mb-3" style="height: 10px;" title="Répartition graphique de la carte féodale">
                                <?php foreach ($mapTileCategories as $cat): 
                                    $pct = round(($cat['count'] / $mapTileStats['total_tiles']) * 100, 2);
                                    if ($pct <= 0) continue;
                                ?>
                                    <div class="progress-bar <?= $cat['bar_color'] ?>" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" title="<?= htmlspecialchars($cat['name']) ?> : <?= number_format($cat['count']) ?> tuiles (<?= $pct ?>%)"></div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Grille des 9 catégories de tuiles -->
                            <div class="row row-cards g-2">
                                <?php foreach ($mapTileCategories as $key => $cat): 
                                    $pct = round(($cat['count'] / $mapTileStats['total_tiles']) * 100, 1);
                                ?>
                                    <div class="col-6 col-sm-4 col-md-3 col-xl">
                                        <div class="card card-sm h-100 shadow-none border">
                                            <div class="card-body p-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="<?= $cat['img'] ?>" alt="<?= htmlspecialchars($cat['name']) ?>" 
                                                         style="width: 32px; height: 32px; border-radius: 4px; object-fit: cover; border: 1px solid rgba(0,0,0,0.15);" class="flex-shrink-0">
                                                    <div class="overflow-hidden flex-grow-1">
                                                        <div class="text-truncate fw-bold text-dark small" title="<?= htmlspecialchars($cat['name']) ?> (<?= htmlspecialchars($cat['sub']) ?>)">
                                                            <?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?>
                                                        </div>
                                                        <div class="d-flex align-items-baseline justify-content-between gap-1 mt-1">
                                                            <span class="badge <?= $cat['badge_bg'] ?> px-1 py-0 fw-bold" style="font-size: 0.72rem;">
                                                                <?= number_format($cat['count']) ?>
                                                            </span>
                                                            <span class="text-secondary small font-monospace" style="font-size: 0.7rem;">
                                                                <?= $pct ?>%
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Arpenteur du Shogunat & Déploiement des Fiefs -->
                    <div class="card mb-4 border-0 shadow-sm" style="border-top: 3px solid #10b981 !important;">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h3 class="card-title d-flex align-items-center gap-2 m-0 text-success">
                                    <span>🗾</span> Arpenteur du Shogunat &amp; Expansion des Provinces
                                </h3>
                                <div class="text-secondary small mt-1">Création procédurale de fiefs, vallées et sanctuaires</div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="devWorldGenForm" onsubmit="generateDevWorld(event)">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span>Nombre de Terres &amp; Fiefs à Déployer</span>
                                            <span class="badge bg-success-lt" id="dev_badge_planet_count">12 fiefs</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_planet_count_range" min="1" max="50" value="12" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_planet_count_input').value = this.value; document.getElementById('dev_badge_planet_count').textContent = this.value + ' fiefs';">
                                            <input type="number" id="dev_planet_count_input" name="planet_count" min="1" max="50" value="12" 
                                                   class="form-control text-center font-weight-bold" style="width: 75px; min-height: 36px;"
                                                   oninput="document.getElementById('dev_planet_count_range').value = this.value; document.getElementById('dev_badge_planet_count').textContent = this.value + ' fiefs';">
                                        </div>
                                        <div class="form-hint">Terres libres prêtes à être explorées, pillées ou inféodées.</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span>Rayon de Dispersion Géographique</span>
                                            <span class="badge bg-info-lt" id="dev_badge_radius">&plusmn; 12</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_radius_range" min="5" max="35" value="12" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_radius_input').value = this.value; document.getElementById('dev_badge_radius').textContent = '± ' + this.value;">
                                            <input type="number" id="dev_radius_input" name="radius" min="5" max="35" value="12" 
                                                   class="form-control text-center font-weight-bold" style="width: 75px; min-height: 36px;"
                                                   oninput="document.getElementById('dev_radius_range').value = this.value; document.getElementById('dev_badge_radius').textContent = '± ' + this.value;">
                                        </div>
                                        <div class="form-hint">Étendue des provinces [-R, +R] autour de la capitale impériale.</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark mb-1">
                                            Gestion des Terres Inoccupées
                                        </label>
                                        <div class="d-flex align-items-center" style="min-height: 38px;">
                                            <label class="form-check m-0">
                                                <input class="form-check-input" type="checkbox" name="clear_uninhabited" value="1" id="dev_clear_uninhabited">
                                                <span class="form-check-label fw-medium text-dark">
                                                    Purger les terres libres inoccupées existantes avant génération
                                                </span>
                                            </label>
                                        </div>
                                        <div class="form-hint">Ne supprime jamais les fiefs possédés par un Daimyō ou un bot.</div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-success px-4 fw-bold">
                                        🗾 Déployer les Fiefs dans les Provinces
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
                <?php endif; ?>

                <!-- ═══════════════════════════════════════════════════════════════════════
                     ONGLET 8 : ÉCOSYSTÈME DES OASIS (GAME ELEVATE DESIGNER)
                     ═══════════════════════════════════════════════════════════════════════ -->
                <?php if (in_array('oases_ecosystem', $allowedTabs, true)): ?>
                <div class="tab-pane fade <?= $activeTab === 'oases_ecosystem' ? 'show active' : '' ?>" id="tab-oases_ecosystem" role="tabpanel">
                    
                    <div class="card mb-4 border-0 shadow-sm" style="border-top: 3px solid #22c55e !important;">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-purple-lt text-purple fw-bold">🎮 Game Elevate Designer</span>
                                    <span class="badge bg-green-lt fw-bold">🌿 Faune &amp; Oasis</span>
                                </div>
                                <h3 class="card-title d-flex align-items-center gap-2 m-0 text-green">
                                    <span>🌿</span> Écosystème des Oasis Naturelles &amp; Faune Sauvage (Style Travian)
                                </h3>
                                <div class="text-secondary small mt-1">
                                    Gestion du réseau d'oasis sauvages, de la faune hostile (Sangliers, Loups, Ours) et de la réapparition continue après capture.
                                </div>
                            </div>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <span class="badge bg-green-lt fw-bold">
                                    <?= $oasisStats['total_oases'] ?> Oasis Totales
                                </span>
                                <span class="badge bg-blue-lt fw-bold">
                                    <?= $oasisStats['captured_oases'] ?> Fiefs Annexés
                                </span>
                                <span class="badge bg-danger-lt fw-bold">
                                    <?= $oasisStats['wild_oases'] ?> Sauvages Libres
                                </span>
                                <span class="badge bg-warning-lt fw-bold">
                                    🐗 <?= number_format($oasisStats['total_wild_animals']) ?> Bêtes Sauvages
                                </span>
                            </div>
                        </div>

                        <div class="card-body">
                            <!-- Panneau de contrôle et rééquilibrage de densité -->
                            <div class="card card-body bg-light border mb-3">
                                <h4 class="card-title d-flex align-items-center gap-2 text-success mb-2" style="font-size: 0.95rem;">
                                    <span>⚙️</span> Générateur &amp; Rééquilibrage par Pourcentage de Couverture
                                </h4>
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4 col-sm-6">
                                        <label class="form-label fw-bold text-dark mb-1">
                                            Pourcentage de Densité Cible (%) :
                                        </label>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="input-group" style="width: 110px;">
                                                <input type="number" id="dev_repop_density" min="0.5" max="20.0" step="0.5" 
                                                       value="<?= (float)($gameSettings['oasis_density_percent'] ?? 2.0) ?>" class="form-control text-center font-weight-bold">
                                                <span class="input-group-text px-1 text-muted">%</span>
                                            </div>
                                            <span class="text-secondary small">&approx; 65 oasis à 2.0%</span>
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label fw-bold text-dark mb-1">
                                            Rayon de Couverture Carte :
                                        </label>
                                        <div class="input-group" style="width: 110px;">
                                            <input type="number" id="dev_repop_radius" min="10" max="50" value="28" class="form-control text-center font-weight-bold">
                                            <span class="input-group-text px-1 text-muted">tuiles</span>
                                        </div>
                                    </div>

                                    <div class="col-md-5 col-sm-12">
                                        <label class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="dev_repop_clear_unoccupied" value="1">
                                            <span class="form-check-label fw-medium text-dark">Remplacer uniquement les oasis sauvages existantes</span>
                                        </label>
                                        <button type="button" onclick="executeDevRepopulateOases()" class="btn btn-success w-100 fw-bold">
                                            🌿 Appliquer &amp; Générer les Oasis
                                        </button>
                                    </div>
                                </div>
                                <div class="text-secondary small mt-2">
                                    ℹ️ Les oasis sont automatiquement réparties de façon équitable entre les 4 quadrants géographiques (NO, NE, SO, SE) sans empiéter sur les fiefs ni les 12 donjons authentiques.
                                </div>
                            </div>

                            <!-- Tableau des Oasis existantes -->
                            <div class="table-responsive" style="max-height: 440px;">
                                <table class="table card-table table-vcenter table-striped text-nowrap">
                                    <thead class="sticky-top bg-light">
                                        <tr>
                                            <th class="w-1">#</th>
                                            <th>Nom de l'Oasis</th>
                                            <th class="text-center">Coords</th>
                                            <th>Bonus de Récolte</th>
                                            <th>Faune / Garnison</th>
                                            <th class="text-center">Statut Féodal</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dev-oases-tbody">
                                        <?php if (empty($allOases)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">
                                                    Aucune oasis recensée. Utilisez le générateur ci-dessus pour peupler le royaume.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($allOases as $o): ?>
                                                <?php 
                                                    $isCaptured = !empty($o['owner_planet_id']);
                                                    $bText = '';
                                                    if ($o['bonus_rice'] > 0) $bText .= "+{$o['bonus_rice']}% 🌾 ";
                                                    if ($o['bonus_wood'] > 0) $bText .= "+{$o['bonus_wood']}% 🪵 ";
                                                    if ($o['bonus_stone'] > 0) $bText .= "+{$o['bonus_stone']}% 🪨 ";
                                                ?>
                                                <tr class="dev-oasis-table-row <?= $isCaptured ? 'table-primary-lt' : '' ?>">
                                                    <td class="text-muted fw-bold"><?= $o['id'] ?></td>
                                                    <td>
                                                        <strong class="text-dark"><?= htmlspecialchars($o['name']) ?></strong>
                                                        <div class="text-secondary small">
                                                            🪵 <?= number_format($o['res_wood']) ?> &bull; 🪨 <?= number_format($o['res_stone']) ?> &bull; 🌾 <?= number_format($o['res_rice']) ?>
                                                        </div>
                                                    </td>
                                                    <td class="text-center fw-bold text-azure">
                                                        [<?= $o['coord_x'] ?> : <?= $o['coord_y'] ?>]
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning-lt fw-bold"><?= trim($bText) ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($o['garrison'])): ?>
                                                            <div class="d-flex flex-wrap gap-1">
                                                                <?php foreach ($o['garrison'] as $g): ?>
                                                                    <span class="badge bg-dark-lt text-dark border">
                                                                         <?= $g['icon'] ?> <?= htmlspecialchars($g['unit_name']) ?> <strong class="text-warning">x<?= $g['count'] ?></strong>
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="badge bg-success-lt">🕊️ Pacifiée (Aucune bête)</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($isCaptured): ?>
                                                            <span class="badge bg-blue-lt">
                                                                🛡️ Fief de <?= htmlspecialchars($o['owner_username'] ?? 'Daimyō') ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger-lt">
                                                                🐗 Sauvage Libre
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="/?page=map&x=<?= $o['coord_x'] ?>&y=<?= $o['coord_y'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                            🗾 Carte
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination du Réseau des Oasis -->
                            <?php if (!empty($allOases)): ?>
                                <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2 py-2" id="devOasesPaginationContainer">
                                    <p class="m-0 text-secondary small" id="devOasesPaginationInfo">
                                        Affichage de <strong id="devOasesPaginationStart"><?= min(1, count($allOases)) ?></strong> à <strong id="devOasesPaginationEnd"><?= min(15, count($allOases)) ?></strong> sur <strong id="devOasesPaginationTotal"><?= count($allOases) ?></strong> oasis
                                    </p>
                                    <div class="d-flex align-items-center gap-2">
                                        <label for="devOasesPerPageSelect" class="small text-muted mb-0 d-none d-sm-inline">Par page :</label>
                                        <select id="devOasesPerPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="changeDevOasesPerPage(this.value)">
                                            <option value="15" selected>15</option>
                                            <option value="30">30</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                        <ul class="pagination pagination-sm m-0" id="devOasesPaginationList">
                                            <!-- Rempli en JavaScript -->
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL : ASSIGNATION D'UN MÉTIER
     ═══════════════════════════════════════════════════════════════════════════ -->
<?php if ($canManageTeam): ?>
<div class="modal fade" id="modal-assign-role" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2 m-0">
                        <span>🛠️</span> Attribution des Métiers de Développement
                    </h5>
                    <div class="text-muted small mt-1">Sélectionnez un membre et les métiers à lui confier. Les métiers déjà détenus sont automatiquement bloqués.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="form-assign-role" onsubmit="handleAssignRoleSubmit(event)">
                <div class="modal-body">
                    <!-- 1. Sélection du Membre -->
                    <div class="mb-3">
                        <label class="form-label required fw-bold">Membre de l'Équipe</label>
                        <select name="user_id" id="assign-role-user-id" class="form-select" onchange="onAssignUserChanged(this.value)" required>
                            <option value="">-- Choisir un joueur ou collaborateur --</option>
                            <?php foreach ($allUsersList as $u): ?>
                                <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['username']) ?> (ID: <?= (int)$u['id'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 2. Aperçu des métiers actuels du joueur -->
                    <div id="assign-user-current-roles-box" class="p-3 bg-light rounded border mb-3 d-none">
                        <div class="text-muted small fw-bold mb-2">Métier(s) actuellement détenu(s) par ce joueur :</div>
                        <div class="d-flex flex-wrap gap-1" id="assign-user-current-roles-list"></div>
                    </div>

                    <!-- 3. Sélection Multiple des Métiers -->
                    <div id="assign-roles-selector-block">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label required fw-bold m-0">Métiers Disponibles (Sélection Multiple)</label>
                            <div class="small">
                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" onclick="selectAllAvailableRoles()">Tout cocher</button>
                                <span class="text-muted mx-1">&bull;</span>
                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" onclick="clearSelectedRoles()">Tout décocher</button>
                            </div>
                        </div>

                        <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column gap-2" id="assign-roles-container">
                            <?php foreach (DevTeamEngine::ROLES as $rKey => $rVal): ?>
                                <label class="form-selectgroup-item w-100" id="assign-role-item-<?= $rKey ?>" style="cursor: pointer; transition: all 0.2s ease;">
                                    <input type="checkbox" name="role_ids[]" value="<?= $rKey ?>" class="form-selectgroup-input assign-role-checkbox" data-role-id="<?= $rKey ?>" onchange="updateAssignSubmitBtnState()">
                                    <span class="form-selectgroup-label d-flex align-items-center p-2 text-start">
                                        <span class="me-3"><span class="form-selectgroup-check"></span></span>
                                        <span class="fs-2 me-2"><?= $rVal['icon'] ?></span>
                                        <span class="form-selectgroup-label-content flex-fill">
                                            <span class="d-flex justify-content-between align-items-center">
                                                <strong class="text-dark"><?= htmlspecialchars($rVal['title']) ?></strong>
                                                <span class="badge <?= $rVal['badge_color'] ?>"><?= htmlspecialchars($rVal['category']) ?></span>
                                            </span>
                                            <span class="text-muted small d-block" style="font-size: 0.75rem; line-height: 1.3;"><?= htmlspecialchars($rVal['description']) ?></span>
                                            <span class="badge bg-secondary-lt text-secondary mt-1 role-assigned-tag d-none" style="font-size: 0.7rem;">
                                                ✓ Déjà assigné à ce joueur
                                            </span>
                                        </span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 4. Alerte si tous les rôles sont déjà assignés -->
                    <div id="assign-all-roles-taken" class="alert alert-info d-none mt-2 py-2 small mb-0">
                        ℹ️ Ce joueur possède déjà l'ensemble des 8 métiers de la Dev Team.
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between align-items-center">
                    <span class="text-muted small" id="assign-selection-count">0 métier sélectionné</span>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-purple" id="btn-submit-assign" disabled>
                            <span>➕</span> Assigner les métiers
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL : RÉCOMPENSER EN FORGE XP
     ═══════════════════════════════════════════════════════════════════════════ -->
<?php if ($canManageSprints): ?>
<div class="modal fade" id="modal-award-xp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">⭐ Récompenser une Contribution (Forge XP)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="form-award-xp" onsubmit="handleAwardXpSubmit(event)">
                <input type="hidden" name="user_id" id="award-user-id" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Destinataire</label>
                        <input type="text" class="form-control" id="award-username" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Montant d'XP</label>
                        <select name="xp_amount" class="form-select" required>
                            <option value="50">+50 XP &bull; Correction mineure / Relecture lore</option>
                            <option value="100" selected>+100 XP &bull; Nouveau composant / Équilibrage testé</option>
                            <option value="250">+250 XP &bull; Déploiement d'une nouvelle mécanique clé</option>
                            <option value="500">+500 XP &bull; Fin de Sprint majeure / Release officielle</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Motif de la récompense</label>
                        <input type="text" name="reason" class="form-control" placeholder="Ex: Refonte du timer impérial, quête du Shōgun validée..." required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold" id="btn-submit-award">Attribuer les XP</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (in_array('mailing', $allowedTabs, true)): ?>
<!-- ── MODALE : COMPOSITION DE NEWSLETTER / MISSIVE IMPÉRIALE ── -->
<div class="modal fade" id="modal-compose-newsletter" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-teal text-white">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <span>📢</span> Composer une Missive Impériale / Newsletter
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-compose-newsletter" onsubmit="handleSendNewsletter(event)">
                <div class="modal-body">
                    
                    <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                        <span class="fs-2">⛩️</span>
                        <div class="small">
                            Votre missive sera mise en forme avec le gabarit féodal officiel d'OpenShogun (bannière impériale, blason du clan du joueur et mentions de conformité RGPD).
                        </div>
                    </div>

                    <!-- Sujet de l'e-mail -->
                    <div class="mb-3">
                        <label class="form-label fw-bold required">Sujet de la Missive</label>
                        <input type="text" class="form-control" name="subject" id="newsletter-subject" required placeholder="ex: Chroniques du Shōgunat : Les Nouvelles Terres Féodales et Événement de Siège" maxlength="150">
                        <div class="form-hint">Le sujet sous lequel le message apparaîtra dans la boîte de réception des Daimyōs.</div>
                    </div>

                    <!-- Groupe Cible -->
                    <div class="mb-3">
                        <label class="form-label fw-bold required">Groupe Cible des Destinataires</label>
                        <select class="form-select" name="target_group" id="newsletter-target-group" required onchange="updateTargetGroupHint(this.value)">
                            <option value="all_optin" selected>📬 Tous les Abonnés à la Newsletter (Opt-in actif — Recommandé RGPD)</option>
                            <option value="all_active">👥 Tous les Joueurs Actifs (Comptes confirmés)</option>
                            <option value="faction_terran">🛡️ Clan Tokugawa uniquement (Abonnés)</option>
                            <option value="faction_vorash">⚔️ Clan Oda uniquement (Abonnés)</option>
                            <option value="faction_aethelis">🏹 Clan Takeda uniquement (Abonnés)</option>
                            <option value="dev_team">🛠️ Membres de la Dev Team uniquement</option>
                            <option value="test_self">🧪 Test personnel (Expédier uniquement sur mon propre e-mail)</option>
                        </select>
                        <div class="form-hint" id="target-group-hint">
                            Les missives seront transmises uniquement aux joueurs ayant explicitement coché la case d'inscription.
                        </div>
                    </div>

                    <!-- Corps de la Missive -->
                    <div class="mb-3">
                        <label class="form-label fw-bold required">Corps de la Missive</label>
                        <textarea class="form-control" name="body_html" id="newsletter-body" rows="8" required placeholder="Seigneurs et Dames de l'Archipel,

Le Conseil impérial se réunit aujourd'hui pour vous annoncer de grandes réformes dans vos domaines castraux..."></textarea>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="form-hint">Les sauts de ligne seront automatiquement convertis en paragraphes.</span>
                            <button type="button" class="btn btn-sm btn-ghost-secondary" onclick="previewNewsletterModal()">
                                👁️ Prévisualiser le Rendu
                            </button>
                        </div>
                    </div>

                    <!-- Aperçu dynamique -->
                    <div id="newsletter-preview-container" class="border rounded p-3 bg-light d-none mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="text-dark small">Aperçu du message formaté :</strong>
                            <button type="button" class="btn-close btn-sm" onclick="document.getElementById('newsletter-preview-container').classList.add('d-none');"></button>
                        </div>
                        <div id="newsletter-preview-content" class="p-3 bg-white rounded border small" style="max-height: 250px; overflow-y: auto;"></div>
                    </div>

                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-teal text-white d-flex align-items-center gap-2" id="btn-submit-newsletter">
                        <span>🚀</span> Lancer l'Expédition de la Missive
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL : CONFIRMATION TABLER (Pas de confirm natif)
     ═══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modal-confirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="text-warning mb-2" id="confirm-icon" style="font-size: 2.5rem;">⚠️</div>
                <h3 class="mb-1" id="confirm-title">Confirmation</h3>
                <div class="text-muted small" id="confirm-message">Êtes-vous certain de vouloir effectuer cette action ?</div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col"><button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Annuler</button></div>
                        <div class="col"><button type="button" class="btn btn-primary w-100" id="btn-confirm-action">Confirmer</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     SCRIPTS JAVASCRIPT VANILLA DE LA DEV TEAM
     ═══════════════════════════════════════════════════════════════════════════ -->
<script>
// Cache des rôles détenus par utilisateur et métadonnées globales
const devMembersRoles = <?= json_encode($membersRolesMap, JSON_UNESCAPED_UNICODE) ?>;
const devAllRolesMeta = <?= json_encode(DevTeamEngine::ROLES, JSON_UNESCAPED_UNICODE) ?>;
const canManageTeamPermission = <?= $canManageTeam ? 'true' : 'false' ?>;

let pendingConfirmCallback = null;

function showConfirmModal(title, message, callback, btnClass = 'btn-primary', icon = '⚠️') {
    const modalEl = document.getElementById('modal-confirm');
    if (!modalEl) {
        callback();
        return;
    }
    document.getElementById('confirm-title').innerText = title;
    document.getElementById('confirm-message').innerHTML = message;
    document.getElementById('confirm-icon').innerText = icon;
    const confirmBtn = document.getElementById('btn-confirm-action');
    if (confirmBtn) {
        confirmBtn.className = `btn ${btnClass} w-100`;
    }
    
    pendingConfirmCallback = callback;
    
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        new bootstrap.Modal(modalEl).show();
    } else {
        callback();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('btn-confirm-action');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            const modalEl = document.getElementById('modal-confirm');
            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
            }
            if (typeof pendingConfirmCallback === 'function') {
                const cb = pendingConfirmCallback;
                pendingConfirmCallback = null;
                cb();
            }
        });
    }

    // Basculement automatique sur l'onglet demandé dans l'URL (?tab=mailing ou #tab-mailing)
    const urlParams = new URLSearchParams(window.location.search);
    const reqTab = urlParams.get('tab') || window.location.hash.replace('#tab-', '').replace('#', '');
    if (reqTab) {
        switchDevTab(reqTab);
    }
});

function showAlert(message, type = 'success') {
    const alertBox = document.getElementById('dev-alert');
    const content = document.getElementById('dev-alert-content');
    if (!alertBox || !content) return;

    alertBox.className = `alert alert-${type} alert-dismissible mb-3 shadow-sm`;
    content.innerHTML = message;
    alertBox.classList.remove('d-none');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function switchDevTab(tabId) {
    const triggerEl = document.querySelector(`#dev-team-tabs a[href="#tab-${tabId}"]`);
    if (triggerEl) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
            new bootstrap.Tab(triggerEl).show();
        } else {
            document.querySelectorAll('#dev-team-tabs .nav-link').forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));
            triggerEl.classList.add('active');
            const target = document.getElementById(`tab-${tabId}`);
            if (target) target.classList.add('show', 'active');
        }
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabId);
            url.searchParams.delete('forbidden');
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
    }
}

// ── GESTION DE L'ASSIGNATION MULTIPLE & BLOCAGE DES DOUBLONS ──────────────

function openAssignRoleModal(userId = null, username = null) {
    const selectEl = document.getElementById('assign-role-user-id');
    if (selectEl && userId) {
        selectEl.value = userId;
        onAssignUserChanged(userId);
    } else if (selectEl) {
        selectEl.value = '';
        onAssignUserChanged('');
    }

    const modalEl = document.getElementById('modal-assign-role');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        new bootstrap.Modal(modalEl).show();
    }
}

function onAssignUserChanged(userId) {
    const box = document.getElementById('assign-user-current-roles-box');
    const badgesList = document.getElementById('assign-user-current-roles-list');
    const allRolesBlock = document.getElementById('assign-roles-selector-block');
    const allTakenAlert = document.getElementById('assign-all-roles-taken');

    userId = parseInt(userId, 10);
    const ownedRoles = (userId && devMembersRoles[userId]) ? devMembersRoles[userId] : [];

    // Afficher l'aperçu des rôles actuels
    if (userId && box && badgesList) {
        box.classList.remove('d-none');
        if (ownedRoles.length === 0) {
            badgesList.innerHTML = `<span class="text-muted small fst-italic">Aucun métier actif actuellement</span>`;
        } else {
            badgesList.innerHTML = ownedRoles.map(rId => {
                const meta = devAllRolesMeta[rId];
                if (!meta) return '';
                return `<span class="badge ${meta.badge_color || 'bg-secondary'} me-1">${meta.icon || '🛠️'} ${meta.title}</span>`;
            }).join('');
        }
    } else if (box) {
        box.classList.add('d-none');
    }

    // Mettre à jour chaque case à cocher : bloquer / désactiver les doublons
    const totalRolesCount = Object.keys(devAllRolesMeta).length;
    let availableCount = 0;

    Object.keys(devAllRolesMeta).forEach(rKey => {
        const itemLabel = document.getElementById(`assign-role-item-${rKey}`);
        const cb = itemLabel ? itemLabel.querySelector('.assign-role-checkbox') : null;
        const tag = itemLabel ? itemLabel.querySelector('.role-assigned-tag') : null;

        if (!cb || !itemLabel) return;

        cb.checked = false;

        if (!userId) {
            cb.disabled = true;
            itemLabel.style.opacity = '0.6';
            itemLabel.style.cursor = 'not-allowed';
            if (tag) tag.classList.add('d-none');
        } else if (ownedRoles.includes(rKey)) {
            // Rôle déjà possédé : blocage strict
            cb.disabled = true;
            itemLabel.style.opacity = '0.55';
            itemLabel.style.cursor = 'not-allowed';
            if (tag) tag.classList.remove('d-none');
        } else {
            // Rôle libre
            cb.disabled = false;
            itemLabel.style.opacity = '1';
            itemLabel.style.cursor = 'pointer';
            if (tag) tag.classList.add('d-none');
            availableCount++;
        }
    });

    const isAllTaken = (userId && availableCount === 0);
    if (allTakenAlert) allTakenAlert.classList.toggle('d-none', !isAllTaken);
    if (allRolesBlock) allRolesBlock.classList.toggle('d-none', isAllTaken);

    updateAssignSubmitBtnState();
}

function selectAllAvailableRoles() {
    document.querySelectorAll('.assign-role-checkbox:not(:disabled)').forEach(cb => {
        cb.checked = true;
    });
    updateAssignSubmitBtnState();
}

function clearSelectedRoles() {
    document.querySelectorAll('.assign-role-checkbox').forEach(cb => {
        cb.checked = false;
    });
    updateAssignSubmitBtnState();
}

function updateAssignSubmitBtnState() {
    const checked = document.querySelectorAll('.assign-role-checkbox:checked');
    const count = checked.length;
    const btn = document.getElementById('btn-submit-assign');
    const counterText = document.getElementById('assign-selection-count');
    const userSelect = document.getElementById('assign-role-user-id');
    const hasUser = userSelect && userSelect.value !== '';

    if (counterText) {
        counterText.textContent = (count <= 1) ? `${count} métier sélectionné` : `${count} métiers sélectionnés`;
    }

    if (btn) {
        btn.disabled = (!hasUser || count === 0);
    }
}

// ── SOUMISSION DE L'ATTRIBUTION DES MÉTIERS ──────────────────────────────

async function handleAssignRoleSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('btn-submit-assign');
    const originalText = btn.innerHTML;

    const checkedBoxes = form.querySelectorAll('input[name="role_ids[]"]:checked');
    if (checkedBoxes.length === 0) {
        showAlert("⚠️ Veuillez cocher au moins un métier à assigner.", "warning");
        return;
    }

    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Attribution en cours...`;

    try {
        const formData = new FormData(form);
        formData.append('action', 'assign_roles');

        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success) {
            showAlert(`🎉 ${data.message}`, 'success');

            // Fermer la modale Tabler
            const modalEl = document.getElementById('modal-assign-role');
            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
            }

            const targetId = parseInt(data.user_id, 10);
            const userSelect = document.getElementById('assign-role-user-id');
            const targetUsername = userSelect?.options[userSelect.selectedIndex]?.text?.replace(/\s*\(ID:.*$/, '') || '';

            // Mettre à jour le cache local des rôles
            if (!devMembersRoles[targetId]) devMembersRoles[targetId] = [];
            if (Array.isArray(data.assigned_roles)) {
                data.assigned_roles.forEach(r => {
                    if (!devMembersRoles[targetId].includes(r.id)) {
                        devMembersRoles[targetId].push(r.id);
                    }
                });
            }

            // Mettre à jour le DOM dans la table du Roster
            const container = document.getElementById(`user-roles-${targetId}`);
            if (container && Array.isArray(data.assigned_roles)) {
                const placeholder = container.querySelector('.no-roles-placeholder');
                if (placeholder) placeholder.remove();

                data.assigned_roles.forEach(role => {
                    const existingBadge = document.getElementById(`badge-role-${targetId}-${role.id}`);
                    if (!existingBadge) {
                        const newBadge = createRoleBadgeElement(targetId, role, targetUsername);
                        container.appendChild(newBadge);
                    }
                    updateRoleDistributionGrid(role.id, targetUsername, 'add');
                });
            } else {
                // Si le joueur n'était pas encore dans la table, rafraîchissement rapide
                setTimeout(() => window.location.reload(), 600);
            }

        } else {
            showAlert(`❌ <strong>Erreur :</strong> ${data.error || 'Échec de l\'attribution'}`, 'danger');
        }
    } catch (err) {
        showAlert(`❌ <strong>Erreur réseau :</strong> ${err.message}`, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// ── SUPPRESSION / RÉVOCATION D'UN RÔLE ───────────────────────────────────

function confirmRemoveDevRole(userId, roleId, username, roleTitle) {
    showConfirmModal(
        'Révocation de Métier',
        `Voulez-vous vraiment retirer le métier <strong>« ${roleTitle} »</strong> à <strong>${username}</strong> ?`,
        () => executeRemoveDevRole(userId, roleId, username, roleTitle),
        'btn-danger',
        '🗑️'
    );
}

async function executeRemoveDevRole(userId, roleId, username, roleTitle) {
    const badgeEl = document.getElementById(`badge-role-${userId}-${roleId}`);
    if (badgeEl) {
        badgeEl.style.opacity = '0.5';
    }

    try {
        const formData = new FormData();
        formData.append('action', 'remove_role');
        formData.append('user_id', userId);
        formData.append('role_id', roleId);

        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success) {
            showAlert(`✅ <strong>Succès :</strong> Le métier « ${roleTitle} » a été retiré à ${username}.`, 'info');

            // Animation et suppression du badge dans le DOM
            if (badgeEl) {
                badgeEl.style.transition = 'all 0.25s ease';
                badgeEl.style.opacity = '0';
                badgeEl.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    badgeEl.remove();
                    const container = document.getElementById(`user-roles-${userId}`);
                    if (container && container.querySelectorAll('.dev-role-badge').length === 0) {
                        container.innerHTML = '<span class="text-muted small fst-italic no-roles-placeholder">Aucun métier</span>';
                    }
                }, 250);
            }

            // Mettre à jour le cache JavaScript
            if (devMembersRoles[userId]) {
                devMembersRoles[userId] = devMembersRoles[userId].filter(r => r !== roleId);
            }

            // Mettre à jour la grille de distribution en haut
            updateRoleDistributionGrid(roleId, username, 'remove');

        } else {
            if (badgeEl) badgeEl.style.opacity = '1';
            showAlert(`❌ <strong>Erreur :</strong> ${data.error || 'Impossible de révoquer ce métier.'}`, 'danger');
        }
    } catch (err) {
        if (badgeEl) badgeEl.style.opacity = '1';
        showAlert(`❌ <strong>Erreur réseau :</strong> ${err.message}`, 'danger');
    }
}

// ── HELPERS DOM POUR L'AFFICHAGE DYNAMIQUE ────────────────────────────────

function createRoleBadgeElement(userId, role, username) {
    const span = document.createElement('span');
    span.className = `badge ${role.badge_color || 'bg-secondary'} d-inline-flex align-items-center gap-1 dev-role-badge shadow-none`;
    span.id = `badge-role-${userId}-${role.id}`;
    span.setAttribute('data-user-id', userId);
    span.setAttribute('data-role-id', role.id);
    if (role.honor_title) span.title = role.honor_title;

    const label = document.createElement('span');
    label.textContent = `${role.icon || '🛠️'} ${role.title}`;
    span.appendChild(label);

    if (canManageTeamPermission) {
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn-close btn-close-white ms-1 dev-role-close-btn';
        closeBtn.style.fontSize = '0.55rem';
        closeBtn.style.width = '0.7em';
        closeBtn.style.height = '0.7em';
        closeBtn.style.opacity = '0.85';
        closeBtn.style.cursor = 'pointer';
        closeBtn.title = 'Retirer ce métier';
        closeBtn.addEventListener('click', () => {
            confirmRemoveDevRole(userId, role.id, username, role.title);
        });
        span.appendChild(closeBtn);
    }

    return span;
}

function updateRoleDistributionGrid(roleId, username, action) {
    const distContainer = document.getElementById(`role-dist-list-${roleId}`);
    if (!distContainer) return;

    if (action === 'remove') {
        const tags = distContainer.querySelectorAll('.dist-user-tag');
        tags.forEach(tag => {
            if (tag.getAttribute('data-username') === username) {
                tag.remove();
            }
        });
        if (distContainer.querySelectorAll('.dist-user-tag').length === 0) {
            distContainer.innerHTML = `<span class="text-muted fst-italic small no-member-tag" style="font-size: 0.75rem;">Aucun pour le moment</span>`;
        }
    } else if (action === 'add') {
        const placeholder = distContainer.querySelector('.no-member-tag');
        if (placeholder) placeholder.remove();

        // Vérifier si pas déjà présent
        const existing = Array.from(distContainer.querySelectorAll('.dist-user-tag')).some(t => t.getAttribute('data-username') === username);
        if (!existing && username) {
            const newTag = document.createElement('span');
            newTag.className = 'badge bg-light text-dark border dist-user-tag';
            newTag.setAttribute('data-username', username);
            newTag.style.fontSize = '0.75rem';
            newTag.textContent = `👤 ${username}`;
            distContainer.appendChild(newTag);
        }
    }
}

// ── ACTIONS SANDBOX QA & CRON ────────────────────────────────────────────

function triggerSandboxAction(subAction, btn) {
    showConfirmModal(
        'Commande Sandbox QA',
        'Exécuter cette commande de test directe sur votre fief ?',
        () => executeSandboxAction(subAction, btn),
        'btn-warning',
        '🧪'
    );
}

async function executeSandboxAction(subAction, btn) {
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Exécution...`;

    try {
        const formData = new FormData();
        formData.append('action', 'sandbox_action');
        formData.append('sub_action', subAction);

        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success) {
            showAlert(`✅ <strong>Succès QA :</strong> ${data.message}`, 'success');
        } else {
            showAlert(`❌ <strong>Erreur :</strong> ${data.error || 'Échec de la commande'}`, 'danger');
        }
    } catch (err) {
        showAlert(`❌ <strong>Erreur réseau :</strong> ${err.message}`, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

async function triggerBotCycle(btn) {
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Calculs en cours...`;

    const logBox = document.getElementById('bot-cycle-log');

    try {
        const formData = new FormData();
        formData.append('action', 'trigger_cron');

        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success) {
            showAlert(`✅ <strong>Live Ops :</strong> ${data.message}`, 'success');
            if (logBox) {
                logBox.classList.remove('d-none');
                logBox.innerHTML = `<strong>[${new Date().toLocaleTimeString()}] Résultat du cycle :</strong>\n` + JSON.stringify(data.details, null, 2);
            }
        } else {
            showAlert(`❌ <strong>Erreur :</strong> ${data.error || 'Échec du cycle'}`, 'danger');
        }
    } catch (err) {
        showAlert(`❌ <strong>Erreur réseau :</strong> ${err.message}`, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// ── RÉCOMPENSES FORGE XP ─────────────────────────────────────────────────

function openAwardXpModal(userId, username) {
    document.getElementById('award-user-id').value = userId;
    document.getElementById('award-username').value = username;
    const modalEl = document.getElementById('modal-award-xp');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        new bootstrap.Modal(modalEl).show();
    }
}

async function handleAwardXpSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('btn-submit-award');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Attribution...`;

    try {
        const formData = new FormData(form);
        formData.append('action', 'award_xp');

        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success) {
            showAlert(`⭐ ${data.message}`, 'success');
            setTimeout(() => window.location.reload(), 800);
        } else {
            showAlert(`❌ <strong>Erreur :</strong> ${data.error}`, 'danger');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (err) {
        showAlert(`❌ <strong>Erreur réseau :</strong> ${err.message}`, 'danger');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// ── GESTION DE LA RECETTE QA & CONTRÔLE DE SYNTAXE ────────────────────────

function setFeatureStatus(featureId, newStatus, featureTitle) {
    const btnClass = (newStatus === 'Validée') ? 'btn-success' : ((newStatus === 'Rejetée') ? 'btn-danger' : 'btn-warning');
    const icon = (newStatus === 'Validée') ? '✔' : ((newStatus === 'Rejetée') ? '✗' : '⏳');

    showConfirmModal(
        `Recette QA : Statut « ${newStatus} »`,
        `Voulez-vous modifier le statut de la fonctionnalité <strong>« ${featureTitle} »</strong> en <strong>${newStatus}</strong> ?`,
        () => executeSetFeatureStatus(featureId, newStatus, featureTitle),
        btnClass,
        icon
    );
}

async function executeSetFeatureStatus(featureId, newStatus, featureTitle) {
    try {
        const formData = new FormData();
        formData.append('action', 'update_feature_status');
        formData.append('feature_id', featureId);
        formData.append('new_status', newStatus);

        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success) {
            showAlert(`✅ <strong>Recette QA :</strong> ${data.message}`, 'success');

            // Mettre à jour le badge de la fonctionnalité
            const badgeEl = document.getElementById(`feature-badge-${featureId}`);
            if (badgeEl) {
                if (newStatus === 'Validée') {
                    badgeEl.className = 'badge bg-success-lt text-success border border-success px-2 py-1';
                    badgeEl.innerHTML = '✔ Validée';
                } else if (newStatus === 'Rejetée') {
                    badgeEl.className = 'badge bg-danger-lt text-danger border border-danger px-2 py-1';
                    badgeEl.innerHTML = '✗ Rejetée';
                } else {
                    badgeEl.className = 'badge bg-warning-lt text-warning border border-warning px-2 py-1';
                    badgeEl.innerHTML = '⏳ À tester';
                }
            }

            // Mettre à jour les statistiques de recette dans le bandeau
            if (data.stats) {
                const pendingEl = document.getElementById('qa-stat-pending');
                const validEl = document.getElementById('qa-stat-validated');
                const rejEl = document.getElementById('qa-stat-rejected');
                const navBadge = document.getElementById('nav-qa-pending-badge');

                if (pendingEl) pendingEl.textContent = data.stats.pending;
                if (validEl) validEl.textContent = data.stats.validated;
                if (rejEl) rejEl.textContent = data.stats.rejected;

                if (navBadge) {
                    if (data.stats.pending > 0) {
                        navBadge.textContent = data.stats.pending;
                        navBadge.classList.remove('d-none');
                    } else {
                        navBadge.classList.add('d-none');
                    }
                }
            }
        } else {
            showAlert(`❌ <strong>Erreur QA :</strong> ${data.error || 'Impossible de mettre à jour le statut.'}`, 'danger');
        }
    } catch (err) {
        showAlert(`❌ <strong>Erreur réseau :</strong> ${err.message}`, 'danger');
    }
}

async function handleRunSyntaxCheck(btn) {
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Analyse en cours (php -l)...`;

    try {
        const formData = new FormData();
        formData.append('action', 'run_syntax_check');

        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success && data.syntax) {
            const syn = data.syntax;
            showAlert(`⚡ <strong>Contrôle technique :</strong> ${data.message}`, syn.is_clean ? 'success' : 'danger');

            // Mettre à jour l'indicateur principal
            const badge = document.getElementById('badge-syntax-status');
            const icon = document.getElementById('icon-syntax-clean');
            const passedText = document.getElementById('text-syntax-passed');
            const totalText = document.getElementById('text-syntax-total');
            const dateText = document.getElementById('val-syntax-date');
            const durText = document.getElementById('val-syntax-dur');
            const errorsBox = document.getElementById('qa-syntax-errors-box');
            const errorsList = document.getElementById('qa-syntax-errors-list');

            if (badge) {
                badge.className = `badge ${syn.is_clean ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger'}`;
                badge.textContent = syn.is_clean ? '✔ 100% VALIDE' : '✗ ERREURS DÉTECTÉES';
            }
            if (icon) icon.textContent = syn.is_clean ? '🟢' : '🔴';
            if (passedText) passedText.textContent = syn.passed_count;
            if (totalText) totalText.textContent = syn.total_files;
            if (dateText) dateText.textContent = syn.checked_at;
            if (durText) durText.textContent = syn.duration_ms;

            if (errorsBox) {
                if (syn.is_clean || syn.errors.length === 0) {
                    errorsBox.classList.add('d-none');
                } else {
                    errorsBox.classList.remove('d-none');
                    if (errorsList) {
                        errorsList.innerHTML = syn.errors.map(err => `<li><strong>${err.file} :</strong> ${err.message}</li>`).join('');
                    }
                }
            }
        } else {
            showAlert(`❌ <strong>Erreur :</strong> ${data.error || 'Échec du contrôle syntaxique.'}`, 'danger');
        }
    } catch (err) {
        showAlert(`❌ <strong>Erreur réseau :</strong> ${err.message}`, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// ── MODULE MAILING LIST & DIFFUSION COMMUNAUTAIRE ─────────────────────────

let currentMailingPage = 1;
let mailingSearchTimeout = null;

function debounceMailingSearch() {
    clearTimeout(mailingSearchTimeout);
    mailingSearchTimeout = setTimeout(() => {
        loadMailingSubscribers(1);
    }, 350);
}

function resetMailingFilters() {
    const form = document.getElementById('mailing-filter-form');
    if (form) form.reset();
    loadMailingSubscribers(1);
}

function changeMailingPage(delta) {
    const targetPage = currentMailingPage + delta;
    if (targetPage >= 1) {
        loadMailingSubscribers(targetPage);
    }
}

async function loadMailingSubscribers(page = 1) {
    currentMailingPage = page;
    const searchVal = document.getElementById('filter-mailing-search')?.value || '';
    const optinVal = document.getElementById('filter-mailing-optin')?.value || 'all';
    const statusVal = document.getElementById('filter-mailing-status')?.value || 'all';
    const factionVal = document.getElementById('filter-mailing-faction')?.value || 'all';
    const roleVal = document.getElementById('filter-mailing-role')?.value || 'all';

    const params = new URLSearchParams({
        action: 'get_mailing_subscribers',
        search: searchVal,
        optin: optinVal,
        status: statusVal,
        faction: factionVal,
        role: roleVal,
        page: page,
        limit: 20
    });

    const tbody = document.getElementById('mailing-subscribers-tbody');
    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Chargement du registre des abonnés...</td></tr>`;
    }

    try {
        const res = await fetch('/api/dev_team.php?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success) {
            renderSubscribersTable(data.subscribers);

            // Mettre à jour les compteurs et KPIs
            const totalCountEl = document.getElementById('mailing-subscribers-count');
            if (totalCountEl) totalCountEl.textContent = data.total;

            if (data.stats) {
                const kpiTotal = document.getElementById('kpi-mailing-total');
                const kpiOptin = document.getElementById('kpi-mailing-optin');
                const kpiRate = document.getElementById('kpi-mailing-rate');
                const kpiCamp = document.getElementById('kpi-mailing-campaigns');
                const navBadge = document.getElementById('nav-mailing-count-badge');

                if (kpiTotal) kpiTotal.textContent = data.stats.total_users;
                if (kpiOptin) kpiOptin.textContent = data.stats.newsletter_subscribers;
                if (kpiRate) kpiRate.textContent = data.stats.optin_rate_percent + '%';
                if (kpiCamp) kpiCamp.textContent = data.stats.total_campaigns;
                if (navBadge) navBadge.textContent = data.stats.newsletter_subscribers;
            }

            // Pagination
            const pageInd = document.getElementById('mailing-pagination-indicator');
            const pageInfo = document.getElementById('mailing-page-info');
            const prevBtn = document.getElementById('btn-mailing-prev');
            const nextBtn = document.getElementById('btn-mailing-next');

            const totalPages = Math.max(1, Math.ceil(data.total / data.limit));
            if (pageInd) pageInd.textContent = `Page ${data.page} sur ${totalPages}`;
            if (pageInfo) {
                const startIdx = data.total === 0 ? 0 : (data.page - 1) * data.limit + 1;
                const endIdx = Math.min(data.total, data.page * data.limit);
                pageInfo.textContent = `Affichage de ${startIdx} à ${endIdx} sur ${data.total} Daimyōs`;
            }
            if (prevBtn) prevBtn.disabled = (data.page <= 1);
            if (nextBtn) nextBtn.disabled = (data.page >= totalPages);

        } else {
            if (tbody) tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">❌ ${data.error || 'Erreur lors du chargement des abonnés.'}</td></tr>`;
        }
    } catch (err) {
        if (tbody) tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">❌ Erreur réseau : ${err.message}</td></tr>`;
    }
}

function renderSubscribersTable(subscribers) {
    const tbody = document.getElementById('mailing-subscribers-tbody');
    if (!tbody) return;

    if (!Array.isArray(subscribers) || subscribers.length === 0) {
        tbody.innerHTML = `<tr class="no-subscribers-row"><td colspan="7" class="text-center text-muted py-4 fst-italic">Aucun Daimyō ne correspond à ces critères de recherche.</td></tr>`;
        return;
    }

    const factionMap = {
        'terran': { label: 'Tokugawa', color: 'bg-blue-lt text-blue' },
        'vorash': { label: 'Oda', color: 'bg-red-lt text-red' },
        'aethelis': { label: 'Takeda', color: 'bg-green-lt text-green' }
    };

    tbody.innerHTML = subscribers.map(s => {
        const fac = factionMap[s.faction] || { label: s.faction, color: 'bg-secondary-lt text-secondary' };
        
        let rolesBadges = '';
        if (s.is_admin) rolesBadges += `<span class="badge bg-danger text-white">👑 Admin</span> `;
        if (s.is_moderator) rolesBadges += `<span class="badge bg-warning text-white">🛡️ Modo</span> `;
        if (Array.isArray(s.dev_roles) && s.dev_roles.length > 0) {
            rolesBadges += s.dev_roles.map(dr => `<span class="badge bg-purple-lt">${dr.icon || '🛠️'} ${dr.title}</span>`).join(' ');
        }
        if (!rolesBadges) {
            rolesBadges = `<span class="badge bg-light text-muted">Joueur</span>`;
        }

        const activeBadge = s.is_active 
            ? `<span class="badge bg-success-lt" style="font-size: 0.68rem;">✔ Confirmé</span>`
            : `<span class="badge bg-warning-lt" style="font-size: 0.68rem;">⏳ En attente</span>`;

        const optinBadge = s.newsletter_optin
            ? `<span class="badge bg-success-lt text-success">✓ Abonné</span>`
            : `<span class="badge bg-secondary-lt text-muted">✗ Non abonné</span>`;

        const actionBtnText = s.newsletter_optin ? 'Désinscrire' : 'Abonner';

        return `
            <tr id="subscriber-row-${s.id}">
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-sm bg-blue-lt">👤</span>
                        <div>
                            <div class="fw-bold text-dark">${escapeHtml(s.username)}</div>
                            <div class="text-muted small font-monospace">ID #${s.id}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="text-dark font-monospace small">${escapeHtml(s.email)}</div>
                    ${activeBadge}
                </td>
                <td>
                    <span class="badge ${fac.color}">${escapeHtml(fac.label)}</span>
                </td>
                <td>
                    <div class="d-flex flex-wrap gap-1">${rolesBadges}</div>
                </td>
                <td class="text-center" id="subscriber-optin-cell-${s.id}">
                    ${optinBadge}
                </td>
                <td class="small text-muted font-monospace">
                    ${escapeHtml(s.created_at ? s.created_at.substring(0, 10) : '')}
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                            onclick="toggleSubscriberOptin(${s.id}, '${escapeHtml(addslashes(s.username))}', ${s.newsletter_optin ? 'true' : 'false'})"
                            title="Basculer le statut d'adhésion">
                        ${actionBtnText}
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function toggleSubscriberOptin(userId, username, currentOptin) {
    const actionLabel = currentOptin ? 'désinscrire de' : 'inscrire à';
    showConfirmModal(
        'Abonnement Newsletter (RGPD)',
        `Voulez-vous vraiment <strong>${actionLabel}</strong> la liste de diffusion le joueur <strong>${username}</strong> ?`,
        () => executeToggleSubscriberOptin(userId),
        currentOptin ? 'btn-danger' : 'btn-teal',
        '📢'
    );
}

async function executeToggleSubscriberOptin(userId) {
    try {
        const formData = new FormData();
        formData.append('action', 'toggle_subscriber_optin');
        formData.append('user_id', userId);

        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success) {
            showAlert(`✔ <strong>Mailing List :</strong> ${data.message}`, 'success');
            loadMailingSubscribers(currentMailingPage);
        } else {
            showAlert(`❌ <strong>Erreur :</strong> ${data.error || 'Impossible de modifier le statut.'}`, 'danger');
        }
    } catch (err) {
        showAlert(`❌ <strong>Erreur réseau :</strong> ${err.message}`, 'danger');
    }
}

function updateTargetGroupHint(targetGroup) {
    const hintEl = document.getElementById('target-group-hint');
    if (!hintEl) return;

    switch (targetGroup) {
        case 'all_optin':
            hintEl.textContent = "Missive envoyée uniquement aux joueurs ayant expressément consenti à la réception (Conforme RGPD).";
            break;
        case 'all_active':
            hintEl.textContent = "Attention : cette option envoie la missive à l'ensemble des comptes actifs du jeu.";
            break;
        case 'faction_terran':
            hintEl.textContent = "Destinée exclusivement aux seigneurs du Clan Tokugawa abonnés.";
            break;
        case 'faction_vorash':
            hintEl.textContent = "Destinée exclusivement aux seigneurs du Clan Oda abonnés.";
            break;
        case 'faction_aethelis':
            hintEl.textContent = "Destinée exclusivement aux seigneurs du Clan Takeda abonnés.";
            break;
        case 'dev_team':
            hintEl.textContent = "Communication interne transmise uniquement aux membres de l'équipe de développement.";
            break;
        case 'test_self':
            hintEl.textContent = "Mode test : la missive sera envoyée uniquement à votre adresse e-mail pour validation visuelle.";
            break;
        default:
            hintEl.textContent = "";
    }
}

function previewNewsletterModal() {
    const bodyText = document.getElementById('newsletter-body')?.value || '';
    const previewContainer = document.getElementById('newsletter-preview-container');
    const previewContent = document.getElementById('newsletter-preview-content');

    if (!previewContainer || !previewContent) return;

    if (!bodyText.trim()) {
        previewContent.innerHTML = `<span class="text-muted fst-italic">Veuillez saisir un texte dans la zone de message pour voir l'aperçu.</span>`;
    } else {
        const safeHtml = escapeHtml(bodyText).replace(/\n/g, '<br>');
        previewContent.innerHTML = `
            <div style="font-family: serif; color: #1e293b; font-size: 14px; line-height: 1.6;">
                <p><strong>Salutations, Honorable Daimyō [Nom du Joueur],</strong></p>
                <div>${safeHtml}</div>
                <hr style="margin: 15px 0;">
                <p class="text-muted" style="font-size: 11px;">(Pied de page RGPD & lien de désinscription automatique)</p>
            </div>
        `;
    }
    previewContainer.classList.remove('d-none');
}

function handleSendNewsletter(event) {
    event.preventDefault();
    const form = document.getElementById('form-compose-newsletter');
    const subject = document.getElementById('newsletter-subject')?.value.trim();
    const targetGroup = document.getElementById('newsletter-target-group')?.value;
    const bodyHtml = document.getElementById('newsletter-body')?.value.trim();

    if (!subject || !bodyHtml) {
        showAlert("Veuillez renseigner le sujet et le contenu de la missive.", 'warning');
        return;
    }

    const groupSelect = document.getElementById('newsletter-target-group');
    const groupLabel = groupSelect?.options[groupSelect.selectedIndex]?.text || targetGroup;

    showConfirmModal(
        'Expédition de la Missive Impériale',
        `Êtes-vous certain de vouloir expédier cette missive au groupe <strong>« ${escapeHtml(groupLabel)} »</strong> ?<br><br>
         <span class="text-muted small">• Sujet : <em>${escapeHtml(subject)}</em></span>`,
        () => executeSendNewsletter({ subject, target_group: targetGroup, body_html: bodyHtml }),
        'btn-teal',
        '🚀'
    );
}

async function executeSendNewsletter(payload) {
    const btn = document.getElementById('btn-submit-newsletter');
    const originalText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Expédition en cours...`;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'send_newsletter_campaign');
        formData.append('subject', payload.subject);
        formData.append('target_group', payload.target_group);
        formData.append('body_html', payload.body_html);

        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success) {
            // Fermer la modale
            const modalEl = document.getElementById('modal-compose-newsletter');
            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
            }
            document.getElementById('form-compose-newsletter')?.reset();
            document.getElementById('newsletter-preview-container')?.classList.add('d-none');

            showAlert(`🎉 <strong>Succès :</strong> ${data.message} (+35 XP Forge accordés)`, 'success');
            loadMailingSubscribers(currentMailingPage);
            loadMailingCampaignHistory();
        } else {
            showAlert(`❌ <strong>Échec :</strong> ${data.error || 'Erreur lors de l\'envoi de la campagne.'}`, 'danger');
        }
    } catch (err) {
        showAlert(`❌ <strong>Erreur réseau :</strong> ${err.message}`, 'danger');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }
}

async function loadMailingCampaignHistory() {
    try {
        const res = await fetch('/api/dev_team.php?action=get_campaign_history', {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success && Array.isArray(data.history)) {
            const tbody = document.getElementById('mailing-campaigns-tbody');
            if (!tbody) return;

            if (data.history.length === 0) {
                tbody.innerHTML = `<tr class="no-campaign-row"><td colspan="6" class="text-center text-muted py-4 fst-italic">Aucune missive groupée n'a encore été expédiée.</td></tr>`;
                return;
            }

            tbody.innerHTML = data.history.map(c => `
                <tr>
                    <td class="small font-monospace">${escapeHtml(c.sent_at)}</td>
                    <td class="fw-bold text-dark">${escapeHtml(c.subject)}</td>
                    <td><span class="badge bg-secondary-lt">${escapeHtml(c.target_group)}</span></td>
                    <td class="text-center font-monospace fw-bold text-teal">${parseInt(c.recipient_count, 10)}</td>
                    <td class="small">${escapeHtml(c.sender_name)}</td>
                    <td>
                        <span class="badge ${c.status === 'sent' ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger'}">
                            ${c.status === 'sent' ? '✔ Expédiée' : '✗ Échec'}
                        </span>
                    </td>
                </tr>
            `).join('');
        }
    } catch (e) {}
}

function handleExportMailingCsv() {
    const searchVal = document.getElementById('filter-mailing-search')?.value || '';
    const optinVal = document.getElementById('filter-mailing-optin')?.value || 'all';
    const statusVal = document.getElementById('filter-mailing-status')?.value || 'all';
    const factionVal = document.getElementById('filter-mailing-faction')?.value || 'all';
    const roleVal = document.getElementById('filter-mailing-role')?.value || 'all';

    const params = new URLSearchParams({
        action: 'export_subscribers_csv',
        search: searchVal,
        optin: optinVal,
        status: statusVal,
        faction: factionVal,
        role: roleVal
    });

    window.location.href = '/api/dev_team.php?' + params.toString();
}

// ═══════════════════════════════════════════════════════════════════════════
// GAME ELEVATE DESIGNER : VITESSES, ARPENTAGE & OASIS
// ═══════════════════════════════════════════════════════════════════════════

function applyDevSpeedPreset(gSpeed, rSpeed, fSpeed) {
    const gInput = document.getElementById('dev_game_speed_input');
    const gRange = document.getElementById('dev_game_speed_range');
    const bG = document.getElementById('dev_badge_game_speed');
    if (gInput && gRange) {
        gInput.value = gSpeed;
        gRange.value = gSpeed;
        if (bG) bG.textContent = 'x' + gSpeed;
    }

    const rInput = document.getElementById('dev_resource_speed_input');
    const rRange = document.getElementById('dev_resource_speed_range');
    const bR = document.getElementById('dev_badge_resource_speed');
    if (rInput && rRange) {
        rInput.value = rSpeed;
        rRange.value = rSpeed;
        if (bR) bR.textContent = 'x' + rSpeed;
    }

    const fInput = document.getElementById('dev_fleet_speed_input');
    const fRange = document.getElementById('dev_fleet_speed_range');
    const bF = document.getElementById('dev_badge_fleet_speed');
    if (fInput && fRange) {
        fInput.value = fSpeed;
        fRange.value = fSpeed;
        if (bF) bF.textContent = 'x' + fSpeed;
    }
}

async function saveDevGameSettings(event) {
    if (event) event.preventDefault();
    const gForm = document.getElementById('devGameSettingsForm');
    const formData = gForm ? new FormData(gForm) : new FormData();
    formData.append('action', 'save_game_settings');

    const submitBtn = gForm ? gForm.querySelector('button[type="submit"]') : null;
    const oldBtnText = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enregistrement...';
    }

    try {
        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            showAlert(`💾 <strong>Succès :</strong> ${data.message} (+30 XP Forge accordés)`, 'success');
        } else {
            showAlert(`❌ <strong>Erreur :</strong> ${data.error || "Impossible d'enregistrer les paramètres."}`, 'danger');
        }
    } catch (err) {
        showAlert(`❌ <strong>Erreur réseau :</strong> ${err.message}`, 'danger');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = oldBtnText;
        }
    }
}

async function generateDevWorld(event) {
    if (event) event.preventDefault();
    const count = document.getElementById('dev_planet_count_input')?.value || 12;
    const radius = document.getElementById('dev_radius_range')?.value || 12;
    const clearUninhabited = document.getElementById('dev_clear_uninhabited')?.checked ? '1' : '0';

    showConfirmModal(
        "Expansion Provinciale Féodale",
        `Voulez-vous générer <strong>${count}</strong> nouveaux fiefs et terres procédurales dans un rayon de <strong>&plusmn;${radius}</strong> provinces ?`,
        async () => {
            const formData = new FormData();
            formData.append('action', 'generate_world');
            formData.append('planet_count', count);
            formData.append('radius', radius);
            formData.append('clear_uninhabited', clearUninhabited);

            try {
                const res = await fetch('/api/dev_team.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    const pList = (data.planets || []).slice(0, 5).map(p => `• <strong>${escapeHtml(p.name)}</strong> ${escapeHtml(p.coords)} (${escapeHtml(p.type)})`).join('<br>');
                    const moreTxt = (data.planets || []).length > 5 ? `<br>... et ${(data.planets || []).length - 5} autres domaines.` : '';
                    showAlert(
                        `🗾 <strong>Expansion Réussie :</strong> <strong>${data.generated_count}</strong> terres et fiefs ont été déployés (+40 XP Forge accordés) !<br><br>${pList}${moreTxt}`,
                        'success'
                    );
                    setTimeout(() => location.reload(), 2500);
                } else {
                    showAlert(`❌ <strong>Erreur :</strong> ${data.error || "Impossible d'arpenter les terres."}`, 'danger');
                }
            } catch (e) {
                showAlert(`❌ <strong>Erreur réseau :</strong> ${e.message}`, 'danger');
            }
        },
        'btn-success',
        '🗾'
    );
}

async function executeDevRepopulateOases() {
    const density = parseFloat(document.getElementById('dev_repop_density')?.value) || 2.0;
    const radius = parseInt(document.getElementById('dev_repop_radius')?.value, 10) || 28;
    const clearUnoccupied = document.getElementById('dev_repop_clear_unoccupied')?.checked ? '1' : '0';

    showConfirmModal(
        "Génération des Oasis Naturelles",
        `Confirmez-vous la génération et l'équilibrage des oasis avec une densité cible de <strong>${density}%</strong> sur un rayon de <strong>${radius}</strong> tuiles ?`,
        async () => {
            const formData = new FormData();
            formData.append('action', 'repopulate_oases');
            formData.append('density_percent', density);
            formData.append('radius', radius);
            formData.append('clear_unoccupied', clearUnoccupied);

            try {
                const res = await fetch('/api/dev_team.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    showAlert(`🌿 <strong>Succès :</strong> ${data.message} (+35 XP Forge accordés)`, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert(`❌ <strong>Erreur :</strong> ${data.error || "Impossible de générer les oasis."}`, 'danger');
                }
            } catch (e) {
                showAlert(`❌ <strong>Erreur réseau :</strong> ${e.message}`, 'danger');
            }
        },
        'btn-success',
        '🌿'
    );
}

// Pagination des oasis dans Studio Dev
let devOasesPerPage = 15;
let currentDevOasesPage = 1;

function initDevOasesPagination() {
    const rows = document.querySelectorAll('.dev-oasis-table-row');
    if (rows.length === 0) return;
    const select = document.getElementById('devOasesPerPageSelect');
    if (select) devOasesPerPage = parseInt(select.value, 10) || 15;
    renderDevOasesPage(currentDevOasesPage);
}

function changeDevOasesPerPage(newVal) {
    devOasesPerPage = parseInt(newVal, 10) || 15;
    currentDevOasesPage = 1;
    renderDevOasesPage(currentDevOasesPage);
}

function renderDevOasesPage(page) {
    const rows = Array.from(document.querySelectorAll('.dev-oasis-table-row'));
    const totalOases = rows.length;
    if (totalOases === 0) return;
    const totalPages = Math.ceil(totalOases / devOasesPerPage) || 1;
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    currentDevOasesPage = page;

    const startIdx = (page - 1) * devOasesPerPage;
    const endIdx = Math.min(startIdx + devOasesPerPage, totalOases);

    rows.forEach((row, i) => {
        row.style.display = (i >= startIdx && i < endIdx) ? '' : 'none';
    });

    const startEl = document.getElementById('devOasesPaginationStart');
    const endEl = document.getElementById('devOasesPaginationEnd');
    const totalEl = document.getElementById('devOasesPaginationTotal');
    if (startEl) startEl.textContent = (startIdx + 1);
    if (endEl) endEl.textContent = endIdx;
    if (totalEl) totalEl.textContent = totalOases;

    const listEl = document.getElementById('devOasesPaginationList');
    if (!listEl) return;

    let html = '';
    html += `<li class="page-item ${page === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="renderDevOasesPage(${page - 1}); return false;">&laquo;</a>
    </li>`;

    let startPage = Math.max(1, page - 2);
    let endPage = Math.min(totalPages, page + 2);
    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="renderDevOasesPage(1); return false;">1</a></li>`;
        if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
    }

    for (let p = startPage; p <= endPage; p++) {
        html += `<li class="page-item ${p === page ? 'active' : ''}">
            <a class="page-link" href="#" onclick="renderDevOasesPage(${p}); return false;">${p}</a>
        </li>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="#" onclick="renderDevOasesPage(${totalPages}); return false;">${totalPages}</a></li>`;
    }

    html += `<li class="page-item ${page === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="renderDevOasesPage(${page + 1}); return false;">&raquo;</a>
    </li>`;

    listEl.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', () => {
    initDevOasesPagination();
});

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function addslashes(str) {
    if (!str) return '';
    return String(str).replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
}
</script>
