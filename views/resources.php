<?php
/**
 * Vue du Domaine Rural Féodal & 9 Parcelles Stratégiques (OpenShogun - Dorf 1)
 * Refonte majeure : Abandon de la grille de 40 parcelles au profit de 9 structures profondes uniques
 * Niveaux 20 à 100 avec potentiels procéduraux, carte panoramique 16:9 Grab-and-Pan et modale d'élévation Tabler.
 */
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/RuralPlotEngine.php';
require_once __DIR__ . '/../core/VillageGeneratorService.php';
require_once __DIR__ . '/../core/OasisEngine.php';
require_once __DIR__ . '/../core/PopulationEngine.php';
require_once __DIR__ . '/../core/AiPromptHelper.php';
require_once __DIR__ . '/../config/game_constants.php';

$buildingEngine = new BuildingEngine();
$planetEngine = new PlanetEngine();
$oasisEngine = new OasisEngine();
$ruralPlotEngine = new RuralPlotEngine();

$buildings = $planetEngine->getBuildings((int)$planet['id']);
$hqLevel = $buildings['hq'] ?? 1;
$queue = $buildingEngine->getQueue((int)$planet['id']);

// Récupération des 9 parcelles procédurales enrichies pour la planète active
$plots = $ruralPlotEngine->getEnrichedPlots((int)$planet['id'], $planet, $hqLevel);
$housingCap = $ruralPlotEngine->getVillageHousingCapacity((int)$planet['id']);
$maxPop = $housingCap['total_capacity'];

// Démographie et satisfaction féodale
$fields = $planetEngine->getFields((int)$planet['id']);
$workforce = PopulationEngine::calculateWorkforceSummary($planet, $buildings, $fields, $maxPop);
$activeFeast = $planetEngine->getActiveFeast((int)$planet['id']);
$contentment = PopulationEngine::calculateContentment($planet, $activeFeast, $workforce, $buildings);
$delinquency = $contentment['delinquency'] ?? PopulationEngine::calculateDelinquency($planet, $buildings, $workforce);

// Oasis annexées
$annexedOases = $oasisEngine->getAnnexedOasesForPlanet((int)$planet['id']);
$oasisBonuses = $oasisEngine->getTotalOasisBonusesForPlanet((int)$planet['id']);

$isTerran = (($user['faction'] ?? 'terran') === 'terran');
?>

<style>
/* Disposition générale du Domaine Rural */
.container {
    max-width: 1850px !important;
    width: 98% !important;
    margin: 1rem auto !important;
}

.grid-main {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 360px;
    gap: 1.5rem;
    align-items: start;
}

@media (max-width: 1200px) {
    .grid-main {
        grid-template-columns: 1fr;
    }
}

/* ========================================================
   CARTE ILLUSTRÉE PANORAMIQUE & VIEWPORT DRAG-TO-PAN 16:9
   ======================================================== */
.rural-viewport-wrapper {
    position: relative;
    width: 100%;
    height: 720px;
    background: #0f172a;
    border-radius: 12px;
    overflow: hidden;
    user-select: none;
    cursor: grab;
    border: 2px solid rgba(255, 255, 255, 0.1);
    box-shadow: inset 0 0 45px rgba(0, 0, 0, 0.7), 0 10px 30px rgba(0, 0, 0, 0.15);
}

.rural-viewport-wrapper.is-dragging {
    cursor: grabbing !important;
}

.rural-viewport-wrapper:fullscreen {
    height: 100vh !important;
    border-radius: 0 !important;
    border: none !important;
}

/* Scène panoramique 16:9 (1920×1080) */
.rural-stage {
    position: absolute;
    top: 0;
    left: 0;
    width: 1920px;
    height: 1080px;
    background-image: url('/public/assets/shogun_rural_terroir_9plots.jpg');
    background-size: 100% 100%;
    background-repeat: no-repeat;
    transform-origin: 0 0;
    will-change: transform;
}

.rural-stage.is-animating {
    transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.25, 1) !important;
}

/* Barre d'outils flottante du viewport */
.rural-map-toolbar {
    position: absolute;
    top: 14px;
    left: 14px;
    right: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    z-index: 50;
    pointer-events: none;
}

.rural-map-toolbar > * {
    pointer-events: auto;
}

