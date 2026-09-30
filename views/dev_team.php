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
$canManageSprints = $devEngine->hasPermission($userId, 'sprints.manage') || $auth->isAdmin();
$canReviewQA      = $devEngine->hasPermission($userId, 'bugs.manage') || $devEngine->hasPermission($userId, 'debug.sandbox') || $auth->isAdmin();

require_once __DIR__ . '/../core/FeatureRegistry.php';
require_once __DIR__ . '/../core/QASyntaxChecker.php';

$qaFeatures   = FeatureRegistry::getAllFeatures();
$qaStats      = FeatureRegistry::getStatistics();
$syntaxStatus = QASyntaxChecker::getLastCheckResult();

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

    <!-- ── ONGLETS DE NAVIGATION DU STUDIO ── -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header border-bottom">
            <ul class="nav nav-tabs card-header-tabs" id="dev-team-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab-roster-btn" data-bs-toggle="tab" href="#tab-roster" role="tab" onclick="switchDevTab('roster')">
                        <span>👥</span> Studio Roster &amp; Métiers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-qa-btn" data-bs-toggle="tab" href="#tab-qa" role="tab" onclick="switchDevTab('qa')">
                        <span>📋</span> QA &amp; Recette
                        <?php if ($qaStats['pending'] > 0): ?>
                            <span class="badge bg-danger text-white ms-1" id="nav-qa-pending-badge"><?= $qaStats['pending'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if ($canUseSandbox): ?>
                <li class="nav-item">
                    <a class="nav-link" id="tab-sandbox-btn" data-bs-toggle="tab" href="#tab-sandbox" role="tab" onclick="switchDevTab('sandbox')">
                        <span>🧪</span> Atelier QA & Sandbox
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($canMonitorSystem || $canTriggerCron): ?>
                <li class="nav-item">
                    <a class="nav-link" id="tab-system-btn" data-bs-toggle="tab" href="#tab-system" role="tab" onclick="switchDevTab('system')">
                        <span>⚙️</span> Live Ops & Serveur
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" id="tab-lore-btn" data-bs-toggle="tab" href="#tab-lore" role="tab" onclick="switchDevTab('lore')">
                        <span>📜</span> Univers & Lore
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-forge-btn" data-bs-toggle="tab" href="#tab-forge" role="tab" onclick="switchDevTab('forge')">
                        <span>🔨</span> Journal de Forge & Trophées
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content">

                <!-- ═══════════════════════════════════════════════════════════════════════
                     ONGLET 1 : ROSTER & LES 8 MÉTIERS DU JEU VIDÉO
                     ═══════════════════════════════════════════════════════════════════════ -->
                <div class="tab-pane fade show active" id="tab-roster" role="tabpanel">
                    
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                        <div>
                            <h3 class="card-title mb-1">Les 8 Métiers de l'Équipe de Développement</h3>
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

                <!-- ═══════════════════════════════════════════════════════════════════════
                     ONGLET 2 : QA & RECETTE (FONCTIONNALITÉS & TESTS DE SYNTAXE)
                     ═══════════════════════════════════════════════════════════════════════ -->
                <div class="tab-pane fade" id="tab-qa" role="tabpanel">

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

                <!-- ═══════════════════════════════════════════════════════════════════════
                     ONGLET 3 : ATELIER QA & SANDBOX
                     ═══════════════════════════════════════════════════════════════════════ -->
                <?php if ($canUseSandbox): ?>
                <div class="tab-pane fade" id="tab-sandbox" role="tabpanel">
                    
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
                <?php if ($canMonitorSystem || $canTriggerCron): ?>
                <div class="tab-pane fade" id="tab-system" role="tabpanel">

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
                <div class="tab-pane fade" id="tab-lore" role="tabpanel">
                    
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

                <!-- ═══════════════════════════════════════════════════════════════════════
                     ONGLET 5 : JOURNAL DE FORGE & XP
                     ═══════════════════════════════════════════════════════════════════════ -->
                <div class="tab-pane fade" id="tab-forge" role="tabpanel">

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
</script>
