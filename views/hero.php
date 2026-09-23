<?php
/**
 * Vue du Samouraï Héros Champion (OpenShogun - Thème Tabler.io)
 * Système complet inspiré des mécaniques emblématiques féodales :
 * Progression, points d'attributs, aventures féodales (max 3/j), vitalité, régénération post-mortem (24h) et reliques uniques.
 */
require_once __DIR__ . '/../core/HeroEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

$heroEngine = new HeroEngine();
$planetEngine = new PlanetEngine();

$hero = $heroEngine->getHeroByUserId((int)$user['id']);
if (!$hero) {
    $heroName = "Samouraï " . ucfirst($user['username'] ?? 'Champion');
    $pId = (int)($planet['id'] ?? 0);
    if ($pId <= 0) {
        $db = Database::getConnection();
        $stmtP = $db->prepare("SELECT id FROM planets WHERE user_id = ? ORDER BY id ASC LIMIT 1");
        $stmtP->execute([(int)$user['id']]);
        $pId = (int)$stmtP->fetchColumn() ?: 1;
    }
    $db = Database::getConnection();
    $db->prepare("
        INSERT INTO heroes 
        (user_id, current_planet_id, name, level, experience, health, status, last_health_update, unassigned_points) 
        VALUES (?, ?, ?, 1, 0, 100.0, 'home', UNIX_TIMESTAMP(), 4)
        ON DUPLICATE KEY UPDATE current_planet_id = VALUES(current_planet_id)
    ")->execute([(int)$user['id'], $pId, $heroName]);
    $hero = $heroEngine->getHeroByUserId((int)$user['id']);
}

if (!$hero) {
    echo '<div class="alert alert-danger m-3">Impossible de charger le Samouraï Héros pour ce compte. Veuillez vérifier que votre fief est initialisé.</div>';
    return;
}

if (empty($hero['effective']) || !is_array($hero['effective'])) {
    $hero['effective'] = [
        'combat_strength' => 150 + (((int)($hero['stat_strength'] ?? 0)) * 80),
        'base_strength' => 150,
        'equipment_strength' => 0,
        'offense_bonus_pct' => 0,
        'defense_bonus_pct' => 0,
        'hourly_production' => ['metal' => 0, 'crystal' => 0, 'deuterium' => 0],
        'equipment_speed_bonus' => 0,
        'exp_bonus_pct' => 0
    ];
}

$adventures = $heroEngine->getAdventures((int)$user['id']);
$inventory = $heroEngine->getInventory((int)$user['id']);
$activeTab = $_GET['tab'] ?? 'attributes';
$dailyQuota = $hero['daily_adventures'] ?? $heroEngine->getDailyAdventureQuota((int)$user['id']);
if (!is_array($dailyQuota)) {
    $dailyQuota = [
        'count' => 0,
        'max' => 3,
        'remaining' => 3,
        'can_adventure' => true,
        'reset_timestamp' => strtotime('tomorrow midnight'),
        'seconds_until_reset' => 86400
    ];
}

$factionIcons = [
    'terran' => '🏯',
    'vorash' => '🐎',
    'aethelis' => '⛩️'
];
$fIcon = $factionIcons[$user['faction'] ?? 'terran'] ?? '⚔️';

$health = round((float)($hero['health'] ?? 100));
$healthBadgeClass = ($health >= 60) ? 'bg-success' : (($health >= 25) ? 'bg-warning' : 'bg-danger');

$statusLabels = [
    'home' => ['label' => 'Au Domaine (Garnison)', 'color' => '#22c55e', 'badge_class' => 'bg-success text-white', 'icon' => '🏯'],
    'mission' => ['label' => 'En Marche Militaire', 'color' => '#3b82f6', 'badge_class' => 'bg-info text-white', 'icon' => '🚩'],
    'adventure' => ['label' => 'En Aventure Féodale', 'color' => '#a855f7', 'badge_class' => 'bg-purple text-white', 'icon' => '🗺️'],
    'dead' => ['label' => 'Tombé au Combat', 'color' => '#ef4444', 'badge_class' => 'bg-danger text-white', 'icon' => '💀'],
    'reviving' => ['label' => 'Régénération en cours (24h)', 'color' => '#f59e0b', 'badge_class' => 'bg-warning text-dark', 'icon' => '✨']
];
$st = $statusLabels[$hero['status'] ?? 'home'] ?? ['label' => 'Inconnu', 'color' => '#94a3b8', 'badge_class' => 'bg-secondary text-white', 'icon' => '❓'];

if (!function_exists('renderRelicBonusesHtml')) {
    function renderRelicBonusesHtml($bonusData): string {
        if (empty($bonusData)) return '';
        if (is_string($bonusData)) {
            $bonusData = json_decode($bonusData, true) ?: [];
        }
        if (!is_array($bonusData) || empty($bonusData)) return '';
        $badges = [];
        foreach ($bonusData as $k => $v) {
            switch ($k) {
                case 'strength':
                    $badges[] = '<span class="badge bg-red-lt text-red border border-red-lt">+' . (int)$v . ' Force</span>';
                    break;
                case 'offense_bonus':
                    $badges[] = '<span class="badge bg-orange-lt text-orange border border-orange-lt">+' . (float)$v . '% Attaque</span>';
                    break;
                case 'defense_bonus':
                    $badges[] = '<span class="badge bg-blue-lt text-blue border border-blue-lt">+' . (float)$v . '% Défense</span>';
                    break;
                case 'speed':
                    $badges[] = '<span class="badge bg-cyan-lt text-cyan border border-cyan-lt">+' . (int)$v . '% Vitesse</span>';
                    break;
                case 'exp_bonus':
                    $badges[] = '<span class="badge bg-purple-lt text-purple border border-purple-lt">+' . (int)$v . '% Exp</span>';
                    break;
                case 'production_rice':
                    $badges[] = '<span class="badge bg-green-lt text-green border border-green-lt">+' . (int)$v . ' Riz/h</span>';
                    break;
                case 'production_wood':
                    $badges[] = '<span class="badge bg-teal-lt text-teal border border-teal-lt">+' . (int)$v . ' Bois/h</span>';
                    break;
                case 'production_stone':
                    $badges[] = '<span class="badge bg-secondary-lt text-secondary border border-secondary-lt">+' . (int)$v . ' Pierre/h</span>';
                    break;
                case 'cages_count':
                    $badges[] = '<span class="badge bg-success-lt text-success border border-success-lt fw-bold">🎋 ' . (int)$v . ' Cages de capture</span>';
                    break;
                default:
                    $badges[] = '<span class="badge bg-secondary-lt">+' . htmlspecialchars((string)$v) . ' ' . htmlspecialchars($k) . '</span>';
                    break;
            }
        }
        return implode(' ', $badges);
    }
}
?>

<!-- En-tête de navigation Tabler -->
<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Général &amp; Champion du Fief</div>
            <h2 class="page-title d-flex align-items-center gap-2">
                <span><?= $fIcon ?></span>
                <span>Samouraï <?= htmlspecialchars($hero['name']) ?></span>
                <span class="badge bg-primary text-white ms-2" style="font-size:0.75rem;">Niveau <?= $hero['level'] ?></span>
                <span class="badge <?= $st['badge_class'] ?> ms-1" style="font-size:0.75rem;"><?= $st['icon'] ?> <?= $st['label'] ?></span>
            </h2>
        </div>
    </div>
</div>

<!-- 4 Stat Cards Tabler : Vitalité, Niveau/XP, Puissance, Quota Aventures -->
<div class="row row-cards mb-3">
    <!-- Stat 1 : Vitalité -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar rounded <?= ($health >= 60) ? 'bg-success-lt text-success' : (($health >= 25) ? 'bg-warning-lt text-warning' : 'bg-danger-lt text-danger') ?>" style="font-size:1.3rem;">
                            ❤️
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">Santé &amp; Vitalité</div>
                        <div class="text-secondary font-weight-bold" style="font-size: 1.15rem;">
                            <?= $health ?>%
                        </div>
                    </div>
                </div>
                <div class="progress progress-xs mt-2">
                    <div class="progress-bar <?= ($health >= 60) ? 'bg-success' : (($health >= 25) ? 'bg-warning' : 'bg-danger') ?>" style="width: <?= $health ?>%"></div>
                </div>
                <div class="text-secondary small mt-1 d-flex justify-content-between">
                    <span><?= ($hero['status'] === 'dead') ? '💀 Héros tombé' : (($hero['status'] === 'reviving') ? '⏳ En régénération' : 'Régénération +15%/j') ?></span>
                    <span>Max 100%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat 2 : Niveau & XP -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar rounded bg-primary-lt text-primary" style="font-size:1.3rem;">
                            🥋
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">Niveau <?= $hero['level'] ?></div>
                        <div class="text-secondary font-weight-bold" style="font-size: 1.05rem;">
                            <?= number_format($hero['experience']) ?> <small class="text-muted">/ <?= number_format($hero['xp_next_level']) ?> XP</small>
                        </div>
                    </div>
                </div>
                <div class="progress progress-xs mt-2">
                    <div class="progress-bar bg-primary" style="width: <?= $hero['xp_progress_percent'] ?>%"></div>
                </div>
                <div class="text-secondary small mt-1 d-flex justify-content-between">
                    <span>Niv. <?= $hero['level'] ?></span>
                    <span><?= $hero['xp_progress_percent'] ?>% &bull; Vers Niv. <?= $hero['level'] + 1 ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat 3 : Puissance de Combat -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar rounded bg-danger-lt text-danger" style="font-size:1.3rem;">
                            ⚔️
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">Force Martiale</div>
                        <div class="text-secondary font-weight-bold" style="font-size: 1.15rem;">
                            <?= number_format($hero['effective']['combat_strength']) ?> <small class="text-muted">pts</small>
                        </div>
                    </div>
                </div>
                <div class="text-secondary small mt-2">
                    Attaque armée : <strong class="text-danger">+<?= $hero['effective']['offense_bonus_pct'] ?>%</strong> &bull; Défense : <strong class="text-success">+<?= $hero['effective']['defense_bonus_pct'] ?>%</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat 4 : Quota Aventures Quotidiennes (Max 3/j) -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar rounded bg-purple-lt text-purple" style="font-size:1.3rem;">
                            🗺️
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">Aventures du jour</div>
                        <div class="text-secondary font-weight-bold" style="font-size: 1.15rem;">
                            <span class="<?= ($dailyQuota['remaining'] > 0) ? 'text-success' : 'text-danger' ?>">
                                <?= $dailyQuota['count'] ?> / <?= $dailyQuota['max'] ?>
                            </span>
                            <small class="text-muted">(<?= $dailyQuota['remaining'] ?> libre<?= $dailyQuota['remaining'] > 1 ? 's' : '' ?>)</small>
                        </div>
                    </div>
                </div>
                <div class="progress progress-xs mt-2">
                    <div class="progress-bar bg-purple" style="width: <?= round(($dailyQuota['count'] / $dailyQuota['max']) * 100) ?>%"></div>
                </div>
                <div class="text-secondary small mt-1 d-flex justify-content-between">
                    <span>3 maxi par jour</span>
                    <span>Reset à minuit</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alertes d'État : Héros Tombé ou Régénération en cours (24h) -->
<?php if ($hero['status'] === 'dead'): ?>
    <div class="alert alert-danger d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <span style="font-size: 1.8rem;">💀</span>
            <div>
                <h4 class="alert-title m-0">Votre Samouraï est tombé au champ d'honneur !</h4>
                <div class="text-secondary small mt-1">
                    La régénération du héros après sa mort dure <strong>24 heures</strong>. Invoquez les esprits tutélaires au sanctuaire pour commencer la régénération.
                </div>
            </div>
        </div>
        <div>
            <button type="button" onclick="executeReviveHero(<?= (int)$planet['id'] ?>)" class="btn btn-danger font-weight-bold">
                ✨ Lancer la Régénération (24h)
            </button>
        </div>
    </div>
<?php elseif ($hero['status'] === 'reviving'): ?>
    <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <span style="font-size: 1.8rem;">⏳</span>
            <div>
                <h4 class="alert-title m-0">Régénération sacrée en cours (durée : 24 heures)</h4>
                <div class="text-secondary small mt-1">
                    Votre Samouraï est en communion spirituelle au donjon. Il recouvrera 100% de sa vitalité dès la fin du rituel.
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="small text-secondary font-weight-bold">Fin dans :</span>
            <span class="badge bg-warning text-dark font-monospace py-2 px-3 fs-5" data-countdown="<?= $hero['revive_finish_time'] ?>">
                Calcul...
            </span>
        </div>
    </div>
<?php endif; ?>

<!-- Panneau Principal Tabler avec Onglets -->
<div class="card mb-3">
    <!-- Onglets Tabler -->
    <div class="card-header border-bottom">
        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
            <li class="nav-item">
                <a href="?page=hero&tab=attributes" class="nav-link <?= ($activeTab === 'attributes') ? 'active' : '' ?>">
                    <span class="me-1">🥋</span> Compétences &amp; Attributs
                    <?php if ($hero['unassigned_points'] > 0): ?>
                        <span class="badge bg-warning text-dark ms-2">+<?= $hero['unassigned_points'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="?page=hero&tab=adventures" class="nav-link <?= ($activeTab === 'adventures') ? 'active' : '' ?>">
                    <span class="me-1">🗺️</span> Aventures Provinciales
                    <span class="badge bg-purple-lt ms-2"><?= $dailyQuota['count'] ?>/<?= $dailyQuota['max'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="?page=hero&tab=inventory" class="nav-link <?= ($activeTab === 'inventory') ? 'active' : '' ?>">
                    <span class="me-1">🗡️</span> Arsenal &amp; Reliques
                    <?php if (count($inventory) > 0): ?>
                        <span class="badge bg-secondary-lt ms-2"><?= count($inventory) ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
    </div>

    <!-- CONTENU ONGLET 1 : ATTRIBUTS & COMPÉTENCES -->
    <?php if ($activeTab === 'attributes'): ?>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <h3 class="card-title m-0">Points de Compétences Féodales</h3>
                    <div class="text-secondary small mt-1">
                        Répartissez vos 4 points gagnés à chaque niveau entre les 4 vertus de commandement du Samouraï.
                    </div>
                </div>
                <div class="badge bg-warning-lt border border-warning fs-6 py-2 px-3">
                    Points disponibles : <strong id="unassignedDisplay" class="ms-1"><?= $hero['unassigned_points'] ?></strong>
                </div>
            </div>

            <form id="heroAttributesForm" onsubmit="event.preventDefault(); submitAttributes();">
                <div class="row g-3">
                    <!-- 1. Force de Combat -->
                    <div class="col-md-6">
                        <div class="card card-sm h-100 border">
                            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div style="flex:1; min-width: 200px;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="fs-3">⚔️</span>
                                        <strong class="fs-5">Force Personnelle</strong>
                                    </div>
                                    <p class="text-secondary small mb-1">
                                        Augmente la puissance pure du Samouraï (+80 pts de puissance par point).
                                    </p>
                                    <div class="text-primary small font-weight-bold">
                                        Puissance actuelle : <span id="effectiveStrength"><?= number_format($hero['effective']['combat_strength']) ?></span>
                                        <?php if ($hero['effective']['equipment_strength'] > 0): ?>
                                            <span class="text-warning small">(+<?= $hero['effective']['equipment_strength'] ?> via armes)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="font-weight-bold fs-5 px-2" id="baseStrengthPts"><?= $hero['stat_strength'] ?></span>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustPoint('strength', -1)">-</button>
                                        <span class="btn btn-sm btn-light font-monospace font-weight-bold text-success px-2" id="add-strength">0</span>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="adjustPoint('strength', 1)">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Bonus d'Attaque (Offense %) -->
                    <div class="col-md-6">
                        <div class="card card-sm h-100 border">
                            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div style="flex:1; min-width: 200px;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="fs-3">🏹</span>
                                        <strong class="fs-5">Bonus Offensif d'Armée</strong>
                                    </div>
                                    <p class="text-secondary small mb-1">
                                        Augmente la force d'attaque de TOUTES les troupes qui accompagnent le héros (+0.2%/point, max 20%).
                                    </p>
                                    <div class="text-danger small font-weight-bold">
                                        Bonus offensif : <span id="effectiveOffense">+<?= $hero['effective']['offense_bonus_pct'] ?>%</span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="font-weight-bold fs-5 px-2" id="baseOffensePts"><?= $hero['stat_offense_bonus'] ?></span>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustPoint('offense', -1)">-</button>
                                        <span class="btn btn-sm btn-light font-monospace font-weight-bold text-success px-2" id="add-offense">0</span>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="adjustPoint('offense', 1)">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Bonus de Défense (Défense %) -->
                    <div class="col-md-6">
                        <div class="card card-sm h-100 border">
                            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div style="flex:1; min-width: 200px;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="fs-3">🛡️</span>
                                        <strong class="fs-5">Bonus Défensif d'Armée</strong>
                                    </div>
                                    <p class="text-secondary small mb-1">
                                        Augmente la défense de TOUTES les troupes du fief lorsqu'il est présent (+0.2%/point, max 20%).
                                    </p>
                                    <div class="text-success small font-weight-bold">
                                        Bonus défensif : <span id="effectiveDefense">+<?= $hero['effective']['defense_bonus_pct'] ?>%</span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="font-weight-bold fs-5 px-2" id="baseDefensePts"><?= $hero['stat_defense_bonus'] ?></span>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustPoint('defense', -1)">-</button>
                                        <span class="btn btn-sm btn-light font-monospace font-weight-bold text-success px-2" id="add-defense">0</span>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="adjustPoint('defense', 1)">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Production du Domaine -->
                    <div class="col-md-6">
                        <div class="card card-sm h-100 border">
                            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div style="flex:1; min-width: 200px;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="fs-3">🌾</span>
                                        <strong class="fs-5">Bénédiction de Récolte</strong>
                                    </div>
                                    <p class="text-secondary small mb-1">
                                        Accroît la production horaire de ressources du fief où réside le héros (+120 res/h par point).
                                    </p>
                                    <div class="text-warning small font-weight-bold">
                                        Production bonus : <span id="effectiveProd">+<?= number_format($hero['stat_production'] * 120) ?> res/h</span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="font-weight-bold fs-5 px-2" id="baseProdPts"><?= $hero['stat_production'] ?></span>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustPoint('production', -1)">-</button>
                                        <span class="btn btn-sm btn-light font-monospace font-weight-bold text-success px-2" id="add-production">0</span>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="adjustPoint('production', 1)">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orientation de production -->
                <div class="card card-sm border mt-3 bg-surface-secondary">
                    <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <span class="text-secondary small font-weight-bold text-uppercase" style="letter-spacing:0.5px;">Orientation des Récoltes :</span>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <label class="form-check form-check-inline m-0">
                                <input class="form-check-input" type="radio" name="prod_type" value="balanced" <?= ($hero['production_type'] === 'balanced') ? 'checked' : '' ?> onchange="changeProductionType(this.value)">
                                <span class="form-check-label">⚖️ Équilibrée (Tous)</span>
                            </label>
                            <label class="form-check form-check-inline m-0">
                                <input class="form-check-input" type="radio" name="prod_type" value="metal" <?= ($hero['production_type'] === 'metal') ? 'checked' : '' ?> onchange="changeProductionType(this.value)">
                                <span class="form-check-label" style="color:var(--tblr-warning-emphasis, #b45309);">🪵 Bois de Cèdre pur</span>
                            </label>
                            <label class="form-check form-check-inline m-0">
                                <input class="form-check-input" type="radio" name="prod_type" value="crystal" <?= ($hero['production_type'] === 'crystal') ? 'checked' : '' ?> onchange="changeProductionType(this.value)">
                                <span class="form-check-label" style="color:var(--tblr-primary, #2563eb);">🪨 Pierre de Taille pure</span>
                            </label>
                            <label class="form-check form-check-inline m-0">
                                <input class="form-check-input" type="radio" name="prod_type" value="deuterium" <?= ($hero['production_type'] === 'deuterium') ? 'checked' : '' ?> onchange="changeProductionType(this.value)">
                                <span class="form-check-label" style="color:var(--tblr-success, #16a34a);">🌾 Riz Impérial pur</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'enregistrement -->
                <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
                    <button type="button" class="btn btn-secondary" onclick="resetPoints()">Réinitialiser</button>
                    <button type="submit" class="btn btn-primary" id="savePointsBtn">
                        ✨ Enregistrer les Attributs
                    </button>
                </div>
            </form>
        </div>

    <!-- CONTENU ONGLET 2 : AVENTURES FÉODALES (MAX 3 PAR JOUR) -->
    <?php elseif ($activeTab === 'adventures'): ?>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <h3 class="card-title m-0">🗺️ Expéditions &amp; Aventures Provinciales</h3>
                    <div class="text-secondary small mt-1">
                        Envoyez votre Samouraï explorer les sanctuaires oubliés et ruines antiques pour acquérir de l'XP, du butin et des reliques uniques.
                    </div>
                </div>

                <!-- Badge Quota Journalier -->
                <div class="badge <?= ($dailyQuota['remaining'] > 0) ? 'bg-success-lt border border-success' : 'bg-danger-lt border border-danger' ?> py-2 px-3 fs-6">
                    Quota du jour : <strong class="ms-1"><?= $dailyQuota['count'] ?> / <?= $dailyQuota['max'] ?></strong>
                    <span class="small ms-1">(<?= $dailyQuota['remaining'] ?> restante<?= $dailyQuota['remaining'] > 1 ? 's' : '' ?>)</span>
                </div>
            </div>

            <!-- Encart d'information sur la règle des 3 aventures par jour -->
            <div class="card card-sm mb-3 border <?= ($dailyQuota['remaining'] > 0) ? 'border-purple-subtle bg-purple-lt' : 'border-danger-subtle bg-danger-lt' ?>">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                    <div>
                        <div class="font-weight-bold <?= ($dailyQuota['remaining'] > 0) ? 'text-purple' : 'text-danger' ?>">
                            <?= ($dailyQuota['remaining'] > 0) ? '⏳ Règle Féodale : 3 aventures par jour maximum' : '🔒 Quota quotidien épuisé (3 / 3 aventures)' ?>
                        </div>
                        <div class="small text-secondary mt-1">
                            <?php if ($dailyQuota['remaining'] > 0): ?>
                                Votre héros peut encore accomplir <strong><?= $dailyQuota['remaining'] ?> aventure<?= $dailyQuota['remaining'] > 1 ? 's' : '' ?></strong> aujourd'hui. Réinitialisation chaque nuit à minuit.
                            <?php else: ?>
                                Votre Samouraï a accompli ses 3 aventures du jour. Il médite au dojo pour reprendre des forces jusqu'à minuit avant de repartir en quête.
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <?php for ($i = 1; $i <= $dailyQuota['max']; $i++): ?>
                            <?php if ($i <= $dailyQuota['count']): ?>
                                <span class="badge bg-success text-white py-1 px-2">✓ Aventure <?= $i ?></span>
                            <?php else: ?>
                                <span class="badge bg-surface border text-secondary py-1 px-2">○ Aventure <?= $i ?></span>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <?php if (empty($adventures)): ?>
                <div class="text-center py-5 border rounded bg-surface">
                    <div class="fs-1 mb-2">📜</div>
                    <h4 class="font-weight-bold">Aucune aventure n'est disponible pour l'instant</h4>
                    <p class="text-secondary small max-w-sm mx-auto mb-0">
                        De nouvelles rumeurs et pistes d'aventures apparaissent régulièrement à mesure que vos éclaireurs sillonnent les provinces.
                    </p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($adventures as $adv): ?>
                        <?php 
                            $diffBadges = [
                                'easy' => ['label' => 'Difficulté : Faible', 'class' => 'bg-success-lt text-success border border-success'],
                                'medium' => ['label' => 'Difficulté : Moyenne', 'class' => 'bg-warning-lt text-warning border border-warning'],
                                'hard' => ['label' => 'Difficulté : Périlleuse', 'class' => 'bg-danger-lt text-danger border border-danger']
                            ];
                            $dbdg = $diffBadges[$adv['difficulty']] ?? $diffBadges['easy'];
                            $canStart = ($hero['status'] === 'home' && $hero['health'] >= 15.0 && $dailyQuota['can_adventure']);
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge <?= $dbdg['class'] ?>"><?= $dbdg['label'] ?></span>
                                        <span class="badge bg-secondary-lt font-monospace">[<?= $adv['coord_x'] ?> : <?= $adv['coord_y'] ?>]</span>
                                    </div>
                                    <h4 class="card-title font-weight-bold mb-2">
                                        ⛩️ <?= htmlspecialchars($adv['name']) ?>
                                    </h4>
                                    <div class="bg-surface-secondary border rounded p-2 small text-secondary d-flex justify-content-between mb-2">
                                        <span>Distance : <strong><?= $adv['distance'] ?></strong> lieues</span>
                                        <span>Marche : <strong><?= gmdate('H:i:s', $adv['duration']) ?></strong></span>
                                    </div>
                                    <div class="small text-muted">
                                        Gains potentiels : XP, Vivres, Troupes ou Relique unique
                                    </div>
                                </div>
                                <div class="card-footer bg-surface d-flex justify-content-between align-items-center py-2 px-3 border-top">
                                    <span class="small text-secondary">Statut : <?= $canStart ? '<span class="text-success font-weight-bold">Prêt</span>' : '<span class="text-muted">Bloqué</span>' ?></span>
                                    <?php if ($canStart): ?>
                                        <button type="button" onclick="executeStartAdventure(<?= (int)$adv['id'] ?>)" class="btn btn-sm btn-primary font-weight-bold">
                                            Partir en Aventure &rarr;
                                        </button>
                                    <?php else: ?>
                                        <?php
                                            $btnReason = 'Indisponible';
                                            $btnTitle = '';
                                            if (!$dailyQuota['can_adventure']) {
                                                $btnReason = '🔒 Quota atteint (3/3)';
                                                $btnTitle = 'Quota quotidien de 3 aventures atteint. Réinitialisation à minuit.';
                                            } elseif ($hero['status'] !== 'home') {
                                                $btnReason = 'Indisponible (En route)';
                                                $btnTitle = 'Le Samouraï doit être au domaine pour partir en aventure.';
                                            } elseif ($hero['health'] < 15.0) {
                                                $btnReason = 'Blessé (< 15% PV)';
                                                $btnTitle = 'Santé insuffisante. Laissez votre Samouraï récupérer ses PV.';
                                            }
                                        ?>
                                        <button type="button" disabled class="btn btn-sm btn-secondary opacity-75" title="<?= htmlspecialchars($btnTitle) ?>">
                                            <?= $btnReason ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <!-- CONTENU ONGLET 3 : ARSENAL & RELIQUES (INVENTAIRE) -->
    <?php elseif ($activeTab === 'inventory'): ?>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <h3 class="card-title m-0">🗡️ Arsenal &amp; Reliques Ancestrales</h3>
                    <div class="text-secondary small mt-1">
                        Équipez votre Samouraï des armes légendaires et trésors sacrés découverts lors de ses expéditions.
                    </div>
                </div>
                <div class="badge bg-warning-lt border border-warning fs-6 py-2 px-3">
                    Reliques possédées : <strong class="ms-1"><?= count($inventory) ?> / 35</strong>
                </div>
            </div>

            <!-- Règle des Reliques Uniques -->
            <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center gap-2">
                <span class="fs-4">⛩️</span>
                <div class="small">
                    <strong>Règle Féodale des Reliques :</strong> Chaque relique du Japon féodal est unique. <strong>Vous ne pouvez jamais obtenir deux fois la même relique</strong> au cours de vos aventures.
                </div>
            </div>

            <!-- Mannequin du Héros & 5 Emplacements d'Équipement -->
            <h4 class="font-weight-bold mb-2">🥋 Équipement Actuel du Champion</h4>
            <div class="row g-3 mb-4 align-items-stretch">
                <!-- Portrait / Mannequin -->
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 border overflow-hidden shadow-sm text-center bg-dark" style="position:relative; min-height:280px; cursor:pointer;" onclick="window.open('/public/assets/hero_samurai.jpg', '_blank')" title="Agrandir le portrait">
                        <img src="/public/assets/hero_samurai.jpg?v=<?= file_exists(__DIR__ . '/../public/assets/hero_samurai.jpg') ? filemtime(__DIR__ . '/../public/assets/hero_samurai.jpg') : 1 ?>" 
                             alt="Héros Samouraï" 
                             style="width: 100%; height: 100%; object-fit: cover; object-position: top center;">
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(0deg, rgba(15,23,42,0.95) 0%, rgba(15,23,42,0.5) 70%, rgba(15,23,42,0) 100%); padding: 0.75rem 0.5rem; text-align: center;">
                            <div class="text-white font-weight-bold fs-5"><?= htmlspecialchars($hero['name']) ?></div>
                            <div class="text-warning small font-weight-bold">Niveau <?= $hero['level'] ?> &bull; <?= number_format($hero['effective']['combat_strength']) ?> pts</div>
                        </div>
                    </div>
                </div>

                <!-- 5 Slots d'Équipement -->
                <div class="col-md-8 col-lg-9">
                    <div class="row g-2 h-100">
                        <?php 
                            $slots = [
                                'weapon' => ['label' => 'Arme de Poing', 'icon' => '🗡️', 'field' => 'equipped_weapon'],
                                'helmet' => ['label' => 'Casque Kabuto', 'icon' => '🪖', 'field' => 'equipped_helmet'],
                                'armor' => ['label' => 'Armure O-Yoroi', 'icon' => '🥋', 'field' => 'equipped_armor'],
                                'horse' => ['label' => 'Monture & Destrier', 'icon' => '🐎', 'field' => 'equipped_horse'],
                                'talisman' => ['label' => 'Talisman Shintō', 'icon' => '📿', 'field' => 'equipped_talisman']
                            ];
                        ?>
                        <?php foreach ($slots as $slotKey => $sl): ?>
                            <?php 
                                $eqCode = $hero[$sl['field']] ?? null;
                                $equippedItem = null;
                                if ($eqCode) {
                                    foreach ($inventory as $invItem) {
                                        if (($invItem['item_code'] ?? '') === $eqCode && !empty($invItem['is_equipped'])) {
                                            $equippedItem = $invItem;
                                            if (empty($equippedItem['bonus_data'])) {
                                                $equippedItem['bonus_data'] = [];
                                            } elseif (is_string($equippedItem['bonus_data'])) {
                                                $equippedItem['bonus_data'] = json_decode($equippedItem['bonus_data'], true) ?: [];
                                            }
                                            break;
                                        }
                                    }
                                }
                            ?>
                            <div class="col-sm-6 col-md-4">
                                <div class="card h-100 border p-2 text-center <?= $equippedItem ? 'border-primary bg-primary-lt' : 'bg-surface' ?>" style="min-height: 130px; border-style: <?= $equippedItem ? 'solid' : 'dashed' ?> !important;">
                                    <?php if ($equippedItem): ?>
                                        <div class="d-flex align-items-center gap-2 mb-2 text-start">
                                            <div class="position-relative flex-shrink-0" style="width: 52px; height: 52px;">
                                                <img src="<?= htmlspecialchars($equippedItem['image_url']) ?>" 
                                                     alt="<?= htmlspecialchars($equippedItem['name']) ?>" 
                                                     class="rounded border shadow-sm w-100 h-100" 
                                                     style="object-fit: cover; cursor: pointer;"
                                                     onclick="openItemDetailModal(<?= htmlspecialchars(json_encode($equippedItem), ENT_QUOTES, 'UTF-8') ?>)"
                                                     title="Examiner la relique">
                                                <span class="position-absolute bottom-0 end-0 badge bg-dark p-1 rounded-circle" style="transform: translate(20%, 20%); font-size: 8px; line-height: 1;">🔍</span>
                                            </div>
                                            <div class="overflow-hidden">
                                                <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.65rem;"><?= $sl['icon'] ?> <?= $sl['label'] ?></div>
                                                <div class="fw-bold text-dark text-truncate small" style="cursor: pointer;" onclick="openItemDetailModal(<?= htmlspecialchars(json_encode($equippedItem), ENT_QUOTES, 'UTF-8') ?>)">
                                                    <?= htmlspecialchars($equippedItem['name']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-1 mb-2 justify-content-start">
                                            <?= renderRelicBonusesHtml($equippedItem['bonus_data'] ?? []) ?>
                                        </div>
                                        <div class="mt-auto d-flex justify-content-between align-items-center pt-1 border-top">
                                            <button type="button" onclick="openItemDetailModal(<?= htmlspecialchars(json_encode($equippedItem), ENT_QUOTES, 'UTF-8') ?>)" class="btn btn-sm btn-ghost-secondary py-0 px-2" style="font-size: 0.72rem;">
                                                Détails
                                            </button>
                                            <button type="button" onclick="executeUnequip('<?= $slotKey ?>')" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:0.72rem;">
                                                Déséquiper
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100 py-3">
                                            <div class="fs-2 mb-1 opacity-50"><?= $sl['icon'] ?></div>
                                            <div class="text-secondary small font-weight-bold text-uppercase"><?= $sl['label'] ?></div>
                                            <div class="text-muted small fst-italic mt-1" style="font-size:0.75rem;">Emplacement libre</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Grille d'Inventaire / Coffre de Reliques -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="font-weight-bold mb-0">📦 Coffre &amp; Reliques Féodales Collectées (<?= count($inventory) ?>/35)</h4>
                <div class="text-secondary small">
                    Cliquez sur une illustration pour l'admirer en grand format.
                </div>
            </div>

            <?php if (empty($inventory)): ?>
                <div class="text-center py-5 border rounded bg-surface">
                    <div class="fs-1 mb-2">⛩️</div>
                    <h4 class="text-dark fw-bold">Votre coffre de reliques est vide</h4>
                    <p class="text-secondary mb-0 max-w-md mx-auto small">
                        Envoyez votre Samouraï en aventure féodale chaque jour (jusqu'à 3 expéditions quotidiennes) pour exhumer des katanas mythiques, des kabutos ornés et des trésors sacrés !
                    </p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($inventory as $it): ?>
                        <?php 
                            $isEq = !empty($it['is_equipped']);
                            $itemSlot = $it['slot'] ?? ($it['item_type'] ?? 'weapon');
                            $it['slot'] = $itemSlot;
                            $it['item_type'] = $itemSlot;
                            if (empty($it['bonus_data'])) {
                                $it['bonus_data'] = [];
                            } elseif (is_string($it['bonus_data'])) {
                                $it['bonus_data'] = json_decode($it['bonus_data'], true) ?: [];
                            }
                            $slotLabels = [
                                'weapon' => ['label' => 'Arme', 'icon' => '🗡️'],
                                'helmet' => ['label' => 'Casque', 'icon' => '🪖'],
                                'armor' => ['label' => 'Armure', 'icon' => '🥋'],
                                'horse' => ['label' => 'Monture', 'icon' => '🐎'],
                                'talisman' => ['label' => 'Talisman', 'icon' => '📿']
                            ];
                            $slInfo = $slotLabels[$itemSlot] ?? ['label' => ucfirst((string)$itemSlot), 'icon' => '🛡️'];
                        ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border <?= $isEq ? 'border-primary bg-primary-lt' : 'bg-surface' ?> shadow-sm">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex gap-3 align-items-start mb-2">
                                            <!-- Vignette Estampe -->
                                            <div class="position-relative flex-shrink-0" style="width: 78px; height: 78px;">
                                                <img src="<?= htmlspecialchars($it['image_url']) ?>" 
                                                     alt="<?= htmlspecialchars($it['name']) ?>" 
                                                     class="rounded border shadow-sm w-100 h-100" 
                                                     style="object-fit: cover; cursor: pointer; transition: transform 0.15s ease-in-out;"
                                                     onclick="openItemDetailModal(<?= htmlspecialchars(json_encode($it), ENT_QUOTES, 'UTF-8') ?>)"
                                                     onmouseover="this.style.transform='scale(1.04)'"
                                                     onmouseout="this.style.transform='scale(1)'"
                                                     title="Examiner en grand format">
                                                <button type="button" 
                                                        class="btn btn-icon btn-sm btn-dark position-absolute bottom-0 end-0 m-1 rounded-circle opacity-75" 
                                                        style="width: 20px; height: 20px; font-size: 9px; padding: 0;"
                                                        onclick="openItemDetailModal(<?= htmlspecialchars(json_encode($it), ENT_QUOTES, 'UTF-8') ?>)"
                                                        title="Agrandir">
                                                    🔍
                                                </button>
                                            </div>

                                            <!-- Informations Relique -->
                                            <div class="overflow-hidden flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                                                    <span class="badge bg-secondary-lt text-secondary" style="font-size: 0.7rem;">
                                                        <?= $slInfo['icon'] ?> <?= $slInfo['label'] ?>
                                                    </span>
                                                    <?php if ($isEq): ?>
                                                        <span class="badge bg-primary text-white" style="font-size: 0.7rem;">✓ Équipé</span>
                                                    <?php endif; ?>
                                                </div>
                                                <strong class="text-dark d-block text-truncate fs-4" style="cursor: pointer;" onclick="openItemDetailModal(<?= htmlspecialchars(json_encode($it), ENT_QUOTES, 'UTF-8') ?>)">
                                                    <?= htmlspecialchars($it['name']) ?>
                                                </strong>
                                                <div class="text-secondary small mt-1" style="font-size: 0.78rem; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                    <?= htmlspecialchars($it['description']) ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Badges de Bonus -->
                                        <div class="d-flex flex-wrap gap-1 mb-3 pt-1">
                                            <?= renderRelicBonusesHtml($it['bonus_data'] ?? []) ?>
                                        </div>
                                    </div>

                                    <!-- Pied de Carte / Actions -->
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <button type="button" class="btn btn-sm btn-ghost-secondary px-2" onclick="openItemDetailModal(<?= htmlspecialchars(json_encode($it), ENT_QUOTES, 'UTF-8') ?>)">
                                            🔍 Examiner
                                        </button>
                                        <div>
                                            <?php if ($it['item_type'] === 'consumable'): ?>
                                                <?php $cCount = (int)($it['bonus_data']['cages_count'] ?? 0); ?>
                                                <span class="badge bg-success-lt text-success fw-bold" style="font-size:0.75rem;">
                                                    🎋 En réserve : <?= $cCount ?>
                                                </span>
                                            <?php elseif ($isEq): ?>
                                                <button type="button" onclick="executeUnequip('<?= htmlspecialchars($itemSlot) ?>')" class="btn btn-sm btn-outline-danger">
                                                    Déséquiper
                                                </button>
                                            <?php else: ?>
                                                <button type="button" onclick="executeEquip(<?= (int)$it['id'] ?>)" class="btn btn-sm btn-primary">
                                                    Équiper
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- MODALE LIGHTBOX RELIQUE HD -->
<div id="itemDetailModal" class="modal-overlay" style="display:none;" onclick="closeItemDetailModal(event)">
    <div class="modal-card modal-card-lg" style="max-width: 820px; padding: 1.5rem;" onclick="event.stopPropagation()">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
            <div>
                <h3 id="itemModalTitle" class="m-0 fs-2 font-weight-bold text-dark"></h3>
                <div id="itemModalSubtitle" class="text-secondary small mt-1"></div>
            </div>
            <button type="button" class="btn-close" onclick="closeItemDetailModal()"></button>
        </div>
        <div class="row g-3 align-items-center">
            <div class="col-md-6 text-center">
                <div class="position-relative d-inline-block w-100">
                    <img id="itemModalImg" src="" alt="Relique" 
                         class="rounded shadow border w-100" 
                         style="max-height: 420px; object-fit: contain; background: #000;">
                </div>
            </div>
            <div class="col-md-6 d-flex flex-column justify-content-between">
                <div>
                    <div class="mb-3">
                        <label class="form-label text-muted text-uppercase small font-weight-bold mb-1">📜 Récit &amp; Origine Féodale</label>
                        <p id="itemModalDesc" class="text-secondary fs-4 fst-italic bg-surface p-3 rounded border" style="line-height: 1.5;"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted text-uppercase small font-weight-bold mb-1">⚡ Pouvoirs &amp; Bénédictions</label>
                        <div id="itemModalBonuses" class="d-flex flex-wrap gap-1"></div>
                    </div>
                </div>
                <div class="pt-3 border-top d-flex justify-content-between align-items-center mt-3" id="itemModalActions">
                    <!-- Boutons équipement / fermeture dynamiques -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Gestion de l'attribution des points
let unassignedAvailable = <?= (int)$hero['unassigned_points'] ?>;
const pointsToAdd = {
    strength: 0,
    offense: 0,
    defense: 0,
    production: 0
};

function adjustPoint(stat, delta) {
    if (delta > 0) {
        if (unassignedAvailable <= 0) return;
        pointsToAdd[stat]++;
        unassignedAvailable--;
    } else if (delta < 0) {
        if (pointsToAdd[stat] <= 0) return;
        pointsToAdd[stat]--;
        unassignedAvailable++;
    }
    updatePointsUI();
}

function resetPoints() {
    unassignedAvailable = <?= (int)$hero['unassigned_points'] ?>;
    pointsToAdd.strength = 0;
    pointsToAdd.offense = 0;
    pointsToAdd.defense = 0;
    pointsToAdd.production = 0;
    updatePointsUI();
}

function updatePointsUI() {
    const unDisp = document.getElementById('unassignedDisplay');
    if (unDisp) unDisp.innerText = unassignedAvailable;
    
    const addStr = document.getElementById('add-strength');
    if (addStr) addStr.innerText = pointsToAdd.strength;
    
    const addOff = document.getElementById('add-offense');
    if (addOff) addOff.innerText = pointsToAdd.offense;
    
    const addDef = document.getElementById('add-defense');
    if (addDef) addDef.innerText = pointsToAdd.defense;
    
    const addProd = document.getElementById('add-production');
    if (addProd) addProd.innerText = pointsToAdd.production;

    // Prévisualisation des stats
    const baseStr = <?= (int)$hero['stat_strength'] ?>;
    const baseOff = <?= (int)$hero['stat_offense_bonus'] ?>;
    const baseDef = <?= (int)$hero['stat_defense_bonus'] ?>;
    const eqStrength = <?= (int)$hero['effective']['equipment_strength'] ?>;

    const newCombatStr = 150 + ((baseStr + pointsToAdd.strength) * 80) + eqStrength;
    const newOffPct = Math.min(20, (baseOff + pointsToAdd.offense) * 0.2).toFixed(1);
    const newDefPct = Math.min(20, (baseDef + pointsToAdd.defense) * 0.2).toFixed(1);

    const effStr = document.getElementById('effectiveStrength');
    if (effStr) effStr.innerText = newCombatStr.toLocaleString();
    
    const effOff = document.getElementById('effectiveOffense');
    if (effOff) effOff.innerText = '+' + newOffPct + '%';
    
    const effDef = document.getElementById('effectiveDefense');
    if (effDef) effDef.innerText = '+' + newDefPct + '%';
}

async function submitAttributes() {
    const total = pointsToAdd.strength + pointsToAdd.offense + pointsToAdd.defense + pointsToAdd.production;
    if (total <= 0) {
        showModalAlert("Veuillez attribuer au moins un point d'attribut à votre Samouraï.", "warning", "Attributs");
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'allocate_points');
        formData.append('strength', pointsToAdd.strength);
        formData.append('offense', pointsToAdd.offense);
        formData.append('defense', pointsToAdd.defense);
        formData.append('production', pointsToAdd.production);

        const res = await fetch('/api/hero.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showModalAlert(data.message, "success", "Points de Samouraï Attribués");
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || "Impossible d'enregistrer les attributs.", "danger", "Erreur");
        }
    } catch (e) {
        showModalAlert("Une erreur est survenue lors de l'enregistrement.", "danger", "Erreur");
    }
}

async function changeProductionType(type) {
    try {
        const formData = new FormData();
        formData.append('action', 'set_production_type');
        formData.append('production_type', type);

        const res = await fetch('/api/hero.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showModalAlert(data.message, "success", "Orientation Modifiée");
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showModalAlert(data.error || "Impossible de modifier la production.", "danger", "Erreur");
        }
    } catch (e) {
        console.error("Erreur orientation:", e);
    }
}

async function executeStartAdventure(advId) {
    try {
        const formData = new FormData();
        formData.append('action', 'start_adventure');
        formData.append('adventure_id', advId);

        const res = await fetch('/api/hero.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showModalAlert(data.message, "success", "Départ en Aventure Féodale");
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || "Le Samouraï ne peut pas partir.", "warning", "Aventure Impossible");
        }
    } catch (e) {
        showModalAlert("Une erreur est survenue lors du départ.", "danger", "Erreur");
    }
}

async function executeReviveHero(planetId) {
    try {
        const formData = new FormData();
        formData.append('action', 'revive_hero');
        formData.append('planet_id', planetId);

        const res = await fetch('/api/hero.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showModalAlert(data.message, "success", "Régénération Sacrée Lancée");
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || "Impossible de régénérer le héros.", "danger", "Régénération Impossible");
        }
    } catch (e) {
        showModalAlert("Une erreur est survenue lors du rituel.", "danger", "Erreur");
    }
}

async function executeEquip(itemId) {
    try {
        const formData = new FormData();
        formData.append('action', 'equip_item');
        formData.append('item_id', itemId);

        const res = await fetch('/api/hero.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showModalAlert(data.message, "success", "Arsenal Modifié");
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showModalAlert(data.error || "Impossible d'équiper cet objet.", "danger", "Erreur");
        }
    } catch (e) {
        showModalAlert("Erreur lors de l'équipement.", "danger", "Erreur");
    }
}

async function executeUnequip(slot) {
    try {
        const formData = new FormData();
        formData.append('action', 'unequip_item');
        formData.append('slot', slot);

        const res = await fetch('/api/hero.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showModalAlert(data.message, "success", "Arsenal Modifié");
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showModalAlert(data.error || "Impossible de déséquiper.", "danger", "Erreur");
        }
    } catch (e) {
        showModalAlert("Erreur lors du déséquipement.", "danger", "Erreur");
    }
}

function openItemDetailModal(item) {
    const modal = document.getElementById('itemDetailModal');
    if (!modal || !item) return;

    document.getElementById('itemModalTitle').innerText = item.name || 'Relique Féodale';
    document.getElementById('itemModalImg').src = item.image_url || '/public/assets/hero_samurai.jpg';
    document.getElementById('itemModalDesc').innerText = item.description || '';

    const slotLabels = {
        'weapon': '🗡️ Arme de Poing',
        'helmet': '🪖 Casque Kabuto',
        'armor': '🥋 Armure O-Yoroi',
        'horse': '🐎 Monture & Destrier',
        'talisman': '📿 Talisman Shintō',
        'consumable': '🎋 Consommable Féodal'
    };
    const slotCode = item.slot || item.item_type || 'weapon';
    const slotName = slotLabels[slotCode] || slotCode || 'Relique';
    const isEquipped = item.is_equipped == 1 || item.is_equipped === true;

    document.getElementById('itemModalSubtitle').innerHTML = `
        <span class="badge bg-secondary-lt me-2">${slotName}</span>
        ${isEquipped ? '<span class="badge bg-primary text-white">✓ Équipé sur votre Samouraï</span>' : '<span class="badge bg-light text-muted">Dans le coffre</span>'}
    `;

    const bonusesContainer = document.getElementById('itemModalBonuses');
    bonusesContainer.innerHTML = '';
    let bData = item.bonus_data || {};
    if (typeof bData === 'string') {
        try { bData = JSON.parse(bData); } catch (e) { bData = {}; }
    }
    if (typeof bData === 'object' && bData !== null && Object.keys(bData).length > 0) {
        for (const [k, v] of Object.entries(bData)) {
            let badgeHtml = '';
            if (k === 'strength') badgeHtml = `<span class="badge bg-red-lt text-red border border-red-lt fs-5 px-2 py-1">+${v} Force</span>`;
            else if (k === 'offense_bonus') badgeHtml = `<span class="badge bg-orange-lt text-orange border border-orange-lt fs-5 px-2 py-1">+${v}% Attaque</span>`;
            else if (k === 'defense_bonus') badgeHtml = `<span class="badge bg-blue-lt text-blue border border-blue-lt fs-5 px-2 py-1">+${v}% Défense</span>`;
            else if (k === 'speed') badgeHtml = `<span class="badge bg-cyan-lt text-cyan border border-cyan-lt fs-5 px-2 py-1">+${v}% Vitesse</span>`;
            else if (k === 'exp_bonus') badgeHtml = `<span class="badge bg-purple-lt text-purple border border-purple-lt fs-5 px-2 py-1">+${v}% Exp</span>`;
            else if (k === 'production_rice') badgeHtml = `<span class="badge bg-green-lt text-green border border-green-lt fs-5 px-2 py-1">+${v} Riz/h</span>`;
            else if (k === 'production_wood') badgeHtml = `<span class="badge bg-teal-lt text-teal border border-teal-lt fs-5 px-2 py-1">+${v} Bois/h</span>`;
            else if (k === 'production_stone') badgeHtml = `<span class="badge bg-secondary-lt text-secondary border border-secondary-lt fs-5 px-2 py-1">+${v} Pierre/h</span>`;
            else if (k === 'cages_count') badgeHtml = `<span class="badge bg-success-lt text-success border border-success-lt fs-5 px-2 py-1 fw-bold">🎋 ${v} Cages disponibles</span>`;
            else badgeHtml = `<span class="badge bg-secondary-lt fs-5 px-2 py-1">+${v} ${k}</span>`;
            bonusesContainer.innerHTML += badgeHtml;
        }
    } else {
        bonusesContainer.innerHTML = '<span class="text-muted small fst-italic">Aucun attribut passif</span>';
    }

    const actionsContainer = document.getElementById('itemModalActions');
    if (slotCode === 'consumable' || item.item_type === 'consumable') {
        const cCount = (bData && bData.cages_count) ? bData.cages_count : 0;
        actionsContainer.innerHTML = `
            <div class="small text-muted">🎋 Consommable automatique lors des raids et attaques d'oasis (Stock : <strong>${cCount}</strong>)</div>
            <button type="button" class="btn btn-secondary" onclick="closeItemDetailModal()">
                Fermer
            </button>
        `;
    } else if (isEquipped) {
        actionsContainer.innerHTML = `
            <button type="button" class="btn btn-outline-danger" onclick="executeUnequip('${slotCode}')">
                Déséquiper
            </button>
            <button type="button" class="btn btn-secondary" onclick="closeItemDetailModal()">
                Fermer
            </button>
        `;
    } else {
        actionsContainer.innerHTML = `
            <button type="button" class="btn btn-primary" onclick="executeEquip(${item.id})">
                🗡️ Équiper cette Relique
            </button>
            <button type="button" class="btn btn-secondary" onclick="closeItemDetailModal()">
                Fermer
            </button>
        `;
    }

    modal.style.display = 'flex';
}

function closeItemDetailModal(e) {
    const modal = document.getElementById('itemDetailModal');
    if (modal && (!e || e.target.id === 'itemDetailModal')) {
        modal.style.display = 'none';
    }
}
</script>