.rural-controls-cluster {
    background: rgba(15, 23, 42, 0.88);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    padding: 0.25rem;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.rural-controls-cluster .btn {
    padding: 0.35rem 0.65rem;
    font-size: 0.78rem;
    font-weight: 700;
}

/* ========================================================
   BADGES & TUILES INTERACTIVES DES 9 STRUCTURES
   ======================================================== */
.rural-plot-badge {
    position: absolute;
    transform: translate(-50%, -50%);
    cursor: pointer;
    z-index: 25;
    transition: transform 0.22s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.2s ease;
    user-select: none;
}

.rural-plot-badge:hover {
    transform: translate(-50%, -50%) scale(1.12);
    z-index: 45;
}

.rural-badge-inner {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    background: rgba(15, 23, 42, 0.94);
    backdrop-filter: blur(8px);
    border: 2px solid var(--badge-color, #ffffff);
    border-radius: 50px;
    padding: 0.38rem 0.9rem 0.38rem 0.45rem;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.75), 0 0 16px var(--badge-glow, rgba(255, 255, 255, 0.3));
    color: #ffffff;
    min-width: 165px;
}

.rural-badge-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--badge-color, #ffffff);
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    font-weight: bold;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
}

.rural-badge-body {
    display: flex;
    flex-direction: column;
    min-width: 0;
    line-height: 1.15;
}

.rural-badge-name {
    font-size: 0.82rem;
    font-weight: 800;
    color: #ffffff;
    white-space: nowrap;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
}

.rural-badge-level-row {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-top: 0.2rem;
}

