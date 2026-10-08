<?php
/**
 * Vue Studio Dev Team & Métiers du Jeu Vidéo (OpenShogun)
 * Architecture Restructurée par Métier (UN ONGLET PAR MÉTIER) avec sous-navigation de modules
 * Modules modulaires situés dans views/studio/<slug_metier>/<module>.php
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
            <div class="empty-icon text-danger" style="font-size: 3rem;"><i class="fa-solid fa-lock"></i></div>
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
$canManageSprints = $devEngine->hasPermission($userId, 'sprints.manage') || $auth->isAdmin();
$canReviewQA      = $devEngine->hasPermission($userId, 'bugs.manage') || $devEngine->hasPermission($userId, 'debug.sandbox') || $auth->isAdmin();
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

// Liste de tous les utilisateurs humains pour l'attribution de métiers
$allUsersList = [];
if ($canManageTeam) {
    $stmtUsers = $db->query("SELECT id, username FROM users WHERE is_bot = 0 ORDER BY username ASC");
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

// ── Matrice des Métiers autorisés pour l'utilisateur connecté ────────────────
$allowedMetiers = $devEngine->getAllowedMetiers($userId, $auth->isAdmin());
if (empty($allowedMetiers)) {
    http_response_code(403);
    ?>
    <div class="container-xl py-5 text-center">
        <div class="empty">
            <div class="empty-icon text-danger" style="font-size: 3rem;"><i class="fa-solid fa-lock"></i></div>
            <p class="empty-title">Accès Interdit au Studio</p>
            <p class="empty-subtitle text-muted">Aucun métier ou onglet du studio de développement n'est habilité pour votre profil.</p>
        </div>
    </div>
    <?php
    return;
}

// ── Résolution de l'Onglet Métier et du Sous-Module Actifs ───────────────────
// Rétrocompatibilité avec les anciens paramètres ?tab=...
$tabToMetierMap = [
    'roster'           => ['metier' => 'roster', 'module' => 'members'],
    'forge'            => ['metier' => 'roster', 'module' => 'forge-journal'],
    'game_speeds'      => ['metier' => 'game-elevate-designer', 'module' => 'speed-balancing'],
    'world_expansion'  => ['metier' => 'game-elevate-designer', 'module' => 'shogunat-survey'],
    'oases_ecosystem'  => ['metier' => 'game-elevate-designer', 'module' => 'oasis-ecosystem'],
    'combat_simulator'    => ['metier' => 'game-elevate-designer', 'module' => 'combat-simulator'],
    'village_simulator'   => ['metier' => 'game-elevate-designer', 'module' => 'village-life-simulator'],
    'building_derivation' => ['metier' => 'game-elevate-designer', 'module' => 'building-derivation'],
    'mailing'             => ['metier' => 'community-manager', 'module' => 'mailing-list'],
    'qa'               => ['metier' => 'qa-tester', 'module' => 'qa-validation'],
    'sandbox'          => ['metier' => 'qa-tester', 'module' => 'sandbox'],
    'system'           => ['metier' => 'backend-dev', 'module' => 'system-monitoring'],
    'lore'             => ['metier' => 'narrative-designer', 'module' => 'lore'],
];

$requestedMetier = trim((string)($_GET['metier'] ?? ''));
$requestedModule = trim((string)($_GET['module'] ?? ''));

if ($requestedMetier === '' && isset($_GET['tab']) && isset($tabToMetierMap[$_GET['tab']])) {
    $requestedMetier = $tabToMetierMap[$_GET['tab']]['metier'];
    if ($requestedModule === '') {
        $requestedModule = $tabToMetierMap[$_GET['tab']]['module'];
    }
}

if ($requestedMetier !== '' && in_array($requestedMetier, $allowedMetiers, true)) {
    $activeMetier = $requestedMetier;
} else {
    $activeMetier = $allowedMetiers[0];
}

$activeMetierConfig = DevTeamEngine::STUDIO_METIERS[$activeMetier];
$availableModules = array_keys($activeMetierConfig['modules']);

if ($requestedModule !== '' && in_array($requestedModule, $availableModules, true)) {
    $activeModule = $requestedModule;
} else {
    $activeModule = $activeMetierConfig['default_module'] ?? $availableModules[0];
}

// Données requises pour les modules du Game Elevate Designer
$gameSettings = [];
$mapTileStats = [];
$mapTileCategories = [];
$oasisStats = ['total_oases' => 0, 'captured_oases' => 0, 'wild_oases' => 0, 'total_wild_animals' => 0];
$allOases = [];

if ($activeMetier === 'game-elevate-designer') {
    require_once __DIR__ . '/../core/GameConfig.php';
    require_once __DIR__ . '/../core/WorldGenerator.php';
    require_once __DIR__ . '/../core/OasisEngine.php';
    $gameSettings = GameConfig::load();

    if ($activeModule === 'shogunat-survey') {
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

        $stmtOasesCoords = $db->query("SELECT coord_x, coord_y FROM oases");
        $oasisCoords = [];
        while ($row = $stmtOasesCoords->fetch(PDO::FETCH_ASSOC)) {
            $oasisCoords[((int)$row['coord_x']) . ':' . ((int)$row['coord_y'])] = true;
        }

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
                'icon' => '<i class="fa-solid fa-chess-rook me-1"></i>',
                'badge_bg' => 'bg-purple-lt text-purple',
                'bar_color' => 'bg-purple',
                'img' => '/public/assets/map/tile_village.jpg?v=2',
                'desc' => 'Capitales et fiefs colonisés par les joueurs et clans IA.'
            ],
            'free_lands' => [
                'name' => 'Terres Libres',
                'sub' => 'Emplacements arpentés',
                'count' => $mapTileStats['free_lands'],
                'icon' => '<i class="fa-solid fa-flag me-1"></i>',
                'badge_bg' => 'bg-secondary-lt text-secondary',
                'bar_color' => 'bg-secondary',
                'img' => '/public/assets/map/tile_plains.jpg?v=2',
                'desc' => 'Emplacements arpentés disponibles pour fondation de colonie.'
            ],
            'castles' => [
                'name' => 'Donjons Sacrés',
                'sub' => '現存十二天守',
                'count' => $mapTileStats['castles'],
                'icon' => '<i class="fa-solid fa-crown me-1"></i>',
                'badge_bg' => 'bg-warning-lt text-warning',
                'bar_color' => 'bg-warning',
                'img' => '/public/assets/map/tile_authentic_castle.jpg?v=2',
                'desc' => 'Les 12 forteresses impériales historiques du Japon féodal.'
            ],
            'oases' => [
                'name' => 'Oasis Naturelles',
                'sub' => 'Faune & Bonus',
                'count' => $mapTileStats['oases'],
                'icon' => '<i class="fa-solid fa-leaf me-1"></i>',
                'badge_bg' => 'bg-teal-lt text-teal',
                'bar_color' => 'bg-teal',
                'img' => '/public/assets/map/tile_lake.jpg?v=2',
                'desc' => 'Havres de faune sauvage procurant des bonus de production.'
            ],
            'plains' => [
                'name' => 'Plaines Fertiles',
                'sub' => 'Prairies & Terres',
                'count' => $mapTileStats['plains'],
                'icon' => '<i class="fa-solid fa-wheat-awn me-1"></i>',
                'badge_bg' => 'bg-lime-lt text-lime',
                'bar_color' => 'bg-lime',
                'img' => '/public/assets/map/tile_plains.jpg?v=2',
                'desc' => 'Terres arables verdoyantes et plaines propices à l\'agriculture.'
            ],
            'forest' => [
                'name' => 'Forêts de Cèdres',
                'sub' => 'Bois d\'Œuvre',
                'count' => $mapTileStats['forest'],
                'icon' => '<i class="fa-solid fa-tree me-1"></i>',
                'badge_bg' => 'bg-green-lt text-green',
                'bar_color' => 'bg-green',
                'img' => '/public/assets/map/tile_forest.jpg?v=2',
                'desc' => 'Massifs forestiers anciens riches en cèdres pour la charpente.'
            ],
            'mountain' => [
                'name' => 'Monts & Granit',
                'sub' => 'Gisements de Pierre',
                'count' => $mapTileStats['mountain'],
                'icon' => '<i class="fa-solid fa-mountain me-1"></i>',
                'badge_bg' => 'bg-secondary-lt text-secondary',
                'bar_color' => 'bg-secondary',
                'img' => '/public/assets/map/tile_mountain.jpg?v=2',
                'desc' => 'Coteaux rocheux renfermant les filons de granit et de minerai.'
            ],
            'hills' => [
                'name' => 'Collines Sanctuaire',
                'sub' => 'Sérénité & Esprits',
                'count' => $mapTileStats['hills'],
                'icon' => '<i class="fa-solid fa-torii-gate me-1"></i>',
                'badge_bg' => 'bg-orange-lt text-orange',
                'bar_color' => 'bg-orange',
                'img' => '/public/assets/map/tile_hills.jpg?v=2',
                'desc' => 'Hautes collines baignées de ferveur spirituelle pour les sanctuaires.'
            ],
            'lake' => [
                'name' => 'Lacs & Rivières',
                'sub' => 'Eaux Sacrées',
                'count' => $mapTileStats['lake'],
                'icon' => '<i class="fa-solid fa-water me-1"></i>',
                'badge_bg' => 'bg-azure-lt text-azure',
                'bar_color' => 'bg-azure',
                'img' => '/public/assets/map/tile_lake.jpg?v=2',
                'desc' => 'Plans d\'eau navigables et canaux essentiels à l\'irrigation.'
            ]
        ];
    }

    if ($activeModule === 'oasis-ecosystem') {
        $oasisEngine = new OasisEngine();
        $oasisStats = $oasisEngine->getOasisStatistics();

        $allOases = $db->query("
            SELECT o.*, 
                   p.name as owner_planet_name, 
                   u.username as owner_username 
            FROM oases o 
            LEFT JOIN planets p ON o.owner_planet_id = p.id 
            LEFT JOIN users u ON p.user_id = u.id 
            ORDER BY o.owner_planet_id DESC, o.id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allOases as &$oRow) {
            $oRow['garrison'] = $oasisEngine->getOasisGarrison((int)$oRow['id']);
        }
        unset($oRow);
    }
}

$isForbiddenRedirect = !empty($_GET['forbidden']);
?>

<div class="container-fluid px-0 py-3">

    <!-- ── HÉROS BANNER : STUDIO DEV & PROFIL CRÉATEUR ── -->
    <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #1e1e2d 0%, #2a223f 100%); color: #fff; border-radius: 12px; overflow: hidden;">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <div class="avatar avatar-xl rounded-circle shadow" style="background: linear-gradient(135deg, #8b5cf6, #ec4899); font-size: 2rem; border: 3px solid rgba(255,255,255,0.2);">
                        <i class="fa-solid fa-hammer text-white"></i>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <span class="badge bg-purple-lt text-white px-2 py-1" style="background: rgba(139, 92, 246, 0.3) !important;">
                            <i class="fa-solid fa-gamepad me-1"></i> OpenShogun Studio Lab
                        </span>
                        <span class="badge bg-warning text-dark fw-bold">
                            Niveau <?= $levelInfo['level'] ?> &bull; <?= htmlspecialchars($levelInfo['title']) ?>
                        </span>
                        <?php if ($auth->isAdmin()): ?>
                            <span class="badge bg-danger"><i class="fa-solid fa-crown text-warning me-1"></i> Administrateur Suprême</span>
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
                                    <?= $r['icon'] ?? '<i class="fa-solid fa-hammer"></i>' ?> <?= htmlspecialchars($r['title']) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Jauge de Forge XP -->
                <div class="col-12 col-md-4">
                    <div class="p-3 rounded" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);">
                        <div class="d-flex justify-content-between align-items-center mb-1 small text-white-50">
                            <span><i class="fa-solid fa-hammer me-1 text-warning"></i> Forge XP : <strong><?= number_format($levelInfo['xp']) ?> XP</strong></span>
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

    <!-- ── ALERTE REDIRECTION FORCÉE (MÉTIER REQUIS) ── -->
    <?php if ($isForbiddenRedirect): ?>
    <div class="alert alert-warning alert-dismissible shadow-sm mb-3" role="alert">
        <div class="d-flex align-items-center gap-2">
            <span class="fs-2 text-warning"><i class="fa-solid fa-lock"></i></span>
            <div>
                <h4 class="alert-title mb-1">Accès Restreint &bull; Métier Requis</h4>
                <div class="text-muted small">Vous avez été redirigé(e) vers un métier autorisé car votre profil ne possède pas le métier requis pour accéder à l'espace demandé.</div>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════════════════
         1. NAVIGATION PRINCIPALE : UN ONGLET PAR MÉTIER
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="dev-metiers-tabs" role="tablist">
                <?php foreach ($allowedMetiers as $mSlug): 
                    $mCfg = DevTeamEngine::STUDIO_METIERS[$mSlug];
                    $isActive = ($activeMetier === $mSlug);
                ?>
                <li class="nav-item">
                    <a class="nav-link py-3 px-3 <?= $isActive ? 'active fw-bold' : '' ?>" 
                       href="/?page=dev_team&metier=<?= urlencode($mSlug) ?>">
                        <?= $mCfg['icon'] ?>
                        <span class="ms-1"><?= htmlspecialchars($mCfg['title']) ?></span>
                        <?php if ($mSlug === 'qa-tester' && $qaStats['pending'] > 0): ?>
                            <span class="badge bg-danger text-white ms-1"><?= $qaStats['pending'] ?></span>
                        <?php elseif ($mSlug === 'community-manager' && $mailingStats['newsletter_subscribers'] > 0): ?>
                            <span class="badge bg-teal-lt text-teal ms-1"><?= $mailingStats['newsletter_subscribers'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         2. SOUS-NAVIGATION : PILLS DES MODULES DU MÉTIER ACTIF
         ═══════════════════════════════════════════════════════════════════════ -->
    <?php if (count($activeMetierConfig['modules']) > 1): ?>
    <div class="card mb-3 border-0 shadow-sm" style="background: #f8fafc;">
        <div class="card-body p-2">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge <?= $activeMetierConfig['badge_color'] ?> px-2 py-1 font-game">
                        <?= $activeMetierConfig['icon'] ?> <?= htmlspecialchars($activeMetierConfig['title']) ?>
                    </span>
                    <ul class="nav nav-pills card-header-pills m-0">
                        <?php foreach ($activeMetierConfig['modules'] as $modSlug => $modCfg): 
                            $isModActive = ($activeModule === $modSlug);
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $isModActive ? 'active fw-bold shadow-xs' : 'text-secondary' ?> py-1 px-3" 
                               href="/?page=dev_team&metier=<?= urlencode($activeMetier) ?>&module=<?= urlencode($modSlug) ?>">
                                <?= $modCfg['icon'] ?> <?= htmlspecialchars($modCfg['title']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="text-secondary small fst-italic pe-2 d-none d-md-block">
                    <i class="fa-solid fa-certificate text-warning me-1"></i><?= htmlspecialchars($activeMetierConfig['honor_title']) ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════════════════
         3. CONTENU DU MODULE SÉLECTIONNÉ (INCLUSION DYNAMIQUE)
         ═══════════════════════════════════════════════════════════════════════ -->
    <div class="studio-module-wrapper">
        <?php
        $moduleRelativePath = $activeMetierConfig['modules'][$activeModule]['file'] ?? null;
        $moduleFullPath = $moduleRelativePath ? (__DIR__ . '/studio/' . $moduleRelativePath) : null;

        if ($moduleFullPath && file_exists($moduleFullPath)) {
            require $moduleFullPath;
        } else {
            ?>
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="card-body">
                    <div class="empty-icon text-muted fs-1 mb-3"><i class="fa-solid fa-puzzle-piece"></i></div>
                    <h3 class="card-title">Module en cours de développement</h3>
                    <p class="text-muted">Le module demandé (<code><?= htmlspecialchars($activeModule) ?></code>) n'a pas encore été finalisé pour ce métier.</p>
                </div>
            </div>
            <?php
        }
        ?>
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
                        <i class="fa-solid fa-hammer text-primary me-1"></i>Attribution des Métiers de Développement
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
                                                <i class="fa-solid fa-check text-success me-1"></i>Déjà assigné à ce joueur
                                            </span>
                                        </span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 4. Alerte si tous les rôles sont déjà assignés -->
                    <div id="assign-all-roles-taken" class="alert alert-info d-none mt-2 py-2 small mb-0">
                        <i class="fa-solid fa-circle-info text-info me-1"></i>Ce joueur possède déjà l'ensemble des 8 métiers de la Dev Team.
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between align-items-center">
                    <span class="text-muted small" id="assign-selection-count">0 métier sélectionné</span>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-purple" id="btn-submit-assign" disabled>
                            <i class="fa-solid fa-plus me-1"></i>Assigner les métiers
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL UNIVERSELLE DE CONFIRMATION TABLER.IO
     ═══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="dev-universal-confirm-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div id="dev-confirm-icon-box" class="fs-1 text-warning mb-2"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <h3 id="dev-confirm-title" class="fw-bold mb-2">Confirmation Requise</h3>
                <div id="dev-confirm-message" class="text-secondary small mb-3">Êtes-vous sûr(e) de vouloir exécuter cette opération ?</div>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" id="dev-confirm-btn-action" class="btn btn-danger w-100">Confirmer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS JAVASCRIPT GLOBAUX DU STUDIO -->
<script>
const MEMBERS_ROLES_MAP = <?= json_encode($membersRolesMap) ?>;
const ROLES_METADATA = <?= json_encode(DevTeamEngine::ROLES) ?>;

function showDevAlert(type, message) {
    const alertBox = document.getElementById('dev-alert');
    const contentBox = document.getElementById('dev-alert-content');
    if (!alertBox || !contentBox) return;

    alertBox.className = `alert alert-${type} alert-dismissible shadow-sm mb-3`;
    contentBox.innerHTML = message;
    alertBox.classList.remove('d-none');
    window.scrollTo({ top: alertBox.offsetTop - 80, behavior: 'smooth' });
}

function showConfirmModal(title, message, onConfirm, btnClass = 'btn-danger', iconHtml = '<i class="fa-solid fa-triangle-exclamation"></i>') {
    const modalEl = document.getElementById('dev-universal-confirm-modal');
    if (!modalEl) {
        if (confirm(message.replace(/<[^>]*>?/gm, ''))) onConfirm();
        return;
    }
    document.getElementById('dev-confirm-title').innerHTML = title;
    document.getElementById('dev-confirm-message').innerHTML = message;
    document.getElementById('dev-confirm-icon-box').innerHTML = iconHtml;

    const actionBtn = document.getElementById('dev-confirm-btn-action');
    actionBtn.className = `btn ${btnClass} w-100`;

    const newBtn = actionBtn.cloneNode(true);
    actionBtn.parentNode.replaceChild(newBtn, actionBtn);

    newBtn.addEventListener('click', () => {
        const bsModal = bootstrap.Modal.getInstance(modalEl);
        if (bsModal) bsModal.hide();
        onConfirm();
    });

    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
}

// ═══════════════════════════════════════════════════════════════════════════
// GESTION DU ROSTER ET DES MÉTIERS
// ═══════════════════════════════════════════════════════════════════════════
function onAssignUserChanged(userId) {
    userId = parseInt(userId, 10);
    const box = document.getElementById('assign-user-current-roles-box');
    const list = document.getElementById('assign-user-current-roles-list');
    const allTakenAlert = document.getElementById('assign-all-roles-taken');
    const selectorBlock = document.getElementById('assign-roles-selector-block');

    document.querySelectorAll('.assign-role-checkbox').forEach(cb => {
        cb.checked = false;
        cb.disabled = false;
    });
    document.querySelectorAll('.role-assigned-tag').forEach(tag => tag.classList.add('d-none'));
    document.querySelectorAll('.form-selectgroup-item').forEach(item => item.style.opacity = '1');

    if (!userId || !MEMBERS_ROLES_MAP[userId]) {
        if (box) box.classList.add('d-none');
        if (allTakenAlert) allTakenAlert.classList.add('d-none');
        if (selectorBlock) selectorBlock.classList.remove('d-none');
        updateAssignSubmitBtnState();
        return;
    }

    const currentRoles = MEMBERS_ROLES_MAP[userId];
    if (box && list) {
        if (currentRoles.length > 0) {
            box.classList.remove('d-none');
            list.innerHTML = currentRoles.map(rid => {
                const r = ROLES_METADATA[rid] || { title: rid, icon: '', badge_color: 'bg-secondary-lt' };
                return `<span class="badge ${r.badge_color} border me-1">${r.icon} ${r.title}</span>`;
            }).join('');
        } else {
            box.classList.add('d-none');
        }
    }

    let availableCount = 0;
    currentRoles.forEach(rid => {
        const cb = document.querySelector(`.assign-role-checkbox[data-role-id="${rid}"]`);
        const item = document.getElementById(`assign-role-item-${rid}`);
        if (cb) {
            cb.checked = false;
            cb.disabled = true;
        }
        if (item) {
            item.style.opacity = '0.5';
            const tag = item.querySelector('.role-assigned-tag');
            if (tag) tag.classList.remove('d-none');
        }
    });

    document.querySelectorAll('.assign-role-checkbox').forEach(cb => {
        if (!cb.disabled) availableCount++;
    });

    if (availableCount === 0) {
        if (allTakenAlert) allTakenAlert.classList.remove('d-none');
        if (selectorBlock) selectorBlock.classList.add('d-none');
    } else {
        if (allTakenAlert) allTakenAlert.classList.add('d-none');
        if (selectorBlock) selectorBlock.classList.remove('d-none');
    }

    updateAssignSubmitBtnState();
}

function selectAllAvailableRoles() {
    document.querySelectorAll('.assign-role-checkbox:not(:disabled)').forEach(cb => cb.checked = true);
    updateAssignSubmitBtnState();
}

function clearSelectedRoles() {
    document.querySelectorAll('.assign-role-checkbox:not(:disabled)').forEach(cb => cb.checked = false);
    updateAssignSubmitBtnState();
}

function updateAssignSubmitBtnState() {
    const btn = document.getElementById('btn-submit-assign');
    const userSelect = document.getElementById('assign-role-user-id');
    const checkedCount = document.querySelectorAll('.assign-role-checkbox:checked').length;
    const countSpan = document.getElementById('assign-selection-count');

    if (countSpan) {
        countSpan.textContent = checkedCount === 0 ? '0 métier sélectionné' : `${checkedCount} métier(s) sélectionné(s)`;
    }

    const hasUser = userSelect && userSelect.value !== '';
    if (btn) btn.disabled = !(hasUser && checkedCount > 0);
}

async function handleAssignRoleSubmit(event) {
    event.preventDefault();
    const form = document.getElementById('form-assign-role');
    const formData = new FormData(form);
    formData.append('action', 'assign_roles');

    const submitBtn = document.getElementById('btn-submit-assign');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Assignation...';
    }

    try {
        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            showDevAlert('success', `<strong>Attribution réussie :</strong> ${data.message}`);
            const modalEl = document.getElementById('modal-assign-role');
            if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();
            setTimeout(() => location.reload(), 1200);
        } else {
            alert('Erreur : ' + (data.error || 'Impossible d\'assigner les métiers.'));
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-plus me-1"></i>Assigner les métiers';
            }
        }
    } catch (err) {
        alert('Erreur réseau lors de l\'assignation.');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-plus me-1"></i>Assigner les métiers';
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ACTIONS GLOBALES GAME ELEVATE DESIGNER & CONSTANTES
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
            showDevAlert('success', `<i class="fa-solid fa-floppy-disk text-success me-1"></i><strong>Succès :</strong> ${data.message} (+30 XP Forge accordés)`);
        } else {
            showDevAlert('danger', `<i class="fa-solid fa-circle-xmark text-danger me-1"></i><strong>Erreur :</strong> ${data.error || "Impossible d'enregistrer les paramètres."}`);
        }
    } catch (err) {
        showDevAlert('danger', `<i class="fa-solid fa-circle-xmark text-danger me-1"></i><strong>Erreur réseau :</strong> ${err.message}`);
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
                    showDevAlert('success', `<i class="fa-solid fa-map-location-dot text-primary me-1"></i><strong>Expansion Réussie :</strong> <strong>${data.generated_count}</strong> terres ont été déployées (+40 XP Forge accordés) !`);
                    setTimeout(() => location.reload(), 2500);
                } else {
                    showDevAlert('danger', `<i class="fa-solid fa-circle-xmark text-danger me-1"></i><strong>Erreur :</strong> ${data.error || "Impossible d'arpenter les terres."}`);
                }
            } catch (e) {
                showDevAlert('danger', `<i class="fa-solid fa-circle-xmark text-danger me-1"></i><strong>Erreur réseau :</strong> ${e.message}`);
            }
        },
        'btn-success',
        '<i class="fa-solid fa-map-location-dot"></i>'
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
                    showDevAlert('success', `<i class="fa-solid fa-leaf text-success me-1"></i><strong>Succès :</strong> ${data.message} (+35 XP Forge accordés)`);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showDevAlert('danger', `<i class="fa-solid fa-circle-xmark text-danger me-1"></i><strong>Erreur :</strong> ${data.error || "Impossible de générer les oasis."}`);
                }
            } catch (e) {
                showDevAlert('danger', `<i class="fa-solid fa-circle-xmark text-danger me-1"></i><strong>Erreur réseau :</strong> ${e.message}`);
            }
        },
        'btn-success',
        '<i class="fa-solid fa-leaf"></i>'
    );
}

// Pagination des oasis
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
</script>