.rural-badge-level-pill {
    font-size: 0.72rem;
    font-weight: 800;
    color: var(--badge-color, #facc15);
    font-family: monospace;
}

.rural-badge-prod-pill {
    font-size: 0.68rem;
    font-weight: 700;
    color: #cbd5e1;
    background: rgba(255, 255, 255, 0.12);
    border-radius: 4px;
    padding: 0.05rem 0.35rem;
    white-space: nowrap;
}

.rural-badge-progress {
    height: 4px;
    background: rgba(255, 255, 255, 0.18);
    border-radius: 2px;
    overflow: hidden;
    margin-top: 0.25rem;
}

.rural-badge-progress-bar {
    height: 100%;
    background: var(--badge-color, #ffffff);
    transition: width 0.3s ease;
}

/* Chips de coût dans la modale */
.cost-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.65rem;
    border-radius: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.cost-chip.affordable {
    color: #10b981;
    border-color: rgba(16, 185, 129, 0.35);
    background: rgba(16, 185, 129, 0.1);
}

.cost-chip.missing {
    color: #ef4444;
    border-color: rgba(239, 68, 68, 0.35);
    background: rgba(239, 68, 68, 0.1);
}
</style>

<div class="container">

    <!-- En-tête féodal & Bannière du Domaine Rural -->
    <div class="card mb-3 shadow-sm" style="border-left: 4px solid #10b981;">
        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="mb-0 fs-2 fw-bold d-flex align-items-center gap-2">
                    <span>🌾</span> Domaine Rural &amp; Terroirs du Fief
                    <span class="badge bg-green-lt fw-bold font-monospace" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-map-location-dot me-1"></i>9 Domaines Uniques
                    </span>
                    <span class="badge bg-primary-lt fw-bold" style="font-size: 0.72rem;">
                        Progression Niveaux 20 à 100
                    </span>
                </h2>
                <div class="text-secondary small mt-1">
                    Gouvernance de <strong><?= htmlspecialchars($planet['name']) ?></strong> [<?= (int)$planet['coord_x'] ?>:<?= (int)$planet['coord_y'] ?>] &bull;
                    Capacité d'habitation : <strong class="text-primary"><?= number_format($maxPop) ?> villageois</strong> &bull;
                    Main-d'œuvre active : <strong><?= number_format($workforce['assigned_workers']) ?></strong> / <?= number_format($workforce['required_workers']) ?> requis
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="?page=city" class="btn btn-outline-danger btn-sm">
                    <i class="fa-solid fa-chess-rook me-1"></i> Cité Castrale (Dorf 2) &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Bannière de Synthèse des 9 Domaines Stratégiques -->
    <div class="card mb-3 shadow-sm">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 text-center text-sm-start">
                <?php
                $topKpis = [
                    ['icon' => 'fa-solid fa-tree text-success', 'name' => 'Bois', 'val' => '+' . number_format($planet['prod_rates']['metal']) . '/h'],
                    ['icon' => 'fa-solid fa-mountain text-secondary', 'name' => 'Pierre', 'val' => '+' . number_format($planet['prod_rates']['crystal']) . '/h'],
                    ['icon' => 'fa-solid fa-wheat-awn text-warning', 'name' => 'Riz', 'val' => '+' . number_format($planet['prod_rates']['deuterium']) . '/h'],
                    ['icon' => 'fa-solid fa-cubes-stacked text-orange', 'name' => 'Argile', 'val' => '+' . number_format($plots['fosse_argile']['prod_hourly']) . '/h'],
                    ['icon' => 'fa-solid fa-leaf text-teal', 'name' => 'Thé', 'val' => '+' . number_format($plots['culture_the']['prod_hourly']) . '/h'],
                    ['icon' => 'fa-solid fa-seedling text-lime', 'name' => 'Soja', 'val' => '+' . number_format($plots['champ_soja']['prod_hourly']) . '/h'],
                    ['icon' => 'fa-solid fa-torii-gate text-danger', 'name' => 'Sérénité', 'val' => '+' . number_format($plots['sanctuaire_shinto']['prod_hourly'])],
                    ['icon' => 'fa-solid fa-people-roof text-indigo', 'name' => 'Logements', 'val' => number_format($maxPop) . ' places'],
                ];
                foreach ($topKpis as $kpi):
                ?>
                    <div class="p-1 px-2 rounded bg-surface-secondary border d-flex align-items-center gap-2" style="font-size:0.8rem;">
                        <i class="<?= $kpi['icon'] ?> fs-3"></i>
                        <div>
                            <div class="text-secondary small fw-bold" style="font-size:0.7rem;"><?= $kpi['name'] ?></div>
                            <strong class="text-dark"><?= $kpi['val'] ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Grille Principale (Scène Interactive à Gauche, Chantiers & Troupes à Droite) -->
    <div class="grid-main">

        <!-- ========================================================
             COLONNE GAUCHE : CARTE ILLUSTRÉE PANORAMIQUE 16:9
             ======================================================== -->
        <div>
            <div class="rural-viewport-wrapper" id="ruralViewport">

                <!-- Barre d'outils flottante du viewport -->
                <div class="rural-map-toolbar">
                    <div class="d-flex align-items-center gap-2">
                        <?= AiPromptHelper::renderBadge('shogun_rural_terroir_9plots.jpg', 'Panorama Stratégique des 9 Parcelles Féodales', '/public/assets/shogun_rural_terroir_9plots.jpg', '', true) ?>
                        <span class="badge bg-dark-lt text-white d-none d-lg-inline-block shadow-sm">
                            <i class="fa-solid fa-arrows-up-down-left-right me-1 text-warning"></i> Glisser pour explorer &bull; Molette pour zoomer
                        </span>
                    </div>

                    <div class="rural-controls-cluster">
                        <button type="button" class="btn btn-dark text-white" onclick="zoomRuralMap(0.18)" title="Zoomer avant (+)">
                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                        </button>
                        <button type="button" class="btn btn-dark text-white font-monospace" onclick="resetRuralMapZoom()" title="Ajuster la vue">
                            <span id="ruralZoomIndicator">100%</span>
                        </button>
                        <button type="button" class="btn btn-dark text-white" onclick="zoomRuralMap(-0.18)" title="Zoomer arrière (-)">
                            <i class="fa-solid fa-magnifying-glass-minus"></i>
                        </button>
                        <button type="button" class="btn btn-dark text-white" onclick="centerRuralMap()" title="Recentrer le fief">
                            <i class="fa-solid fa-crosshairs"></i>
                        </button>
                        <button type="button" class="btn btn-dark text-white" onclick="toggleRuralFullscreen()" title="Plein écran (⛶)">
                            <i class="fa-solid fa-expand"></i>
                        </button>
                    </div>
                </div>

                <!-- Scène interactive 16:9 contenant l'illustration HD et les 9 badges -->
                <div class="rural-stage" id="ruralStage">

                    <?php foreach ($plots as $type => $p): ?>
                        <?php
                            $isMax = !empty($p['is_max']);
                            $canAfford = !empty($p['can_afford']);
                            $cost = $p['cost'];
                            $tooltip = '<strong>' . htmlspecialchars($p['name']) . '</strong> (Niv. ' . $p['level'] . ' / ' . $p['max_level'] . ')<br>' .
                                       '<span class="text-warning">' . htmlspecialchars($p['prod_label']) . '</span><br>' .
                                       '<small class="text-muted">' . htmlspecialchars($p['worker_role']) . ' : ' . $p['workers_assigned'] . ' ouvriers<br><em>Cliquer pour gérer &amp; élever</em></small>';
                        ?>
                        <div class="rural-plot-badge"
                             id="rural-badge-<?= $type ?>"
                             style="left: <?= $p['pos_x'] ?>%; top: <?= $p['pos_y'] ?>%; --badge-color: <?= $p['color'] ?>; --badge-glow: <?= $p['color'] ?>80;"
                             data-type="<?= $type ?>"
                             data-name="<?= htmlspecialchars($p['name']) ?>"
                             data-jp-name="<?= htmlspecialchars($p['jp_name']) ?>"
                             data-desc="<?= htmlspecialchars($p['desc']) ?>"
                             data-level="<?= $p['level'] ?>"
                             data-max-level="<?= $p['max_level'] ?>"
                             data-next-level="<?= $p['next_level'] ?>"
                             data-is-max="<?= $isMax ? '1' : '0' ?>"
                             data-workers="<?= $p['workers_assigned'] ?>"
                             data-worker-role="<?= htmlspecialchars($p['worker_role']) ?>"
                             data-prod-label="<?= htmlspecialchars($p['prod_label']) ?>"
                             data-next-prod-label="<?= htmlspecialchars($p['next_prod_label']) ?>"
                             data-tile-img="<?= htmlspecialchars($p['tile_img']) ?>"
                             data-bg-img="<?= htmlspecialchars($p['bg_image']) ?>"
                             data-cost-metal="<?= (int)$cost['metal'] ?>"
                             data-cost-crystal="<?= (int)$cost['crystal'] ?>"
                             data-cost-clay="<?= (int)$cost['clay'] ?>"
                             data-cost-deuterium="<?= (int)$cost['deuterium'] ?>"
                             data-duration="<?= (int)$p['duration'] ?>"
                             data-can-afford="<?= $canAfford ? '1' : '0' ?>"
                             data-color-class="<?= $p['color_class'] ?>"
                             data-color="<?= $p['color'] ?>"
                             data-icon="<?= $p['icon'] ?>"
                             data-progress-pct="<?= $p['progress_pct'] ?>"
                             data-bs-toggle="tooltip"
                             data-bs-html="true"
                             data-bs-placement="top"
                             title="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>"
                             onclick="handleRuralPinClick(this, event)">

                            <div class="rural-badge-inner">
                                <div class="rural-badge-avatar">
                                    <i class="<?= $p['icon'] ?>"></i>
                                </div>
                                <div class="rural-badge-body">
                                    <div class="rural-badge-name"><?= htmlspecialchars($p['name']) ?></div>
                                    <div class="rural-badge-level-row">
                                        <span class="rural-badge-level-pill">Niv. <?= $p['level'] ?> / <?= $p['max_level'] ?></span>
                                        <span class="rural-badge-prod-pill"><?= htmlspecialchars($p['prod_label']) ?></span>
                                    </div>
                                    <div class="rural-badge-progress">
                                        <div class="rural-badge-progress-bar" style="width: <?= $p['progress_pct'] ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>

        <!-- ========================================================
             COLONNE DROITE : CHANTIERS, RÉCOLTES & GARNISONS
             ======================================================== -->
        <div class="d-flex flex-column gap-3">
            <!-- Didacticiel Féodal & Quêtes du Daimyō -->
            <?php require __DIR__ . '/partials/quest_banner.php'; ?>

            <!-- File Urbaine : Chantiers en cours -->
            <div class="card shadow-sm">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0 fs-3">
                        <i class="fa-solid fa-helmet-safety me-2 text-warning"></i>Chantiers Urbains
                    </h3>
                    <span class="badge bg-warning-lt fw-bold"><?= count($queue) ?> en cours</span>
                </div>
                <div class="card-body p-2">
                    <?php if (empty($queue)): ?>
                        <p style="color:var(--text-muted); font-size:0.85rem; text-align:center; padding:1rem 0; margin-bottom:0;">
                            <i class="fa-solid fa-helmet-safety text-secondary d-block mb-1 fs-2"></i>
                            Aucune construction urbaine en cours.
                        </p>
                    <?php else: ?>
                        <?php foreach ($queue as $q): ?>
                            <?php
                                if ($q['build_category'] === 'field') {
                                    $tSlot = (int)$q['target_id'];
                                    $tType = FIELD_LAYOUT[$tSlot] ?? 'metal_mine';
                                    $name = (FIELD_TYPES[$tType]['name'] ?? 'Parcelle') . " #{$tSlot}";
                                } else {
                                    $name = BUILDINGS[$q['target_id']]['name'] ?? $q['target_id'];
                                }
                                $qNow = time();
                                $qStart = (int)($q['started_at'] ?? $qNow);
                                $qEnd = (int)($q['finishes_at'] ?? $qNow);
                                $qTotal = max(1, $qEnd - $qStart);
                                $qElapsed = max(0, $qNow - $qStart);
                                $qPct = min(100, max(0, (int)round(($qElapsed / $qTotal) * 100)));
                                $isDemolish = ((int)$q['target_level'] === 0);
                            ?>
                            <div class="queue-item p-2 mb-2 rounded bg-surface-secondary border" style="display: flex; flex-direction: column; align-items: stretch; gap: 0.4rem; padding: 0.75rem;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="queue-info">
                                        <h4 class="mb-0 fw-bold" style="font-size:0.9rem;"><?= htmlspecialchars($name) ?></h4>
                                        <?php if ($isDemolish): ?>
                                            <span class="badge bg-danger-lt fw-bold" style="font-size:0.7rem;"><i class="fa-solid fa-trash-can me-1"></i>Démolition</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt" style="font-size:0.7rem;">Niveau <?= $q['target_level'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="queue-progress-box mt-1">
                                    <div class="progress" style="height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-<?= $isDemolish ? 'danger' : 'warning' ?> building-progress-bar"
                                             role="progressbar"
                                             style="width: <?= $qPct ?>%;"
                                             aria-valuenow="<?= $qPct ?>"
                                             aria-valuemin="0"
                                             aria-valuemax="100"></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 0.75rem;">
                                        <span class="text-secondary fw-semibold">Avancement : <strong class="text-dark building-progress-pct"><?= $qPct ?>%</strong></span>
                                        <span class="queue-timer font-monospace fw-bold text-danger building-time-remaining" data-countdown="<?= $qEnd ?>">En cours</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bilan des Récoltes & Oasis Annexées -->
            <div class="card shadow-sm">
                <div class="card-header py-2">
                    <h3 class="card-title mb-0 fs-3">
                        <i class="fa-solid fa-chart-line me-2 text-success"></i>Récoltes &amp; Oasis
                    </h3>
                </div>
                <div class="card-body p-3" style="font-size:0.85rem;">
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fa-solid fa-tree text-success me-1"></i> Bois de Cèdre :</span>
                        <strong class="text-success">+<?= number_format($planet['prod_rates']['metal']) ?> / h</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fa-solid fa-mountain text-secondary me-1"></i> Pierre de Taille :</span>
                        <strong class="text-secondary">+<?= number_format($planet['prod_rates']['crystal']) ?> / h</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fa-solid fa-wheat-awn text-warning me-1"></i> Riz Impérial :</span>
                        <strong class="text-warning">+<?= number_format($planet['prod_rates']['deuterium']) ?> / h</strong>
                    </div>

                    <?php if (!empty($annexedOases)): ?>
                        <hr class="my-2">
                        <div class="fw-bold text-success mb-2" style="font-size:0.78rem;">
                            <i class="fa-solid fa-seedling me-1"></i> Oasis Annexées (<?= count($annexedOases) ?> / 3) :
                        </div>
                        <?php foreach ($annexedOases as $ao): ?>
                            <div class="d-flex justify-content-between align-items-center p-1 rounded bg-surface-secondary mb-1" style="font-size:0.75rem;">
                                <span><?= htmlspecialchars($ao['name']) ?> [<?= $ao['coord_x'] ?>:<?= $ao['coord_y'] ?>]</span>
                                <span class="badge bg-success-lt">+<?= $ao['bonus_rice'] ?? 25 ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Panel des Troupes & Garnisons -->
            <?php require __DIR__ . '/partials/troops_panel.php'; ?>
        </div>
    </div>
</div>

<!-- ========================================================
     MODALE D'AMÉLIORATION INTERACTIVE D'UNE PARCELLE (TABLER)
     ======================================================== -->
<div class="modal fade" id="plotUpgradeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header py-2 bg-dark text-white">
                <h4 class="modal-title d-flex align-items-center gap-2 mb-0 fs-3">
                    <span id="modalPlotIconAvatar" class="avatar avatar-sm rounded text-white bg-primary"></span>
                    <div>
                        <span id="modalPlotTitle">Parcelle</span>
                        <div class="text-muted font-monospace" style="font-size:0.72rem;" id="modalPlotJpSubtitle"></div>
                    </div>
                </h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body p-3">
                <!-- Visuel & Statut -->
                <div class="d-flex gap-3 mb-3">
                    <div id="modalPlotVisualThumb" class="rounded border shadow-sm flex-shrink-0" style="width: 100px; height: 100px; background-size: cover; background-position: center;"></div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary fw-bold" id="modalPlotLevelBadge">Niveau 1 / 30</span>
                        </div>
                        <p class="text-muted mb-2" style="font-size:0.82rem;" id="modalPlotDesc"></p>
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size:0.78rem;">
                            <i class="fa-solid fa-person-digging text-warning"></i>
                            <span><strong id="modalPlotWorkerRole">Ouvriers</strong> : <strong class="text-body" id="modalPlotWorkersCount">2</strong> requis</span>
                        </div>
                    </div>
                </div>

                <!-- Jauge de Potentiel Vertical (20 à 100) -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.75rem;">
                        <span class="text-muted fw-bold">POTENTIEL FÉODAL DE LA PARCELLE</span>
                        <span class="text-primary font-monospace fw-bold" id="modalPlotPotentialText">35%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" id="modalPlotPotentialBar" role="progressbar" style="width: 35%;"></div>
                    </div>
                </div>

                <!-- Bénéfices / Production -->
                <div class="p-2 mb-3 rounded bg-surface-secondary border">
                    <div class="d-flex justify-content-between align-items-center" style="font-size:0.85rem;">
                        <span class="text-muted"><i class="fa-solid fa-chart-line text-success me-1"></i> Rendement / Apport actuel :</span>
                        <strong class="text-success" id="modalPlotCurrentProd">+35 / h</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1" style="font-size:0.85rem;">
                        <span class="text-muted"><i class="fa-solid fa-arrow-trend-up text-primary me-1"></i> Prochain niveau :</span>
                        <strong class="text-primary" id="modalPlotNextProd">+55 / h</strong>
                    </div>
                </div>

                <!-- Coûts requis pour l'élévation -->
                <div class="mb-3">
                    <label class="form-label mb-2 fw-bold text-muted" style="font-size:0.75rem;">COÛTS REQUIS POUR L'ÉLÉVATION :</label>
                    <div class="d-flex flex-wrap gap-2" id="modalPlotCostTags"></div>
                </div>

                <!-- Durée estimée du chantier -->
                <div class="d-flex justify-content-between align-items-center text-muted mb-3" style="font-size:0.8rem;">
                    <span><i class="fa-regular fa-clock me-1"></i> Durée estimée du chantier :</span>
                    <strong class="font-monospace text-body" id="modalPlotDuration">00:01:30</strong>
                </div>

                <!-- Bouton d'action -->
                <div id="modalPlotActionContainer">
                    <button type="button" class="btn btn-primary w-100" id="btnModalPlotUpgrade">
                        <i class="fa-solid fa-arrow-up me-1"></i> Élever la structure
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.RURAL_CONFIG = {
    stocks: {
        metal: <?= (int)($planet['metal'] ?? 0) ?>,
        crystal: <?= (int)($planet['crystal'] ?? 0) ?>,
        deuterium: <?= (int)($planet['deuterium'] ?? 0) ?>
    }
};
</script>
<script src="/public/js/rural_domain_map.js?v=<?= file_exists(__DIR__ . '/../public/js/rural_domain_map.js') ? filemtime(__DIR__ . '/../public/js/rural_domain_map.js') : time() ?>"></script>
